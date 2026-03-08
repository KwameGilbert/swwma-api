<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * WebAdminProfile Model
 * 
 * Represents the profile data for a web_admin user.
 */
class WebAdminProfile extends Model
{
    protected $table = 'web_admin_profiles';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'gender',
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
