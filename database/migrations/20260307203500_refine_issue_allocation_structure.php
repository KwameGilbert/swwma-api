<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class RefineIssueAllocationStructure extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('issue_resource_allocations');
        $table->renameColumn('personnel_assigned', 'personnel_items')
            ->renameColumn('materials_provided', 'material_items')
            ->update();

        // Change columns to longtext for JSON storage (Phinx doesn't always have JSON type depending on version/DB, but longtext works)
        $table->changeColumn('personnel_items', 'text', ['null' => true, 'comment' => 'Structured JSON array of personnel items'])
            ->changeColumn('material_items', 'text', ['null' => true, 'comment' => 'Structured JSON array of material items'])
            ->update();
    }
}
