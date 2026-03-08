<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddAuditLogsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('audit_logs', ['id' => true]);
        $table->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('action', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('metadata', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'])
            ->addIndex(['action'])
            ->addIndex(['ip_address'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
