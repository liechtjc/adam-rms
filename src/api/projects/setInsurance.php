<?php
require_once __DIR__ . '/../apiHeadSecure.php';
if (!$AUTH->instancePermissionCheck("PROJECTS:EDIT:INSURANCE")) die("404");
use Money\Currencies\ISOCurrencies;
use Money\Parser\DecimalMoneyParser;

$array = [];
foreach ($_POST['formData'] as $item) {
    $array[$item['name']] = $item['value'];
}
if (strlen($array['projects_id']) < 1) finish(false, ["code" => "PARAM-ERROR", "message" => "No data for action"]);

$DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("projects.projects_deleted", 0);
$DBLIB->where("projects.projects_id", $array['projects_id']);
$project = $DBLIB->getone("projects", ["projects_id"]);
if (!$project) finish(false);

$currencies = new ISOCurrencies();
$moneyParser = new DecimalMoneyParser($currencies);
$amount = null;
if (isset($array['projects_insurance_amount']) && strlen($array['projects_insurance_amount']) > 0) {
    $amount = $moneyParser->parse($array['projects_insurance_amount'], $AUTH->data['instance']['instances_config_currency'])->getAmount();
    if ($amount == 0) $amount = null;
}

$DBLIB->where("projects_id", $project['projects_id']);
$update = $DBLIB->update("projects", [
    "projects_insurance_rate" => $array['projects_insurance_rate'],
    "projects_insurance_amount" => $amount,
]);
if (!$update) finish(false);

$bCMS->auditLog("EDIT-INSURANCE", "projects", $project['projects_id'], $AUTH->data['users_userid'], null, $project['projects_id']);

finish(true);

/** @OA\Post(
 *     path="/projects/setInsurance.php",
 *     summary="Set Project Insurance",
 *     description="Set the insurance rate/amount for a project
Requires Instance Permission PROJECTS:EDIT:INSURANCE
",
 *     operationId="setInsurance",
 *     tags={"projects"},
 *     @OA\Response(
 *         response="200",
 *         description="Success",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="result",
 *                     type="boolean",
 *                     description="Whether the request was successful",
 *                 ),
 *             ),
 *         ),
 *     ),
 *     @OA\Response(
 *         response="404",
 *         description="Permission Error",
 *     ),
 *     @OA\Parameter(
 *         name="formData",
 *         in="query",
 *         description="Form Data",
 *         required="true",
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="projects_id",
 *                 type="number",
 *                 description="Project ID",
 *             ),
 *             @OA\Property(
 *                 property="projects_insurance_rate",
 *                 type="number",
 *                 description="Insurance rate as a percentage",
 *             ),
 *             @OA\Property(
 *                 property="projects_insurance_amount",
 *                 type="string",
 *                 description="Fixed insurance amount - overrides the rate when set",
 *             ),
 *         ),
 *     ),
 * )
 */
