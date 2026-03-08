<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RefreshToken Model
 */
class RefreshToken extends Model
{
    protected $table = 'refresh_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'device_name',
        'ip_address',
        'user_agent',
        'expires_at',
        'revoked_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return is_null($this->revoked_at) && $this->expires_at->isFuture();
    }

    public function revoke(): bool
    {
        return $this->update(['revoked_at' => date('Y-m-d H:i:s')]);
    }

    public static function revokeAllForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);
    }
}
