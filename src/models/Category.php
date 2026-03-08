<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Category Model
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }
}
