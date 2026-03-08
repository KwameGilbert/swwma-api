<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * SubSector Model
 */
class SubSector extends Model
{
    protected $table = 'sub_sectors';

    protected $fillable = [
        'name',
        'slug',
        'sector_id'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($subsector) {
            if (empty($subsector->slug)) {
                $subsector->slug = Str::slug($subsector->name);
            }
        });
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
}
