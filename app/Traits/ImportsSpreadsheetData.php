<?php

namespace App\Traits;

trait ImportsSpreadsheetData
{
    /**
     * Parsing string CSV mentah menjadi headers ter-normalisasi dan rows terstruktur.
     *
     * @return array{headers?: array<string>, rows?: array<int, array{row_number: int, values: array<int, string|null>}>, error?: string}
     */
    protected function parseCsvContent(string $csvContent): array
    {
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', trim($csvContent));

        if (empty($csvContent)) {
            return ['error' => 'Berkas atau spreadsheet tidak memiliki data untuk diimpor.'];
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        // Deteksi delimiter dari baris pertama
        $firstLine = fgets($stream);
        if ($firstLine === false || trim($firstLine) === '') {
            fclose($stream);
            return ['error' => 'Format data spreadsheet tidak dikenali.'];
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($stream);

        $headerRaw = fgetcsv($stream, 0, $delimiter, '"', '\\');
        if (! is_array($headerRaw) || empty($headerRaw)) {
            fclose($stream);
            return ['error' => 'Gagal membaca judul kolom spreadsheet.'];
        }

        $headers = $this->normalizeHeaders($headerRaw);

        $rows = [];
        $lineNum = 1;
        while (($rowValues = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $lineNum++;
            // Lewati baris kosong
            if (count($rowValues) === 1 && $rowValues[0] === null) {
                continue;
            }
            if (empty(array_filter($rowValues, fn ($v) => $v !== null && trim((string) $v) !== ''))) {
                continue;
            }

            $rows[] = [
                'row_number' => $lineNum,
                'values' => $rowValues,
            ];
        }

        fclose($stream);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Mengubah tautan Google Sheets publik menjadi tautan ekspor CSV.
     * Membatasi skema wajib HTTPS dan host docs.google.com untuk mitigasi SSRF.
     */
    protected function normalizeGoogleSheetsUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        if (! isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        if (strtolower($parsed['scheme']) !== 'https' || strtolower($parsed['host']) !== 'docs.google.com') {
            return null;
        }

        if (preg_match('/docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheetId = $matches[1];
            $gid = '0';
            if (preg_match('/[#&?]gid=([0-9]+)/', $url, $gidMatches)) {
                $gid = $gidMatches[1];
            }

            return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        }

        return null;
    }
}
