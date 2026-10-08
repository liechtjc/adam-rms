<?php
require_once __DIR__ . '/../../apiHeadSecure.php';

if (!$AUTH->instancePermissionCheck("PROJECTS:PROJECT_ASSETS:EDIT:ASSIGNMENT_STATUS") or !isset($_POST['projects_id']) or !isset($_POST['assetsAssignments_status']) or !isset($_POST['text']) or strlen($_POST['text']) < 1) finish(false, ["message" => "Missing required fields","code"=>"MISSINGFIELDS"]);

/**
 * Find assignments in the project that the scanned (unassigned) asset could replace.
 * Only assets of the same asset type that have never been given a status are candidates: once an asset has a
 * status it has been handled, and swapping it would lose the traceability of the physical asset.
 * @return array|false
 */
function findSwapCandidates($tag, $projectsId, $targetStatusId)
{
    global $DBLIB, $AUTH;
    $DBLIB->where("assets.assets_deleted", 0);
    $DBLIB->where("assets.assets_tag", $tag);
    $DBLIB->where("assets.instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->join("assetTypes", "assets.assetTypes_id=assetTypes.assetTypes_id", "LEFT");
    $scanned = $DBLIB->getone("assets", ["assets.assets_id", "assets.assets_tag", "assets.assetTypes_id", "assetTypes.assetTypes_name"]);
    if (!$scanned) return false;

    $DBLIB->where("assetsAssignmentsStatus_id", $targetStatusId);
    $DBLIB->where("instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->where("assetsAssignmentsStatus_deleted", 0);
    if (!$DBLIB->getone("assetsAssignmentsStatus", ["assetsAssignmentsStatus_id"])) return false;

    $DBLIB->where("assetsAssignments.projects_id", $projectsId);
    $DBLIB->where("assetsAssignments.assetsAssignments_deleted", 0);
    $DBLIB->where("assetsAssignments.assetsAssignments_linkedTo", NULL, "IS");
    $DBLIB->where("assetsAssignments.assetsAssignmentsStatus_id", NULL, "IS");
    $DBLIB->where("assets.assetTypes_id", $scanned['assetTypes_id']);
    $DBLIB->where("assets.assets_deleted", 0);
    $DBLIB->join("assets", "assetsAssignments.assets_id=assets.assets_id", "LEFT");
    $rows = $DBLIB->get("assetsAssignments", null, ["assetsAssignments.assetsAssignments_id", "assets.assets_tag"]);
    if (!$rows) return false;

    $candidates = [];
    foreach ($rows as $row) $candidates[] = ["assetsAssignments_id" => $row['assetsAssignments_id'], "assets_tag" => $row['assets_tag']];
    return ["assets_id" => $scanned['assets_id'], "assets_tag" => $scanned['assets_tag'], "assetTypes_name" => $scanned['assetTypes_name'], "candidates" => $candidates];
}

$DBLIB->where("assetsAssignments.assetsAssignments_deleted",0); //Ignore assignments which have been removed from the project
$DBLIB->where("assets.assets_deleted",0);
$DBLIB->where("assets.assets_tag", $_POST["text"]);
$DBLIB->where("projects.projects_id", $_POST['projects_id']);
$DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("projects.projects_deleted", 0);
$DBLIB->join("projects", "assetsAssignments.projects_id=projects.projects_id", "LEFT");
$DBLIB->join("assets", "assetsAssignments.assets_id=assets.assets_id", "LEFT");
$assignment = $DBLIB->getone("assetsAssignments",["assets.assets_id", "assetsAssignments.assetsAssignments_id", "assetsAssignments.assetsAssignmentsStatus_id"]);
if (!$assignment or $assignment['assets_id'] == null) {
    //Not in this project: if it is a free asset of the same type as one that is in a lower status, offer to swap
    $swap = findSwapCandidates($_POST["text"], $_POST['projects_id'], $_POST['assetsAssignments_status']);
    if ($swap) finish(false, ["message" => "Asset not in this project - it can be swapped with a not yet dispatched asset","code"=>"NOTINPROJECT", "swap" => $swap]);
    finish(false, ["message" => "Asset not found","code"=>"NOTFOUND"]);
}
$currentStatusId = $assignment['assetsAssignmentsStatus_id'];
if ($currentStatusId === null) { //No status stored: the board shows these in the first column, so treat it as the first status
    $DBLIB->where("instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->where("assetsAssignmentsStatus_deleted", 0);
    $DBLIB->orderBy("assetsAssignmentsStatus_order", "ASC");
    $firstStatus = $DBLIB->getone("assetsAssignmentsStatus", ["assetsAssignmentsStatus_id"]);
    if ($firstStatus) $currentStatusId = $firstStatus['assetsAssignmentsStatus_id'];
}
if ($currentStatusId == $_POST['assetsAssignments_status']) finish(true, null, ["assets_id" => $assignment['assets_id'], "unchanged" => true]); // Already in the target status

$DBLIB->where("assetsAssignmentsStatus_id", $_POST['assetsAssignments_status']);
$DBLIB->where("instances_id", $AUTH->data['instance']['instances_id']); // The project's instance owns the status set
$DBLIB->where("assetsAssignmentsStatus_deleted", 0);
$status = $DBLIB->getone("assetsAssignmentsStatus",["assetsAssignmentsStatus_id"]);
if (!$status or $status['assetsAssignmentsStatus_id'] == null) finish(false, ["message" => "Status not found","code"=>"STATUSNOTFOUND"]);

$DBLIB->where("assetsAssignments_id", $assignment['assetsAssignments_id']);
$update = $DBLIB->update("assetsAssignments", ["assetsAssignmentsStatus_id" => $status['assetsAssignmentsStatus_id']], 1);
if (!$update) finish(false, ["message" => "Asset not assigned to project","code"=>"NOTASSIGNED"]);
else {
    $bCMS->auditLog("EDIT-STATUS", "assetsAssignments", $assignment['assetsAssignments_id'] . " set from " . $assignment['assetsAssignmentsStatus_id'] . " to " . $status['assetsAssignmentsStatus_id'] . " by direct tag entry", $AUTH->data['users_userid'],null, $_POST['projects_id']);
    finish(true, null, ["assets_id" => $assignment['assets_id']]);
}

/**
 *  @OA\Post(
 *      path="/projects/assets/setStatusByTag.php",
 *      summary="Set Asset Status by Tag",
 *      description="Set asset status for a project by the asset's tag",
 *      operationId="setStatusByTag",
 *      tags={"project_assets"},
 *      @OA\Response(
 *          response="200",
 *          description="Success",
 *          @OA\MediaType(
 *             mediaType="application/json", 
 *             @OA\Schema(ref="#/components/schemas/SimpleResponse"),
 *         ),
 *      ),
 *      @OA\Parameter(
 *          name="text",
 *          in="query",
 *          description="Value of the asset tag",
 *          required="true",
 *          @OA\Schema(
 *              type="string",
 *          ),
 *      ),
 *      @OA\Parameter(
 *          name="assetsAssignments_status",
 *          in="query",
 *          description="Status Id to set asset to",
 *          required="true",
 *          @OA\Schema(
 *              type="number",
 *          ),
 *      ),
 *  )
 */