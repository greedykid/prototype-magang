<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Support\Carbon;

class LpkSurveillanceService
{
    /**
     * Hitung jadwal acuan siklus pengawasan KAN (S1, S2, dan Re-Akreditasi).
     * S1: Surveilen 1 pada bulan 15-18 (notifikasi email bulan 13/14).
     * S2: Surveilen 2 pada bulan 36-39 (notifikasi email bulan 34/35).
     * RA: Upload dokumen mulai bulan 48 (maks. bulan 51 lengkap), asesmen RA maks. bulan 54.
     */
    public function calculateMilestones(Lpk $lpk): array
    {
        /** @var Carbon|null $certDate */
        $certDate = $lpk->certificate_date ?: ($lpk->expired_at ? $lpk->expired_at->copy()->subYears(5) : null);
        /** @var Carbon|null $expDate */
        $expDate = $lpk->expired_at ?: ($certDate ? $certDate->copy()->addYears(5) : null);

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
        $assessments = $lpk->relationLoaded('assessments') ? $lpk->assessments : $lpk->assessments()->get();
        foreach ($assessments as $item) {
            if (! $item->relationLoaded('lpk')) {
                $item->setRelation('lpk', $lpk);
            }
        }

        // Cek S1
        if ($milestones['s1']['notice_date']) {
            $hasS1 = $assessments->contains(function ($item) use ($certDate, $now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                $isSurv = str_contains(strtolower($item->assessment_type ?: ''), 'survei') || str_contains(strtolower($item->title ?: ''), 's1');
                $isWithinRange = $item->start_at && $item->start_at->gte($certDate->copy()->subMonths(2)) && $item->start_at->lte($certDate->copy()->addMonths(26));

                // Toleransi pengisian asesmen maksimal hingga akhir bulan dan tahun yang sama dari waktu kunjungan
                if ($item->end_at && $now->gt($item->end_at->copy()->endOfMonth()->endOfDay()) && $item->status !== 'COMPLETED') {
                    return false;
                }

                return $isSurv && $isWithinRange;
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
                $isWithinRange = $item->start_at && $item->start_at->gte($certDate->copy()->addMonths(24)) && $item->start_at->lte($certDate->copy()->addMonths(46));

                // Toleransi pengisian asesmen maksimal hingga akhir bulan dan tahun yang sama dari waktu kunjungan
                if ($item->end_at && $now->gt($item->end_at->copy()->endOfMonth()->endOfDay()) && $item->status !== 'COMPLETED') {
                    return false;
                }

                return $isSurv && $isWithinRange;
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
            $hasRA = $assessments->contains(function ($item) use ($certDate, $expDate, $now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                // Toleransi pengisian asesmen maksimal hingga akhir bulan dan tahun yang sama dari waktu kunjungan
                if ($item->end_at && $now->gt($item->end_at->copy()->endOfMonth()->endOfDay()) && $item->status !== 'COMPLETED') {
                    return false;
                }

                $isRa = str_contains(strtolower($item->assessment_type ?: ''), 're-') || str_contains(strtolower($item->title ?: ''), 're-akreditasi') || str_contains(strtolower($item->title ?: ''), 'ra');
                $isWithinRange = $item->start_at && $item->start_at->gte($certDate->copy()->addMonths(42)) && (! $expDate || $item->start_at->lte($expDate->copy()->addMonths(6)));

                return $isRa && $isWithinRange;
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
     * Dapatkan daftar notifikasi persisten yang sedang aktif.
     */
    public function getActiveAlerts(Lpk $lpk): array
    {
        $alerts = [];
        $milestones = $this->calculateMilestones($lpk);

        foreach ($milestones as $milestone) {
            if (in_array($milestone['status'], ['DUE', 'OVERDUE', 'EXPIRED'], true)) {
                $isUrgent = in_array($milestone['status'], ['OVERDUE', 'EXPIRED'], true);

                $label = match ($milestone['status']) {
                    'OVERDUE' => 'MELEWATI JADWAL',
                    'EXPIRED' => 'SERTIFIKAT KEDALUWARSA',
                    default => 'WAKTU NOTIFIKASI AKTIF',
                };

                $alerts[] = [
                    'lpk_id' => $lpk->id,
                    'lpk_reg' => $lpk->registration_number,
                    'lpk_name' => $lpk->name,
                    'lpk_email' => $lpk->email,
                    'code' => $milestone['code'],
                    'name' => $milestone['name'],
                    'notice_date' => $milestone['notice_date'],
                    'target_date' => $milestone['target_date'],
                    'status' => $milestone['status'],
                    'status_label' => $label,
                    'is_urgent' => $isUrgent,
                    'severity' => $isUrgent ? 'danger' : 'warning',
                    'description' => $milestone['description'],
                    'last_notified_at' => $lpk->last_surveillance_notified_at,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Mengecek apakah LPK memiliki minimal satu notifikasi pengawasan aktif.
     */
    public function hasActiveAlert(Lpk $lpk): bool
    {
        return count($this->getActiveAlerts($lpk)) > 0;
    }

    /**
     * Otomatis membuat agenda asesmen surveilen (S1, S2) dan Re-Akreditasi (RA)
     * berdasarkan tanggal terbit sertifikat akreditasi LPK sesuai siklus KAN U-01.
     */
    public function generateAssessments(Lpk $lpk, ?int $creatorId = null): int
    {
        /** @var Carbon|null $certDate */
        $certDate = $lpk->certificate_date ?: ($lpk->expired_at ? $lpk->expired_at->copy()->subYears(5) : null);
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
        $isLpkActive = $lpk->getRawOriginal('status') === 'ACTIVE';

        // 1. Asesmen Surveilen 1 (S1): Bulan 15 (Target Kunjungan Bulan 15-18)
        $cycleStart = $certDate->copy()->subMonths(3);
        $cycleEnd = $certDate->copy()->addYears(5)->addMonths(3);

        $s1Target = $certDate->copy()->addMonths(15)->startOfDay()->setHour(9);
        $s1End = $s1Target->copy()->addDays(2)->setHour(17);
        $s1IsPast = $isLpkActive && $s1End->lt($now->copy()->startOfYear());

        $s1Assessment = $lpk->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Surveilen 1%')
                    ->orWhere('title', 'like', '%(S1)%')
                    ->orWhere('title', 'like', '% S1 %')
                    ->orWhere('assessment_type', Assessment::TYPE_SURVEILEN_1);
            })
            ->where('start_at', '>=', $cycleStart)
            ->where('start_at', '<=', $cycleEnd)
            ->first();

        if (! $s1Assessment) {
            $lpk->assessments()->create([
                'created_by' => $creatorId,
                'title' => "Asesmen Surveilen 1 (S1) - {$lpk->name}",
                'assessment_type' => Assessment::TYPE_SURVEILEN_1,
                'start_at' => $s1Target,
                'end_at' => $s1End,
                'location' => $lpk->address ?: 'Kantor / Fasilitas LPK',
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

        $s2Assessment = $lpk->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Surveilen 2%')
                    ->orWhere('title', 'like', '%(S2)%')
                    ->orWhere('title', 'like', '% S2 %')
                    ->orWhere('assessment_type', Assessment::TYPE_SURVEILEN_2);
            })
            ->where('start_at', '>=', $cycleStart)
            ->where('start_at', '<=', $cycleEnd)
            ->first();

        if (! $s2Assessment) {
            $lpk->assessments()->create([
                'created_by' => $creatorId,
                'title' => "Asesmen Surveilen 2 (S2) - {$lpk->name}",
                'assessment_type' => Assessment::TYPE_SURVEILEN_2,
                'start_at' => $s2Target,
                'end_at' => $s2End,
                'location' => $lpk->address ?: 'Kantor / Fasilitas LPK',
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

        $raAssessment = $lpk->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Re-asesmen%')
                    ->orWhere('title', 'like', '%Re-Akreditasi%')
                    ->orWhere('title', 'like', '%(RA)%')
                    ->orWhere('title', 'like', '% RA %')
                    ->orWhereIn('assessment_type', [Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT']);
            })
            ->where('start_at', '>=', $cycleStart)
            ->where('start_at', '<=', $cycleEnd)
            ->first();

        if (! $raAssessment) {
            $lpk->assessments()->create([
                'created_by' => $creatorId,
                'title' => "Asesmen Re-Akreditasi (RA) - {$lpk->name}",
                'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
                'start_at' => $raTarget,
                'end_at' => $raEnd,
                'location' => $lpk->address ?: 'Kantor / Fasilitas LPK',
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

    /**
     * Menghitung status dinamis akreditasi LPK berdasarkan kepatuhan siklus pengawasan KAN & masa berlaku sertifikat.
     */
    public function determineDynamicStatus(Lpk $lpk): string
    {
        if ($lpk->status === 'INACTIVE') {
            return 'INACTIVE';
        }

        if ($lpk->status === 'REVOKED' || $lpk->status === 'DICABUT') {
            return 'REVOKED';
        }

        if ($lpk->status === 'SUSPENDED') {
            return 'SUSPENDED';
        }

        if ($lpk->isExpired()) {
            return 'EXPIRED';
        }

        $assessmentsList = $lpk->relationLoaded('assessments') ? $lpk->assessments : $lpk->assessments()->get();
        foreach ($assessmentsList as $item) {
            if (! $item->relationLoaded('lpk')) {
                $item->setRelation('lpk', $lpk);
            }
        }

        // 1. Cek apakah ada asesmen yang statusnya REVOKED atau telah melewati batas 1 tahun pembekuan tanpa penyelesaian
        if ($assessmentsList->contains(fn (Assessment $a) => $a->status === 'REVOKED' || $a->is_suspension_expired)) {
            return 'REVOKED';
        }

        // 2. Cek apakah ada asesmen yang berstatus SUSPENDED atau melewati batas waktu toleransi pengisian / batas waktu TP
        if ($assessmentsList->contains(function (Assessment $a) {
            if ($a->isPastSurveillanceForActiveLpk()) {
                return false;
            }
            if ($a->status === 'SUSPENDED' || $a->is_tp_overdue) {
                return true;
            }
            if ($a->is_submission_overdue && ($a->status !== 'PLANNED' || ! empty($a->report_date) || ! empty($a->eha_date))) {
                return true;
            }
            return false;
        })) {
            return 'SUSPENDED';
        }

        $milestones = $this->calculateMilestones($lpk);

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
    public function determineDynamicStatusLabel(Lpk $lpk): string
    {
        return match ($this->determineDynamicStatus($lpk)) {
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
     * Rangkuman status operasional otomatis berdasarkan asesmen, batas TP, dan siklus KAN.
     */
    public function generateDynamicKeterangan(Lpk $lpk): string
    {
        $activeAssessment = $lpk->getActiveOrUpcomingAssessment();

        if ($activeAssessment) {
            $typeLabel = $activeAssessment->assessment_type_label;
            $now = now();

            // 0. Status Dicabut (melewati batas 1 tahun pembekuan)
            if ($activeAssessment->status === 'REVOKED' || $activeAssessment->is_suspension_expired) {
                $deadline = $activeAssessment->suspension_resolution_deadline ? $activeAssessment->suspension_resolution_deadline->format('d/m/Y') : '-';
                return "{$typeLabel}: Akreditasi Dicabut (melewati batas 1 tahun masa pembekuan surveilen {$deadline}).";
            }

            // 1. Tindakan Perbaikan (TP) Aktif / Dibekukan
            if ($activeAssessment->tp_status && ! in_array($activeAssessment->tp_status, [Assessment::TP_STATUS_NONE, Assessment::TP_STATUS_SATISFIED], true)) {
                $due = $activeAssessment->effective_tp_due_date ? $activeAssessment->effective_tp_due_date->format('d/m/Y') : '-';

                if ($activeAssessment->is_tp_overdue || $activeAssessment->status === 'SUSPENDED') {
                    return "{$typeLabel}: Dibekukan (melewati batas waktu {$due} belum memenuhi).";
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
                return "{$typeLabel}: Dibekukan (melewati batas waktu {$due} belum memenuhi).";
            }

            // 2. SK Akreditasi KAN telah terbit
            if (! empty($activeAssessment->sk_number)) {
                $skDateStr = $activeAssessment->sk_date ? ' tgl ' . $activeAssessment->sk_date->format('d/m/Y') : '';
                return "{$typeLabel}: Selesai (SK No. {$activeAssessment->sk_number}{$skDateStr}).";
            }

            // 3. Status TP Memenuhi (Selesai sebelum SK terbit)
            if ($activeAssessment->tp_status === Assessment::TP_STATUS_SATISFIED) {
                return "{$typeLabel}: Selesai (TP Memenuhi).";
            }

            // 4. Tahap Evaluasi Hasil Asesmen (EHA)
            if (! empty($activeAssessment->eha_status) && $activeAssessment->eha_status !== Assessment::EHA_STATUS_BELUM) {
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
        $alerts = $this->getActiveAlerts($lpk);
        if (! empty($alerts)) {
            $firstAlert = reset($alerts);
            $milestoneName = match ($firstAlert['code'] ?? '') {
                'S1' => Assessment::TYPE_SURVEILEN_1,
                'S2' => Assessment::TYPE_SURVEILEN_2,
                'RA' => Assessment::TYPE_RE_AKREDITASI,
                default => $firstAlert['name'] ?? Assessment::TYPE_SURVEILEN_1,
            };
            $target = ! empty($firstAlert['target_date']) ? ' (' . $firstAlert['target_date']->format('d/m/Y') . ')' : '';
            return "{$milestoneName}: Periode jatuh tempo KAN{$target}.";
        }

        // 3. Cek masa berlaku sertifikat
        if ($lpk->isExpired()) {
            $exp = $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-';
            return Assessment::TYPE_RE_AKREDITASI . ": Sertifikat kedaluwarsa ({$exp}).";
        }

        if ($lpk->isExpiringSoon()) {
            $exp = $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-';
            return Assessment::TYPE_RE_AKREDITASI . ": Masa berlaku berakhir {$exp}.";
        }

        return "Aktif normal" . ($lpk->expired_at ? " (s/d " . $lpk->expired_at->format('d/m/Y') . ")" : "") . ".";
    }

    /**
     * Memperbarui masa akreditasi LPK ke siklus 5 tahun berikutnya saat asesmen Re-Akreditasi selesai dengan SK.
     */
    public function renewAccreditationCycle(Lpk $lpk, Assessment $assessment): bool
    {
        $isRa = $assessment->assessment_type === Assessment::TYPE_RE_AKREDITASI
            || str_contains(strtolower($assessment->title ?: ''), 're-akreditasi')
            || str_contains(strtolower($assessment->title ?: ''), 'reakreditasi');

        if (! $isRa || $assessment->status !== 'COMPLETED' || empty($assessment->sk_number)) {
            return false;
        }

        // Tanggal awal siklus baru diambil dari sk_date atau end_at atau expired_at
        $newCertDate = $assessment->sk_date
            ?: ($assessment->end_at ? $assessment->end_at->copy()->startOfDay() : ($lpk->expired_at ?: now()->startOfDay()));

        if (! $newCertDate) {
            return false;
        }

        // Jika certificate_date saat ini sudah lebih baru atau sama dengan newCertDate, jangan dimajukan lagi
        if ($lpk->certificate_date && $lpk->certificate_date->gte($newCertDate->copy()->startOfDay())) {
            return false;
        }

        $newExpDate = $newCertDate->copy()->addYears(5);

        // Update LPK ke siklus 5 tahun berikutnya
        $lpk->update([
            'certificate_date' => $newCertDate->toDateString(),
            'expired_at' => $newExpDate->toDateString(),
            'status' => 'ACTIVE',
        ]);

        return true;
    }

    /**
     * Sinkronisasi siklus akreditasi jika ada asesmen Re-Akreditasi selesai dengan SK yang belum dimajukan.
     */
    public function syncAccreditationCycle(Lpk $lpk): bool
    {
        $latestRa = $lpk->assessments()
            ->where(function ($q) {
                $q->where('assessment_type', Assessment::TYPE_RE_AKREDITASI)
                    ->orWhere('title', 'like', '%Re-Akreditasi%')
                    ->orWhere('title', 'like', '%Reakreditasi%');
            })
            ->where('status', 'COMPLETED')
            ->whereNotNull('sk_number')
            ->orderByDesc('sk_date')
            ->orderByDesc('end_at')
            ->first();

        if (! $latestRa) {
            return false;
        }

        return $this->renewAccreditationCycle($lpk, $latestRa);
    }
}
