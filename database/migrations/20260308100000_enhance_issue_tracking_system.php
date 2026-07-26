<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class EnhanceIssueTrackingSystem extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('issues');
        
        // Add tracking columns
        $table->addColumn('agent_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'constituent_id'])
              ->addColumn('officer_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'agent_id'])
              
              // Add Foreign Keys
              ->addForeignKey('agent_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION', 'constraint' => 'fk_issue_agent'])
              ->addForeignKey('officer_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION', 'constraint' => 'fk_issue_officer'])
              
              ->update();
    }
}
