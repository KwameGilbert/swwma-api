<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IssueReport Model
 * 
 * Represents a community issue report submitted by residents, agents, or officers.
 * Follows workflow: Submit → Review → Task Force Assessment → Resource Allocation → Resolution
 *
 * @property int $id
 * @property string $case_id
 * @property string $title
 * @property string $description
 * @property string|null $category
 * @property string $location
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array|null $images
 * @property string|null $reporter_name
 * @property string|null $reporter_email
 * @property string|null $reporter_phone
 * @property int|null $submitted_by_agent_id
 * @property int|null $submitted_by_officer_id
 * @property int|null $assigned_officer_id
 * @property int|null $assigned_task_force_id
 * @property string $status
 * @property string $priority
 * @property float|null $allocated_budget
 * @property array|null $allocated_resources
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property int|null $acknowledged_by
 * @property \Illuminate\Support\Carbon|null $forwarded_to_admin_at
 * @property \Illuminate\Support\Carbon|null $assigned_to_task_force_at
 * @property \Illuminate\Support\Carbon|null $resources_allocated_at
 * @property int|null $resources_allocated_by
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class IssueReport extends Model
{
    protected $table = 'issue_reports';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    // Status Constants - Updated Workflow
    const STATUS_SUBMITTED = 'submitted';                       // Initial submission
    const STATUS_UNDER_OFFICER_REVIEW = 'under_officer_review'; // Agent's report being reviewed by officer
    const STATUS_FORWARDED_TO_ADMIN = 'forwarded_to_admin';     // Sent to admin for action
    const STATUS_ASSIGNED_TO_TASK_FORCE = 'assigned_to_task_force'; // Admin assigned to task force
    const STATUS_ASSESSMENT_IN_PROGRESS = 'assessment_in_progress'; // Task force investigating
    const STATUS_ASSESSMENT_SUBMITTED = 'assessment_submitted';     // Assessment report submitted
    const STATUS_RESOURCES_ALLOCATED = 'resources_allocated';       // Admin allocated resources
    const STATUS_RESOLUTION_IN_PROGRESS = 'resolution_in_progress'; // Task force fixing issue
    const STATUS_RESOLUTION_SUBMITTED = 'resolution_submitted';     // Resolution report submitted
    const STATUS_RESOLVED = 'resolved';                             // Issue marked as resolved
    const STATUS_CLOSED = 'closed';                                 // Issue closed
    const STATUS_REJECTED = 'rejected';                             // Issue rejected

    // Priority Constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Source constants
    const SOURCE_PUBLIC = 'public';
    const SOURCE_AGENT = 'agent';
    const SOURCE_OFFICER = 'officer';

    protected $fillable = [
        'case_id',
        'user_id',
        'title',
        'description',
        'category',
        'location',
        'specific_location',
        'latitude',
        'longitude',
        'images',
        // Classification fields (NEW)
        'sector_id',
        'sub_sector_id',
        'issue_type',
        'affected_people_count',
        'estimated_budget',
        // Location hierarchy (NEW)
        'main_community_id',
        'smaller_community_id',
        'suburb_id',
        'cottage_id',
        // Constituent information (RENAMED from reporter_*)
        'constituent_name',
        'constituent_email',
        'constituent_contact',
        'constituent_gender',
        'constituent_address',
        // Legacy fields for backward compatibility
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'reporter_gender',
        'reporter_address',
        'additional_notes',
        'submitted_by_agent_id',
        'submitted_by_officer_id',
        'assigned_officer_id',
        'assigned_agent_id',
        'assigned_task_force_id',
        'status',
        'priority',
        'allocated_budget',
        'allocated_resources',
        'resolution_notes',
        'acknowledged_at',
        'acknowledged_by',
        'forwarded_to_admin_at',
        'assigned_to_task_force_at',
        'resources_allocated_at',
        'resources_allocated_by',
        'resolved_at',
        'resolved_by',
        // Review tracking (NEW)
        'reviewed_by_officer_id',
        'reviewed_at',
        'assessment_reviewed_by',
        'assessment_reviewed_at',
        'assessment_decision',
    ];

    protected $casts = [
        'images' => 'array',
        'allocated_resources' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'allocated_budget' => 'decimal:2',
        'estimated_budget' => 'decimal:2',
        'longitude' => 'decimal:8',
        'allocated_budget' => 'decimal:2',
        // IDs
        'sector_id' => 'integer',
        'sub_sector_id' => 'integer',
        'affected_people_count' => 'integer',
        'main_community_id' => 'integer',
        'smaller_community_id' => 'integer',
        'suburb_id' => 'integer',
        'cottage_id' => 'integer',
        'submitted_by_agent_id' => 'integer',
        'submitted_by_officer_id' => 'integer',
        'assigned_officer_id' => 'integer',
        'assigned_task_force_id' => 'integer',
        'acknowledged_by' => 'integer',
        'resources_allocated_by' => 'integer',
        'resolved_by' => 'integer',
        'reviewed_by_officer_id' => 'integer',
        'assessment_reviewed_by' => 'integer',
        // Timestamps
        'acknowledged_at' => 'datetime',
        'forwarded_to_admin_at' => 'datetime',
        'assigned_to_task_force_at' => 'datetime',
        'resources_allocated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'assessment_reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    public function submittedByAgent()
    {
        return $this->belongsTo(Agent::class, 'submitted_by_agent_id');
    }

    public function submittedByOfficer()
    {
        return $this->belongsTo(Officer::class, 'submitted_by_officer_id');
    }

    public function assignedOfficer()
    {
        return $this->belongsTo(Officer::class, 'assigned_officer_id');
    }

    public function assignedAgent()
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_id');
    }

    public function assignedTaskForce()
    {
        return $this->belongsTo(TaskForce::class, 'assigned_task_force_id');
    }

    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function resourcesAllocatedByUser()
    {
        return $this->belongsTo(User::class, 'resources_allocated_by');
    }

    public function comments()
    {
        return $this->hasMany(IssueReportComment::class, 'issue_report_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(IssueReportStatusHistory::class, 'issue_report_id');
    }

    public function assessmentReport()
    {
        return $this->hasOne(IssueAssessmentReport::class, 'issue_report_id');
    }

    public function resolutionReport()
    {
        return $this->hasOne(IssueResolutionReport::class, 'issue_report_id');
    }

    // NEW relationships for classification and location
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function subSector()
    {
        return $this->belongsTo(SubSector::class);
    }

    public function mainCommunity()
    {
        return $this->belongsTo(Location::class, 'main_community_id');
    }

    public function smallerCommunity()
    {
        return $this->belongsTo(Location::class, 'smaller_community_id');
    }

    public function suburb()
    {
        return $this->belongsTo(Location::class, 'suburb_id');
    }

    public function cottage()
    {
        return $this->belongsTo(Location::class, 'cottage_id');
    }

    public function reviewedByOfficer()
    {
        return $this->belongsTo(Officer::class, 'reviewed_by_officer_id');
    }

    public function assessmentReviewedByUser()
    {
        return $this->belongsTo(User::class, 'assessment_reviewed_by');
    }

    /* -----------------------------------------------------------------
     |  Static Methods
     | -----------------------------------------------------------------
     */

    public static function generateCaseId(): string
    {
        $prefix = 'ISS';
        $date = date('Ymd');
        $random = strtoupper(substr(md5((string)mt_rand()), 0, 4));
        return sprintf('%s-%s-%s', $prefix, $date, $random);
    }

    public static function findByCaseId(string $caseId): ?IssueReport
    {
        return static::where('case_id', $caseId)->first();
    }

    /* -----------------------------------------------------------------
     |  Query Scopes
     | -----------------------------------------------------------------
     */

    public function scopePending($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_REJECTED]);
    }

    public function scopeAwaitingAdminAction($query)
    {
        return $query->where('status', self::STATUS_FORWARDED_TO_ADMIN);
    }

    public function scopeAwaitingTaskForceAssessment($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ASSIGNED_TO_TASK_FORCE,
            self::STATUS_ASSESSMENT_IN_PROGRESS
        ]);
    }

    public function scopeAwaitingResolution($query)
    {
        return $query->whereIn('status', [
            self::STATUS_RESOURCES_ALLOCATED,
            self::STATUS_RESOLUTION_IN_PROGRESS
        ]);
    }

    public function scopeByTaskForce($query, int $taskForceId)
    {
        return $query->where('assigned_task_force_id', $taskForceId);
    }

    /* -----------------------------------------------------------------
     |  Workflow Methods
     | -----------------------------------------------------------------
     */

    /**
     * Get the source of the issue report
     */
    public function getSource(): string
    {
        if ($this->submitted_by_agent_id) return self::SOURCE_AGENT;
        if ($this->submitted_by_officer_id) return self::SOURCE_OFFICER;
        return self::SOURCE_PUBLIC;
    }

    /**
     * Officer acknowledges and reviews agent's report
     */
    public function acknowledgeByOfficer(int $officerId): bool
    {
        return $this->update([
            'status' => self::STATUS_UNDER_OFFICER_REVIEW,
            'assigned_officer_id' => $officerId,
            'acknowledged_at' => date('Y-m-d H:i:s'),
            'acknowledged_by' => $officerId,
        ]);
    }

    /**
     * Forward to admin for action
     */
    public function forwardToAdmin(): bool
    {
        return $this->update([
            'status' => self::STATUS_FORWARDED_TO_ADMIN,
            'forwarded_to_admin_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Admin assigns to task force
     */
    public function assignToTaskForce(int $taskForceId): bool
    {
        return $this->update([
            'status' => self::STATUS_ASSIGNED_TO_TASK_FORCE,
            'assigned_task_force_id' => $taskForceId,
            'assigned_to_task_force_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Task force starts assessment
     */
    public function startAssessment(): bool
    {
        return $this->update(['status' => self::STATUS_ASSESSMENT_IN_PROGRESS]);
    }

    /**
     * Mark assessment as submitted
     */
    public function markAssessmentSubmitted(): bool
    {
        return $this->update(['status' => self::STATUS_ASSESSMENT_SUBMITTED]);
    }

    /**
     * Admin allocates resources
     */
    public function allocateResources(int $adminId, float $budget, ?array $resources = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOURCES_ALLOCATED,
            'allocated_budget' => $budget,
            'allocated_resources' => $resources,
            'resources_allocated_at' => date('Y-m-d H:i:s'),
            'resources_allocated_by' => $adminId,
        ]);
    }

    /**
     * Task force starts resolution work
     */
    public function startResolution(): bool
    {
        return $this->update(['status' => self::STATUS_RESOLUTION_IN_PROGRESS]);
    }

    /**
     * Mark resolution report as submitted
     */
    public function markResolutionSubmitted(): bool
    {
        return $this->update(['status' => self::STATUS_RESOLUTION_SUBMITTED]);
    }

    /**
     * Admin marks as resolved
     */
    public function resolve(int $adminId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => $adminId,
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Close the issue
     */
    public function close(): bool
    {
        return $this->update(['status' => self::STATUS_CLOSED]);
    }

    /**
     * Reject the issue
     */
    public function reject(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'resolution_notes' => $reason,
        ]);
    }

    /* -----------------------------------------------------------------
     |  Helper Methods
     | -----------------------------------------------------------------
     */

    public function isPending(): bool
    {
        return !in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_REJECTED]);
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isAwaitingAssessment(): bool
    {
        return in_array($this->status, [
            self::STATUS_ASSIGNED_TO_TASK_FORCE,
            self::STATUS_ASSESSMENT_IN_PROGRESS
        ]);
    }

    public function isAwaitingResolution(): bool
    {
        return in_array($this->status, [
            self::STATUS_RESOURCES_ALLOCATED,
            self::STATUS_RESOLUTION_IN_PROGRESS
        ]);
    }

    public function hasAssessmentReport(): bool
    {
        return $this->assessmentReport()->exists();
    }

    public function hasResolutionReport(): bool
    {
        return $this->resolutionReport()->exists();
    }

    public function toPublicArray(): array
    {
        $sectorName = $this->sector?->name ?: $this->getAttribute('sector');
        $subSectorName = $this->subSector?->name ?: $this->getAttribute('subsector');
        $mainCommunityName = $this->mainCommunity?->name ?: $this->getAttribute('location');
        $smallerCommunityName = $this->smallerCommunity?->name ?: $this->getAttribute('smaller_community');
        $suburbName = $this->suburb?->name ?: $this->getAttribute('suburb');
        $cottageName = $this->cottage?->name ?: $this->getAttribute('cottage');

        return [
            'id' => $this->id,
            'case_id' => $this->case_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->getAttribute('type') ?? $this->issue_type,
            'issue_type' => $this->issue_type ?? $this->getAttribute('type'),
            'sector' => $sectorName,
            'subsector' => $subSectorName,
            'people_affected' => $this->affected_people_count,
            'estimated_budget' => $this->estimated_budget ? (float)$this->estimated_budget : null,
            'additional_notes' => $this->getAttribute('additional_notes'),
            'location' => $mainCommunityName ?: $this->location,
            'community' => $mainCommunityName ?: $this->location,
            'smaller_community' => $smallerCommunityName,
            'suburb' => $suburbName,
            'cottage' => $cottageName,
            'specific_location' => $this->getAttribute('specific_location'),
            'images' => $this->images,
            'reporter_name' => $this->reporter_name ?: $this->constituent_name,
            'reporter_phone' => $this->reporter_phone ?: $this->constituent_contact,
            'reporter_email' => $this->reporter_email ?: $this->constituent_email,
            'reporter_gender' => $this->constituent_gender ?: $this->getAttribute('reporter_gender'),
            'reporter_address' => $this->constituent_address ?: $this->getAttribute('reporter_address'),
            'status' => $this->status,
            'priority' => $this->priority,
            'acknowledged_at' => $this->acknowledged_at?->toDateTimeString(),
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    public function toFullArray(): array
    {
        $data = $this->toPublicArray();
        $data['source'] = $this->getSource();
        $data['allocated_budget'] = $this->allocated_budget;
        $data['allocated_resources'] = $this->allocated_resources;
        $data['forwarded_to_admin_at'] = $this->forwarded_to_admin_at?->toDateTimeString();
        $data['assigned_to_task_force_at'] = $this->assigned_to_task_force_at?->toDateTimeString();
        $data['resources_allocated_at'] = $this->resources_allocated_at?->toDateTimeString();
        
        if ($this->assignedOfficer) {
            $data['assigned_officer'] = [
                'id' => $this->assignedOfficer->id,
                'user' => [
                    'name' => $this->assignedOfficer->user?->name,
                    'email' => $this->assignedOfficer->user?->email,
                ]
            ];
        }

        if ($this->assignedTaskForce) {
            $data['assigned_task_force'] = $this->assignedTaskForce->getPublicProfile();
        }
        
        if ($this->hasAssessmentReport()) {
            $data['assessment_report'] = $this->assessmentReport->toPublicArray();
        }
        
        if ($this->hasResolutionReport()) {
            $data['resolution_report'] = $this->resolutionReport->toPublicArray();
        }
        
        return $data;
    }
}
