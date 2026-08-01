<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateIssueReportStatusHistoryColumns extends AbstractMigration
{
    /**
     * Reversible migration renaming legacy columns (user_id -> changed_by,
     * previous_status -> old_status, comment -> notes) in issue_report_status_history table.
     */
    public function change(): void
    {
        $table = $this->table('issue_report_status_history');

        if ($table->exists()) {
            if ($table->hasColumn('user_id') && !$table->hasColumn('changed_by')) {
                $table->renameColumn('user_id', 'changed_by');
            } elseif (!$table->hasColumn('changed_by')) {
                $table->addColumn('changed_by', 'integer', ['null' => false, 'signed' => false, 'after' => 'issue_report_id']);
            }

            if ($table->hasColumn('previous_status') && !$table->hasColumn('old_status')) {
                $table->renameColumn('previous_status', 'old_status');
            } elseif (!$table->hasColumn('old_status')) {
                $table->addColumn('old_status', 'string', ['limit' => 50, 'null' => true, 'after' => 'changed_by']);
            }

            if ($table->hasColumn('comment') && !$table->hasColumn('notes')) {
                $table->renameColumn('comment', 'notes');
            } elseif (!$table->hasColumn('notes')) {
                $table->addColumn('notes', 'text', ['null' => true, 'after' => 'new_status']);
            }

            $table->update();
        }
    }
}
