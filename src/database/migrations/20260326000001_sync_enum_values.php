<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SyncEnumValues extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('issue_reports');

        // Update status enum to match IssueReport model constants
        $table->changeColumn('status', 'enum', [
            'values' => [
                'submitted',
                'under_officer_review',
                'forwarded_to_admin',
                'assigned_to_task_force',
                'assessment_in_progress',
                'assessment_submitted',
                'resources_allocated',
                'resolution_in_progress',
                'resolution_submitted',
                'resolved',
                'closed',
                'rejected'
            ],
            'default' => 'submitted',
            'null' => false
        ]);

        // Update priority enum (severity) to match IssueReport model constants
        $table->changeColumn('priority', 'enum', [
            'values' => ['low', 'medium', 'high', 'urgent'],
            'default' => 'medium',
            'null' => false
        ]);

        $table->update();
    }
}
