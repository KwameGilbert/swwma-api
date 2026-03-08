<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    /**
     * Log a generic activity
     * 
     * @param int|null $userId User ID who performed the action
     * @param string $action Action performed (view, create, update, delete)
     * @param string|null $modelType Type of model (e.g. 'Award', 'Event')
     * @param int|null $modelId ID of the model instance
     * @param string|null $description Human-readable description
     * @param array|null $oldValues Array of old attributes (for updates/deletes)
     * @param array|null $newValues Array of new attributes (for creates/updates)
     * @return ActivityLog
     */
    public function log(
        ?int $userId,
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ActivityLog {
        return ActivityLog::log(
            $userId,
            $action,
            $modelType,
            $modelId,
            $description,
            $oldValues,
            $newValues
        );
    }

    /**
     * Helper for logging creates
     */
    public function logCreate(?int $userId, string $modelType, int $modelId, ?array $values = null, ?string $description = null): ActivityLog
    {
        return $this->log(
            $userId,
            'create',
            $modelType,
            $modelId,
            $description ?? "Created $modelType #$modelId",
            null,
            $values
        );
    }

    /**
     * Helper for logging updates
     */
    public function logUpdate(?int $userId, string $modelType, int $modelId, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): ActivityLog
    {
        return $this->log(
            $userId,
            'update',
            $modelType,
            $modelId,
            $description ?? "Updated $modelType #$modelId",
            $oldValues,
            $newValues
        );
    }

    /**
     * Helper for logging deletes
     */
    public function logDelete(?int $userId, string $modelType, int $modelId, ?array $oldValues = null, ?string $description = null): ActivityLog
    {
        return $this->log(
            $userId,
            'delete',
            $modelType,
            $modelId,
            $description ?? "Deleted $modelType #$modelId",
            $oldValues,
            null
        );
    }

    /**
     * Helper for logging views
     */
    public function logView(?int $userId, string $modelType, int $modelId, ?string $description = null): ActivityLog
    {
        return $this->log(
            $userId,
            'view',
            $modelType,
            $modelId,
            $description ?? "Viewed $modelType #$modelId"
        );
    }
}
