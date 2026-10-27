<?php

namespace App\Models;

use App\Services\AssessmentStatusService;
use App\Services\AssessmentTpService;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Builder;
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
        self::TP_STATUS_NONE => 'Belum Ditentukan',
        self::TP_STATUS_IN_PROGRESS => 'Penyusunan Perbaikan oleh LPK',
        self::TP_STATUS_UNDER_VERIFICATION => 'Dalam Verifikasi Tim Asesor',
        self::TP_STATUS_SATISFIED => 'Dinyatakan Memenuhi (Selesai)',
    ];

    public const EHA_STATUS_BELUM = 'BELUM_EHA';
    public const EHA_STATUS_DIREKOMENDASIKAN = 'DIREKOMENDASIKAN';
    public const EHA_STATUS_PERLU_VERIFIKASI = 'PERLU_VERIFIKASI';
    public const EHA_STATUS_CATATAN_KHUSUS = 'CATATAN_KHUSUS';

    public const EHA_STATUSES = [
        self::EHA_STATUS_BELUM => 'Belum Rapat Panitia Teknis',
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
        ?string $assessmentType = null,
        ?Carbon $tpExtensionDate = null,
        ?string $ehaStatus = null
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
            $assessmentType,
            $tpExtensionDate,
            $ehaStatus
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

        $isFieldDone = ($this->end_at && now()->gte($this->end_at)) || ! empty($this->report_date) || in_array($this->tp_status, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION, self::TP_STATUS_SATISFIED], true);
        $isTpSatisfied = $this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at);
        $hasEhaCompleted = in_array($this->eha_status, [self::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
            || (! empty($this->eha_date) && ! in_array($this->eha_status, [self::EHA_STATUS_BELUM, self::EHA_STATUS_PERLU_VERIFIKASI, self::EHA_STATUS_CATATAN_KHUSUS], true));

        // Soft Gating: Hanya beralih ke COMPLETED jika SK KAN sudah terbit DAN seluruh tahapan hulu (TP & EHA) telah tuntas
        if (! empty($this->sk_number) && $isFieldDone && $isTpSatisfied && $hasEhaCompleted) {
            return 'COMPLETED';
        }

        // Lewat batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen
        if ($this->is_suspension_expired || $value === 'REVOKED') {
            return 'REVOKED';
        }

        if ($this->is_tp_overdue || $value === 'SUSPENDED') {
            return 'SUSPENDED';
        }

        // Jika ada progres tahapan berjalan (TP memenuhi, ada draft SK, laporan, EHA, atau TP aktif), status asesmen adalah IN_PROGRESS
        if ($isTpSatisfied || ! empty($this->sk_number) || ! empty($this->report_date) || ! empty($this->eha_date) || in_array($this->tp_status, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION], true)) {
            return 'IN_PROGRESS';
        }

        return $value ?: 'PLANNED';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->has_draft_sk_pending_upstream) {
            if ($this->is_tp_overdue || $this->status === 'SUSPENDED') {
                return 'Dibekukan (Draft SK - Batas Waktu TP Terlampaui)';
            }
            if (! ($this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at))) {
                return 'Sedang Berlangsung (Draft SK - Menunggu Perbaikan TP)';
            }
            if ($this->is_eha_needs_verification) {
                return 'Sedang Berlangsung (Draft SK - Perlu Verifikasi Panitia Teknis)';
            }
            if ($this->is_eha_special_notes) {
                return 'Sedang Berlangsung (Draft SK - Catatan Khusus Panitia Teknis)';
            }
            return 'Sedang Berlangsung (Draft SK - Menunggu Rapat Panitia Teknis)';
        }

        if ($this->is_eha_needs_verification) {
            return 'Sedang Berlangsung (Perlu Verifikasi Panitia Teknis)';
        }

        if ($this->is_eha_special_notes) {
            return 'Sedang Berlangsung (Catatan Khusus Panitia Teknis)';
        }

        if ($this->is_waiting_sk) {
            return 'Sedang Berlangsung (Menunggu SK KAN)';
        }

        if ($this->is_waiting_eha) {
            return 'Sedang Berlangsung (Menunggu Rapat Panitia Teknis)';
        }

        return self::STATUS_LABELS[$this->status] ?? ($this->status ?: 'Direncanakan');
    }

    protected $fillable = [
        'lpk_id',
        'lpk_location_id',
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

    public function getShortTypeAttribute(): string
    {
        $shortType = match (strtoupper(trim((string) $this->assessment_type))) {
            'S1', 'SURVEILEN 1', 'SURVEILLANCE 1' => 'S1',
            'SURVEILEN 1 + PRL', 'S1 + PRL', 'S1+PRL' => 'S1+PRL',
            'S2', 'SURVEILEN 2', 'SURVEILLANCE 2' => 'S2',
            'SURVEILEN 2 + PRL', 'S2 + PRL', 'S2+PRL' => 'S2+PRL',
            'RA', 'RE-AKREDITASI', 'REAKREDITASI', 'RE-ASESMEN', 'REASSESSMENT', 'RE-AKREDITASI (AKREDITASI ULANG)' => 'RA',
            'PRL', 'PERLUASAN RUANG LINGKUP', 'PERLUASAN LINGKUP', 'PERLUASAN RUANG LINGKUP (PRL)' => 'PRL',
            'STT', 'SURVEILEN TIDAK TERJADWAL', 'SURVEILEN TIDAK TERJADWAL (STT)' => 'STT',
            'AA', 'ASESMEN AWAL', 'INITIAL', 'AKREDITASI AWAL' => 'AA',
            default => $this->assessment_type ?: 'Asesmen',
        };

        if (in_array($shortType, ['Surveilen', 'Asesmen'], true) && preg_match('/\b(S1|S2|RA|PRL|STT|AA)\b/i', (string) $this->title, $m)) {
            $shortType = strtoupper($m[1]);
        }

        return $shortType;
    }

    public function getDisplayTitleAttribute(): string
    {
        $title = (string) $this->title;
        $typeLabel = $this->assessment_type_label;

        $derived = $title;
        if ($this->relationLoaded('lpk') && $this->lpk?->name) {
            $lpkName = trim($this->lpk->name);
            if (str_ends_with($title, ' - ' . $lpkName)) {
                $derived = trim(substr($title, 0, -strlen(' - ' . $lpkName)));
            }
        } elseif ($this->lpk_id) {
            $lpk = $this->lpk;
            if ($lpk && $lpk->name) {
                $trimmedLpk = trim($lpk->name);
                if (str_ends_with($title, ' - ' . $trimmedLpk)) {
                    $derived = trim(substr($title, 0, -strlen(' - ' . $trimmedLpk)));
                }
            }
        }

        if (preg_match('/^(Asesmen\s+(?:Surveilen\s+\d+\s*\([^\)]+\)|Re-Akreditasi\s*\([^\)]+\)|Akreditasi\s+Awal)|Surveilen\s+\d+\s+Siklus\s+KAN|Re-asesmen\s+Siklus\s+KAN)\s*-\s*.+$/iu', $title, $matches)) {
            $derived = trim($matches[1]);
        }

        // Jika derived title merupakan nama jenis asesmen lama yang bertentangan dengan jenis asesmen saat ini,
        // prioritaskan jenis asesmen saat ini agar tampilan selalu konsisten.
        $knownTypePatterns = [
            'Akreditasi Awal', 'Surveilen 1 + PRL', 'Surveilen 2 + PRL',
            'Surveilen 1', 'Surveilen 2', 'Surveilen Tidak Terjadwal (STT)', 'Surveilen Tidak Terjadwal',
            'Perluasan Ruang Lingkup (PRL)', 'Perluasan Ruang Lingkup', 'PRL', 'STT', 'S1', 'S2', 'RA',
            'Re-Akreditasi (Akreditasi Ulang)', 'Re-Akreditasi', 'Re-asesmen', 'Akreditasi Ulang',
            'Asesmen Surveilen 1 (S1)', 'Asesmen Surveilen 2 (S2)', 'Asesmen Re-Akreditasi (RA)', 'Asesmen Akreditasi Awal'
        ];
        foreach ($knownTypePatterns as $ktp) {
            if (strcasecmp($derived, $ktp) === 0) {
                if ($typeLabel && self::normalizeType($derived) !== $typeLabel) {
                    return $typeLabel;
                }
                break;
            }
        }

        return $derived ?: ($typeLabel ?: $title);
    }

    public function calculateDefaultSubmissionDueDate(): ?Carbon
    {
        return app(AssessmentStatusService::class)->calculateDefaultSubmissionDueDate($this);
    }

    public function getSubmissionDueDateAttribute(): ?Carbon
    {
        return app(AssessmentStatusService::class)->getSubmissionDueDate($this);
    }

    public function getSubmissionCycleHintAttribute(): string
    {
        return app(AssessmentStatusService::class)->getSubmissionCycleHint($this);
    }

    public function getIsEhaNeedsVerificationAttribute(): bool
    {
        $isTpSatisfied = $this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at);

        return $isTpSatisfied
            && $this->eha_status === self::EHA_STATUS_PERLU_VERIFIKASI
            && empty($this->sk_number)
            && $this->status !== 'CANCELLED';
    }

    public function getIsEhaSpecialNotesAttribute(): bool
    {
        $isTpSatisfied = $this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at);

        return $isTpSatisfied
            && $this->eha_status === self::EHA_STATUS_CATATAN_KHUSUS
            && empty($this->sk_number)
            && $this->status !== 'CANCELLED';
    }

    public function getIsWaitingEhaAttribute(): bool
    {
        if (in_array($this->eha_status, [self::EHA_STATUS_PERLU_VERIFIKASI, self::EHA_STATUS_CATATAN_KHUSUS], true)) {
            return false;
        }

        $isTpSatisfied = $this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at);
        $hasEhaCompleted = in_array($this->eha_status, [self::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
            || (! empty($this->eha_date) && ! in_array($this->eha_status, [self::EHA_STATUS_BELUM, self::EHA_STATUS_PERLU_VERIFIKASI, self::EHA_STATUS_CATATAN_KHUSUS], true));

        return $isTpSatisfied && ! $hasEhaCompleted && empty($this->sk_number) && $this->status !== 'CANCELLED';
    }

    public function getIsWaitingSkAttribute(): bool
    {
        $isTpSatisfied = $this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at);
        $hasEhaCompleted = in_array($this->eha_status, [self::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
            || (! empty($this->eha_date) && ! in_array($this->eha_status, [self::EHA_STATUS_BELUM, self::EHA_STATUS_PERLU_VERIFIKASI, self::EHA_STATUS_CATATAN_KHUSUS], true));

        return $isTpSatisfied && $hasEhaCompleted && empty($this->sk_number) && $this->status !== 'CANCELLED';
    }

    public function getHasDraftSkPendingUpstreamAttribute(): bool
    {
        if (empty($this->sk_number) || $this->status === 'CANCELLED' || $this->isPastSurveillanceForActiveLpk()) {
            return false;
        }

        $isFieldDone = ($this->end_at && now()->gte($this->end_at)) || ! empty($this->report_date) || in_array($this->tp_status, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION, self::TP_STATUS_SATISFIED], true) || $this->isPastSurveillanceForActiveLpk();
        $isTpSatisfied = $this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at) || $this->isPastSurveillanceForActiveLpk();
        $hasEhaCompleted = in_array($this->eha_status, [self::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
            || (! empty($this->eha_date) && ! in_array($this->eha_status, [self::EHA_STATUS_BELUM, self::EHA_STATUS_PERLU_VERIFIKASI, self::EHA_STATUS_CATATAN_KHUSUS], true))
            || $this->isPastSurveillanceForActiveLpk();

        return ! ($isFieldDone && $isTpSatisfied && $hasEhaCompleted);
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
        return self::EHA_STATUSES[$this->eha_status] ?? ($this->eha_status ?: 'Belum Rapat Panitia Teknis');
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
            } elseif ($assessment->tp_status && $assessment->tp_status !== self::TP_STATUS_SATISFIED) {
                $assessment->tp_satisfied_at = null;
            }

            // Hitung kualifikasi tahapan hulu
            $isFieldDone = ($assessment->end_at && now()->gte($assessment->end_at)) || ! empty($assessment->report_date) || in_array($assessment->tp_status, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION, self::TP_STATUS_SATISFIED], true) || $assessment->isPastSurveillanceForActiveLpk();
            $isTpSatisfied = $assessment->tp_status === self::TP_STATUS_SATISFIED || ! empty($assessment->tp_satisfied_at) || $assessment->isPastSurveillanceForActiveLpk();
            $hasEhaCompleted = (in_array($assessment->eha_status, [self::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
                || (! empty($assessment->eha_date) && ! in_array($assessment->eha_status, [self::EHA_STATUS_BELUM, self::EHA_STATUS_PERLU_VERIFIKASI, self::EHA_STATUS_CATATAN_KHUSUS], true)))
                || $assessment->isPastSurveillanceForActiveLpk();

            $isUpstreamCompleted = $isFieldDone && $isTpSatisfied && $hasEhaCompleted;

            // Sinkronisasi otomatis siklus hidup asesmen:
            // 0. Asesmen lampau untuk LPK aktif otomatis terealisasikan
            if ($assessment->isPastSurveillanceForActiveLpk() && $assessment->status !== 'CANCELLED') {
                $assessment->status = 'COMPLETED';
                $assessment->tp_status = self::TP_STATUS_SATISFIED;
            }
            // 1. Jika SK sudah terbit DAN seluruh tahapan hulu telah tuntas, status menjadi COMPLETED (Selesai).
            elseif (! empty($assessment->sk_number) && $isUpstreamCompleted && $assessment->status !== 'CANCELLED') {
                $assessment->status = 'COMPLETED';
            }
            // 2. Jika melewati batas 1 tahun kesempatan penyelesaian pembekuan surveilen,
            // status otomatis menjadi REVOKED (Dicabut).
            elseif ($assessment->is_suspension_expired && $assessment->status !== 'CANCELLED' && empty($assessment->sk_number)) {
                $assessment->status = 'REVOKED';
            }
            // 3. Jika asesmen melewati tenggat waktu TP tanpa dinyatakan memenuhi,
            // status otomatis menjadi SUSPENDED (Dibekukan).
            elseif ($assessment->is_tp_overdue && $assessment->status !== 'CANCELLED') {
                $assessment->status = 'SUSPENDED';
            }
            // 4. Jika asesmen sudah mulai berjalan (ada laporan, tanggal EHA, nomor SK draft, atau TP aktif/memenuhi),
            // atau jika statusnya COMPLETED namun tahapan hulu belum lengkap, status adalah IN_PROGRESS (Sedang Berlangsung).
            elseif ($assessment->status !== 'CANCELLED' && (
                in_array($assessment->status, ['PLANNED', 'SCHEDULED', 'COMPLETED'], true) && (
                    in_array($assessment->tp_status, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION, self::TP_STATUS_SATISFIED], true) ||
                    ! empty($assessment->report_date) ||
                    ! empty($assessment->eha_date) ||
                    ! empty($assessment->sk_number) ||
                    ! empty($assessment->tp_satisfied_at)
                )
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

            if ($isRa && $assessment->status === 'COMPLETED' && ! empty($assessment->sk_number) && $assessment->lpk) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($assessment): void {
                    $assessment->lpk->renewAccreditationCycleFromAssessment($assessment);
                });
            }
            \App\Services\LpkSurveillanceService::invalidateAlertsCache($assessment->lpk);
        });

        static::deleted(function (Assessment $assessment): void {
            \App\Services\LpkSurveillanceService::invalidateAlertsCache($assessment->lpk);
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
                                                ->where(function ($extQ) {
                                                    $extQ->where(function ($ext0) {
                                                        $ext0->where('tp_has_extension', false)
                                                            ->whereDate('end_at', '<', now()->subMonths(3));
                                                    })->orWhere(function ($ext1) {
                                                        $ext1->where('tp_has_extension', true)
                                                            ->whereDate('end_at', '<', now()->subMonths(4));
                                                    });
                                                });
                                        })->orWhere(function ($otherQ) {
                                            $otherQ->whereNotIn('assessment_type', [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'])
                                                ->where(function ($extQ) {
                                                    $extQ->where(function ($ext0) {
                                                        $ext0->where('tp_has_extension', false)
                                                            ->whereDate('end_at', '<', now()->subMonths(2));
                                                    })->orWhere(function ($ext1) {
                                                        $ext1->where('tp_has_extension', true)
                                                            ->whereDate('end_at', '<', now()->subMonths(3));
                                                    });
                                                });
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

        return $query->whereNotIn('status', ['COMPLETED', 'CANCELLED', 'SUSPENDED'])
            ->whereNull('sk_number')
            ->whereNotIn('tp_status', [self::TP_STATUS_NONE, self::TP_STATUS_SATISFIED])
            ->where(function ($q) use ($now, $target): void {
                $q->where(function ($hasDueDate) use ($now, $target): void {
                    $hasDueDate->whereNotNull('tp_due_date')
                        ->where(function ($sub) use ($now, $target): void {
                            $sub->where(function ($ext0) use ($now, $target): void {
                                $ext0->where('tp_has_extension', false)
                                    ->whereDate('tp_due_date', '>=', $now)
                                    ->whereDate('tp_due_date', '<=', $target);
                            })->orWhere(function ($ext1) use ($now, $target): void {
                                $ext1->where('tp_has_extension', true)
                                    ->whereDate('tp_due_date', '>=', $now->copy()->subMonth())
                                    ->whereDate('tp_due_date', '<=', $target->copy()->subMonth());
                            });
                        });
                })->orWhere(function ($noDueDate) use ($now, $target): void {
                    $noDueDate->whereNull('tp_due_date')
                        ->whereNotNull('end_at')
                        ->where(function ($typeQ) use ($now, $target): void {
                            $typeQ->where(function ($initialQ) use ($now, $target): void {
                                $initialQ->whereIn('assessment_type', [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'])
                                    ->where(function ($subExt) use ($now, $target): void {
                                        $subExt->where(function ($ext0) use ($now, $target): void {
                                            $ext0->where('tp_has_extension', false)
                                                ->whereDate('end_at', '>=', $now->copy()->subMonths(3))
                                                ->whereDate('end_at', '<=', $target->copy()->subMonths(3));
                                        })->orWhere(function ($ext1) use ($now, $target): void {
                                            $ext1->where('tp_has_extension', true)
                                                ->whereDate('end_at', '>=', $now->copy()->subMonths(4))
                                                ->whereDate('end_at', '<=', $target->copy()->subMonths(4));
                                        });
                                    });
                            })->orWhere(function ($otherQ) use ($now, $target): void {
                                $otherQ->whereNotIn('assessment_type', [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'])
                                    ->where(function ($subExt) use ($now, $target): void {
                                        $subExt->where(function ($ext0) use ($now, $target): void {
                                            $ext0->where('tp_has_extension', false)
                                                ->whereDate('end_at', '>=', $now->copy()->subMonths(2))
                                                ->whereDate('end_at', '<=', $target->copy()->subMonths(2));
                                        })->orWhere(function ($ext1) use ($now, $target): void {
                                            $ext1->where('tp_has_extension', true)
                                                ->whereDate('end_at', '>=', $now->copy()->subMonths(3))
                                                ->whereDate('end_at', '<=', $target->copy()->subMonths(3));
                                        });
                                    });
                            });
                        });
                });
            });
    }

    public function scopeTpActive($query)
    {
        return $query->whereIn('tp_status', [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION]);
    }

    public function scopeForAssessmentType($query, ?string $assessmentType)
    {
        if (empty($assessmentType)) {
            return $query;
        }

        if ($assessmentType === self::TYPE_AKREDITASI_AWAL || $assessmentType === 'Asesmen Awal') {
            return $query->whereIn('assessment_type', [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA']);
        } elseif ($assessmentType === self::TYPE_SURVEILEN_1) {
            return $query->where(function ($q) {
                $q->whereIn('assessment_type', [self::TYPE_SURVEILEN_1, 'S1'])
                    ->orWhere(function ($sq) {
                        $sq->whereIn('assessment_type', ['Surveilen', 'SURVEILLANCE'])
                            ->where(function ($titleQ) {
                                $titleQ->where('title', 'like', '%Surveilen 1%')
                                    ->orWhere('title', 'like', '%(S1)%')
                                    ->orWhere('title', 'like', '% S1 %');
                            })
                            ->where('title', 'not like', '%PRL%');
                    });
            });
        } elseif ($assessmentType === self::TYPE_SURVEILEN_1_PRL) {
            return $query->where(function ($q) {
                $q->whereIn('assessment_type', [self::TYPE_SURVEILEN_1_PRL, 'S1 + PRL'])
                    ->orWhere(function ($sq) {
                        $sq->where('title', 'like', '%Surveilen 1%')
                            ->where('title', 'like', '%PRL%');
                    });
            });
        } elseif ($assessmentType === self::TYPE_SURVEILEN_2) {
            return $query->where(function ($q) {
                $q->whereIn('assessment_type', [self::TYPE_SURVEILEN_2, 'S2'])
                    ->orWhere(function ($sq) {
                        $sq->whereIn('assessment_type', ['Surveilen', 'SURVEILLANCE'])
                            ->where(function ($titleQ) {
                                $titleQ->where('title', 'like', '%Surveilen 2%')
                                    ->orWhere('title', 'like', '%(S2)%')
                                    ->orWhere('title', 'like', '% S2 %');
                            })
                            ->where('title', 'not like', '%PRL%');
                    });
            });
        } elseif ($assessmentType === self::TYPE_SURVEILEN_2_PRL) {
            return $query->where(function ($q) {
                $q->whereIn('assessment_type', [self::TYPE_SURVEILEN_2_PRL, 'S2 + PRL'])
                    ->orWhere(function ($sq) {
                        $sq->where('title', 'like', '%Surveilen 2%')
                            ->where('title', 'like', '%PRL%');
                    });
            });
        } elseif ($assessmentType === self::TYPE_STT) {
            return $query->whereIn('assessment_type', [self::TYPE_STT, 'STT', 'Surveilen Tidak Terjadwal']);
        } elseif ($assessmentType === self::TYPE_PRL) {
            return $query->whereIn('assessment_type', [self::TYPE_PRL, 'PRL', 'Perluasan Ruang Lingkup', 'Perluasan Lingkup']);
        } elseif ($assessmentType === self::TYPE_RE_AKREDITASI || $assessmentType === 'Re-asesmen') {
            return $query->whereIn('assessment_type', [self::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'RA', 'Re-asesmen', 'REASSESSMENT']);
        } elseif ($assessmentType === 'Surveilen') {
            return $query->where(function ($q) {
                $q->whereIn('assessment_type', [self::TYPE_SURVEILEN_1, self::TYPE_SURVEILEN_2, 'Surveilen', 'SURVEILLANCE', 'S1', 'S2']);
            });
        } else {
            return $query->where('assessment_type', $assessmentType);
        }
    }

    public function scopeForStatusFilter($query, ?string $status)
    {
        if (empty($status)) {
            return $query;
        }

        if ($status === 'OVERDUE') {
            return $query->tpOverdue();
        }

        if ($status === 'SUSPENDED') {
            return $query->where(function ($q) {
                $q->where('status', 'SUSPENDED')
                    ->orWhere(fn ($sub) => $sub->tpOverdue());
            });
        }

        return $query->where('status', $status);
    }

    public function lpk(): BelongsTo
    {
        return $this->belongsTo(Lpk::class);
    }

    public function lpkLocation(): BelongsTo
    {
        return $this->belongsTo(LpkLocation::class, 'lpk_location_id');
    }

    public function getDisplayLocationAttribute(): string
    {
        if ($this->lpkLocation) {
            $base = $this->lpkLocation->name;
            if ($this->location && $this->location !== $this->lpkLocation->name && $this->location !== $this->lpkLocation->address) {
                $base .= " - {$this->location}";
            }

            return $base;
        }

        return $this->location ?: 'Lokasi Lapangan LPK';
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

    public function canView(?User $user): bool
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

        return $this->lpk->canView($user);
    }

    public function isPrimaryFor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->lpk && $this->lpk->isPrimaryFor($user)) {
            return true;
        }

        return (int) $this->created_by === (int) $user->id;
    }

    /**
     * Lingkup asesmen yang berhak dikelola/dihapus oleh pengguna (Admin atau PIC pengelola LPK).
     */
    public function scopeManageableBy(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->whereHas('lpk', fn ($lq) => $lq->manageableBy($user))
              ->orWhere(fn ($sq) => $sq->whereNull('lpk_id')->where('created_by', $user->id));
        });
    }

    /**
     * Filter daftar asesmen berdasarkan parameter pencarian, status, jenis, SLA, dan tanggal.
     */
    public function scopeFilterByCriteria(Builder $query, array $criteria): Builder
    {
        $search = $criteria['search'] ?? null;
        $lpkId = $criteria['lpk_id'] ?? null;
        $assessmentType = $criteria['assessment_type'] ?? null;
        $status = $criteria['status'] ?? null;
        $tpFilter = $criteria['tp_status'] ?? null;
        $startFrom = $criteria['start_from'] ?? null;
        $startTo = $criteria['start_to'] ?? null;

        return $query
            ->when($search, function ($q) use ($search) {
                $terms = self::expandSearchTerms($search);
                $q->where(function ($sub) use ($terms, $search) {
                    foreach ($terms as $term) {
                        $sub->orWhere('title', 'like', "%{$term}%")
                            ->orWhere('assessment_type', 'like', "%{$term}%");
                    }
                    $sub->orWhere('sk_number', 'like', "%{$search}%")
                        ->orWhereHas('lpk', fn ($lpkQ) => $lpkQ
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%")
                            ->orWhere('no_reg', 'like', "%{$search}%")
                            ->orWhere('accreditation_number', 'like', "%{$search}%")
                        );
                });
            })
            ->when($lpkId, fn ($q) => $q->where('lpk_id', $lpkId))
            ->when($assessmentType, fn ($q) => $q->forAssessmentType($assessmentType))
            ->when($status, fn ($q, $st) => $q->forStatusFilter($st))
            ->when($tpFilter, function ($q) use ($tpFilter) {
                if ($tpFilter === 'OVERDUE') {
                    $q->tpOverdue();
                } elseif ($tpFilter === 'DUE_SOON') {
                    $q->tpDueSoon();
                } elseif ($tpFilter === 'ACTIVE') {
                    $q->tpActive();
                } else {
                    $q->where('tp_status', $tpFilter);
                }
            })
            ->when($startFrom, fn ($q) => $q->where('start_at', '>=', "{$startFrom} 00:00:00"))
            ->when($startTo, fn ($q) => $q->where('start_at', '<=', "{$startTo} 23:59:59"));
    }

    /**
     * Memperluas kata kunci pencarian dengan singkatan umum KAN (S1, S2, RA, STT, PRL)
     * agar pencarian berbasis singkatan tetap menemukan asesmen terkait.
     *
     * @return array<int, string>
     */
    public static function expandSearchTerms(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $terms = [$q];
        $qLower = strtolower($q);

        $mapping = [
            's1' => ['Surveilen 1', 'S1'],
            's2' => ['Surveilen 2', 'S2'],
            'ra' => ['Re-Akreditasi', 'Reakreditasi', 'Akreditasi Ulang', 'RA'],
            'stt' => ['Surveilen Tidak Terjadwal', 'STT'],
            'prl' => ['Perluasan Ruang Lingkup', 'PRL'],
        ];

        $phrases = [
            's1' => ['surveilen 1'],
            's2' => ['surveilen 2'],
            'ra' => ['re-akreditasi', 'reakreditasi', 'akreditasi ulang'],
            'stt' => ['tidak terjadwal'],
            'prl' => ['perluasan'],
        ];

        foreach ($mapping as $key => $synonyms) {
            $isAcronym = $qLower === $key || preg_match('/\b'.preg_quote($key, '/').'\b/i', $qLower);
            $isPhrase = false;
            foreach ($phrases[$key] ?? [] as $phrase) {
                if (str_contains($qLower, $phrase)) {
                    $isPhrase = true;
                    break;
                }
            }

            if ($isAcronym || $isPhrase) {
                array_push($terms, ...$synonyms);
            }
        }

        return array_values(array_unique($terms));
    }
}

