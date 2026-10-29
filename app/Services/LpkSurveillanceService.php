<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        if ($lpk->hasMemoizedMilestones()) {
            return $lpk->getMemoizedMilestones();
        }

        /** @var Carbon|null $cycleEnd */
        $cycleEnd = $lpk->expired_at ?: ($lpk->certificate_date ? $lpk->certificate_date->copy()->addYearsNoOverflow(5) : null);
        /** @var Carbon|null $cycleStart */
        $cycleStart = $lpk->certificate_date ?: ($cycleEnd ? $cycleEnd->copy()->subYearsNoOverflow(5) : null);

        $milestones = [
            's1' => [
                'code' => 'S1',
                'name' => Assessment::TYPE_SURVEILEN_1,
                'notice_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(13) : null,
                'visit_target_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(15) : null,
                'target_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(18) : null,
                'tolerance_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(24) : null,
                'description' => 'Reminder S1 bulan ke-13, Jatuh Tempo (JT) S1 bulan ke-18 (toleransi kunjungan maks 2 tahun/bulan 24)',
                'status' => 'PENDING',
            ],
            's2' => [
                'code' => 'S2',
                'name' => Assessment::TYPE_SURVEILEN_2,
                'notice_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(34) : null,
                'visit_target_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(36) : null,
                'target_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(39) : null,
                'tolerance_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(39) : null,
                'description' => 'Reminder S2 bulan ke-34, Batas Waktu Standar S2 bulan ke-39',
                'status' => 'PENDING',
            ],
            'ra' => [
                'code' => 'RA',
                'name' => Assessment::TYPE_RE_AKREDITASI,
                'notice_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(48) : null,
                'visit_target_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(54) : null,
                'target_date' => $cycleStart ? $cycleStart->copy()->addMonthsNoOverflow(51) : null,
                'tolerance_date' => $cycleEnd,
                'description' => 'Pengajuan RA bulan ke-48 s/d 51, asesmen lapangan sebelum bulan ke-60',
                'status' => 'PENDING',
            ],
        ];

        if (! $cycleStart || ! $cycleEnd) {
            $lpk->setMemoizedMilestones($milestones);
            return $milestones;
        }

        $now = now();
        $rawLpkStatus = $lpk->getAttributes()['status'] ?? $lpk->getRawOriginal('status');
        $isLpkActive = in_array(strtoupper((string) $rawLpkStatus), ['ACTIVE', 'AKTIF'], true);
        $isCertValid = $now->lt($cycleEnd);

        // Cek asesmen yang sudah pernah dibuat untuk LPK ini
        $assessments = $lpk->relationLoaded('assessments') ? $lpk->assessments : $lpk->assessments()->get();
        foreach ($assessments as $item) {
            if (! $item->relationLoaded('lpk')) {
                $item->setRelation('lpk', $lpk);
            }
        }

        // Cek S1
        if ($milestones['s1']['notice_date']) {
            $hasS1 = $assessments->contains(function ($item) use ($cycleStart, $now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                $type = strtolower($item->assessment_type ?: '');
                $title = strtolower($item->title ?: '');
                $isSurv = str_contains($type, 's1') || str_contains($type, 'surveilen 1') || str_contains($title, 's1')
                    || (str_contains($type, 'survei') && ! str_contains($type, '2') && ! str_contains($title, '2'));
                $isWithinRange = $item->start_at && $item->start_at->gte($cycleStart->copy()->subMonths(2)) && $item->start_at->lte($cycleStart->copy()->addMonths(26));

                // Lewati jika asesmen dibatalkan atau telah melewati batas toleransi pengisian dokumen (submission overdue)
                if ($item->status === 'CANCELLED' || $item->is_submission_overdue) {
                    return false;
                }

                return $isSurv && $isWithinRange;
            });

            $suspendedS1 = $assessments->first(function ($item) use ($cycleStart) {
                $type = strtolower($item->assessment_type ?: '');
                $title = strtolower($item->title ?: '');
                $isSurv = str_contains($type, 's1') || str_contains($type, 'surveilen 1') || str_contains($title, 's1')
                    || (str_contains($type, 'survei') && ! str_contains($type, '2') && ! str_contains($title, '2'));
                $isWithinRange = $item->start_at && $item->start_at->gte($cycleStart->copy()->subMonths(2)) && $item->start_at->lte($cycleStart->copy()->addMonths(26));

                return $isSurv && $isWithinRange && $item->status === 'SUSPENDED';
            });

            if ($suspendedS1) {
                $milestones['s1']['status'] = 'SUSPENDED';
            } elseif ($hasS1) {
                $milestones['s1']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($isLpkActive && $milestones['s1']['tolerance_date'] && $now->gte($milestones['s1']['tolerance_date'])) {
                // Untuk LPK yang aktif, jadwal S1 lampau yang melewati masa toleransinya dianggap terealisasi
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
            $hasS2 = $assessments->contains(function ($item) use ($cycleStart, $now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                $type = strtolower($item->assessment_type ?: '');
                $title = strtolower($item->title ?: '');
                $isSurv = str_contains($type, 's2') || str_contains($type, 'surveilen 2') || str_contains($title, 's2')
                    || (str_contains($type, 'survei') && (str_contains($type, '2') || str_contains($title, '2')));
                $isWithinRange = $item->start_at && $item->start_at->gte($cycleStart->copy()->addMonths(24)) && $item->start_at->lte($cycleStart->copy()->addMonths(46));

                // Lewati jika asesmen dibatalkan atau telah melewati batas toleransi pengisian dokumen (submission overdue)
                if ($item->status === 'CANCELLED' || $item->is_submission_overdue) {
                    return false;
                }

                return $isSurv && $isWithinRange;
            });

            $suspendedS2 = $assessments->first(function ($item) use ($cycleStart) {
                $type = strtolower($item->assessment_type ?: '');
                $title = strtolower($item->title ?: '');
                $isSurv = str_contains($type, 's2') || str_contains($type, 'surveilen 2') || str_contains($title, 's2')
                    || (str_contains($type, 'survei') && (str_contains($type, '2') || str_contains($title, '2')));
                $isWithinRange = $item->start_at && $item->start_at->gte($cycleStart->copy()->addMonths(24)) && $item->start_at->lte($cycleStart->copy()->addMonths(46));

                return $isSurv && $isWithinRange && $item->status === 'SUSPENDED';
            });

            if ($suspendedS2) {
                $milestones['s2']['status'] = 'SUSPENDED';
            } elseif ($hasS2) {
                $milestones['s2']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($isLpkActive && $milestones['ra']['notice_date'] && $now->gte($milestones['ra']['notice_date'])) {
                // Untuk LPK aktif yang sudah memasuki siklus RA, jadwal S2 lampau dianggap terealisasi
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
            $hasRA = $assessments->contains(function ($item) use ($cycleStart, $cycleEnd, $now) {
                if ($item->status === 'PLANNED') {
                    return false;
                }

                // Lewati jika asesmen dibatalkan atau telah melewati batas toleransi pengisian dokumen (submission overdue)
                if ($item->status === 'CANCELLED' || $item->is_submission_overdue) {
                    return false;
                }

                $type = strtolower((string) ($item->assessment_type ?: ''));
                $title = strtolower((string) ($item->title ?: ''));
                $isRa = in_array($item->assessment_type, ['RA', 'Re-Akreditasi', 'Re-Akreditasi (RA)', 'REAKREDITASI', 'REASSESSMENT', 'Re-asesmen', 'Akreditasi Ulang'], true)
                    || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $title)
                    || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $type);

                $isWithinRange = $item->start_at && $item->start_at->gte($cycleStart->copy()->addMonths(42)) && (! $cycleEnd || $item->start_at->lte($cycleEnd->copy()->addMonths(6)));

                return $isRa && $isWithinRange;
            });

            $suspendedRA = $assessments->first(function ($item) use ($cycleStart, $cycleEnd) {
                $type = strtolower((string) ($item->assessment_type ?: ''));
                $title = strtolower((string) ($item->title ?: ''));
                $isRa = in_array($item->assessment_type, ['RA', 'Re-Akreditasi', 'Re-Akreditasi (RA)', 'REAKREDITASI', 'REASSESSMENT', 'Re-asesmen', 'Akreditasi Ulang'], true)
                    || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $title)
                    || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $type);

                $isWithinRange = $item->start_at && $item->start_at->gte($cycleStart->copy()->addMonths(42)) && (! $cycleEnd || $item->start_at->lte($cycleEnd->copy()->addMonths(6)));

                return $isRa && $isWithinRange && $item->status === 'SUSPENDED';
            });

            if ($suspendedRA) {
                $milestones['ra']['status'] = 'SUSPENDED';
            } elseif ($cycleEnd && $now->gt($cycleEnd->copy()->addMonths(6))) {
                $milestones['ra']['status'] = 'REVOKED';
            } elseif ($hasRA) {
                $milestones['ra']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($cycleEnd && $now->gte($cycleEnd)) {
                $milestones['ra']['status'] = 'EXPIRED';
            } elseif ($now->gte($milestones['ra']['notice_date'])) {
                $milestones['ra']['status'] = 'DUE';
            } else {
                $milestones['ra']['status'] = 'UPCOMING';
            }
        }

        $lpk->setMemoizedMilestones($milestones);
        return $milestones;
    }

    /**
     * Dapatkan daftar notifikasi persisten yang sedang aktif.
     */
    public function getActiveAlerts(Lpk $lpk): array
    {
        $alerts = [];

        // 1. Cek status kritis tingkat LPK (Pencabutan Akreditasi, Masa Tenggang, atau Pembekuan)
        $dynamicStatus = $lpk->dynamic_status;

        if ($dynamicStatus === 'REVOKED') {
            $expStr = $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-';
            $deadline = $lpk->grace_period_deadline ? $lpk->grace_period_deadline->format('d/m/Y') : $expStr;
            $desc = ! $lpk->hasReaccreditationInFlight()
                ? "Siklus akreditasi 5 tahun telah berakhir ({$expStr}) tanpa pelaksanaan asesmen akreditasi ulang (RA). Status akreditasi resmi dicabut."
                : "Melewati batas 6 bulan masa tenggang Re-Akreditasi ({$deadline}) tanpa penetapan status baru. Status akreditasi resmi dicabut.";

            $alerts[] = [
                'lpk_id' => $lpk->id,
                'lpk_reg' => $lpk->registration_number,
                'lpk_name' => $lpk->name,
                'lpk_email' => $lpk->email,
                'code' => 'RA',
                'name' => Assessment::TYPE_RE_AKREDITASI,
                'notice_date' => $lpk->expired_at,
                'target_date' => $lpk->expired_at,
                'status' => 'REVOKED',
                'status_label' => 'AKREDITASI DICABUT',
                'is_urgent' => true,
                'severity' => 'danger',
                'description' => $desc,
                'last_notified_at' => $lpk->last_surveillance_notified_at,
            ];

            return $alerts;
        }

        if ($dynamicStatus === 'GRACE_PERIOD') {
            $deadline = $lpk->grace_period_deadline ? $lpk->grace_period_deadline->format('d/m/Y') : '-';
            $daysLeft = $lpk->days_remaining_grace_period ?? 0;

            $alerts[] = [
                'lpk_id' => $lpk->id,
                'lpk_reg' => $lpk->registration_number,
                'lpk_name' => $lpk->name,
                'lpk_email' => $lpk->email,
                'code' => 'RA',
                'name' => Assessment::TYPE_RE_AKREDITASI,
                'notice_date' => $lpk->expired_at,
                'target_date' => $lpk->grace_period_deadline,
                'status' => 'GRACE_PERIOD',
                'status_label' => 'MASA TENGGANG (6 BLN)',
                'is_urgent' => true,
                'severity' => 'danger',
                'description' => "Masa berlaku akreditasi berakhir. Sisa toleransi masa tenggang Re-Akreditasi: {$daysLeft} hari (s/d {$deadline}).",
                'last_notified_at' => $lpk->last_surveillance_notified_at,
            ];

            return $alerts;
        }

        if ($dynamicStatus === 'SUSPENDED') {
            $activeAssessment = $lpk->getActiveOrUpcomingAssessment();
            $code = 'S1';
            $name = Assessment::TYPE_SURVEILEN_1;
            $noticeDate = $activeAssessment?->start_at;
            $targetDate = $activeAssessment?->effective_tp_due_date ?: $activeAssessment?->submission_due_date;
            $desc = "Akreditasi laboratorium dibekukan karena melewati batas waktu surveilen atau tindakan perbaikan.";

            if ($activeAssessment) {
                $code = str_contains(strtolower($activeAssessment->title ?: ''), 's2') ? 'S2' : (str_contains(strtolower($activeAssessment->title ?: ''), 'ra') ? 'RA' : 'S1');
                $name = $activeAssessment->assessment_type_label;
            } else {
                $milestones = $this->calculateMilestones($lpk);
                $overdueMilestone = collect($milestones)->first(fn ($m) => ($m['status'] ?? '') === 'OVERDUE');
                if ($overdueMilestone) {
                    $code = $overdueMilestone['code'];
                    $name = $overdueMilestone['name'];
                    $noticeDate = $overdueMilestone['notice_date'];
                    $targetDate = $overdueMilestone['target_date'];
                    $desc = "Akreditasi laboratorium dibekukan karena melewati batas waktu maksimal {$name} sesuai regulasi KAN U-01.";
                }
            }

            $alerts[] = [
                'lpk_id' => $lpk->id,
                'lpk_reg' => $lpk->registration_number,
                'lpk_name' => $lpk->name,
                'lpk_email' => $lpk->email,
                'code' => $code,
                'name' => $name,
                'notice_date' => $noticeDate,
                'target_date' => $targetDate,
                'status' => 'SUSPENDED',
                'status_label' => 'STATUS DIBEKUKAN',
                'is_urgent' => true,
                'severity' => 'danger',
                'description' => $desc,
                'last_notified_at' => $lpk->last_surveillance_notified_at,
            ];

            return $alerts;
        }

        // 2. Cek milestone siklus KAN (S1, S2, RA)
        $milestones = $this->calculateMilestones($lpk);

        foreach ($milestones as $milestone) {
            if (in_array($milestone['status'], ['DUE', 'OVERDUE', 'EXPIRED', 'GRACE_PERIOD', 'SUSPENDED', 'REVOKED'], true)) {
                $isUrgent = in_array($milestone['status'], ['OVERDUE', 'EXPIRED', 'SUSPENDED', 'REVOKED'], true);

                $label = match ($milestone['status']) {
                    'OVERDUE' => 'LEWAT JATUH TEMPO',
                    'EXPIRED' => 'SERTIFIKAT KEDALUWARSA',
                    'GRACE_PERIOD' => 'MASA TENGGANG (6 BLN)',
                    'REVOKED' => 'AKREDITASI DICABUT',
                    'SUSPENDED' => 'DIBEKUKAN',
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
                    'tolerance_date' => $milestone['tolerance_date'] ?? null,
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
        return DB::transaction(function () use ($lpk, $creatorId) {
            /** @var Carbon|null $cycleEnd */
        $cycleEnd = $lpk->expired_at ?: ($lpk->certificate_date ? $lpk->certificate_date->copy()->addYears(5) : null);
        if (! $cycleEnd) {
            return 0;
        }
        /** @var Carbon $cycleStart */
        $cycleStart = $lpk->certificate_date ?: $cycleEnd->copy()->subYears(5);

        $creatorId = $creatorId
            ?: auth()->id()
            ?: User::where('role', User::ROLE_ADMIN)->value('id')
            ?: User::value('id');

        if (! $creatorId) {
            return 0;
        }

        $created = 0;
        $now = now();
        $rawLpkStatus = $lpk->getAttributes()['status'] ?? $lpk->getRawOriginal('status');
        $isLpkActive = in_array(strtoupper((string) $rawLpkStatus), ['ACTIVE', 'AKTIF'], true);

        // Rentang pencarian asesmen yang relevan dengan siklus ini
        $queryStart = $cycleStart->copy()->subMonths(3);
        $queryEnd = $cycleEnd->copy()->addMonths(6);
        $mainLocationId = $lpk->mainLocation()?->id;

        // 1. Asesmen Surveilen 1 (S1): Bulan 15 (Target Kunjungan Bulan 15-18)
        $s1Target = $cycleStart->copy()->addMonths(15)->startOfDay()->setHour(9);
        $s1End = $s1Target->copy()->addDays(2)->setHour(17);
        $s1Tolerance = $cycleStart->copy()->addMonths(24)->endOfDay();
        $s1IsPast = $isLpkActive && $now->gte($s1Tolerance);

        $s1Assessment = $lpk->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Surveilen 1%')
                    ->orWhere('title', 'like', '%(S1)%')
                    ->orWhere('title', 'like', '% S1 %')
                    ->orWhere('assessment_type', Assessment::TYPE_SURVEILEN_1);
            })
            ->where('start_at', '>=', $queryStart)
            ->where('start_at', '<=', $queryEnd)
            ->first();

        if (! $s1Assessment) {
            $lpk->assessments()->create([
                'lpk_location_id' => $mainLocationId,
                'created_by' => $creatorId,
                'title' => 'Asesmen Surveilen 1 (S1)',
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
        } else {
            if ($s1IsPast && $s1Assessment->getRawOriginal('status') === 'PLANNED') {
                $s1Assessment->update([
                    'status' => 'COMPLETED',
                    'tp_status' => Assessment::TP_STATUS_SATISFIED,
                    'tp_satisfied_at' => $s1Assessment->tp_satisfied_at ?: $s1End->copy()->addMonth()->startOfDay(),
                ]);
            } elseif (! $s1IsPast && $s1Assessment->status === 'PLANNED' && empty($s1Assessment->report_date) && empty($s1Assessment->sk_number)) {
                $s1Assessment->update([
                    'start_at' => $s1Target,
                    'end_at' => $s1End,
                    'status' => 'PLANNED',
                ]);
            }
        }

        // 2. Asesmen Surveilen 2 (S2): Bulan 36 (Target Kunjungan Bulan 36-39)
        $s2Target = $cycleStart->copy()->addMonths(36)->startOfDay()->setHour(9);
        $s2End = $s2Target->copy()->addDays(2)->setHour(17);
        $s2Tolerance = $cycleStart->copy()->addMonths(39)->endOfDay();
        $s2IsPast = $isLpkActive && $now->gte($s2Tolerance);

        $s2Assessment = $lpk->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Surveilen 2%')
                    ->orWhere('title', 'like', '%(S2)%')
                    ->orWhere('title', 'like', '% S2 %')
                    ->orWhere('assessment_type', Assessment::TYPE_SURVEILEN_2);
            })
            ->where('start_at', '>=', $queryStart)
            ->where('start_at', '<=', $queryEnd)
            ->first();

        if (! $s2Assessment) {
            $lpk->assessments()->create([
                'lpk_location_id' => $mainLocationId,
                'created_by' => $creatorId,
                'title' => 'Asesmen Surveilen 2 (S2)',
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
        } else {
            if ($s2IsPast && $s2Assessment->getRawOriginal('status') === 'PLANNED') {
                $s2Assessment->update([
                    'status' => 'COMPLETED',
                    'tp_status' => Assessment::TP_STATUS_SATISFIED,
                    'tp_satisfied_at' => $s2Assessment->tp_satisfied_at ?: $s2End->copy()->addMonth()->startOfDay(),
                ]);
            } elseif (! $s2IsPast && $s2Assessment->status === 'PLANNED' && empty($s2Assessment->report_date) && empty($s2Assessment->sk_number)) {
                $s2Assessment->update([
                    'start_at' => $s2Target,
                    'end_at' => $s2End,
                    'status' => 'PLANNED',
                ]);
            }
        }

        // 3. Re-Akreditasi (RA): Bulan 54 (H-6 bulan sebelum masa berlaku berakhir)
        $raTarget = $cycleStart->copy()->addMonths(54)->startOfDay()->setHour(9);
        $raEnd = $raTarget->copy()->addDays(3)->setHour(17);
        $raIsPast = $isLpkActive && ($raEnd->lt($now) || $now->gte($cycleEnd));

        $raAssessment = $lpk->assessments()
            ->where(function ($q) {
                $q->where('title', 'like', '%Re-asesmen%')
                    ->orWhere('title', 'like', '%Re-Akreditasi%')
                    ->orWhere('title', 'like', '%(RA)%')
                    ->orWhere('title', 'like', '% RA %')
                    ->orWhereIn('assessment_type', [Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT']);
            })
            ->where('start_at', '>=', $queryStart)
            ->where('start_at', '<=', $queryEnd)
            ->first();

        if (! $raAssessment) {
            $lpk->assessments()->create([
                'lpk_location_id' => $mainLocationId,
                'created_by' => $creatorId,
                'title' => 'Asesmen Re-Akreditasi (RA)',
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
        } else {
            if ($raIsPast && $raAssessment->getRawOriginal('status') === 'PLANNED') {
                $raAssessment->update([
                    'status' => 'COMPLETED',
                    'tp_status' => Assessment::TP_STATUS_SATISFIED,
                    'tp_satisfied_at' => $raAssessment->tp_satisfied_at ?: $raEnd->copy()->addMonth()->startOfDay(),
                ]);
            } elseif (! $raIsPast && $raAssessment->status === 'PLANNED' && empty($raAssessment->report_date) && empty($raAssessment->sk_number)) {
                $raAssessment->update([
                    'start_at' => $raTarget,
                    'end_at' => $raEnd,
                    'status' => 'PLANNED',
                ]);
            }
        }

            return $created;
        });
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

        // Cek toleransi masa tenggang 6 bulan setelah kedaluwarsa & auto-revocation siklus
        if ($lpk->isRevocationOverdue()) {
            return 'REVOKED';
        }

        if ($lpk->isInGracePeriod()) {
            return 'GRACE_PERIOD';
        }

        $milestones = $this->calculateMilestones($lpk);

        if (($milestones['ra']['status'] ?? '') === 'REVOKED') {
            return 'REVOKED';
        }

        if (($milestones['ra']['status'] ?? '') === 'EXPIRED') {
            return 'EXPIRED';
        }

        $assessmentsList = $lpk->relationLoaded('assessments') ? $lpk->assessments : $lpk->assessments()->get();
        foreach ($assessmentsList as $item) {
            if (! $item->relationLoaded('lpk')) {
                $item->setRelation('lpk', $lpk);
            }
        }

        // 1. Cek apakah ada asesmen yang statusnya REVOKED atau telah melewati batas 1 tahun pembekuan tanpa penyelesaian
        if ($assessmentsList->contains(fn (Assessment $a) => ! $a->isPastSurveillanceForActiveLpk() && ($a->status === 'REVOKED' || $a->is_suspension_expired))) {
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

        // Cek apakah ada surveilen yang melewati batas toleransi maksimal (SUSPENDED)
        $isToleranceExceeded = ($milestones['s1']['status'] ?? '') === 'SUSPENDED'
            || ($milestones['s2']['status'] ?? '') === 'SUSPENDED';

        if ($isToleranceExceeded) {
            return 'SUSPENDED';
        }

        // Cek apakah ada surveilen yang melewati target waktu tanpa pelaksanaan asesmen (OVERDUE)
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
        $status = $this->determineDynamicStatus($lpk);

        if ($status === 'SURVEILLANCE_DUE') {
            return 'Jatuh Tempo Surveilen';
        }

        if ($status === 'SURVEILLANCE_OVERDUE') {
            return 'Lewat Jatuh Tempo Surveilen';
        }

        if ($status === 'ACTIVE') {
            $activeAssessment = $lpk->getActiveOrUpcomingAssessment();
            if ($activeAssessment && in_array($activeAssessment->status, ['IN_PROGRESS', 'SCHEDULED'], true)) {
                $short = $activeAssessment->short_type;
                return "Aktif (Proses {$short})";
            }
            return 'Aktif';
        }

        return match ($status) {
            'INACTIVE' => 'Tidak Aktif',
            'REVOKED' => 'Dicabut',
            'SUSPENDED' => 'Dibekukan',
            'GRACE_PERIOD' => 'Masa Tenggang (6 Bln)',
            'EXPIRED' => 'Kedaluwarsa',
            default => 'Aktif',
        };
    }

    /**
     * Rangkuman status operasional otomatis berdasarkan asesmen, batas TP, dan siklus KAN.
     */
    public function generateDynamicKeterangan(Lpk $lpk): string
    {
        // 0a. Auto-revocation Re-Akreditasi
        if ($lpk->isRevocationOverdue()) {
            if (! $lpk->hasReaccreditationInFlight()) {
                $exp = $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-';
                return "Re-Akreditasi (RA): Akreditasi Dicabut (siklus akreditasi berakhir {$exp} tanpa pelaksanaan asesmen akreditasi ulang).";
            }

            $deadline = $lpk->grace_period_deadline ? $lpk->grace_period_deadline->format('d/m/Y') : '-';
            return "Re-Akreditasi (RA): Akreditasi Dicabut (melewati batas 6 bulan masa tenggang Re-Akreditasi s/d {$deadline}).";
        }

        // 0b. Berada dalam masa tenggang toleransi 6 bulan setelah masa akreditasi habis
        if ($lpk->isInGracePeriod()) {
            $deadline = $lpk->grace_period_deadline ? $lpk->grace_period_deadline->format('d/m/Y') : '-';
            $daysLeft = $lpk->days_remaining_grace_period ?? 0;
            return "Re-Akreditasi (RA): Masa Tenggang Toleransi (sisa {$daysLeft} hari s/d {$deadline}). Perhatian: Penggunaan simbol akreditasi KAN dibekukan sementara hingga keputusan akreditasi ulang ditetapkan.";
        }

        $activeAssessment = $lpk->getActiveOrUpcomingAssessment();

        if ($activeAssessment) {
            $typeLabel = $activeAssessment->assessment_type_label;
            $now = now();

            // 0. Status Dicabut (melewati batas 1 tahun pembekuan)
            if (($activeAssessment->status === 'REVOKED' || $activeAssessment->is_suspension_expired) && ! $activeAssessment->isPastSurveillanceForActiveLpk()) {
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
            if ($activeAssessment->status === 'SUSPENDED' || $activeAssessment->is_tp_overdue || ($activeAssessment->is_submission_overdue && ($activeAssessment->status !== 'PLANNED' || ! empty($activeAssessment->report_date) || ! empty($activeAssessment->eha_date)))) {
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
                $penaltyText = ($lpk->s1_delay_penalty_months > 0)
                    ? " (waktu persiapan menuju S1 menyempit menjadi {$lpk->s1_prep_remaining_months} bulan akibat keterlambatan RA {$lpk->s1_delay_penalty_months} bulan)"
                    : "";
                return "{$typeLabel}: Selesai (SK No. {$activeAssessment->sk_number}{$skDateStr}){$penaltyText}.";
            }

            // 3. Status TP Memenuhi (Selesai sebelum SK terbit)
            if ($activeAssessment->tp_status === Assessment::TP_STATUS_SATISFIED) {
                return "{$typeLabel}: Selesai (TP Memenuhi).";
            }

            // 4. Tahap Rapat Panitia Teknis
            if (! empty($activeAssessment->eha_status) && $activeAssessment->eha_status !== Assessment::EHA_STATUS_BELUM) {
                return "{$typeLabel}: Rapat Panitia Teknis ({$activeAssessment->eha_status_label}).";
            }

            // 5. Laporan asesmen telah terbit, menunggu Rapat Panitia Teknis
            if ($activeAssessment->report_date) {
                return "{$typeLabel}: Laporan terbit ({$activeAssessment->report_date->format('d/m/Y')}), proses Rapat Panitia Teknis.";
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
                $penaltyText = ($lpk->s1_delay_penalty_months > 0 && (str_contains(strtolower($activeAssessment->assessment_type ?: ''), 'survei') || str_contains(strtolower($activeAssessment->title ?: ''), 's1')))
                    ? " (waktu persiapan lab menyempit menjadi {$lpk->s1_prep_remaining_months} bulan akibat keterlambatan RA {$lpk->s1_delay_penalty_months} bulan)"
                    : "";
                return "{$typeLabel}: Terjadwal {$activeAssessment->start_at->format('d/m/Y')}{$penaltyText}.";
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
            if (($firstAlert['status'] ?? '') === 'SUSPENDED') {
                return "{$milestoneName}: Dibekukan (melewati batas waktu surveilen KAN{$target}).";
            }
            if (($firstAlert['status'] ?? '') === 'REVOKED') {
                return "{$milestoneName}: Akreditasi Dicabut.";
            }
            if (($firstAlert['status'] ?? '') === 'OVERDUE') {
                return "{$milestoneName}: Lewat jatuh tempo KAN{$target}.";
            }
            return "{$milestoneName}: Periode jatuh tempo KAN{$target}.";
        }

        // 3. Cek masa berlaku sertifikat
        if ($lpk->isExpired()) {
            $exp = $lpk->cycle_expired_at ? $lpk->cycle_expired_at->format('d/m/Y') : '-';
            return Assessment::TYPE_RE_AKREDITASI . ": Sertifikat kedaluwarsa ({$exp}).";
        }

        if ($lpk->isExpiringSoon()) {
            $exp = $lpk->cycle_expired_at ? $lpk->cycle_expired_at->format('d/m/Y') : '-';
            return Assessment::TYPE_RE_AKREDITASI . ": Masa berlaku berakhir {$exp}.";
        }

        if ($lpk->s1_delay_penalty_months > 0) {
            return "Aktif normal (siklus baru). Waktu persiapan menuju Surveilen 1 menyempit menjadi {$lpk->s1_prep_remaining_months} bulan akibat keterlambatan RA {$lpk->s1_delay_penalty_months} bulan.";
        }

        $cycleExp = $lpk->cycle_expired_at ? " (s/d " . $lpk->cycle_expired_at->format('d/m/Y') . ")" : "";
        return "Aktif normal{$cycleExp}.";
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

        // Tanggal penyelesaian RA
        $completedDate = $assessment->sk_date
            ?: ($assessment->end_at ? $assessment->end_at->copy()->startOfDay() : ($lpk->expired_at ?: now()->startOfDay()));

        if (! $completedDate) {
            return false;
        }

        $previousExpDate = $lpk->expired_at ? $lpk->expired_at->copy()->startOfDay() : null;

        // Jika diselesaikan dalam masa tenggang 6 bulan setelah expired_at lama:
        // Kunci baseline siklus baru tetap pada expired_at lama (ritme 5 tahun KAN tidak bergeser)
        if ($previousExpDate && $completedDate->gt($previousExpDate) && $completedDate->lte($previousExpDate->copy()->addMonths(6))) {
            $newCertDate = $previousExpDate;
            $newExpDate = $previousExpDate->copy()->addYears(5);
        } else {
            $newCertDate = $completedDate;
            $newExpDate = $newCertDate->copy()->addYears(5);
        }

        // Jika certificate_date dan expired_at saat ini sudah sama atau lebih baru dari siklus baru, jangan dimajukan lagi
        if ($lpk->certificate_date && $lpk->certificate_date->gte($newCertDate->copy()->startOfDay()) && $lpk->expired_at && $lpk->expired_at->gte($newExpDate->copy()->startOfDay())) {
            return false;
        }

        // Update LPK ke siklus 5 tahun berikutnya secara atomik (model event updated otomatis sinkronisasi agenda asesmen)
        return DB::transaction(function () use ($lpk, $newCertDate, $newExpDate, $assessment): bool {
            // Bersihkan asesmen RA lama yang tertinggal dalam status PLANNED sebelum siklus baru
            $lpk->assessments()
                ->where('id', '!=', $assessment->id)
                ->where(function ($q) {
                    $q->where('assessment_type', Assessment::TYPE_RE_AKREDITASI)
                        ->orWhere('title', 'like', '%Re-Akreditasi%')
                        ->orWhere('title', 'like', '%Reakreditasi%');
                })
                ->where('start_at', '<=', $newCertDate)
                ->where('status', 'PLANNED')
                ->delete();

            $lpk->update([
                'certificate_date' => $newCertDate->toDateString(),
                'expired_at' => $newExpDate->toDateString(),
                'status' => 'ACTIVE',
            ]);

            return true;
        });
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

    /**
     * Dapatkan cache key notifikasi surveilen yang terisolasi per peran & PIC.
     */
    public static function getAlertsCacheKey(?User $user): string
    {
        $epoch = (int) \Illuminate\Support\Facades\Cache::get('simasadi_alerts_epoch', 1);
        if ($user && $user->isAdmin()) {
            return "simasadi_alerts_admin_e{$epoch}";
        }

        $userId = $user ? $user->id : 'guest';
        return "simasadi_alerts_user_{$userId}_e{$epoch}";
    }

    /**
     * Ambil notifikasi surveilen pengguna dari cache terpusat.
     */
    public static function getAlertsForUser(?User $user, int $ttlSeconds = 300): array
    {
        $cacheKey = static::getAlertsCacheKey($user);

        $rawAlerts = \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttlSeconds, function () use ($user) {
            $isNonAdmin = $user && ! $user->isAdmin();
            $lpks = Lpk::query()
                ->when($isNonAdmin, fn ($q) => $q->primaryFor($user))
                ->with(['assessments' => fn ($q) => $q->select([
                    'id', 'lpk_id', 'title', 'assessment_type', 'status', 'start_at', 'end_at',
                    'report_date', 'eha_date', 'eha_status', 'tp_status', 'tp_due_date',
                    'tp_has_extension', 'tp_extension_months', 'tp_satisfied_at', 'sk_number', 'sk_date',
                ])])
                ->get();

            $list = [];
            foreach ($lpks as $lpk) {
                foreach ($lpk->assessments as $asm) {
                    $asm->setRelation('lpk', $lpk);
                }
                foreach ($lpk->getActiveSurveillanceAlerts() as $alert) {
                    $list[] = $alert;
                }
            }

            return array_map(function ($item) {
                foreach (['target_date', 'notice_date', 'last_notified_at'] as $dateKey) {
                    if (! empty($item[$dateKey]) && $item[$dateKey] instanceof \Carbon\CarbonInterface) {
                        $item[$dateKey] = $item[$dateKey]->toIso8601String();
                    }
                }
                return $item;
            }, $list);
        });

        return array_map(function ($item) {
            foreach (['target_date', 'notice_date', 'last_notified_at'] as $dateKey) {
                if (! empty($item[$dateKey]) && is_string($item[$dateKey])) {
                    try {
                        $item[$dateKey] = \Illuminate\Support\Carbon::parse($item[$dateKey]);
                    } catch (\Throwable) {
                        $item[$dateKey] = null;
                    }
                }
            }
            return $item;
        }, (array) $rawAlerts);
    }

    /**
     * Invalidate cache notifikasi secara terarah (targeted).
     */
    public static function invalidateAlertsCache(?Lpk $lpk = null, ?User $user = null): void
    {
        if ($lpk) {
            $lpk->setMemoizedMilestones(null);
        }

        $epoch = (int) \Illuminate\Support\Facades\Cache::get('simasadi_alerts_epoch', 1);
        \Illuminate\Support\Facades\Cache::forget("simasadi_alerts_admin_e{$epoch}");

        if ($user) {
            \Illuminate\Support\Facades\Cache::forget("simasadi_alerts_user_{$user->id}_e{$epoch}");
        }

        if ($lpk && $lpk->pic_id) {
            \Illuminate\Support\Facades\Cache::forget("simasadi_alerts_user_{$lpk->pic_id}_e{$epoch}");
        }

        // Jika tidak ada target spesifik (misal impor massal atau operasi global), bump epoch global
        if (! $lpk && ! $user) {
            \Illuminate\Support\Facades\Cache::put('simasadi_alerts_epoch', $epoch + 1);
        }
    }

    /**
     * Invalidate cache notifikasi untuk seluruh pengguna secara global (epoch bump).
     */
    public static function invalidateAllAlertsCache(): void
    {
        $epoch = (int) \Illuminate\Support\Facades\Cache::get('simasadi_alerts_epoch', 1);
        \Illuminate\Support\Facades\Cache::put('simasadi_alerts_epoch', $epoch + 1);
    }
}

