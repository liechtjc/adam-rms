<?php
require_once __DIR__ . '/../../apiHeadSecure.php';

if (!$AUTH->instancePermissionCheck("PROJECTS:VIEW") or !isset($_POST['projects_id'])) finish(false, ["code" => "AUTH-ERROR", "message" => "Provide a project"]);


$_POST['id'] = $_POST['projects_id'];
require_once __DIR__ . '/../data.php'; //Where most of the data comes from


require_once __DIR__ . '/../../../common/libs/bCMS/assetBoard.php';
$sortedAssets = buildAssetBoard($PAGEDATA['assetsAssignmentsStatus'], $PAGEDATA['FINANCIALS'], $AUTH->data['instance']['instances_name']);

finish(true, null, $sortedAssets);

/** @OA\Post(
 *     path="/projects/assets/statusList.php", 
 *     summary="Get Asset Assignment Status List", 
 *     description="Get the list of statuses for an asset assignment  
Requires Instance Permission PROJECTS:VIEW
", 
 *     operationId="getAssetAssignmentStatusList", 
 *     tags={"project_assets"}, 
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
 *     @OA\Parameter(
 *         name="projects_id",
 *         in="query",
 *         description="Project ID",
 *         required="true", 
 *         @OA\Schema(
 *             type="number"), 
 *         ), 
 * )
 */