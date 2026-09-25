<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentImportController extends Controller
{
    /**
     * Unduh template resmi untuk impor data program asesmen (CSV atau XLSX).
     */
    public function downloadTemplate(Request $request): mixed
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        $columns = [
            'nomor_registrasi_lpk',
            'judul_agenda',
            'jenis_asesmen',
            'tanggal_mulai',
            'tanggal_selesai',
            'lokasi',
            'status',
            'ketua_asesor',
            'status_tp',
            'batas_waktu_tp',
            'nomor_sk',
            'tanggal_sk',
            'catatan',
        ];

        // Contoh baris sampel realistis
        $firstLpk = Lpk::orderBy('registration_number')->first();
        $sampleReg = $firstLpk ? $firstLpk->registration_number : 'LP-077-IDN';

        $samples = [
            [
                $sampleReg,
                'Asesmen Surveilen 1 (S1) Laboratorium Pengujian',
                'Surveilen',
                date('Y-m-d 09:00', strtotime('+10 days')),
                date('Y-m-d 17:00', strtotime('+12 days')),
                'Kawasan Sains Puspiptek Serpong, Tangerang Selatan',
                'SCHEDULED',
                'Dr. Ir. Suryadi, M.Eng.',
                'NONE',
                '',
                '',
                '',
                'Surveilen berkala lingkup pengujian ISO/IEC 17025.',
            ],
            [
                $sampleReg,
                'Asesmen Re-akreditasi Siklus 5 Tahunan',
                'Re-asesmen',
                date('Y-m-d 08:30', strtotime('-30 days')),
                date('Y-m-d 16:30', strtotime('-28 days')),
                'Gedung Laboratorium Utama Lantai 3',
                'COMPLETED',
                'Dra. Hj. Nurul Hidayati, M.Si.',
                'IN_PROGRESS',
                date('Y-m-d', strtotime('+30 days')),
                '',
                '',
                'Terdapat 2 temuan Kategori 2 yang sedang dalam tindakan perbaikan.',
            ],
        ];

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template Asesmen');
            $sheet->fromArray([$columns, ...$samples]);

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 'template-import-asesmen.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-import-asesmen.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        return response()->stream(function () use ($columns, $samples) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, $columns);
            foreach ($samples as $sample) {
                fputcsv($handle, $sample);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Memproses impor data asesmen dari berkas (.xlsx, .csv) atau tautan Google Sheets.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['nullable', 'file', 'extensions:csv,txt,xlsx', 'max:10240'],
            'sheets_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $headers = [];
        $rows = [];

        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();

            if ($extension === 'xlsx' || str_contains($mime, 'spreadsheetml')) {
                try {
                    $parsed = $this->parseXlsxFile($file->getRealPath());
                    $headers = $parsed['headers'];
                    $rows = $parsed['rows'];
                } catch (\Throwable $e) {
                    return back()->with('error', 'Gagal membaca berkas Excel: ' . $e->getMessage());
                }
            } else {
                $csvContent = file_get_contents($file->getRealPath());
                $parsed = $this->parseCsvContent($csvContent);
                if (isset($parsed['error'])) {
                    return back()->with('error', $parsed['error']);
                }
                $headers = $parsed['headers'];
                $rows = $parsed['rows'];
            }
        } elseif ($request->filled('sheets_url')) {
            $inputUrl = $request->input('sheets_url');
            $url = $this->normalizeGoogleSheetsUrl($inputUrl);

            try {
                $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/csv,text/plain,*/*',
                ])->withOptions([
                    'cookies' => $cookieJar,
                    'allow_redirects' => [
                        'max' => 10,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                        'track_redirects' => true,
                    ],
                ])->timeout(20)->get($url);

                if (! $response->successful() && preg_match('/docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $inputUrl, $sheetMatches)) {
                    $sheetId = $sheetMatches[1];
                    $gvizUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/gviz/tq?tqx=out:csv";
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    ])->timeout(20)->get($gvizUrl);
                }

                if (! $response->successful()) {
                    return back()->with('error', 'Gagal mengunduh spreadsheet dari URL yang diberikan. Pastikan tautan dapat diakses publik (Anyone with the link).');
                }

                $parsed = $this->parseCsvContent($response->body());
                if (isset($parsed['error'])) {
                    return back()->with('error', $parsed['error']);
                }
                $headers = $parsed['headers'];
                $rows = $parsed['rows'];
            } catch (\Throwable $e) {
                return back()->with('error', 'Koneksi ke Google Sheets gagal: ' . $e->getMessage());
            }
        } else {
            return back()->with('error', 'Pilih berkas CSV/Excel atau masukkan tautan Google Sheets terlebih dahulu.');
        }

        if (empty($rows)) {
            return back()->with('error', 'Tidak ditemukan baris data pada berkas atau spreadsheet yang diimpor.');
        }

        $userId = $request->user()?->id ?? 1;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $unmatchedLpks = [];

        // Cache LPKs untuk performa
        $lpksByReg = Lpk::all()->keyBy(fn ($l) => strtoupper(trim((string) $l->registration_number)));
        $lpksByName = Lpk::all()->keyBy(fn ($l) => strtolower(trim((string) $l->name)));

        foreach ($rows as $rowItem) {
            $row = $rowItem['values'];

            $data = [];
            foreach ($headers as $colIdx => $colName) {
                if (isset($row[$colIdx])) {
                    $data[$colName] = trim((string) $row[$colIdx]);
                }
            }

            $regInput = strtoupper(trim((string) ($data['registration_number'] ?? '')));
            $titleInput = trim((string) ($data['title'] ?? ''));

            if (empty($regInput) && empty($titleInput)) {
                $skippedCount++;
                continue;
            }

            // Cari LPK
            $matchedLpk = null;
            if (! empty($regInput) && isset($lpksByReg[$regInput])) {
                $matchedLpk = $lpksByReg[$regInput];
            } elseif (! empty($data['lpk_name'])) {
                $lpkNameKey = strtolower(trim((string) $data['lpk_name']));
                $matchedLpk = $lpksByName[$lpkNameKey] ?? null;
            }

            if (! $matchedLpk) {
                // Coba substring match jika ada format LP-XXX
                if (preg_match('/(L[PKI]-\d+-IDN)/i', $regInput, $m)) {
                    $normReg = strtoupper($m[1]);
                    $matchedLpk = $lpksByReg[$normReg] ?? null;
                }
            }

            if (! $matchedLpk) {
                $skippedCount++;
                if (! empty($regInput) && ! in_array($regInput, $unmatchedLpks, true)) {
                    $unmatchedLpks[] = $regInput;
                }
                continue;
            }

            // Format tanggal mulai dan selesai
            $startAt = $this->parseDateTime($data['start_at'] ?? null);
            $endAt = $this->parseDateTime($data['end_at'] ?? null);

            if (! $startAt) {
                $startAt = now()->addDays(7)->setTime(9, 0);
            }
            if (! $endAt) {
                $endAt = (clone $startAt)->addDays(2)->setTime(17, 0);
            }

            // Judul agenda default bila kosong
            $typeInput = $this->normalizeAssessmentType($data['assessment_type'] ?? 'Surveilen');
            if (empty($titleInput)) {
                $titleInput = "Asesmen {$typeInput} - {$matchedLpk->name}";
            }

            $status = $this->normalizeStatus($data['status'] ?? 'SCHEDULED');
            $tpStatus = $this->normalizeTpStatus($data['tp_status'] ?? 'NONE');
            $tpDueDate = ! empty($data['tp_due_date']) ? $this->parseDate($data['tp_due_date']) : null;
            $skDate = ! empty($data['sk_date']) ? $this->parseDate($data['sk_date']) : null;
            $skNumber = ! empty($data['sk_number']) ? trim((string) $data['sk_number']) : null;

            // Cari existing assessment untuk di-update (berdasarkan lpk_id dan title / tanggal mulai)
            $existing = Assessment::where('lpk_id', $matchedLpk->id)
                ->where(function ($q) use ($titleInput, $startAt) {
                    $q->where('title', $titleInput)
                        ->orWhereDate('start_at', $startAt->toDateString());
                })
                ->first();

            $payload = [
                'lpk_id' => $matchedLpk->id,
                'title' => $titleInput,
                'assessment_type' => $typeInput,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'location' => ! empty($data['location']) ? $data['location'] : ($existing->location ?? $matchedLpk->address),
                'status' => $status,
                'lead_assessor' => ! empty($data['lead_assessor']) ? $data['lead_assessor'] : ($existing->lead_assessor ?? null),
                'notes' => ! empty($data['notes']) ? $data['notes'] : ($existing->notes ?? null),
                'tp_status' => $tpStatus,
                'tp_due_date' => $tpDueDate ?: ($existing->tp_due_date ?? null),
                'sk_number' => $skNumber ?: ($existing->sk_number ?? null),
                'sk_date' => $skDate ?: ($existing->sk_date ?? null),
            ];

            if ($existing) {
                $existing->update($payload);
                $updatedCount++;
            } else {
                $payload['created_by'] = $userId;
                Assessment::create($payload);
                $createdCount++;
            }
        }

        $msg = "Impor data Asesmen selesai: {$createdCount} agenda baru ditambahkan, {$updatedCount} diperbarui";
        if ($skippedCount > 0) {
            $msg .= ", dan {$skippedCount} baris dilewati";
            if (! empty($unmatchedLpks)) {
                $msg .= ' (LPK tidak terdaftar: ' . implode(', ', array_slice($unmatchedLpks, 0, 3)) . (count($unmatchedLpks) > 3 ? ' dst.' : '') . ')';
            }
            $msg .= '.';
        } else {
            $msg .= '.';
        }

        return redirect()->route('assessments.index')->with('success', $msg);
    }

    /**
     * Parsing string CSV mentah menjadi headers dan rows.
     */
    protected function parseCsvContent(string $csvContent): array
    {
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', trim($csvContent));

        if (empty($csvContent)) {
            return ['error' => 'Berkas atau spreadsheet tidak memiliki data untuk diimpor.'];
        }

        $lines = preg_split('/\r\n|\r|\n/', $csvContent);
        if (empty($lines)) {
            return ['error' => 'Format data spreadsheet tidak dikenali.'];
        }

        $firstLine = $lines[0];
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $headerRaw = str_getcsv(array_shift($lines), $delimiter, '"', '\\');
        $headers = $this->normalizeHeaders($headerRaw);

        $rows = [];
        $lineNum = 1;
        foreach ($lines as $line) {
            $lineNum++;
            if (trim($line) === '') {
                continue;
            }
            $rowValues = str_getcsv($line, $delimiter, '"', '\\');
            $rows[] = [
                'row_number' => $lineNum,
                'values' => $rowValues,
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Parsing berkas Excel (.xlsx) menjadi headers dan rows.
     */
    protected function parseXlsxFile(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $rows = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $rowNum = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowValues = [];

            foreach ($cellIterator as $cell) {
                if (ExcelDate::isDateTime($cell)) {
                    try {
                        $dateObj = ExcelDate::excelToDateTimeObject($cell->getValue());
                        $val = $dateObj->format('Y-m-d H:i:s');
                    } catch (\Throwable) {
                        $val = (string) $cell->getCalculatedValue();
                    }
                } else {
                    $val = (string) ($cell->getCalculatedValue() ?? '');
                }

                $rowValues[] = $val;
            }

            if (empty(array_filter($rowValues, fn ($v) => trim((string) $v) !== ''))) {
                continue;
            }

            if (empty($headers)) {
                $headers = $this->normalizeHeaders($rowValues);
            } else {
                $rows[] = [
                    'row_number' => $rowNum,
                    'values' => $rowValues,
                ];
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Normalisasi nama kolom header ke format atribut asesmen.
     */
    protected function normalizeHeaders(array $headerRaw): array
    {
        return array_map(function ($col) {
            $clean = strtolower(trim((string) $col));
            $clean = str_replace([' ', '-', '_', '.', '/', '(', ')', ':', ',', '\\'], '', $clean);

            return match ($clean) {
                'nomorregistrasilpk', 'nomorregistrasi', 'noreg', 'registrationnumber', 'nomorreg', 'noakreditasi', 'nomorakreditasi', 'lpkreg', 'lpkid', 'noregister', 'kodelpk' => 'registration_number',
                'namalpk', 'lpk', 'namalembaga', 'laboratorium' => 'lpk_name',
                'judulagenda', 'judul', 'judulasesmen', 'agenda', 'title', 'namaagenda' => 'title',
                'jenisasesmen', 'jenis', 'type', 'skema', 'tipe', 'tipeasesmen', 'kategori' => 'assessment_type',
                'tanggalmulai', 'mulai', 'startat', 'tglmulai', 'startdate', 'waktumulai' => 'start_at',
                'tanggalselesai', 'selesai', 'endat', 'tglselesai', 'enddate', 'waktuselesai' => 'end_at',
                'lokasi', 'tempat', 'location', 'alamat' => 'location',
                'status', 'statusagenda', 'statusasesmen' => 'status',
                'ketuaasesor', 'leadassessor', 'asesor', 'namaasesor', 'ketuatim', 'asesorkepala' => 'lead_assessor',
                'statustp', 'tpstatus', 'tindakanperbaikan', 'statusperbaikan' => 'tp_status',
                'bataswaktutp', 'tpduedate', 'duedatetp', 'jatuhtempotp', 'batastp' => 'tp_due_date',
                'nomorsk', 'sknumber', 'nosk', 'nomorsuratkeputusan' => 'sk_number',
                'tanggalsk', 'skdate', 'tglsk' => 'sk_date',
                'catatan', 'keterangan', 'notes', 'deskripsi' => 'notes',
                default => $clean,
            };
        }, $headerRaw);
    }

    protected function normalizeAssessmentType(string $input): string
    {
        $input = trim($input);
        $lower = strtolower($input);

        if (str_contains($lower, 'awal') || str_contains($lower, 'initial')) {
            return 'Asesmen Awal';
        }
        if (str_contains($lower, 'surveilen') || str_contains($lower, 'surveillance')) {
            return 'Surveilen';
        }
        if (str_contains($lower, 're-') || str_contains($lower, 'reasesmen') || str_contains($lower, 'reakreditasi') || str_contains($lower, 'reassessment')) {
            return 'Re-asesmen';
        }
        if (str_contains($lower, 'kecukupan') || str_contains($lower, 'adequacy')) {
            return 'Audit Kecukupan';
        }
        if (str_contains($lower, 'witness') || str_contains($lower, 'pemasaksian') || str_contains($lower, 'penyaksian')) {
            return 'Penyaksian';
        }
        if (str_contains($lower, 'perluasan') || str_contains($lower, 'extension')) {
            return 'Perluasan Lingkup';
        }

        return $input ?: 'Surveilen';
    }

    protected function normalizeStatus(string $input): string
    {
        $input = strtoupper(trim($input));

        return match ($input) {
            'DIRENCANAKAN', 'PLAN', 'PLANNED' => 'PLANNED',
            'TERJADWAL', 'SCHEDULED', 'SCHEDULE' => 'SCHEDULED',
            'BERJALAN', 'SEDANG BERJALAN', 'IN_PROGRESS', 'RUNNING' => 'IN_PROGRESS',
            'SELESAI', 'DONE', 'COMPLETED' => 'COMPLETED',
            'BATAL', 'DIBATALKAN', 'CANCELLED', 'CANCELED' => 'CANCELLED',
            default => 'SCHEDULED',
        };
    }

    protected function normalizeTpStatus(string $input): string
    {
        $input = strtoupper(trim($input));

        return match ($input) {
            'NIHIL', 'NONE', 'TIDAK ADA TEMUAN', 'AMAN' => Assessment::TP_STATUS_NONE,
            'PERBAIKAN', 'IN_PROGRESS', 'SEDANG PERBAIKAN', 'PENYUSUNAN' => Assessment::TP_STATUS_IN_PROGRESS,
            'VERIFIKASI', 'UNDER_VERIFICATION', 'DALAM VERIFIKASI' => Assessment::TP_STATUS_UNDER_VERIFICATION,
            'MEMENUHI', 'SATISFIED', 'SELESAI', 'CLOSED' => Assessment::TP_STATUS_SATISFIED,
            default => Assessment::TP_STATUS_NONE,
        };
    }

    protected function parseDateTime(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeGoogleSheetsUrl(string $url): string
    {
        if (preg_match('/docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheetId = $matches[1];
            $gid = '0';
            if (preg_match('/[#&?]gid=([0-9]+)/', $url, $gidMatches)) {
                $gid = $gidMatches[1];
            }

            return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        }

        return $url;
    }
}
