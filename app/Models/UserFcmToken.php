<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the FCM token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Deactivate all tokens for a user.
     */
    public static function deactivateUserTokens($userId)
    {
        return static::where('user_id', $userId)->update(['is_active' => false]);
    }

    /**
     * Desactiva un token concreto (p. ej. tras SenderId mismatch en FCM).
     */
    public static function deactivateByToken(string $fcmToken): int
    {
        return static::where('fcm_token', $fcmToken)->update(['is_active' => false]);
    }

    /**
     * Get active tokens for a user.
     */
    public static function getActiveTokens($userId)
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->toArray();
    }
}
