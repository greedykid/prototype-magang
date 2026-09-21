<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <title>Pemberitahuan Pengawasan Akreditasi KAN</title>
    <style>
        /* Base Resets */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        /* Mobile Breakpoint Styles (<= 620px) */
        @media only screen and (max-width: 620px) {
            .email-body {
                padding: 10px 8px !important;
            }
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
                border-radius: 6px !important;
            }
            .header-banner {
                padding: 18px 14px !important;
            }
            .header-banner h2 {
                font-size: 16px !important;
                line-height: 1.3 !important;
            }
            .notice-banner {
                padding: 12px 14px !important;
            }
            .notice-banner strong {
                font-size: 13px !important;
            }
            .content-area {
                padding: 18px 14px !important;
            }
            .detail-card {
                padding: 14px 12px !important;
                margin: 16px 0 !important;
            }
            /* Stack table rows on mobile: label above value for full-width legibility */
            .detail-table,
            .detail-table tbody,
            .detail-table tr,
            .detail-table td {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .detail-table tr {
                margin-bottom: 12px !important;
                padding-bottom: 10px !important;
                border-bottom: 1px dashed #e2e8f0 !important;
            }
            .detail-table tr:last-child {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
                border-bottom: none !important;
            }
            .detail-table td.label-col {
                padding: 0 0 4px 0 !important;
                width: 100% !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.04em !important;
                color: #64748b !important;
            }
            .detail-table td.val-col {
                padding: 0 !important;
                width: 100% !important;
                font-size: 13.5px !important;
                color: #0f172a !important;
            }
            .scope-box {
                max-height: 150px !important;
                font-size: 12px !important;
                padding: 10px !important;
                margin-top: 2px !important;
            }
            .instructions-list {
                padding-left: 18px !important;
                font-size: 13px !important;
            }
            .btn-drive-wrap {
                margin: 20px 0 !important;
            }
            .btn-drive {
                display: block !important;
                width: 100% !important;
                text-align: center !important;
                padding: 12px 16px !important;
                min-height: 44px !important;
                line-height: 20px !important;
                box-sizing: border-box !important;
            }
            .footer-card {
                padding: 16px 14px !important;
                font-size: 11.5px !important;
            }
        }
    </style>
</head>
<body class="email-body" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; margin: 0; padding: 24px 12px;">
    <div class="email-container" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); box-sizing: border-box;">
        
        <!-- Header -->
        <div class="header-banner" style="background-color: #0f172a; padding: 24px; text-align: center; border-bottom: 4px solid #e11d48;">
            <h2 style="color: #ffffff; margin: 0 0 6px 0; font-size: 18px; text-transform: uppercase; letter-spacing: 0.5px;">Komite Akreditasi Nasional (KAN)</h2>
            <p style="color: #94a3b8; margin: 0; font-size: 13px;">Sistem Informasi & Administrasi Akreditasi (SIMASADI)</p>
        </div>

        <!-- Notification Banner -->
        <div class="notice-banner" style="background-color: {{ $alert['is_urgent'] ? '#fee2e2' : '#fef3c7' }}; border-left: 5px solid {{ $alert['is_urgent'] ? '#ef4444' : '#f59e0b' }}; padding: 14px 20px; word-break: break-word;">
            <strong style="color: {{ $alert['is_urgent'] ? '#991b1b' : '#92400e' }}; font-size: 14px; text-transform: uppercase; display: block;">
                {{ $alert['status_label'] }}: {{ $alert['name'] }}
            </strong>
            <div style="font-size: 13px; color: {{ $alert['is_urgent'] ? '#b91c1c' : '#b45309' }}; margin-top: 4px;">
                {{ $alert['description'] }}
            </div>
        </div>

        <!-- Body -->
        <div class="content-area" style="padding: 24px;">
            <p style="margin-top: 0; font-size: 14px; word-break: break-word;">Kepada Yth. <strong>PIC / Manajemen Lab {{ $lpk->name }}</strong>,</p>
            
            <p style="font-size: 14px; color: #334155; line-height: 1.6; word-break: break-word;">
                Berdasarkan siklus pemeliharaan akreditasi Komite Akreditasi Nasional (KAN), sistem mencatat bahwa Lembaga Penilaian Kesesuaian (LPK) Saudara saat ini telah memasuki masa jatuh tempo untuk pelaksanaan <strong>{{ $alert['name'] }}</strong>.
            </p>

            <!-- Detail LPK Box -->
            <div class="detail-card" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin: 20px 0; box-sizing: border-box;">
                <table class="detail-table" style="width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed;">
                    <tr>
                        <td class="label-col" style="padding: 7px 10px 7px 0; color: #64748b; width: 34%; vertical-align: top; font-size: 12.5px; word-break: break-word;">Nomor Registrasi LPK</td>
                        <td class="val-col" style="padding: 7px 0; font-weight: bold; color: #0f172a; width: 66%; vertical-align: top; word-break: break-word;">{{ $lpk->registration_number }}</td>
                    </tr>
                    <tr>
                        <td class="label-col" style="padding: 7px 10px 7px 0; color: #64748b; width: 34%; vertical-align: top; font-size: 12.5px; word-break: break-word;">Nama Lembaga (LPK)</td>
                        <td class="val-col" style="padding: 7px 0; font-weight: bold; color: #0f172a; width: 66%; vertical-align: top; word-break: break-word;">{{ $lpk->name }}</td>
                    </tr>
                    @if($lpk->scope)
                    <tr>
                        <td class="label-col" style="padding: 7px 10px 7px 0; color: #64748b; width: 34%; vertical-align: top; font-size: 12.5px; word-break: break-word;">Ruang Lingkup</td>
                        <td class="val-col" style="padding: 7px 0; color: #334155; width: 66%; vertical-align: top; word-break: break-word;">
                            <div class="scope-box" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 12px; font-size: 12px; line-height: 1.5; color: #475569; max-height: 180px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;">{{ $lpk->scope }}</div>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label-col" style="padding: 7px 10px 7px 0; color: #64748b; width: 34%; vertical-align: top; font-size: 12.5px; word-break: break-word;">Tanggal Terbit Sertifikat</td>
                        <td class="val-col" style="padding: 7px 0; color: #334155; width: 66%; vertical-align: top; word-break: break-word;">{{ $lpk->certificate_date ? \Carbon\Carbon::parse($lpk->certificate_date)->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col" style="padding: 7px 10px 7px 0; color: #64748b; width: 34%; vertical-align: top; font-size: 12.5px; word-break: break-word;">Masa Berlaku Akreditasi</td>
                        <td class="val-col" style="padding: 7px 0; font-weight: bold; color: #0f172a; width: 66%; vertical-align: top; word-break: break-word;">{{ $lpk->expired_at ? \Carbon\Carbon::parse($lpk->expired_at)->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col" style="padding: 7px 10px 7px 0; color: #64748b; width: 34%; vertical-align: top; font-size: 12.5px; word-break: break-word;">Batas Waktu / Target Kunjungan</td>
                        <td class="val-col" style="padding: 7px 0; font-weight: bold; color: {{ $alert['is_urgent'] ? '#ef4444' : '#d97706' }}; width: 66%; vertical-align: top; word-break: break-word;">
                            {{ $alert['target_date'] ? \Carbon\Carbon::parse($alert['target_date'])->format('d/m/Y') : '-' }}
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Petunjuk Tindak Lanjut -->
            <h4 style="margin: 20px 0 10px 0; font-size: 14px; color: #0f172a;">Instruksi Tindak Lanjut:</h4>
            <ol class="instructions-list" style="font-size: 13.5px; color: #475569; padding-left: 20px; margin: 0 0 20px 0; word-break: break-word;">
                <li style="margin-bottom: 8px;">Memastikan kesiapan sistem manajemen mutu dan rekaman teknis laboratorium/inspeksi sesuai standar ISO/IEC terkait.</li>
                <li style="margin-bottom: 8px;">Menyiapkan konfirmasi personel penanggung jawab teknis dan jadwal penerimaan tim asesor penilikan KAN.</li>
                <li style="margin-bottom: 8px;">Memastikan berkas kelengkapan akreditasi dan rekaman uji banding / uji profisiensi telah terbarui.</li>
            </ol>

            @if($lpk->drive_url && str_starts_with($lpk->drive_url, 'http'))
            <div class="btn-drive-wrap" style="text-align: center; margin: 24px 0;">
                <a href="{{ $lpk->drive_url }}" class="btn-drive" target="_blank" rel="noopener noreferrer" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 6px; font-size: 13.5px; font-weight: 600; box-sizing: border-box; word-break: break-word;">
                    Buka Berkas Google Drive LPK &rarr;
                </a>
            </div>
            @endif

            <p style="font-size: 13px; color: #64748b; margin-top: 24px; border-top: 1px dashed #cbd5e1; padding-top: 16px; word-break: break-word; line-height: 1.5;">
                <em>Catatan: Pemberitahuan ini dikirimkan secara resmi oleh sistem pemantauan akreditasi SIMASADI KAN. Harap segera berkoordinasi dengan Sekretariat KAN untuk sinkronisasi jadwal penugasan asesor.</em>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer-card" style="background-color: #f8fafc; padding: 18px 24px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; word-break: break-word;">
            <p style="margin: 0 0 4px 0;"><strong>Sekretariat Komite Akreditasi Nasional (KAN)</strong></p>
            <p style="margin: 0; line-height: 1.5;">Gedung BSN, Kompleks Puspiptek Serpong, Tangerang Selatan | Email: sekretariat@kan.or.id</p>
        </div>
    </div>
</body>
</html>
