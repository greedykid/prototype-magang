<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PIC = 'pic';

    // Aliases untuk kompatibilitas ke belakang
    public const ROLE_STAFF = 'admin';
    public const ROLE_ASSESSOR = 'pic';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPic(): bool
    {
        return $this->role === self::ROLE_PIC;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAssessor(): bool
    {
        return $this->role === self::ROLE_PIC;
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin Unit Akreditasi Lab',
            self::ROLE_PIC => 'PIC Laboratorium',
            default => ucfirst((string) ($this->role ?? 'Pengguna')),
        };
    }

    public function getRoleShortLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin Unit Lab',
            self::ROLE_PIC => 'PIC Lab',
            default => ucfirst((string) ($this->role ?? 'Pengguna')),
        };
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'badge-role-admin',
            self::ROLE_PIC => 'badge-role-pic',
            default => 'badge-role-default',
        };
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'created_by');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(IssueFollowup::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(Amendment::class, 'created_by');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'created_by');
    }

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
        ];
    }
}
