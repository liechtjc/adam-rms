<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAssetTypeInternal extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE assetTypes ADD COLUMN assetTypes_internal TINYINT(1) NOT NULL DEFAULT 0;");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE assetTypes DROP COLUMN assetTypes_internal;");
    }
}
