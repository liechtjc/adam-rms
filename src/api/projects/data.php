<?php
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) require_once __DIR__ . '/../apiHeadSecure.php'; //Only if it wasn't included from somewhere else
require_once __DIR__ . '/../../common/libs/bCMS/projectFinance.php';
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Formatter\IntlMoneyFormatter;

$array = [];
if (isset($_POST['formData'])) {
    foreach ($_POST['formData'] as $item) {
        $array[$item['name']] = $item['value'];
        $_GET[$item['name']] = $item['value'];
    }
}
if (isset($_POST['id'])) $_GET['id'] = $_POST['id'];
if (!$AUTH->instancePermissionCheck("PROJECTS:VIEW") or !isset($_GET['id'])) finish(false);

//The project itself
$DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("projects.projects_deleted", 0);
$DBLIB->where("projects.projects_id", $_GET['id']);
$DBLIB->join("projectsTypes", "projects.projectsTypes_id=projectsTypes.projectsTypes_id", "LEFT");
$DBLIB->join("clients", "projects.clients_id=clients.clients_id", "LEFT");
$DBLIB->join("users", "projects.projects_manager=users.users_userid", "LEFT");
$DBLIB->join("locations","locations.locations_id=projects.locations_id","LEFT");
$DBLIB->join("projectsStatuses", "projects.projectsStatuses_id=projectsStatuses.projectsStatuses_id", "LEFT");
$PAGEDATA['project'] = $DBLIB->getone("projects", ["projects.*", "projectsTypes.*", "clients.clients_id", "clients_name","clients_website","clients_email","clients_notes","clients_address","clients_phone","clients_contact","clients_postcode","clients_city","clients_country","users.users_userid", "users.users_name1", "users.users_name2", "users.users_email","locations.locations_name","locations.locations_address","projectsStatuses.projectsStatuses_id", "projectsStatuses.projectsStatuses_name", "projectsStatuses.projectsStatuses_foregroundColour","projectsStatuses.projectsStatuses_backgroundColour","projectsStatuses.projectsStatuses_assetsReleased","projectsStatuses.projectsStatuses_description"]);
if (!$PAGEDATA['project']) $PAGEDATA['USE_TWIG_404'] ? die($TWIG->render('404.twig', $PAGEDATA)) : die("404");

//subprojects
$DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("projects.projects_deleted", 0);
$DBLIB->where("projects.projects_parent_project_id", $_GET['id']);
$DBLIB->join("projectsStatuses", "projects.projectsStatuses_id=projectsStatuses.projectsStatuses_id", "LEFT");
$PAGEDATA['project']['subProjects'] = $DBLIB->get("projects", null, ["projects.*", "projectsStatuses.projectsStatuses_name", "projectsStatuses.projectsStatuses_foregroundColour","projectsStatuses.projectsStatuses_backgroundColour"]);

//Finances

//Payments and also
$PAGEDATA['FINANCIALS'] = projectFinancials($PAGEDATA['project']);
$DBLIB->where("projects_id",$PAGEDATA['project']['projects_id']);
$DBLIB->orderBy("projectsFinanceCache_timestamp", "DESC");
$projectFinanceCache = $DBLIB->getone("projectsFinanceCache");
$projectFinancesCacheMismatch = false;
if (!$projectFinanceCache) {
    //Insert a new project finance cache for this project as it doesn't seem to have one hmmm
    $projectFinanceCacheInsert = [
        "projects_id" => $PAGEDATA['project']['projects_id'],
        "projectsFinanceCache_timestamp" => date("Y-m-d H:i:s"),
        "projectsFinanceCache_equipmentSubTotal" =>$PAGEDATA['FINANCIALS']['prices']['subTotal']->getAmount(),
        "projectsFinanceCache_equiptmentDiscounts" =>$PAGEDATA['FINANCIALS']['prices']['discounts']->getAmount(),
        "projectsFinanceCache_equiptmentTotal" =>$PAGEDATA['FINANCIALS']['prices']['total']->getAmount(),
        "projectsFinanceCache_salesTotal" =>$PAGEDATA['FINANCIALS']['payments']['sales']['total']->getAmount(),
        "projectsFinanceCache_staffTotal" =>$PAGEDATA['FINANCIALS']['payments']['staff']['total']->getAmount(),
        "projectsFinanceCache_externalHiresTotal" => $PAGEDATA['FINANCIALS']['payments']['subHire']['total']->getAmount(),
        "projectsFinanceCache_paymentsReceived" =>$PAGEDATA['FINANCIALS']['payments']['received']['total']->getAmount(),
        "projectsFinanceCache_insuranceTotal" =>$PAGEDATA['FINANCIALS']['insurance']['total']->getAmount(),
        "projectsFinanceCache_vatTotal" =>$PAGEDATA['FINANCIALS']['vat']['total']->getAmount(),
        "projectsFinanceCache_grandTotal" =>$PAGEDATA['FINANCIALS']['payments']['total']->getAmount(),
        "projectsFinanceCache_mass"=>$PAGEDATA['FINANCIALS']['mass'],
        "projectsFinanceCache_value"=>$PAGEDATA['FINANCIALS']['value']->getAmount(),
    ];
    $DBLIB->insert("projectsFinanceCache", $projectFinanceCacheInsert); //Add a cache for the finance of the project
//Just check the cache while we're here - shouldn't ever be thrown!
} elseif ($projectFinanceCache["projectsFinanceCache_equipmentSubTotal"] != $PAGEDATA['FINANCIALS']['prices']['subTotal']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_equiptmentDiscounts"] != $PAGEDATA['FINANCIALS']['prices']['discounts']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_equiptmentTotal"] != $PAGEDATA['FINANCIALS']['prices']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_salesTotal"] != $PAGEDATA['FINANCIALS']['payments']['sales']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_staffTotal"] != $PAGEDATA['FINANCIALS']['payments']['staff']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_externalHiresTotal"] !=  $PAGEDATA['FINANCIALS']['payments']['subHire']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_paymentsReceived"] != $PAGEDATA['FINANCIALS']['payments']['received']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_insuranceTotal"] != $PAGEDATA['FINANCIALS']['insurance']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_vatTotal"] != $PAGEDATA['FINANCIALS']['vat']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_grandTotal"] != $PAGEDATA['FINANCIALS']['payments']['total']->getAmount()) $projectFinancesCacheMismatch = true;
elseif ($projectFinanceCache["projectsFinanceCache_value"] != $PAGEDATA['FINANCIALS']['value']->getAmount()) $projectFinancesCacheMismatch = true;
elseif (round($projectFinanceCache["projectsFinanceCache_mass"]*100000) != round($PAGEDATA['FINANCIALS']['mass']*100000)) $projectFinancesCacheMismatch = true;

if ($projectFinancesCacheMismatch) {
    //So there's a serious project finance mismatch - you can force a rebuild of the cache by passing a get parameter - otherwise throw an error so support are notified
    $projectFinanceCacheInsert = [
        "projects_id" => $PAGEDATA['project']['projects_id'],
        "projectsFinanceCache_timestamp" => date("Y-m-d H:i:s"),
        "projectsFinanceCache_equipmentSubTotal" =>$PAGEDATA['FINANCIALS']['prices']['subTotal']->getAmount(),
        "projectsFinanceCache_equiptmentDiscounts" =>$PAGEDATA['FINANCIALS']['prices']['discounts']->getAmount(),
        "projectsFinanceCache_equiptmentTotal" =>$PAGEDATA['FINANCIALS']['prices']['total']->getAmount(),
        "projectsFinanceCache_salesTotal" =>$PAGEDATA['FINANCIALS']['payments']['sales']['total']->getAmount(),
        "projectsFinanceCache_staffTotal" =>$PAGEDATA['FINANCIALS']['payments']['staff']['total']->getAmount(),
        "projectsFinanceCache_externalHiresTotal" => $PAGEDATA['FINANCIALS']['payments']['subHire']['total']->getAmount(),
        "projectsFinanceCache_paymentsReceived" =>$PAGEDATA['FINANCIALS']['payments']['received']['total']->getAmount(),
        "projectsFinanceCache_insuranceTotal" =>$PAGEDATA['FINANCIALS']['insurance']['total']->getAmount(),
        "projectsFinanceCache_vatTotal" =>$PAGEDATA['FINANCIALS']['vat']['total']->getAmount(),
        "projectsFinanceCache_grandTotal" =>$PAGEDATA['FINANCIALS']['payments']['total']->getAmount(),
        "projectsFinanceCache_mass"=>$PAGEDATA['FINANCIALS']['mass'],
        "projectsFinanceCache_value"=>$PAGEDATA['FINANCIALS']['value']->getAmount(),
    ];
    $newCache = $DBLIB->insert("projectsFinanceCache", $projectFinanceCacheInsert); //Add a cache for the finance of the project
    if(!$newCache) throw new \Exception('Cache reload error');
    else trigger_error("Project finance cache mismatch " . json_encode($projectFinanceCacheInsert) . " vs " . json_encode($projectFinanceCache), E_USER_WARNING);
}



usort($PAGEDATA['FINANCIALS']['payments']['subHire']['ledger'], function ($a, $b) {
    // Sort sub-hires in order of supplier so you can do supplier headings
    return $a['payments_supplier'] <=> $b['payments_supplier'];
});
usort($PAGEDATA['FINANCIALS']['payments']['sales']['ledger'], function ($a, $b) {
    // Sort sub-hires in order of supplier so you can do supplier headings
    return $a['payments_supplier'] <=> $b['payments_supplier'];
});
usort($PAGEDATA['FINANCIALS']['payments']['staff']['ledger'], function ($a, $b) {
    // Sort sub-hires in order of supplier so you can do supplier headings
    return $a['payments_supplier'] <=> $b['payments_supplier'];
});

//Notes
$DBLIB->where("projectsNotes_deleted", 0);
$DBLIB->where("projects_id", $PAGEDATA['project']['projects_id']);
$DBLIB->orderBy("projectsNotes_id", "ASC");
$PAGEDATA['project']['notes'] = $DBLIB->get("projectsNotes");

//Crew
$DBLIB->where("projects_id", $PAGEDATA['project']['projects_id']);
$DBLIB->where("crewAssignments.crewAssignments_deleted", 0);
$DBLIB->join("users", "crewAssignments.users_userid=users.users_userid", "LEFT");
$DBLIB->orderBy("crewAssignments.crewAssignments_rank", "ASC");
$DBLIB->orderBy("crewAssignments.crewAssignments_id", "ASC");
$PAGEDATA['project']['crewAssignments'] = $DBLIB->get("crewAssignments", null, ["crewAssignments.*", "users.users_name1", "users.users_name2", "users.users_email"]);

//Files
$PAGEDATA['files'] = $bCMS->s3List(7, $PAGEDATA['project']['projects_id']);

$PAGEDATA['invoices'] = $bCMS->s3List(20, $PAGEDATA['project']['projects_id'],'s3files_meta_uploaded', 'DESC');
$PAGEDATA['quotes'] = $bCMS->s3List(21, $PAGEDATA['project']['projects_id'],'s3files_meta_uploaded', 'DESC');
$PAGEDATA['deliveryNotes'] = $bCMS->s3List(22, $PAGEDATA['project']['projects_id'],'s3files_meta_uploaded', 'DESC');

$DBLIB->orderBy("assetsAssignmentsStatus_order","ASC");
$DBLIB->where("assetsAssignmentsStatus_deleted", 0);
$DBLIB->where("instances_id", $AUTH->data['instance']['instances_id']);
$PAGEDATA['assetsAssignmentsStatus'] = $DBLIB->get("assetsAssignmentsStatus");


$DATA = [
    "project" => $PAGEDATA['project'],
    "files" => $PAGEDATA['files'],
    "assetsAssignmentsStatus" => $PAGEDATA['assetsAssignmentsStatus'],
    'FINANCIALS' => $PAGEDATA['FINANCIALS']
]; //Data that's safe to return to the app


if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) finish(true, null, $DATA);

/** @OA\Post(
 *     path="/projects/data.php", 
 *     summary="Data", 
 *     description="Get the data of a project  
Requires Instance Permission PROJECTS:VIEW
", 
 *     operationId="data", 
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
 *         required="false", 
 *         @OA\Schema(
 *             type="object", 
 *             ),
 *     ), 
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="Project ID",
 *         required="false", 
 *         @OA\Schema(
 *             type="number"), 
 *         ), 
 * )
 */