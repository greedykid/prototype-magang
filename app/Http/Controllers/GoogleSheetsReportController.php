<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        Log::info('Google Sheets live feed accessed: LPKs', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

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

        Log::info('Google Sheets live feed accessed: Assessments', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->generateAssessmentsCsv(true);
    }

    /**
     * Validasi key feed Google Sheets dengan perbandingan konstan waktu (timing-safe).
     */
    protected function isValidFeedKey(Request $request): bool
    {
        $configured = (string) config('services.sheets.feed_key', '');

        // Keamanan: Tolak jika kunci feed belum dikonfigurasi atau masih menggunakan nilai placeholder bawaan
        if ($configured === '' || $configured === self::DEFAULT_FEED_KEY) {
            return false;
        }

        $provided = (string) $request->query('key', '');

        if ($provided === '') {
            return false;
        }

        return hash_equals($configured, $provided);
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

                fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $row));
            }

            fclose($handle);
        }, 200, $headers);
    }
}
