<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Location Model
 * 
 * Represents a location (Community or Suburb) in the constituency.
 * Communities can be parents of Suburbs.
 * 
 * @property int $id
 * @property string $name
 * @property string $type
 * @property int|null $parent_id
 */
class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'type',
        'parent_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Constants for types
    const TYPE_COMMUNITY = 'community';
    const TYPE_SUBURB = 'suburb';

    /**
     * Parent relationship (e.g., getting the community of a suburb)
     */
    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /**
     * Children relationship (e.g., getting all suburbs of a community)
     */
    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    /**
     * Scope to filter by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
