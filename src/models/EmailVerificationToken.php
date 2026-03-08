<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EmailVerificationToken Model
 */
class EmailVerificationToken extends Model
{
    protected $table = 'email_verification_tokens';
    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'created_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public function markAsUsed(): bool
    {
        return $this->update(['used_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Create a new token for a user and return the plain text token
     */
    public static function createWithPlainToken(User $user, int $expiryHours = 24): array
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $tokenRecord = self::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', time() + ($expiryHours * 3600)),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'token' => $tokenRecord,
            'plainToken' => $plainToken
        ];
    }

    /**
     * Find by plain text token
     */
    public static function findByToken(string $plainToken): ?self
    {
        $tokenHash = hash('sha256', $plainToken);
        return self::where('token_hash', $tokenHash)->first();
    }
}
