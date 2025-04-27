<?php

namespace SepaLaravel\SepaLaravel\Tests\Unit\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SepaLaravel\SepaLaravel\Entities\Presenter;
use SepaLaravel\SepaLaravel\Exceptions\SepaException;
use SepaLaravel\SepaLaravel\Tests\TestCase;

class PresenterTest extends TestCase
{
    // Casos de prueba para identificadores válidos
    public static function validIdentifierProvider(): array
    {
        return [
            'Formato estándar' => ['ES00-COMPANY-12345678A'],
            'Sin letra final' => ['ES12-SHORT-12345678'],
            'Con letra final' => ['ES99-LONGERNAME-12345678X'],
            'Solo sufijo' => ['ES01-COMPANY123'],
            'Múltiples guiones' => ['ES45-COMP-ANY-12345678B'],
        ];
    }

    // Casos de prueba para identificadores inválidos
    public static function invalidIdentifierProvider(): array
    {
        return [
            'Vacío' => [
                '',
                'Presenter identifier is required',
            ],
            'Demasiado largo' => [
                'ES00-'.str_repeat('A', 31), // 36 caracteres
                'Presenter identifier must be 35 characters or less',
            ],
            'Falta ES' => [
                '00-COMPANY-12345678A',
                'Presenter identifier must follow format ESXX-SUFIJO-NIF or similar',
            ],
            'Falta dígitos' => [
                'ESXX-COMPANY-12345678A',
                'Presenter identifier must follow format ESXX-SUFIJO-NIF or similar',
            ],
            'Caracteres inválidos' => [
                'ES00-COMPAÑY-12345678',
                'Presenter identifier must follow format ESXX-SUFIJO-NIF or similar',
            ],
        ];
    }

    // Casos de prueba para oficinas BBVA inválidas
    public static function invalidOfficeProvider(): array
    {
        return [
            '0000' => ['0000'],
            '9999' => ['9999'],
            'Letras' => ['ABCD'],
            'Corto' => ['123'],
            'Largo' => ['12345'],
        ];
    }

    #[Test]
    #[DataProvider('validIdentifierProvider')]
    public function it_accepts_valid_identifier_formats(string $identifier)
    {
        $presenter = new Presenter($identifier, 'Valid Name', '0182');
        $this->assertEquals($identifier, $presenter->getIdentifier());
    }

    #[Test]
    #[DataProvider('invalidIdentifierProvider')]
    public function it_rejects_invalid_identifiers(string $invalidIdentifier, string $expectedMessage)
    {
        $this->expectException(SepaException::class);
        $this->expectExceptionMessage($expectedMessage);
        new Presenter($invalidIdentifier, 'Valid Name', '0182');
    }

    #[Test]
    public function it_accepts_valid_name()
    {
        $presenter = new Presenter('ES00-COMPANY-12345678A', 'Nombre válido', '0182');
        $this->assertEquals('Nombre válido', $presenter->getName());
    }

    #[Test]
    public function it_rejects_empty_name()
    {
        $this->expectException(SepaException::class);
        $this->expectExceptionMessage('Presenter name is required');
        new Presenter('ES00-COMPANY-12345678A', '', '0182');
    }

    #[Test]
    public function it_rejects_long_name()
    {
        $this->expectException(SepaException::class);
        $this->expectExceptionMessage('Presenter name must be 70 characters or less');
        new Presenter('ES00-COMPANY-12345678A', str_repeat('A', 71), '0182');
    }

    #[Test]
    public function it_accepts_valid_bbva_office()
    {
        $presenter = new Presenter('ES00-COMPANY-12345678A', 'Valid Name', '0182');
        $this->assertEquals('0182', $presenter->getBbvaOffice());
    }

    #[Test]
    #[DataProvider('invalidOfficeProvider')]
    public function it_rejects_invalid_bbva_offices(string $invalidOffice)
    {
        $this->expectException(SepaException::class);
        $this->expectExceptionMessage('BBVA office must be a 4-digit number between 0001 and 9998');
        new Presenter('ES00-COMPANY-12345678A', 'Valid Name', $invalidOffice);
    }

    #[Test]
    public function it_provides_access_to_all_properties()
    {
        $identifier = 'ES00-COMPANY-12345678A';
        $name = 'Mi Empresa S.L.';
        $office = '0182';

        $presenter = new Presenter($identifier, $name, $office);

        $this->assertEquals($identifier, $presenter->getIdentifier());
        $this->assertEquals($name, $presenter->getName());
        $this->assertEquals($office, $presenter->getBbvaOffice());
    }
}
