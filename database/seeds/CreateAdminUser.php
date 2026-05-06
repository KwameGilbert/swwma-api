<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CreateAdminUser extends AbstractSeed
{
    /**
     * Run Method - Creates an admin user for the platform
     */
    public function run(): void
    {
        // Check if admin already exists
        $adminExists = $this->fetchRow('SELECT * FROM users WHERE role = \'admin\' LIMIT 1');

        if ($adminExists) {
            echo "Admin user already exists. Skipping...\n";
            return;
        }

        // Hash password with Argon2id (same as User model)
        $passwordHash = password_hash('Admin@123', PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,  
            'time_cost' => 4,        
            'threads' => 2           // 2 parallel threads
        ]);

        // Insert admin user
        $this->table('users')->insert([
            [
                'email' => 'admin@eventic.com',
                'password' => $passwordHash,
                'role' => 'admin',
                'status' => 'active',
                'email_verified' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ])->save();

        // Get the inserted user ID
        $insertedUser = $this->fetchRow('SELECT id FROM users WHERE email = \'admin@eventic.com\' LIMIT 1');
        $userId = (int)$insertedUser['id'];

        // Create corresponding admin profile
        $profileExists = $this->fetchRow('SELECT id FROM admin_profiles WHERE user_id = ' . $userId . ' LIMIT 1');
        if (!$profileExists) {
            $this->table('admin_profiles')->insert([
                [
                    'user_id' => $userId,
                    'first_name' => 'Admin',
                    'last_name' => 'User',
                    'gender' => 'male',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            ])->save();
        }

        echo "✅ Admin user created successfully!\n";
        echo "   Email: admin@eventic.com\n";
        echo "   Password: Admin@123\n";
        echo "   ⚠️  Please change this password after first login!\n";
    }
}
