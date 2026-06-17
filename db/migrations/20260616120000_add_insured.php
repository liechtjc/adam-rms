<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInsured extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE assetTypes ADD COLUMN assetTypes_insured TINYINT(1) NOT NULL DEFAULT 0;");
        $this->execute("ALTER TABLE assets ADD COLUMN assets_insured TINYINT(1) DEFAULT NULL;");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE assetTypes DROP COLUMN assetTypes_insured;");
        $this->execute("ALTER TABLE assets DROP COLUMN assets_insured;");
    }
}
