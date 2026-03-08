<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Project Model
 * Tracker for constituency-wide development projects.
 */
class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'title',
        'status',
        'budget',
        'progress_percent',
        'images'
    ];

    protected $casts = [
        'budget' => 'float',
        'progress_percent' => 'integer',
        'images' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const STATUS_PLANNING = 'planning';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_CANCELLED = 'cancelled';
}
