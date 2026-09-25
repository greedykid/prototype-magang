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
     * Tentukan status asesmen secara otomatis berbasis tanggal pelaksanaan dan progres milestone (SLA KAN).
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
        if ($currentStatus === 'CANCELLED') {
            return 'CANCELLED';
        }

        // 1. Keputusan akhir: SK KAN sudah terbit atau Tindakan Perbaikan telah dinyatakan Memenuhi
        if (! empty($skNumber) || $tpStatus === self::TP_STATUS_SATISFIED || ! empty($tpSatisfiedAt)) {
            return 'COMPLETED';
        }

        $now = now();

        // 2. Batas waktu awal (SLA) tindakan perbaikan:
        // Jika sampai tanggal batas waktu awal (SLA) belum ada dinyatakan memenuhi, status otomatis DIBEKUKAN (SUSPENDED).
        $hasExplicitSla = ! empty($tpDueDate);
        $hasActiveTp = in_array($tpStatus, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION], true);

        if ($hasExplicitSla || $hasActiveTp) {
            $baseDueDate = $tpDueDate ?: self::calculateDefaultDueDateForType($assessmentType, $endAt ?: $startAt);
            if ($baseDueDate) {
                $effectiveDueDate = $baseDueDate->copy()->startOfDay();
                if ($tpHasExtension && (int) $tpExtensionMonths > 0) {
                    $effectiveDueDate->addMonths(min(1, max(1, (int) $tpExtensionMonths)));
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
                $defaultSla = self::calculateDefaultDueDateForType($assessmentType, $endAt);
                if ($defaultSla && $now->startOfDay()->gt($defaultSla->startOfDay())) {
                    return 'SUSPENDED';
                }

                return 'COMPLETED';
            }

            // PENTING: Jika tanggal telah lewat namun BELUM ADA TINDAKAN APAPUN
            // (tidak ada laporan, tidak ada EHA, tidak ada TP/VTP, dan tidak ada SK),
            // maka asesmen tersebut adalah agenda yang BELUM TERLAKSANA / LEWAT JADWAL (bukan selesai!).
            // Statusnya tetap PLANNED (Direncanakan).
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
        $raw = trim((string) $type);

        // 1. Kecocokan langsung dengan 8 jenis resmi
        if (isset(self::TYPES[$raw])) {
            return self::TYPES[$raw];
        }

        // 2. Surveilen 1 + PRL
        if (
            in_array($raw, ['Surveilen 1 + PRL', 'S1 + PRL', 'S1+PRL', 'Surveilen 1 & PRL'], true)
            || (preg_match('/\b(Surveilen\s*1|S1)\b/i', $title) && preg_match('/\b(PRL|Perluasan)\b/i', $title))
            || (preg_match('/\b(Surveilen\s*1|S1)\b/i', $raw) && preg_match('/\b(PRL|Perluasan)\b/i', $raw))
        ) {
            return self::TYPE_SURVEILEN_1_PRL;
        }

        // 3. Surveilen 2 + PRL
        if (
            in_array($raw, ['Surveilen 2 + PRL', 'S2 + PRL', 'S2+PRL', 'Surveilen 2 & PRL'], true)
            || (preg_match('/\b(Surveilen\s*2|S2)\b/i', $title) && preg_match('/\b(PRL|Perluasan)\b/i', $title))
            || (preg_match('/\b(Surveilen\s*2|S2)\b/i', $raw) && preg_match('/\b(PRL|Perluasan)\b/i', $raw))
        ) {
            return self::TYPE_SURVEILEN_2_PRL;
        }

        // 4. Surveilen 1
        if (
            in_array($raw, ['S1', 'Surveilen 1', 'Surveilen 1 (S1)', 'SURVEILLANCE 1'], true)
            || preg_match('/\b(Surveilen\s*1|S1)\b/i', $title)
        ) {
            return self::TYPE_SURVEILEN_1;
        }

        // 5. Surveilen 2
        if (
            in_array($raw, ['S2', 'Surveilen 2', 'Surveilen 2 (S2)', 'SURVEILLANCE 2'], true)
            || preg_match('/\b(Surveilen\s*2|S2)\b/i', $title)
        ) {
            return self::TYPE_SURVEILEN_2;
        }

        // 6. STT
        if (
            in_array($raw, ['STT', 'Surveilen Tidak Terjadwal', 'Surveilen Tidak Terjadwal (STT)'], true)
            || preg_match('/\b(STT|Tidak Terjadwal)\b/i', $title)
        ) {
            return self::TYPE_STT;
        }

        // 7. PRL (stand-alone)
        if (
            in_array($raw, ['PRL', 'Perluasan Ruang Lingkup', 'Perluasan Ruang Lingkup (PRL)', 'Perluasan Lingkup', 'SCOPE_EXTENSION'], true)
            || preg_match('/\b(PRL|Perluasan Lingkup|Perluasan Ruang Lingkup)\b/i', $title)
        ) {
            return self::TYPE_PRL;
        }

        // 8. Re-Akreditasi (Akreditasi Ulang)
        if (
            in_array($raw, ['RA', 'Re-Akreditasi', 'Re-Akreditasi (RA)', 'REAKREDITASI', 'REASSESSMENT', 'Re-asesmen', 'Re-asesmen (Reassessment)', 'Akreditasi Ulang', 'Re-Akreditasi (Akreditasi Ulang)'], true)
            || preg_match('/\b(Re-Akreditasi|Re-asesmen|REAKREDITASI|RA)\b/i', $title)
        ) {
            return self::TYPE_RE_AKREDITASI;
        }

        // 9. Akreditasi Awal
        if (
            in_array($raw, ['INITIAL', 'Asesmen Awal', 'AA', 'Asesmen Awal (AA)', 'Akreditasi Awal'], true)
            || preg_match('/\b(Asesmen Awal|Akreditasi Awal|AA)\b/i', $title)
        ) {
            return self::TYPE_AKREDITASI_AWAL;
        }

        // Fallback untuk "Surveilen" polos
        if (in_array($raw, ['SURVEILLANCE', 'Surveilen', 'Surveilen (Surveillance)'], true)) {
            return self::TYPE_SURVEILEN_1;
        }

        return $raw ?: self::TYPE_SURVEILEN_1;
    }

    public function getAssessmentTypeLabelAttribute(): string
    {
        return self::normalizeType($this->assessment_type, (string) $this->title);
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
     * Batas waktu kesempatan penyelesaian pembekuan surveilen (1 tahun sejak batas toleransi pengisian).
     * LPK diberi kesempatan 1 tahun untuk menyelesaikan surveilen sebelum status akreditasi dicabut.
     */
    public function getSuspensionResolutionDeadlineAttribute(): ?Carbon
    {
        $dueDate = $this->submission_due_date;
        if (! $dueDate) {
            return null;
        }

        return $dueDate->copy()->addYear()->endOfDay();
    }

    /**
     * Sisa hari menuju batas akhir 1 tahun kesempatan penyelesaian pembekuan sebelum pencabutan akreditasi.
     */
    public function getDaysRemainingSuspensionAttribute(): ?int
    {
        $deadline = $this->suspension_resolution_deadline;
        if (! $deadline) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);
    }

    /**
     * Memeriksa apakah asesmen ini merupakan surveilen periode lampau dari LPK yang saat ini berstatus aktif.
     * Jika LPK saat ini aktif, seluruh surveilen dari tahun-tahun sebelumnya otomatis sudah terealisasikan.
     */
    public function isPastSurveillanceForActiveLpk(): bool
    {
        $lpk = $this->relationLoaded('lpk') ? $this->lpk : $this->lpk()->first();
        if (! $lpk || $lpk->getRawOriginal('status') !== 'ACTIVE') {
            return false;
        }

        // Jika tanggal selesai berada di tahun sebelum tahun berjalan, otomatis terealisasikan
        return (bool) ($this->end_at && $this->end_at->lt(now()->startOfYear()));
    }

    /**
     * Cek apakah masa kesempatan pembekuan 1 tahun telah terlampaui tanpa penyelesaian.
     * Jika sudah lewat 1 tahun dari toleransi pengisian, maka akreditasi dicabut (REVOKED).
     */
    public function getIsSuspensionExpiredAttribute(): bool
    {
        $rawStatus = $this->attributes['status'] ?? null;
        if (in_array($rawStatus, ['COMPLETED', 'CANCELLED'], true)) {
            return false;
        }

        if ($this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->sk_number) || ! empty($this->report_date)) {
            return false;
        }

        if ($this->isPastSurveillanceForActiveLpk()) {
            return false;
        }

        $deadline = $this->suspension_resolution_deadline;
        if (! $deadline) {
            return false;
        }

        return now()->gt($deadline);
    }

    /**
     * Cek apakah pengisian asesmen telah melewati batas toleransi bulan dan tahun kunjungan.
     * Jika sudah lewat dari akhir bulan waktu kunjungan dan belum berstatus COMPLETED, maka dikatakan sudah lewat jadwal asesmen.
     */
    public function getIsSubmissionOverdueAttribute(): bool
    {
        $rawStatus = $this->attributes['status'] ?? null;
        if (in_array($rawStatus, ['COMPLETED', 'CANCELLED'], true)) {
            return false;
        }

        if ($this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->sk_number) || ! empty($this->report_date)) {
            return false;
        }

        if ($this->isPastSurveillanceForActiveLpk()) {
            return false;
        }

        $dueDate = $this->submission_due_date;
        if (! $dueDate) {
            return false;
        }

        return now()->gt($dueDate);
    }

    /**
     * Hitung batas waktu default SLA sesuai skema akreditasi KAN:
     * - Akreditasi Awal: 3 bulan
     * - S1, S2, RA, PRL, STT, Survailen, Re-asesmen: 2 bulan
     */
    public static function calculateDefaultDueDateForType(?string $type, ?Carbon $baseDate): ?Carbon
    {
        if (! $baseDate) {
            return null;
        }

        $normalizedType = self::normalizeType($type ?? '');
        $isInitial = in_array($normalizedType, [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'], true);

        return $baseDate->copy()->addMonths($isInitial ? 3 : 2)->startOfDay();
    }

    /**
     * Hitung batas waktu default TP & VTP sesuai regulasi KAN:
     * - Akreditasi Awal: 3 bulan
     * - S1, S2, RA, PRL, STT, Survailen, Re-asesmen: 2 bulan
     */
    public function calculateDefaultTpDueDate(): ?Carbon
    {
        if (! $this->end_at) {
            return null;
        }

        return self::calculateDefaultDueDateForType($this->assessment_type, $this->end_at);
    }

    /**
     * Hitung tanggal reminder TP & VTP (Notifikasi 1 Pengingat Awal):
     * Sesuai ketentuan SOP PIC Unit Akreditasi Lab:
     * - Akreditasi Awal (AA): 2 bulan setelah tanggal realisasi pelaksanaan (end_at)
     * - Lainnya (Sr1, Sr2, PRL, STT, RA): 1 bulan setelah tanggal realisasi pelaksanaan (end_at)
     */
    public function calculateDefaultTpReminderDate(): ?Carbon
    {
        if (! $this->end_at) {
            return null;
        }

        $isInitial = in_array($this->assessment_type_label, [self::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA'], true);

        return $this->end_at->copy()->addMonths($isInitial ? 2 : 1)->startOfDay();
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

    /**
     * Hitung tanggal reminder penerbitan SK KAN:
     * 10 hari kalender setelah tanggal verifikasi TP (VTP) selesai (tp_satisfied_at).
     * Berlaku khusus untuk kategori S1, S2, dan STT.
     */
    public function calculateDefaultSkReminderDate(): ?Carbon
    {
        if (! $this->tp_satisfied_at) {
            return null;
        }

        $type = strtoupper(trim((string) $this->assessment_type));
        $eligibleTypes = [
            'S1', 'S2', 'STT',
            'SURVEILEN 1', 'SURVEILEN 2', 'SURVEILEN TIDAK TERJADWAL',
            'SURVEILEN', 'SURVEILLANCE'
        ];

        if (! in_array($type, $eligibleTypes, true)) {
            return null;
        }

        return $this->tp_satisfied_at->copy()->addDays(10)->startOfDay();
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
        if ($this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at) || ! empty($this->sk_number)) {
            return false;
        }

        // Asesmen lampau untuk LPK aktif otomatis terealisasi
        if ($this->isPastSurveillanceForActiveLpk()) {
            return false;
        }

        // Jika memiliki tanggal batas waktu SLA eksplisit (tp_due_date)
        if ($this->tp_due_date) {
            $effectiveDueDate = $this->effective_tp_due_date;
            return $effectiveDueDate && now()->startOfDay()->gt($effectiveDueDate);
        }

        // Jika dalam proses perbaikan aktif atau asesmen telah terlaksana (ada laporan/EHA)
        $hasActiveTp = in_array($this->tp_status, [self::TP_STATUS_IN_PROGRESS, self::TP_STATUS_UNDER_VERIFICATION], true)
            || ! empty($this->report_date)
            || ! empty($this->eha_date);

        if ($hasActiveTp) {
            $effectiveDueDate = $this->effective_tp_due_date;
            return $effectiveDueDate && now()->startOfDay()->gt($effectiveDueDate);
        }

        return false;
    }

    /**
     * Label status tindakan perbaikan dalam bahasa Indonesia.
     */
    public function getTpStatusLabelAttribute(): string
    {
        if ($this->isPastSurveillanceForActiveLpk()) {
            return 'Dinyatakan Memenuhi (Selesai)';
        }

        if ($this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->tp_satisfied_at)) {
            return 'Dinyatakan Memenuhi (Selesai)';
        }

        if ($this->is_suspension_expired || $this->status === 'REVOKED') {
            return 'Akreditasi Dicabut (Lewat 1 Tahun)';
        }

        if ($this->is_tp_overdue || $this->status === 'SUSPENDED') {
            return 'Dibekukan (Lewat SLA)';
        }

        if (in_array($this->status, ['PLANNED', 'SCHEDULED'], true) && $this->tp_status === self::TP_STATUS_NONE && ! $this->tp_due_date) {
            return 'Menunggu Pelaksanaan Asesmen';
        }

        return 'Sedang Berlangsung';
    }

    /**
     * Badge status SLA KAN terintegrasi untuk tampilan tabel dan detail.
     */
    public function getTpSlaBadgeAttribute(): array
    {
        if ($this->isPastSurveillanceForActiveLpk()) {
            return [
                'type' => 'success',
                'label' => 'Terealisasi',
                'detail' => 'Asesmen surveilen periode lampau otomatis terealisasikan (LPK berstatus aktif)',
            ];
        }

        if ($this->tp_status === self::TP_STATUS_SATISFIED || ! empty($this->sk_number)) {
            return [
                'type' => 'success',
                'label' => 'Memenuhi',
                'detail' => $this->tp_satisfied_at ? 'Dinyatakan pada ' . $this->tp_satisfied_at->format('d M Y') : 'Tindakan perbaikan diterima',
            ];
        }

        if ($this->is_suspension_expired || $this->status === 'REVOKED') {
            return [
                'type' => 'danger',
                'label' => 'Akreditasi Dicabut',
                'detail' => 'Status akreditasi dicabut: melewati batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen tanpa penyelesaian',
            ];
        }

        if ($this->is_tp_overdue || $this->status === 'SUSPENDED') {
            $days = $this->days_remaining_tp;
            $overdueDays = abs($days ?? 0);
            return [
                'type' => 'suspended',
                'label' => 'Dibekukan (Terlambat ' . ($overdueDays > 0 ? $overdueDays . ' Hari' : '') . ')',
                'detail' => 'Status dibekukan: melewati batas waktu awal (SLA KAN) (' . ($this->effective_tp_due_date ? $this->effective_tp_due_date->format('d M Y') : '-') . ') belum dinyatakan memenuhi',
            ];
        }

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

        $days = $this->days_remaining_tp;

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
            // 3. Jika asesmen melewati batas waktu awal (SLA) tanpa dinyatakan memenuhi,
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

    public function expense(): HasOne
    {
        return $this->hasOne(AssessmentExpense::class);
    }
}
