<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class MakeSubSectorNullableInIssues extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     */
    public function change(): void
    {
        $table = $this->table('issues');
        $table->changeColumn('sub_sector_id', 'integer', [
            'signed' => false,
            'null' => true
        ])->update();
    }
}
