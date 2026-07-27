<?php
require_once __DIR__ . '/../apiHeadSecure.php';
if (!$AUTH->instancePermissionCheck("PROJECTS:EDIT:VAT")) die("404");

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

$DBLIB->where("projects_id", $project['projects_id']);
$update = $DBLIB->update("projects", [
    "projects_vat_export" => !empty($array['projects_vat_export']) ? 1 : 0,
]);
if (!$update) finish(false);

$bCMS->auditLog("EDIT-VAT", "projects", $project['projects_id'], $AUTH->data['users_userid'], null, $project['projects_id']);

finish(true);

/** @OA\Post(
 *     path="/projects/setVat.php",
 *     summary="Set Project VAT Export Status",
 *     description="Mark a project as VAT-exempt (Export) or not
Requires Instance Permission PROJECTS:EDIT:VAT
",
 *     operationId="setVat",
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
 *                 property="projects_vat_export",
 *                 type="boolean",
 *                 description="Whether the project is marked as an Export (0% VAT)",
 *             ),
 *         ),
 *     ),
 * )
 */
