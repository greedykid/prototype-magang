<?php

namespace App\Http\Controllers;

use App\Models\Lpk;
use App\Traits\ImportsSpreadsheetData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LpkImportController extends Controller
{
    use ImportsSpreadsheetData;
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
                fputcsv($handle, array_map(function ($val) {
                    $str = (string) ($val ?? '');
                    if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($str)) {
                        return "'" . $str;
                    }
                    return $str;
                }, $row));
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
        ], [
            'csv_file.extensions' => 'Berkas harus berformat .csv, .txt, atau .xlsx.',
            'csv_file.max' => 'Ukuran berkas maksimal 10 MB.',
            'sheets_url.url' => 'Format tautan Google Sheets tidak valid.',
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
            $inputUrl = (string) $request->input('sheets_url');
            $url = $this->normalizeGoogleSheetsUrl($inputUrl);

            if (! $url) {
                return back()->with('error', 'Tautan Google Sheets tidak valid. Pastikan tautan diawali dengan https://docs.google.com/spreadsheets/d/... dan dapat diakses publik.');
            }

            try {
                $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/csv,text/plain,*/*',
                ])->withOptions([
                    'cookies' => $cookieJar,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['https'],
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
        $unauthorizedLpks = [];

        $currentUser = auth()->user();
        $picId = $currentUser && $currentUser->isPic() ? $currentUser->id : null;

        // Kumpulkan identifier unik dari baris spreadsheet untuk query selektif
        $incomingNoRegs = [];
        $incomingAccreds = [];
        $incomingRegNos = [];

        foreach ($rows as $item) {
            $rowValues = $item['values'] ?? [];
            $rowData = [];
            foreach ($headers as $index => $key) {
                $rowData[$key] = isset($rowValues[$index]) ? trim((string) $rowValues[$index]) : null;
            }
            if (! empty($rowData['no_reg'])) {
                $val = strtoupper($rowData['no_reg']);
                $incomingNoRegs[] = $val;
                if (preg_match('/^\d+$/', $val)) {
                    $incomingNoRegs[] = 'LP-' . str_pad($val, 3, '0', STR_PAD_LEFT) . '-IDN';
                    $incomingRegNos[] = 'LP-' . str_pad($val, 3, '0', STR_PAD_LEFT) . '-IDN';
                }
            }
            if (! empty($rowData['accreditation_number'])) {
                $val = strtoupper($rowData['accreditation_number']);
                $incomingAccreds[] = $val;
                if (preg_match('/^\d+$/', $val)) {
                    $incomingAccreds[] = 'LP-' . str_pad($val, 3, '0', STR_PAD_LEFT) . '-IDN';
                    $incomingRegNos[] = 'LP-' . str_pad($val, 3, '0', STR_PAD_LEFT) . '-IDN';
                }
            }
            if (! empty($rowData['registration_number'])) {
                $val = strtoupper($rowData['registration_number']);
                $incomingRegNos[] = $val;
                if (preg_match('/^\d+$/', $val)) {
                    $incomingRegNos[] = 'LP-' . str_pad($val, 3, '0', STR_PAD_LEFT) . '-IDN';
                }
            }
        }

        $incomingNoRegs = array_values(array_unique($incomingNoRegs));
        $incomingAccreds = array_values(array_unique($incomingAccreds));
        $incomingRegNos = array_values(array_unique($incomingRegNos));

        // Pre-load hanya LPKs yang cocok dengan file input secara terproyeksi
        $allLpksQuery = Lpk::select([
            'id', 'pic_id', 'no_reg', 'accreditation_number', 'accreditation_type',
            'registration_number', 'name', 'status', 'certificate_date', 'expired_at',
            'drive_url', 'scope', 'address', 'email', 'phone', 'notes',
        ]);

        if (! empty($incomingNoRegs) || ! empty($incomingAccreds) || ! empty($incomingRegNos)) {
            $allLpksQuery->where(function ($q) use ($incomingNoRegs, $incomingAccreds, $incomingRegNos) {
                if (! empty($incomingNoRegs)) {
                    $q->whereIn('no_reg', $incomingNoRegs);
                }
                if (! empty($incomingAccreds)) {
                    $q->orWhereIn('accreditation_number', $incomingAccreds);
                }
                if (! empty($incomingRegNos)) {
                    $q->orWhereIn('registration_number', $incomingRegNos);
                }
            });
        } else {
            $allLpksQuery->whereRaw('1 = 0');
        }

        $allLpks = $allLpksQuery->with('members')->get();
        $lpksByNoReg = $allLpks->whereNotNull('no_reg')->keyBy(fn ($l) => strtoupper(trim((string) $l->no_reg)));
        $lpksByAccreditation = $allLpks->whereNotNull('accreditation_number')->keyBy(fn ($l) => strtoupper(trim((string) $l->accreditation_number)));
        $lpksByRegNo = $allLpks->whereNotNull('registration_number')->keyBy(fn ($l) => strtoupper(trim((string) $l->registration_number)));

        try {
            DB::transaction(function () use (
            $rows,
            $headers,
            $sheetHyperlinks,
            $currentUser,
            $picId,
            $lpksByNoReg,
            $lpksByAccreditation,
            $lpksByRegNo,
            &$createdCount,
            &$updatedCount,
            &$skippedCount,
            &$unauthorizedLpks
        ) {
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
                    $regNo = 'LP-' . str_pad($regNo, 3, '0', STR_PAD_LEFT) . '-IDN';
                    $accreditationNumber = $regNo;
                }

                $rawStatus = strtolower(trim((string) ($data['status'] ?? 'active')));
                if (str_contains($rawStatus, 'inactive') || str_contains($rawStatus, 'withdrawn') || str_contains($rawStatus, 'kadaluarsa')) {
                    $status = 'INACTIVE';
                } elseif (str_contains($rawStatus, 'beku') || str_contains($rawStatus, 'suspended')) {
                    $status = 'SUSPENDED';
                } else {
                    $status = 'ACTIVE';
                }

                if (! $accreditationType) {
                    $checkAccred = strtoupper((string) ($accreditationNumber ?: $regNo));
                    if (str_starts_with($checkAccred, 'LK-')) {
                        $accreditationType = 'Laboratorium Kalibrasi';
                    } elseif (str_starts_with($checkAccred, 'LM-')) {
                        $accreditationType = 'Laboratorium Medik';
                    } elseif (str_starts_with($checkAccred, 'LI-')) {
                        $accreditationType = 'Lembaga Inspeksi';
                    } else {
                        $accreditationType = 'Laboratorium Penguji';
                    }
                }

                $phone = ! empty($data['phone']) ? $data['phone'] : ($data['nomorpersonilpenghubung'] ?? null);

                $address = $data['address'] ?? null;
                if (! empty($data['provinsi']) && $address && ! str_contains(strtolower($address), strtolower($data['provinsi']))) {
                    $address .= ', ' . $data['provinsi'];
                }

                $scope = $data['scope'] ?? null;
                if (! empty($data['bidanguji']) && $scope && ! str_contains(strtolower($scope), strtolower($data['bidanguji']))) {
                    $scope = $data['bidanguji'] . ' - ' . $scope;
                }

                $notesParts = [];
                if (! empty($data['notes'])) {
                    $notesParts[] = $data['notes'];
                }
                if (! empty($data['picproses'])) {
                    $notesParts[] = 'PIC: ' . $data['picproses'];
                }
                if (! empty($data['ketuatim'])) {
                    $notesParts[] = 'Ketua Tim: ' . $data['ketuatim'];
                }
                if (! empty($data['personilpenghubung'])) {
                    $cp = 'CP: ' . $data['personilpenghubung'];
                    if (! empty($data['nomorpersonilpenghubung']) && $data['nomorpersonilpenghubung'] !== $phone) {
                        $cp .= ' (' . $data['nomorpersonilpenghubung'] . ')';
                    }
                    $notesParts[] = $cp;
                }
                if (! empty($data['ket'])) {
                    $notesParts[] = 'Standar: ' . $data['ket'];
                }
                $compiledNotes = ! empty($notesParts) ? implode(' | ', $notesParts) : null;

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
                        $candidateLink = $sheetHyperlinks[$excelRowNumber][$driveColLetter];
                    } else {
                        $candidateLink = reset($sheetHyperlinks[$excelRowNumber]);
                    }

                    if ($candidateLink && filter_var($candidateLink, FILTER_VALIDATE_URL) && str_starts_with($candidateLink, 'http')) {
                        $driveUrl = $candidateLink;
                    }
                }

                // Pencarian existing LPK menggunakan in-memory cache collection
                $existing = null;
                if (! empty($noReg)) {
                    $existing = $lpksByNoReg->get(strtoupper($noReg));
                }
                if (! $existing && ! empty($accreditationNumber)) {
                    $existing = $lpksByAccreditation->get(strtoupper($accreditationNumber))
                        ?: $lpksByRegNo->get(strtoupper($accreditationNumber));
                }
                if (! $existing && ! empty($regNo)) {
                    $existing = $lpksByRegNo->get(strtoupper($regNo))
                        ?: $lpksByAccreditation->get(strtoupper($regNo))
                        ?: $lpksByNoReg->get(strtoupper($regNo));
                }

                if ($existing) {
                    // Otorisasi keamanan: perbolehkan jika Admin atau pengguna yang memang memiliki izin mengelola LPK ini
                    $canClaimOrManage = ! $currentUser
                        || $currentUser->isAdmin()
                        || $existing->canManage($currentUser);

                    if (! $canClaimOrManage) {
                        $skippedCount++;
                        $unauthorizedLpks[] = ($existing->accreditation_number ?: $existing->registration_number) ?: $existing->name;
                        continue;
                    }

                    $existing->update([
                        'pic_id' => $currentUser && $currentUser->isAdmin() ? ($existing->pic_id ?: $picId) : $existing->pic_id,
                        'no_reg' => $noReg ? mb_substr($noReg, 0, 30) : $existing->no_reg,
                        'accreditation_number' => $accreditationNumber ? mb_substr($accreditationNumber, 0, 50) : ($existing->accreditation_number ?: $existing->registration_number),
                        'accreditation_type' => $accreditationType ?: $existing->accreditation_type,
                        'registration_number' => $accreditationNumber ? mb_substr($accreditationNumber, 0, 50) : ($existing->registration_number ?: ($noReg ?: $existing->no_reg)),
                        'name' => mb_substr($name, 0, 255),
                        'scope' => $scope ?: $existing->scope,
                        'certificate_date' => $certificateDate ?: $existing->certificate_date,
                        'address' => $address ?: $existing->address,
                        'email' => ! empty($data['email']) ? $data['email'] : $existing->email,
                        'phone' => $phone ? mb_substr($phone, 0, 30) : $existing->phone,
                        'status' => $status,
                        'expired_at' => $expiredAt ?: $existing->expired_at,
                        'drive_url' => $driveUrl ?: ($existing->drive_url && str_starts_with($existing->drive_url, 'http') ? $existing->drive_url : null),
                        'notes' => $compiledNotes ?: $existing->notes,
                    ]);
                    $existing->ensureMainLocation();
                    $updatedCount++;
                } else {
                    $createdLpk = Lpk::create([
                        'pic_id' => $picId,
                        'no_reg' => $noReg ? mb_substr($noReg, 0, 30) : null,
                        'accreditation_number' => mb_substr($accreditationNumber ?: ($regNo ?: ''), 0, 50),
                        'accreditation_type' => $accreditationType ?: 'Laboratorium Penguji',
                        'registration_number' => mb_substr($accreditationNumber ?: ($regNo ?: ($noReg ?: '')), 0, 50),
                        'name' => mb_substr($name, 0, 255),
                        'scope' => $scope,
                        'certificate_date' => $certificateDate,
                        'address' => $address,
                        'email' => $data['email'] ?? null,
                        'phone' => $phone ? mb_substr($phone, 0, 30) : null,
                        'status' => $status,
                        'expired_at' => $expiredAt,
                        'drive_url' => $driveUrl,
                        'notes' => $compiledNotes,
                    ]);

                    // Perbarui in-memory lookup cache untuk baris berikutnya
                    if ($createdLpk->no_reg) {
                        $lpksByNoReg->put(strtoupper($createdLpk->no_reg), $createdLpk);
                    }
                    if ($createdLpk->accreditation_number) {
                        $lpksByAccreditation->put(strtoupper($createdLpk->accreditation_number), $createdLpk);
                    }
                    if ($createdLpk->registration_number) {
                        $lpksByRegNo->put(strtoupper($createdLpk->registration_number), $createdLpk);
                    }

                    $createdCount++;
                }
            }
        });
        } catch (\Throwable $e) {
            Log::error('LPK import error: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memproses impor LPK: '.$e->getMessage());
        }

        \App\Services\LpkSurveillanceService::invalidateAlertsCache();

        // Audit log terstruktur
        Log::info('LPK import batch completed', [
            'user_id' => $currentUser?->id,
            'user_email' => $currentUser?->email,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'ip_address' => $request->ip(),
        ]);

        if ($createdCount === 0 && $updatedCount === 0 && $skippedCount > 0) {
            $msg = "Impor selesai. {$skippedCount} baris dilewati";
            if (! empty($unauthorizedLpks)) {
                $uniqueUnauthorized = array_values(array_unique($unauthorizedLpks));
                $sample = implode(', ', array_slice($uniqueUnauthorized, 0, 3));
                if (count($uniqueUnauthorized) > 3) {
                    $sample .= ' dst.';
                }
                $msg .= " karena terdaftar atas nama PIC lain ({$sample})";
            }
            $msg .= '.';
        } else {
            $msg = "Impor data LPK selesai: {$createdCount} LPK baru ditambahkan, {$updatedCount} diperbarui";
            if ($skippedCount > 0) {
                $msg .= ", dan {$skippedCount} baris dilewati";
                if (! empty($unauthorizedLpks)) {
                    $uniqueUnauthorized = array_values(array_unique($unauthorizedLpks));
                    $sample = implode(', ', array_slice($uniqueUnauthorized, 0, 3));
                    if (count($uniqueUnauthorized) > 3) {
                        $sample .= ' dst.';
                    }
                    $msg .= " (milik PIC lain: {$sample})";
                }
                $msg .= '.';
            } else {
                $msg .= '.';
            }
        }

        return redirect()->route('lpks.index')->with('success', $msg);
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
                'noakreditasi', 'nomorakreditasi', 'noakred' => 'accreditation_number',
                'nomorregistrasi', 'registrationnumber', 'no' => 'registration_number',
                'jenisakreditasi', 'tipeakreditasi', 'skema', 'skemaakreditasi', 'jenis' => 'accreditation_type',
                'namalpk', 'nama', 'namalembaga', 'name', 'lembagapenilaiankesesuaian', 'lpk', 'namalaboratorium', 'laboratorium' => 'name',
                'ruanglingkup', 'lingkup', 'scope', 'bidang', 'ruanglingkupakreditasi', 'ruanglingkupuji' => 'scope',
                'alamat', 'address', 'lokasi' => 'address',
                'email', 'surel', 'mail' => 'email',
                'telepon', 'telp', 'phone', 'notelp', 'teleponhp', 'teleponfax', 'telfax', 'fax', 'telpfax' => 'phone',
                'status', 'statusoperasional' => 'status',
                'tanggalterbitsertifikat', 'tanggalterbit', 'tglterbit', 'certificatedate', 'tglterbitsertif', 'terbitsertifikat', 'tanggalakreditasi' => 'certificate_date',
                'masaberlaku', 'expired', 'expireddate', 'tanggalkedaluwarsa', 'masaberlakuakreditasi', 'masaberlakuakreditasiexpired', 'expiredakreditasi', 'tanggalkadaluarsa', 'exp' => 'expired_at',
                'link', 'linkdrive', 'linkdrivedokumen', 'driveurl', 'tautandrive', 'tautan', 'linkdrivesertifikat', 'linkdriveamandemen', 'url', 'googledrive', 'linkberkas' => 'drive_url',
                'catatan', 'keterangan', 'notes' => 'notes',
                'nomor' => 'nomor_seq',
                default => $clean,
            };
        }, $headerRaw);
    }

    /**
     * Parsing fleksibel format tanggal (ISO, d/m/Y, teks bulan Indonesia, dsb.)
     */
    protected function parseDateString(?string $date): ?string
    {
        return $this->parseSpreadsheetDate($date);
    }
}
