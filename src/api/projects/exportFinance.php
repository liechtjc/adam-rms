<?php
require_once __DIR__ . '/../apiHeadSecure.php';
require_once __DIR__ . '/../../common/libs/bCMS/projectFinance.php';

if (!$AUTH->instancePermissionCheck("PROJECTS:EXPORT:FINANCE")) die("Sorry - you can't access this page");

use Money\Currency;
use Money\Money;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;

if (!isset($_GET['start']) || !isset($_GET['end'])) die("Start and end dates are required");

$instanceId = $AUTH->data['instance']['instances_id'];
$instanceName = $AUTH->data['instance']['instances_name'];
$safeInstanceName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $instanceName);
$currency = $AUTH->data['instance']['instances_config_currency'];

$start = date("Y-m-d 00:00:00", strtotime($_GET['start']));
$end = date("Y-m-d 23:59:59", strtotime($_GET['end']));
$includeArchived = isset($_GET['archived']) && $_GET['archived'] == '1';
$includeLines = isset($_GET['lines']) && $_GET['lines'] == '1';
$statusIds = (isset($_GET['statuses']) && is_array($_GET['statuses'])) ? array_map('intval', $_GET['statuses']) : [];
$ownerShare = isset($_GET['ownerShare']) ? (float)$_GET['ownerShare'] : 60.0;

$filenameEnd = date("Y-m-d", strtotime($_GET['end']));
$filenameStart = date("Y-m-d", strtotime($_GET['start']));

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=" . $filenameEnd . "_" . $filenameStart . "_" . $safeInstanceName . "_project-finance.csv");
header("Pragma: no-cache");
header("Expires: 0");
error_reporting(0);
ini_set('display_errors', 0);

//Plain decimal, no thousands separator - so every spreadsheet app reads these as numbers rather than text
$moneyFormatter = new DecimalMoneyFormatter(new ISOCurrencies());
function fmt($money) {
    global $moneyFormatter;
    return $moneyFormatter->format($money);
}

//DD-MM-YYYY, no time
function euroDate($datetime) {
    return $datetime ? date("d-m-Y", strtotime($datetime)) : "";
}

//One payout column per entity present in the system, including this one - a stable, identical column set no matter who runs the export
$DBLIB->where("instances_deleted", 0);
$DBLIB->orderBy("instances_name", "ASC");
$entities = $DBLIB->get("instances", null, ["instances_id", "instances_name"]);
$entityColumnIndex = [];
foreach ($entities as $i => $entity) $entityColumnIndex[$entity['instances_id']] = $i;

//Projects delivered within the requested date range
$DBLIB->where("projects.instances_id", $instanceId);
$DBLIB->where("projects.projects_deleted", 0);
$DBLIB->where("projects.projects_dates_deliver_start", $start, ">=");
$DBLIB->where("projects.projects_dates_deliver_start", $end, "<=");
if (!$includeArchived) $DBLIB->where("projects.projects_archived", 0);
if (count($statusIds) > 0) $DBLIB->where("projects.projectsStatuses_id", $statusIds, "IN");
$DBLIB->join("clients", "projects.clients_id=clients.clients_id", "LEFT");
$DBLIB->join("projectsStatuses", "projects.projectsStatuses_id=projectsStatuses.projectsStatuses_id", "LEFT");
$DBLIB->orderBy("projects.projects_dates_deliver_start", "ASC");
$projects = $DBLIB->get("projects", null, ["projects.*", "clients.clients_name", "projectsStatuses.projectsStatuses_name"]);

$fp = fopen('php://output', 'w');

$projectHeader = ["Project ID", "Project Name", "Client", "Status", "Delivery Start", "Delivery End", "Archived"];
$itemHeader = ["Asset Tag", "Asset Type", "Category", "Owning Entity", "Day Rate", "Week Rate", "Duration Days", "Duration Weeks", "Gross Price", "Discount %", "Net Price"];
$financeHeader = ["Equipment SubTotal", "Discounts", "Equipment Total", "Sales", "Staffing", "Additional Hires", "Insurance", "SubTotal", "VAT", "Grand Total", "Payments Received", "Last Payment Date", "Grand Total Outstanding"];
$entityHeader = ["Owner Share %"];
foreach ($entities as $entity) $entityHeader[] = $entity['instances_name'] . " Payout";

$header = $includeLines ? array_merge($projectHeader, $itemHeader, $financeHeader, $entityHeader) : array_merge($projectHeader, $financeHeader);
fputcsv($fp, $header, ",", "\"", "\\", "\r\n");

foreach ($projects as $project) {
    $financials = projectFinancials($project);

    $projectRow = [
        $project['projects_id'],
        $project['projects_name'],
        $project['clients_name'],
        $project['projectsStatuses_name'],
        euroDate($project['projects_dates_deliver_start']),
        euroDate($project['projects_dates_deliver_end']),
        $project['projects_archived'] ? "Y" : "N",
    ];
    $receivedLedger = $financials['payments']['received']['ledger']; //Ordered by payments_date ASC, so the last entry is the most recent
    $lastPaymentDate = count($receivedLedger) > 0 ? euroDate(end($receivedLedger)['payments_date']) : "";

    $financeRow = [
        fmt($financials['prices']['subTotal']),
        fmt($financials['prices']['discounts']),
        fmt($financials['prices']['total']),
        fmt($financials['payments']['sales']['total']),
        fmt($financials['payments']['staff']['total']),
        fmt($financials['payments']['subHire']['total']),
        fmt($financials['insurance']['total']),
        fmt($financials['payments']['subTotal']),
        fmt($financials['vat']['total']),
        fmt($financials['payments']['grandTotal']),
        fmt($financials['payments']['received']['total']),
        $lastPaymentDate,
        fmt($financials['payments']['total']),
    ];

    if (!$includeLines) {
        fputcsv($fp, array_merge($projectRow, $financeRow), ",", "\"", "\\", "\r\n");
        continue;
    }

    //Project summary row: financial totals shown once per project, item/entity columns blank (except duration, which is project-wide anyway)
    $itemBlank = ["", "", "", "", "", "", $financials['priceMaths']['days'], $financials['priceMaths']['weeks'], "", "", ""];
    $entityBlank = array_fill(0, 1 + count($entities), ""); //Owner Share % + one slot per entity
    fputcsv($fp, array_merge($projectRow, $itemBlank, $financeRow, $entityBlank), ",", "\"", "\\", "\r\n");

    //Then one row per equipment line, financial totals left blank since they're already on the summary row above
    $financeBlank = array_fill(0, count($financeHeader), "");
    $lineGroups = [["ownerName" => $instanceName, "ownerInstanceId" => $instanceId, "assetTypes" => $financials['assetsAssigned']]];
    foreach ($financials['assetsAssignedSUB'] as $ownerInstanceId => $subGroup) {
        $lineGroups[] = ["ownerName" => $subGroup['instance']['instances_name'], "ownerInstanceId" => $ownerInstanceId, "assetTypes" => $subGroup['assets']];
    }

    foreach ($lineGroups as $group) {
        foreach ($group['assetTypes'] as $type) {
            foreach ($type['assets'] as $asset) {
                if ($asset['assetsAssignments_linkedTo'] !== null) continue; //Descriptive-only, doesn't contribute to totals - excluded so payouts stay in line with the invoiced amount

                $itemRow = [
                    $asset['assets_tag'],
                    $asset['assetTypes_name'],
                    $asset['assetCategories_name'],
                    $group['ownerName'],
                    fmt(new Money(($asset['assets_dayRate'] !== null ? $asset['assets_dayRate'] : $asset['assetTypes_dayRate']), new Currency($currency))),
                    fmt(new Money(($asset['assets_weekRate'] !== null ? $asset['assets_weekRate'] : $asset['assetTypes_weekRate']), new Currency($currency))),
                    $financials['priceMaths']['days'],
                    $financials['priceMaths']['weeks'],
                    fmt($asset['price']),
                    $asset['assetsAssignments_discount'],
                    fmt($asset['discountPrice']),
                ];

                //Same formula for every entity, including this instance's own - so in-house lines populate this instance's own column too
                $entityRow = array_fill(0, count($entities), "");
                if (isset($entityColumnIndex[$group['ownerInstanceId']])) {
                    $payout = $asset['discountPrice']->multiply($ownerShare / 100);
                    $entityRow[$entityColumnIndex[$group['ownerInstanceId']]] = fmt($payout);
                }

                fputcsv($fp, array_merge($projectRow, $itemRow, $financeBlank, [$ownerShare], $entityRow), ",", "\"", "\\", "\r\n");
            }
        }
    }
}
fclose($fp);
