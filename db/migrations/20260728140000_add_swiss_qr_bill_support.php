<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSwissQrBillSupport extends AbstractMigration
{
    public function change(): void
    {
        $this->table('clients')
            ->addColumn('clients_contact', 'string', [
                'null' => true,
                'limit' => 500,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'clients_address',
            ])
            ->addColumn('clients_postcode', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'clients_contact',
            ])
            ->addColumn('clients_city', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'clients_postcode',
            ])
            ->addColumn('clients_country', 'string', [
                'null' => true,
                'limit' => 2,
                'default' => 'CH',
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'clients_city',
            ])
            ->save();

        $this->table('instances')
            ->addColumn('instances_config_qrBillName', 'string', [
                'null' => true,
                'limit' => 500,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'instances_config_currency',
            ])
            ->addColumn('instances_config_qrBillIban', 'string', [
                'null' => true,
                'limit' => 34,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('instances_config_qrBillStreet', 'string', [
                'null' => true,
                'limit' => 500,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'instances_config_qrBillIban',
            ])
            ->addColumn('instances_config_qrBillBuildingNumber', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'instances_config_qrBillStreet',
            ])
            ->addColumn('instances_config_qrBillPostcode', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'instances_config_qrBillBuildingNumber',
            ])
            ->addColumn('instances_config_qrBillCity', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'instances_config_qrBillPostcode',
            ])
            ->addColumn('instances_config_qrBillCountry', 'string', [
                'null' => true,
                'limit' => 2,
                'default' => 'CH',
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'instances_config_qrBillCity',
            ])
            ->addColumn('instances_config_qrBillReferencePrefix', 'string', [
                'null' => true,
                'limit' => 11,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'instances_config_qrBillCountry',
            ])
            ->addColumn('instances_config_qrBillIncludeProjectName', 'boolean', [
                'null' => false,
                'default' => false,
                'after' => 'instances_config_qrBillReferencePrefix',
            ])
            ->save();
    }
}
