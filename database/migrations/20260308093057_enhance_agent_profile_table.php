<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class EnhanceAgentProfileTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('agent_profiles');
        $table->addColumn('address', 'text', ['null' => true, 'after' => 'last_name'])
              ->addColumn('agent_code', 'string', ['limit' => 50, 'null' => true, 'after' => 'user_id'])
              ->addColumn('id_type', 'string', ['limit' => 50, 'null' => true, 'after' => 'gender'])
              ->addColumn('id_number', 'string', ['limit' => 50, 'null' => true, 'after' => 'id_type'])
              ->addColumn('emergency_contact_name', 'string', ['limit' => 100, 'null' => true, 'after' => 'address'])
              ->addColumn('emergency_contact_phone', 'string', ['limit' => 50, 'null' => true, 'after' => 'emergency_contact_name'])
              ->update();
    }
}
