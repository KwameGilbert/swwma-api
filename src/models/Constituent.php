<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Constituent Model
 * 
 * Represents a person living in the constituency who can report issues.
 */
class Constituent extends Model
{
    protected $table = 'constituents';

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'gender',
        'home_address'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Issues reported by this constituent
     */
    public function issues()
    {
        return $this->hasMany(Issue::class, 'constituent_id');
    }
}
