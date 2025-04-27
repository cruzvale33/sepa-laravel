<?php

namespace SepaLaravel\SepaLaravel;

use DateTime;
use DOMDocument;
use SepaLaravel\SepaLaravel\Entities\Creditor;
use SepaLaravel\SepaLaravel\Entities\Payment;
use SepaLaravel\SepaLaravel\Entities\Presenter;
use SepaLaravel\SepaLaravel\Exceptions\SepaException;

class SepaLaravel
{
    protected $presenter;

    protected $creditor;

    protected $payments = [];

    protected $messageId;

    protected $creationDateTime;

    protected $numberOfTransactions = 0;

    protected $controlSum = 0.0;

    protected $pmtInfId;

    public function __construct()
    {
        $this->messageId = 'SEPA-'.date('Ymd-His').'-'.substr(uniqid(), -6);
        $this->creationDateTime = new DateTime;
    }

    public function setPresenter(Presenter $presenter): self
    {
        $this->presenter = $presenter;

        return $this;
    }

    public function setCreditor(Creditor $creditor): self
    {
        $this->creditor = $creditor;

        return $this;
    }

    public function setPmtInfId(string $pmtInfId): self
    {
        $this->pmtInfId = $pmtInfId;

        return $this;
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;
        $this->numberOfTransactions++;
        $this->controlSum += $payment->getAmount();

        return $this;
    }

    public function generateXml(): string
    {
        $this->validateData();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Create Document root with proper namespace handling
        $document = $dom->createElementNS('urn:iso:std:iso:20022:tech:xsd:pain.008.001.02', 'Document');
        $dom->appendChild($document);

        // Add namespaces correctly
        $document->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $document->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation',
            'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd');

        // Create Customer Direct Debit Initiation
        $cstmrDrctDbtInitn = $dom->createElement('CstmrDrctDbtInitn');
        $document->appendChild($cstmrDrctDbtInitn);

        // Add Group Header
        $this->addGroupHeader($dom, $cstmrDrctDbtInitn);

        // Add Payment Information
        $this->addPaymentInformation($dom, $cstmrDrctDbtInitn);

        return $dom->saveXML();
    }

    protected function addGroupHeader(DOMDocument $dom, \DOMElement $parent): void
    {
        $grpHdr = $dom->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        $grpHdr->appendChild($dom->createElement('MsgId', $this->messageId));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $this->creationDateTime->format('Y-m-d\TH:i:s\Z')));
        $grpHdr->appendChild($dom->createElement('NbOfTxs', $this->numberOfTransactions));
        $grpHdr->appendChild($dom->createElement('CtrlSum', number_format($this->controlSum, 2, '.', '')));

        $initgPty = $dom->createElement('InitgPty');
        $grpHdr->appendChild($initgPty);

        $initgPty->appendChild($dom->createElement('Nm', $this->presenter->getName()));

        $id = $dom->createElement('Id');
        $orgId = $dom->createElement('OrgId');
        $othr = $dom->createElement('Othr');
        $othr->appendChild($dom->createElement('Id', $this->presenter->getIdentifier()));
        $orgId->appendChild($othr);
        $id->appendChild($orgId);
        $initgPty->appendChild($id);
    }

    protected function addPaymentInformation(DOMDocument $dom, \DOMElement $parent): void
    {
        $pmtInf = $dom->createElement('PmtInf');
        $parent->appendChild($pmtInf);

        // Usar PmtInfId proporcionado o generar uno si no se especificó
        $pmtInfId = $this->pmtInfId ?? 'PMT-'.uniqid();
        $pmtInf->appendChild($dom->createElement('PmtInfId', $pmtInfId));

        $pmtInf->appendChild($dom->createElement('PmtMtd', 'DD'));

        $pmtTpInf = $dom->createElement('PmtTpInf');
        $pmtInf->appendChild($pmtTpInf);

        $svcLvl = $dom->createElement('SvcLvl');
        $svcLvl->appendChild($dom->createElement('Cd', 'SEPA'));
        $pmtTpInf->appendChild($svcLvl);

        $lclInstrm = $dom->createElement('LclInstrm');
        $lclInstrm->appendChild($dom->createElement('Cd', 'CORE'));
        $pmtTpInf->appendChild($lclInstrm);

        $pmtTpInf->appendChild($dom->createElement('SeqTp', 'RCUR'));

        $pmtInf->appendChild($dom->createElement('ReqdColltnDt', $this->creditor->getCollectionDate()->format('Y-m-d')));

        $cdtr = $dom->createElement('Cdtr');
        $cdtr->appendChild($dom->createElement('Nm', $this->creditor->getName()));
        $pmtInf->appendChild($cdtr);

        $cdtrAcct = $dom->createElement('CdtrAcct');
        $id = $dom->createElement('Id');
        $id->appendChild($dom->createElement('IBAN', $this->creditor->getIban()));
        $cdtrAcct->appendChild($id);
        $pmtInf->appendChild($cdtrAcct);

        $cdtrAgt = $dom->createElement('CdtrAgt');
        $finInstnId = $dom->createElement('FinInstnId');
        $finInstnId->appendChild($dom->createElement('BIC', $this->creditor->getBic()));
        $cdtrAgt->appendChild($finInstnId);
        $pmtInf->appendChild($cdtrAgt);

        $cdtrSchmeId = $dom->createElement('CdtrSchmeId');
        $id = $dom->createElement('Id');
        $prvtId = $dom->createElement('PrvtId');
        $othr = $dom->createElement('Othr');
        $othr->appendChild($dom->createElement('Id', $this->creditor->getSchemeId()));
        $prvtId->appendChild($othr);
        $id->appendChild($prvtId);
        $cdtrSchmeId->appendChild($id);
        $pmtInf->appendChild($cdtrSchmeId);

        foreach ($this->payments as $payment) {
            $this->addDirectDebitTransaction($dom, $pmtInf, $payment);
        }
    }

    protected function addDirectDebitTransaction(DOMDocument $dom, \DOMElement $pmtInf, Payment $payment): void
    {
        $drctDbtTxInf = $dom->createElement('DrctDbtTxInf');
        $pmtInf->appendChild($drctDbtTxInf);

        $pmtId = $dom->createElement('PmtId');
        $pmtId->appendChild($dom->createElement('EndToEndId', $payment->getEndToEndId()));
        $drctDbtTxInf->appendChild($pmtId);

        $instdAmt = $dom->createElement('InstdAmt', number_format($payment->getAmount(), 2, '.', ''));
        $instdAmt->setAttribute('Ccy', 'EUR');
        $drctDbtTxInf->appendChild($instdAmt);

        $drctDbtTx = $dom->createElement('DrctDbtTx');
        $mndtRltdInf = $dom->createElement('MndtRltdInf');
        $mndtRltdInf->appendChild($dom->createElement('MndtId', $payment->getMandateId()));
        $mndtRltdInf->appendChild($dom->createElement('DtOfSgntr', $payment->getMandateDate()->format('Y-m-d')));
        $drctDbtTx->appendChild($mndtRltdInf);
        $drctDbtTxInf->appendChild($drctDbtTx);

        $dbtrAgt = $dom->createElement('DbtrAgt');
        $finInstnId = $dom->createElement('FinInstnId');
        $othr = $dom->createElement('Othr');
        $othr->appendChild($dom->createElement('Id', 'NOTPROVIDED'));
        $finInstnId->appendChild($othr);
        $dbtrAgt->appendChild($finInstnId);
        $drctDbtTxInf->appendChild($dbtrAgt);

        $dbtr = $dom->createElement('Dbtr');
        $dbtr->appendChild($dom->createElement('Nm', $payment->getDebtor()->getName()));
        $drctDbtTxInf->appendChild($dbtr);

        $dbtrAcct = $dom->createElement('DbtrAcct');
        $id = $dom->createElement('Id');
        $id->appendChild($dom->createElement('IBAN', $payment->getDebtor()->getIban()));
        $dbtrAcct->appendChild($id);
        $drctDbtTxInf->appendChild($dbtrAcct);

        $rmtInf = $dom->createElement('RmtInf');
        $rmtInf->appendChild($dom->createElement('Ustrd', $payment->getConcept()));
        $drctDbtTxInf->appendChild($rmtInf);
    }

    protected function validateData(): void
    {
        if (! $this->presenter) {
            throw new SepaException('Presenter data is required');
        }

        if (! $this->creditor) {
            throw new SepaException('Creditor data is required');
        }

        if (empty($this->payments)) {
            throw new SepaException('At least one payment is required');
        }

        $calculatedSum = array_reduce($this->payments,
            fn ($sum, $payment) => $sum + $payment->getAmount(),
            0.0
        );

        if (abs($calculatedSum - $this->controlSum) > 0.001) {
            throw new SepaException('Control sum does not match payments sum');
        }
    }
}
