<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSpecificLocationAndEstimatedBudgetToIssueReportsTable extends AbstractMigration
{
    /**
     * Reversible migration adding specific_location and estimated_budget columns to issue_reports table.
     */
    public function change(): void
    {
        $table = $this->table('issue_reports');

        if ($table->exists()) {
            if (!$table->hasColumn('specific_location')) {
                $table->addColumn('specific_location', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'after' => $table->hasColumn('cottage_id') ? 'cottage_id' : 'suburb_id'
                ]);
            }
            if (!$table->hasColumn('estimated_budget')) {
                $table->addColumn('estimated_budget', 'decimal', [
                    'precision' => 12,
                    'scale' => 2,
                    'null' => true,
                    'after' => $table->hasColumn('affected_people_count') ? 'affected_people_count' : 'status'
                ]);
            }
            $table->update();
        }
    }
}
