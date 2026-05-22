<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BackfillGlobalAssetTypeCurrency extends AbstractMigration
{
    public function up(): void
    {
        // The original migration (20260316120000) only backfilled instance-private
        // assetTypes. Global assetTypes (instances_id IS NULL) were left with
        // assetTypes_currency = NULL, making them invisible to the visibility filter
        // "(instances_id IS NULL AND assetTypes_currency = X)" in searchType.php.
        //
        // For each global assetType, derive currency from the instance that has
        // the most assets of that type. Falls back to the instance with the lowest
        // instances_id if no assets exist (e.g. freshly seeded dev environment).
        $this->execute("
            UPDATE assetTypes AT
            SET AT.assetTypes_currency = COALESCE(
                (
                    SELECT i.instances_config_currency
                    FROM assets a
                    INNER JOIN instances i ON a.instances_id = i.instances_id
                    WHERE a.assetTypes_id = AT.assetTypes_id
                      AND a.assets_deleted = 0
                    GROUP BY i.instances_config_currency
                    ORDER BY COUNT(*) DESC
                    LIMIT 1
                ),
                (
                    SELECT instances_config_currency
                    FROM instances
                    WHERE instances_deleted = 0
                    ORDER BY instances_id ASC
                    LIMIT 1
                )
            )
            WHERE AT.instances_id IS NULL
              AND AT.assetTypes_currency IS NULL;
        ");
    }

    public function down(): void
    {
        // Not reversible — we cannot know which NULLs were intentional vs missed.
    }
}
