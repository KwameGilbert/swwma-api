<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Model
 * 
 * Represents a user in the system.
 * Handles authentication, relationships, and status checks.
 *
 * @property int $id
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $remember_token
 * @property bool $email_verified
 * @property string $role
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'email',
        'phone',
        'password',
        'remember_token',
        'role',
        'email_verified',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * @var array
     */
    protected $casts = [
        'email_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';

    // Role Constants
    const ROLE_ADMIN = 'admin';
    const ROLE_WEB_ADMIN = 'web_admin';
    const ROLE_OFFICER = 'officer';
    const ROLE_AGENT = 'agent';
    const ROLE_TASK_FORCE = 'task_force';

    /* -----------------------------------------------------------------
     |  Mutators & Accessors
     | -----------------------------------------------------------------
     */

    /**
     * Auto-hash password with Argon2id on set.
     */
    public function setPasswordAttribute($value)
    {
        if (preg_match('/^(\$argon2|\$2y\$)/', $value)) {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = password_hash($value, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 2
            ]);
        }
    }

    /* -----------------------------------------------------------------
     |  Static Search Methods
     | -----------------------------------------------------------------
     */

    /**
     * Get user by email.
     */
    public static function findByEmail(string $email): ?User
    {
        return static::where('email', $email)->first();
    }

    /* -----------------------------------------------------------------
     |  Helper Methods
     | -----------------------------------------------------------------
     */

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get the profile associated with the user's role.
     */
    public function getProfile()
    {
        switch ($this->role) {
            case self::ROLE_WEB_ADMIN:
                return $this->webAdminProfile;
            case self::ROLE_OFFICER:
                return $this->officerProfile;
            case self::ROLE_AGENT:
                return $this->agentProfile;
            case self::ROLE_TASK_FORCE:
                return $this->taskForceProfile;
            case self::ROLE_ADMIN:
                return $this->adminProfile;
            default:
                return null;
        }
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */
    
    public function webAdminProfile()
    {
        return $this->hasOne(WebAdminProfile::class, 'user_id');
    }

    public function officerProfile()
    {
        return $this->hasOne(OfficerProfile::class, 'user_id');
    }

    public function agentProfile()
    {
        return $this->hasOne(AgentProfile::class, 'user_id');
    }

    public function taskForceProfile()
    {
        return $this->hasOne(TaskForceProfile::class, 'user_id');
    }

    public function adminProfile()
    {
        return $this->hasOne(AdminProfile::class, 'user_id');
    }

    public function getFullName(): string
    {
        $profile = $this->getProfile();
        if ($profile) {
            return trim($profile->first_name . ' ' . $profile->last_name);
        }
        return $this->email;
    }

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }
}