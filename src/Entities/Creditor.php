<?php

namespace Sepalaravel\SepaLaravel\Entities;

use DateTime;
use SepaLaravel\SepaLaravel\Exceptions\SepaException;

class Creditor
{
    protected $identifier;

    protected $collectionDate;

    protected $name;

    protected $iban;

    protected $bic;

    protected $schemeId;

    public function __construct(string $identifier, DateTime $collectionDate, string $name, string $iban, string $bic, string $schemeId)
    {
        $this->validateIdentifier($identifier);
        // $this->validateCollectionDate($collectionDate);
        $this->validateName($name);
        $this->validateIban($iban);
        $this->validateBic($bic);

        $this->identifier = $identifier;
        $this->collectionDate = $collectionDate;
        $this->name = $name;
        $this->iban = $iban;
        $this->bic = $bic;
        $this->schemeId = $schemeId;
    }

    private function validateIdentifier(string $identifier): void
    {
        if (empty($identifier)) {
            throw new SepaException('Creditor identifier is required');
        }

        if (strlen($identifier) > 35) {
            throw new SepaException('Creditor identifier must be 35 characters or less');
        }

        if (! preg_match('/^ES\d{2}[A-Z0-9]+([A-Z0-9]+)*$/', $identifier)) {
            throw new SepaException('Creditor identifier must follow format ESXX-SUFIJO-NIF or similar');
        }
    }

    private function validateCollectionDate(DateTime $date): void
    {
        if ($date < new DateTime) {
            throw new SepaException('Collection date must be in the future');
        }
    }

    private function validateName(string $name): void
    {
        if (empty($name)) {
            throw new SepaException('Creditor name is required');
        }

        if (strlen($name) > 70) {
            throw new SepaException('Creditor name must be 70 characters or less');
        }
    }

    private function validateIban(string $iban): void
    {
        $iban = str_replace(' ', '', $iban);

        if (empty($iban)) {
            throw new SepaException('Creditor IBAN is required');
        }

        // Validación general de IBAN
        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
            throw new SepaException('Invalid IBAN format');
        }

        // Validación específica para IBAN español
        if (str_starts_with($iban, 'ES')) {
            if (! preg_match('/^ES\d{22}$/', $iban)) {
                throw new SepaException('Spanish IBAN must be 24 characters long');
            }
        }
    }

    private function validateBic(string $bic): void
    {
        if (empty($bic)) {
            throw new SepaException('Creditor BIC is required');
        }

        if (! preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic)) {
            throw new SepaException('Invalid BIC format');
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getCollectionDate(): DateTime
    {
        return $this->collectionDate;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getBic(): string
    {
        return $this->bic;
    }

    public function getSchemeId(): string
    {
        return $this->schemeId;
    }
}
