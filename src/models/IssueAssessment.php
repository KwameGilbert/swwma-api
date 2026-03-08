<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IssueAssessment Model
 * 
 * Represents the technical assessment report for an issue.
 */
class IssueAssessment extends Model
{
    protected $table = 'issue_assessments';

    protected $fillable = [
        'issues_id',
        'recommendations',
        'estimated_costs',
        'estimated_duration',
        'description',
        'issue_confirmed',
        'attachments',
        'status'
    ];

    protected $casts = [
        'attachments' => 'json',
        'issue_confirmed' => 'boolean',
        'estimated_costs' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Status Constants
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_NEEDS_REVISION = 'needs_revision';

    /**
     * Relationship back to the issue
     */
    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issues_id');
    }
}
