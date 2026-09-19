<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Keaslian Dokumen SK Akreditasi KAN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1a1a;
            --muted: #5d5b54;
            --line: #e5e3df;
            --green: #1a7f37;
            --maroon: #5645d4;
            --lavender: #f0edff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #f8f7f5;
            color: var(--ink);
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.5;
            padding: 30px 16px;
        }
        .container {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
            margin: 0 auto;
            max-width: 640px;
            overflow: hidden;
        }
        .header {
            align-items: center;
            background: linear-gradient(135deg, #2b1f7a 0%, #5645d4 100%);
            color: #ffffff;
            display: flex;
            gap: 16px;
            padding: 24px 28px;
        }
        .header-logo {
            background: #ffffff;
            border-radius: 8px;
            padding: 6px;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .header-logo img {
            max-height: 38px;
            max-width: 44px;
            object-fit: contain;
        }
        .header h1 {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.25;
        }
        .header p {
            font-size: 13px;
            opacity: 0.88;
            margin-top: 2px;
        }
        .body {
            padding: 28px;
        }
        .seal-alert {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            color: #065f46;
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            padding: 14px 18px;
        }
        .seal-alert strong {
            display: block;
            font-size: 14.5px;
        }
        .seal-alert small {
            display: block;
            font-size: 12px;
            margin-top: 2px;
            opacity: 0.9;
        }
        .info-table {
            border-collapse: collapse;
            font-size: 13.5px;
            margin-bottom: 24px;
            width: 100%;
        }
        .info-table th {
            color: var(--muted);
            font-weight: 500;
            padding: 10px 0;
            text-align: left;
            vertical-align: top;
            width: 38%;
            border-bottom: 1px solid #f1eeea;
        }
        .info-table td {
            color: var(--ink);
            font-weight: 600;
            padding: 10px 0;
            border-bottom: 1px solid #f1eeea;
        }
        .hash-code {
            background: #faf8f5;
            border: 1px solid #e7e4dc;
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 11px;
            font-weight: 600;
            padding: 8px 10px;
            word-break: break-all;
        }
        .footer {
            background: #faf9f7;
            border-top: 1px solid var(--line);
            font-size: 12px;
            color: var(--muted);
            padding: 16px 28px;
            text-align: center;
            line-height: 1.45;
        }
        .back-btn {
            display: inline-block;
            background: var(--maroon);
            color: #ffffff;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 6px;
            margin-top: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-logo">
            <img src="{{ asset('images/logo-bsn.png') }}" alt="Logo BSN">
        </div>
        <div>
            <h1>KOMITE AKREDITASI NASIONAL (KAN)</h1>
            <p>Layanan Verifikasi Integritas Tanda Tangan Elektronik BSrE</p>
        </div>
    </div>
    <div class="body">
        <div class="seal-alert">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            <div>
                <strong>DOKUMEN ASLI & TERVERIFIKASI SAH</strong>
                <small>Telah ditandatangani secara elektronik bersertifikat resmi Balai Sertifikasi Elektronik (BSrE - BSSN)</small>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <th>Nomor SK Akreditasi</th>
                <td>{{ $signature->sk_number }}</td>
            </tr>
            <tr>
                <th>Lembaga Pemohon (LPK)</th>
                <td>{{ $accreditation->lpk->name }} ({{ $accreditation->lpk->registration_number }})</td>
            </tr>
            <tr>
                <th>Pejabat Penandatangan</th>
                <td>{{ $signature->signer_name }}</td>
            </tr>
            <tr>
                <th>NIP Penandatangan</th>
                <td>{{ $signature->signer_nip ?: 'N/A' }}</td>
            </tr>
            <tr>
                <th>Jabatan</th>
                <td>{{ $signature->signer_title }}</td>
            </tr>
            <tr>
                <th>Waktu Penandatanganan</th>
                <td>{{ $signature->signed_at?->format('d F Y, H:i:s') }} WIB</td>
            </tr>
            <tr>
                <th>Seri Sertifikat Digital</th>
                <td>{{ $signature->certificate_series }}</td>
            </tr>
            <tr>
                <th>Nilai Hash Integritas (SHA-256)</th>
                <td>
                    <div class="hash-code">{{ $signature->verify_hash }}</div>
                </td>
            </tr>
        </table>

        <div style="text-align: center;">
            <a href="{{ route('accreditations.show', $accreditation) }}" class="back-btn">&larr; Kembali ke SIMASADI</a>
        </div>
    </div>
    <div class="footer">
        Verifikasi ini dihasilkan secara otomatis oleh SIMASADI KAN-BSN. Berdasarkan UU ITE No. 11/2008 & PP No. 71/2019, dokumen elektronik ini memiliki kekuatan hukum yang sah.
    </div>
</div>
</body>
</html>
