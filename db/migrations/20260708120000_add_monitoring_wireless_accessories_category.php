<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMonitoringWirelessAccessoriesCategory extends AbstractMigration
{
    /**
     * Adds an "Accessories" category to the "Monitoring + Wireless" group (id 8).
     * Rank 485 slots it after "Video Cables" (480) and before the next group's
     * first category (490), using the gap left by the step-10 rank scheme from
     * db/migrations/20260707120000_reorganize_asset_categories.php.
     */
    public function up(): void
    {
        $preCountRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetCategories WHERE assetCategories_deleted = 0");
        $preCount = (int) $preCountRow['c'];

        $this->execute("INSERT INTO assetCategories
            (assetCategories_name, assetCategories_fontAwesome, assetCategories_rank, assetCategoriesGroups_id, instances_id, assetCategories_deleted)
            VALUES ('Accessories', 'fas fa-cog', 485, 8, NULL, 0);");
        $newId = (int) $this->getAdapter()->getConnection()->lastInsertId();
        if (!$newId) {
            throw new Exception('AddMonitoringWirelessAccessoriesCategory: failed to insert new category');
        }

        $postCountRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetCategories WHERE assetCategories_deleted = 0");
        $postCount = (int) $postCountRow['c'];
        if ($postCount !== $preCount + 1) {
            throw new Exception("AddMonitoringWirelessAccessoriesCategory: expected active category count to increase by 1 (from {$preCount} to " . ($preCount + 1) . "), found {$postCount}");
        }
    }

    /**
     * Aborts (does not delete) if any assetTypes have already been assigned to
     * this category, to avoid destroying user-created data on rollback.
     */
    public function down(): void
    {
        $row = $this->fetchRow("SELECT assetCategories_id FROM assetCategories WHERE assetCategories_name = 'Accessories' AND assetCategoriesGroups_id = 8 AND assetCategories_deleted = 0");
        if (!$row) {
            return;
        }
        $id = (int) $row['assetCategories_id'];

        $usageRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetTypes WHERE assetCategories_id = {$id}");
        if ((int) $usageRow['c'] > 0) {
            throw new Exception("AddMonitoringWirelessAccessoriesCategory down(): category id {$id} has assetTypes referencing it - cannot safely delete. Reassign those asset types manually before rolling back.");
        }

        $this->execute("DELETE FROM assetCategories WHERE assetCategories_id = {$id};");
    }
}
