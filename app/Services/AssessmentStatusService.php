<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Support\Carbon;

class AssessmentStatusService
{
    /**
     * Tentukan status asesmen secara dinamis berdasarkan parameter tanggal pelaksanaan,
     * status pelaporan, rapat EHA, batas waktu TP, dan penerbitan SK.
     */
    public function determineStatusFromDates(
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
        if ($currentStatus === 'CANCELLED') {
            return 'CANCELLED';
        }

        // 1. Keputusan akhir: SK KAN sudah terbit atau Tindakan Perbaikan telah dinyatakan Memenuhi
        if (! empty($skNumber) || $tpStatus === Assessment::TP_STATUS_SATISFIED || ! empty($tpSatisfiedAt)) {
            return 'COMPLETED';
        }

        $now = now();

        // 2. Batas waktu awal tindakan perbaikan:
        // Jika sampai tanggal batas waktu awal belum ada dinyatakan memenuhi, status otomatis DIBEKUKAN (SUSPENDED).
        $hasExplicitSla = ! empty($tpDueDate);
        $hasActiveTp = in_array($tpStatus, [Assessment::TP_STATUS_IN_PROGRESS, Assessment::TP_STATUS_UNDER_VERIFICATION], true);

        if ($hasExplicitSla || $hasActiveTp) {
            $baseDueDate = $tpDueDate ?: Assessment::calculateDefaultDueDateForType($assessmentType, $endAt ?: $startAt);
            if ($baseDueDate) {
                $effectiveDueDate = $baseDueDate->copy()->startOfDay();
                if ($tpHasExtension && (int) $tpExtensionMonths > 0) {
                    $defaultBase = Assessment::calculateDefaultDueDateForType($assessmentType, $endAt ?: $startAt);
                    $extMonths = min(1, max(1, (int) $tpExtensionMonths));
                    if (! ($defaultBase && $tpDueDate && $tpDueDate->toDateString() === $defaultBase->copy()->addMonths($extMonths)->toDateString())) {
                        $effectiveDueDate->addMonths($extMonths);
                    }
                }

                if ($now->startOfDay()->gt($effectiveDueDate)) {
                    return 'SUSPENDED';
                }
            }

            if ($hasActiveTp) {
                return 'IN_PROGRESS';
            }
        }

        // 3. Sedang dalam rentang waktu pelaksanaan kunjungan asesmen
        if ($startAt && $endAt && $now->between($startAt, $endAt)) {
            return 'IN_PROGRESS';
        }

        // 4. Tanggal pelaksanaan kunjungan asesmen telah lewat (now > end_at)
        if ($endAt && $now->gt($endAt)) {
            // Cek apakah asesmen benar-benar telah terlaksana (ada Laporan Asesmen atau Rapat EHA)
            $hasExecutionProof = ! empty($reportDate) || ! empty($ehaDate);

            if ($hasExecutionProof) {
                $defaultSla = Assessment::calculateDefaultDueDateForType($assessmentType, $endAt);
                if ($defaultSla && $now->startOfDay()->gt($defaultSla->startOfDay())) {
                    return 'SUSPENDED';
                }

                return 'COMPLETED';
            }

            // Jika tanggal telah lewat namun belum ada tindakan apapun, statusnya tetap PLANNED.
            if ($currentStatus === 'PLANNED' || empty($currentStatus)) {
                return 'PLANNED';
            }

            return $currentStatus;
        }

        // 5. Tanggal mulai di masa depan
        if ($startAt && $now->lt($startAt)) {
            return $currentStatus ?? 'SCHEDULED';
        }

        // 6. Jika ada laporan atau EHA terisi
        if (! empty($reportDate) || ! empty($ehaDate)) {
            return 'IN_PROGRESS';
        }

        return $currentStatus ?: 'PLANNED';
    }

    /**
     * Normalisasi variasi penulisan teks tipe asesmen ke salah satu dari 8 skema resmi KAN.
     */
    public function normalizeType(?string $type, string $title = ''): string
    {
        $raw = trim((string) $type);

        // 1. Kecocokan langsung dengan 8 jenis resmi
        if (isset(Assessment::TYPES[$raw])) {
            return Assessment::TYPES[$raw];
        }

        // 2. Surveilen 1 + PRL
        if (
            in_array($raw, ['Surveilen 1 + PRL', 'S1 + PRL', 'S1+PRL', 'Surveilen 1 & PRL'], true)
            || (preg_match('/\b(Surveilen\s*1|S1)\b/i', $title) && preg_match('/\b(PRL|Perluasan)\b/i', $title))
            || (preg_match('/\b(Surveilen\s*1|S1)\b/i', $raw) && preg_match('/\b(PRL|Perluasan)\b/i', $raw))
        ) {
            return Assessment::TYPE_SURVEILEN_1_PRL;
        }

        // 3. Surveilen 2 + PRL
        if (
            in_array($raw, ['Surveilen 2 + PRL', 'S2 + PRL', 'S2+PRL', 'Surveilen 2 & PRL'], true)
            || (preg_match('/\b(Surveilen\s*2|S2)\b/i', $title) && preg_match('/\b(PRL|Perluasan)\b/i', $title))
            || (preg_match('/\b(Surveilen\s*2|S2)\b/i', $raw) && preg_match('/\b(PRL|Perluasan)\b/i', $raw))
        ) {
            return Assessment::TYPE_SURVEILEN_2_PRL;
        }

        // 4. Surveilen 1
        if (
            in_array($raw, ['S1', 'Surveilen 1', 'Surveilen 1 (S1)', 'SURVEILLANCE 1'], true)
            || preg_match('/\b(Surveilen\s*1|S1)\b/i', $title)
        ) {
            return Assessment::TYPE_SURVEILEN_1;
        }

        // 5. Surveilen 2
        if (
            in_array($raw, ['S2', 'Surveilen 2', 'Surveilen 2 (S2)', 'SURVEILLANCE 2'], true)
            || preg_match('/\b(Surveilen\s*2|S2)\b/i', $title)
        ) {
            return Assessment::TYPE_SURVEILEN_2;
        }

        // 6. STT
        if (
            in_array($raw, ['STT', 'Surveilen Tidak Terjadwal', 'Surveilen Tidak Terjadwal (STT)'], true)
            || preg_match('/\b(STT|Tidak Terjadwal)\b/i', $title)
        ) {
            return Assessment::TYPE_STT;
        }

        // 7. PRL (stand-alone)
        if (
            in_array($raw, ['PRL', 'Perluasan Ruang Lingkup', 'Perluasan Ruang Lingkup (PRL)', 'Perluasan Lingkup', 'SCOPE_EXTENSION'], true)
            || preg_match('/\b(PRL|Perluasan Lingkup|Perluasan Ruang Lingkup)\b/i', $title)
        ) {
            return Assessment::TYPE_PRL;
        }

        // 8. Re-Akreditasi (Akreditasi Ulang)
        if (
            in_array($raw, ['RA', 'Re-Akreditasi', 'Re-Akreditasi (RA)', 'REAKREDITASI', 'REASSESSMENT', 'Re-asesmen', 'Re-asesmen (Reassessment)', 'Akreditasi Ulang', 'Re-Akreditasi (Akreditasi Ulang)'], true)
            || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $title)
        ) {
            return Assessment::TYPE_RE_AKREDITASI;
        }

        // 9. Akreditasi Awal
        if (
            in_array($raw, ['INITIAL', 'Asesmen Awal', 'AA', 'Asesmen Awal (AA)', 'Akreditasi Awal'], true)
            || preg_match('/\b(Asesmen Awal|Akreditasi Awal|AA)\b/i', $title)
        ) {
            return Assessment::TYPE_AKREDITASI_AWAL;
        }

        // Fallback untuk "Surveilen" polos
        if (in_array($raw, ['SURVEILLANCE', 'Surveilen', 'Surveilen (Surveillance)'], true)) {
            return Assessment::TYPE_SURVEILEN_1;
        }

        return $raw ?: Assessment::TYPE_SURVEILEN_1;
    }

    /**
     * Hitung tanggal default toleransi pengisian dokumen asesmen.
     */
    public function calculateDefaultSubmissionDueDate(Assessment $assessment): ?Carbon
    {
        $lpk = $assessment->relationLoaded('lpk') ? $assessment->lpk : $assessment->lpk()->first();
        $cycleEnd = $lpk?->expired_at ?: ($lpk?->certificate_date ? $lpk->certificate_date->copy()->addYears(5) : null);
        $cycleStart = $lpk?->certificate_date ?: ($cycleEnd ? $cycleEnd->copy()->subYears(5) : null);

        if ($cycleStart && $cycleEnd) {
            $type = strtolower((string) ($assessment->assessment_type ?: ''));
            $title = strtolower((string) ($assessment->title ?: ''));

            $isRa = in_array($assessment->assessment_type, ['RA', 'Re-Akreditasi', 'Re-Akreditasi (RA)', 'REAKREDITASI', 'REASSESSMENT', 'Re-asesmen', 'Re-asesmen (Reassessment)', 'Akreditasi Ulang', 'Re-Akreditasi (Akreditasi Ulang)'], true)
                || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $title)
                || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $type);

            if ($isRa) {
                return $cycleEnd->copy()->endOfDay();
            }

            $isS2 = in_array($assessment->assessment_type, ['S2', 'Surveilen 2', 'Surveilen 2 (S2)', 'SURVEILLANCE 2'], true)
                || preg_match('/\b(Surveilen\s*2|S2)\b/i', $title)
                || preg_match('/\b(Surveilen\s*2|S2)\b/i', $type);

            if ($isS2) {
                return $cycleStart->copy()->addMonths(39)->endOfDay();
            }

            return $cycleStart->copy()->addMonths(18)->endOfDay();
        }

        if ($assessment->end_at) {
            return $assessment->end_at->copy()->addMonths(3)->endOfDay();
        }

        if ($assessment->start_at) {
            return $assessment->start_at->copy()->addMonths(3)->endOfDay();
        }

        return null;
    }

    /**
     * Batas waktu toleransi pengisian dokumen surveilen.
     */
    public function getSubmissionDueDate(Assessment $assessment): ?Carbon
    {
        if (! empty($assessment->getAttributes()['submission_due_date'])) {
            return Carbon::parse($assessment->getAttributes()['submission_due_date'])->endOfDay();
        }

        return $this->calculateDefaultSubmissionDueDate($assessment);
    }

    /**
     * Batas waktu kesempatan penyelesaian pembekuan surveilen (1 tahun sejak batas toleransi pengisian).
     */
    public function calculateSuspensionResolutionDeadline(Assessment $assessment): ?Carbon
    {
        $dueDate = $this->getSubmissionDueDate($assessment);
        if (! $dueDate) {
            return null;
        }

        return $dueDate->copy()->addYear()->endOfDay();
    }

    /**
     * Sisa hari menuju batas akhir 1 tahun kesempatan penyelesaian pembekuan sebelum pencabutan akreditasi.
     */
    public function calculateDaysRemainingSuspension(Assessment $assessment): ?int
    {
        $deadline = $this->calculateSuspensionResolutionDeadline($assessment);
        if (! $deadline) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);
    }

    /**
     * Memeriksa apakah asesmen ini merupakan surveilen periode lampau dari LPK yang saat ini berstatus aktif.
     */
    public function isPastSurveillanceForActiveLpk(Assessment $assessment): bool
    {
        $lpk = $assessment->relationLoaded('lpk') ? $assessment->lpk : $assessment->lpk()->first();
        if (! $lpk || $lpk->getRawOriginal('status') !== 'ACTIVE') {
            return false;
        }

        if ($lpk->isExpired()) {
            return false;
        }

        // Jika ada proses Tindakan Perbaikan (TP) aktif atau memiliki batas waktu TP khusus
        if (in_array($assessment->tp_status, [Assessment::TP_STATUS_IN_PROGRESS, Assessment::TP_STATUS_UNDER_VERIFICATION], true)) {
            return false;
        }
        if (! empty($assessment->tp_due_date) && $assessment->tp_status !== Assessment::TP_STATUS_SATISFIED) {
            return false;
        }

        // Jika ada laporan atau rapat EHA, ini asesmen yang sedang diproses
        if (! empty($assessment->report_date) || ! empty($assessment->eha_date)) {
            return false;
        }

        if ($assessment->end_at && $lpk->certificate_date && $assessment->end_at->lt($lpk->certificate_date->copy()->startOfDay())) {
            return true;
        }

        $rawStatus = $assessment->getAttributes()['status'] ?? null;
        if ($rawStatus === 'CANCELLED') {
            return false;
        }

        if ($rawStatus === 'COMPLETED' || $assessment->tp_status === Assessment::TP_STATUS_SATISFIED) {
            return true;
        }

        return (bool) ($assessment->end_at && $assessment->end_at->lt(now()->startOfYear()));
    }

    /**
     * Cek apakah masa kesempatan pembekuan 1 tahun telah terlampaui tanpa penyelesaian.
     */
    public function isSuspensionExpired(Assessment $assessment): bool
    {
        $rawStatus = $assessment->getAttributes()['status'] ?? null;
        if (in_array($rawStatus, ['COMPLETED', 'CANCELLED'], true)) {
            return false;
        }

        if ($assessment->tp_status === Assessment::TP_STATUS_SATISFIED || ! empty($assessment->sk_number) || ! empty($assessment->report_date)) {
            return false;
        }

        // Re-Akreditasi dan Akreditasi Awal bukan surveilen berkala, tidak dikenakan pembekuan atau pencabutan surveilen
        $type = strtolower((string) ($assessment->assessment_type ?: ''));
        $title = strtolower((string) ($assessment->title ?: ''));
        $isRaOrInitial = in_array($assessment->assessment_type, ['RA', 'Re-Akreditasi', 'Re-Akreditasi (RA)', 'REAKREDITASI', 'REASSESSMENT', 'Re-asesmen', 'Akreditasi Ulang', 'INITIAL', 'AA', 'Akreditasi Awal', 'Asesmen Awal'], true)
            || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA|Akreditasi Awal|Asesmen Awal|AA)\b/i', $title)
            || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA|Akreditasi Awal|Asesmen Awal|AA)\b/i', $type);

        if ($isRaOrInitial) {
            return false;
        }

        if ($this->isPastSurveillanceForActiveLpk($assessment)) {
            return false;
        }

        // Asesmen yang belum dimulai tidak dapat kedaluwarsa masa pembekuannya
        if ($assessment->start_at && now()->lt($assessment->start_at)) {
            return false;
        }

        $deadline = $this->calculateSuspensionResolutionDeadline($assessment);
        if (! $deadline) {
            return false;
        }

        return now()->gt($deadline);
    }

    /**
     * Cek apakah pengisian asesmen telah melewati batas toleransi bulan dan tahun kunjungan.
     */
    public function isSubmissionOverdue(Assessment $assessment): bool
    {
        $rawStatus = $assessment->getAttributes()['status'] ?? null;
        if (in_array($rawStatus, ['COMPLETED', 'CANCELLED'], true)) {
            return false;
        }

        if ($assessment->tp_status === Assessment::TP_STATUS_SATISFIED || ! empty($assessment->sk_number) || ! empty($assessment->report_date)) {
            return false;
        }

        if ($this->isPastSurveillanceForActiveLpk($assessment)) {
            return false;
        }

        if ($assessment->start_at && now()->lt($assessment->start_at)) {
            return false;
        }

        $dueDate = $this->getSubmissionDueDate($assessment);
        if (! $dueDate) {
            return false;
        }

        return now()->gt($dueDate);
    }
}
