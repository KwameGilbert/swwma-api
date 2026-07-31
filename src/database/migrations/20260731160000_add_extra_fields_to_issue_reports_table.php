<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExtraFieldsToIssueReportsTable extends AbstractMigration
{
    /**
     * Reversible migration adding missing classification, location hierarchy,
     * and constituent columns to the issue_reports table.
     */
    public function change(): void
    {
        $table = $this->table('issue_reports');

        if ($table->exists()) {
            if (!$table->hasColumn('case_id')) {
                $table->addColumn('case_id', 'string', ['limit' => 50, 'null' => true, 'after' => 'id']);
            }
            if (!$table->hasColumn('issue_type')) {
                $table->addColumn('issue_type', 'enum', [
                    'values' => ['community_based', 'individual_based'],
                    'default' => 'community_based',
                    'null' => true,
                    'after' => 'sub_sector_id'
                ]);
            }
            if (!$table->hasColumn('affected_people_count')) {
                $table->addColumn('affected_people_count', 'integer', ['null' => true, 'signed' => false, 'after' => 'issue_type']);
            }
            if (!$table->hasColumn('location')) {
                $table->addColumn('location', 'string', ['limit' => 255, 'null' => true, 'after' => 'affected_people_count']);
            }
            if (!$table->hasColumn('main_community_id')) {
                $table->addColumn('main_community_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'location']);
            }
            if (!$table->hasColumn('smaller_community_id')) {
                $table->addColumn('smaller_community_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'main_community_id']);
            }
            if (!$table->hasColumn('suburb_id')) {
                $table->addColumn('suburb_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'smaller_community_id']);
            }
            if (!$table->hasColumn('cottage_id')) {
                $table->addColumn('cottage_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'suburb_id']);
            }
            if (!$table->hasColumn('constituent_name')) {
                $table->addColumn('constituent_name', 'string', ['limit' => 255, 'null' => true, 'after' => 'longitude']);
            }
            if (!$table->hasColumn('constituent_email')) {
                $table->addColumn('constituent_email', 'string', ['limit' => 255, 'null' => true, 'after' => 'constituent_name']);
            }
            if (!$table->hasColumn('constituent_contact')) {
                $table->addColumn('constituent_contact', 'string', ['limit' => 50, 'null' => true, 'after' => 'constituent_email']);
            }
            if (!$table->hasColumn('constituent_gender')) {
                $table->addColumn('constituent_gender', 'string', ['limit' => 20, 'null' => true, 'after' => 'constituent_contact']);
            }
            if (!$table->hasColumn('constituent_address')) {
                $table->addColumn('constituent_address', 'string', ['limit' => 255, 'null' => true, 'after' => 'constituent_gender']);
            }
            if (!$table->hasColumn('reporter_name')) {
                $table->addColumn('reporter_name', 'string', ['limit' => 255, 'null' => true, 'after' => 'constituent_address']);
            }
            if (!$table->hasColumn('reporter_email')) {
                $table->addColumn('reporter_email', 'string', ['limit' => 255, 'null' => true, 'after' => 'reporter_name']);
            }
            if (!$table->hasColumn('reporter_phone')) {
                $table->addColumn('reporter_phone', 'string', ['limit' => 50, 'null' => true, 'after' => 'reporter_email']);
            }
            if (!$table->hasColumn('reporter_gender')) {
                $table->addColumn('reporter_gender', 'string', ['limit' => 20, 'null' => true, 'after' => 'reporter_phone']);
            }
            if (!$table->hasColumn('reporter_address')) {
                $table->addColumn('reporter_address', 'string', ['limit' => 255, 'null' => true, 'after' => 'reporter_gender']);
            }
            if (!$table->hasColumn('additional_notes')) {
                $table->addColumn('additional_notes', 'text', ['null' => true, 'after' => 'reporter_address']);
            }
            if (!$table->hasColumn('priority')) {
                $table->addColumn('priority', 'enum', [
                    'values' => ['low', 'medium', 'high', 'urgent'],
                    'default' => 'medium',
                    'null' => true,
                    'after' => 'status'
                ]);
            }
            $table->update();
        }
    }
}
