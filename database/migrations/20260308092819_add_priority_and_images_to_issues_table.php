<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddPriorityAndImagesToIssuesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('issues');
        $table->addColumn('priority', 'enum', ['values' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium', 'after' => 'status'])
              ->addColumn('images', 'json', ['null' => true, 'after' => 'priority'])
              ->update();
    }
}
