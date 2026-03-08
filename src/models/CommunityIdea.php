<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CommunityIdea Model
 */
class CommunityIdea extends Model
{
    protected $table = 'community_ideas';

    protected $fillable = [
        'title',
        'description'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
