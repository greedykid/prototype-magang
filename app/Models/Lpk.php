<?php

namespace App\Models;

use App\Models\Assessment;
use App\Models\User;
use App\Services\LpkSurveillanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public const MEMBER_ROLE_LEAD = 'lead';
    public const MEMBER_ROLE_VIEWER = 'viewer';

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

    /**
     * Cache internal memori per siklus request untuk kalkulasi milestone surveilen.
     */
    protected ?array $memoizedMilestones = null;

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
            if ($lpk->no_reg !== null && trim((string) $lpk->no_reg) === '') {
                $lpk->no_reg = null;
            }

            if ($lpk->accreditation_number !== null && trim((string) $lpk->accreditation_number) === '') {
                $lpk->accreditation_number = null;
            }

            if (empty($lpk->registration_number)) {
                $lpk->registration_number = $lpk->accreditation_number ?: ($lpk->no_reg ?: null);
            }

            if (empty($lpk->accreditation_type)) {
                $lpk->accreditation_type = 'Laboratorium Penguji';
            }
        });

        static::saved(function (Lpk $lpk): void {
            $lpk->memoizedMilestones = null;
            \App\Services\LpkSurveillanceService::invalidateAlertsCache($lpk);
        });

        static::created(function (Lpk $lpk): void {
            $lpk->ensureMainLocation();
        });

        static::updated(function (Lpk $lpk): void {
            if ($lpk->wasChanged(['address', 'phone', 'email'])) {
                $main = $lpk->mainLocation();
                if ($main && $main->is_main) {
                    $main->update([
                        'address' => $lpk->address ?: $main->address,
                        'contact_phone' => $lpk->phone ?: $main->contact_phone,
                        'contact_email' => $lpk->email ?: $main->contact_email,
                    ]);
                }
            }
        });

        static::deleting(function (Lpk $lpk): void {
            $lpk->calendarEvents()->delete();
            $lpk->assessments()->delete();
            $lpk->locations()->delete();
            $lpk->members()->detach();
        });

        static::deleted(function (Lpk $lpk): void {
            \App\Services\LpkSurveillanceService::invalidateAlertsCache($lpk);
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
        if (! $this->relationLoaded('assessments')) {
            $this->load(['assessments' => function ($query) {
                $query->orderBy('start_at');
            }]);
        }

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
        return $this->certificate_date ?: ($this->expired_at ? $this->expired_at->copy()->subYears(5) : null);
    }

    public function getCycleExpiredAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->expired_at ?: ($this->certificate_date ? $this->certificate_date->copy()->addYears(5) : null);
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->copy()->endOfDay()->isPast();
    }

    /**
     * Memeriksa apakah LPK memiliki proses akreditasi ulang yang sedang aktif atau telah terlaksana
     * sebelum berakhirnya siklus akreditasi.
     */
    public function hasReaccreditationInFlight(): bool
    {
        $assessments = $this->relationLoaded('assessments') ? $this->assessments : $this->assessments()->get();
        $cycleStart = $this->certificate_date ?: ($this->expired_at ? $this->expired_at->copy()->subYears(5) : null);

        return $assessments->contains(function (Assessment $a) use ($cycleStart) {
            $isRa = in_array($a->assessment_type, [Assessment::TYPE_RE_AKREDITASI, 'RA', 'Re-Akreditasi', 'Re-Akreditasi (RA)', 'REAKREDITASI', 'REASSESSMENT', 'Re-asesmen', 'Akreditasi Ulang'], true)
                || str_contains(strtolower($a->title ?: ''), 're-akreditasi')
                || str_contains(strtolower($a->title ?: ''), 'reakreditasi');

            if (! $isRa) {
                return false;
            }

            // Asesmen harus berada di dalam siklus berjalan
            if ($cycleStart && $a->start_at && $a->start_at->lt($cycleStart->copy()->subMonths(6))) {
                return false;
            }

            // Jika SK Akreditasi baru sudah terbit, maka asesmen sudah tuntas dan bukan lagi in-flight
            if (! empty($a->sk_number)) {
                return false;
            }

            // Memenuhi syarat jika status asesmen SCHEDULED, IN_PROGRESS, atau COMPLETED (menunggu keputusan),
            // atau terdapat proses tindakan perbaikan (TP) aktif / pemenuhan TP, atau proses EHA berjalan.
            return in_array($a->status, ['IN_PROGRESS', 'SCHEDULED', 'COMPLETED'], true)
                || in_array($a->tp_status, [Assessment::TP_STATUS_IN_PROGRESS, Assessment::TP_STATUS_UNDER_VERIFICATION, Assessment::TP_STATUS_SATISFIED], true)
                || (! empty($a->eha_status) && $a->eha_status !== Assessment::EHA_STATUS_BELUM);
        });
    }

    /**
     * Masa tenggang akreditasi ulang (6 bulan sejak berakhirnya siklus).
     * Hanya berlaku jika asesmen akreditasi ulang sudah dilaksanakan sebelum berakhirnya siklus.
     */
    public function isInGracePeriod(): bool
    {
        if (! $this->expired_at || ! $this->isExpired()) {
            return false;
        }

        // Jika sampai berakhirnya siklus belum dilaksanakan asesmen akreditasi ulang,
        // akreditasi dicabut (tidak mendapatkan masa tenggang).
        if (! $this->hasReaccreditationInFlight()) {
            return false;
        }

        $deadline = $this->grace_period_deadline;
        if (! $deadline) {
            return false;
        }

        return now()->lte($deadline);
    }

    /**
     * Memeriksa apakah akreditasi harus dicabut:
     * 1. Langsung dicabut jika siklus berakhir tanpa ada pelaksanaan asesmen RA.
     * 2. Dicabut jika lewat dari 6 bulan masa tenggang tanpa keputusan akreditasi baru.
     */
    public function isRevocationOverdue(): bool
    {
        if (! $this->expired_at || ! $this->isExpired()) {
            return false;
        }

        // Tanpa asesmen RA in-flight, dicabut seketika saat expired_at terlampaui.
        if (! $this->hasReaccreditationInFlight()) {
            return true;
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
        if (! $this->relationLoaded('assessments')) {
            return $this->assessments()
                ->where('status', 'COMPLETED')
                ->whereNotNull('sk_number')
                ->where(function ($q) {
                    $q->whereIn('assessment_type', [Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT'])
                        ->orWhere('title', 'like', '%re-akreditasi%')
                        ->orWhere('title', 'like', '%reakreditasi%');
                })
                ->orderByRaw('COALESCE(sk_date, end_at, start_at) DESC')
                ->first();
        }

        return $this->assessments
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

    public function hasMemoizedMilestones(): bool
    {
        return $this->memoizedMilestones !== null;
    }

    public function getMemoizedMilestones(): ?array
    {
        return $this->memoizedMilestones;
    }

    public function setMemoizedMilestones(?array $milestones): self
    {
        $this->memoizedMilestones = $milestones;
        return $this;
    }

    public function refresh()
    {
        $this->memoizedMilestones = null;
        return parent::refresh();
    }

    public function unsetRelation($relation)
    {
        if ($relation === 'assessments') {
            $this->memoizedMilestones = null;
        }

        return parent::unsetRelation($relation);
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

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lpk_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isLeadPic(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->pic_id !== null && (int) $this->pic_id === (int) $user->id) {
            return true;
        }

        if ($this->relationLoaded('members')) {
            $member = $this->members->firstWhere('id', $user->id);
            return $member && $member->pivot?->role === 'lead';
        }

        return $this->members()->where('lpk_members.user_id', $user->id)->where('lpk_members.role', 'lead')->exists();
    }

    public function isViewerPic(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isLeadPic($user)) {
            return false;
        }

        if ($this->pic_id !== null && $user->isViewerFor((int) $this->pic_id)) {
            return true;
        }

        if ($this->relationLoaded('members')) {
            $member = $this->members->firstWhere('id', $user->id);
            return $member && $member->pivot?->role === 'viewer';
        }

        return $this->members()->where('lpk_members.user_id', $user->id)->where('lpk_members.role', 'viewer')->exists();
    }

    public function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->isLeadPic($user);
    }

    /**
     * Wewenang menghapus data LPK:
     * - Jika LPK memiliki PIC pengelola: Hanya PIC pengelola bersangkutan yang berhak menghapus.
     * - Jika LPK belum memiliki PIC (unassigned): Hanya Admin yang berhak menghapus.
     */
    public function canDelete(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->pic_id !== null && (int) $this->pic_id === (int) $user->id;
    }

    public function isManagedBy(?User $user): bool
    {
        return $this->canManage($user);
    }


    public function canView(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->pic_id !== null && (int) $this->pic_id === (int) $user->id) {
            return true;
        }

        if ($this->pic_id !== null && $user->isViewerFor((int) $this->pic_id)) {
            return true;
        }

        if ($this->relationLoaded('members')) {
            return $this->members->contains('id', $user->id);
        }

        return $this->members()->where('lpk_members.user_id', $user->id)->exists();
    }

    public function isPrimaryFor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->pic_id !== null && (int) $this->pic_id === (int) $user->id) {
            return true;
        }

        if ($this->relationLoaded('members')) {
            return $this->members->contains('id', $user->id);
        }

        return $this->members()->where('lpk_members.user_id', $user->id)->exists();
    }

    public function scopePrimaryFor(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('lpks.pic_id', $user->id)
              ->orWhereHas('members', fn ($mq) => $mq->where('lpk_members.user_id', $user->id));
        })->distinct();
    }

    public function scopeManageableBy(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('lpks.pic_id', $user->id)
              ->orWhereHas('members', fn ($mq) => $mq->where('lpk_members.user_id', $user->id)->where('lpk_members.role', 'lead'));
        })->distinct();
    }

    public function scopeAccessibleBy(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('lpks.pic_id', $user->id)
              ->orWhereIn('lpks.pic_id', function ($sub) use ($user) {
                  $sub->select('user_id')
                      ->from('user_account_links')
                      ->where('viewer_id', $user->id);
              })
              ->orWhereHas('members', fn ($mq) => $mq->where('lpk_members.user_id', $user->id));
        })->distinct();
    }

    /**
     * Scope untuk menyaring LPK yang boleh dihapus oleh user:
     * - Admin: hanya boleh menghapus LPK yang belum ada PIC (unassigned) atau ditugaskan ke dirinya sendiri.
     * - PIC: hanya boleh menghapus LPK yang ditugaskan ke dirinya sendiri.
     */
    public function scopeDeletableBy(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('lpks.pic_id', $user->id);
    }

    /**
     * Scope untuk menyaring data LPK berdasarkan status dinamis operasional
     * dan status surveilen secara terpadu tanpa duplikasi kode antar-controller.
     */
    public function scopeFilterDynamicStatusAndSurveillance(Builder $query, ?string $status, ?string $surveillance): Builder
    {
        $needsDynamicStatusFilter = in_array($status, [
            'GRACE_PERIOD', 'REVOKED', 'SUSPENDED', 'SURVEILLANCE_OVERDUE', 'SURVEILLANCE_DUE', 'EXPIRED', 'ACTIVE',
        ], true);
        $needsSurveillanceFilter = ! empty($surveillance);

        if (! $needsDynamicStatusFilter && ! $needsSurveillanceFilter) {
            return $query;
        }

        $candidateQuery = (clone $query)
            ->select(['lpks.id', 'lpks.status', 'lpks.certificate_date', 'lpks.expired_at', 'lpks.last_surveillance_notified_at'])
            ->with(['assessments' => function ($q) {
                $q->select([
                    'id', 'lpk_id', 'title', 'assessment_type', 'start_at', 'end_at',
                    'status', 'tp_status', 'tp_due_date', 'tp_has_extension', 'tp_extension_months',
                    'tp_extension_date', 'tp_satisfied_at', 'submission_due_date',
                    'report_date', 'eha_date', 'eha_status', 'sk_number', 'sk_date', 'created_at',
                ]);
            }]);

        // Pre-filter level database untuk memangkas footprint RAM dan jumlah kandidat
        if ($status === 'EXPIRED') {
            $candidateQuery->whereNotNull('expired_at')->where('expired_at', '<', now());
        } elseif ($status === 'ACTIVE') {
            $candidateQuery->where('status', '!=', 'INACTIVE')
                ->where(fn ($q) => $q->whereNull('expired_at')->orWhere('expired_at', '>=', now()));
        } elseif ($status === 'GRACE_PERIOD') {
            $candidateQuery->whereNotNull('expired_at')
                ->where('expired_at', '<', now())
                ->where('expired_at', '>=', now()->subMonths(6));
        } elseif ($status === 'REVOKED') {
            $candidateQuery->where(function ($q) {
                $q->whereIn('status', ['REVOKED', 'DICABUT'])
                    ->orWhere('expired_at', '<', now()->subMonths(6))
                    ->orWhereHas('assessments', fn ($aq) => $aq->where('status', 'REVOKED'));
            });
        } elseif ($status === 'SUSPENDED') {
            $candidateQuery->where(function ($q) {
                $q->where('status', 'SUSPENDED')
                    ->orWhereNotNull('certificate_date')
                    ->orWhereHas('assessments', fn ($aq) => $aq->where('status', 'SUSPENDED')->orWhere('tp_status', 'IN_PROGRESS'));
            });
        } elseif (in_array($status, ['SURVEILLANCE_OVERDUE', 'SURVEILLANCE_DUE'], true)) {
            $candidateQuery->whereNotNull('certificate_date')->where('status', '!=', 'INACTIVE');
        }

        if (in_array($surveillance, ['DUE_S1', 'DUE_S2', 'OVERDUE'], true)) {
            $candidateQuery->where('status', '!=', 'INACTIVE')->whereNotNull('certificate_date');
        } elseif ($surveillance === 'DUE_RA') {
            $candidateQuery->where('status', '!=', 'INACTIVE')->where(fn ($q) => $q->whereNotNull('certificate_date')->orWhereNotNull('expired_at'));
        } elseif ($surveillance === 'NEEDS_ACTION') {
            $candidateQuery->where('status', '!=', 'INACTIVE')->where(fn ($q) => $q->whereNotNull('certificate_date')->orWhereNotNull('expired_at'));
        }

        $validIds = [];
        $candidateQuery->chunkById(150, function ($candidateChunk) use (
            &$validIds,
            $needsDynamicStatusFilter,
            $status,
            $needsSurveillanceFilter,
            $surveillance
        ) {
            foreach ($candidateChunk as $candidate) {
                foreach ($candidate->assessments as $asm) {
                    $asm->setRelation('lpk', $candidate);
                }

                if ($needsDynamicStatusFilter && $candidate->dynamic_status !== $status) {
                    continue;
                }

                if ($needsSurveillanceFilter) {
                    $alerts = $candidate->getActiveSurveillanceAlerts();
                    $milestones = $candidate->surveillance_milestones;

                    $matched = match ($surveillance) {
                        'NEEDS_ACTION' => count($alerts) > 0,
                        'DUE_S1' => in_array($milestones['s1']['status'] ?? '', ['DUE', 'OVERDUE', 'SUSPENDED'], true),
                        'DUE_S2' => in_array($milestones['s2']['status'] ?? '', ['DUE', 'OVERDUE', 'SUSPENDED'], true),
                        'DUE_RA' => in_array($milestones['ra']['status'] ?? '', ['DUE', 'OVERDUE', 'EXPIRED', 'SUSPENDED'], true),
                        'OVERDUE' => collect($alerts)->contains('is_urgent', true),
                        default => true,
                    };

                    if (! $matched) {
                        continue;
                    }
                }

                $validIds[] = $candidate->id;
            }
        });

        if (empty($validIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIntegerInRaw('lpks.id', $validIds);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(LpkLocation::class);
    }

    public function mainLocation(): ?LpkLocation
    {
        if ($this->relationLoaded('locations')) {
            return $this->locations->firstWhere('is_main', true) ?? $this->locations->first();
        }

        return $this->locations()->where('is_main', true)->first() ?? $this->locations()->first();
    }

    public function ensureMainLocation(): LpkLocation
    {
        $main = $this->mainLocation();

        if (! $main) {
            $main = $this->locations()->create([
                'name' => 'Kantor Pusat',
                'location_type' => LpkLocation::TYPE_HEAD_OFFICE,
                'is_main' => true,
                'address' => $this->address ?: '-',
                'contact_person' => $this->pic?->name,
                'contact_email' => $this->email ?: $this->pic?->email,
                'contact_phone' => $this->phone,
                'scope_notes' => null,
            ]);

            if ($this->relationLoaded('locations')) {
                $this->load('locations.assessments');
            }
        } elseif ($this->address && ($main->address === '-' || empty($main->address))) {
            $main->update([
                'address' => $this->address,
                'contact_phone' => $this->phone ?: $main->contact_phone,
                'contact_email' => $this->email ?: $main->contact_email,
            ]);
        }

        return $main;
    }

    public function isMultilocation(): bool
    {
        if ($this->relationLoaded('locations')) {
            return $this->locations->count() > 1;
        }

        return $this->locations()->count() > 1;
    }

    /**
     * Ringkasan status kepatuhan audit multilokasi dalam siklus 5 tahun (KAN U-01 Klausul 3.1.8).
     *
     * @return array{total: int, assessed: int, unassessed: int, unassessed_locations: \Illuminate\Support\Collection<int, LpkLocation>, is_fully_assessed: bool}
     */
    public function multilocationAuditSummary(): array
    {
        $locations = $this->relationLoaded('locations') ? $this->locations : $this->locations()->get();
        $cycleStart = $this->cycle_base_date;
        $cycleEnd = $this->cycle_expired_at;

        $total = $locations->count();
        $assessedCount = 0;
        $unassessed = collect();

        foreach ($locations as $loc) {
            if ($loc->isAssessedInCycle($cycleStart, $cycleEnd)) {
                $assessedCount++;
            } else {
                $unassessed->push($loc);
            }
        }

        return [
            'total' => $total,
            'assessed' => $assessedCount,
            'unassessed' => $total - $assessedCount,
            'unassessed_locations' => $unassessed,
            'is_fully_assessed' => ($total > 0 && $assessedCount === $total),
        ];
    }

    /**
     * Memperbarui masa akreditasi LPK ke siklus 5 tahun berikutnya
     * saat asesmen Re-Akreditasi telah selesai (COMPLETED) dan memiliki SK.
     */
    public function renewAccreditationCycleFromAssessment(Assessment $assessment): bool
    {
        return app(LpkSurveillanceService::class)->renewAccreditationCycle($this, $assessment);
    }
}
