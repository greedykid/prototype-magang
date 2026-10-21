<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleSheetsReportController extends Controller
{
    /**
     * Unduh Master Data LPK (format CSV atau XLSX) untuk pengguna login.
     */
    public function exportLpks(Request $request): StreamedResponse
    {
        $ids = $this->parseIds($request);
        $format = strtolower((string) $request->input('format', $request->query('format', 'csv')));

        if ($format === 'xlsx') {
            return $this->generateLpksXlsx($ids, $request->user());
        }

        return $this->generateLpksCsv(false, $ids, $request->user());
    }

    /**
     * Live Feed CSV Master Data LPK untuk Google Sheets.
     */
    public function feedLpks(Request $request): Response|StreamedResponse
    {
        $validationResult = $this->checkFeedAccess($request);
        if ($validationResult !== null) {
            return $validationResult;
        }

        Log::info('Google Sheets live feed accessed: LPKs', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->generateLpksCsv(true);
    }

    /**
     * Download Rekapitulasi Program Asesmen (format CSV atau XLSX) untuk pengguna login.
     */
    public function exportAssessments(Request $request): StreamedResponse
    {
        $ids = $this->parseIds($request);
        $format = strtolower((string) $request->input('format', $request->query('format', 'csv')));

        if ($format === 'xlsx') {
            return $this->generateAssessmentsXlsx($ids, $request->user());
        }

        return $this->generateAssessmentsCsv(false, $ids, $request->user());
    }

    /**
     * Live Feed CSV Program Asesmen untuk Google Sheets.
     */
    public function feedAssessments(Request $request): Response|StreamedResponse
    {
        $validationResult = $this->checkFeedAccess($request);
        if ($validationResult !== null) {
            return $validationResult;
        }

        Log::info('Google Sheets live feed accessed: Assessments', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->generateAssessmentsCsv(true);
    }

    /**
     * Validasi otorisasi live feed Google Sheets.
     */
    protected function checkFeedAccess(Request $request): ?Response
    {
        $configured = trim((string) config('services.sheets.feed_key', ''));

        // Tolak jika belum dikonfigurasi atau masih menggunakan token default/dummy yang tidak aman
        if ($configured === '' || in_array($configured, ['simasadi-live', 'ganti-dengan-token-rahasia-acak'], true)) {
            return response("Unauthorized: Live feed Google Sheets dinonaktifkan atau belum dikonfigurasi di file .env server.\n", 401, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        // Dukung otorisasi via URL bertanda tangan (HMAC), header aman, atau parameter ?key=
        if ($request->hasValidSignature()) {
            return null;
        }

        $provided = (string) (
            $request->header('X-Feed-Key')
            ?: $request->bearerToken()
            ?: $request->query('key', '')
        );

        if ($provided === '' || ! hash_equals($configured, $provided)) {
            return response("Unauthorized: Parameter '?key=' atau header otorisasi tidak valid untuk live feed Google Sheets SIMASADI.\n", 401, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return null;
    }

    /**
     * Format label status asesmen ke bahasa Indonesia.
     */
    protected function formatAssessmentStatus(?string $status): string
    {
        return match ($status) {
            'PLANNED' => 'Direncanakan',
            'SCHEDULED' => 'Terjadwal',
            'IN_PROGRESS' => 'Sedang Berlangsung',
            'COMPLETED' => 'Selesai',
            'SUSPENDED' => 'Dibekukan',
            'REVOKED' => 'Dicabut',
            'CANCELLED' => 'Dibatalkan',
            default => Assessment::STATUS_LABELS[$status] ?? ($status ?: '-'),
        };
    }

    /**
     * Format label status tindakan perbaikan (TP) ke bahasa Indonesia.
     */
    protected function formatTpStatus(?string $tpStatus): string
    {
        return match ($tpStatus) {
            'NONE' => 'Tidak Ada Temuan',
            'IN_PROGRESS' => 'Penyusunan Perbaikan',
            'UNDER_VERIFICATION' => 'Dalam Verifikasi',
            'SATISFIED' => 'Dinyatakan Memenuhi',
            'NOT_APPLICABLE', null, '' => '-',
            default => Assessment::TP_STATUSES[$tpStatus] ?? $tpStatus,
        };
    }

    /**
     * Format label status operasional LPK ke bahasa Indonesia.
     */
    protected function formatLpkStatus(Lpk $lpk): string
    {
        return $lpk->dynamic_status_label ?: match ($lpk->status) {
            'ACTIVE' => 'Aktif',
            'INACTIVE' => 'Tidak Aktif',
            'SUSPENDED' => 'Dibekukan',
            'REVOKED' => 'Dicabut',
            'EXPIRED' => 'Kedaluwarsa',
            default => $lpk->status ?: 'Aktif',
        };
    }

    /**
     * Format tanggal untuk CSV dengan prefix apostrof agar Google Sheets tidak mengonversi ke angka serial (46021).
     */
    protected function formatDateForCsv(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        if ($date instanceof \DateTimeInterface) {
            return "'" . $date->format('d/m/Y');
        }

        return "'" . (string) $date;
    }

    /**
     * Sanitasi sel CSV untuk mencegah Formula / CSV Injection (CWE-1236).
     */
    protected function sanitizeCsvCell(mixed $value): string
    {
        $str = (string) ($value ?? '');
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            if ($str[0] === '-' && is_numeric($str)) {
                return $str;
            }
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Susun baris data master LPK untuk ekspor CSV / Excel.
     */
    protected function buildLpkRow(Lpk $lpk, bool $isCsv = false): array
    {
        return [
            'LPK-' . str_pad((string) $lpk->id, 4, '0', STR_PAD_LEFT),
            $lpk->no_reg ?: $lpk->registration_number,
            $lpk->accreditation_number ?: $lpk->registration_number,
            $lpk->accreditation_type ?: 'Laboratorium Penguji',
            $lpk->name,
            $lpk->scope ?: '-',
            $this->formatLpkStatus($lpk),
            $isCsv
                ? $this->formatDateForCsv($lpk->certificate_date)
                : ($lpk->certificate_date ? $lpk->certificate_date->translatedFormat('d F Y') : '-'),
            $isCsv
                ? $this->formatDateForCsv($lpk->expired_at)
                : ($lpk->expired_at ? $lpk->expired_at->translatedFormat('d F Y') : '-'),
            $lpk->drive_url ?: '-',
        ];
    }

    /**
     * Susun baris data program asesmen untuk ekspor CSV / Excel.
     */
    protected function buildAssessmentRow(Assessment $asm, bool $isCsv = false): array
    {
        return [
            'ASM-' . str_pad((string) $asm->id, 4, '0', STR_PAD_LEFT),
            $asm->title,
            $asm->lpk?->name ?? '-',
            $asm->assessment_type,
            $isCsv
                ? $this->formatDateForCsv($asm->start_at)
                : ($asm->start_at ? $asm->start_at->translatedFormat('d F Y') : '-'),
            $isCsv
                ? $this->formatDateForCsv($asm->end_at)
                : ($asm->end_at ? $asm->end_at->translatedFormat('d F Y') : '-'),
            $this->formatAssessmentStatus($asm->status),
            $this->formatTpStatus($asm->tp_status),
            $isCsv
                ? $this->formatDateForCsv($asm->effective_tp_due_date)
                : ($asm->effective_tp_due_date ? $asm->effective_tp_due_date->translatedFormat('d F Y') : '-'),
        ];
    }

    /**
     * Parse daftar ID dari parameter request (bisa string koma atau array).
     */
    protected function parseIds(Request $request): ?array
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return null;
        }

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $filtered = array_values(array_filter(array_map('intval', (array) $ids)));
        return ! empty($filtered) ? $filtered : null;
    }

    /**
     * Generate CSV Data Master LPK.
     */
    protected function generateLpksCsv(bool $isFeed, ?array $ids = null, ?User $user = null): StreamedResponse
    {
        $filename = 'data-master-lpk-simasadi-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => ($isFeed ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $columns = [
            'ID LPK',
            'No Reg LPK',
            'No Akreditasi',
            'Jenis Akreditasi',
            'Nama Lembaga Penilaian Kesesuaian',
            'Ruang Lingkup Akreditasi',
            'Status Operasional',
            'Tanggal Terbit Sertifikat',
            'Masa Berlaku Akreditasi',
            'Tautan Drive Dokumen (Sertifikat & Amandemen)',
        ];

        return response()->stream(function () use ($columns, $ids, $user) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $query = Lpk::with(['assessments' => function ($q) {
                $q->select([
                    'id', 'lpk_id', 'title', 'assessment_type', 'start_at', 'end_at',
                    'status', 'tp_status', 'tp_due_date', 'tp_has_extension', 'tp_extension_months',
                    'tp_extension_date', 'tp_satisfied_at', 'submission_due_date',
                    'report_date', 'eha_date', 'sk_number',
                ]);
            }])
                ->when($user && $user->isPic(), fn ($q) => $q->accessibleBy($user))
                ->orderBy('name');

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }

            $lpks = $query->cursor();

            foreach ($lpks as $lpk) {
                foreach ($lpk->assessments as $asm) {
                    $asm->setRelation('lpk', $lpk);
                }
                $row = $this->buildLpkRow($lpk, true);
                fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $row));
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate CSV Program Asesmen.
     */
    protected function generateAssessmentsCsv(bool $isFeed, ?array $ids = null, ?User $user = null): StreamedResponse
    {
        $filename = 'jadwal-asesmen-simasadi-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => ($isFeed ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $columns = [
            'ID Asesmen',
            'Judul Agenda Asesmen',
            'Nama LPK',
            'Jenis Asesmen',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Status Pelaksanaan',
            'Status Tindakan Perbaikan',
            'Batas Waktu TP',
        ];

        return response()->stream(function () use ($columns, $ids, $user) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $query = Assessment::with(['lpk'])
                ->when($user && $user->isPic(), fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
                ->orderByDesc('start_at');

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }

            $assessments = $query->cursor();

            foreach ($assessments as $asm) {
                $row = $this->buildAssessmentRow($asm, true);
                fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $row));
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate XLSX Data Master LPK.
     */
    protected function generateLpksXlsx(?array $ids = null, ?User $user = null): StreamedResponse
    {
        @ini_set('memory_limit', '512M');

        $filename = 'data-master-lpk-simasadi-' . date('Y-m-d') . '.xlsx';

        $columns = [
            'ID LPK',
            'No Reg LPK',
            'No Akreditasi',
            'Jenis Akreditasi',
            'Nama Lembaga Penilaian Kesesuaian',
            'Ruang Lingkup Akreditasi',
            'Status Operasional',
            'Tanggal Terbit Sertifikat',
            'Masa Berlaku Akreditasi',
            'Tautan Drive Dokumen (Sertifikat & Amandemen)',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Master LPK');

        // Header
        $sheet->fromArray([$columns], null, 'A1');

        $query = Lpk::with(['assessments' => function ($q) {
            $q->select([
                'id', 'lpk_id', 'title', 'assessment_type', 'start_at', 'end_at',
                'status', 'tp_status', 'tp_due_date', 'tp_has_extension', 'tp_extension_months',
                'tp_extension_date', 'tp_satisfied_at', 'submission_due_date',
                'report_date', 'eha_date', 'sk_number',
            ]);
        }])
            ->when($user && $user->isPic(), fn ($q) => $q->accessibleBy($user))
            ->orderBy('name');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $batch = [];
        $batchStartRow = 2;
        $rowIdx = 2;

        foreach ($query->cursor() as $lpk) {
            foreach ($lpk->assessments as $asm) {
                $asm->setRelation('lpk', $lpk);
            }
            $row = $this->buildLpkRow($lpk, false);

            $batch[] = array_map([$this, 'sanitizeCsvCell'], $row);
            $rowIdx++;

            if (count($batch) >= 200) {
                $sheet->fromArray($batch, null, 'A' . $batchStartRow);
                $batchStartRow = $rowIdx;
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $sheet->fromArray($batch, null, 'A' . $batchStartRow);
        }

        $highestColumn = Coordinate::stringFromColumnIndex(count($columns));
        $lastRow = max(1, $rowIdx - 1);

        // Header Styling
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0075DE'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // AutoFilter & Freeze Pane
        $sheet->setAutoFilter('A1:' . $highestColumn . $lastRow);
        $sheet->freezePane('A2');

        // Alignment formatting
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Lebar kolom diatur hanya mengikuti panjang teks header kolom (bukan isi data)
        foreach ($columns as $idx => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->getColumnDimension($colLetter)->setAutoSize(false);
            $sheet->getColumnDimension($colLetter)->setWidth(max(12, mb_strlen($headerText) + 4));
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate XLSX Program Asesmen.
     */
    protected function generateAssessmentsXlsx(?array $ids = null, ?User $user = null): StreamedResponse
    {
        @ini_set('memory_limit', '512M');

        $filename = 'jadwal-asesmen-simasadi-' . date('Y-m-d') . '.xlsx';

        $columns = [
            'ID Asesmen',
            'Judul Agenda Asesmen',
            'Nama LPK',
            'Jenis Asesmen',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Status Pelaksanaan',
            'Status Tindakan Perbaikan',
            'Batas Waktu TP',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Asesmen');

        // Header
        $sheet->fromArray([$columns], null, 'A1');

        $query = Assessment::with(['lpk'])
            ->when($user && $user->isPic(), fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->orderByDesc('start_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $batch = [];
        $batchStartRow = 2;
        $rowIdx = 2;

        foreach ($query->cursor() as $asm) {
            $row = $this->buildAssessmentRow($asm, false);

            $batch[] = array_map([$this, 'sanitizeCsvCell'], $row);
            $rowIdx++;

            if (count($batch) >= 200) {
                $sheet->fromArray($batch, null, 'A' . $batchStartRow);
                $batchStartRow = $rowIdx;
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $sheet->fromArray($batch, null, 'A' . $batchStartRow);
        }

        $highestColumn = Coordinate::stringFromColumnIndex(count($columns));
        $lastRow = max(1, $rowIdx - 1);

        // Header Styling
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0075DE'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // AutoFilter & Freeze Pane
        $sheet->setAutoFilter('A1:' . $highestColumn . $lastRow);
        $sheet->freezePane('A2');

        // Alignment formatting
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Lebar kolom diatur hanya mengikuti panjang teks header kolom (bukan isi data)
        foreach ($columns as $idx => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->getColumnDimension($colLetter)->setAutoSize(false);
            $sheet->getColumnDimension($colLetter)->setWidth(max(12, mb_strlen($headerText) + 4));
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
