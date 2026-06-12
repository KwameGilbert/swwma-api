<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Consolidated Database Schema Migration
 * 
 * This migration consolidates all existing schema parts into a single file.
 * It is idempotent: creates tables if they don't exist, and updates them if they do.
 */
final class ConsolidatedSchema extends AbstractMigration
{
    public function up(): void
    {
        // 1. USERS
        $users = $this->table('users', ['id' => false, 'primary_key' => ['id']]);
        $users->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('role', 'enum', ['values' => ['web_admin', 'officer', 'agent', 'task_force', 'admin'], 'null' => false])
            ->addColumn('email_verified', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('email_verified_at', 'timestamp', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'suspended', 'pending'], 'default' => 'pending', 'null' => false])
            ->addColumn('first_login', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('last_login_at', 'timestamp', ['null' => true])
            ->addColumn('last_login_ip', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('remember_token', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['phone'])
            ->addIndex(['role'])
            ->addIndex(['status']);
        
        if (!$this->hasTable('users')) {
            $users->create();
        } else {
            $users->save();
        }

        // 2. WEB ADMINS
        $webAdmins = $this->table('web_admins', ['id' => false, 'primary_key' => ['id']]);
        $webAdmins->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('employee_id', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Staff ID'])
            ->addColumn('admin_level', 'enum', ['values' => ['super_admin', 'admin', 'moderator'], 'default' => 'admin', 'null' => false])
            ->addColumn('department', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('permissions', 'json', ['null' => true, 'comment' => 'Specific permissions override'])
            ->addColumn('profile_image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['employee_id'], ['unique' => true])
            ->addIndex(['admin_level'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        
        if (!$this->hasTable('web_admins')) {
            $webAdmins->create();
        } else {
            $webAdmins->save();
        }

        // 3. OFFICERS
        $officers = $this->table('officers', ['id' => false, 'primary_key' => ['id']]);
        $officers->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('employee_id', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Staff ID'])
            ->addColumn('title', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Job title'])
            ->addColumn('department', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('assigned_sectors', 'json', ['null' => true, 'comment' => 'Array of sector IDs they manage'])
            ->addColumn('assigned_locations', 'json', ['null' => true, 'comment' => 'Array of locations they cover'])
            ->addColumn('can_manage_projects', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('can_manage_reports', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('can_manage_events', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('can_publish_content', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('profile_image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('bio', 'text', ['null' => true])
            ->addColumn('office_location', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('office_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['employee_id'], ['unique' => true])
            ->addIndex(['department'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        
        if (!$this->hasTable('officers')) {
            $officers->create();
        } else {
            $officers->save();
        }

        // 4. AGENTS
        $agents = $this->table('agents', ['id' => false, 'primary_key' => ['id']]);
        $agents->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('agent_code', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Unique agent identifier'])
            ->addColumn('supervisor_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Officer supervising this agent'])
            ->addColumn('assigned_communities', 'json', ['null' => true, 'comment' => 'Array of community names/IDs'])
            ->addColumn('assigned_location', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Primary area of operation'])
            ->addColumn('can_submit_reports', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('can_collect_data', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('can_register_residents', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('profile_image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('id_type', 'enum', ['values' => ['ghana_card', 'voter_id', 'passport', 'drivers_license'], 'null' => true])
            ->addColumn('id_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('id_verified', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('id_verified_at', 'timestamp', ['null' => true])
            ->addColumn('address', 'text', ['null' => true])
            ->addColumn('emergency_contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('emergency_contact_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('reports_submitted', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('last_active_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['agent_code'], ['unique' => true])
            ->addIndex(['supervisor_id'])
            ->addIndex(['assigned_location'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('supervisor_id', 'officers', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);
        
        if (!$this->hasTable('agents')) {
            $agents->create();
        } else {
            $agents->save();
        }

        // 5. TASK FORCE MEMBERS
        $taskForce = $this->table('task_force_members', ['id' => false, 'primary_key' => ['id']]);
        $taskForce->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('employee_id', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('title', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('specialization', 'enum', ['values' => ['infrastructure', 'health', 'education', 'water_sanitation', 'electricity', 'roads', 'general'], 'default' => 'general', 'null' => false])
            ->addColumn('assigned_sectors', 'json', ['null' => true])
            ->addColumn('skills', 'json', ['null' => true])
            ->addColumn('can_assess_issues', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('can_resolve_issues', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('can_request_resources', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('profile_image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('id_type', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('id_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('id_verified', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('id_verified_at', 'timestamp', ['null' => true])
            ->addColumn('address', 'text', ['null' => true])
            ->addColumn('emergency_contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('emergency_contact_phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('assessments_completed', 'integer', ['default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('resolutions_completed', 'integer', ['default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('last_active_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['employee_id'], ['unique' => true])
            ->addIndex(['specialization'])
            ->addIndex(['id_verified'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        
        if (!$this->hasTable('task_force_members')) {
            $taskForce->create();
        } else {
            $taskForce->save();
        }

        // 6. PASSWORD RESETS
        $passwordResets = $this->table('password_resets', ['id' => false, 'primary_key' => ['id']]);
        $passwordResets->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('used', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('used_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['email', 'token'], ['name' => 'password_resets_email_token'])
            ->addIndex(['expires_at']);
        
        if (!$this->hasTable('password_resets')) {
            $passwordResets->create();
        } else {
            $passwordResets->save();
        }

        // 7. REFRESH TOKENS
        $refreshTokens = $this->table('refresh_tokens', ['id' => false, 'primary_key' => ['id']]);
        $refreshTokens->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('token_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('device_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('revoked', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('revoked_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addIndex(['expires_at'])
            ->addIndex(['revoked'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('refresh_tokens')) {
            $refreshTokens->create();
        } else {
            $refreshTokens->save();
        }

        // 8. EMAIL VERIFICATION TOKENS
        $emailTokens = $this->table('email_verification_tokens', ['id' => false, 'primary_key' => ['id']]);
        $emailTokens->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('token', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('used', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('used_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'])
            ->addIndex(['token'])
            ->addIndex(['expires_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('email_verification_tokens')) {
            $emailTokens->create();
        } else {
            $emailTokens->save();
        }

        // 9. AUDIT LOGS
        $auditLogs = $this->table('audit_logs', ['id' => false, 'primary_key' => ['id']]);
        $auditLogs->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('action', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('entity_type', 'string', ['limit' => 100, 'null' => true, 'comment' => 'e.g., project, report, event'])
            ->addColumn('entity_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('old_values', 'json', ['null' => true])
            ->addColumn('new_values', 'json', ['null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('metadata', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'])
            ->addIndex(['action'])
            ->addIndex(['entity_type', 'entity_id'])
            ->addIndex(['created_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('audit_logs')) {
            $auditLogs->create();
        } else {
            $auditLogs->save();
        }

        // 10. LOCATIONS
        $locations = $this->table('locations', ['id' => false, 'primary_key' => ['id']]);
        $locations->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('type', 'enum', ['values' => ['community', 'suburb', 'cottage', 'smaller_community'], 'default' => 'community', 'null' => true])
            ->addColumn('parent_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('population', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('area_size', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('latitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('longitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['name'])
            ->addIndex(['type'])
            ->addIndex(['parent_id'])
            ->addIndex(['status'])
            ->addForeignKey('parent_id', 'locations', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('locations')) {
            $locations->create();
        } else {
            $locations->save();
        }

        // 11. CATEGORIES
        $categories = $this->table('categories', ['id' => false, 'primary_key' => ['id']]);
        $categories->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('icon', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('color', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('display_order', 'integer', ['default' => 0, 'signed' => false, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])
            ->addIndex(['display_order']);

        if (!$this->hasTable('categories')) {
            $categories->create();
        } else {
            $categories->save();
        }

        // 12. SECTORS
        $sectors = $this->table('sectors', ['id' => false, 'primary_key' => ['id']]);
        $sectors->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('category_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('icon', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Icon class or image path'])
            ->addColumn('color', 'string', ['limit' => 20, 'null' => true, 'comment' => 'Hex color for UI'])
            ->addColumn('display_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])
            ->addIndex(['category_id'])
            ->addForeignKey('category_id', 'categories', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('sectors')) {
            $sectors->create();
        } else {
            $sectors->save();
        }

        // 13. SUB SECTORS
        $subSectors = $this->table('sub_sectors', ['id' => false, 'primary_key' => ['id']]);
        $subSectors->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('sector_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('code', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('icon', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('display_order', 'integer', ['default' => 0, 'signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['sector_id'])
            ->addIndex(['status'])
            ->addForeignKey('sector_id', 'sectors', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('sub_sectors')) {
            $subSectors->create();
        } else {
            $subSectors->save();
        }

        // 14. NOTIFICATIONS
        $notifications = $this->table('notifications', ['id' => false, 'primary_key' => ['id']]);
        $notifications->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('type', 'enum', ['values' => ['info', 'success', 'warning', 'error', 'issue', 'project', 'announcement', 'assignment', 'system'], 'default' => 'info', 'null' => true])
            ->addColumn('title', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('message', 'text', ['null' => true])
            ->addColumn('action_url', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('action_text', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('data', 'json', ['null' => true])
            ->addColumn('is_read', 'boolean', ['default' => false, 'null' => true])
            ->addColumn('read_at', 'timestamp', ['null' => true])
            ->addColumn('expires_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['user_id'])
            ->addIndex(['type'])
            ->addIndex(['is_read'])
            ->addIndex(['created_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('notifications')) {
            $notifications->create();
        } else {
            $notifications->save();
        }

        // 15. ANNOUNCEMENTS
        $announcements = $this->table('announcements', ['id' => false, 'primary_key' => ['id']]);
        $announcements->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('category', 'enum', ['values' => ['general', 'events', 'infrastructure', 'health', 'education', 'emergency', 'other'], 'default' => 'general', 'null' => false])
            ->addColumn('priority', 'enum', ['values' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium', 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published', 'archived'], 'default' => 'draft', 'null' => false])
            ->addColumn('publish_date', 'date', ['null' => true])
            ->addColumn('expiry_date', 'date', ['null' => true])
            ->addColumn('image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('attachment', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('views', 'integer', ['default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('is_pinned', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('published_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['category'])->addIndex(['priority'])->addIndex(['status'])
            ->addForeignKey('created_by', 'web_admins', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('announcements')) {
            $announcements->create();
        } else {
            $announcements->save();
        }

        // 16. BLOG POSTS
        $blogPosts = $this->table('blog_posts', ['id' => false, 'primary_key' => ['id']]);
        $blogPosts->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('excerpt', 'text', ['null' => true])
            ->addColumn('content', 'text', ['null' => true])
            ->addColumn('image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('author', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('tags', 'json', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published', 'archived'], 'default' => 'draft', 'null' => false])
            ->addColumn('is_featured', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('views', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('published_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])->addIndex(['is_featured'])
            ->addForeignKey('created_by', 'web_admins', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('blog_posts')) {
            $blogPosts->create();
        } else {
            $blogPosts->save();
        }

        // 17. HERO SLIDES
        $heroSlides = $this->table('hero_slides', ['id' => false, 'primary_key' => ['id']]);
        $heroSlides->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('subtitle', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('image', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('cta_label', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Call to action button text'])
            ->addColumn('cta_link', 'string', ['limit' => 500, 'null' => true, 'comment' => 'Call to action URL'])
            ->addColumn('display_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('starts_at', 'timestamp', ['null' => true])
            ->addColumn('ends_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['status'])->addIndex(['display_order']);

        if (!$this->hasTable('hero_slides')) {
            $heroSlides->create();
        } else {
            $heroSlides->save();
        }

        // 18. FAQS
        $faqs = $this->table('faqs', ['id' => false, 'primary_key' => ['id']]);
        $faqs->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('question', 'text', ['null' => false])
            ->addColumn('answer', 'text', ['null' => false])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('display_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['category'])->addIndex(['status']);

        if (!$this->hasTable('faqs')) {
            $faqs->create();
        } else {
            $faqs->save();
        }

        // 19. CONSTITUENCY EVENTS
        $events = $this->table('constituency_events', ['id' => false, 'primary_key' => ['id']]);
        $events->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('event_date', 'date', ['null' => false])
            ->addColumn('start_time', 'time', ['null' => true])
            ->addColumn('end_time', 'time', ['null' => true])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('venue_address', 'text', ['null' => true])
            ->addColumn('map_url', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('organizer', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('contact_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['upcoming', 'ongoing', 'completed', 'cancelled', 'postponed'], 'default' => 'upcoming', 'null' => false])
            ->addColumn('is_featured', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('max_attendees', 'integer', ['null' => true])
            ->addColumn('registration_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['event_date'])->addIndex(['status']);

        if (!$this->hasTable('constituency_events')) {
            $events->create();
        } else {
            $events->save();
        }

        // 20. CONTACT INFO
        $contactInfo = $this->table('contact_info', ['id' => false, 'primary_key' => ['id']]);
        $contactInfo->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('type', 'enum', ['values' => ['address', 'phone', 'email', 'social'], 'null' => false])
            ->addColumn('label', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('value', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('icon', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('link', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('display_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true]);

        if (!$this->hasTable('contact_info')) {
            $contactInfo->create();
        } else {
            $contactInfo->save();
        }

        // 21. COMMUNITY STATS
        $communityStats = $this->table('community_stats', ['id' => false, 'primary_key' => ['id']]);
        $communityStats->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('label', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('value', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('icon', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('display_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true]);

        if (!$this->hasTable('community_stats')) {
            $communityStats->create();
        } else {
            $communityStats->save();
        }

        // 22. NEWSLETTER SUBSCRIBERS
        $subscribers = $this->table('newsletter_subscribers', ['id' => false, 'primary_key' => ['id']]);
        $subscribers->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'unsubscribed'], 'default' => 'active', 'null' => false])
            ->addColumn('subscribed_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('unsubscribed_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['email'], ['unique' => true]);

        if (!$this->hasTable('newsletter_subscribers')) {
            $subscribers->create();
        } else {
            $subscribers->save();
        }

        // 23. GALLERIES
        $galleries = $this->table('galleries', ['id' => false, 'primary_key' => ['id']]);
        $galleries->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('category', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('cover_image', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('images', 'json', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active', 'null' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['null' => true])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addIndex(['slug'], ['unique' => true]);

        if (!$this->hasTable('galleries')) {
            $galleries->create();
        } else {
            $galleries->save();
        }

        // 24. COMMUNITY IDEAS
        $ideas = $this->table('community_ideas', ['id' => false, 'primary_key' => ['id']]);
        $ideas->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('category', 'enum', ['values' => ['infrastructure', 'education', 'healthcare', 'environment', 'social', 'economic', 'governance', 'other'], 'default' => 'other', 'null' => false])
            ->addColumn('submitter_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('submitter_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('submitter_contact', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('submitter_user_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'If submitted by a registered user'])
            ->addColumn('status', 'enum', ['values' => ['pending', 'under_review', 'approved', 'rejected', 'implemented'], 'default' => 'pending', 'null' => false])
            ->addColumn('priority', 'enum', ['values' => ['low', 'medium', 'high'], 'default' => 'medium', 'null' => false])
            ->addColumn('votes', 'integer', ['default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('downvotes', 'integer', ['default' => 0, 'null' => true])
            ->addColumn('estimated_cost', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('estimated_cost_min', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('estimated_cost_max', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('target_beneficiaries', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('implementation_timeline', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('images', 'json', ['null' => true])
            ->addColumn('documents', 'json', ['null' => true])
            ->addColumn('admin_notes', 'text', ['null' => true])
            ->addColumn('reviewed_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('reviewed_at', 'timestamp', ['null' => true])
            ->addColumn('implemented_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])->addIndex(['category'])
            ->addForeignKey('submitter_user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('community_ideas')) {
            $ideas->create();
        } else {
            $ideas->save();
        }

        // 25. COMMUNITY IDEA VOTES
        $ideaVotes = $this->table('community_idea_votes', ['id' => false, 'primary_key' => ['id']]);
        $ideaVotes->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('idea_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('type', 'enum', ['values' => ['up', 'down'], 'default' => 'up', 'null' => true])
            ->addColumn('voter_ip', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('voter_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['idea_id', 'user_id'], ['unique' => true, 'name' => 'unique_user_vote'])
            ->addForeignKey('idea_id', 'community_ideas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('community_idea_votes')) {
            $ideaVotes->create();
        } else {
            $ideaVotes->save();
        }

        // 26. PROJECTS
        $projects = $this->table('projects', ['id' => false, 'primary_key' => ['id']]);
        $projects->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('managing_officer_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Officer overseeing the project'])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('sector_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['planning', 'ongoing', 'completed', 'on_hold', 'cancelled'], 'default' => 'planning', 'null' => false])
            ->addColumn('start_date', 'date', ['null' => true])
            ->addColumn('end_date', 'date', ['null' => true])
            ->addColumn('budget', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('spent', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0, 'null' => true])
            ->addColumn('progress_percent', 'integer', ['default' => 0, 'null' => false, 'comment' => '0-100'])
            ->addColumn('beneficiaries', 'integer', ['null' => true])
            ->addColumn('image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('gallery', 'json', ['null' => true])
            ->addColumn('contractor', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_person', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('is_featured', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('views', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addForeignKey('sector_id', 'sectors', 'id', ['delete' => 'NO_ACTION', 'update' => 'CASCADE'])
            ->addForeignKey('managing_officer_id', 'officers', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('projects')) {
            $projects->create();
        } else {
            $projects->save();
        }

        // 27. EMPLOYMENT JOBS
        $jobs = $this->table('employment_jobs', ['id' => false, 'primary_key' => ['id']]);
        $jobs->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('company', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('job_type', 'enum', ['values' => ['full_time', 'part_time', 'contract', 'internship', 'temporary', 'volunteer'], 'default' => 'full_time', 'null' => false])
            ->addColumn('salary_range', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('salary_min', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => true])
            ->addColumn('salary_max', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => true])
            ->addColumn('requirements', 'text', ['null' => true])
            ->addColumn('responsibilities', 'text', ['null' => true])
            ->addColumn('benefits', 'text', ['null' => true])
            ->addColumn('application_deadline', 'date', ['null' => true])
            ->addColumn('application_url', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('application_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published', 'closed', 'archived'], 'default' => 'draft', 'null' => false])
            ->addColumn('category', 'string', ['limit' => 255, 'default' => 'Other', 'null' => false])
            ->addColumn('sector', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('experience_level', 'enum', ['values' => ['entry', 'mid', 'senior', 'executive'], 'default' => 'entry', 'null' => false])
            ->addColumn('applicants_count', 'integer', ['default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('views', 'integer', ['default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('is_featured', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('published_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true]);

        if (!$this->hasTable('employment_jobs')) {
            $jobs->create();
        } else {
            $jobs->save();
        }

        // 28. JOB APPLICANTS
        $applicants = $this->table('job_applicants', ['id' => false, 'primary_key' => ['id']]);
        $applicants->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('job_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('resume_url', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('cover_letter', 'text', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['pending', 'reviewed', 'shortlisted', 'rejected', 'accepted'], 'default' => 'pending', 'null' => false])
            ->addColumn('applied_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['job_id'])
            ->addForeignKey('job_id', 'employment_jobs', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('job_applicants')) {
            $applicants->create();
        } else {
            $applicants->save();
        }

        // 29. ISSUE REPORTS
        $reports = $this->table('issue_reports', ['id' => false, 'primary_key' => ['id']]);
        $reports->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('report_code', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('submitted_by_agent_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('submitted_by_officer_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('assigned_officer_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('assigned_agent_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('assigned_task_force_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('sector_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('sub_sector_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('location_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('landmark', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('gps_coords', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('latitude', 'decimal', ['precision' => 10, 'scale' => 8, 'null' => true])
            ->addColumn('longitude', 'decimal', ['precision' => 11, 'scale' => 8, 'null' => true])
            ->addColumn('contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('severity', 'enum', ['values' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium', 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['pending', 'investigating', 'assessed', 'resolving', 'resolved', 'closed', 'rejected'], 'default' => 'pending', 'null' => false])
            ->addColumn('images', 'json', ['null' => true])
            ->addColumn('videos', 'json', ['null' => true])
            ->addColumn('is_public', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('anonymous', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('acknowledged_at', 'timestamp', ['null' => true])
            ->addColumn('acknowledged_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('forwarded_to_admin_at', 'timestamp', ['null' => true])
            ->addColumn('assigned_to_task_force_at', 'timestamp', ['null' => true])
            ->addColumn('resources_allocated_at', 'timestamp', ['null' => true])
            ->addColumn('resources_allocated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('resolved_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('reported_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('resolved_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['report_code'], ['unique' => true])
            ->addIndex(['status'])->addIndex(['severity'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('submitted_by_agent_id', 'agents', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('submitted_by_officer_id', 'officers', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('assigned_officer_id', 'officers', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('assigned_agent_id', 'agents', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('assigned_task_force_id', 'task_force_members', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('sector_id', 'sectors', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('sub_sector_id', 'sub_sectors', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('acknowledged_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('resources_allocated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('resolved_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('issue_reports')) {
            $reports->create();
        } else {
            $reports->save();
        }

        // 30. ISSUE REPORT COMMENTS
        $comments = $this->table('issue_report_comments', ['id' => false, 'primary_key' => ['id']]);
        $comments->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('issue_report_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('comment', 'text', ['null' => false])
            ->addColumn('is_internal', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('attachments', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addForeignKey('issue_report_id', 'issue_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('issue_report_comments')) {
            $comments->create();
        } else {
            $comments->save();
        }

        // 31. ISSUE REPORT STATUS HISTORY
        $history = $this->table('issue_report_status_history', ['id' => false, 'primary_key' => ['id']]);
        $history->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('issue_report_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('previous_status', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('new_status', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('comment', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addForeignKey('issue_report_id', 'issue_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('issue_report_status_history')) {
            $history->create();
        } else {
            $history->save();
        }

        // 32. ISSUE ASSESSMENT REPORTS
        $assessments = $this->table('issue_assessment_reports', ['id' => false, 'primary_key' => ['id']]);
        $assessments->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('issue_report_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('assessed_by', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Task Force Member ID'])
            ->addColumn('actual_severity', 'enum', ['values' => ['low', 'medium', 'high', 'critical'], 'null' => false])
            ->addColumn('root_cause', 'text', ['null' => true])
            ->addColumn('assessment_details', 'text', ['null' => false])
            ->addColumn('recommended_action', 'text', ['null' => true])
            ->addColumn('estimated_cost', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('estimated_time_hours', 'integer', ['null' => true])
            ->addColumn('required_resources', 'json', ['null' => true])
            ->addColumn('images', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addForeignKey('issue_report_id', 'issue_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('assessed_by', 'task_force_members', 'id', ['delete' => 'NO_ACTION', 'update' => 'CASCADE']);

        if (!$this->hasTable('issue_assessment_reports')) {
            $assessments->create();
        } else {
            $assessments->save();
        }

        // 33. ISSUE RESOLUTION REPORTS
        $resolutions = $this->table('issue_resolution_reports', ['id' => false, 'primary_key' => ['id']]);
        $resolutions->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('issue_report_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('resolved_by', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Task Force Member ID'])
            ->addColumn('resolution_details', 'text', ['null' => false])
            ->addColumn('actual_cost', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('time_spent_hours', 'integer', ['null' => true])
            ->addColumn('resources_used', 'json', ['null' => true])
            ->addColumn('images', 'json', ['null' => true])
            ->addColumn('completion_date', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addForeignKey('issue_report_id', 'issue_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('resolved_by', 'task_force_members', 'id', ['delete' => 'NO_ACTION', 'update' => 'CASCADE']);

        if (!$this->hasTable('issue_resolution_reports')) {
            $resolutions->create();
        } else {
            $resolutions->save();
        }

        // 34. YOUTH PROGRAMS
        $youthPrograms = $this->table('youth_programs', ['id' => false, 'primary_key' => ['id']]);
        $youthPrograms->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => false])
            ->addColumn('category', 'enum', ['values' => ['skills_training', 'education', 'entrepreneurship', 'sports', 'arts_culture', 'leadership', 'other'], 'default' => 'other', 'null' => false])
            ->addColumn('target_age_min', 'integer', ['null' => true])
            ->addColumn('target_age_max', 'integer', ['null' => true])
            ->addColumn('location_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('venue', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('start_date', 'date', ['null' => true])
            ->addColumn('end_date', 'date', ['null' => true])
            ->addColumn('application_deadline', 'date', ['null' => true])
            ->addColumn('max_participants', 'integer', ['null' => true])
            ->addColumn('current_participants', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published', 'ongoing', 'completed', 'cancelled'], 'default' => 'draft', 'null' => false])
            ->addColumn('image', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('is_featured', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addForeignKey('location_id', 'locations', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('youth_programs')) {
            $youthPrograms->create();
        } else {
            $youthPrograms->save();
        }

        // 35. YOUTH PROGRAM PARTICIPANTS
        $participants = $this->table('youth_program_participants', ['id' => false, 'primary_key' => ['id']]);
        $participants->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('program_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('status', 'enum', ['values' => ['applied', 'approved', 'rejected', 'withdrawn', 'completed'], 'default' => 'applied', 'null' => false])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['program_id', 'user_id'], ['unique' => true])
            ->addForeignKey('program_id', 'youth_programs', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        if (!$this->hasTable('youth_program_participants')) {
            $participants->create();
        } else {
            $participants->save();
        }

        // 36. YOUTH RECORDS
        $youthRecords = $this->table('youth_records', ['id' => false, 'primary_key' => ['id']]);
        $youthRecords->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('full_name', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('date_of_birth', 'date', ['null' => true])
            ->addColumn('gender', 'enum', ['values' => ['male', 'female', 'other'], 'null' => true])
            ->addColumn('national_id', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('hometown', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('community', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('location_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('education_level', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('jhs_completed', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('shs_qualification', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('certificate_qualification', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('diploma_qualification', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('degree_qualification', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('postgraduate_qualification', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('professional_qualification', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('employment_status', 'enum', ['values' => ['employed', 'unemployed', 'student', 'self_employed'], 'default' => 'unemployed', 'null' => true])
            ->addColumn('availability_status', 'enum', ['values' => ['available', 'unavailable'], 'default' => 'available', 'null' => true])
            ->addColumn('current_employment', 'string', ['limit' => 300, 'null' => true])
            ->addColumn('preferred_location', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('salary_expectation', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('employment_notes', 'text', ['null' => true])
            ->addColumn('work_experiences', 'json', ['null' => true])
            ->addColumn('skills', 'text', ['null' => true])
            ->addColumn('interests', 'text', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['pending', 'approved', 'rejected'], 'default' => 'pending', 'null' => true])
            ->addColumn('admin_notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['status'])
            ->addIndex(['employment_status'])
            ->addIndex(['location_id'])
            ->addIndex(['created_at'])
            ->addIndex(['full_name', 'phone'])
            ->addIndex(['created_by'])
            ->addForeignKey('location_id', 'locations', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE']);

        if (!$this->hasTable('youth_records')) {
            $youthRecords->create();
        } else {
            $youthRecords->save();
        }
    }

    public function down(): void
    {
        // Drop tables in reverse order of dependencies
        $tables = [
            'youth_records',
            'youth_program_participants',
            'youth_programs',
            'issue_resolution_reports',
            'issue_assessment_reports',
            'issue_report_status_history',
            'issue_report_comments',
            'issue_reports',
            'job_applicants',
            'employment_jobs',
            'projects',
            'community_idea_votes',
            'community_ideas',
            'galleries',
            'newsletter_subscribers',
            'community_stats',
            'contact_info',
            'constituency_events',
            'faqs',
            'hero_slides',
            'blog_posts',
            'announcements',
            'notifications',
            'sub_sectors',
            'sectors',
            'categories',
            'locations',
            'audit_logs',
            'email_verification_tokens',
            'refresh_tokens',
            'password_resets',
            'task_force_members',
            'agents',
            'officers',
            'web_admins',
            'users'
        ];

        foreach ($tables as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }
    }
}
