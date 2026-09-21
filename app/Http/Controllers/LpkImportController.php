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
            'alamat',
            'email',
            'telepon',
            'status',
            'masa_berlaku',
            'link_drive_sertifikat',
            'link_drive_amandemen',
        ];

        $samples = [
            [
                'LP-101-IDN',
                'Balai Pengujian Lingkungan Sejahtera',
                'Jl. M.H. Thamrin No. 12, Jakarta Pusat',
                'kontak@lablingkungan.id',
                '021-3141234',
                'ACTIVE',
                date('Y-m-d', strtotime('+3 years')),
                'https://drive.google.com/file/d/1demo-sertifikat-lp101/view',
                'https://drive.google.com/drive/folders/1demo-amandemen-lp101',
            ],
            [
                'LK-202-IDN',
                'Pusat Kalibrasi Presisi Bandung',
                'Jl. Ir. H. Juanda No. 88, Bandung',
                'layanan@kalibrasipresisi.co.id',
                '022-2508899',
                'ACTIVE',
                date('Y-m-d', strtotime('+4 years')),
                'https://drive.google.com/file/d/1demo-sertifikat-lk202/view',
                'https://drive.google.com/drive/folders/1demo-amandemen-lk202',
            ],
            [
                'LI-303-IDN',
                'Lembaga Inspeksi Teknik Terpadu',
                'Jl. Pemuda No. 45, Surabaya',
                'sekretariat@inspeksiteknik.id',
                '031-5345678',
                'ACTIVE',
                date('Y-m-d', strtotime('+2 years')),
                'https://drive.google.com/file/d/1demo-sertifikat-li303/view',
                'https://drive.google.com/drive/folders/1demo-amandemen-li303',
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
            $clean = strtolower(trim($col));
            $clean = str_replace([' ', '-', '_'], '', $clean);

            return match ($clean) {
                'nomorregistrasi', 'noreg', 'registrationnumber', 'nomorreg' => 'registration_number',
                'namalpk', 'nama', 'namalembaga', 'name', 'lembagapenilaiankesesuaian' => 'name',
                'alamat', 'address' => 'address',
                'email', 'surel' => 'email',
                'telepon', 'telp', 'phone', 'notelp', 'teleponhp' => 'phone',
                'status', 'statusoperasional' => 'status',
                'masaberlaku', 'expired', 'expireddate', 'tanggalkedaluwarsa', 'masaberlakuakreditasi' => 'expired_at',
                'linkdrivesertifikat', 'drivesertifikat', 'sertifikatdrive', 'linkdrive' => 'certificate_drive_url',
                'linkdriveamandemen', 'driveamandemen', 'amandemendrive', 'lampirandrive' => 'amendment_drive_url',
                'catatan', 'keterangan', 'notes' => 'notes',
                default => $clean,
            };
        }, $headerRaw);

        if (! in_array('registration_number', $headers, true) || ! in_array('name', $headers, true)) {
            return back()->with('error', 'Format kolom tidak sesuai. Kolom "nomor_registrasi" dan "nama_lpk" wajib ada. Silakan unduh template contoh.');
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
                $data[$key] = isset($row[$index]) ? trim($row[$index]) : null;
            }

            $regNo = $data['registration_number'] ?? null;
            $name = $data['name'] ?? null;

            if (! $regNo || ! $name) {
                $skippedCount++;
                continue;
            }

            $status = strtoupper($data['status'] ?? 'ACTIVE');
            if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
                $status = 'ACTIVE';
            }

            $expiredAt = ! empty($data['expired_at']) ? date('Y-m-d', strtotime($data['expired_at'])) : null;
            $certUrl = ! empty($data['certificate_drive_url']) ? $data['certificate_drive_url'] : null;
            $amendUrl = ! empty($data['amendment_drive_url']) ? $data['amendment_drive_url'] : null;

            $existing = Lpk::where('registration_number', $regNo)->first();

            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'address' => ! empty($data['address']) ? $data['address'] : $existing->address,
                    'email' => ! empty($data['email']) ? $data['email'] : $existing->email,
                    'phone' => ! empty($data['phone']) ? $data['phone'] : $existing->phone,
                    'status' => $status,
                    'expired_at' => $expiredAt ?: $existing->expired_at,
                    'certificate_drive_url' => $certUrl ?: $existing->certificate_drive_url,
                    'amendment_drive_url' => $amendUrl ?: $existing->amendment_drive_url,
                    'notes' => ! empty($data['notes']) ? $data['notes'] : $existing->notes,
                ]);
                $updatedCount++;
            } else {
                Lpk::create([
                    'registration_number' => $regNo,
                    'name' => $name,
                    'address' => $data['address'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'status' => $status,
                    'expired_at' => $expiredAt,
                    'certificate_drive_url' => $certUrl,
                    'amendment_drive_url' => $amendUrl,
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
