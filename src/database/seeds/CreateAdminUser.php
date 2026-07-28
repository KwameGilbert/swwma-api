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
        // Truncate users table first
        $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        $this->execute('TRUNCATE TABLE users;');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1;');

        // Hash password with Bcrypt (same as User model)
        $passwordHash = password_hash('Password@123', PASSWORD_DEFAULT);

        // Insert admin user
        $this->table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'password' => $passwordHash,
                'role' => 'admin',
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'first_login' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ])->save();

        echo "✅ Admin user created successfully!\n";
        echo "   Email: admin@gmail.com\n";
        echo "   Password: Password@123\n";
        echo "   ⚠️  Please change this password after first login!\n";
    }
}
