<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Issue Model
 * 
 * Represents a problem or request reported within the constituency.
 */
class Issue extends Model
{
    protected $table = 'issues';

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'sector_id',
        'sub_sector_id',
        'community_id',
        'suburb_id',
        'specific_location',
        'issue_type',
        'people_affected',
        'estimated_budget',
        'status',
        'priority',
        'details',
        'images',
        'constituent_id',
        'agent_id',
        'officer_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'images' => 'array',
        'people_affected' => 'integer',
        'estimated_budget' => 'float'
    ];

    /**
     * Appends for JSON serialization
     */
    protected $appends = ['category_name', 'location_display'];

    // Status Constants
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_ASSESSMENT_IN_PROGRESS = 'assessment_in_progress';
    const STATUS_ASSESSMENT_SUBMITTED = 'assessment_submitted';
    const STATUS_RESOLUTION_IN_PROGRESS = 'resolution_in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_NEEDS_REVISION = 'needs_revision';

    /**
     * Relationship to the reporting constituent
     */
    public function constituent()
    {
        return $this->belongsTo(Constituent::class);
    }

    /**
     * Relationship to the category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship to the sector
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Relationship to the sub-sector
     */
    public function subsector()
    {
        return $this->belongsTo(SubSector::class, 'sub_sector_id');
    }

    /**
     * Relationship to the community (Location)
     */
    public function community()
    {
        return $this->belongsTo(Location::class, 'community_id');
    }

    /**
     * Relationship to the suburb (Location)
     */
    public function suburb()
    {
        return $this->belongsTo(Location::class, 'suburb_id');
    }

    /**
     * Relationship to the agent involved (as creator or assignee)
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Relationship to the officer involved (as creator or reviewer)
     */
    public function officer()
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    // --- Accessors for Frontend ---

    public function getCategoryNameAttribute()
    {
        return $this->category->name ?? 'Other';
    }

    public function getLocationDisplayAttribute()
    {
        $loc = $this->community->name ?? 'Unknown';
        if ($this->suburb) {
            $loc .= " ({$this->suburb->name})";
        }
        return $loc;
    }
}
