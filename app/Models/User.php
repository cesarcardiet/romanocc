<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserType;
use App\Enums\UserStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'accepted_terms',
        'type',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'accepted_terms' => 'boolean',
            'type' => UserType::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Determina si el usuario puede acceder al panel de Filament.
     * Cualquier usuario activo puede acceder (evita 403 en instalaciones nuevas).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Si no tiene status (ej. usuario creado antes de agregar el campo), permitir acceso
        if ($this->status === null) {
            return true;
        }
        return $this->isActive();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->type === UserType::ADMIN;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Get user type label
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    /**
     * Get user status label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Get user type color
     */
    public function getTypeColorAttribute(): string
    {
        return $this->type->color();
    }

    /**
     * Get user status color
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    /**
     * Get user status icon
     */
    public function getStatusIconAttribute(): string
    {
        return $this->status->icon();
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the FCM tokens for the user.
     */
    public function fcmTokens()
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function activeFcmTokens()
    {
        return $this->hasMany(UserFcmToken::class)
            ->where('is_active', true);
    }

    /**
     * Get active FCM tokens for the user.
     */
    public function getActiveFcmTokens()
    {
        return $this->fcmTokens()
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->toArray();
    }
}
