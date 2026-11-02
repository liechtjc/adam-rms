<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBmountAndAcPowerCategories extends AbstractMigration
{
    private const CATEGORIES = [
        // Rank 395: after "V-Mount Batteries" (390), before "Small Format Batteries" (400) in "Power" (group 7).
        ['name' => 'B-Mount Batteries', 'fontAwesome' => 'fas fa-car-battery', 'rank' => 395, 'group' => 7],
        // Rank 245: after "Stands" (240), before "Accessories" (250) in "Lighting" (group 4).
        ['name' => 'AC Power & Extension Cords', 'fontAwesome' => 'fas fa-plug', 'rank' => 245, 'group' => 4],
        // Rank 215: after "Specialty LED" (210), before "Light modifiers" (220) in "Lighting" (group 4).
        ['name' => 'Tungsten', 'fontAwesome' => 'fas fa-sun', 'rank' => 215, 'group' => 4],
    ];

    /**
     * Adds "B-Mount Batteries" to the "Power" group, and "AC Power & Extension Cords"
     * and "Tungsten" to the "Lighting" group, using the gaps left by the step-10 rank scheme from
     * db/migrations/20260707120000_reorganize_asset_categories.php.
     */
    public function up(): void
    {
        $preCountRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetCategories WHERE assetCategories_deleted = 0");
        $preCount = (int) $preCountRow['c'];

        foreach (self::CATEGORIES as $category) {
            $escapedName = str_replace("'", "''", $category['name']);
            $this->execute("INSERT INTO assetCategories
                (assetCategories_name, assetCategories_fontAwesome, assetCategories_rank, assetCategoriesGroups_id, instances_id, assetCategories_deleted)
                VALUES ('{$escapedName}', '{$category['fontAwesome']}', {$category['rank']}, {$category['group']}, NULL, 0);");
            $newId = (int) $this->getAdapter()->getConnection()->lastInsertId();
            if (!$newId) {
                throw new Exception("AddBmountAndAcPowerCategories: failed to insert new category '{$category['name']}'");
            }
        }

        $postCountRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetCategories WHERE assetCategories_deleted = 0");
        $postCount = (int) $postCountRow['c'];
        $expected = $preCount + count(self::CATEGORIES);
        if ($postCount !== $expected) {
            throw new Exception("AddBmountAndAcPowerCategories: expected active category count to be {$expected}, found {$postCount}");
        }
    }

    /**
     * Aborts (does not delete) if any assetTypes have already been assigned to
     * these categories, to avoid destroying user-created data on rollback.
     */
    public function down(): void
    {
        $ids = [];
        foreach (self::CATEGORIES as $category) {
            $escapedName = str_replace("'", "''", $category['name']);
            $row = $this->fetchRow("SELECT assetCategories_id FROM assetCategories WHERE assetCategories_name = '{$escapedName}' AND assetCategoriesGroups_id = {$category['group']} AND instances_id IS NULL AND assetCategories_deleted = 0");
            if (!$row) {
                continue;
            }
            $id = (int) $row['assetCategories_id'];

            $usageRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetTypes WHERE assetCategories_id = {$id}");
            if ((int) $usageRow['c'] > 0) {
                throw new Exception("AddBmountAndAcPowerCategories down(): category id {$id} has assetTypes referencing it - cannot safely delete. Reassign those asset types manually before rolling back.");
            }
            $ids[] = $id;
        }

        foreach ($ids as $id) {
            $this->execute("DELETE FROM assetCategories WHERE assetCategories_id = {$id};");
        }
    }
}
