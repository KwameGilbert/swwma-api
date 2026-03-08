<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Sector Model
 */
class Sector extends Model
{
    protected $table = 'sectors';

    protected $fillable = [
        'name',
        'slug',
        'category_id'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($sector) {
            if (empty($sector->slug)) {
                $sector->slug = Str::slug($sector->name);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subsectors()
    {
        return $this->hasMany(SubSector::class);
    }
}
