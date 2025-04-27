<?php

namespace SepaLaravel\SepaLaravel\Entities;

use DateTime;
use SepaLaravel\SepaLaravel\Exceptions\SepaException;

class Payment
{
    protected $debtor;

    protected $mandateId;

    protected $mandateDate;

    protected $sequenceType;

    protected $endToEndId;

    protected $amount;

    protected $concept;

    public function __construct(
        Debtor $debtor,
        string $mandateId,
        DateTime $mandateDate,
        string $sequenceType,
        string $endToEndId,
        float $amount,
        string $concept = ''
    ) {

        $this->validateMandateId($mandateId);
        $this->validateMandateDate($mandateDate);
        $this->validateSequenceType($sequenceType);
        $this->validateEndToEndId($endToEndId);
        $this->validateAmount($amount);
        $this->validateConcept($concept);

        $this->debtor = $debtor;
        $this->mandateId = $mandateId;
        $this->mandateDate = $mandateDate;
        $this->sequenceType = $sequenceType;
        $this->endToEndId = $endToEndId;
        $this->amount = $amount;
        $this->concept = $concept;
    }

    private function validateMandateId(string $mandateId): void
    {
        if (empty($mandateId)) {
            throw new SepaException('Mandate ID is required');
        }

        if (strlen($mandateId) > 35) {
            throw new SepaException('Mandate ID must be 35 characters or less');
        }

        if (! preg_match('/^[A-Z0-9\-]+$/', $mandateId)) {
            throw new SepaException('Mandate ID contains invalid characters. Only letters, numbers and hyphens are allowed');
        }
    }

    private function validateMandateDate(DateTime $mandateDate): void
    {
        if ($mandateDate > new DateTime) {
            throw new SepaException('Mandate date cannot be in the future');
        }

        // Para recibos migrados a adeudos directos (según el Excel)
        $migrationDate = DateTime::createFromFormat('d.m.Y', '31.10.2009');
        if ($mandateDate < $migrationDate) {
            throw new SepaException('Mandate date cannot be before 31.10.2009');
        }
    }

    private function validateSequenceType(string $sequenceType): void
    {
        $validTypes = ['RCUR', 'OOFF', 'FRST', 'FNAL'];

        if (! in_array($sequenceType, $validTypes)) {
            throw new SepaException(sprintf(
                'Invalid sequence type. Must be one of: %s',
                implode(', ', $validTypes)
            ));
        }
    }

    private function validateEndToEndId(string $endToEndId): void
    {
        if (empty($endToEndId)) {
            throw new SepaException('End-to-end ID is required');
        }

        if (strlen($endToEndId) > 35) {
            throw new SepaException('End-to-end ID must be 35 characters or less');
        }

        if (! preg_match('/^[A-Z0-9\-]+$/', $endToEndId)) {
            throw new SepaException('End-to-end ID contains invalid characters. Only letters, numbers and hyphens are allowed');
        }
    }

    private function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new SepaException('Amount must be greater than 0');
        }

        if ($amount > 999999999.99) {
            throw new SepaException('Amount exceeds maximum allowed value');
        }

        // Validar que no tenga más de 2 decimales
        if (round($amount, 2) != $amount) {
            throw new SepaException('Amount must have no more than 2 decimal places');
        }
    }

    private function validateConcept(string $concept): void
    {
        if (strlen($concept) > 640) {
            throw new SepaException('Concept must be 640 characters or less');
        }
    }

    public function getDebtor(): Debtor
    {
        return $this->debtor;
    }

    public function getMandateId(): string
    {
        return $this->mandateId;
    }

    public function getMandateDate(): DateTime
    {
        return $this->mandateDate;
    }

    public function getSequenceType(): string
    {
        return $this->sequenceType;
    }

    public function getEndToEndId(): string
    {
        return $this->endToEndId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getConcept(): string
    {
        return $this->concept;
    }
}
