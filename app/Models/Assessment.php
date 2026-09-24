<?php

namespace App\Models;

use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    /**
     * 6 Skema Pelaksanaan Asesmen Resmi sesuai Dokumen KAN U-01 Rev.3
     */
    public const TYPES = [
        'Asesmen Awal' => 'Asesmen Awal (Initial Assessment)',
        'Surveilen' => 'Surveilen (Surveillance)',
        'Re-asesmen' => 'Re-asesmen (Reassessment)',
        'Audit Kecukupan' => 'Audit Kecukupan (Adequacy Audit)',
        'Penyaksian (Witness)' => 'Penyaksian (Witnessing)',
        'Perluasan Lingkup' => 'Perluasan Lingkup (Scope Extension)',
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

    protected $fillable = [
        'lpk_id',
        'created_by',
        'title',
        'assessment_type',
        'start_at',
        'end_at',
        'location',
        'status',
        'lead_assessor',
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
            'tp_due_date' => 'date',
            'tp_has_extension' => 'boolean',
            'tp_extension_months' => 'integer',
            'tp_extension_date' => 'date',
            'tp_satisfied_at' => 'date',
            'sk_date' => 'date',
        ];
    }

    public function getAssessmentTypeLabelAttribute(): string
    {
        return match ($this->assessment_type) {
            'INITIAL', 'Asesmen Awal' => 'Asesmen Awal (Initial)',
            'SURVEILLANCE', 'Surveilen' => 'Surveilen (Surveillance)',
            'REASSESSMENT', 'Re-asesmen' => 'Re-asesmen (Reassessment)',
            'Audit Kecukupan', 'ADEQUACY' => 'Audit Kecukupan (Adequacy)',
            'Penyaksian (Witness)', 'Penyaksian', 'WITNESS' => 'Penyaksian (Witness)',
            'Perluasan Lingkup', 'SCOPE_EXTENSION' => 'Perluasan Lingkup',
            default => $this->assessment_type ?: '-',
        };
    }

    /**
     * Batas akhir toleransi pengisian hasil asesmen:
     * Batas terakhir pada bulan dan tahun yang sama dengan waktu pelaksanaan/kunjungan (end_at).
     * Contoh: Kunjungan 18 April 2026 -> Batas akhir 30 April 2026 (pada tahun 2026 yang sama).
     */
    public function getSubmissionDueDateAttribute(): ?Carbon
    {
        if (! $this->end_at) {
            return null;
        }

        return $this->end_at->copy()->endOfMonth()->endOfDay();
    }

    /**
     * Cek apakah pengisian asesmen telah melewati batas toleransi bulan dan tahun kunjungan.
     * Jika sudah lewat dari akhir bulan waktu kunjungan dan belum berstatus COMPLETED, maka dikatakan sudah lewat jadwal asesmen.
     */
    public function getIsSubmissionOverdueAttribute(): bool
    {
        if (in_array($this->status, ['COMPLETED', 'CANCELLED'], true)) {
            return false;
        }

        $dueDate = $this->submission_due_date;
        if (! $dueDate) {
            return false;
        }

        return now()->gt($dueDate);
    }

    /**
     * Hitung batas waktu default TP & VTP sesuai regulasi KAN:
     * - Akreditasi Awal: 3 bulan
     * - Survailen, PRL, Re-asesmen, Audit Kecukupan: 2 bulan
     */
    public function calculateDefaultTpDueDate(): ?Carbon
    {
        if (! $this->end_at) {
            return null;
        }

        $isInitial = in_array($this->assessment_type, ['Asesmen Awal', 'INITIAL'], true);

        return $this->end_at->copy()->addMonths($isInitial ? 3 : 2)->startOfDay();
    }

    /**
     * Batas waktu efektif TP & VTP.
     * Mengakomodasi perpanjangan masa perbaikan maksimal 1 bulan jika ada surat permohonan resmi.
     */
    public function getEffectiveTpDueDateAttribute(): ?Carbon
    {
        $baseDate = $this->tp_due_date ?: $this->calculateDefaultTpDueDate();

        if (! $baseDate) {
            return null;
        }

        if ($this->tp_has_extension && $this->tp_extension_months > 0) {
            // Regulasi KAN membatasi perpanjangan maksimal 1 bulan
            $extensionMonths = min(1, max(1, (int) $this->tp_extension_months));
            return $baseDate->copy()->addMonths($extensionMonths)->startOfDay();
        }

        return $baseDate->copy()->startOfDay();
    }

    /**
     * Sisa hari menuju batas akhir efektif TP & VTP (positif = sisa hari, negatif = lewat jatuh tempo).
     */
    public function getDaysRemainingTpAttribute(): ?int
    {
        $effectiveDueDate = $this->effective_tp_due_date;

        if (! $effectiveDueDate) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($effectiveDueDate, false);
    }

    /**
     * Cek apakah TP & VTP melewati batas waktu yang ditetapkan KAN.
     */
    public function getIsTpOverdueAttribute(): bool
    {
        if (in_array($this->tp_status, [self::TP_STATUS_NONE, self::TP_STATUS_SATISFIED], true)) {
            return false;
        }

        $effectiveDueDate = $this->effective_tp_due_date;

        if (! $effectiveDueDate) {
            return false;
        }

        return now()->startOfDay()->gt($effectiveDueDate);
    }

    /**
     * Label status tindakan perbaikan dalam bahasa Indonesia.
     */
    public function getTpStatusLabelAttribute(): string
    {
        if (in_array($this->status, ['PLANNED', 'SCHEDULED'], true) && $this->tp_status === self::TP_STATUS_NONE) {
            return 'Menunggu Pelaksanaan Asesmen';
        }

        return self::TP_STATUSES[$this->tp_status] ?? ($this->tp_status ?: 'Nihil / Tidak Ada Temuan');
    }

    /**
     * Badge status SLA KAN terintegrasi untuk tampilan tabel dan detail.
     */
    public function getTpSlaBadgeAttribute(): array
    {
        if (in_array($this->status, ['PLANNED', 'SCHEDULED'], true) && $this->tp_status === self::TP_STATUS_NONE) {
            return [
                'type' => 'neutral',
                'label' => 'Belum Asesmen',
                'detail' => 'Kunjungan asesmen belum dilaksanakan (belum ada temuan KNC)',
            ];
        }

        if ($this->tp_status === self::TP_STATUS_NONE) {
            return [
                'type' => 'neutral',
                'label' => 'Nihil Temuan',
                'detail' => 'Tidak memerlukan perbaikan',
            ];
        }

        if ($this->tp_status === self::TP_STATUS_SATISFIED) {
            return [
                'type' => 'success',
                'label' => 'Memenuhi',
                'detail' => $this->tp_satisfied_at ? 'Dinyatakan pada ' . $this->tp_satisfied_at->format('d M Y') : 'Tindakan perbaikan diterima',
            ];
        }

        $days = $this->days_remaining_tp;

        if ($this->is_tp_overdue) {
            $overdueDays = abs($days ?? 0);
            return [
                'type' => 'danger',
                'label' => 'Terlambat ' . ($overdueDays > 0 ? $overdueDays . ' Hari' : ''),
                'detail' => 'Melewati batas akhir KAN (' . ($this->effective_tp_due_date ? $this->effective_tp_due_date->format('d M Y') : '-') . ')',
            ];
        }

        if ($days !== null && $days <= 14) {
            return [
                'type' => 'warning',
                'label' => 'Jatuh Tempo (' . $days . ' Hari)',
                'detail' => 'Batas akhir ' . ($this->effective_tp_due_date ? $this->effective_tp_due_date->format('d M Y') : '-'),
            ];
        }

        return [
            'type' => 'info',
            'label' => 'Dalam Proses' . ($this->tp_has_extension ? ' (+1 Bulan)' : ''),
            'detail' => 'Sisa ' . ($days ?? 0) . ' hari hingga ' . ($this->effective_tp_due_date ? $this->effective_tp_due_date->format('d M Y') : '-'),
        ];
    }

    /**
     * Hitung rentang durasi (dalam hari kalender) dari pelaksanaan asesmen hingga tanggal penerbitan SK.
     * Menggunakan tanggal selesai asesmen (end_at) atau tanggal mulai (start_at) sebagai titik awal.
     */
    public function getSkLeadTimeDaysAttribute(): ?int
    {
        $baseDate = $this->end_at ?? $this->start_at;
        if (! $baseDate || ! $this->sk_date) {
            return null;
        }

        return (int) $baseDate->copy()->startOfDay()->diffInDays($this->sk_date->copy()->startOfDay(), false);
    }

    /**
     * Label representatif untuk rentang waktu pelaksanaan asesmen sampai tanggal SK.
     * Contoh: "74 hari (2 bulan 14 hari)", "15 hari", atau "0 hari (pada hari pelaksanaan)".
     */
    public function getSkLeadTimeLabelAttribute(): ?string
    {
        $days = $this->sk_lead_time_days;
        if ($days === null) {
            return null;
        }

        if ($days < 0) {
            return abs($days) . ' hari sebelum asesmen';
        }

        if ($days === 0) {
            return '0 hari (pada hari pelaksanaan)';
        }

        $baseDate = ($this->end_at ?? $this->start_at)->copy()->startOfDay();
        $skDate = $this->sk_date->copy()->startOfDay();
        $diff = $baseDate->diff($skDate);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y . ' tahun';
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' bulan';
        }
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' hari';
        }

        $detailed = !empty($parts) ? implode(' ', $parts) : ($days . ' hari');

        if ($diff->y > 0 || $diff->m > 0) {
            return "{$days} hari ({$detailed})";
        }

        return "{$days} hari";
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
        });
    }

    public function scopeTpOverdue($query)
    {
        return $query->whereNotIn('tp_status', [self::TP_STATUS_NONE, self::TP_STATUS_SATISFIED])
            ->whereNotNull('tp_due_date')
            ->where(function ($q): void {
                $q->where('tp_has_extension', false)
                    ->whereDate('tp_due_date', '<', now())
                    ->orWhere(function ($sub): void {
                        $sub->where('tp_has_extension', true)
                            ->whereDate('tp_due_date', '<', now()->subMonth());
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

    public function expense(): HasOne
    {
        return $this->hasOne(AssessmentExpense::class);
    }
}
