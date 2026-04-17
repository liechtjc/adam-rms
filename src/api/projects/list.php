<?php
require_once __DIR__ . '/../apiHeadSecure.php';
$return = [];

if (!$AUTH->instancePermissionCheck("PROJECTS:VIEW")) die("401");

$DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("projects.projects_deleted", 0);
$DBLIB->where("projects.projects_archived", 0);
$DBLIB->join("clients", "projects.clients_id=clients.clients_id", "LEFT");
$DBLIB->orderBy("projects.projects_dates_deliver_start", "ASC");
$DBLIB->orderBy("projects.projects_name", "ASC");
$DBLIB->orderBy("projects.projects_created", "ASC");

if (isset($_POST['subprojects'])) {
    $DBLIB->where("projects.projects_parent_project_id IS NULL");
}

$projects = $DBLIB->get("projects", null, ["projects.projects_id", "projects.projects_name", "clients.clients_name", "projects.projects_manager", "projects.projectsStatuses_id"]);

foreach ($projects as $project) {
    $subprojectData = [];

    if (isset($_POST['subprojects'])) {
        $DBLIB->where("projects.projects_parent_project_id", $project['projects_id']);
        $DBLIB->where("projects.projects_deleted", 0);
        $DBLIB->where("projects.projects_archived", 0);
        $DBLIB->join("clients", "projects.clients_id=clients.clients_id", "LEFT");
        $DBLIB->orderBy("projects.projects_dates_deliver_start", "ASC");
        $DBLIB->orderBy("projects.projects_name", "ASC");
        $DBLIB->orderBy("projects.projects_created", "ASC");
        $subprojects = $DBLIB->get("projects", null, ["projects_id", "projects_archived", "projects_name", "clients_name", "projects_dates_deliver_start", "projects_dates_deliver_end", "projects_dates_use_start", "projects_dates_use_end", "projects_manager", "projectsStatuses_id"]);
        foreach ($subprojects as $subproject) {
            $subprojectData[] = [
                "projects_id" => $subproject['projects_id'],
                "projects_name" => $subproject['projects_name'],
                "clients_name" => $subproject['clients_name'],
                "projects_manager" => $subproject['projects_manager'],
                "thisProjectManager" => ($AUTH->data['users_userid'] == $subproject['projects_manager']),
                "projectsStatuses_id" => $subproject['projectsStatuses_id'],
            ];
        }
    }

    $return[] = [
        "projects_id" => $project['projects_id'],
        "projects_name" => $project['projects_name'],
        "clients_name" => $project['clients_name'],
        "projects_manager" => $project['projects_manager'],
        "thisProjectManager" => ($AUTH->data['users_userid'] == $project['projects_manager']),
        "projectsStatuses_id" => $project['projectsStatuses_id'],
        "subprojects" => $subprojectData,
    ];
}
finish(true, null, $return);

/** @OA\Post(
 *     path="/projects/list.php",
 *     summary="List",
 *     description="Get a list of projects
Requires Instance Permission PROJECTS:VIEW
",
 *     operationId="list",
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
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     description="List of projects",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="projects_id", type="number"),
 *                         @OA\Property(property="projects_name", type="string"),
 *                         @OA\Property(property="clients_name", type="string"),
 *                         @OA\Property(property="projects_manager", type="number"),
 *                         @OA\Property(property="thisProjectManager", type="boolean"),
 *                         @OA\Property(property="projectsStatuses_id", type="number", nullable=true),
 *                         @OA\Property(property="subprojects", type="array", description="Only populated when subprojects param is set", @OA\Items(type="object")),
 *                     ),
 *                 ),
 *             ),
 *         ),
 *     ),
 *     @OA\Response(
 *         response="404",
 *         description="Permission Error",
 *     ),
 *     @OA\Parameter(
 *         name="subprojects",
 *         in="query",
 *         description="If set, restricts the main list to top-level projects only and populates each project's subprojects array with its children",
 *         required=false,
 *         @OA\Schema(type="string"),
 *     ),
 * )
 */
