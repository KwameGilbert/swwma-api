<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddAuthTables extends AbstractMigration
{
    public function change(): void
    {
        // Table: email_verification_tokens
        $tableEvi = $this->table('email_verification_tokens', ['id' => true]);
        $tableEvi->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('used_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Table: password_resets
        $tablePr = $this->table('password_resets', ['id' => false, 'primary_key' => ['email', 'token']]);
        $tablePr->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['email'])
            ->addIndex(['token'])
            ->create();

        // Table: refresh_tokens
        $tableRt = $this->table('refresh_tokens', ['id' => true]);
        $tableRt->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('device_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('revoked_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
