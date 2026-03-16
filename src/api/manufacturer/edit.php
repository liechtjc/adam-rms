<?php
require_once __DIR__ . '/../apiHeadSecure.php';

if (!$AUTH->instancePermissionCheck("ASSETS:MANUFACTURERS:EDIT")) {
  finish(false, ["code" => "AUTH-ERROR", "message" => "No auth for action"]);
}

$array = [];
foreach ($_POST['formData'] as $item) {
  $array[$item['name']] = $item['value'];
}
// Basic parameter validation
if (!isset($array['manufacturers_id']) || strlen($array['manufacturers_id']) < 1) {
  finish(false, ["code" => "PARAM-ERROR", "message" => "No data for action"]);
}
if (!isset($array['manufacturers_name']) || trim($array['manufacturers_name']) === '') {
  finish(false, ["code" => "PARAM-ERROR", "message" => "Manufacturer name is required"]);
}
if (isset($array['manufacturers_website']) && trim($array['manufacturers_website']) !== '') {
  if (filter_var($array['manufacturers_website'], FILTER_VALIDATE_URL) === false) {
    finish(false, ["code" => "PARAM-ERROR", "message" => "Manufacturer website must be a valid URL"]);
  }
}

// Fetch manufacturer — must be global (NULL) or owned by current instance
$DBLIB->where("manufacturers_id", $array['manufacturers_id']);
$DBLIB->where("(instances_id IS NULL OR instances_id = '" . $AUTH->data['instance']['instances_id'] . "')");
$manufacturer = $DBLIB->getOne("manufacturers");
if (!$manufacturer) {
  finish(false, ["code" => "NOT-FOUND", "message" => "Manufacturer not found or not accessible"]);
}

// Resolve new instances_id based on private flag
$isCurrentlyGlobal = $manufacturer['instances_id'] === null;
$wantsPrivate = isset($array['manufacturers_private']) && $array['manufacturers_private'] == '1';

if ($wantsPrivate && $isCurrentlyGlobal) {
  // Global → Private: only allowed if no other instance's assetTypes use this manufacturer
  $DBLIB->where("manufacturers_id", $array['manufacturers_id']);
  $DBLIB->where("(instances_id IS NULL OR instances_id != '" . $AUTH->data['instance']['instances_id'] . "')");
  $usedByOthers = $DBLIB->getValue("assetTypes", "count(*)");
  if ($usedByOthers > 0) {
    finish(false, ["code" => "IN-USE", "message" => "This manufacturer is used by other businesses and cannot be made private."]);
  }
  $newInstancesId = $AUTH->data['instance']['instances_id'];
} elseif (!$wantsPrivate) {
  // Private → Global, or keep global
  $newInstancesId = null;
} else {
  // Already private, keep as-is
  $newInstancesId = $manufacturer['instances_id'];
}

$updateData = array_intersect_key($array, array_flip(['manufacturers_name', 'manufacturers_website', 'manufacturers_notes']));
$updateData['instances_id'] = $newInstancesId;

$DBLIB->where("manufacturers_id", $array['manufacturers_id']);
$result = $DBLIB->update("manufacturers", $updateData, 1);
if (!$result) {
  finish(false, ["code" => "UPDATE-FAIL", "message" => "Could not update manufacturer"]);
}

$bCMS->auditLog("EDIT", "manufacturers", json_encode($array), $AUTH->data['users_userid']);
finish(true);

/** @OA\Post(
 *     path="/manufacturer/edit.php", 
 *     summary="Edit Manufacturer", 
 *     description="Edit a Manufacturer  
Requires Instance Permission ASSETS:MANUFACTURERS:EDIT", 
 *     operationId="editManufacturer", 
 *     tags={"manufacturers"}, 
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
 *                 @OA\Property(
 *                     property="response", 
 *                     type="array", 
 *                     description="A null Array",
 *                 ),
 *             ),
 *         ),
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
 *     @OA\RequestBody(
 *         required=true,
 *         description="The manufacturer data, wrapped in a formData array of name/value pairs",
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="formData",
 *                     type="array",
 *                     description="Array of form fields, each with a name and value (e.g. manufacturers_id, manufacturers_name, manufacturers_website, manufacturers_notes)",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(
 *                             property="name",
 *                             type="string",
 *                             description="Field name (one of: manufacturers_id, manufacturers_name, manufacturers_website, manufacturers_notes)"
 *                         ),
 *                         @OA\Property(
 *                             property="value",
 *                             type="string",
 *                             description="Value for the given field"
 *                         ),
 *                     ),
 *                 ),
 *             ),
 *         ),
 *     ), 
 * )
 */
