<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Job Model
 * Represents an employment opportunity within the constituency.
 */
class Job extends Model
{
    protected $table = 'employment_jobs';

    protected $fillable = [
        'title',
        'description',
        'job_information',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Relationship to applicants
     */
    public function applicants()
    {
        return $this->hasMany(JobApplicant::class, 'job_id');
    }

    /**
     * Scope for open jobs
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
