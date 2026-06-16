<?php
require_once __DIR__ . '/../apiHeadSecure.php';
if (!$AUTH->instancePermissionCheck("BUSINESS:BUSINESS_SETTINGS:VIEW")) die("Sorry - you can't access this page");

$instanceName = $AUTH->data['instance']['instances_name'];
$safeInstanceName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $instanceName);

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=insured-assets-" . $safeInstanceName . ".csv");
header("Pragma: no-cache");
header("Expires: 0");
error_reporting(0);
ini_set('display_errors', 0);

use Money\Currency;
use Money\Money;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

$currencies = new ISOCurrencies();
$numberFormatter = new NumberFormatter('en_GB', NumberFormatter::CURRENCY);
$moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

$DBLIB->where("assets.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("assets_deleted", 0);
$DBLIB->where("(assets.assets_endDate IS NULL OR assets.assets_endDate >= CURRENT_TIMESTAMP())");
$DBLIB->where("(assets.assets_insured = 1 OR (assets.assets_insured IS NULL AND assetTypes.assetTypes_insured = 1))");
$DBLIB->join("assetTypes", "assetTypes.assetTypes_id=assets.assetTypes_id", "LEFT");
$DBLIB->join("manufacturers", "manufacturers.manufacturers_id=assetTypes.manufacturers_id", "LEFT");
$DBLIB->orderBy("assetTypes.assetTypes_name", "ASC");
$DBLIB->orderBy("assets.assets_tag", "ASC");

$assets = $DBLIB->get('assets', null, [
    "assets.assets_tag", "assets.assets_value", "assets.assets_insured",
    "assets.asset_definableFields_1", "assets.asset_definableFields_2",
    "assetTypes.assetTypes_name", "assetTypes.assetTypes_value",
    "manufacturers.manufacturers_name", "manufacturers.manufacturers_id"
]);

$fp = fopen('php://output', 'w');
fputcsv($fp, ["Insured Assets - " . $instanceName, "", "", "", "", ""], ",", "\"", "\\", "\r\n");
fputcsv($fp, ["Asset Code", "Asset Name", "Manufacturer", "Definable Field 1", "Definable Field 2", "Value"], ",", "\"", "\\", "\r\n");
foreach ($assets as $asset) {
    $value = $asset['assets_value'] !== null ? $asset['assets_value'] : ($asset['assetTypes_value'] ?? 0);
    fputcsv($fp, [
        $asset['assets_tag'],
        $asset['assetTypes_name'],
        ($asset['manufacturers_id'] != 1 ? $asset['manufacturers_name'] : ""),
        $asset['asset_definableFields_1'] ?? "",
        $asset['asset_definableFields_2'] ?? "",
        $moneyFormatter->format(new Money($value, new Currency($AUTH->data['instance']['instances_config_currency']))),
    ], ",", "\"", "\\", "\r\n");
}
fclose($fp);
