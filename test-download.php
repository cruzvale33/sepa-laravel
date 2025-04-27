<?php

require __DIR__.'/vendor/autoload.php';

use SepaLaravel\SepaLaravel\Entities\Creditor;
use SepaLaravel\SepaLaravel\Entities\Debtor;
use SepaLaravel\SepaLaravel\Entities\Payment;
use SepaLaravel\SepaLaravel\Entities\Presenter;
use SepaLaravel\SepaLaravel\SepaLaravel;

// 1. Configurar los datos de prueba con identificadores válidos
$presenter = new Presenter(
    'ES2401827363120201678036', // Identificador válido (formato ESXX-SUFIJO-NIF)
    'COMO UN COHETE SL',         // Nombre
    '0182'                       // Oficina BBVA
);

$creditor = new Creditor(
    'ES2401827363120201678036', // Identificador válido
    new \DateTime('2025-05-16'), // Fecha de cobro (usando \DateTime)
    'COMO UN COHETE SL',         // Nombre
    'ES2401827363120201678036',  // IBAN (BBVA 0182)
    'BBVAESMMXXX',               // BIC
    'ES50000B01958115'           // Scheme ID
);

$debtor = new Debtor(
    'PALMA MORENO CORRAL',       // Nombre
    'ES4501820220020201712773',  // IBAN
    'NOTPROVIDED'                // BIC
);

$payment = new Payment(
    $debtor,
    '31844754N-CORE',            // Mandato ID
    new \DateTime('2025-03-10'), // Fecha firma mandato
    'RCUR',                      // Tipo secuencia (RCUR = recurrente)
    '124',                       // Referencia única
    485.35,                      // Importe
    'Factura B2b-2500037 - Vencimiento num: 1 de 1 Fecha Vencimiento: 16-03-2025 - Orden: 8YAQR' // Concepto
);

$payment2 = new Payment(
    $debtor,
    '2565622N-CORE',            // Mandato ID
    new \DateTime('2025-02-10'), // Fecha firma mandato
    'RCUR',                      // Tipo secuencia (RCUR = recurrente)
    '321',                       // Referencia única
    200.14,                      // Importe
    'Factura B2b-11111 - Vencimiento num: 1 de 1 Fecha Vencimiento: 122 - Orden: 8YAQR' // Concepto
);

// 2. Crear instancia de SepaLaravel y configurar
$sepa = new SepaLaravel;
$sepa->setPresenter($presenter)
    ->setCreditor($creditor)
    ->addPayment($payment);
$sepa->addPayment($payment2);
// 3. Generar el XML
$xmlContent = $sepa->generateXml();

// 4. Configurar headers para descarga
header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="sepa_payment_test.xml"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

// 5. Output del contenido XML
echo $xmlContent;
exit;
