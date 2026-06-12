<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * DatabaseSeeder - Comprehensive database seeder for Constituency Development Hub
 * 
 * Seeds all tables with realistic sample data for development and testing.
 */
class DatabaseSeeder extends AbstractSeed
{
    public function run(): void
    {
        echo "🌱 Starting database seeding...\n\n";

        $this->seedUsers();
        $this->seedWebAdmins();
        $this->seedOfficers();
        $this->seedAgents();
        $this->seedTaskForceMembers();
        $this->seedSectors();
        $this->seedSubSectors();
        $this->seedProjects();
        $this->seedBlogPosts();
        $this->seedEvents();
        $this->seedFaqs();
        $this->seedHeroSlides();
        $this->seedCommunityStats();
        $this->seedContactInfo();
        $this->seedIssueReports();
        $this->seedNewsletterSubscribers();

        echo "\n✅ Database seeding completed successfully!\n";
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private function seedUsers(): void
    {
        echo "👤 Seeding users...\n";

        $users = [
            // Web Admins
            ['name' => 'Super Admin', 'email' => 'superadmin@constituency.gov.gh', 'phone' => '+233201234567', 'password' => $this->hashPassword('SuperAdmin@123'), 'role' => 'web_admin', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'John Mensah', 'email' => 'john.mensah@constituency.gov.gh', 'phone' => '+233202345678', 'password' => $this->hashPassword('Admin@123'), 'role' => 'web_admin', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Abena Osei', 'email' => 'abena.osei@constituency.gov.gh', 'phone' => '+233203456789', 'password' => $this->hashPassword('Admin@123'), 'role' => 'web_admin', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            
            // Officers
            ['name' => 'Kwame Asante', 'email' => 'kwame.asante@constituency.gov.gh', 'phone' => '+233204567890', 'password' => $this->hashPassword('Officer@123'), 'role' => 'officer', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Efua Boateng', 'email' => 'efua.boateng@constituency.gov.gh', 'phone' => '+233205678901', 'password' => $this->hashPassword('Officer@123'), 'role' => 'officer', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Kofi Adjei', 'email' => 'kofi.adjei@constituency.gov.gh', 'phone' => '+233206789012', 'password' => $this->hashPassword('Officer@123'), 'role' => 'officer', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Akosua Darko', 'email' => 'akosua.darko@constituency.gov.gh', 'phone' => '+233207890123', 'password' => $this->hashPassword('Officer@123'), 'role' => 'officer', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            
            // Agents
            ['name' => 'Yaw Frimpong', 'email' => 'yaw.frimpong@constituency.gov.gh', 'phone' => '+233208901234', 'password' => $this->hashPassword('Agent@123'), 'role' => 'agent', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Ama Serwaa', 'email' => 'ama.serwaa@constituency.gov.gh', 'phone' => '+233209012345', 'password' => $this->hashPassword('Agent@123'), 'role' => 'agent', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Kwabena Owusu', 'email' => 'kwabena.owusu@constituency.gov.gh', 'phone' => '+233200123456', 'password' => $this->hashPassword('Agent@123'), 'role' => 'agent', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Adwoa Mensah', 'email' => 'adwoa.mensah@constituency.gov.gh', 'phone' => '+233201234599', 'password' => $this->hashPassword('Agent@123'), 'role' => 'agent', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            
            // Task Force Members
            ['name' => 'Emmanuel Tetteh', 'email' => 'emmanuel.tetteh@constituency.gov.gh', 'phone' => '+233202345699', 'password' => $this->hashPassword('TaskForce@123'), 'role' => 'officer', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
            ['name' => 'Grace Amoako', 'email' => 'grace.amoako@constituency.gov.gh', 'phone' => '+233203456799', 'password' => $this->hashPassword('TaskForce@123'), 'role' => 'officer', 'email_verified' => true, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'first_login' => false],
        ];

        foreach ($users as &$user) {
            $user['created_at'] = date('Y-m-d H:i:s');
            $user['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('users')->insert($users)->saveData();
        echo "   ✓ Created " . count($users) . " users\n";
    }

    private function seedWebAdmins(): void
    {
        echo "🔐 Seeding web admins...\n";

        $admins = [
            ['user_id' => 1, 'employee_id' => 'ADM-001', 'admin_level' => 'super_admin', 'department' => 'Administration', 'permissions' => json_encode(['all']), 'notes' => 'Primary super administrator'],
            ['user_id' => 2, 'employee_id' => 'ADM-002', 'admin_level' => 'admin', 'department' => 'Content Management', 'permissions' => json_encode(['content', 'events', 'blog']), 'notes' => 'Content administrator'],
            ['user_id' => 3, 'employee_id' => 'ADM-003', 'admin_level' => 'moderator', 'department' => 'Communications', 'permissions' => json_encode(['blog', 'events']), 'notes' => 'Communications moderator'],
        ];

        foreach ($admins as &$admin) {
            $admin['created_at'] = date('Y-m-d H:i:s');
            $admin['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('web_admins')->insert($admins)->saveData();
        echo "   ✓ Created " . count($admins) . " web admins\n";
    }

    private function seedOfficers(): void
    {
        echo "👔 Seeding officers...\n";

        $officers = [
            ['user_id' => 4, 'employee_id' => 'OFF-001', 'title' => 'Senior Development Officer', 'department' => 'Infrastructure', 'assigned_sectors' => json_encode([1, 3]), 'assigned_locations' => json_encode(['Adum', 'Asafo']), 'can_manage_projects' => true, 'can_manage_reports' => true, 'can_manage_events' => true, 'can_publish_content' => false, 'bio' => 'Experienced development officer with 10 years in infrastructure projects', 'office_location' => 'Block A, Office 12', 'office_phone' => '+233302123456'],
            ['user_id' => 5, 'employee_id' => 'OFF-002', 'title' => 'Health Programs Officer', 'department' => 'Health', 'assigned_sectors' => json_encode([2]), 'assigned_locations' => json_encode(['Bantama', 'Subin']), 'can_manage_projects' => true, 'can_manage_reports' => true, 'can_manage_events' => false, 'can_publish_content' => false, 'bio' => 'Public health specialist focused on community wellness', 'office_location' => 'Block B, Office 5', 'office_phone' => '+233302234567'],
            ['user_id' => 6, 'employee_id' => 'OFF-003', 'title' => 'Education Officer', 'department' => 'Education', 'assigned_sectors' => json_encode([4]), 'assigned_locations' => json_encode(['Nhyiaeso', 'Oforikrom']), 'can_manage_projects' => true, 'can_manage_reports' => true, 'can_manage_events' => true, 'can_publish_content' => true, 'bio' => 'Education policy expert and school development coordinator', 'office_location' => 'Block A, Office 8', 'office_phone' => '+233302345678'],
            ['user_id' => 7, 'employee_id' => 'OFF-004', 'title' => 'Community Liaison Officer', 'department' => 'Community Relations', 'assigned_sectors' => json_encode([5, 6]), 'assigned_locations' => json_encode(['Tafo', 'Suame']), 'can_manage_projects' => false, 'can_manage_reports' => true, 'can_manage_events' => true, 'can_publish_content' => false, 'bio' => 'Community engagement and social development specialist', 'office_location' => 'Block C, Office 3', 'office_phone' => '+233302456789'],
        ];

        foreach ($officers as &$officer) {
            $officer['created_at'] = date('Y-m-d H:i:s');
            $officer['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('officers')->insert($officers)->saveData();
        echo "   ✓ Created " . count($officers) . " officers\n";
    }

    private function seedAgents(): void
    {
        echo "🚶 Seeding agents...\n";

        $agents = [
            ['user_id' => 8, 'agent_code' => 'AGT-001', 'supervisor_id' => 1, 'assigned_communities' => json_encode(['Adum Central', 'Adum North']), 'assigned_location' => 'Adum', 'can_submit_reports' => true, 'can_collect_data' => true, 'can_register_residents' => true, 'id_type' => 'ghana_card', 'id_number' => 'GHA-123456789-0', 'id_verified' => true, 'id_verified_at' => date('Y-m-d H:i:s'), 'address' => '123 Main St, Adum', 'emergency_contact_name' => 'Kofi Frimpong', 'emergency_contact_phone' => '+233201111111', 'reports_submitted' => 25],
            ['user_id' => 9, 'agent_code' => 'AGT-002', 'supervisor_id' => 1, 'assigned_communities' => json_encode(['Bantama East', 'Bantama West']), 'assigned_location' => 'Bantama', 'can_submit_reports' => true, 'can_collect_data' => true, 'can_register_residents' => false, 'id_type' => 'voter_id', 'id_number' => 'VID-987654321', 'id_verified' => true, 'id_verified_at' => date('Y-m-d H:i:s'), 'address' => '45 Station Road, Bantama', 'emergency_contact_name' => 'Ama Kyere', 'emergency_contact_phone' => '+233202222222', 'reports_submitted' => 18],
            ['user_id' => 10, 'agent_code' => 'AGT-003', 'supervisor_id' => 2, 'assigned_communities' => json_encode(['Asafo Market', 'Asafo Residential']), 'assigned_location' => 'Asafo', 'can_submit_reports' => true, 'can_collect_data' => true, 'can_register_residents' => true, 'id_type' => 'ghana_card', 'id_number' => 'GHA-567890123-4', 'id_verified' => true, 'id_verified_at' => date('Y-m-d H:i:s'), 'address' => '78 Commerce Street, Asafo', 'emergency_contact_name' => 'Yaw Mensah', 'emergency_contact_phone' => '+233203333333', 'reports_submitted' => 32],
            ['user_id' => 11, 'agent_code' => 'AGT-004', 'supervisor_id' => 3, 'assigned_communities' => json_encode(['Subin Central']), 'assigned_location' => 'Subin', 'can_submit_reports' => true, 'can_collect_data' => true, 'can_register_residents' => false, 'id_type' => 'ghana_card', 'id_number' => 'GHA-234567890-1', 'id_verified' => false, 'address' => '12 Unity Lane, Subin', 'emergency_contact_name' => 'Akua Boateng', 'emergency_contact_phone' => '+233204444444', 'reports_submitted' => 12],
        ];

        foreach ($agents as &$agent) {
            $agent['created_at'] = date('Y-m-d H:i:s');
            $agent['updated_at'] = date('Y-m-d H:i:s');
            $agent['last_active_at'] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 48) . ' hours'));
        }

        $this->table('agents')->insert($agents)->saveData();
        echo "   ✓ Created " . count($agents) . " agents\n";
    }

    private function seedTaskForceMembers(): void
    {
        echo "⚡ Seeding task force members...\n";

        $members = [
            ['user_id' => 12, 'employee_id' => 'TFM-001', 'title' => 'Infrastructure Specialist', 'specialization' => 'infrastructure', 'assigned_sectors' => json_encode([1, 3]), 'skills' => json_encode(['road construction', 'drainage systems', 'building inspection']), 'can_assess_issues' => true, 'can_resolve_issues' => true, 'can_request_resources' => true, 'id_type' => 'ghana_card', 'id_number' => 'GHA-111222333-4', 'id_verified' => true, 'id_verified_at' => date('Y-m-d H:i:s'), 'address' => '56 Engineers Ave, Kumasi', 'emergency_contact_name' => 'Mary Tetteh', 'emergency_contact_phone' => '+233205555555', 'assessments_completed' => 15, 'resolutions_completed' => 12],
            ['user_id' => 13, 'employee_id' => 'TFM-002', 'title' => 'Water & Sanitation Expert', 'specialization' => 'water_sanitation', 'assigned_sectors' => json_encode([5]), 'skills' => json_encode(['water supply', 'sanitation systems', 'environmental health']), 'can_assess_issues' => true, 'can_resolve_issues' => true, 'can_request_resources' => false, 'id_type' => 'ghana_card', 'id_number' => 'GHA-444555666-7', 'id_verified' => true, 'id_verified_at' => date('Y-m-d H:i:s'), 'address' => '89 Water Works Rd, Kumasi', 'emergency_contact_name' => 'Peter Amoako', 'emergency_contact_phone' => '+233206666666', 'assessments_completed' => 22, 'resolutions_completed' => 18],
        ];

        foreach ($members as &$member) {
            $member['created_at'] = date('Y-m-d H:i:s');
            $member['updated_at'] = date('Y-m-d H:i:s');
            $member['last_active_at'] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 24) . ' hours'));
        }

        $this->table('task_force_members')->insert($members)->saveData();
        echo "   ✓ Created " . count($members) . " task force members\n";
    }

    private function seedSectors(): void
    {
        echo "📊 Seeding sectors...\n";

        $sectors = [
            ['name' => 'Infrastructure', 'slug' => 'infrastructure', 'description' => 'Roads, bridges, drainage systems, and public buildings development', 'icon' => 'building', 'color' => '#3B82F6', 'display_order' => 1, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Healthcare', 'slug' => 'healthcare', 'description' => 'Hospitals, clinics, medical equipment, and health programs', 'icon' => 'heart-pulse', 'color' => '#EF4444', 'display_order' => 2, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Roads & Transport', 'slug' => 'roads-transport', 'description' => 'Road construction, rehabilitation, and transport infrastructure', 'icon' => 'road', 'color' => '#F59E0B', 'display_order' => 3, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Schools, educational facilities, and learning programs', 'icon' => 'graduation-cap', 'color' => '#10B981', 'display_order' => 4, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Water & Sanitation', 'slug' => 'water-sanitation', 'description' => 'Clean water supply, sanitation facilities, and waste management', 'icon' => 'droplet', 'color' => '#06B6D4', 'display_order' => 5, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Agriculture', 'slug' => 'agriculture', 'description' => 'Farming support, irrigation systems, and agricultural development', 'icon' => 'wheat', 'color' => '#84CC16', 'display_order' => 6, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Youth & Sports', 'slug' => 'youth-sports', 'description' => 'Youth development programs, sports facilities, and recreational activities', 'icon' => 'users', 'color' => '#8B5CF6', 'display_order' => 7, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Electricity', 'slug' => 'electricity', 'description' => 'Power supply, electrical infrastructure, and rural electrification', 'icon' => 'zap', 'color' => '#FBBF24', 'display_order' => 8, 'status' => 'active', 'created_by' => 1],
        ];

        foreach ($sectors as &$sector) {
            $sector['created_at'] = date('Y-m-d H:i:s');
            $sector['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('sectors')->insert($sectors)->saveData();
        echo "   ✓ Created " . count($sectors) . " sectors\n";
    }

    private function seedSubSectors(): void
    {
        echo "🌱 Seeding sub-sectors...\n";

        $subSectors = [
            // Infrastructure (Sector 1)
            ['id' => 1, 'sector_id' => 1, 'name' => 'Public Buildings', 'code' => 'INF-PUB', 'description' => 'Construction and maintenance of public buildings', 'display_order' => 1, 'status' => 'active'],
            ['id' => 2, 'sector_id' => 1, 'name' => 'Drainage Systems', 'code' => 'INF-DRN', 'description' => 'Storm drains and gutters', 'display_order' => 2, 'status' => 'active'],
            ['id' => 3, 'sector_id' => 1, 'name' => 'Markets', 'code' => 'INF-MKT', 'description' => 'Market stalls and structures', 'display_order' => 3, 'status' => 'active'],

            // Healthcare (Sector 2)
            ['id' => 4, 'sector_id' => 2, 'name' => 'Clinics & Hospitals', 'code' => 'HEA-CLN', 'description' => 'Health posts, clinics, and CHPS compounds', 'display_order' => 1, 'status' => 'active'],
            ['id' => 5, 'sector_id' => 2, 'name' => 'Medical Equipment', 'code' => 'HEA-EQP', 'description' => 'Supply of medical tools and instruments', 'display_order' => 2, 'status' => 'active'],

            // Roads & Transport (Sector 3)
            ['id' => 6, 'sector_id' => 3, 'name' => 'Road Rehabilitation', 'code' => 'RDS-REH', 'description' => 'Re-tarmacking and grading of roads', 'display_order' => 1, 'status' => 'active'],
            ['id' => 7, 'sector_id' => 3, 'name' => 'Pothole Repair', 'code' => 'RDS-PTH', 'description' => 'Fixing potholes and road surface damage', 'display_order' => 2, 'status' => 'active'],

            // Education (Sector 4)
            ['id' => 8, 'sector_id' => 4, 'name' => 'School Classrooms', 'code' => 'EDU-CLS', 'description' => 'Building and renovation of classroom blocks', 'display_order' => 1, 'status' => 'active'],
            ['id' => 9, 'sector_id' => 4, 'name' => 'Libraries & Labs', 'code' => 'EDU-LIB', 'description' => 'ICT centers, libraries, and laboratories', 'display_order' => 2, 'status' => 'active'],

            // Water & Sanitation (Sector 5)
            ['id' => 10, 'sector_id' => 5, 'name' => 'Water Supply', 'code' => 'WTR-SUP', 'description' => 'Boreholes, pipes, and clean water delivery', 'display_order' => 1, 'status' => 'active'],
            ['id' => 11, 'sector_id' => 5, 'name' => 'Sanitation Facilities', 'code' => 'WTR-SAN', 'description' => 'Public toilets and waste disposal sites', 'display_order' => 2, 'status' => 'active'],
            ['id' => 12, 'sector_id' => 5, 'name' => 'Environmental Management', 'code' => 'WTR-ENV', 'description' => 'Clearance of weeds, bushes, and environmental care', 'display_order' => 3, 'status' => 'active'],

            // Agriculture (Sector 6)
            ['id' => 13, 'sector_id' => 6, 'name' => 'Farming Support', 'code' => 'AGR-SUP', 'description' => 'Seeds, fertilizers, and extension services', 'display_order' => 1, 'status' => 'active'],
            
            // Youth & Sports (Sector 7)
            ['id' => 14, 'sector_id' => 7, 'name' => 'Sports Facilities', 'code' => 'YTH-SPT', 'description' => 'Pitches, courts, and sporting complexes', 'display_order' => 1, 'status' => 'active'],
            
            // Electricity (Sector 8)
            ['id' => 15, 'sector_id' => 8, 'name' => 'Street Lighting', 'code' => 'ELC-LGT', 'description' => 'Installation and repair of street lights', 'display_order' => 1, 'status' => 'active'],
            ['id' => 16, 'sector_id' => 8, 'name' => 'Grid Extension', 'code' => 'ELC-GRD', 'description' => 'Connecting communities to power grid', 'display_order' => 2, 'status' => 'active'],
        ];

        foreach ($subSectors as &$sub) {
            $sub['created_at'] = date('Y-m-d H:i:s');
            $sub['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('sub_sectors')->insert($subSectors)->saveData();
        echo "   ✓ Created " . count($subSectors) . " sub-sectors\n";
    }

    private function seedProjects(): void
    {
        echo "🏗️ Seeding projects...\n";

        $projects = [
            ['title' => 'Adum-Asafo Road Rehabilitation', 'slug' => 'adum-asafo-road-rehabilitation', 'sector_id' => 3, 'location' => 'Adum to Asafo', 'description' => 'Complete rehabilitation of the 5km Adum-Asafo road including drainage systems, street lights, and pedestrian walkways.', 'status' => 'ongoing', 'start_date' => '2025-03-01', 'end_date' => '2026-06-30', 'budget' => 2500000.00, 'spent' => 1250000.00, 'progress_percent' => 50, 'beneficiaries' => 150000, 'contractor' => 'Ghana Roads Construction Ltd', 'contact_person' => 'Ing. Kofi Mensah', 'contact_phone' => '+233302111222', 'is_featured' => true, 'views' => 1250, 'created_by' => 1, 'managing_officer_id' => 1],
            ['title' => 'Bantama Community Health Center', 'slug' => 'bantama-community-health-center', 'sector_id' => 2, 'location' => 'Bantama', 'description' => 'Construction of a modern community health center with outpatient services, maternal care unit, and pharmacy.', 'status' => 'ongoing', 'start_date' => '2025-01-15', 'end_date' => '2025-12-31', 'budget' => 1800000.00, 'spent' => 900000.00, 'progress_percent' => 65, 'beneficiaries' => 45000, 'contractor' => 'Modern Healthcare Builders', 'contact_person' => 'Dr. Ama Boateng', 'contact_phone' => '+233302222333', 'is_featured' => true, 'views' => 890, 'created_by' => 1, 'managing_officer_id' => 2],
            ['title' => 'Subin Primary School Block', 'slug' => 'subin-primary-school-block', 'sector_id' => 4, 'location' => 'Subin', 'description' => 'Construction of a 6-classroom block with library, computer lab, and sanitary facilities for Subin Primary School.', 'status' => 'completed', 'start_date' => '2024-06-01', 'end_date' => '2025-02-28', 'budget' => 950000.00, 'spent' => 920000.00, 'progress_percent' => 100, 'beneficiaries' => 800, 'contractor' => 'Educational Structures Ghana', 'contact_person' => 'Mr. Yaw Adjei', 'contact_phone' => '+233302333444', 'is_featured' => false, 'views' => 456, 'created_by' => 1, 'managing_officer_id' => 3],
            ['title' => 'Asafo Market Drainage System', 'slug' => 'asafo-market-drainage-system', 'sector_id' => 1, 'location' => 'Asafo Market', 'description' => 'Installation of comprehensive drainage system to prevent flooding at Asafo Market and surrounding areas.', 'status' => 'ongoing', 'start_date' => '2025-04-01', 'end_date' => '2025-10-31', 'budget' => 750000.00, 'spent' => 225000.00, 'progress_percent' => 30, 'beneficiaries' => 25000, 'contractor' => 'Drainage Solutions Ghana', 'contact_person' => 'Ing. Kwame Osei', 'contact_phone' => '+233302444555', 'is_featured' => false, 'views' => 320, 'created_by' => 1, 'managing_officer_id' => 1],
            ['title' => 'Nhyiaeso Water Supply Project', 'slug' => 'nhyiaeso-water-supply-project', 'sector_id' => 5, 'location' => 'Nhyiaeso', 'description' => 'Extension of pipe-borne water supply to underserved areas in Nhyiaeso with 10 standpipes and household connections.', 'status' => 'planning', 'start_date' => '2026-01-15', 'end_date' => '2026-08-31', 'budget' => 1200000.00, 'spent' => 0.00, 'progress_percent' => 0, 'beneficiaries' => 20000, 'contractor' => null, 'contact_person' => 'Mr. Emmanuel Asare', 'contact_phone' => '+233302555666', 'is_featured' => false, 'views' => 180, 'created_by' => 1, 'managing_officer_id' => null],
            ['title' => 'Oforikrom Youth Sports Complex', 'slug' => 'oforikrom-youth-sports-complex', 'sector_id' => 7, 'location' => 'Oforikrom', 'description' => 'Multi-purpose sports complex with football pitch, basketball court, and gymnasium for youth development.', 'status' => 'planning', 'start_date' => '2026-03-01', 'end_date' => '2027-02-28', 'budget' => 3500000.00, 'spent' => 0.00, 'progress_percent' => 0, 'beneficiaries' => 35000, 'contractor' => null, 'contact_person' => null, 'contact_phone' => null, 'is_featured' => true, 'views' => 560, 'created_by' => 1, 'managing_officer_id' => null],
            ['title' => 'Rural Electrification - Tafo Farms', 'slug' => 'rural-electrification-tafo-farms', 'sector_id' => 8, 'location' => 'Tafo Farming Communities', 'description' => 'Extension of electricity grid to 15 farming communities in Tafo area to support agricultural activities.', 'status' => 'ongoing', 'start_date' => '2025-02-01', 'end_date' => '2025-11-30', 'budget' => 1650000.00, 'spent' => 825000.00, 'progress_percent' => 55, 'beneficiaries' => 8000, 'contractor' => 'ECG Contractors', 'contact_person' => 'Eng. Peter Owusu', 'contact_phone' => '+233302666777', 'is_featured' => false, 'views' => 290, 'created_by' => 1, 'managing_officer_id' => 1],
            ['title' => 'Suame Agricultural Training Center', 'slug' => 'suame-agricultural-training-center', 'sector_id' => 6, 'location' => 'Suame', 'description' => 'Establishment of agricultural training center with demonstration farms, storage facilities, and processing units.', 'status' => 'completed', 'start_date' => '2024-01-01', 'end_date' => '2024-12-15', 'budget' => 2100000.00, 'spent' => 2050000.00, 'progress_percent' => 100, 'beneficiaries' => 5000, 'contractor' => 'AgriDev Ghana', 'contact_person' => 'Dr. Akua Mensah', 'contact_phone' => '+233302777888', 'is_featured' => false, 'views' => 670, 'created_by' => 1, 'managing_officer_id' => 4],
        ];

        foreach ($projects as &$project) {
            $project['created_at'] = date('Y-m-d H:i:s');
            $project['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('projects')->insert($projects)->saveData();
        echo "   ✓ Created " . count($projects) . " projects\n";
    }

    private function seedBlogPosts(): void
    {
        echo "📝 Seeding blog posts...\n";

        $posts = [
            ['title' => 'MP Commissions New Health Center in Bantama', 'slug' => 'mp-commissions-new-health-center-bantama', 'excerpt' => 'The Member of Parliament has commissioned the newly completed Bantama Community Health Center, marking a significant milestone in healthcare delivery for the constituency.', 'content' => '<p>In a colorful ceremony attended by hundreds of constituents, the Member of Parliament officially commissioned the Bantama Community Health Center on Monday.</p><p>The state-of-the-art facility, which cost GHS 1.8 million, features an outpatient department, maternal care unit, pharmacy, and laboratory services.</p><p>"This health center represents our commitment to bringing quality healthcare closer to our people," said the MP during the commissioning ceremony.</p><p>The facility is expected to serve over 45,000 residents in the Bantama area and reduce the burden on the regional hospital.</p>', 'author' => 'Communications Office', 'category' => 'Development', 'tags' => json_encode(['healthcare', 'development', 'bantama']), 'status' => 'published', 'is_featured' => true, 'views' => 1520, 'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')), 'created_by' => 1],
            ['title' => 'Road Construction Update: Adum-Asafo Project Reaches 50%', 'slug' => 'road-construction-update-adum-asafo-50-percent', 'excerpt' => 'The ongoing Adum-Asafo road rehabilitation project has reached the halfway mark with work progressing according to schedule.', 'content' => '<p>The contractor working on the Adum-Asafo road rehabilitation project has announced that the project has reached 50% completion.</p><p>The 5-kilometer road project, which began in March 2025, includes the installation of drainage systems, street lights, and pedestrian walkways.</p><p>According to the project engineer, the drainage works are 70% complete, while road surface works are at 45%.</p><p>"We are on track to complete the project by June 2026 as planned," the engineer stated.</p>', 'author' => 'Development Office', 'category' => 'Infrastructure', 'tags' => json_encode(['roads', 'infrastructure', 'progress']), 'status' => 'published', 'is_featured' => true, 'views' => 890, 'published_at' => date('Y-m-d H:i:s', strtotime('-1 week')), 'created_by' => 2],
            ['title' => 'Youth Empowerment Program Launches in Oforikrom', 'slug' => 'youth-empowerment-program-oforikrom', 'excerpt' => 'A new youth empowerment program has been launched to provide skills training and employment opportunities for young people in Oforikrom.', 'content' => '<p>The constituency has launched a comprehensive youth empowerment program targeting over 500 young people in the Oforikrom area.</p><p>The program will provide training in ICT, entrepreneurship, and vocational skills including tailoring, hairdressing, and electrical installation.</p><p>Participants will also receive startup capital upon completion of their training to establish their own businesses.</p>', 'author' => 'Youth Office', 'category' => 'Youth Development', 'tags' => json_encode(['youth', 'employment', 'training']), 'status' => 'published', 'is_featured' => false, 'views' => 456, 'published_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')), 'created_by' => 2],
            ['title' => 'Clean Water Project Benefits 20,000 Residents', 'slug' => 'clean-water-project-benefits-residents', 'excerpt' => 'The recently completed Nhyiaeso water supply extension project is now providing clean water to thousands of residents.', 'content' => '<p>The Nhyiaeso Water Supply Extension Project has been successfully completed, bringing pipe-borne water to previously underserved communities.</p><p>The project installed 10 public standpipes and provided household connections to over 2,000 homes.</p><p>Residents who previously walked long distances to fetch water are now enjoying convenient access to clean water.</p>', 'author' => 'Water & Sanitation Dept', 'category' => 'Water', 'tags' => json_encode(['water', 'sanitation', 'community']), 'status' => 'published', 'is_featured' => false, 'views' => 678, 'published_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')), 'created_by' => 1],
            ['title' => 'Constituency Town Hall Meeting Scheduled for January 15', 'slug' => 'constituency-town-hall-meeting-january', 'excerpt' => 'All constituents are invited to the upcoming town hall meeting to discuss development priorities for 2026.', 'content' => '<p>The MP\'s office has announced a town hall meeting scheduled for January 15, 2026, at the Adum Community Center.</p><p>The meeting will provide an opportunity for constituents to share their concerns, ask questions, and contribute to the development planning process.</p><p>All residents are encouraged to attend and participate in this important civic engagement event.</p>', 'author' => 'MP Office', 'category' => 'Events', 'tags' => json_encode(['town hall', 'community', 'engagement']), 'status' => 'published', 'is_featured' => true, 'views' => 234, 'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'created_by' => 1],
        ];

        foreach ($posts as &$post) {
            $post['created_at'] = date('Y-m-d H:i:s');
            $post['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('blog_posts')->insert($posts)->saveData();
        echo "   ✓ Created " . count($posts) . " blog posts\n";
    }

    private function seedEvents(): void
    {
        echo "📅 Seeding events...\n";

        $events = [
            ['name' => 'Town Hall Meeting - Q1 2026', 'slug' => 'town-hall-meeting-q1-2026', 'description' => 'Quarterly town hall meeting to discuss constituency development updates and address community concerns.', 'event_date' => '2026-01-15', 'start_time' => '09:00:00', 'end_time' => '13:00:00', 'location' => 'Adum Community Center', 'venue_address' => 'Main Street, Adum, Kumasi', 'organizer' => 'MP Office', 'contact_phone' => '+233302123456', 'contact_email' => 'events@constituency.gov.gh', 'status' => 'upcoming', 'is_featured' => true, 'max_attendees' => 500, 'registration_required' => true, 'created_by' => 1],
            ['name' => 'Health Outreach Program', 'slug' => 'health-outreach-program-bantama', 'description' => 'Free health screening and medical consultation for community members including blood pressure, diabetes, and eye tests.', 'event_date' => '2026-01-20', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'location' => 'Bantama Health Center', 'venue_address' => 'Hospital Road, Bantama', 'organizer' => 'Health Department', 'contact_phone' => '+233302234567', 'contact_email' => 'health@constituency.gov.gh', 'status' => 'upcoming', 'is_featured' => true, 'max_attendees' => 1000, 'registration_required' => false, 'created_by' => 1],
            ['name' => 'Youth Skills Training Workshop', 'slug' => 'youth-skills-training-workshop', 'description' => 'Three-day intensive skills training workshop covering ICT, entrepreneurship, and business management.', 'event_date' => '2026-02-05', 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'location' => 'Oforikrom Youth Center', 'venue_address' => 'Youth Avenue, Oforikrom', 'organizer' => 'Youth Development Office', 'contact_phone' => '+233302345678', 'contact_email' => 'youth@constituency.gov.gh', 'status' => 'upcoming', 'is_featured' => false, 'max_attendees' => 200, 'registration_required' => true, 'created_by' => 2],
            ['name' => 'Farmers Day Celebration', 'slug' => 'farmers-day-celebration-2025', 'description' => 'Annual celebration honoring hardworking farmers with awards, exhibitions, and agricultural demonstrations.', 'event_date' => '2025-12-06', 'start_time' => '10:00:00', 'end_time' => '18:00:00', 'location' => 'Suame Agricultural Center', 'venue_address' => 'Farm Road, Suame', 'organizer' => 'Agriculture Department', 'contact_phone' => '+233302456789', 'contact_email' => 'agric@constituency.gov.gh', 'status' => 'completed', 'is_featured' => false, 'max_attendees' => 800, 'registration_required' => false, 'created_by' => 1],
            ['name' => 'Clean-Up Campaign - Asafo Market', 'slug' => 'cleanup-campaign-asafo-market', 'description' => 'Community clean-up exercise at Asafo Market to promote hygiene and sanitation.', 'event_date' => '2026-01-25', 'start_time' => '06:00:00', 'end_time' => '12:00:00', 'location' => 'Asafo Market', 'venue_address' => 'Asafo Market, Kumasi', 'organizer' => 'Sanitation Department', 'contact_phone' => '+233302567890', 'contact_email' => 'sanitation@constituency.gov.gh', 'status' => 'upcoming', 'is_featured' => false, 'max_attendees' => 300, 'registration_required' => false, 'created_by' => 2],
        ];

        foreach ($events as &$event) {
            $event['created_at'] = date('Y-m-d H:i:s');
            $event['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('constituency_events')->insert($events)->saveData();
        echo "   ✓ Created " . count($events) . " events\n";
    }

    private function seedFaqs(): void
    {
        echo "❓ Seeding FAQs...\n";

        $faqs = [
            ['question' => 'How can I report an issue in my community?', 'answer' => 'You can report community issues through our online portal by visiting the "Report Issue" section. Fill out the form with details about the issue, location, and any supporting images. You will receive a case ID for tracking your report.', 'category' => 'Issue Reporting', 'display_order' => 1, 'status' => 'active', 'created_by' => 1],
            ['question' => 'How do I track the status of my issue report?', 'answer' => 'Use the case ID provided when you submitted your report to track its status. Visit the "Track Issue" page and enter your case ID to see updates, assigned officers, and resolution progress.', 'category' => 'Issue Reporting', 'display_order' => 2, 'status' => 'active', 'created_by' => 1],
            ['question' => 'How can I contact my MP?', 'answer' => 'You can reach the MP\'s office through our contact page. We are available at our constituency office on weekdays from 8 AM to 5 PM. You can also send an email or call our hotline for urgent matters.', 'category' => 'Contact', 'display_order' => 3, 'status' => 'active', 'created_by' => 1],
            ['question' => 'How can I apply for support from the MP\'s Common Fund?', 'answer' => 'To apply for support from the Common Fund, visit our office with a formal written request, valid ID, and relevant supporting documents. Applications are reviewed monthly by the constituency development committee.', 'category' => 'Support', 'display_order' => 4, 'status' => 'active', 'created_by' => 1],
            ['question' => 'When are town hall meetings held?', 'answer' => 'Town hall meetings are held quarterly on the second Saturday of January, April, July, and October. Announcements are made through our website, social media, and local information centers at least two weeks in advance.', 'category' => 'Events', 'display_order' => 5, 'status' => 'active', 'created_by' => 1],
            ['question' => 'How can I subscribe to constituency updates?', 'answer' => 'You can subscribe to our newsletter through the subscription form at the bottom of our website. Provide your email and optionally your phone number to receive regular updates about development projects, events, and announcements.', 'category' => 'General', 'display_order' => 6, 'status' => 'active', 'created_by' => 1],
            ['question' => 'What development projects are currently ongoing?', 'answer' => 'Visit our Projects page to see all ongoing, completed, and planned development projects. Each project page includes details about location, budget, timeline, and current progress.', 'category' => 'Projects', 'display_order' => 7, 'status' => 'active', 'created_by' => 1],
            ['question' => 'How can I volunteer for community programs?', 'answer' => 'We welcome volunteers for various community programs. Contact our Youth and Community office or fill out the volunteer registration form on our website. We will match you with programs that align with your interests and skills.', 'category' => 'Volunteering', 'display_order' => 8, 'status' => 'active', 'created_by' => 1],
        ];

        foreach ($faqs as &$faq) {
            $faq['created_at'] = date('Y-m-d H:i:s');
            $faq['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('faqs')->insert($faqs)->saveData();
        echo "   ✓ Created " . count($faqs) . " FAQs\n";
    }

    private function seedHeroSlides(): void
    {
        echo "🖼️ Seeding hero slides...\n";

        $slides = [
            ['title' => 'Building Our Constituency Together', 'subtitle' => 'Development Through Unity and Progress', 'description' => 'Join us in transforming our communities through sustainable development projects, quality infrastructure, and improved social services.', 'image' => '/images/hero/hero-1.jpg', 'cta_label' => 'View Projects', 'cta_link' => '/projects', 'display_order' => 1, 'status' => 'active', 'created_by' => 1],
            ['title' => 'Your Voice Matters', 'subtitle' => 'Report Issues, Track Progress', 'description' => 'We are committed to addressing your concerns. Report community issues and track our response in real-time.', 'image' => '/images/hero/hero-2.jpg', 'cta_label' => 'Report Issue', 'cta_link' => '/report-issue', 'display_order' => 2, 'status' => 'active', 'created_by' => 1],
            ['title' => 'Investing in Our Future', 'subtitle' => 'Education & Youth Development', 'description' => 'Supporting quality education and youth empowerment programs to build a skilled and prosperous generation.', 'image' => '/images/hero/hero-3.jpg', 'cta_label' => 'Learn More', 'cta_link' => '/projects?sector=education', 'display_order' => 3, 'status' => 'active', 'created_by' => 1],
            ['title' => 'Healthcare for All', 'subtitle' => 'Bringing Quality Healthcare Closer', 'description' => 'New health facilities and outreach programs ensuring accessible healthcare for every community member.', 'image' => '/images/hero/hero-4.jpg', 'cta_label' => 'Health Programs', 'cta_link' => '/projects?sector=healthcare', 'display_order' => 4, 'status' => 'active', 'created_by' => 1],
        ];

        foreach ($slides as &$slide) {
            $slide['created_at'] = date('Y-m-d H:i:s');
            $slide['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('hero_slides')->insert($slides)->saveData();
        echo "   ✓ Created " . count($slides) . " hero slides\n";
    }

    private function seedCommunityStats(): void
    {
        echo "📈 Seeding community stats...\n";

        $stats = [
            ['label' => 'Projects Completed', 'value' => '45+', 'icon' => 'check-circle', 'display_order' => 1, 'status' => 'active', 'created_by' => 1],
            ['label' => 'Ongoing Projects', 'value' => '12', 'icon' => 'loader', 'display_order' => 2, 'status' => 'active', 'created_by' => 1],
            ['label' => 'Beneficiaries', 'value' => '350K+', 'icon' => 'users', 'display_order' => 3, 'status' => 'active', 'created_by' => 1],
            ['label' => 'Communities Served', 'value' => '67', 'icon' => 'home', 'display_order' => 4, 'status' => 'active', 'created_by' => 1],
            ['label' => 'Issues Resolved', 'value' => '1,200+', 'icon' => 'check-square', 'display_order' => 5, 'status' => 'active', 'created_by' => 1],
            ['label' => 'Investment (GHS)', 'value' => '25M+', 'icon' => 'trending-up', 'display_order' => 6, 'status' => 'active', 'created_by' => 1],
        ];

        foreach ($stats as &$stat) {
            $stat['created_at'] = date('Y-m-d H:i:s');
            $stat['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('community_stats')->insert($stats)->saveData();
        echo "   ✓ Created " . count($stats) . " community stats\n";
    }

    private function seedContactInfo(): void
    {
        echo "📞 Seeding contact info...\n";

        $contacts = [
            ['type' => 'address', 'label' => 'Main Office', 'value' => 'Block A, Civic Center, Adum, Kumasi', 'icon' => 'map-pin', 'display_order' => 1, 'status' => 'active', 'created_by' => 1],
            ['type' => 'phone', 'label' => 'Office Line', 'value' => '+233 302 123 456', 'icon' => 'phone', 'link' => 'tel:+233302123456', 'display_order' => 2, 'status' => 'active', 'created_by' => 1],
            ['type' => 'phone', 'label' => 'Hotline', 'value' => '+233 200 111 222', 'icon' => 'phone-call', 'link' => 'tel:+233200111222', 'display_order' => 3, 'status' => 'active', 'created_by' => 1],
            ['type' => 'email', 'label' => 'General Enquiries', 'value' => 'info@constituency.gov.gh', 'icon' => 'mail', 'link' => 'mailto:info@constituency.gov.gh', 'display_order' => 4, 'status' => 'active', 'created_by' => 1],
            ['type' => 'email', 'label' => 'Support', 'value' => 'support@constituency.gov.gh', 'icon' => 'help-circle', 'link' => 'mailto:support@constituency.gov.gh', 'display_order' => 5, 'status' => 'active', 'created_by' => 1],
            ['type' => 'social', 'label' => 'Facebook', 'value' => '@ConstituencyHub', 'icon' => 'facebook', 'link' => 'https://facebook.com/ConstituencyHub', 'display_order' => 6, 'status' => 'active', 'created_by' => 1],
            ['type' => 'social', 'label' => 'Twitter', 'value' => '@ConstituencyHub', 'icon' => 'twitter', 'link' => 'https://twitter.com/ConstituencyHub', 'display_order' => 7, 'status' => 'active', 'created_by' => 1],
            ['type' => 'social', 'label' => 'Instagram', 'value' => '@constituency_hub', 'icon' => 'instagram', 'link' => 'https://instagram.com/constituency_hub', 'display_order' => 8, 'status' => 'active', 'created_by' => 1],
        ];

        foreach ($contacts as &$contact) {
            $contact['created_at'] = date('Y-m-d H:i:s');
            $contact['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('contact_info')->insert($contacts)->saveData();
        echo "   ✓ Created " . count($contacts) . " contact entries\n";
    }

    private function seedIssueReports(): void
    {
        echo "🚨 Seeding issue reports...\n";

        $issues = [
            ['case_id' => 'ISS-2025-0001', 'title' => 'Pothole on Main Street Adum', 'description' => 'Large pothole causing traffic hazards and vehicle damage near the central market.', 'category' => 'Roads', 'sector_id' => 3, 'sub_sector_id' => 7, 'location' => 'Main Street, Adum', 'latitude' => 6.6885, 'longitude' => -1.6244, 'reporter_name' => 'Kofi Ansah', 'reporter_email' => 'kofi.ansah@email.com', 'reporter_phone' => '+233241234567', 'status' => 'resolved', 'priority' => 'high', 'resolution_notes' => 'Pothole filled and road surface repaired by maintenance team.', 'acknowledged_at' => date('Y-m-d H:i:s', strtotime('-25 days')), 'resolved_at' => date('Y-m-d H:i:s', strtotime('-20 days')), 'submitted_by_agent_id' => 1, 'assigned_officer_id' => 1],
            ['case_id' => 'ISS-2025-0002', 'title' => 'Broken Street Light', 'description' => 'Street light not working for two weeks creating safety concerns at night.', 'category' => 'Electricity', 'sector_id' => 8, 'sub_sector_id' => 15, 'location' => 'Station Road, Bantama', 'latitude' => 6.7012, 'longitude' => -1.6189, 'reporter_name' => 'Ama Serwaa', 'reporter_email' => 'ama.serwaa@email.com', 'reporter_phone' => '+233242345678', 'status' => 'under_officer_review', 'priority' => 'medium', 'acknowledged_at' => date('Y-m-d H:i:s', strtotime('-5 days')), 'submitted_by_agent_id' => 2, 'assigned_officer_id' => 1],
            ['case_id' => 'ISS-2025-0003', 'title' => 'Blocked Drainage Channel', 'description' => 'Drainage blocked with refuse causing flooding during rains.', 'category' => 'Drainage', 'sector_id' => 1, 'sub_sector_id' => 2, 'location' => 'Market Square, Asafo', 'latitude' => 6.6823, 'longitude' => -1.6112, 'reporter_name' => 'Yaw Mensah', 'reporter_email' => 'yaw.mensah@email.com', 'reporter_phone' => '+233243456789', 'status' => 'assigned_to_task_force', 'priority' => 'urgent', 'acknowledged_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'submitted_by_agent_id' => 3, 'assigned_officer_id' => 1, 'assigned_task_force_id' => 1],
            ['case_id' => 'ISS-2025-0004', 'title' => 'Water Supply Interruption', 'description' => 'No water supply for the past week affecting over 50 households.', 'category' => 'Water', 'sector_id' => 5, 'sub_sector_id' => 10, 'location' => 'Nhyiaeso East', 'latitude' => 6.6756, 'longitude' => -1.6278, 'reporter_name' => 'Akua Boateng', 'reporter_email' => 'akua.boateng@email.com', 'reporter_phone' => '+233244567890', 'status' => 'assessment_in_progress', 'priority' => 'high', 'acknowledged_at' => date('Y-m-d H:i:s', strtotime('-10 days')), 'submitted_by_agent_id' => 4, 'assigned_officer_id' => 2, 'assigned_task_force_id' => 2],
            ['case_id' => 'ISS-2025-0005', 'title' => 'Damaged School Roof', 'description' => 'School roof damaged by recent storm, rainwater entering classrooms.', 'category' => 'Education', 'sector_id' => 4, 'sub_sector_id' => 8, 'location' => 'Subin Primary School', 'latitude' => 6.6934, 'longitude' => -1.6156, 'reporter_name' => 'Mr. John Osei', 'reporter_email' => 'john.osei@school.edu.gh', 'reporter_phone' => '+233245678901', 'status' => 'submitted', 'priority' => 'high'],
            ['case_id' => 'ISS-2025-0006', 'title' => 'Overgrown Vegetation Near Road', 'description' => 'Tall grass and bushes obstructing visibility at road junction.', 'category' => 'Environment', 'sector_id' => 5, 'sub_sector_id' => 12, 'location' => 'Tafo Junction', 'latitude' => 6.7089, 'longitude' => -1.6045, 'reporter_name' => 'Kwame Adjei', 'reporter_email' => 'kwame.adjei@email.com', 'reporter_phone' => '+233246789012', 'status' => 'resolved', 'priority' => 'medium', 'resolution_notes' => 'Area cleared by sanitation team.', 'acknowledged_at' => date('Y-m-d H:i:s', strtotime('-15 days')), 'resolved_at' => date('Y-m-d H:i:s', strtotime('-12 days')), 'assigned_officer_id' => 4],
        ];

        foreach ($issues as &$issue) {
            $issue['created_at'] = date('Y-m-d H:i:s', strtotime('-' . rand(5, 30) . ' days'));
            $issue['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('issue_reports')->insert($issues)->saveData();
        echo "   ✓ Created " . count($issues) . " issue reports\n";
    }

    private function seedNewsletterSubscribers(): void
    {
        echo "📧 Seeding newsletter subscribers...\n";

        $subscribers = [
            ['email' => 'subscriber1@email.com', 'name' => 'Emmanuel Asante', 'phone' => '+233247890123', 'status' => 'active'],
            ['email' => 'subscriber2@email.com', 'name' => 'Mary Osei', 'phone' => '+233248901234', 'status' => 'active'],
            ['email' => 'subscriber3@email.com', 'name' => 'Peter Mensah', 'status' => 'active'],
            ['email' => 'subscriber4@email.com', 'name' => 'Grace Boateng', 'phone' => '+233249012345', 'status' => 'active'],
            ['email' => 'subscriber5@email.com', 'name' => 'Kofi Owusu', 'status' => 'active'],
            ['email' => 'oldsubscriber@email.com', 'name' => 'Former Subscriber', 'status' => 'unsubscribed', 'unsubscribed_at' => date('Y-m-d H:i:s', strtotime('-1 month'))],
        ];

        foreach ($subscribers as &$subscriber) {
            $subscriber['subscribed_at'] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 180) . ' days'));
            $subscriber['created_at'] = $subscriber['subscribed_at'];
            $subscriber['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->table('newsletter_subscribers')->insert($subscribers)->saveData();
        echo "   ✓ Created " . count($subscribers) . " newsletter subscribers\n";
    }
}
