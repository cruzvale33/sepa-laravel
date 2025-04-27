<?php

namespace SepaLaravel\SepaLaravel\Tests\Unit\Entities;

use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SepaLaravel\SepaLaravel\Entities\Creditor;
use SepaLaravel\SepaLaravel\Exceptions\SepaException;
use SepaLaravel\SepaLaravel\Tests\TestCase;

class CreditorTest extends TestCase
{
    public static function validIdentifierProvider(): array
    {
        return [
            ['ES00-COMPANY-12345678A'],
            ['ES12-SHORT-12345678'],
            ['ES99-LONGERNAME-12345678X'],
            ['ES01-COMPANY123'],
        ];
    }

    public static function invalidIdentifierProvider(): array
    {
        return [
            'empty' => ['', 'Creditor identifier is required'],
            'too long' => ['ES00-'.str_repeat('A', 32), 'Creditor identifier must be 35 characters or less'],
            'invalid format' => ['INVALID123', 'Creditor identifier must follow format ESXX-SUFIJO-NIF or similar'],
        ];
    }

    public static function invalidIbanProvider(): array
    {
        return [
            'too short' => [
                'ES180182000000000000000',
                'Spanish IBAN must be 24 characters long',
            ],
        ];
    }

    #[Test]
    #[DataProvider('validIdentifierProvider')]
    public function it_accepts_valid_identifiers(string $identifier)
    {
        $creditor = new Creditor(
            $identifier,
            new DateTime('+1 day'),
            'Valid Name',
            'ES1801820000000000000000',
            'BBVAESMMXXX',
            'ES1801820000000000000000',
        );
        $this->assertEquals($identifier, $creditor->getIdentifier());
    }

    #[Test]
    #[DataProvider('invalidIdentifierProvider')]
    public function it_rejects_invalid_identifiers(string $invalidId, string $expectedMessage)
    {
        $this->expectException(SepaException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Creditor(
            $invalidId,
            new DateTime('+1 day'),
            'Valid Name',
            'ES1801820000000000000000',
            'BBVAESMMXXX',
            'ES1801820000000000000000',
        );
    }

    #[Test]
    public function it_requires_non_empty_name()
    {
        $this->expectException(SepaException::class);
        new Creditor(
            'ES00-TEST',
            new DateTime('+1 day'),
            '',
            'ES1801820000000000000000',
            'BBVAESMMXXX',
            'ES1801820000000000000000',
        );
    }

    #[Test]
    #[DataProvider('invalidIbanProvider')]
    public function it_validates_iban_format(string $invalidIban, string $expectedMessage)
    {
        $this->expectException(SepaException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Creditor(
            'ES00-COMPANY123',
            new DateTime('+1 day'),
            'Valid Name',
            $invalidIban,
            'BBVAESMMXXX',
            'ES50000B01958115'
        );
    }

    #[Test]
    public function it_rejects_past_collection_date()
    {
        $this->expectException(SepaException::class);
        new Creditor(
            'ES00-TEST',
            new DateTime('-1 day'),
            'Valid Name',
            'ES1801820000000000000000',
            'BBVAESMMXXX',
            'ES1801820000000000000000',
        );
    }
}
