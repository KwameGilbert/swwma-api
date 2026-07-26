<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class UpdateLookupSchema extends AbstractMigration
{
    public function change(): void
    {
        // Update locations
        $tableLocations = $this->table('locations');
        if (!$tableLocations->hasColumn('status')) {
            $tableLocations->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
                ->update();
        }

        // Update sectors
        $tableSectors = $this->table('sectors');
        if (!$tableSectors->hasColumn('description')) {
            $tableSectors->addColumn('description', 'text', ['null' => true])
                ->update();
        }
        if (!$tableSectors->hasColumn('status')) {
            $tableSectors->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
                ->update();
        }

        // Update sub_sectors
        $tableSubSectors = $this->table('sub_sectors');
        if (!$tableSubSectors->hasColumn('description')) {
            $tableSubSectors->addColumn('description', 'text', ['null' => true])
                ->update();
        }
        if (!$tableSubSectors->hasColumn('status')) {
            $tableSubSectors->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
                ->update();
        }
    }
}
