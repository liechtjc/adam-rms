<?php
use Money\Formatter\DecimalMoneyFormatter;
use Money\Currencies\ISOCurrencies;
use Sprain\SwissQrBill\QrBill;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

require_once __DIR__ . '/../common/headSecure.php';
if (!$AUTH->instancePermissionCheck("PROJECTS:VIEW") or !isset($_GET['id'])) die($TWIG->render('404.twig', $PAGEDATA));
require_once __DIR__ . '/../api/projects/data.php'; //Where most of the data comes from

$PAGEDATA['GET'] = $_GET;
$PAGEDATA['GET']['generate'] = true;

$isQuote = $_GET['type'] == "quote";
$isDeliveryNote = $_GET['type'] == "deliveryNote";
$PAGEDATA['GET']['quote'] = $isQuote;
$PAGEDATA['GET']['deliveryNote'] = $isDeliveryNote;

$typeId = 20;
$PAGEDATA['GET']['fileType'] = 'Invoice';

switch ($_GET['type']) {
    case 'quote':
        $typeId = 21;
        $PAGEDATA['GET']['fileType'] = 'Quotation';
        break;
    case 'deliveryNote':
        $typeId = 22;
        $PAGEDATA['GET']['fileType'] = 'Delivery Note';
        break;
    default:
        break;
}

$DBLIB->where("s3files_meta_type", $typeId);
$DBLIB->where("instances_id",$AUTH->data['instance']['instances_id']);
$DBLIB->where("s3files_meta_subType",$_GET['id']);
$count = $DBLIB->getValue ("s3files", "count(*)");
if ($count) $fileNumber = ($count+1);
else $fileNumber = 1;
$PAGEDATA['fileNumber'] = $fileNumber;

if ($PAGEDATA['USERDATA']['instance']['instances_logo'] and $PAGEDATA['GET']['instancelogo']) {
    $PAGEDATA['INSTANCELOGO'] = $bCMS->s3DataUri($PAGEDATA['USERDATA']['instance']['instances_logo']);
} else $PAGEDATA['INSTANCELOGO'] = false;

// --- Swiss QR-bill (see /Users/liechtjc/.claude/plans/could-we-use-sprain-php-swiss-qr-bill-pure-nygaard.md) ---
// Automatic whenever the instance is eligible (CHF + valid QR-IBAN + address configured
// in instance settings) — no per-invoice toggle. Never generated for quotes/delivery notes,
// and skipped when there's nothing outstanding to pay.
$PAGEDATA['QRBILL_IMAGE'] = false;
if ($_GET['type'] === 'invoice'
    && $bCMS->instanceQrBillIsAvailable($AUTH->data['instance'])
    && $PAGEDATA['FINANCIALS']['payments']['total']->isPositive()
) {
    $qrBillMoneyFormatter = new DecimalMoneyFormatter(new ISOCurrencies());
    $qrBillAmount = $qrBillMoneyFormatter->format($PAGEDATA['FINANCIALS']['payments']['total']);

    $qrBillCreditorName = $AUTH->data['instance']['instances_config_qrBillName'] ?: $AUTH->data['instance']['instances_name'];

    $qrBill = QrBill::create();
    $qrBill->setCreditorInformation(CreditorInformation::create($AUTH->data['instance']['instances_config_qrBillIban']));
    if (!empty($AUTH->data['instance']['instances_config_qrBillStreet'])) {
        $qrBill->setCreditor(StructuredAddress::createWithStreet(
            $qrBillCreditorName,
            $AUTH->data['instance']['instances_config_qrBillStreet'],
            $AUTH->data['instance']['instances_config_qrBillBuildingNumber'] ?: null,
            $AUTH->data['instance']['instances_config_qrBillPostcode'],
            $AUTH->data['instance']['instances_config_qrBillCity'],
            $AUTH->data['instance']['instances_config_qrBillCountry'] ?: 'CH'
        ));
    } else {
        $qrBill->setCreditor(StructuredAddress::createWithoutStreet(
            $qrBillCreditorName,
            $AUTH->data['instance']['instances_config_qrBillPostcode'],
            $AUTH->data['instance']['instances_config_qrBillCity'],
            $AUTH->data['instance']['instances_config_qrBillCountry'] ?: 'CH'
        ));
    }
    $qrBill->setPaymentAmountInformation(PaymentAmountInformation::create('CHF', $qrBillAmount));
    $qrBill->setPaymentReference(PaymentReference::create(
        PaymentReference::TYPE_QR,
        QrPaymentReferenceGenerator::generate(
            $AUTH->data['instance']['instances_config_qrBillReferencePrefix'] ?: null,
            (string) $PAGEDATA['project']['projects_id']
        )
    ));

    if ($AUTH->data['instance']['instances_config_qrBillIncludeProjectName']) {
        $qrBill->setAdditionalInformation(AdditionalInformation::create(
            mb_substr((string) $PAGEDATA['project']['projects_name'], 0, 140)
        ));
    }

    // Debtor (the client being invoiced) — only set if enough structured data is present;
    // the spec allows a QR-bill "without debtor" so we fall back to omitting it otherwise.
    // (Legacy clients edited before postcode/city/country were made mandatory may lack these.)
    $debtorName = trim(($PAGEDATA['project']['clients_contact'] ?? '') !== ''
        ? $PAGEDATA['project']['clients_contact'] . ', ' . $PAGEDATA['project']['clients_name']
        : (string) ($PAGEDATA['project']['clients_name'] ?? ''));
    if ($debtorName !== '' && !empty($PAGEDATA['project']['clients_postcode']) && !empty($PAGEDATA['project']['clients_city'])) {
        $qrBill->setUltimateDebtor(StructuredAddress::createWithoutStreet(
            $debtorName,
            $PAGEDATA['project']['clients_postcode'],
            $PAGEDATA['project']['clients_city'],
            $PAGEDATA['project']['clients_country'] ?: 'CH'
        ));
    }

    if ($qrBill->isValid()) {
        // Raw SVG markup, not a data URI: pdfmake's `image` node only decodes raster
        // formats (PNG/JPEG); SVG has to go through its separate `svg` node type instead.
        $PAGEDATA['QRBILL_IMAGE'] = $qrBill->getQrCode()->getAsString('svg');
        $PAGEDATA['QRBILL_TEXT'] = [
            'iban' => $qrBill->getCreditorInformation()->getFormattedIban(),
            'creditor' => $qrBill->getCreditor()->getFullAddress(),
            'amount' => $qrBillAmount,
            'currency' => 'CHF',
            'reference' => $qrBill->getPaymentReference()->getFormattedReference(),
            'additionalInfo' => $qrBill->getAdditionalInformation() ? $qrBill->getAdditionalInformation()->getMessage() : false,
            'debtor' => $qrBill->getUltimateDebtor() ? $qrBill->getUltimateDebtor()->getFullAddress() : false,
        ];
    }
    // If invalid despite passing the eligibility check (e.g. stale/edited-around-validation
    // data), just omit the QR-bill rather than surfacing an error on the invoice itself.
}
// --- end Swiss QR-bill ---

echo $TWIG->render('project/pdf.twig', $PAGEDATA);