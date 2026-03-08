<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ConstituencyEvent Model
 */
class ConstituencyEvent extends Model
{
    protected $table = 'constituency_events';

    protected $fillable = [
        'name',
        'event_date',
        'status',
        'images'
    ];

    protected $casts = [
        'event_date' => 'date',
        'images' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const STATUS_UPCOMING = 'upcoming';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_POSTPONED = 'postponed';

    /**
     * Scope for upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_UPCOMING)
                     ->where('event_date', '>=', date('Y-m-d'));
    }
}
