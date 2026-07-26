<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CreateTestAgentUser extends AbstractSeed
{
    /**
     * Run Method - Creates a test agent user for testing the agent dashboard
     */
    public function run(): void
    {
        // Check if test agent already exists
        $agentExists = $this->fetchRow("SELECT * FROM users WHERE email = 'testagent@gmail.com' LIMIT 1");

        if ($agentExists) {
            echo "Test agent user already exists. Skipping...\n";
            return;
        }

        // Hash password with Bcrypt (same as User model)
        $passwordHash = password_hash('Password@123', PASSWORD_DEFAULT);

        // Insert agent user
        $userData = [
            'name' => 'Test Agent',
            'email' => 'testagent@gmail.com',
            'phone' => '+233501234567',
            'password' => $passwordHash,
            'role' => 'agent',
            'status' => 'active',
            'email_verified' => true,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'first_login' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->table('users')->insert([$userData])->save();

        // Get the user ID
        $userId = $this->getAdapter()->getConnection()->lastInsertId();

        // Check if agents table exists and create agent profile
        if ($this->hasTable('agents')) {
            $agentCode = 'AGT-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
            
            $this->table('agents')->insert([
                [
                    'user_id' => $userId,
                    'agent_code' => $agentCode,
                    'supervisor_id' => null,
                    'assigned_communities' => json_encode(['Test Community', 'Demo Area']),
                    'assigned_location' => 'Test Location',
                    'can_submit_reports' => true,
                    'can_collect_data' => true,
                    'can_register_residents' => false,
                    'profile_image' => null,
                    'id_type' => 'Ghana Card',
                    'id_number' => 'GHA-123456789-0',
                    'id_verified' => true,
                    'address' => '123 Test Street, Test City',
                    'reports_submitted' => 0,
                    'emergency_contact_name' => 'Emergency Contact',
                    'emergency_contact_phone' => '+233502345678',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            ])->save();
        }

        echo "✅ Test agent user created successfully!\n";
        echo "   Email: testagent@gmail.com\n";
        echo "   Password: Password@123\n";
        echo "   ⚠️  Use these credentials to test the agent dashboard.\n";
    }
}
