<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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
     * Parsing berkas Excel (.xlsx) menjadi headers, rows, dan hyperlinks terstruktur.
     *
     * @return array{headers: array<string>, rows: array<int, array{row_number: int, values: array<int, string|null>}>, hyperlinks?: array<int, array<string, string>>}
     */
    protected function parseXlsxFile(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);
        \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)->disableCalculationCache();
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
                        $rawDateVal = $cell->isFormula() ? $cell->getCalculatedValue() : $cell->getValue();
                        if (is_numeric($rawDateVal)) {
                            $dateObj = ExcelDate::excelToDateTimeObject((float) $rawDateVal);
                            $val = $dateObj->format('Y-m-d');
                        } else {
                            $val = (string) ($rawDateVal ?? '');
                        }
                    } catch (\Throwable) {
                        $val = (string) ($cell->getCalculatedValue() ?? '');
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
