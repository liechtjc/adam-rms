<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAssetTypeCurrency extends AbstractMigration
{
    public function up(): void
    {
        // Add currency column to assetTypes table
        // NULL = not yet assigned (legacy rows before this migration)
        // For global assetTypes (instances_id IS NULL), this column determines
        // which currency the monetary fields (dayRate, weekRate, value) are stored in,
        // allowing currency-based filtering when showing global types to instances.
        $this->execute("
            ALTER TABLE assetTypes
                ADD COLUMN assetTypes_currency VARCHAR(3) NULL DEFAULT NULL
                AFTER assetTypes_inserted;
        ");

        // Backfill existing instance-scoped assetTypes with their instance's currency
        $this->execute("
            UPDATE assetTypes AT
            LEFT JOIN instances I ON AT.instances_id = I.instances_id
            SET AT.assetTypes_currency = I.instances_config_currency
            WHERE AT.instances_id IS NOT NULL;
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE assetTypes
                DROP COLUMN assetTypes_currency;
        ");
    }
}
