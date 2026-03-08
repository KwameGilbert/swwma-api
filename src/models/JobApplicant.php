<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * JobApplicant Model
 * Represents a person who applied for a job.
 */
class JobApplicant extends Model
{
    protected $table = 'job_applicants';

    protected $fillable = [
        'job_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'cv_path',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SHORTLISTED = 'shortlisted';
    const STATUS_INTERVIEWED = 'interviewed';
    const STATUS_HIRED = 'hired';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationship to the job
     */
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * Get the full name of the applicant
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
