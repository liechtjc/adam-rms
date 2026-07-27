<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInsuranceAndVat extends AbstractMigration
{
    public function change(): void
    {
        $this->table('projects')
            ->addColumn('projects_insurance_rate', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'default' => 10.00,
            ])
            ->addColumn('projects_insurance_amount', 'integer', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('projects_vat_export', 'boolean', [
                'null' => false,
                'default' => false,
            ])
            ->save();

        $this->table('instances')
            ->addColumn('instances_config_vatRate', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'default' => 8.10,
            ])
            ->save();

        $this->table('projectsFinanceCache')
            ->addColumn('projectsFinanceCache_insuranceTotal', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('projectsFinanceCache_vatTotal', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->save();
    }
}
