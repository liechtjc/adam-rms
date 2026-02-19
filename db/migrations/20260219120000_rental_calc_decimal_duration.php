<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RentalCalcDecimalDuration extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE projects
                MODIFY COLUMN projects_dates_finances_days DECIMAL(5,1) UNSIGNED DEFAULT NULL NULL,
                MODIFY COLUMN projects_dates_finances_weeks DECIMAL(5,1) UNSIGNED DEFAULT NULL NULL;
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE projects
                MODIFY COLUMN projects_dates_finances_days SMALLINT UNSIGNED DEFAULT NULL NULL,
                MODIFY COLUMN projects_dates_finances_weeks SMALLINT UNSIGNED DEFAULT NULL NULL;
        ");
    }
}
