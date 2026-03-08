<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class IssueDataSeeder extends AbstractSeed
{
    /**
     * Run Method - Seed all relevant tables for issues
     */
    public function run(): void
    {
        // 1. Seed Locations (Communities & Suburbs)
        $communities = [
            ['name' => 'Sefwi Wiawso', 'type' => 'community'],
            ['name' => 'Asawinso', 'type' => 'community'],
            ['name' => 'Boako', 'type' => 'community'],
        ];

        foreach ($communities as $comm) {
            $exists = $this->fetchRow('SELECT id FROM locations WHERE name = \'' . $comm['name'] . '\' LIMIT 1');
            if (!$exists) {
                $this->table('locations')->insert([$comm])->save();
            }
        }

        $wiawso = $this->fetchRow('SELECT id FROM locations WHERE name = \'Sefwi Wiawso\' LIMIT 1');
        $asawinso = $this->fetchRow('SELECT id FROM locations WHERE name = \'Asawinso\' LIMIT 1');

        $suburbs = [
            ['name' => 'Dwenase', 'type' => 'suburb', 'parent_id' => $wiawso['id']],
            ['name' => 'Datano', 'type' => 'suburb', 'parent_id' => $wiawso['id']],
            ['name' => 'Benchema', 'type' => 'suburb', 'parent_id' => $asawinso['id']],
        ];

        foreach ($suburbs as $sub) {
            $exists = $this->fetchRow('SELECT id FROM locations WHERE name = \'' . $sub['name'] . '\' LIMIT 1');
            if (!$exists) {
                $this->table('locations')->insert([$sub])->save();
            }
        }

        // 2. Seed Categories, Sectors, Sub-Sectors
        $categories = [
            ['name' => 'Infrastructure', 'slug' => 'infrastructure'],
            ['name' => 'Health', 'slug' => 'health'],
            ['name' => 'Education', 'slug' => 'education'],
            ['name' => 'Sanitation', 'slug' => 'sanitation'],
        ];

        foreach ($categories as $cat) {
            $exists = $this->fetchRow('SELECT id FROM categories WHERE slug = \'' . $cat['slug'] . '\' LIMIT 1');
            if (!$exists) {
                $this->table('categories')->insert([$cat])->save();
            }
        }

        $infra = $this->fetchRow('SELECT id FROM categories WHERE slug = \'infrastructure\' LIMIT 1');
        $health = $this->fetchRow('SELECT id FROM categories WHERE slug = \'health\' LIMIT 1');
        $sanitation = $this->fetchRow('SELECT id FROM categories WHERE slug = \'sanitation\' LIMIT 1');

        $sectorsData = [
            ['name' => 'Roads', 'slug' => 'roads', 'category_id' => $infra['id']],
            ['name' => 'Water', 'slug' => 'water', 'category_id' => $infra['id']],
            ['name' => 'Clinics', 'slug' => 'clinics', 'category_id' => $health['id']],
            ['name' => 'Waste Management', 'slug' => 'waste-mgmt', 'category_id' => $sanitation['id']],
        ];

        foreach ($sectorsData as $sec) {
            $exists = $this->fetchRow('SELECT id FROM sectors WHERE slug = \'' . $sec['slug'] . '\' LIMIT 1');
            if (!$exists) {
                $this->table('sectors')->insert([$sec])->save();
            }
        }

        $roads = $this->fetchRow('SELECT id FROM sectors WHERE slug = \'roads\' LIMIT 1');
        $clinics = $this->fetchRow('SELECT id FROM sectors WHERE slug = \'clinics\' LIMIT 1');
        $waste = $this->fetchRow('SELECT id FROM sectors WHERE slug = \'waste-mgmt\' LIMIT 1');

        $subSectorsData = [
            ['name' => 'Potholes', 'slug' => 'potholes', 'sector_id' => $roads['id']],
            ['name' => 'Street Lights', 'slug' => 'street-lights', 'sector_id' => $roads['id']],
            ['name' => 'Medical Supplies', 'slug' => 'med-supplies', 'sector_id' => $clinics['id']],
            ['name' => 'Drainage', 'slug' => 'drainage', 'sector_id' => $waste['id']],
        ];

        foreach ($subSectorsData as $subSec) {
            $exists = $this->fetchRow('SELECT id FROM sub_sectors WHERE slug = \'' . $subSec['slug'] . '\' LIMIT 1');
            if (!$exists) {
                $this->table('sub_sectors')->insert([$subSec])->save();
            }
        }

        // 3. Seed Constituents
        $constituents = [
            ['name' => 'Stephen Amoah', 'phone_number' => '0241234567', 'email' => 'stephen@example.com', 'gender' => 'Male', 'home_address' => 'House No. 12, Dwenase'],
            ['name' => 'Alice Mensah', 'phone_number' => '0509876543', 'email' => 'alice@example.com', 'gender' => 'Female', 'home_address' => 'Apt 4B, Datano'],
        ];

        foreach ($constituents as $con) {
            $exists = $this->fetchRow('SELECT id FROM constituents WHERE phone_number = \'' . $con['phone_number'] . '\' LIMIT 1');
            if (!$exists) {
                $this->table('constituents')->insert([$con])->save();
            }
        }

        // 4. Seed Issues
        $agent = $this->fetchRow('SELECT id FROM users WHERE email = \'agent@example.com\' LIMIT 1');
        $officer = $this->fetchRow('SELECT id FROM users WHERE email = \'officer@example.com\' LIMIT 1');
        $constituent = $this->fetchRow('SELECT id FROM constituents LIMIT 1');
        $dwenase = $this->fetchRow('SELECT id FROM locations WHERE name = \'Dwenase\' LIMIT 1');
        
        $potholes = $this->fetchRow('SELECT id FROM sub_sectors WHERE slug = \'potholes\' LIMIT 1');
        $lights = $this->fetchRow('SELECT id FROM sub_sectors WHERE slug = \'street-lights\' LIMIT 1');
        $drainage = $this->fetchRow('SELECT id FROM sub_sectors WHERE slug = \'drainage\' LIMIT 1');

        $issues = [
            [
                'title' => 'Severe Potholes on Dwenase Main Road',
                'description' => 'The road from the market to the station is riddled with deep potholes causing traffic and accidents.',
                'category_id' => $infra['id'],
                'sector_id' => $roads['id'],
                'sub_sector_id' => $potholes['id'],
                'community_id' => $wiawso['id'],
                'suburb_id' => $dwenase['id'],
                'specific_location' => 'Across the Dwenase Market',
                'details' => 'Reported by community members during morning commute.',
                'issue_type' => 'community_based',
                'people_affected' => 500,
                'estimated_budget' => 45000.00,
                'status' => 'submitted',
                'priority' => 'high',
                'images' => json_encode(['https://images.unsplash.com/photo-1515162816999-a0c47dc192f7']),
                'constituent_id' => $constituent['id'],
                'agent_id' => $agent['id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => 'Inoperative Street Lights',
                'description' => 'Most street lights in the suburb are off, leading to security concerns at night.',
                'category_id' => $infra['id'],
                'sector_id' => $roads['id'],
                'sub_sector_id' => $lights['id'],
                'community_id' => $wiawso['id'],
                'suburb_id' => $dwenase['id'],
                'specific_location' => 'Datano Residential Area',
                'details' => 'Security patrol noted this issue.',
                'issue_type' => 'community_based',
                'people_affected' => 200,
                'estimated_budget' => 5000.00,
                'status' => 'under_officer_review',
                'priority' => 'medium',
                'images' => json_encode([]),
                'constituent_id' => $constituent['id'],
                'agent_id' => $agent['id'] ?? null,
                'officer_id' => $officer['id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => 'Waste Overflow near Clinic',
                'description' => 'Trash bins have not been emptied for over a week near the community clinic.',
                'category_id' => $sanitation['id'],
                'sector_id' => $waste['id'],
                'sub_sector_id' => $drainage['id'],
                'community_id' => $wiawso['id'],
                'suburb_id' => $dwenase['id'],
                'specific_location' => 'Dwenase Clinic Gate',
                'details' => 'Health concern for patients.',
                'issue_type' => 'individual_based',
                'people_affected' => 50,
                'estimated_budget' => 1200.00,
                'status' => 'in_progress',
                'priority' => 'urgent',
                'images' => json_encode([]),
                'constituent_id' => $constituent['id'],
                'agent_id' => $agent['id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($issues as $issue) {
            $existing = $this->fetchRow('SELECT id FROM issues WHERE title = \'' . addslashes($issue['title']) . '\' LIMIT 1');
            if (!$existing) {
                $this->table('issues')->insert([$issue])->save();
            } else {
                // Update existing with new fields
                $this->execute("UPDATE issues SET 
                    issue_type = '" . $issue['issue_type'] . "',
                    status = '" . $issue['status'] . "',
                    people_affected = " . ($issue['people_affected'] ?? 'NULL') . ",
                    estimated_budget = " . ($issue['estimated_budget'] ?? 'NULL') . "
                    WHERE id = " . $existing['id']);
            }
        }

        echo "✅ Issue related tables (Locations, Categories, Sectors, Constituents, and Issues) seeded successfully!\n";
    }
}
