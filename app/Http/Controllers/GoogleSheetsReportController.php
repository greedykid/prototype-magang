<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
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
     * Download CSV Master Data LPK untuk pengguna login.
     */
    public function exportLpks(Request $request): StreamedResponse
    {
        $ids = $this->parseIds($request);
        return $this->generateLpksCsv(false, $ids, $request->user());
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
    public function exportAssessments(Request $request): StreamedResponse
    {
        $ids = $this->parseIds($request);
        return $this->generateAssessmentsCsv(false, $ids, $request->user());
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
            'Nomor Registrasi / No Reg LPK',
            'No Akreditasi',
            'Jenis Akreditasi',
            'Nama Lembaga Penilaian Kesesuaian',
            'Ruang Lingkup Akreditasi',
            'Status Operasional',
            'Tanggal Terbit Sertifikat',
            'Masa Berlaku Akreditasi',
            'Tautan Drive Dokumen (Sertifikat & Amandemen)',
            'Total Akreditasi',
            'Tanggal Terdaftar',
        ];

        return response()->stream(function () use ($columns, $ids, $user) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $query = Lpk::withCount(['accreditations'])
                ->when($user && $user->isPic(), fn ($q) => $q->accessibleBy($user))
                ->orderBy('name');

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }

            $lpks = $query->cursor();

            foreach ($lpks as $lpk) {
                $row = [
                    'LPK-' . str_pad((string) $lpk->id, 4, '0', STR_PAD_LEFT),
                    $lpk->no_reg ?: $lpk->registration_number,
                    $lpk->accreditation_number ?: $lpk->registration_number,
                    $lpk->accreditation_type ?: 'Laboratorium Penguji',
                    $lpk->name,
                    $lpk->scope ?: '-',
                    $lpk->status,
                    $lpk->certificate_date ? $lpk->certificate_date->format('d/m/Y') : '-',
                    $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-',
                    $lpk->drive_url ?: '-',
                    $lpk->accreditations_count,
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
                $row = [
                    'ASM-' . str_pad((string) $asm->id, 4, '0', STR_PAD_LEFT),
                    $asm->title,
                    $asm->lpk?->name ?? '-',
                    $asm->assessment_type,
                    $asm->start_at ? $asm->start_at->format('d/m/Y') : '-',
                    $asm->end_at ? $asm->end_at->format('d/m/Y') : '-',
                    $asm->status,
                    $asm->tp_status ?? 'NOT_APPLICABLE',
                    $asm->tp_due_date ? $asm->tp_due_date->format('d/m/Y') : '-',
                ];

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
