<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Carbon;

class AssessmentTpService
{
    /**
     * Hitung batas waktu default sesuai skema akreditasi KAN:
     * - Akreditasi Awal: 3 bulan
     * - S1, S2, RA, PRL, STT, Survailen, Re-asesmen: 2 bulan
     */
    public function calculateDefaultDueDate(?string $type, ?Carbon $baseDate): ?Carbon
    {
        if (! $baseDate) {
            return null;
        }

        $normalizedType = Assessment::normalizeType($type ?? '');
        $isInitial = in_array($normalizedType, [Assessment::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'], true);

        return $baseDate->copy()->addMonths($isInitial ? 3 : 2)->startOfDay();
    }

    /**
     * Hitung batas waktu default TP & VTP sesuai regulasi KAN.
     */
    public function calculateDefaultTpDueDate(Assessment $assessment): ?Carbon
    {
        if (! $assessment->end_at) {
            return null;
        }

        return $this->calculateDefaultDueDate($assessment->assessment_type, $assessment->end_at);
    }

    /**
     * Hitung tanggal reminder TP & VTP:
     * - Akreditasi Awal (AA): 2 bulan setelah tanggal realisasi pelaksanaan (end_at)
     * - Lainnya (Sr1, Sr2, PRL, STT, RA): 1 bulan setelah tanggal realisasi pelaksanaan (end_at)
     */
    public function calculateDefaultTpReminderDate(Assessment $assessment): ?Carbon
    {
        if (! $assessment->end_at) {
            return null;
        }

        $isInitial = in_array($assessment->assessment_type_label, [Assessment::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'], true);

        return $assessment->end_at->copy()->addMonths($isInitial ? 2 : 1)->startOfDay();
    }

    /**
     * Hitung tanggal reminder penerbitan SK KAN:
     * 10 hari kalender setelah tanggal verifikasi TP (VTP) selesai (tp_satisfied_at).
     * Berlaku khusus untuk kategori S1, S2, dan STT.
     */
    public function calculateDefaultSkReminderDate(Assessment $assessment): ?Carbon
    {
        if (! $assessment->tp_satisfied_at) {
            return null;
        }

        $type = strtoupper(trim((string) $assessment->assessment_type));
        $eligibleTypes = [
            'S1', 'S2', 'STT',
            'SURVEILEN 1', 'SURVEILEN 2', 'SURVEILEN TIDAK TERJADWAL',
            'SURVEILEN', 'SURVEILLANCE',
        ];

        if (! in_array($type, $eligibleTypes, true)) {
            return null;
        }

        return $assessment->tp_satisfied_at->copy()->addDays(10)->startOfDay();
    }

    /**
     * Batas waktu efektif TP & VTP.
     * Mengakomodasi perpanjangan masa perbaikan maksimal 1 bulan jika ada surat permohonan resmi.
     */
    public function calculateEffectiveTpDueDate(Assessment $assessment): ?Carbon
    {
        $baseDate = $assessment->tp_due_date ?: $this->calculateDefaultTpDueDate($assessment);

        if (! $baseDate) {
            return null;
        }

        if ($assessment->tp_has_extension && $assessment->tp_extension_months > 0) {
            $defaultBase = $this->calculateDefaultTpDueDate($assessment);
            $extensionMonths = min(1, max(1, (int) $assessment->tp_extension_months));

            // Jika tp_due_date sudah sama persis dengan tanggal setelah perpanjangan,
            // gunakan tp_due_date tersebut agar tidak bertambah dobel menjadi +2 bulan.
            if ($defaultBase && $assessment->tp_due_date && $assessment->tp_due_date->toDateString() === $defaultBase->copy()->addMonths($extensionMonths)->toDateString()) {
                return $assessment->tp_due_date->copy()->startOfDay();
            }

            return $baseDate->copy()->addMonths($extensionMonths)->startOfDay();
        }

        return $baseDate->copy()->startOfDay();
    }

    /**
     * Sisa hari menuju batas akhir efektif TP & VTP (positif = sisa hari, negatif = lewat jatuh tempo).
     */
    public function calculateDaysRemainingTp(Assessment $assessment): ?int
    {
        $effectiveDueDate = $this->calculateEffectiveTpDueDate($assessment);

        if (! $effectiveDueDate) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($effectiveDueDate, false);
    }

    /**
     * Cek apakah TP & VTP melewati batas waktu yang ditetapkan KAN.
     */
    public function isTpOverdue(Assessment $assessment): bool
    {
        if ($assessment->tp_status === Assessment::TP_STATUS_SATISFIED || ! empty($assessment->tp_satisfied_at) || ! empty($assessment->sk_number)) {
            return false;
        }

        // Asesmen lampau untuk LPK aktif otomatis terealisasi
        if ($assessment->isPastSurveillanceForActiveLpk()) {
            return false;
        }

        // Jika memiliki tanggal batas waktu eksplisit (tp_due_date)
        if ($assessment->tp_due_date) {
            $effectiveDueDate = $this->calculateEffectiveTpDueDate($assessment);
            return $effectiveDueDate && now()->startOfDay()->gt($effectiveDueDate);
        }

        // Jika dalam proses perbaikan aktif atau asesmen telah terlaksana (ada laporan/EHA)
        $hasActiveTp = in_array($assessment->tp_status, [Assessment::TP_STATUS_IN_PROGRESS, Assessment::TP_STATUS_UNDER_VERIFICATION], true)
            || ! empty($assessment->report_date)
            || ! empty($assessment->eha_date);

        if ($hasActiveTp) {
            $effectiveDueDate = $this->calculateEffectiveTpDueDate($assessment);
            return $effectiveDueDate && now()->startOfDay()->gt($effectiveDueDate);
        }

        return false;
    }

    /**
     * Label status tindakan perbaikan dalam bahasa Indonesia.
     */
    public function getTpStatusLabel(Assessment $assessment): string
    {
        if ($assessment->isPastSurveillanceForActiveLpk()) {
            return 'Dinyatakan Memenuhi (Selesai)';
        }

        if ($assessment->tp_status === Assessment::TP_STATUS_SATISFIED || ! empty($assessment->tp_satisfied_at)) {
            return 'Dinyatakan Memenuhi (Selesai)';
        }

        if ($assessment->is_suspension_expired || $assessment->status === 'REVOKED') {
            return 'Akreditasi Dicabut (Lewat 1 Tahun)';
        }

        if ($this->isTpOverdue($assessment) || $assessment->status === 'SUSPENDED') {
            return 'Dibekukan (Lewat Batas Waktu)';
        }

        if (in_array($assessment->status, ['PLANNED', 'SCHEDULED'], true) && $assessment->tp_status === Assessment::TP_STATUS_NONE && ! $assessment->tp_due_date) {
            return 'Menunggu Pelaksanaan Asesmen';
        }

        return 'Sedang Berlangsung';
    }

    /**
     * Badge status batas waktu KAN terintegrasi untuk tampilan tabel dan detail.
     */
    public function getTpSlaBadge(Assessment $assessment): array
    {
        if ($assessment->tp_status === Assessment::TP_STATUS_SATISFIED || ! empty($assessment->sk_number)) {
            return [
                'type' => 'success',
                'label' => 'Memenuhi',
                'detail' => $assessment->tp_satisfied_at ? 'Dinyatakan pada ' . $assessment->tp_satisfied_at->format('d M Y') : 'Tindakan perbaikan diterima',
            ];
        }

        if ($assessment->isPastSurveillanceForActiveLpk()) {
            return [
                'type' => 'success',
                'label' => 'Terealisasi',
                'detail' => 'Asesmen surveilen periode lampau otomatis terealisasikan (LPK berstatus aktif)',
            ];
        }

        if ($assessment->is_suspension_expired || $assessment->status === 'REVOKED') {
            return [
                'type' => 'danger',
                'label' => 'Akreditasi Dicabut',
                'detail' => 'Status akreditasi dicabut: melewati batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen tanpa penyelesaian',
            ];
        }

        if ($this->isTpOverdue($assessment) || $assessment->status === 'SUSPENDED') {
            $days = $this->calculateDaysRemainingTp($assessment);
            $overdueDays = abs($days ?? 0);
            return [
                'type' => 'suspended',
                'label' => 'Dibekukan (Terlambat ' . ($overdueDays > 0 ? $overdueDays . ' Hari' : '') . ')',
                'detail' => 'Status dibekukan: melewati batas waktu awal (' . ($assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-') . ') belum dinyatakan memenuhi',
            ];
        }

        if (in_array($assessment->status, ['PLANNED', 'SCHEDULED'], true) && $assessment->tp_status === Assessment::TP_STATUS_NONE) {
            return [
                'type' => 'neutral',
                'label' => 'Belum Asesmen',
                'detail' => 'Kunjungan asesmen belum dilaksanakan (belum ada temuan KNC)',
            ];
        }

        if ($assessment->tp_status === Assessment::TP_STATUS_NONE) {
            return [
                'type' => 'neutral',
                'label' => 'Nihil Temuan',
                'detail' => 'Tidak memerlukan perbaikan',
            ];
        }

        $days = $this->calculateDaysRemainingTp($assessment);

        if ($days !== null && $days <= 14) {
            return [
                'type' => 'warning',
                'label' => 'Jatuh Tempo (' . $days . ' Hari)',
                'detail' => 'Batas akhir ' . ($assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-'),
            ];
        }

        return [
            'type' => 'info',
            'label' => 'Dalam Proses' . ($assessment->tp_has_extension ? ' (+1 Bulan)' : ''),
            'detail' => 'Sisa ' . ($days ?? 0) . ' hari hingga ' . ($assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-'),
        ];
    }

    /**
     * Hitung rentang durasi (dalam hari kalender) dari pelaksanaan asesmen hingga tanggal penerbitan SK.
     */
    public function calculateSkLeadTimeDays(Assessment $assessment): ?int
    {
        $baseDate = $assessment->end_at ?? $assessment->start_at;
        if (! $baseDate || ! $assessment->sk_date) {
            return null;
        }

        return (int) $baseDate->copy()->startOfDay()->diffInDays($assessment->sk_date->copy()->startOfDay(), false);
    }

    /**
     * Label representatif untuk rentang waktu pelaksanaan asesmen sampai tanggal SK.
     */
    public function calculateSkLeadTimeLabel(Assessment $assessment): ?string
    {
        $days = $this->calculateSkLeadTimeDays($assessment);
        if ($days === null) {
            return null;
        }

        if ($days < 0) {
            return abs($days) . ' hari sebelum asesmen';
        }

        if ($days === 0) {
            return '0 hari (pada hari pelaksanaan)';
        }

        $baseDate = ($assessment->end_at ?? $assessment->start_at)->copy()->startOfDay();
        $skDate = $assessment->sk_date->copy()->startOfDay();
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

        $humanFormatted = ! empty($parts) ? implode(' ', $parts) : "{$days} hari";

        return "{$days} hari ({$humanFormatted})";
    }
}
