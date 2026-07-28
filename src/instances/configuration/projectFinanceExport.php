<?php
require_once __DIR__ . '/../../common/headSecure.php';

$PAGEDATA['pageConfig'] = ["TITLE" => "Project Finance Export", "BREADCRUMB" => false];

if (!$AUTH->instancePermissionCheck("PROJECTS:EXPORT:FINANCE")) die($TWIG->render('404.twig', $PAGEDATA));

$DBLIB->where("projectsStatuses.instances_id", $AUTH->data['instance']["instances_id"]);
$DBLIB->where("projectsStatuses.projectsStatuses_deleted", 0);
$DBLIB->orderBy("projectsStatuses.projectsStatuses_rank", "ASC");
$PAGEDATA['projectStatuses'] = $DBLIB->get("projectsStatuses");

echo $TWIG->render('instances/configuration/instances_configuration_projectFinanceExport.twig', $PAGEDATA);
?>
