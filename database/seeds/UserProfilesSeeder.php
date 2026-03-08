<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserProfilesSeeder extends AbstractSeed
{
    /**
     * Run Method - Seed the users and profiles tables
     */
    public function run(): void
    {
        // Password for all: Password@123
        $passwordHash = password_hash('Password@123', PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2
        ]);

        $users = [
            [
                'email' => 'admin@example.com',
                'password' => $passwordHash,
                'role' => 'admin',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email' => 'webadmin@example.com',
                'password' => $passwordHash,
                'role' => 'web_admin',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email' => 'officer@example.com',
                'password' => $passwordHash,
                'role' => 'officer',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email' => 'agent@example.com',
                'password' => $passwordHash,
                'role' => 'agent',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email' => 'taskforce@example.com',
                'password' => $passwordHash,
                'role' => 'task_force',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($users as $userData) {
            // Check if user already exists
            $existingUser = $this->fetchRow('SELECT id FROM users WHERE email = \'' . $userData['email'] . '\' LIMIT 1');
            
            if (!$existingUser) {
                $this->table('users')->insert([$userData])->save();
                // Get the last insert ID. 
                // Using fetchRow on same email as $this->getAdapter()->getConnection()->lastInsertId() can be flaky in some Phinx setups
                $insertedUser = $this->fetchRow('SELECT id FROM users WHERE email = \'' . $userData['email'] . '\' LIMIT 1');
                $userId = (int)$insertedUser['id'];
            } else {
                $userId = (int)$existingUser['id'];
            }

            // Create corresponding profile
            switch ($userData['role']) {
                case 'admin':
                    $this->seedProfile('admin_profiles', $userId, 'Super', 'Admin');
                    break;
                case 'web_admin':
                    $this->seedProfile('web_admin_profiles', $userId, 'Web', 'Admin');
                    break;
                case 'officer':
                    $this->seedProfile('officer_profiles', $userId, 'Senior', 'Officer');
                    break;
                case 'agent':
                    $this->seedAgentProfile($userId, 'Field', 'Agent');
                    break;
                case 'task_force':
                    $this->seedProfile('task_force_profiles', $userId, 'Task', 'Force');
                    break;
            }
        }

        echo "✅ Users and Profiles seeded successfully!\n";
        echo "   Password for all: Password@123\n";
    }

    private function seedProfile(string $table, int $userId, string $fname, string $lname): void
    {
        $exists = $this->fetchRow('SELECT id FROM ' . $table . ' WHERE user_id = ' . $userId . ' LIMIT 1');
        if (!$exists) {
            $this->table($table)->insert([
                [
                    'user_id' => $userId,
                    'first_name' => $fname,
                    'last_name' => $lname,
                    'gender' => 'Male',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            ])->save();
        }
    }

    private function seedAgentProfile(int $userId, string $fname, string $lname): void
    {
        $exists = $this->fetchRow('SELECT id FROM agent_profiles WHERE user_id = ' . $userId . ' LIMIT 1');
        if (!$exists) {
            $this->table('agent_profiles')->insert([
                [
                    'user_id' => $userId,
                    'agent_code' => 'AGT-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT),
                    'first_name' => $fname,
                    'last_name' => $lname,
                    'address' => '123 Agent St, Sefwi Wiawso',
                    'gender' => 'Male',
                    'id_type' => 'Ghana Card',
                    'id_number' => 'GHA-000000000-0',
                    'emergency_contact_name' => 'Emergency Contact',
                    'emergency_contact_phone' => '0240000000',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            ])->save();
        }
    }
}
