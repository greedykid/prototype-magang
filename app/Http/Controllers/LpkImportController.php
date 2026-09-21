<?php

namespace App\Http\Controllers;

use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LpkImportController extends Controller
{
    /**
     * Mengunduh berkas template CSV resmi untuk impor LPK.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-import-lpk.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $columns = [
            'nomor_registrasi',
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
                'LP-101-IDN',
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
                'LK-202-IDN',
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
                'LI-303-IDN',
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
     * Memproses impor LPK dari berkas CSV unggahan atau tautan Google Sheets.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'sheets_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $csvContent = '';

        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $csvContent = file_get_contents($file->getRealPath());
        } elseif ($request->filled('sheets_url')) {
            $url = $this->normalizeGoogleSheetsUrl($request->input('sheets_url'));

            try {
                $response = Http::timeout(15)->get($url);
                if (! $response->successful()) {
                    return back()->with('error', 'Gagal mengunduh spreadsheet dari URL yang diberikan. Pastikan tautan dapat diakses publik (Anyone with the link).');
                }
                $csvContent = $response->body();
            } catch (\Throwable $e) {
                return back()->with('error', 'Terjadi kesalahan koneksi saat mengakses Google Sheets: ' . $e->getMessage());
            }
        } else {
            return back()->with('error', 'Silakan unggah berkas CSV atau masukkan tautan Google Sheets.');
        }

        // Hapus BOM UTF-8 jika ada
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', trim($csvContent));

        if (empty($csvContent)) {
            return back()->with('error', 'Berkas atau spreadsheet tidak memiliki data untuk diimpor.');
        }

        // Pisahkan per baris
        $lines = preg_split('/\r\n|\r|\n/', $csvContent);
        if (empty($lines)) {
            return back()->with('error', 'Format data spreadsheet tidak dikenali.');
        }

        // Deteksi pemisah (koma atau titik koma)
        $firstLine = $lines[0];
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $headerRaw = str_getcsv(array_shift($lines), $delimiter);
        $headers = array_map(function ($col) {
            $clean = strtolower(trim((string) $col));
            $clean = str_replace([' ', '-', '_', '.', '/', '(', ')', ':', ',', '\\'], '', $clean);

            return match ($clean) {
                'nomorregistrasi', 'noreg', 'registrationnumber', 'nomorreg', 'noakreditasi', 'nomorakreditasi', 'noakred', 'nomor', 'no' => 'registration_number',
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

        if (! in_array('registration_number', $headers, true) || ! in_array('name', $headers, true)) {
            return back()->with('error', 'Format kolom tidak sesuai. Kolom "nomor_registrasi" (atau "NO. AKREDITASI") dan "nama_lpk" wajib ada. Silakan periksa kembali judul kolom spreadsheet.');
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line, $delimiter);
            if (count($row) < 2) {
                $skippedCount++;
                continue;
            }

            $data = [];
            foreach ($headers as $index => $key) {
                $data[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $regNo = $data['registration_number'] ?? null;
            $name = $data['name'] ?? null;

            if (! $regNo || ! $name) {
                $skippedCount++;
                continue;
            }

            // Normalisasi otomatis nomor akreditasi numerik murni (misal: 077 -> LP-077-IDN)
            if (preg_match('/^\d+$/', $regNo)) {
                $regNo = 'LP-' . str_pad($regNo, 3, '0', STR_PAD_LEFT) . '-IDN';
            }

            $status = strtoupper($data['status'] ?? 'ACTIVE');
            if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
                $status = 'ACTIVE';
            }

            $certificateDate = $this->parseDateString($data['certificate_date'] ?? null);
            $expiredAt = $this->parseDateString($data['expired_at'] ?? null);
            if (! $expiredAt && $certificateDate) {
                $expiredAt = date('Y-m-d', strtotime('+5 years', strtotime($certificateDate)));
            }

            $driveUrl = ! empty($data['drive_url']) && filter_var($data['drive_url'], FILTER_VALIDATE_URL) ? $data['drive_url'] : ($data['drive_url'] ?? null);

            $existing = Lpk::where('registration_number', $regNo)->first();

            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'scope' => ! empty($data['scope']) ? $data['scope'] : $existing->scope,
                    'certificate_date' => $certificateDate ?: $existing->certificate_date,
                    'address' => ! empty($data['address']) ? $data['address'] : $existing->address,
                    'email' => ! empty($data['email']) ? $data['email'] : $existing->email,
                    'phone' => ! empty($data['phone']) ? $data['phone'] : $existing->phone,
                    'status' => $status,
                    'expired_at' => $expiredAt ?: $existing->expired_at,
                    'drive_url' => $driveUrl ?: $existing->drive_url,
                    'notes' => ! empty($data['notes']) ? $data['notes'] : $existing->notes,
                ]);
                $updatedCount++;
            } else {
                Lpk::create([
                    'registration_number' => $regNo,
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
        // Format standar: https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit#gid=0
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
}
