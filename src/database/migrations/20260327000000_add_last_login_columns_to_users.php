<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLastLoginColumnsToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Safely adds missing last_login_at, last_login_ip, and first_login columns to users table.
     */
    public function change(): void
    {
        $table = $this->table('users');

        if ($table->exists()) {
            $hasChanges = false;

            if (!$table->hasColumn('first_login')) {
                $table->addColumn('first_login', 'boolean', [
                    'default' => true,
                    'null' => false,
                    'after' => 'status'
                ]);
                $hasChanges = true;
            }

            if (!$table->hasColumn('last_login_at')) {
                $table->addColumn('last_login_at', 'timestamp', [
                    'null' => true,
                    'after' => $table->hasColumn('first_login') ? 'first_login' : 'status'
                ]);
                $hasChanges = true;
            }

            if (!$table->hasColumn('last_login_ip')) {
                $table->addColumn('last_login_ip', 'string', [
                    'limit' => 45,
                    'null' => true,
                    'after' => $table->hasColumn('last_login_at') ? 'last_login_at' : 'status'
                ]);
                $hasChanges = true;
            }

            if ($hasChanges) {
                $table->update();
            }
        }
    }
}
