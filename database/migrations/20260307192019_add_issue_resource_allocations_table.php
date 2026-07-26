<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddIssueResourceAllocationsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('issue_resource_allocations', ['id' => true]);
        $table->addColumn('issues_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('allocated_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('personnel_assigned', 'text', ['null' => true, 'comment' => 'Details of personnel/task force assigned'])
            ->addColumn('materials_provided', 'text', ['null' => true, 'comment' => 'Details of physical materials/equipment'])
            ->addColumn('additional_notes', 'text', ['null' => true])
            ->addColumn('allocation_date', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['issues_id'])
            ->addIndex(['allocated_by'])
            ->addForeignKey('issues_id', 'issues', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('allocated_by', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
