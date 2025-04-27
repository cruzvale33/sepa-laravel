<?php

namespace SepaLaravel\SepaLaravel\Entities;

use SepaLaravel\SepaLaravel\Exceptions\SepaException;

class Debtor
{
    protected $name;

    protected $iban;

    protected $bic;

    public function __construct(string $name, string $iban, string $bic)
    {
        $this->validateName($name);
        $this->validateIban($iban);
        $this->validateBic($bic);

        $this->name = $name;
        $this->iban = $iban;
        $this->bic = $bic;
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new SepaException('Debtor name is required');
        }

        if (strlen($name) > 70) {
            throw new SepaException('Debtor name must be 70 characters or less');
        }

        if (! preg_match('/^[a-zA-Z0-9\sáéíóúÁÉÍÓÚñÑüÜ\-\'\.\,]+$/', $name)) {
            throw new SepaException('Debtor name contains invalid characters');
        }
    }

    private function validateIban(string $iban): void
    {
        $iban = str_replace(' ', '', $iban);

        if (empty($iban)) {
            throw new SepaException('Debtor IBAN is required');
        }

        // Validación general de IBAN
        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
            throw new SepaException('Invalid IBAN format');
        }

        // Validación específica para IBAN español (opcional)
        if (str_starts_with($iban, 'ES')) {
            if (! preg_match('/^ES\d{22}$/', $iban)) {
                throw new SepaException('Spanish IBAN must be 24 characters long');
            }
        }
    }

    private function validateBic(string $bic): void
    {
        $bic = strtoupper(str_replace(' ', '', $bic));

        if (empty($bic)) {
            throw new SepaException('Debtor BIC is required');
        }

        // Validación formato BIC/SWIFT (8 u 11 caracteres)
        if (! preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic)) {
            throw new SepaException('Invalid BIC format. Must be 8 or 11 characters');
        }
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
}
