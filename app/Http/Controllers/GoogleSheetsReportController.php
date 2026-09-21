<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleSheetsReportController extends Controller
{
    /**
     * Kunci akses publik untuk bot crawler Google Sheets (=IMPORTDATA).
     */
    public const DEFAULT_FEED_KEY = 'simasadi-live';

    /**
     * Download CSV Rekapitulasi Biaya Asesor (SBM) untuk pengguna login.
     */
    public function exportExpenses(): StreamedResponse
    {
        return $this->generateExpensesCsv(false);
    }

    /**
     * Live Feed CSV Biaya Asesor untuk formula =IMPORTDATA("...") Google Sheets.
     */
    public function feedExpenses(Request $request): Response|StreamedResponse
    {
        if (! $this->isValidFeedKey($request)) {
            return response("Unauthorized: Parameter '?key=' tidak valid untuk live feed Google Sheets SIMASADI.\n", 401, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return $this->generateExpensesCsv(true);
    }

    /**
     * Download CSV Master Data LPK untuk pengguna login.
     */
    public function exportLpks(): StreamedResponse
    {
        return $this->generateLpksCsv(false);
    }

    /**
     * Live Feed CSV Master Data LPK untuk Google Sheets.
     */
    public function feedLpks(Request $request): Response|StreamedResponse
    {
        if (! $this->isValidFeedKey($request)) {
            return response("Unauthorized: Parameter '?key=' tidak valid untuk live feed Google Sheets SIMASADI.\n", 401, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return $this->generateLpksCsv(true);
    }

    /**
     * Download CSV Rekapitulasi Program Asesmen untuk pengguna login.
     */
    public function exportAssessments(): StreamedResponse
    {
        return $this->generateAssessmentsCsv(false);
    }

    /**
     * Live Feed CSV Program Asesmen untuk Google Sheets.
     */
    public function feedAssessments(Request $request): Response|StreamedResponse
    {
        if (! $this->isValidFeedKey($request)) {
            return response("Unauthorized: Parameter '?key=' tidak valid untuk live feed Google Sheets SIMASADI.\n", 401, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return $this->generateAssessmentsCsv(true);
    }

    /**
     * Validasi key feed Google Sheets.
     */
    protected function isValidFeedKey(Request $request): bool
    {
        $expected = env('SHEETS_FEED_KEY', self::DEFAULT_FEED_KEY);
        return $request->query('key') === $expected;
    }

    /**
     * Generate CSV Biaya Asesor (SBM).
     */
    protected function generateExpensesCsv(bool $isFeed): StreamedResponse
    {
        $filename = 'rekap-biaya-sbm-simasadi-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => ($isFeed ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $columns = [
            'ID Asesmen',
            'Judul Agenda Asesmen',
            'Nama LPK',
            'Nomor Registrasi LPK',
            'Jenis Asesmen',
            'Waktu Pelaksanaan',
            'Status Asesmen',
            'Uang Harian (Rp)',
            'Transportasi (Rp)',
            'Akomodasi (Rp)',
            'Paket Data (Rp)',
            'Total Biaya Realisasi (Rp)',
            'Status Verifikasi SBM',
            'Bukti Kwitansi / SPPD',
            'Catatan Verifikator KAN',
            'Waktu Verifikasi',
        ];

        return response()->stream(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            // Output UTF-8 BOM untuk kompatibilitas Microsoft Excel dan Google Sheets
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $assessments = Assessment::with(['lpk', 'expense'])
                ->orderByDesc('start_at')
                ->cursor();

            foreach ($assessments as $item) {
                $expense = $item->expense;
                $row = [
                    'ASM-' . str_pad((string) $item->id, 4, '0', STR_PAD_LEFT),
                    $item->title,
                    $item->lpk?->name ?? '-',
                    $item->lpk?->registration_number ?? '-',
                    $item->assessment_type,
                    $item->start_at ? $item->start_at->format('d/m/Y H:i') : '-',
                    $item->status,
                    $expense ? (int) $expense->daily_allowance : 0,
                    $expense ? (int) $expense->transport_cost : 0,
                    $expense ? (int) $expense->accommodation_cost : 0,
                    $expense ? (int) $expense->package_data_cost : 0,
                    $expense ? (int) $expense->total_cost : 0,
                    $expense?->status ?? 'BELUM_DILAPORKAN',
                    $expense?->receipt_note ?? '-',
                    $expense?->verification_notes ?? '-',
                    $expense?->verified_at ? $expense->verified_at->format('d/m/Y H:i') : '-',
                ];

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate CSV Data Master LPK.
     */
    protected function generateLpksCsv(bool $isFeed): StreamedResponse
    {
        $filename = 'data-master-lpk-simasadi-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => ($isFeed ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $columns = [
            'ID LPK',
            'Nomor Registrasi',
            'Nama Lembaga Penilaian Kesesuaian',
            'Ruang Lingkup Akreditasi',
            'Status Operasional',
            'Masa Berlaku Akreditasi',
            'Tautan Drive Dokumen (Sertifikat & Amandemen)',
            'Total Akreditasi',
            'Total Kendala / Isu',
            'Tanggal Terdaftar',
        ];

        return response()->stream(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $lpks = Lpk::withCount(['accreditations', 'issues'])
                ->orderBy('name')
                ->cursor();

            foreach ($lpks as $lpk) {
                $row = [
                    'LPK-' . str_pad((string) $lpk->id, 4, '0', STR_PAD_LEFT),
                    $lpk->registration_number,
                    $lpk->name,
                    $lpk->scope ?: '-',
                    $lpk->status,
                    $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-',
                    $lpk->drive_url ?: '-',
                    $lpk->accreditations_count,
                    $lpk->issues_count,
                    $lpk->created_at ? $lpk->created_at->format('d/m/Y') : '-',
                ];

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate CSV Program Asesmen.
     */
    protected function generateAssessmentsCsv(bool $isFeed): StreamedResponse
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
            'Waktu Mulai',
            'Waktu Selesai',
            'Status Pelaksanaan',
            'Status Biaya SBM',
            'Total Realisasi Biaya (Rp)',
        ];

        return response()->stream(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $assessments = Assessment::with(['lpk', 'expense'])
                ->orderByDesc('start_at')
                ->cursor();

            foreach ($assessments as $asm) {
                $row = [
                    'ASM-' . str_pad((string) $asm->id, 4, '0', STR_PAD_LEFT),
                    $asm->title,
                    $asm->lpk?->name ?? '-',
                    $asm->assessment_type,
                    $asm->start_at ? $asm->start_at->format('d/m/Y H:i') : '-',
                    $asm->end_at ? $asm->end_at->format('d/m/Y H:i') : '-',
                    $asm->status,
                    $asm->expense?->status ?? 'BELUM_DILAPORKAN',
                    $asm->expense ? (int) $asm->expense->total_cost : 0,
                ];

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
