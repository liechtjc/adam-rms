<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * The project's instance owns the asset status set: every assignment in a project, including assets hired in from
 * other instances, uses the project instance's statuses.
 *
 * Assignments whose status belongs to a different instance than the project are remapped to the project instance's
 * status with the same name and order, then the same name, then the same order. Anything left over is cleared (NULL
 * shows in the first column of the board). Other instances' statuses are not deleted.
 */
final class RemapForeignAssetStatusesToProjectInstance extends AbstractMigration
{
    public function up(): void
    {
        $remap = function (string $matchCondition) {
            $this->execute("
                UPDATE assetsAssignments aa
                JOIN projects p ON aa.projects_id = p.projects_id
                JOIN assetsAssignmentsStatus cur ON aa.assetsAssignmentsStatus_id = cur.assetsAssignmentsStatus_id
                JOIN assetsAssignmentsStatus tgt ON tgt.instances_id = p.instances_id
                    AND tgt.assetsAssignmentsStatus_deleted = 0
                    AND ($matchCondition)
                SET aa.assetsAssignmentsStatus_id = tgt.assetsAssignmentsStatus_id
                WHERE cur.instances_id <> p.instances_id
            ");
        };
        $remap("tgt.assetsAssignmentsStatus_name = cur.assetsAssignmentsStatus_name AND tgt.assetsAssignmentsStatus_order <=> cur.assetsAssignmentsStatus_order");
        $remap("tgt.assetsAssignmentsStatus_name = cur.assetsAssignmentsStatus_name");
        $remap("tgt.assetsAssignmentsStatus_order <=> cur.assetsAssignmentsStatus_order");

        $this->execute("
            UPDATE assetsAssignments aa
            JOIN projects p ON aa.projects_id = p.projects_id
            JOIN assetsAssignmentsStatus cur ON aa.assetsAssignmentsStatus_id = cur.assetsAssignmentsStatus_id
            SET aa.assetsAssignmentsStatus_id = NULL
            WHERE cur.instances_id <> p.instances_id
        ");
    }

    public function down(): void
    {
        // The original per-asset-instance statuses cannot be reconstructed, so this is intentionally a no-op.
    }
}
