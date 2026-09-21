<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lpk extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'name',
        'scope',
        'address',
        'email',
        'phone',
        'status',
        'notes',
        'expired_at',
        'drive_url',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'date',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    public function isExpiringSoon(int $days = 90): bool
    {
        if (! $this->expired_at || $this->isExpired()) {
            return false;
        }

        return $this->expired_at->diffInDays(now()) <= $days;
    }

    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(Amendment::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
