<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ActivityLog Model
 * 
 * Tracks general user activities (create, update, delete, view)
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    public $timestamps = false; // Only has created_at
    const CREATED_AT = 'created_at';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'created_at'
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity (static helper)
     */
    public static function log(
        ?int $userId,
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'old_values' => $oldValues,
            'new_values' => $newValues
        ]);
    }

    /**
     * Cleanup old logs
     */
    public static function cleanupOld(int $days = 365): int
    {
        return static::where('created_at', '<', date('Y-m-d H:i:s', strtotime("-$days days")))->delete();
    }
}
