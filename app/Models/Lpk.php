<?php

namespace App\Models;

use App\Models\Assessment;
use App\Models\User;
use App\Services\LpkSurveillanceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $pic_id
 * @property string $registration_number
 * @property string $name
 * @property string|null $scope
 * @property Carbon|null $certificate_date
 * @property string|null $address
 * @property string|null $email
 * @property string|null $phone
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $expired_at
 * @property Carbon|null $last_surveillance_notified_at
 * @property string|null $drive_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $dynamic_status
 * @property-read string $dynamic_status_label
 * @property-read array $surveillance_milestones
 */
class Lpk extends Model
{
    use HasFactory;

    public const ACCREDITATION_TYPES = [
        'Laboratorium Penguji' => 'Laboratorium Penguji (LP)',
        'Laboratorium Kalibrasi' => 'Laboratorium Kalibrasi (LK)',
        'Laboratorium Medik' => 'Laboratorium Medik (LM)',
        'Lembaga Penyelenggara Uji Kemahiran' => 'Penyelenggara Uji Kemahiran (PUP)',
        'Produsen Bahan Acuan' => 'Produsen Bahan Acuan (PBA)',
        'Lembaga Inspeksi' => 'Lembaga Inspeksi (LI)',
        'Lembaga Sertifikasi Produk' => 'Lembaga Sertifikasi Produk (LSPr)',
        'Lembaga Sertifikasi Sistem Manajemen' => 'Lembaga Sertifikasi Sistem Manajemen (LSSM)',
    ];

    protected $fillable = [
        'pic_id',
        'no_reg',
        'accreditation_number',
        'accreditation_type',
        'registration_number',
        'name',
        'scope',
        'certificate_date',
        'address',
        'email',
        'phone',
        'status',
        'notes',
        'expired_at',
        'last_surveillance_notified_at',
        'drive_url',
    ];

    protected function casts(): array
    {
        return [
            'certificate_date' => 'date',
            'expired_at' => 'date',
            'last_surveillance_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Lpk $lpk): void {
            if (empty($lpk->no_reg)) {
                if (!empty($lpk->registration_number) && preg_match('/^\d+$/', (string) $lpk->registration_number)) {
                    $lpk->no_reg = (string) $lpk->registration_number;
                } else {
                    $maxNoReg = \Illuminate\Support\Facades\DB::table('lpks')->whereRaw('no_reg GLOB "[0-9]*"')->max(\Illuminate\Support\Facades\DB::raw('CAST(no_reg AS INTEGER)'));
                    $lpk->no_reg = (string) ($maxNoReg ? $maxNoReg + 1 : 3340);
                }
            }

            if (empty($lpk->accreditation_number) && !empty($lpk->registration_number)) {
                $lpk->accreditation_number = $lpk->registration_number;
            }

            if (empty($lpk->registration_number)) {
                $lpk->registration_number = $lpk->accreditation_number ?: $lpk->no_reg;
            }

            if (empty($lpk->accreditation_type)) {
                $lpk->accreditation_type = 'Laboratorium Penguji';
            }
        });

        static::created(function (Lpk $lpk): void {
            $lpk->generateSurveillanceAssessments();
        });

        static::updated(function (Lpk $lpk): void {
            if ($lpk->wasChanged(['certificate_date', 'expired_at'])) {
                $lpk->generateSurveillanceAssessments();
            }
        });
    }

    /**
     * Label masa akreditasi (format awal - akhir sertifikat atau tanda -).
     */
    public function getMasaAkreditasiLabelAttribute(): string
    {
        if ($this->certificate_date && $this->expired_at) {
            return $this->certificate_date->format('d M Y') . ' - ' . $this->expired_at->format('d M Y');
        } elseif ($this->expired_at) {
            return 's/d ' . $this->expired_at->format('d M Y');
        }

        return '-';
    }

    /**
     * Dapatkan asesmen yang paling relevan untuk status operasional LPK:
     * 1. Asesmen aktif/berjalan (IN_PROGRESS, TP aktif, atau sedang tahap EHA)
     * 2. Asesmen yang lewat jadwal (PLANNED/SCHEDULED dengan end_at < now)
     * 3. Asesmen terencana mendatang yang paling dekat dengan hari ini (start_at >= now)
     * 4. Asesmen terakhir yang selesai/riwayat
     */
    public function getActiveOrUpcomingAssessment(): ?Assessment
    {
        if ($this->relationLoaded('assessments')) {
            $assessments = $this->assessments;
            foreach ($assessments as $a) {
                if (! $a->relationLoaded('lpk')) {
                    $a->setRelation('lpk', $this);
                }
            }

            // 1. Sedang berlangsung, dicabut, dibekukan, TP aktif, tahap EHA berjalan
            $inProgress = $assessments
                ->filter(function (Assessment $a) {
                    return in_array($a->status, ['IN_PROGRESS', 'SUSPENDED', 'REVOKED'], true)
                        || in_array($a->tp_status, [Assessment::TP_STATUS_IN_PROGRESS, Assessment::TP_STATUS_UNDER_VERIFICATION], true)
                        || $a->is_tp_overdue
                        || (! empty($a->eha_status) && $a->eha_status !== Assessment::EHA_STATUS_BELUM && empty($a->sk_number));
                })
                ->sortBy('start_at')
                ->first(fn (Assessment $a) => ! $a->isPastSurveillanceForActiveLpk());

            if ($inProgress) {
                return $inProgress;
            }

            // 2. Lewat jadwal pelaksanaan dan belum selesai (kecuali yang otomatis terealisasi)
            $now = now();
            $overdue = $assessments
                ->filter(function (Assessment $a) use ($now) {
                    return in_array($a->status, ['PLANNED', 'SCHEDULED'], true)
                        && $a->end_at
                        && $a->end_at < $now;
                })
                ->sortBy('start_at')
                ->first(fn (Assessment $a) => ! $a->isPastSurveillanceForActiveLpk());

            if ($overdue) {
                return $overdue;
            }

            // 3. Asesmen mendatang terdekat (upcoming)
            $upcoming = $assessments
                ->filter(function (Assessment $a) use ($now) {
                    return in_array($a->status, ['PLANNED', 'SCHEDULED'], true)
                        && $a->start_at
                        && $a->start_at >= $now;
                })
                ->sortBy('start_at')
                ->first();

            if ($upcoming) {
                return $upcoming;
            }

            // 4. Asesmen terakhir
            return $assessments
                ->sortByDesc('start_at')
                ->first();
        }

        // 1. Sedang berlangsung, dicabut, dibekukan, TP aktif, tahap EHA berjalan
        $inProgress = $this->assessments()
            ->where(function ($q) {
                $q->whereIn('status', ['IN_PROGRESS', 'SUSPENDED', 'REVOKED'])
                    ->orWhereIn('tp_status', [Assessment::TP_STATUS_IN_PROGRESS, Assessment::TP_STATUS_UNDER_VERIFICATION])
                    ->orWhere(fn ($sub) => $sub->tpOverdue())
                    ->orWhere(function ($eq) {
                        $eq->whereNotNull('eha_status')
                            ->where('eha_status', '!=', Assessment::EHA_STATUS_BELUM)
                            ->whereNull('sk_number');
                    });
            })
            ->orderBy('start_at')
            ->get()
            ->first(fn (Assessment $a) => ! $a->isPastSurveillanceForActiveLpk());

        if ($inProgress) {
            return $inProgress;
        }

        // 2. Lewat jadwal pelaksanaan dan belum selesai (kecuali yang otomatis terealisasi)
        $overdue = $this->assessments()
            ->whereIn('status', ['PLANNED', 'SCHEDULED'])
            ->where('end_at', '<', now())
            ->orderBy('start_at')
            ->get()
            ->first(fn (Assessment $a) => ! $a->isPastSurveillanceForActiveLpk());

        if ($overdue) {
            return $overdue;
        }

        // 3. Asesmen mendatang terdekat (upcoming)
        $upcoming = $this->assessments()
            ->whereIn('status', ['PLANNED', 'SCHEDULED'])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->first();

        if ($upcoming) {
            return $upcoming;
        }

        // 4. Asesmen terakhir
        return $this->assessments()
            ->orderByDesc('start_at')
            ->first();
    }

    /**
     * Rangkuman status operasional otomatis berdasarkan asesmen, batas TP, dan siklus KAN.
     * Dibuat ringkas, simpel, dan secara tegas mencantumkan jenis proses dari ke-8 skema resmi.
     */
    public function getDynamicKeteranganAttribute(): string
    {
        return app(LpkSurveillanceService::class)->generateDynamicKeterangan($this);
    }

    /**
     * Otomatis membuat agenda asesmen surveilen (S1, S2) dan Re-Akreditasi (RA)
     * berdasarkan tanggal terbit sertifikat akreditasi LPK sesuai siklus KAN U-01.
     */
    public function generateSurveillanceAssessments(?int $creatorId = null): int
    {
        return app(LpkSurveillanceService::class)->generateAssessments($this, $creatorId);
    }

    public function getCycleBaseDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->expired_at ?: ($this->certificate_date ? $this->certificate_date->copy()->addYears(5) : null);
    }

    public function getCycleExpiredAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->cycle_base_date ? $this->cycle_base_date->copy()->addYears(5) : null;
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    public function isInGracePeriod(): bool
    {
        if (! $this->expired_at || ! $this->isExpired()) {
            return false;
        }

        $deadline = $this->grace_period_deadline;
        if (! $deadline) {
            return false;
        }

        return now()->lte($deadline);
    }

    public function isRevocationOverdue(): bool
    {
        if (! $this->expired_at || ! $this->isExpired()) {
            return false;
        }

        $deadline = $this->grace_period_deadline;
        if (! $deadline) {
            return false;
        }

        return now()->gt($deadline);
    }

    public function getGracePeriodDeadlineAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->expired_at ? $this->expired_at->copy()->addMonths(6) : null;
    }

    public function getDaysRemainingGracePeriodAttribute(): ?int
    {
        if (! $this->isInGracePeriod() || ! $this->grace_period_deadline) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->grace_period_deadline, false));
    }

    public function getLatestCompletedReaccreditation(): ?Assessment
    {
        $assessments = $this->relationLoaded('assessments') ? $this->assessments : $this->assessments()->get();

        return $assessments
            ->filter(function (Assessment $a) {
                $isRa = in_array($a->assessment_type, [Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT'], true)
                    || str_contains(strtolower($a->title ?: ''), 're-akreditasi')
                    || str_contains(strtolower($a->title ?: ''), 'reakreditasi');

                return $isRa && $a->status === 'COMPLETED' && ! empty($a->sk_number);
            })
            ->sortByDesc(fn (Assessment $a) => $a->sk_date ?: $a->end_at ?: $a->start_at)
            ->first();
    }

    public function getS1DelayPenaltyMonthsAttribute(): int
    {
        if (! $this->certificate_date) {
            return 0;
        }

        $latestRa = $this->getLatestCompletedReaccreditation();
        if (! $latestRa) {
            return 0;
        }

        $completedDate = $latestRa->sk_date
            ?: ($latestRa->end_at ? $latestRa->end_at->copy()->startOfDay() : null);

        if (! $completedDate) {
            return 0;
        }

        // Jika tanggal selesai RA berada setelah titik awal siklus baru (certificate_date)
        // dan masih dalam rentang toleransi masa tenggang 6 bulan dari siklus berjalan
        if ($completedDate->gt($this->certificate_date) && $completedDate->lte($this->certificate_date->copy()->addMonths(6))) {
            return (int) round($this->certificate_date->floatDiffInMonths($completedDate));
        }

        return 0;
    }

    public function getS1PrepRemainingMonthsAttribute(): int
    {
        $penalty = $this->s1_delay_penalty_months;
        return max(0, 15 - $penalty);
    }

    public function isExpiringSoon(int $days = 90): bool
    {
        if (! $this->expired_at || $this->isExpired()) {
            return false;
        }

        return $this->expired_at->isFuture() && $this->expired_at->lte(now()->addDays($days));
    }

    /**
     * Menghitung status dinamis akreditasi LPK berdasarkan kepatuhan siklus pengawasan KAN & masa berlaku sertifikat.
     * Mengembalikan:
     * - 'INACTIVE': Jika dinonaktifkan secara manual oleh admin
     * - 'REVOKED': Jika status akreditasi dicabut (lewat 1 tahun pembekuan)
     * - 'SUSPENDED': Jika dibekukan (lewat batas waktu / toleransi pengisian surveilen)
     * - 'EXPIRED': Jika masa berlaku sertifikat akreditasi telah habis
     * - 'SURVEILLANCE_OVERDUE': Jika telah melewati batas target jadwal surveilen tanpa agenda asesmen
     * - 'SURVEILLANCE_DUE': Jika berada dalam masa aktif notifikasi surveilen / re-akreditasi
     * - 'ACTIVE': Jika status aktif dan seluruh jadwal pengawasan aman / terpenuhi
     */
    public function getDynamicStatusAttribute(): string
    {
        return app(LpkSurveillanceService::class)->determineDynamicStatus($this);
    }

    public function getDynamicStatusLabelAttribute(): string
    {
        return app(LpkSurveillanceService::class)->determineDynamicStatusLabel($this);
    }

    public function getSurveillanceMilestonesAttribute(): array
    {
        return app(LpkSurveillanceService::class)->calculateMilestones($this);
    }

    public function getActiveSurveillanceAlerts(): array
    {
        return app(LpkSurveillanceService::class)->getActiveAlerts($this);
    }

    public function hasActiveSurveillanceAlert(): bool
    {
        return app(LpkSurveillanceService::class)->hasActiveAlert($this);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function isManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->pic_id === null || (int) $this->pic_id === (int) $user->id;
    }

    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Memperbarui masa akreditasi LPK ke siklus 5 tahun berikutnya
     * saat asesmen Re-Akreditasi telah selesai (COMPLETED) dan memiliki SK.
     */
    public function renewAccreditationCycleFromAssessment(Assessment $assessment): bool
    {
        return app(LpkSurveillanceService::class)->renewAccreditationCycle($this, $assessment);
    }

    public function syncAccreditationCycleWithCompletedReAccreditation(): bool
    {
        return app(LpkSurveillanceService::class)->syncAccreditationCycle($this);
    }
}
