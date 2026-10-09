<?php
/**
 * Build the columns of the project asset board (asset dispatch).
 *
 * The project's own instance owns the status set, so every assignment in the project - including assets
 * hired in from other instances - is placed in a column of that one set.
 *
 * @param array  $statuses         The project instance's (non-deleted) statuses, ordered by assetsAssignmentsStatus_order
 * @param array  $financials       Output of projectFinance (uses assetsAssigned and assetsAssignedSUB)
 * @param string $ownInstanceName  Name of the project's instance, shown as the entity of its own assets
 * @return array Ordered list of statuses, each with an "assets" key. Assets carry an extra "board_entity" name
 */
function buildAssetBoard($statuses, $financials, $ownInstanceName)
{
    $columns = [];
    $byId = [];
    $byName = [];
    foreach ($statuses as $status) {
        $status['assets'] = [];
        $columns[] = $status;
        $index = count($columns) - 1;
        $byId[$status['assetsAssignmentsStatus_id']] = $index;
        $nameKey = strtolower(trim($status['assetsAssignmentsStatus_name']));
        if (!isset($byName[$nameKey])) $byName[$nameKey] = $index;
    }
    if (count($columns) == 0) return [];

    $groups = [[$ownInstanceName, $financials['assetsAssigned']]];
    foreach ($financials['assetsAssignedSUB'] as $instance) {
        $groups[] = [$instance['instance']['instances_name'], $instance['assets']];
    }

    foreach ($groups as [$entityName, $assetTypes]) {
        foreach ($assetTypes as $assetType) {
            foreach ($assetType['assets'] as $asset) {
                $index = 0; //No status (or one we can't place) goes in the first column
                if (isset($asset['assetsAssignmentsStatus_id']) && isset($byId[$asset['assetsAssignmentsStatus_id']])) {
                    $index = $byId[$asset['assetsAssignmentsStatus_id']];
                } elseif (!empty($asset['assetsAssignmentsStatus_name'])) {
                    //A status from another instance (pre-migration data) - fall back to the same name in the project's set
                    $nameKey = strtolower(trim($asset['assetsAssignmentsStatus_name']));
                    if (isset($byName[$nameKey])) $index = $byName[$nameKey];
                }
                $asset['board_entity'] = $entityName;
                $columns[$index]['assets'][] = $asset;
            }
        }
    }
    return $columns;
}
