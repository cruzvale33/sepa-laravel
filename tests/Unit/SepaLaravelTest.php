<?php

namespace SepaLaravel\SepaLaravel\Tests\Unit;

use DateTime;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use SepaLaravel\SepaLaravel\Entities\Creditor;
use SepaLaravel\SepaLaravel\Entities\Debtor;
use SepaLaravel\SepaLaravel\Entities\Payment;
use SepaLaravel\SepaLaravel\Entities\Presenter;
use SepaLaravel\SepaLaravel\Exceptions\SepaException;
use SepaLaravel\SepaLaravel\SepaLaravel;
use SepaLaravel\SepaLaravel\Tests\TestCase;

class SepaLaravelTest extends TestCase
{
    private function createValidPresenter(): Presenter
    {
        return new Presenter(
            'ES00-COMPANY123-12345678X', // Identificador válido
            'Mi Empresa S.L.',
            '0182'
        );
    }

    private function createValidCreditor(): Creditor
    {
        return new Creditor(
            'ES00-COMPANY123-12345678X', // Identificador válido
            new DateTime('+5 day'),
            'Mi Empresa S.L.',
            'ES9121000418450200051332',
            'BBVAESMMXXX',
            'ES50000B01958115',
        );
    }

    private function createValidDebtor(): Debtor
    {
        return new Debtor(
            'Cliente Ejemplo',
            'ES7620770024003102575766',
            'BSCHESMMXXX'
        );
    }

    private function createValidPayment(): Payment
    {
        return new Payment(
            $this->createValidDebtor(),
            'MANDATE-123456',
            new DateTime('2023-01-15'),
            'RCUR',
            'INV-2023-001',
            150.50,
            'Factura enero 2023'
        );
    }

    #[Test]
    public function it_generates_valid_sepa_xml()
    {
        $sepa = new SepaLaravel;
        $sepa->setPresenter($this->createValidPresenter())
            ->setCreditor($this->createValidCreditor())
            ->addPayment($this->createValidPayment());

        $xml = $sepa->generateXml();

        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('Mi Empresa S.L.', $xml);
        $this->assertStringContainsString('150.50', $xml);
        $this->assertStringContainsString('pain.008.001.02', $xml);

        return $xml;
    }

    #[Test]
    #[Depends('it_generates_valid_sepa_xml')]
    public function it_validates_xml_structure(string $xml)
    {
        $dom = new \DOMDocument;
        $dom->loadXML($xml);

        // Opción 1: Validar contra XSD real (descargarlo primero)
        // $xsd = file_get_contents(__DIR__.'/pain.008.001.02.xsd');

        // Opción 2: Validación básica de estructura
        $this->assertStringContainsString('<CstmrDrctDbtInitn>', $xml);
        $this->assertStringContainsString('</CstmrDrctDbtInitn>', $xml);
        $this->assertStringContainsString('<GrpHdr>', $xml);
        $this->assertStringContainsString('<PmtInf>', $xml);

        // Opción 3: Validación con DOM (sin XSD)
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');

        $this->assertGreaterThan(0, $xpath->query('//s:GrpHdr/s:MsgId')->length);
        $this->assertGreaterThan(0, $xpath->query('//s:GrpHdr/s:CreDtTm')->length);
        $this->assertGreaterThan(0, $xpath->query('//s:DrctDbtTxInf')->length);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $this->expectException(SepaException::class);

        $sepa = new SepaLaravel;
        $sepa->generateXml(); // Missing required data
    }

    #[Test]
    public function it_calculates_correct_control_sum()
    {
        $sepa = new SepaLaravel;
        $sepa->setPresenter($this->createValidPresenter())
            ->setCreditor($this->createValidCreditor())
            ->addPayment($this->createValidPayment())
            ->addPayment(new Payment(
                $this->createValidDebtor(),
                'MANDATE-789012',
                new DateTime('2023-01-15'),
                'RCUR',
                'INV-2023-002',
                200.00,
                'Factura febrero 2023'
            ));

        $xml = $sepa->generateXml();

        $this->assertStringContainsString('350.50', $xml);
        $this->assertStringContainsString('<NbOfTxs>2</NbOfTxs>', $xml);
    }

    #[Test]
    public function it_handles_multiple_payments_correctly()
    {
        $sepa = new SepaLaravel;
        $sepa->setPresenter($this->createValidPresenter())
            ->setCreditor($this->createValidCreditor());

        // Add 5 payments
        for ($i = 1; $i <= 5; $i++) {
            $sepa->addPayment(new Payment(
                $this->createValidDebtor(),
                "MANDATE-$i",
                new DateTime('2023-01-15'),
                'RCUR',
                "INV-2023-00$i",
                100.00 * $i,
                "Factura $i"
            ));
        }

        $xml = $sepa->generateXml();

        $this->assertStringContainsString('<NbOfTxs>5</NbOfTxs>', $xml);
        $this->assertStringContainsString('1500.00', $xml); // Sum of 100+200+300+400+500
        $this->assertEquals(5, substr_count($xml, '<DrctDbtTxInf>'));
    }
}
