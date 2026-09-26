<?php

namespace App\Models;

use App\Models\Assessment;
use App\Models\User;
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
        $activeAssessment = $this->getActiveOrUpcomingAssessment();

        if ($activeAssessment) {
            $typeLabel = $activeAssessment->assessment_type_label;
            $now = now();

            // 0. Status Dicabut (melewati batas 1 tahun pembekuan)
            if ($activeAssessment->status === 'REVOKED' || $activeAssessment->is_suspension_expired) {
                $deadline = $activeAssessment->suspension_resolution_deadline ? $activeAssessment->suspension_resolution_deadline->format('d/m/Y') : '-';
                return "{$typeLabel}: Akreditasi Dicabut (melewati batas 1 tahun masa pembekuan surveilen {$deadline}).";
            }

            // 1. Tindakan Perbaikan (TP) Aktif / Dibekukan
            if ($activeAssessment->tp_status && !in_array($activeAssessment->tp_status, [Assessment::TP_STATUS_NONE, Assessment::TP_STATUS_SATISFIED], true)) {
                $due = $activeAssessment->effective_tp_due_date ? $activeAssessment->effective_tp_due_date->format('d/m/Y') : '-';

                if ($activeAssessment->is_tp_overdue || $activeAssessment->status === 'SUSPENDED') {
                    return "{$typeLabel}: Dibekukan (melewati batas waktu SLA {$due} belum memenuhi).";
                }

                if ($activeAssessment->tp_has_extension) {
                    return "{$typeLabel}: Perpanjangan TP s/d {$due}.";
                }

                if ($activeAssessment->days_remaining_tp !== null && $activeAssessment->days_remaining_tp <= 14) {
                    return "{$typeLabel}: Batas TP segera berakhir ({$activeAssessment->days_remaining_tp} hari s/d {$due}).";
                }

                if ($activeAssessment->tp_status === Assessment::TP_STATUS_UNDER_VERIFICATION) {
                    return "{$typeLabel}: Verifikasi TP oleh Asesor.";
                }

                return "{$typeLabel}: Penyusunan TP (batas: {$due}).";
            }

            // Jika status asesmen SUSPENDED atau melewati batas toleransi pengisian
            if ($activeAssessment->status === 'SUSPENDED' || $activeAssessment->is_tp_overdue || $activeAssessment->is_submission_overdue) {
                if ($activeAssessment->is_submission_overdue) {
                    $due = $activeAssessment->submission_due_date ? $activeAssessment->submission_due_date->format('d/m/Y') : '-';
                    $deadline = $activeAssessment->suspension_resolution_deadline ? $activeAssessment->suspension_resolution_deadline->format('d/m/Y') : '-';
                    $daysLeft = $activeAssessment->days_remaining_suspension ?? 0;
                    return "{$typeLabel}: Dibekukan (toleransi pengisian {$due} terlampaui. Sisa {$daysLeft} hari kesempatan penyelesaian s/d {$deadline}).";
                }

                $due = $activeAssessment->effective_tp_due_date ? $activeAssessment->effective_tp_due_date->format('d/m/Y') : '-';
                return "{$typeLabel}: Dibekukan (melewati batas waktu SLA {$due} belum memenuhi).";
            }

            // 2. SK Akreditasi KAN telah terbit
            if (!empty($activeAssessment->sk_number)) {
                $skDateStr = $activeAssessment->sk_date ? ' tgl ' . $activeAssessment->sk_date->format('d/m/Y') : '';
                return "{$typeLabel}: Selesai (SK No. {$activeAssessment->sk_number}{$skDateStr}).";
            }

            // 3. Status TP Memenuhi (Selesai sebelum SK terbit)
            if ($activeAssessment->tp_status === Assessment::TP_STATUS_SATISFIED) {
                return "{$typeLabel}: Selesai (TP Memenuhi).";
            }

            // 4. Tahap Evaluasi Hasil Asesmen (EHA)
            if (!empty($activeAssessment->eha_status) && $activeAssessment->eha_status !== Assessment::EHA_STATUS_BELUM) {
                return "{$typeLabel}: Evaluasi Hasil Asesmen ({$activeAssessment->eha_status_label}).";
            }

            // 5. Laporan asesmen telah terbit, menunggu EHA
            if ($activeAssessment->report_date) {
                return "{$typeLabel}: Laporan terbit ({$activeAssessment->report_date->format('d/m/Y')}), proses EHA.";
            }

            // 6. Sedang berlangsung (kunjungan lapangan)
            if ($activeAssessment->start_at && $activeAssessment->end_at && $now->between($activeAssessment->start_at, $activeAssessment->end_at)) {
                return "{$typeLabel}: Sedang berlangsung (kunjungan asesmen).";
            }

            // 7. Tanggal pelaksanaan telah lewat namun belum ada tindakan / pelaporan
            if ($activeAssessment->end_at && $now->gt($activeAssessment->end_at) && $activeAssessment->status !== 'COMPLETED') {
                return "{$typeLabel}: Lewat jadwal (rencana: {$activeAssessment->start_at->format('d/m/Y')}).";
            }

            // 8. Terjadwal di masa mendatang
            if ($activeAssessment->start_at) {
                return "{$typeLabel}: Terjadwal {$activeAssessment->start_at->format('d/m/Y')}.";
            }
        }

        // 2. Jika tidak ada agenda asesmen, cek siklus pengawasan KAN
        $alerts = $this->getActiveSurveillanceAlerts();
        if (!empty($alerts)) {
            $firstAlert = reset($alerts);
            $milestoneName = match ($firstAlert['code'] ?? '') {
                'S1' => Assessment::TYPE_SURVEILEN_1,
                'S2' => Assessment::TYPE_SURVEILEN_2,
                'RA' => Assessment::TYPE_RE_AKREDITASI,
                default => $firstAlert['name'] ?? Assessment::TYPE_SURVEILEN_1,
            };
            $target = !empty($firstAlert['target_date']) ? ' (' . $firstAlert['target_date']->format('d/m/Y') . ')' : '';
            return "{$milestoneName}: Periode jatuh tempo KAN{$target}.";
        }

        // 3. Cek masa berlaku sertifikat
        if ($this->isExpired()) {
            $exp = $this->expired_at ? $this->expired_at->format('d/m/Y') : '-';
            return Assessment::TYPE_RE_AKREDITASI . ": Sertifikat kedaluwarsa ({$exp}).";
        }

        if ($this->isExpiringSoon()) {
            $exp = $this->expired_at ? $this->expired_at->format('d/m/Y') : '-';
            return Assessment::TYPE_RE_AKREDITASI . ": Masa berlaku berakhir {$exp}.";
        }

        return "Aktif normal" . ($this->expired_at ? " (s/d " . $this->expired_at->format('d/m/Y') . ")" : "") . ".";
    }

    /**
     * Otomatis membuat agenda asesmen surveilen (S1, S2) dan Re-Akreditasi (RA)
     * berdasarkan tanggal terbit sertifikat akreditasi LPK sesuai siklus KAN U-01.
     */
    public function generateSurveillanceAssessments(?int $creatorId = null): int
    {
        /** @var Carbon|null $certDate */
        $certDate = $this->certificate_date ?: ($this->expired_at ? $this->expired_at->copy()->subYears(5) : null);
        if (! $certDate) {
            return 0;
        }

        $creatorId = $creatorId
            ?: auth()->id()
            ?: User::where('role', User::ROLE_ADMIN)->value('id')
            ?: User::value('id');

        if (! $creatorId) {
            return 0;
        }

        $created = 0;

        $now = now();
        $isLpkActive = $this->getRawOriginal('status') === 'ACTIVE';

        // 1. Asesmen Surveilen 1 (S1): Bulan 15 (Target Kunjungan Bulan 15-18)
        $s1Target = $certDate->copy()->addMonths(15)->startOfDay()->setHour(9);
        $s1End = $s1Target->copy()->addDays(2)->setHour(17);
        $s1IsPast = $isLpkActive && $s1End->lt($now->copy()->startOfYear());

        $s1Assessment = $this->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Surveilen 1%')
                    ->orWhere('title', 'like', '%(S1)%')
                    ->orWhere('title', 'like', '% S1 %')
                    ->orWhere('assessment_type', Assessment::TYPE_SURVEILEN_1);
            })->first();

        if (! $s1Assessment) {
            $this->assessments()->create([
                'created_by' => $creatorId,
                'title' => "Asesmen Surveilen 1 (S1) - {$this->name}",
                'assessment_type' => Assessment::TYPE_SURVEILEN_1,
                'start_at' => $s1Target,
                'end_at' => $s1End,
                'location' => $this->address ?: 'Kantor / Fasilitas LPK',
                'status' => $s1IsPast ? 'COMPLETED' : 'PLANNED',
                'tp_status' => $s1IsPast ? Assessment::TP_STATUS_SATISFIED : Assessment::TP_STATUS_NONE,
                'tp_satisfied_at' => $s1IsPast ? $s1End->copy()->addMonth()->startOfDay() : null,
                'notes' => $s1IsPast
                    ? 'Agenda asesmen surveilen berkala tahun pertama telah selesai dan terealisasi.'
                    : 'Agenda asesmen surveilen berkala tahun pertama otomatis dijadwalkan sesuai siklus KAN (Bulan ke-15).',
            ]);
            $created++;
        } elseif ($s1Assessment->status === 'PLANNED') {
            $s1Assessment->update([
                'start_at' => $s1Target,
                'end_at' => $s1End,
                'status' => $s1IsPast ? 'COMPLETED' : 'PLANNED',
                'tp_status' => $s1IsPast ? Assessment::TP_STATUS_SATISFIED : $s1Assessment->tp_status,
                'tp_satisfied_at' => $s1IsPast ? ($s1Assessment->tp_satisfied_at ?: $s1End->copy()->addMonth()->startOfDay()) : $s1Assessment->tp_satisfied_at,
            ]);
        }

        // 2. Asesmen Surveilen 2 (S2): Bulan 36 (Target Kunjungan Bulan 36-39)
        $s2Target = $certDate->copy()->addMonths(36)->startOfDay()->setHour(9);
        $s2End = $s2Target->copy()->addDays(2)->setHour(17);
        $s2IsPast = $isLpkActive && $s2End->lt($now->copy()->startOfYear());

        $s2Assessment = $this->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Surveilen 2%')
                    ->orWhere('title', 'like', '%(S2)%')
                    ->orWhere('title', 'like', '% S2 %')
                    ->orWhere('assessment_type', Assessment::TYPE_SURVEILEN_2);
            })->first();

        if (! $s2Assessment) {
            $this->assessments()->create([
                'created_by' => $creatorId,
                'title' => "Asesmen Surveilen 2 (S2) - {$this->name}",
                'assessment_type' => Assessment::TYPE_SURVEILEN_2,
                'start_at' => $s2Target,
                'end_at' => $s2End,
                'location' => $this->address ?: 'Kantor / Fasilitas LPK',
                'status' => $s2IsPast ? 'COMPLETED' : 'PLANNED',
                'tp_status' => $s2IsPast ? Assessment::TP_STATUS_SATISFIED : Assessment::TP_STATUS_NONE,
                'tp_satisfied_at' => $s2IsPast ? $s2End->copy()->addMonth()->startOfDay() : null,
                'notes' => $s2IsPast
                    ? 'Agenda asesmen surveilen berkala tahun ketiga telah selesai dan terealisasi.'
                    : 'Agenda asesmen surveilen berkala tahun ketiga otomatis dijadwalkan sesuai siklus KAN (Bulan ke-36).',
            ]);
            $created++;
        } elseif ($s2Assessment->status === 'PLANNED') {
            $s2Assessment->update([
                'start_at' => $s2Target,
                'end_at' => $s2End,
                'status' => $s2IsPast ? 'COMPLETED' : 'PLANNED',
                'tp_status' => $s2IsPast ? Assessment::TP_STATUS_SATISFIED : $s2Assessment->tp_status,
                'tp_satisfied_at' => $s2IsPast ? ($s2Assessment->tp_satisfied_at ?: $s2End->copy()->addMonth()->startOfDay()) : $s2Assessment->tp_satisfied_at,
            ]);
        }

        // 3. Re-Akreditasi (RA): Bulan 54 (H-6 bulan sebelum masa berlaku 60 bulan berakhir)
        $raTarget = $certDate->copy()->addMonths(54)->startOfDay()->setHour(9);
        $raEnd = $raTarget->copy()->addDays(3)->setHour(17);
        $raIsPast = $isLpkActive && $raEnd->lt($now->copy()->startOfYear());

        $raAssessment = $this->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Re-asesmen%')
                    ->orWhere('title', 'like', '%Re-Akreditasi%')
                    ->orWhere('title', 'like', '%(RA)%')
                    ->orWhere('title', 'like', '% RA %')
                    ->orWhereIn('assessment_type', [Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT']);
            })->first();

        if (! $raAssessment) {
            $this->assessments()->create([
                'created_by' => $creatorId,
                'title' => "Asesmen Re-Akreditasi (RA) - {$this->name}",
                'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
                'start_at' => $raTarget,
                'end_at' => $raEnd,
                'location' => $this->address ?: 'Kantor / Fasilitas LPK',
                'status' => $raIsPast ? 'COMPLETED' : 'PLANNED',
                'tp_status' => $raIsPast ? Assessment::TP_STATUS_SATISFIED : Assessment::TP_STATUS_NONE,
                'tp_satisfied_at' => $raIsPast ? $raEnd->copy()->addMonth()->startOfDay() : null,
                'notes' => $raIsPast
                    ? 'Agenda asesmen Re-Akreditasi telah selesai dan terealisasi.'
                    : 'Agenda asesmen Re-Akreditasi (Akreditasi Ulang) otomatis dijadwalkan sesuai siklus KAN (Bulan ke-54, H-6 bulan sebelum masa berlaku berakhir).',
            ]);
            $created++;
        } elseif ($raAssessment->status === 'PLANNED') {
            $raAssessment->update([
                'start_at' => $raTarget,
                'end_at' => $raEnd,
                'status' => $raIsPast ? 'COMPLETED' : 'PLANNED',
                'tp_status' => $raIsPast ? Assessment::TP_STATUS_SATISFIED : $raAssessment->tp_status,
                'tp_satisfied_at' => $raIsPast ? ($raAssessment->tp_satisfied_at ?: $raEnd->copy()->addMonth()->startOfDay()) : $raAssessment->tp_satisfied_at,
            ]);
        }

        return $created;
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

        return $this->expired_at->isFuture() && $this->expired_at->lte(now()->addDays($days));
    }

    /**
     * Menghitung status dinamis akreditasi LPK berdasarkan kepatuhan siklus pengawasan KAN & masa berlaku sertifikat.
     * Mengembalikan:
     * - 'INACTIVE': Jika dinonaktifkan secara manual oleh admin
     * - 'REVOKED': Jika status akreditasi dicabut (lewat 1 tahun pembekuan)
     * - 'SUSPENDED': Jika dibekukan (lewat SLA / toleransi pengisian surveilen)
     * - 'EXPIRED': Jika masa berlaku sertifikat akreditasi telah habis
     * - 'SURVEILLANCE_OVERDUE': Jika telah melewati batas target jadwal surveilen tanpa agenda asesmen
     * - 'SURVEILLANCE_DUE': Jika berada dalam masa aktif notifikasi surveilen / re-akreditasi
     * - 'ACTIVE': Jika status aktif dan seluruh jadwal pengawasan aman / terpenuhi
     */
    public function getDynamicStatusAttribute(): string
    {
        if ($this->status === 'INACTIVE') {
            return 'INACTIVE';
        }

        if ($this->status === 'REVOKED' || $this->status === 'DICABUT') {
            return 'REVOKED';
        }

        if ($this->status === 'SUSPENDED') {
            return 'SUSPENDED';
        }

        if ($this->isExpired()) {
            return 'EXPIRED';
        }

        $assessmentsList = $this->relationLoaded('assessments') ? $this->assessments : $this->assessments()->get();

        // 1. Cek apakah ada asesmen yang statusnya REVOKED atau telah melewati batas 1 tahun pembekuan tanpa penyelesaian
        if ($assessmentsList->contains(fn (Assessment $a) => $a->status === 'REVOKED' || $a->is_suspension_expired)) {
            return 'REVOKED';
        }

        // 2. Cek apakah ada asesmen yang berstatus SUSPENDED atau melewati batas waktu toleransi pengisian / SLA TP
        if ($assessmentsList->contains(function (Assessment $a) {
            if ($a->isPastSurveillanceForActiveLpk()) {
                return false;
            }
            if ($a->status === 'SUSPENDED' || $a->is_tp_overdue) {
                return true;
            }
            // Toleransi pengisian untuk asesmen yang sedang berjalan/terlaksana
            if ($a->is_submission_overdue && ($a->status !== 'PLANNED' || ! empty($a->report_date) || ! empty($a->eha_date))) {
                return true;
            }
            return false;
        })) {
            return 'SUSPENDED';
        }

        $milestones = $this->surveillance_milestones;

        // Cek apakah ada surveilen yang melewati target waktu tanpa pelaksanaan asesmen
        $isOverdue = ($milestones['s1']['status'] ?? '') === 'OVERDUE'
            || ($milestones['s2']['status'] ?? '') === 'OVERDUE';

        if ($isOverdue) {
            return 'SURVEILLANCE_OVERDUE';
        }

        // Cek apakah ada notifikasi surveilen yang sedang aktif (due)
        $isDue = ($milestones['s1']['status'] ?? '') === 'DUE'
            || ($milestones['s2']['status'] ?? '') === 'DUE'
            || ($milestones['ra']['status'] ?? '') === 'DUE';

        if ($isDue) {
            return 'SURVEILLANCE_DUE';
        }

        return 'ACTIVE';
    }

    /**
     * Label representasi status akreditasi dinamis LPK.
     */
    public function getDynamicStatusLabelAttribute(): string
    {
        return match ($this->dynamic_status) {
            'INACTIVE' => 'Tidak Aktif',
            'REVOKED' => 'Dicabut',
            'SUSPENDED' => 'Dibekukan',
            'EXPIRED' => 'Kedaluwarsa',
            'SURVEILLANCE_OVERDUE' => 'Lewat Jadwal Surveilen',
            'SURVEILLANCE_DUE' => 'Jatuh Tempo Surveilen',
            default => 'Aktif',
        };
    }

    /**
     * Hitung jadwal acuan siklus pengawasan KAN (S1, S2, dan Re-Akreditasi).
     * S1: Surveilen 1 pada bulan 15-18 (notifikasi email bulan 14).
     * S2: Surveilen 2 pada bulan 36-39 (notifikasi email bulan 35).
     * RA: Upload dokumen mulai bulan 48 (maks. bulan 51 lengkap), asesmen RA maks. bulan 54.
     */
    public function getSurveillanceMilestonesAttribute(): array
    {
        /** @var Carbon|null $certDate */
        $certDate = $this->certificate_date ?: ($this->expired_at ? $this->expired_at->copy()->subYears(5) : null);
        /** @var Carbon|null $expDate */
        $expDate = $this->expired_at ?: ($certDate ? $certDate->copy()->addYears(5) : null);

        $milestones = [
            's1' => [
                'code' => 'S1',
                'name' => Assessment::TYPE_SURVEILEN_1,
                'notice_date' => $certDate ? $certDate->copy()->addMonths(13) : null,
                'target_date' => $certDate ? $certDate->copy()->addMonths(18) : null,
                'tolerance_date' => $certDate ? $certDate->copy()->addMonths(24) : null,
                'description' => 'Reminder S1 bulan ke-13, Jatuh Tempo (JT) S1 bulan ke-18 (toleransi kunjungan maks 2 tahun/bulan 24)',
                'status' => 'PENDING',
            ],
            's2' => [
                'code' => 'S2',
                'name' => Assessment::TYPE_SURVEILEN_2,
                'notice_date' => $certDate ? $certDate->copy()->addMonths(34) : null,
                'target_date' => $certDate ? $certDate->copy()->addMonths(39) : null,
                'tolerance_date' => $certDate ? $certDate->copy()->addMonths(48) : null,
                'description' => 'Reminder S2 bulan ke-34, Jatuh Tempo (JT) S2 bulan ke-39 (toleransi kunjungan maks 2 tahun)',
                'status' => 'PENDING',
            ],
            'ra' => [
                'code' => 'RA',
                'name' => Assessment::TYPE_RE_AKREDITASI,
                'notice_date' => $certDate ? $certDate->copy()->addMonths(48) : ($expDate ? $expDate->copy()->subMonths(12) : null),
                'target_date' => $certDate ? $certDate->copy()->addMonths(54) : ($expDate ? $expDate->copy()->subMonths(6) : null),
                'tolerance_date' => $expDate,
                'description' => 'Reminder RA bulan ke-48, Jatuh Tempo (JT) RA bulan ke-54 (masa berlaku habis bulan ke-60)',
                'status' => 'PENDING',
            ],
        ];

        if (! $certDate && ! $expDate) {
            return $milestones;
        }

        $now = now();

        // Cek asesmen yang sudah pernah dibuat untuk LPK ini
        $assessments = $this->relationLoaded('assessments') ? $this->assessments : $this->assessments()->get();

        // Cek S1
        if ($milestones['s1']['notice_date']) {
            $hasS1 = $assessments->contains(function ($item) use ($certDate, $now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                $isSurv = str_contains(strtolower($item->assessment_type ?: ''), 'survei') || str_contains(strtolower($item->title ?: ''), 's1');
                $isWithinRange = $item->start_at && $item->start_at->gte($certDate->copy()->addMonths(10)) && $item->start_at->lte($certDate->copy()->addMonths(24));

                // Toleransi pengisian asesmen maksimal hingga akhir bulan dan tahun yang sama dari waktu kunjungan
                if ($item->end_at && $now->gt($item->end_at->copy()->endOfMonth()->endOfDay()) && $item->status !== 'COMPLETED') {
                    return false;
                }

                return $isSurv && ($isWithinRange || str_contains(strtolower($item->title ?: ''), 's1'));
            });

            if ($hasS1) {
                $milestones['s1']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($now->gte($milestones['s1']['target_date'])) {
                $milestones['s1']['status'] = 'OVERDUE';
            } elseif ($now->gte($milestones['s1']['notice_date'])) {
                $milestones['s1']['status'] = 'DUE';
            } else {
                $milestones['s1']['status'] = 'UPCOMING';
            }
        }

        // Cek S2
        if ($milestones['s2']['notice_date']) {
            $hasS2 = $assessments->contains(function ($item) use ($certDate, $now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                $isSurv = str_contains(strtolower($item->assessment_type ?: ''), 'survei') || str_contains(strtolower($item->title ?: ''), 's2');
                $isWithinRange = $item->start_at && $item->start_at->gte($certDate->copy()->addMonths(25)) && $item->start_at->lte($certDate->copy()->addMonths(44));

                // Toleransi pengisian asesmen maksimal hingga akhir bulan dan tahun yang sama dari waktu kunjungan
                if ($item->end_at && $now->gt($item->end_at->copy()->endOfMonth()->endOfDay()) && $item->status !== 'COMPLETED') {
                    return false;
                }

                return $isSurv && ($isWithinRange || str_contains(strtolower($item->title ?: ''), 's2'));
            });

            if ($hasS2) {
                $milestones['s2']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($now->gte($milestones['s2']['target_date'])) {
                $milestones['s2']['status'] = 'OVERDUE';
            } elseif ($now->gte($milestones['s2']['notice_date'])) {
                $milestones['s2']['status'] = 'DUE';
            } else {
                $milestones['s2']['status'] = 'UPCOMING';
            }
        }

        // Cek RA
        if ($milestones['ra']['notice_date']) {
            $hasRA = $assessments->contains(function ($item) use ($now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                // Toleransi pengisian asesmen maksimal hingga akhir bulan dan tahun yang sama dari waktu kunjungan
                if ($item->end_at && $now->gt($item->end_at->copy()->endOfMonth()->endOfDay()) && $item->status !== 'COMPLETED') {
                    return false;
                }

                return str_contains(strtolower($item->assessment_type ?: ''), 're-') || str_contains(strtolower($item->title ?: ''), 're-akreditasi') || str_contains(strtolower($item->title ?: ''), 'ra');
            });

            if ($hasRA) {
                $milestones['ra']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($expDate && $now->gte($expDate)) {
                $milestones['ra']['status'] = 'EXPIRED';
            } elseif ($now->gte($milestones['ra']['notice_date'])) {
                $milestones['ra']['status'] = 'DUE';
            } else {
                $milestones['ra']['status'] = 'UPCOMING';
            }
        }

        return $milestones;
    }

    /**
     * Dapatkan daftar notifikasi persisten yang sedang aktif dan tidak boleh diabaikan.
     */
    public function getActiveSurveillanceAlerts(): array
    {
        $alerts = [];
        $milestones = $this->surveillance_milestones;

        foreach ($milestones as $key => $milestone) {
            if (in_array($milestone['status'], ['DUE', 'OVERDUE', 'EXPIRED'], true)) {
                $isUrgent = in_array($milestone['status'], ['OVERDUE', 'EXPIRED'], true);

                $label = match ($milestone['status']) {
                    'OVERDUE' => 'MELEWATI JADWAL',
                    'EXPIRED' => 'SERTIFIKAT KEDALUWARSA',
                    default => 'WAKTU NOTIFIKASI AKTIF',
                };

                $alerts[] = [
                    'lpk_id' => $this->id,
                    'lpk_reg' => $this->registration_number,
                    'lpk_name' => $this->name,
                    'lpk_email' => $this->email,
                    'code' => $milestone['code'],
                    'name' => $milestone['name'],
                    'notice_date' => $milestone['notice_date'],
                    'target_date' => $milestone['target_date'],
                    'status' => $milestone['status'],
                    'status_label' => $label,
                    'is_urgent' => $isUrgent,
                    'severity' => $isUrgent ? 'danger' : 'warning',
                    'description' => $milestone['description'],
                    'last_notified_at' => $this->last_surveillance_notified_at,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Mengecek apakah LPK memiliki minimal satu notifikasi pengawasan aktif.
     */
    public function hasActiveSurveillanceAlert(): bool
    {
        return count($this->getActiveSurveillanceAlerts()) > 0;
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
}
