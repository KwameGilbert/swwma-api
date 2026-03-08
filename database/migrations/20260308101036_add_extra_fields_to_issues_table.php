<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExtraFieldsToIssuesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('issues');
        $table->addColumn('issue_type', 'enum', [
                'values' => ['community_based', 'individual_based'], 
                'default' => 'community_based', 
                'null' => false, 
                'after' => 'description'
              ])
              ->addColumn('people_affected', 'integer', ['null' => true, 'after' => 'sub_sector_id'])
              ->addColumn('estimated_budget', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'after' => 'people_affected'])
              ->update();
    }
}
