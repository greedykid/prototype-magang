<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LpkLocation extends Model
{
    use HasFactory;

    public const TYPE_HEAD_OFFICE = 'HEAD_OFFICE';
    public const TYPE_BRANCH_LAB = 'BRANCH_LAB';
    public const TYPE_SAMPLING_SITE = 'SAMPLING_SITE';
    public const TYPE_PERMANENT_FACILITY = 'PERMANENT_FACILITY';

    public const LOCATION_TYPES = [
        self::TYPE_BRANCH_LAB => 'Laboratorium Cabang',
        self::TYPE_HEAD_OFFICE => 'Laboratorium Utama (Kantor Pusat)',
    ];

    protected $fillable = [
        'lpk_id',
        'name',
        'location_type',
        'is_main',
        'address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'scope_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
        ];
    }

    public function lpk(): BelongsTo
    {
        return $this->belongsTo(Lpk::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'lpk_location_id');
    }

    public function latestAssessment(): ?Assessment
    {
        return $this->assessments()->orderByDesc('start_at')->first();
    }

    public function lastAssessedAt(): ?\Carbon\Carbon
    {
        return $this->assessments()->max('start_at') ? \Carbon\Carbon::parse($this->assessments()->max('start_at')) : null;
    }

    public function isAssessedInCycle(?CarbonInterface $cycleStart, ?CarbonInterface $cycleEnd): bool
    {
        if ($this->relationLoaded('assessments')) {
            if (! $cycleStart || ! $cycleEnd) {
                return $this->assessments->isNotEmpty();
            }

            return $this->assessments
                ->where('start_at', '>=', $cycleStart)
                ->where('start_at', '<=', $cycleEnd)
                ->isNotEmpty();
        }

        if (! $cycleStart || ! $cycleEnd) {
            return $this->assessments()->exists();
        }

        return $this->assessments()
            ->whereBetween('start_at', [$cycleStart, $cycleEnd])
            ->exists();
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->name;
    }

    public function getLocationTypeLabelAttribute(): string
    {
        if ($this->is_main) {
            return 'Laboratorium Utama';
        }

        return self::LOCATION_TYPES[$this->location_type] ?? 'Laboratorium Cabang';
    }
}
