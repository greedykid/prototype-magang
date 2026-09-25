<?php

namespace App\Http\Controllers;

use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LpkImportController extends Controller
{
    /**
     * Mengunduh berkas template resmi untuk impor LPK (format CSV atau XLSX).
     */
    public function downloadTemplate(Request $request): mixed
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        $columns = [
            'nomor_registrasi_lpk',
            'no_akreditasi',
            'jenis_akreditasi',
            'nama_lpk',
            'ruang_lingkup',
            'alamat',
            'email',
            'telepon',
            'status',
            'tanggal_terbit_sertifikat',
            'masa_berlaku',
            'link_drive_dokumen',
        ];

        $samples = [
            [
                '3340',
                'LP-101-IDN',
                'Laboratorium Penguji',
                'Balai Pengujian Lingkungan Sejahtera',
                'Laboratorium Pengujian Kimia, Fisika, dan Lingkungan Hidup',
                'Jl. M.H. Thamrin No. 12, Jakarta Pusat',
                'kontak@lablingkungan.id',
                '021-3141234',
                'ACTIVE',
                date('Y-m-d', strtotime('-2 years')),
                date('Y-m-d', strtotime('+3 years')),
                'https://drive.google.com/drive/folders/1demo-berkas-lp101',
            ],
            [
                '3341',
                'LK-202-IDN',
                'Laboratorium Kalibrasi',
                'Pusat Kalibrasi Presisi Bandung',
                'Laboratorium Kalibrasi Suhu, Tekanan, dan Massa',
                'Jl. Ir. H. Juanda No. 88, Bandung',
                'layanan@kalibrasipresisi.co.id',
                '022-2508899',
                'ACTIVE',
                date('Y-m-d', strtotime('-1 year')),
                date('Y-m-d', strtotime('+4 years')),
                'https://drive.google.com/drive/folders/1demo-berkas-lk202',
            ],
            [
                '3342',
                'LI-303-IDN',
                'Lembaga Inspeksi',
                'Lembaga Inspeksi Teknik Terpadu',
                'Lembaga Inspeksi Instalasi Pipa dan Bejana Tekan',
                'Jl. Pemuda No. 45, Surabaya',
                'sekretariat@inspeksiteknik.id',
                '031-5345678',
                'ACTIVE',
                date('Y-m-d', strtotime('-3 years')),
                date('Y-m-d', strtotime('+2 years')),
                'https://drive.google.com/drive/folders/1demo-berkas-li303',
            ],
        ];

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template LPK');
            $sheet->fromArray([$columns, ...$samples]);

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 'template-import-lpk.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-import-lpk.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        return response()->stream(function () use ($columns, $samples) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);
            foreach ($samples as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Memproses impor LPK dari berkas unggahan (.xlsx, .csv) atau tautan Google Sheets.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['nullable', 'file', 'extensions:csv,txt,xlsx', 'max:10240'],
            'sheets_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $headers = [];
        $rows = [];
        $sheetHyperlinks = [];

        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();

            if ($extension === 'xlsx' || str_contains($mime, 'spreadsheetml')) {
                try {
                    $parsed = $this->parseXlsxFile($file->getRealPath());
                    $headers = $parsed['headers'];
                    $rows = $parsed['rows'];
                    $sheetHyperlinks = $parsed['hyperlinks'];
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

                // Fallback ke GViz CSV endpoint jika endpoint ekspor standar gagal
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
                $csvContent = $response->body();

                // Coba ambil XLSX untuk mengekstrak hyperlink formula asli
                if (preg_match('/docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $inputUrl, $sheetMatches)) {
                    $sheetId = $sheetMatches[1];
                    $xlsxUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=xlsx";
                    try {
                        $xlsxResponse = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        ])->withOptions([
                            'cookies' => $cookieJar,
                            'allow_redirects' => ['max' => 10, 'strict' => false, 'referer' => true, 'protocols' => ['http', 'https']],
                        ])->timeout(15)->get($xlsxUrl);

                        if ($xlsxResponse->successful() && strlen($xlsxResponse->body()) > 500) {
                            $sheetHyperlinks = $this->extractHyperlinksFromXlsx($xlsxResponse->body());
                        }
                    } catch (\Throwable) {
                        // Abaikan jika XLSX tidak dapat diambil
                    }
                }

                $parsed = $this->parseCsvContent($csvContent);
                if (isset($parsed['error'])) {
                    return back()->with('error', $parsed['error']);
                }
                $headers = $parsed['headers'];
                $rows = $parsed['rows'];
            } catch (\Throwable $e) {
                return back()->with('error', 'Terjadi kesalahan koneksi saat mengakses Google Sheets: ' . $e->getMessage());
            }
        } else {
            return back()->with('error', 'Silakan unggah berkas Excel/CSV atau masukkan tautan Google Sheets.');
        }

        $hasIdentifier = in_array('registration_number', $headers, true)
            || in_array('accreditation_number', $headers, true)
            || in_array('no_reg', $headers, true);

        if (! $hasIdentifier || ! in_array('name', $headers, true)) {
            return back()->with('error', 'Format kolom tidak sesuai. Kolom nomor/registrasi ("no_reg_lpk", "no_akreditasi", atau "nomor_registrasi") dan "nama_lpk" wajib ada. Silakan periksa kembali judul kolom spreadsheet.');
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $item) {
            $excelRowNumber = $item['row_number'];
            $row = $item['values'];

            if (count($row) < 2) {
                $skippedCount++;
                continue;
            }

            $data = [];
            foreach ($headers as $index => $key) {
                $data[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $noReg = $data['no_reg'] ?? null;
            $accreditationNumber = $data['accreditation_number'] ?? null;
            $regNo = $data['registration_number'] ?? null;
            $accreditationType = $data['accreditation_type'] ?? null;
            $name = $data['name'] ?? null;

            if ((! $noReg && ! $accreditationNumber && ! $regNo) || ! $name) {
                $skippedCount++;
                continue;
            }

            if (! $regNo && $accreditationNumber) {
                $regNo = $accreditationNumber;
            } elseif (! $accreditationNumber && $regNo) {
                $accreditationNumber = $regNo;
            }

            // Normalisasi otomatis nomor akreditasi numerik murni (misal: 077 -> LP-077-IDN)
            if ($regNo && preg_match('/^\d+$/', $regNo)) {
                if (! $noReg) {
                    $noReg = $regNo;
                }
                $regNo = 'LP-' . str_pad($regNo, 3, '0', STR_PAD_LEFT) . '-IDN';
                $accreditationNumber = $regNo;
            }

            $status = strtoupper($data['status'] ?? 'ACTIVE');
            if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
                $status = 'ACTIVE';
            }

            $certificateDate = $this->parseDateString($data['certificate_date'] ?? null);
            $expiredAt = $this->parseDateString($data['expired_at'] ?? null);
            if (! $expiredAt && $certificateDate) {
                $expiredAt = date('Y-m-d', strtotime('+5 years', strtotime($certificateDate)));
            } elseif (! $certificateDate && $expiredAt) {
                $certificateDate = date('Y-m-d', strtotime('-5 years', strtotime($expiredAt)));
            }

            $rawDriveUrl = ! empty($data['drive_url']) ? trim((string) $data['drive_url']) : null;
            $driveUrl = null;

            if (! empty($rawDriveUrl) && filter_var($rawDriveUrl, FILTER_VALIDATE_URL) && str_starts_with($rawDriveUrl, 'http')) {
                $driveUrl = $rawDriveUrl;
            } elseif (! empty($sheetHyperlinks[$excelRowNumber])) {
                $driveColIndex = array_search('drive_url', $headers, true);
                $driveColLetter = $driveColIndex !== false ? Coordinate::stringFromColumnIndex($driveColIndex + 1) : null;

                if ($driveColLetter && ! empty($sheetHyperlinks[$excelRowNumber][$driveColLetter])) {
                    $driveUrl = $sheetHyperlinks[$excelRowNumber][$driveColLetter];
                } else {
                    $driveUrl = reset($sheetHyperlinks[$excelRowNumber]);
                }
            }

            $existing = null;
            if (! empty($noReg)) {
                $existing = Lpk::where('no_reg', $noReg)->first();
            }
            if (! $existing && ! empty($accreditationNumber)) {
                $existing = Lpk::where('accreditation_number', $accreditationNumber)
                    ->orWhere('registration_number', $accreditationNumber)
                    ->first();
            }
            if (! $existing && ! empty($regNo)) {
                $existing = Lpk::where('registration_number', $regNo)
                    ->orWhere('accreditation_number', $regNo)
                    ->orWhere('no_reg', $regNo)
                    ->first();
            }

            if ($existing) {
                $existing->update([
                    'no_reg' => $noReg ?: $existing->no_reg,
                    'accreditation_number' => $accreditationNumber ?: ($existing->accreditation_number ?: $existing->registration_number),
                    'accreditation_type' => $accreditationType ?: $existing->accreditation_type,
                    'registration_number' => $accreditationNumber ?: ($existing->registration_number ?: ($noReg ?: $existing->no_reg)),
                    'name' => $name,
                    'scope' => ! empty($data['scope']) ? $data['scope'] : $existing->scope,
                    'certificate_date' => $certificateDate ?: $existing->certificate_date,
                    'address' => ! empty($data['address']) ? $data['address'] : $existing->address,
                    'email' => ! empty($data['email']) ? $data['email'] : $existing->email,
                    'phone' => ! empty($data['phone']) ? $data['phone'] : $existing->phone,
                    'status' => $status,
                    'expired_at' => $expiredAt ?: $existing->expired_at,
                    'drive_url' => $driveUrl ?: ($existing->drive_url && str_starts_with($existing->drive_url, 'http') ? $existing->drive_url : null),
                    'notes' => ! empty($data['notes']) ? $data['notes'] : $existing->notes,
                ]);
                $existing->generateSurveillanceAssessments();
                $updatedCount++;
            } else {
                Lpk::create([
                    'no_reg' => $noReg,
                    'accreditation_number' => $accreditationNumber ?: $regNo,
                    'accreditation_type' => $accreditationType ?: 'Laboratorium Penguji',
                    'registration_number' => $regNo ?: ($accreditationNumber ?: $noReg),
                    'name' => $name,
                    'scope' => $data['scope'] ?? null,
                    'certificate_date' => $certificateDate,
                    'address' => $data['address'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'status' => $status,
                    'expired_at' => $expiredAt,
                    'drive_url' => $driveUrl,
                    'notes' => $data['notes'] ?? null,
                ]);
                $createdCount++;
            }
        }

        $msg = "Impor data LPK selesai: {$createdCount} LPK baru ditambahkan, {$updatedCount} diperbarui";
        if ($skippedCount > 0) {
            $msg .= ", dan {$skippedCount} baris dilewati (kosong/tidak valid).";
        } else {
            $msg .= '.';
        }

        return redirect()->route('lpks.index')->with('success', $msg);
    }

    /**
     * Mem-parsing string CSV mentah menjadi headers dan rows.
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
     * Mem-parsing berkas Excel (.xlsx) menjadi headers, rows, dan hyperlinks.
     */
    protected function parseXlsxFile(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $rows = [];
        $hyperlinks = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $rowNum = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowValues = [];
            $colIndex = 0;

            foreach ($cellIterator as $cell) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                $val = '';

                if ($cell->hasHyperlink() && ! empty($cell->getHyperlink()->getUrl())) {
                    $hyperlinks[$rowNum][$colLetter] = $cell->getHyperlink()->getUrl();
                }

                if ($cell->isFormula()) {
                    $formula = (string) $cell->getValue();
                    if (preg_match('/HYPERLINK\(\s*["\']([^"\']+)["\']/i', $formula, $urlMatches)) {
                        $hyperlinks[$rowNum][$colLetter] = trim($urlMatches[1]);
                    }
                }

                if (ExcelDate::isDateTime($cell)) {
                    try {
                        $dateObj = ExcelDate::excelToDateTimeObject($cell->getValue());
                        $val = $dateObj->format('Y-m-d');
                    } catch (\Throwable) {
                        $val = (string) $cell->getCalculatedValue();
                    }
                } else {
                    $val = (string) ($cell->getCalculatedValue() ?? '');
                }

                $rowValues[] = $val;
                $colIndex++;
            }

            // Lewati baris kosong
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
            'hyperlinks' => $hyperlinks,
        ];
    }

    /**
     * Normalisasi nama kolom header ke format atribut model LPK.
     */
    protected function normalizeHeaders(array $headerRaw): array
    {
        return array_map(function ($col) {
            $clean = strtolower(trim((string) $col));
            $clean = str_replace([' ', '-', '_', '.', '/', '(', ')', ':', ',', '\\'], '', $clean);

            return match ($clean) {
                'noreglpk', 'noreg', 'nomorreglpk', 'nomorreg', 'idreg', 'idunik', 'noregister' => 'no_reg',
                'noakreditasi', 'nomorakreditasi', 'noakred', 'nomor' => 'accreditation_number',
                'nomorregistrasi', 'registrationnumber', 'no' => 'registration_number',
                'jenisakreditasi', 'tipeakreditasi', 'skema', 'skemaakreditasi', 'jenis' => 'accreditation_type',
                'namalpk', 'nama', 'namalembaga', 'name', 'lembagapenilaiankesesuaian', 'lpk' => 'name',
                'ruanglingkup', 'lingkup', 'scope', 'bidang', 'ruanglingkupakreditasi', 'ruanglingkupuji' => 'scope',
                'alamat', 'address', 'lokasi' => 'address',
                'email', 'surel', 'mail' => 'email',
                'telepon', 'telp', 'phone', 'notelp', 'teleponhp', 'teleponfax', 'telfax', 'fax', 'telpfax' => 'phone',
                'status', 'statusoperasional' => 'status',
                'tanggalterbitsertifikat', 'tanggalterbit', 'tglterbit', 'certificatedate', 'tglterbitsertif', 'terbitsertifikat' => 'certificate_date',
                'masaberlaku', 'expired', 'expireddate', 'tanggalkedaluwarsa', 'masaberlakuakreditasi', 'masaberlakuakreditasiexpired', 'expiredakreditasi', 'tanggalkadaluarsa', 'exp' => 'expired_at',
                'link', 'linkdrive', 'linkdrivedokumen', 'driveurl', 'tautandrive', 'tautan', 'linkdrivesertifikat', 'linkdriveamandemen', 'url', 'googledrive', 'linkberkas' => 'drive_url',
                'catatan', 'keterangan', 'notes' => 'notes',
                default => $clean,
            };
        }, $headerRaw);
    }

    /**
     * Parsing fleksibel format tanggal (ISO, d/m/Y, teks bulan Indonesia, dsb.)
     */
    protected function parseDateString(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $date = trim($date);

        // Jika berupa 4 digit angka tahun (misal: 2027)
        if (preg_match('/^\d{4}$/', $date)) {
            return "{$date}-12-31";
        }

        // Terjemahkan nama bulan Indonesia ke Inggris
        $idMonths = [
            'januari' => 'january', 'februari' => 'february', 'maret' => 'march',
            'april' => 'april', 'mei' => 'may', 'juni' => 'june',
            'juli' => 'july', 'agustus' => 'august', 'september' => 'september',
            'oktober' => 'october', 'november' => 'november', 'desember' => 'december',
            'agu' => 'aug', 'okt' => 'oct', 'des' => 'dec',
        ];
        $dateLower = strtolower($date);
        foreach ($idMonths as $id => $en) {
            $dateLower = str_replace($id, $en, $dateLower);
        }

        // Ganti '/' dengan '-' agar strtotime mengenali pola hari-bulan-tahun
        $dateNormalized = str_replace('/', '-', $dateLower);

        $timestamp = strtotime($dateNormalized);
        if ($timestamp !== false && $timestamp > 0) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Mengubah tautan Google Sheets publik menjadi tautan ekspor CSV.
     */
    protected function normalizeGoogleSheetsUrl(string $url): string
    {
        if (preg_match('/docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheetId = $matches[1];
            $gid = '0';
            if (preg_match('/gid=([0-9]+)/', $url, $gidMatches)) {
                $gid = $gidMatches[1];
            }

            return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        }

        return $url;
    }

    /**
     * Mengekstrak URL hyperlink dari file XLSX Google Sheets (misal formula =HYPERLINK("url", "Link")).
     */
    protected function extractHyperlinksFromXlsx(string $xlsxBinary): array
    {
        $hyperlinks = [];
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_') . '.zip';

        try {
            file_put_contents($tempFile, $xlsxBinary);
            $phar = new \PharData($tempFile);

            if (isset($phar['xl/worksheets/sheet1.xml'])) {
                $xml = $phar['xl/worksheets/sheet1.xml']->getContent();

                if (preg_match_all('/<c r="([A-Z]+)(\d+)"[^>]*><f>(.*?)<\/f>/s', $xml, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $m) {
                        $col = $m[1];
                        $row = (int) $m[2];
                        $formula = html_entity_decode($m[3], ENT_QUOTES | ENT_XML1, 'UTF-8');

                        if (preg_match('/HYPERLINK\(\s*["\']([^"\']+)["\']/i', $formula, $urlMatches)) {
                            $targetUrl = trim($urlMatches[1]);

                            if (str_contains($targetUrl, 'google.com/url?') && str_contains($targetUrl, 'q=')) {
                                $parsed = parse_url($targetUrl);
                                if (! empty($parsed['query'])) {
                                    parse_str($parsed['query'], $queryArgs);
                                    if (! empty($queryArgs['q'])) {
                                        $targetUrl = $queryArgs['q'];
                                    }
                                }
                            }

                            if (filter_var($targetUrl, FILTER_VALIDATE_URL) && str_starts_with($targetUrl, 'http')) {
                                $hyperlinks[$row][$col] = $targetUrl;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Abaikan kesalahan pembongkaran phar
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        return $hyperlinks;
    }
}
