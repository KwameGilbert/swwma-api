<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IssueResourceAllocation Model
 * 
 * Represents resources (financial, human, material) allocated by admins to resolve an issue.
 */
class IssueResourceAllocation extends Model
{
    protected $table = 'issue_resource_allocations';

    protected $fillable = [
        'issues_id',
        'allocated_by',
        'amount',
        'personnel_items',
        'material_items',
        'additional_notes',
        'allocation_date'
    ];

    protected $casts = [
        'amount' => 'float',
        'personnel_items' => 'json',
        'material_items' => 'json',
        'allocation_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationship back to the issue
     */
    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issues_id');
    }

    /**
     * Relationship to the admin who allocated the resources
     */
    public function allocator()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
