<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IssueResolution Model
 * 
 * Represents the final resolution report for an issue.
 */
class IssueResolution extends Model
{
    protected $table = 'issue_resolutions';

    protected $fillable = [
        'issues_id',
        'summary',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_COMPLETED = 'completed';

    /**
     * Relationship back to the issue
     */
    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issues_id');
    }
}
