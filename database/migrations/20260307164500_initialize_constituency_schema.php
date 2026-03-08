<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class InitializeConstituencySchema extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // ============================================================
        // DOMAIN 1: CLASSIFICATION & LOOKUPS
        // ============================================================

        // Table: categories
        $tableCategories = $this->table('categories', ['id' => true]);
        $tableCategories->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // Table: sectors
        $tableSectors = $this->table('sectors', ['id' => true]);
        $tableSectors->addColumn('category_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['category_id'])
            ->addForeignKey('category_id', 'categories', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_sectors_category'])
            ->create();

        // Table: sub_sectors
        $tableSubSectors = $this->table('sub_sectors', ['id' => true]);
        $tableSubSectors->addColumn('sector_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['sector_id'])
            ->addForeignKey('sector_id', 'sectors', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_sub_sectors_sector'])
            ->create();

        // Table: locations
        $tableLocations = $this->table('locations', ['id' => true]);
        $tableLocations->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('type', 'enum', ['values' => ['community', 'suburb'], 'default' => 'community'])
            ->addColumn('parent_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['parent_id'])
            ->addForeignKey('parent_id', 'locations', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION', 'constraint' => 'fk_locations_parent'])
            ->create();

        // ============================================================
        // DOMAIN 2: USERS & ROLE PROFILES
        // ============================================================

        // Table: users
        $tableUsers = $this->table('users', ['id' => true]);
        $tableUsers->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('role', 'enum', ['values' => ['super_admin', 'admin', 'web_admin', 'officer', 'agent', 'task_force'], 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['active', 'suspended', 'pending'], 'default' => 'pending'])
            ->addColumn('remember_token', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('email_verified', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();

        // Profile Tables
        $profileTables = [
            'web_admin_profiles' => 'fk_web_admin_user',
            'officer_profiles' => 'fk_officer_user',
            'agent_profiles' => 'fk_agent_user',
            'task_force_profiles' => 'fk_task_force_user',
            'admin_profiles' => 'fk_admin_user'
        ];

        foreach ($profileTables as $tableName => $fkName) {
            $table = $this->table($tableName, ['id' => true]);
            $table->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('first_name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('last_name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('gender', 'enum', ['values' => ['male', 'female', 'other'], 'null' => true])
                ->addColumn('profile_image', 'string', ['limit' => 500, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
                ->addIndex(['user_id'], ['unique' => true])
                ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => $fkName])
                ->create();
        }

        // ============================================================
        // ISSUE TRACKING & RESOLUTION
        // ============================================================

        // Table: constituents
        $tableConstituents = $this->table('constituents', ['id' => true]);
        $tableConstituents->addColumn('name', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('phone_number', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('gender', 'enum', ['values' => ['male', 'female', 'other'], 'null' => true])
            ->addColumn('home_address', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->create();

        // Table: issues
        $tableIssues = $this->table('issues', ['id' => true]);
        $tableIssues->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('category_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('sector_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('sub_sector_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('community_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('suburb_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('specific_location', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('details', 'text', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['submitted', 'assessment_in_progress', 'assessment_submitted', 'resolution_in_progress', 'resolved', 'needs_revision'], 'default' => 'submitted'])
            ->addColumn('constituent_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['category_id'])
            ->addIndex(['sector_id'])
            ->addIndex(['sub_sector_id'])
            ->addIndex(['community_id'])
            ->addIndex(['constituent_id'])
            ->addForeignKey('category_id', 'categories', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION', 'constraint' => 'fk_issue_cat'])
            ->addForeignKey('sector_id', 'sectors', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION', 'constraint' => 'fk_issue_sec'])
            ->addForeignKey('sub_sector_id', 'sub_sectors', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION', 'constraint' => 'fk_issue_sub'])
            ->addForeignKey('community_id', 'locations', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION', 'constraint' => 'fk_issue_loc'])
            ->addForeignKey('constituent_id', 'constituents', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION', 'constraint' => 'fk_issue_const'])
            ->create();

        // Table: issue_assessments
        $tableAssessments = $this->table('issue_assessments', ['id' => true]);
        $tableAssessments->addColumn('issues_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('recommendations', 'text', ['null' => true])
            ->addColumn('estimated_costs', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('estimated_duration', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('issue_confirmed', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('attachments', 'json', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['pending_approval', 'approved', 'needs_revision'], 'default' => 'pending_approval'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['issues_id'])
            ->addForeignKey('issues_id', 'issues', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_assessment_report'])
            ->create();

        // Table: issue_resolutions
        $tableResolutions = $this->table('issue_resolutions', ['id' => true]);
        $tableResolutions->addColumn('issues_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('summary', 'text', ['null' => false])
            ->addColumn('status', 'enum', ['values' => ['draft', 'submitted', 'completed'], 'default' => 'draft'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['issues_id'])
            ->addForeignKey('issues_id', 'issues', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_resolution_report'])
            ->create();

        // ============================================================
        // DOMAIN 4: IDEAS & CONTENT
        // ============================================================

        // Table: community_ideas
        $tableIdeas = $this->table('community_ideas', ['id' => true]);
        $tableIdeas->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->create();

        // Table: blog_posts
        $tableBlog = $this->table('blog_posts', ['id' => true]);
        $tableBlog->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('content', 'text', ['null' => true])
            ->addColumn('tags', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published', 'archived'], 'default' => 'draft'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->create();

        // ============================================================
        // DOMAIN 5: PROJECTS & EVENTS
        // ============================================================

        // Table: projects
        $tableProjects = $this->table('projects', ['id' => true]);
        $tableProjects->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['planning', 'ongoing', 'completed', 'on_hold', 'cancelled'], 'default' => 'planning'])
            ->addColumn('budget', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('progress_percent', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('images', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->create();

        // Table: constituency_events
        $tableEvents = $this->table('constituency_events', ['id' => true]);
        $tableEvents->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('event_date', 'date', ['null' => false])
            ->addColumn('status', 'enum', ['values' => ['upcoming', 'ongoing', 'completed', 'cancelled', 'postponed'], 'default' => 'upcoming'])
            ->addColumn('images', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->create();

        // ============================================================
        // DOMAIN 6: EMPLOYMENT & JOBS
        // ============================================================

        // Table: employment_jobs
        $tableJobs = $this->table('employment_jobs', ['id' => true]);
        $tableJobs->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('job_information', 'text', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['open', 'closed', 'archived'], 'default' => 'open'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->create();

        // Table: job_applicants
        $tableApplicants = $this->table('job_applicants', ['id' => true]);
        $tableApplicants->addColumn('job_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('first_name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('last_name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('gender', 'enum', ['values' => ['male', 'female', 'other'], 'null' => true])
            ->addColumn('cv_path', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['pending', 'shortlisted', 'interviewed', 'hired', 'rejected'], 'default' => 'pending'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['job_id'])
            ->addForeignKey('job_id', 'employment_jobs', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_applicant_job'])
            ->create();
    }
}
