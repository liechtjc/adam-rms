<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ReorganizeAssetCategories extends AbstractMigration
{
    /**
     * Renames categories/groups, adds a new "Transport + Protection" group with
     * its categories, moves existing V-mount asset types into "V-Mount Batteries",
     * and renumbers assetCategories_rank to match the intended display order.
     *
     * No assetCategories_id / assetCategoriesGroups_id values are ever changed or
     * reused - only names, group membership of new rows, and rank.
     */
    public function up(): void
    {
        // Capture the pre-migration active category count so the self-verify step below
        // can assert a relative delta (+6) rather than an absolute total - other categories
        // (e.g. instance-scoped ones) may legitimately already exist beyond the base 44.
        $preCountRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetCategories WHERE assetCategories_deleted = 0");
        $preCount = (int) $preCountRow['c'];

        // 1. Move existing V-mount battery asset types into the renamed "V-Mount Batteries" (37)
        //    before category 39 is repurposed as "Power Stations".
        $this->execute("UPDATE assetTypes SET assetCategories_id = 37 WHERE assetCategories_id = 39;");

        // 2. Rename group 8.
        $this->execute("UPDATE assetCategoriesGroups SET assetCategoriesGroups_name = 'Monitoring + Wireless' WHERE assetCategoriesGroups_id = 8;");

        // 3. Rename categories.
        $renames = [
            21 => 'Specialty LED',
            25 => 'Lavalier Microphones',
            26 => 'Shotgun + Specialty Microphones',
            27 => 'Wireless',
            28 => 'Audio Mixers + Recorders',
            37 => 'V-Mount Batteries',
            38 => 'Small Format Batteries',
            39 => 'Power Stations',
            44 => 'Video Cables',
        ];
        foreach ($renames as $id => $name) {
            $escaped = str_replace("'", "''", $name);
            $this->execute("UPDATE assetCategories SET assetCategories_name = '{$escaped}' WHERE assetCategories_id = {$id};");
        }

        // 4. Insert the new group.
        $this->execute("INSERT INTO assetCategoriesGroups
            (assetCategoriesGroups_name, assetCategoriesGroups_fontAwesome, assetCategoriesGroups_order, instances_id, assetCategoriesGroups_deleted)
            VALUES ('Transport + Protection', 'fas fa-truck', 9, NULL, 0);");
        $transportGroupId = (int) $this->getAdapter()->getConnection()->lastInsertId();
        if (!$transportGroupId) {
            throw new Exception('ReorganizeAssetCategories: failed to insert Transport + Protection group');
        }

        // 5. Insert the new categories, capturing each id for the rank pass below.
        $newCategories = [
            ['name' => 'Stands', 'fontAwesome' => 'fas fa-grip-lines-vertical', 'group' => 4],
            ['name' => 'Timecode', 'fontAwesome' => 'fas fa-clock', 'group' => 5],
            ['name' => 'Cables', 'fontAwesome' => 'fas fa-network-wired', 'group' => 7],
            ['name' => 'Accessories', 'fontAwesome' => 'fas fa-cog', 'group' => 7],
            ['name' => 'Bags + Cases', 'fontAwesome' => 'fas fa-suitcase', 'group' => $transportGroupId],
            ['name' => 'Carts', 'fontAwesome' => 'fas fa-dolly', 'group' => $transportGroupId],
        ];
        $newCategoryIds = [];
        foreach ($newCategories as $category) {
            $escapedName = str_replace("'", "''", $category['name']);
            $this->execute("INSERT INTO assetCategories
                (assetCategories_name, assetCategories_fontAwesome, assetCategories_rank, assetCategoriesGroups_id, instances_id, assetCategories_deleted)
                VALUES ('{$escapedName}', '{$category['fontAwesome']}', 999, {$category['group']}, NULL, 0);");
            $newId = (int) $this->getAdapter()->getConnection()->lastInsertId();
            if (!$newId) {
                throw new Exception("ReorganizeAssetCategories: failed to insert new category '{$category['name']}'");
            }
            $newCategoryIds[$category['name']] = $newId;
        }

        // 6. Renumber assetCategories_rank for all 50 rows to match the intended display
        //    order, using step-10 spacing so a single future addition doesn't require
        //    another full renumber.
        $rankMap = [
            1 => 10, 2 => 20, 3 => 30, 4 => 40, 5 => 50, 6 => 60, 7 => 70, 8 => 80,
            9 => 90, 10 => 100, 11 => 110, 12 => 120, 13 => 130, 14 => 140, 15 => 150,
            16 => 160, 17 => 170, 18 => 180, 19 => 190, 20 => 200, 21 => 210, 22 => 220,
            23 => 230,
            $newCategoryIds['Stands'] => 240,
            24 => 250, 25 => 260, 26 => 270, 27 => 280, 28 => 290, 29 => 300,
            $newCategoryIds['Timecode'] => 310,
            30 => 320, 31 => 330, 32 => 340, 33 => 350, 34 => 360, 35 => 370, 36 => 380,
            37 => 390, 38 => 400, 39 => 410, 40 => 420,
            $newCategoryIds['Cables'] => 430,
            $newCategoryIds['Accessories'] => 440,
            41 => 450, 42 => 460, 43 => 470, 44 => 480,
            $newCategoryIds['Bags + Cases'] => 490,
            $newCategoryIds['Carts'] => 500,
        ];
        if (count($rankMap) !== 50) {
            throw new Exception('ReorganizeAssetCategories: rank map does not cover all 50 categories');
        }
        foreach ($rankMap as $id => $rank) {
            $this->execute("UPDATE assetCategories SET assetCategories_rank = {$rank} WHERE assetCategories_id = {$id};");
        }

        // 7. Self-verify before committing.
        $postCountRow = $this->fetchRow("SELECT COUNT(*) AS c FROM assetCategories WHERE assetCategories_deleted = 0");
        $postCount = (int) $postCountRow['c'];
        if ($postCount !== $preCount + 6) {
            throw new Exception("ReorganizeAssetCategories: expected active category count to increase by 6 (from {$preCount} to " . ($preCount + 6) . "), found {$postCount}");
        }
        $dupRow = $this->fetchRow("SELECT COUNT(*) AS c, COUNT(DISTINCT assetCategories_rank) AS d FROM assetCategories WHERE assetCategories_deleted = 0");
        if ((int) $dupRow['c'] !== (int) $dupRow['d']) {
            throw new Exception('ReorganizeAssetCategories: duplicate assetCategories_rank values detected after renumbering');
        }
    }

    /**
     * Reverts renames, the new group/categories, and the rank renumbering.
     *
     * The asset type merge (39 -> 37) performed in up() is NOT reversible here:
     * there is no record of which specific assetTypes rows moved, so restoring
     * category 39's old name would silently mislabel gear that may have been
     * legitimately added to "Power Stations" since. If a true data-level rollback
     * of the merge is ever required, it must come from a pre-migration backup.
     *
     * Guards below abort (throwing rolls back this call's own transaction) rather
     * than deleting anything a user may have created since up() ran.
     */
    public function down(): void
    {
        $transportGroupRow = $this->fetchRow("SELECT assetCategoriesGroups_id FROM assetCategoriesGroups WHERE assetCategoriesGroups_name = 'Transport + Protection' AND assetCategoriesGroups_deleted = 0");
        $transportGroupId = $transportGroupRow ? (int) $transportGroupRow['assetCategoriesGroups_id'] : null;

        $newCategoryLookups = [
            ['name' => 'Stands', 'group' => 4],
            ['name' => 'Timecode', 'group' => 5],
            ['name' => 'Cables', 'group' => 7],
            ['name' => 'Accessories', 'group' => 7],
        ];
        if ($transportGroupId !== null) {
            $newCategoryLookups[] = ['name' => 'Bags + Cases', 'group' => $transportGroupId];
            $newCategoryLookups[] = ['name' => 'Carts', 'group' => $transportGroupId];
        }

        $newCategoryIds = [];
        foreach ($newCategoryLookups as $lookup) {
            $escapedName = str_replace("'", "''", $lookup['name']);
            $row = $this->fetchRow("SELECT assetCategories_id FROM assetCategories WHERE assetCategories_name = '{$escapedName}' AND assetCategoriesGroups_id = {$lookup['group']} AND assetCategories_deleted = 0");
            if ($row) {
                $newCategoryIds[] = (int) $row['assetCategories_id'];
            }
        }

        // Guard A: none of the new categories may be referenced by assetTypes.
        foreach ($newCategoryIds as $id) {
            $row = $this->fetchRow("SELECT COUNT(*) AS c FROM assetTypes WHERE assetCategories_id = {$id}");
            if ((int) $row['c'] > 0) {
                throw new Exception("ReorganizeAssetCategories down(): category id {$id} has assetTypes referencing it - cannot safely delete. Reassign those asset types manually before rolling back.");
            }
        }

        // Guard B: category 39 ("Power Stations") must not have acquired its own asset types.
        $row = $this->fetchRow("SELECT COUNT(*) AS c FROM assetTypes WHERE assetCategories_id = 39");
        if ((int) $row['c'] > 0) {
            throw new Exception("ReorganizeAssetCategories down(): category 39 now has assetTypes assigned (created as 'Power Stations' after this migration ran). Renaming it back to 'Floor batteries AC 230v' would mislabel that gear. Resolve manually before rolling back.");
        }

        // Guard C: the Transport + Protection group must only contain the categories we created.
        if ($transportGroupId !== null) {
            $row = $this->fetchRow("SELECT COUNT(*) AS c FROM assetCategories WHERE assetCategoriesGroups_id = {$transportGroupId} AND assetCategories_deleted = 0");
            $expected = count(array_filter($newCategoryLookups, fn ($l) => $l['group'] === $transportGroupId));
            if ((int) $row['c'] !== $expected) {
                throw new Exception('ReorganizeAssetCategories down(): Transport + Protection group contains unexpected categories - aborting to avoid deleting user-created data.');
            }
        }

        // All guards passed - safe to revert.
        foreach ($newCategoryIds as $id) {
            $this->execute("DELETE FROM assetCategories WHERE assetCategories_id = {$id};");
        }
        if ($transportGroupId !== null) {
            $this->execute("DELETE FROM assetCategoriesGroups WHERE assetCategoriesGroups_id = {$transportGroupId};");
        }

        $this->execute("UPDATE assetCategoriesGroups SET assetCategoriesGroups_name = 'Monitoring et wireless' WHERE assetCategoriesGroups_id = 8;");

        $originalNames = [
            21 => 'Practicals',
            25 => 'Wireless Microphones',
            26 => 'Wired Microphones',
            27 => 'Camera Top Microphones',
            28 => 'Audio Mixers & Recorders',
            37 => 'Floor batteries AC 230v',
            38 => 'Floor batteries DC',
            39 => 'V-mount',
            44 => 'SDI cables',
        ];
        foreach ($originalNames as $id => $name) {
            $escaped = str_replace("'", "''", $name);
            $this->execute("UPDATE assetCategories SET assetCategories_name = '{$escaped}' WHERE assetCategories_id = {$id};");
        }

        $this->execute("UPDATE assetCategories SET assetCategories_rank = assetCategories_id WHERE assetCategories_id BETWEEN 1 AND 44;");
    }
}
