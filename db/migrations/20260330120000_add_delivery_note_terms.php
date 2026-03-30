<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeliveryNoteTerms extends AbstractMigration
{
    public function change(): void
    {
        $this->table('instances')
            ->addColumn('instances_deliveryNoteTerms', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'instances_quoteTerms'
            ])
            ->save();
    }
}
