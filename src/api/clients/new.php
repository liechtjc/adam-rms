<?php
require_once __DIR__ . '/../apiHeadSecure.php';

if (!$AUTH->instancePermissionCheck("CLIENTS:CREATE") or !isset($_POST['formData'])) die("404");

$array = [];
foreach ($_POST['formData'] as $item) {
    $array[$item['name']] = $item['value'];
}

foreach (["clients_name", "clients_postcode", "clients_city", "clients_country"] as $requiredField) {
    if (!isset($array[$requiredField]) or strlen(trim($array[$requiredField])) < 1) {
        finish(false, ["code" => "PARAM-ERROR", "message" => "Name, Postcode, City and Country are required"]);
    }
}

$array["instances_id"] = $AUTH->data['instance']['instances_id'];
$client = $DBLIB->insert("clients", $array);
if (!$client) finish(false, ["code" => "CREATE-CLIENT-FAIL", "message"=> "Could not create new client"]);

$bCMS->auditLog("INSERT", "clients",null, $AUTH->data['users_userid'],null, $client);
finish(true, null, ["clients_id" => $client]);

/** @OA\Post(
 *     path="/clients/new.php", 
 *     summary="Create Client", 
 *     description="Create a client  
Requires Instance Permission CLIENTS:CREATE", 
 *     operationId="createClient", 
 *     tags={"clients"}, 
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
 *         response="400", 
 *         description="Error",
 *     ), 
 *     @OA\Response(
 *         response="default", 
 *         description="Error",
 *         @OA\MediaType(
 *             mediaType="application/json", 
 *             @OA\Schema( 
 *                 type="object", 
 *                 @OA\Property(
 *                     property="result", 
 *                     type="boolean", 
 *                     description="Whether the request was successful",
 *                 ),
 *                 @OA\Property(
 *                     property="error", 
 *                     type="array", 
 *                     description="An Array containing an error code and a message",
 *                 ),
 *             ),
 *         ),
 *     ), 
 *     @OA\Parameter(
 *         name="clients_name",
 *         in="query",
 *         description="The name of the client",
 *         required="true", 
 *         @OA\Schema(
 *             type="string"), 
 *         ), 
 * )
 */