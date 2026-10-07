<?php

namespace App\Models;

use App\Services\AssessmentStatusService;
use App\Services\AssessmentTpService;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    public const TYPE_AKREDITASI_AWAL = 'Akreditasi Awal';
    public const TYPE_SURVEILEN_1 = 'Surveilen 1';
    public const TYPE_SURVEILEN_1_PRL = 'Surveilen 1 + PRL';
    public const TYPE_SURVEILEN_2 = 'Surveilen 2';
    public const TYPE_SURVEILEN_2_PRL = 'Surveilen 2 + PRL';
    public const TYPE_STT = 'Surveilen Tidak Terjadwal (STT)';
    public const TYPE_PRL = 'Perluasan Ruang Lingkup (PRL)';
    public const TYPE_RE_AKREDITASI = 'Re-Akreditasi (Akreditasi Ulang)';

    /**
     * 8 Jenis Proses Pelaksanaan Asesmen Resmi
     */
    public const TYPES = [
        self::TYPE_AKREDITASI_AWAL => 'Akreditasi Awal',
        self::TYPE_SURVEILEN_1 => 'Surveilen 1',
        self::TYPE_SURVEILEN_1_PRL => 'Surveilen 1 + PRL',
        self::TYPE_SURVEILEN_2 => 'Surveilen 2',
        self::TYPE_SURVEILEN_2_PRL => 'Surveilen 2 + PRL',
        self::TYPE_STT => 'Surveilen Tidak Terjadwal (STT)',
        self::TYPE_PRL => 'Perluasan Ruang Lingkup (PRL)',
        self::TYPE_RE_AKREDITASI => 'Re-Akreditasi (Akreditasi Ulang)',
    ];

    public const TP_STATUS_NONE = 'NONE';
    public const TP_STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const TP_STATUS_UNDER_VERIFICATION = 'UNDER_VERIFICATION';
    public const TP_STATUS_SATISFIED = 'SATISFIED';

    public const TP_STATUSES = [
        self::TP_STATUS_NONE => 'Nihil / Tidak Ada Temuan',
        self::TP_STATUS_IN_PROGRESS => 'Penyusunan Perbaikan oleh LPK',
        self::TP_STATUS_UNDER_VERIFICATION => 'Dalam Verifikasi Tim Asesor',
        self::TP_STATUS_SATISFIED => 'Dinyatakan Memenuhi (Selesai)',
    ];

    public const EHA_STATUS_BELUM = 'BELUM_EHA';
    public const EHA_STATUS_DIREKOMENDASIKAN = 'DIREKOMENDASIKAN';
    public const EHA_STATUS_PERLU_VERIFIKASI = 'PERLU_VERIFIKASI';
    public const EHA_STATUS_CATATAN_KHUSUS = 'CATATAN_KHUSUS';

    public const EHA_STATUSES = [
        self::EHA_STATUS_BELUM => 'Belum EHA',
        self::EHA_STATUS_DIREKOMENDASIKAN => 'Direkomendasikan (Memenuhi)',
        self::EHA_STATUS_PERLU_VERIFIKASI => 'Perlu Verifikasi Lanjutan',
        self::EHA_STATUS_CATATAN_KHUSUS => 'Catatan Khusus Panitia Teknis',
    ];

    public const STATUS_LABELS = [
        'PLANNED' => 'Direncanakan',
        'SCHEDULED' => 'Terjadwal',
        'IN_PROGRESS' => 'Sedang Berlangsung',
        'SUSPENDED' => 'Dibekukan',
        'REVOKED' => 'Dicabut',
        'COMPLETED' => 'Selesai',
        'CANCELLED' => 'Dibatalkan',
    ];

    /**
     * Tentukan status asesmen secara otomatis berbasis tanggal pelaksanaan dan progres milestone (batas waktu KAN).
     */
    public static function determineStatusFromDates(
        ?Carbon $startAt,
        ?Carbon $endAt,
        ?string $currentStatus = null,
        ?string $tpStatus = null,
        ?string $skNumber = null,
        ?Carbon $reportDate = null,
        ?Carbon $ehaDate = null,
        ?Carbon $tpDueDate = null,
        bool $tpHasExtension = false,
        ?int $tpExtensionMonths = 0,
        ?Carbon $tpSatisfiedAt = null,
        ?string $assessmentType = null
    ): string {
        return app(AssessmentStatusService::class)->determineStatusFromDates(
            $startAt,
            $endAt,
            $currentStatus,
            $tpStatus,
            $skNumber,
            $reportDate,
            $ehaDate,
            $tpDueDate,
            $tpHasExtension,
            $tpExtensionMonths,
            $tpSatisfiedAt,
            $assessmentType
        );
    }

    public function getStatusAttribute(?string $value): string
    {
        if ($value === 'CANCELLED') {
            return 'CANCELLED';
        }

        // Asesmen surveilen periode lampau dari LPK aktif otomatis terealisasikan
        if ($this->isPastSurveillanceForActiveLpk()) {
            return 'COMPLETED';
        }

        if ($this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->sk_number)) {
            return 'COMPLETED';
        }

        // Lewat batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen
        if ($this->is_suspension_expired || $value === 'REVOKED') {
            return 'REVOKED';
        }

        if ($this->is_tp_overdue || $value === 'SUSPENDED') {
            return 'SUSPENDED';
        }

        return $value ?: 'PLANNED';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ($this->status ?: 'Direncanakan');
    }

    protected $fillable = [
        'lpk_id',
        'created_by',
        'title',
        'assessment_type',
        'start_at',
        'end_at',
        'submission_due_date',
        'report_date',
        'eha_date',
        'eha_status',
        'eha_notes',
        'location',
        'status',
        'lead_assessor',
        'assessment_team',
        'notes',
        'tp_status',
        'tp_due_date',
        'tp_has_extension',
        'tp_extension_months',
        'tp_extension_letter_no',
        'tp_extension_date',
        'tp_extension_notes',
        'tp_satisfied_at',
        'tp_notes',
        'sk_number',
        'sk_date',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'submission_due_date' => 'date',
            'report_date' => 'date',
            'eha_date' => 'date',
            'tp_due_date' => 'date',
            'tp_has_extension' => 'boolean',
            'tp_extension_months' => 'integer',
            'tp_extension_date' => 'date',
            'tp_satisfied_at' => 'date',
            'sk_date' => 'date',
        ];
    }

    public static function normalizeType(?string $type, string $title = ''): string
    {
        return app(AssessmentStatusService::class)->normalizeType($type, $title);
    }

    public function getAssessmentTypeLabelAttribute(): string
    {
        return self::normalizeType($this->assessment_type, (string) $this->title);
    }

    public function getDisplayTitleAttribute(): string
    {
        $title = (string) $this->title;

        if ($this->relationLoaded('lpk') && $this->lpk?->name) {
            $lpkName = trim($this->lpk->name);
            if (str_ends_with($title, ' - ' . $lpkName)) {
                return trim(substr($title, 0, -strlen(' - ' . $lpkName)));
            }
        } elseif ($this->lpk_id) {
            $lpk = $this->lpk;
            if ($lpk && $lpk->name) {
                $trimmedLpk = trim($lpk->name);
                if (str_ends_with($title, ' - ' . $trimmedLpk)) {
                    return trim(substr($title, 0, -strlen(' - ' . $trimmedLpk)));
                }
            }
        }

        if (preg_match('/^(Asesmen\s+(?:Surveilen\s+\d+\s*\([^\)]+\)|Re-Akreditasi\s*\([^\)]+\)|Akreditasi\s+Awal)|Surveilen\s+\d+\s+Siklus\s+KAN|Re-asesmen\s+Siklus\s+KAN)\s*-\s*.+$/iu', $title, $matches)) {
            return trim($matches[1]);
        }

        return $title;
    }

    public function calculateDefaultSubmissionDueDate(): ?Carbon
    {
        return app(AssessmentStatusService::class)->calculateDefaultSubmissionDueDate($this);
    }

    public function getSubmissionDueDateAttribute(): ?Carbon
    {
        return app(AssessmentStatusService::class)->getSubmissionDueDate($this);
    }

    public function getSuspensionResolutionDeadlineAttribute(): ?Carbon
    {
        return app(AssessmentStatusService::class)->calculateSuspensionResolutionDeadline($this);
    }

    public function getDaysRemainingSuspensionAttribute(): ?int
    {
        return app(AssessmentStatusService::class)->calculateDaysRemainingSuspension($this);
    }

    public function isPastSurveillanceForActiveLpk(): bool
    {
        return app(AssessmentStatusService::class)->isPastSurveillanceForActiveLpk($this);
    }

    public function getIsSuspensionExpiredAttribute(): bool
    {
        return app(AssessmentStatusService::class)->isSuspensionExpired($this);
    }

    public function getIsSubmissionOverdueAttribute(): bool
    {
        return app(AssessmentStatusService::class)->isSubmissionOverdue($this);
    }

    /**
     * Hitung batas waktu default sesuai skema akreditasi KAN:
     * - Akreditasi Awal: 3 bulan
     * - S1, S2, RA, PRL, STT, Survailen, Re-asesmen: 2 bulan
     */
    public static function calculateDefaultDueDateForType(?string $type, ?Carbon $baseDate): ?Carbon
    {
        return app(AssessmentTpService::class)->calculateDefaultDueDate($type, $baseDate);
    }

    public function calculateDefaultTpDueDate(): ?Carbon
    {
        return app(AssessmentTpService::class)->calculateDefaultTpDueDate($this);
    }

    public function calculateDefaultTpReminderDate(): ?Carbon
    {
        return app(AssessmentTpService::class)->calculateDefaultTpReminderDate($this);
    }

    public function getAssessmentTeamAttribute(): ?string
    {
        return $this->attributes['assessment_team'] ?? $this->attributes['lead_assessor'] ?? null;
    }

    public function setAssessmentTeamAttribute(?string $value): void
    {
        $this->attributes['assessment_team'] = $value;
        $this->attributes['lead_assessor'] = $value;
    }

    public function getEhaStatusLabelAttribute(): string
    {
        return self::EHA_STATUSES[$this->eha_status] ?? ($this->eha_status ?: 'Belum EHA');
    }

    public function calculateDefaultSkReminderDate(): ?Carbon
    {
        return app(AssessmentTpService::class)->calculateDefaultSkReminderDate($this);
    }

    public function getEffectiveTpDueDateAttribute(): ?Carbon
    {
        return app(AssessmentTpService::class)->calculateEffectiveTpDueDate($this);
    }

    public function getDaysRemainingTpAttribute(): ?int
    {
        return app(AssessmentTpService::class)->calculateDaysRemainingTp($this);
    }

    public function getIsTpOverdueAttribute(): bool
    {
        return app(AssessmentTpService::class)->isTpOverdue($this);
    }

    public function getTpStatusLabelAttribute(): string
    {
        return app(AssessmentTpService::class)->getTpStatusLabel($this);
    }

    public function getTpSlaBadgeAttribute(): array
    {
        return app(AssessmentTpService::class)->getTpSlaBadge($this);
    }

    public function getSkLeadTimeDaysAttribute(): ?int
    {
        return app(AssessmentTpService::class)->calculateSkLeadTimeDays($this);
    }

    public function getSkLeadTimeLabelAttribute(): ?string
    {
        return app(AssessmentTpService::class)->calculateSkLeadTimeLabel($this);
    }


    protected static function booted(): void
    {
        static::saving(function (Assessment $assessment): void {
            if ($assessment->tp_status && $assessment->tp_status !== self::TP_STATUS_NONE && ! $assessment->tp_due_date && $assessment->end_at) {
                $assessment->tp_due_date = $assessment->calculateDefaultTpDueDate();
            }
            if ($assessment->tp_status === self::TP_STATUS_SATISFIED && ! $assessment->tp_satisfied_at) {
                $assessment->tp_satisfied_at = now()->startOfDay();
            }

            // Sinkronisasi otomatis siklus hidup asesmen:
            // 0. Asesmen lampau untuk LPK aktif otomatis terealisasikan
            if ($assessment->isPastSurveillanceForActiveLpk() && $assessment->status !== 'CANCELLED') {
                $assessment->status = 'COMPLETED';
                $assessment->tp_status = self::TP_STATUS_SATISFIED;
            }
            // 1. Jika tindakan perbaikan sudah dinyatakan memenuhi (SATISFIED) atau SK sudah terbit,
            // maka status asesmen otomatis menjadi COMPLETED (Selesai).
            elseif (($assessment->tp_status === self::TP_STATUS_SATISFIED || ! empty($assessment->sk_number)) && $assessment->status !== 'CANCELLED') {
                $assessment->status = 'COMPLETED';
            }
            // 2. Jika melewati batas 1 tahun kesempatan penyelesaian pembekuan surveilen,
            // status otomatis menjadi REVOKED (Dicabut).
            elseif ($assessment->is_suspension_expired && $assessment->status !== 'CANCELLED' && empty($assessment->sk_number)) {
                $assessment->status = 'REVOKED';
            }
            // 3. Jika asesmen melewati batas waktu awal tanpa dinyatakan memenuhi,
            // status otomatis menjadi SUSPENDED (Dibekukan).
            elseif ($assessment->is_tp_overdue && $assessment->status !== 'CANCELLED' && empty($assessment->sk_number)) {
                $assessment->status = 'SUSPENDED';
            }
            // 4. Jika asesmen sudah mulai berjalan (ada laporan, tanggal EHA, atau TP aktif),
            // dan statusnya masih PLANNED, otomatis naik menjadi IN_PROGRESS (Sedang Berlangsung).
            elseif ($assessment->status === 'PLANNED' && (
                in_array($assessment->tp_status, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION], true) ||
                ! empty($assessment->report_date) ||
                ! empty($assessment->eha_date)
            )) {
                $assessment->status = 'IN_PROGRESS';
            }
        });

        static::saved(function (Assessment $assessment): void {
            // Jika asesmen Re-Akreditasi selesai dan SK telah terbit,
            // otomatis perbarui masa akreditasi LPK ke siklus 5 tahun berikutnya.
            $isRa = $assessment->assessment_type === self::TYPE_RE_AKREDITASI
                || str_contains(strtolower($assessment->title ?: ''), 're-akreditasi')
                || str_contains(strtolower($assessment->title ?: ''), 'reakreditasi');

            if ($isRa && $assessment->status === 'COMPLETED' && ! empty($assessment->sk_number)) {
                $assessment->lpk?->renewAccreditationCycleFromAssessment($assessment);
            }

            \Illuminate\Support\Facades\Cache::put('simasadi_surveillance_version', time());
        });

        static::deleted(function (): void {
            \Illuminate\Support\Facades\Cache::put('simasadi_surveillance_version', time());
        });
    }

    public function scopeTpOverdue($query)
    {
        return $query->where(function ($root) {
            $root->where('status', 'SUSPENDED')
                ->orWhere(function ($q) {
                    $q->where('status', '!=', 'CANCELLED')
                        ->where('status', '!=', 'COMPLETED')
                        ->whereNull('sk_number')
                        ->where(function ($subTp) {
                            $subTp->where('tp_status', '!=', self::TP_STATUS_SATISFIED)
                                ->orWhereNull('tp_status');
                        })
                        ->where(function ($dateQ) {
                            $dateQ->where(function ($q2) {
                                $q2->whereNotNull('tp_due_date')
                                    ->where(function ($subExt) {
                                        $subExt->where(function ($ext0) {
                                            $ext0->where('tp_has_extension', false)
                                                ->whereDate('tp_due_date', '<', now());
                                        })->orWhere(function ($ext1) {
                                            $ext1->where('tp_has_extension', true)
                                                ->whereDate('tp_due_date', '<', now()->subMonth());
                                        });
                                    });
                            })->orWhere(function ($q3) {
                                $q3->whereNull('tp_due_date')
                                    ->whereIn('tp_status', [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION])
                                    ->whereNotNull('end_at')
                                    ->where(function ($subEnd) {
                                        $subEnd->where(function ($initialQ) {
                                            $initialQ->whereIn('assessment_type', [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'])
                                                ->whereDate('end_at', '<', now()->subMonths(3));
                                        })->orWhere(function ($otherQ) {
                                            $otherQ->whereNotIn('assessment_type', [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'])
                                                ->whereDate('end_at', '<', now()->subMonths(2));
                                        });
                                    });
                            });
                        });
                });
        });
    }

    public function scopeTpDueSoon($query, int $days = 14)
    {
        $now = now();
        $target = now()->addDays($days);

        return $query->whereNotIn('tp_status', [self::TP_STATUS_NONE, self::TP_STATUS_SATISFIED])
            ->whereNotNull('tp_due_date')
            ->where(function ($q) use ($now, $target): void {
                $q->where(function ($sub) use ($now, $target): void {
                    $sub->where('tp_has_extension', false)
                        ->whereDate('tp_due_date', '>=', $now)
                        ->whereDate('tp_due_date', '<=', $target);
                })->orWhere(function ($sub) use ($now, $target): void {
                    $sub->where('tp_has_extension', true)
                        ->whereDate('tp_due_date', '>=', $now->copy()->subMonth())
                        ->whereDate('tp_due_date', '<=', $target->copy()->subMonth());
                });
            });
    }

    public function scopeTpActive($query)
    {
        return $query->whereIn('tp_status', [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION]);
    }

    public function lpk(): BelongsTo
    {
        return $this->belongsTo(Lpk::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $this->lpk) {
            return (int) $this->created_by === (int) $user->id;
        }

        return $this->lpk->canManage($user);
    }
}
