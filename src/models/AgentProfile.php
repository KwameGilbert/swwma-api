<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AgentProfile Model
 * 
 * Represents the profile data for an agent user.
 */
class AgentProfile extends Model
{
    protected $table = 'agent_profiles';

    protected $fillable = [
        'user_id',
        'agent_code',
        'first_name',
        'last_name',
        'address',
        'gender',
        'id_type',
        'id_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'profile_image'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
