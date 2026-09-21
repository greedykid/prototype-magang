<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Pengawasan Akreditasi KAN</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        <!-- Header -->
        <div style="background-color: #0f172a; padding: 24px; text-align: center; border-bottom: 4px solid #e11d48;">
            <h2 style="color: #ffffff; margin: 0 0 6px 0; font-size: 18px; text-transform: uppercase; letter-spacing: 0.5px;">Komite Akreditasi Nasional (KAN)</h2>
            <p style="color: #94a3b8; margin: 0; font-size: 13px;">Sistem Informasi & Administrasi Akreditasi (SIMASADI)</p>
        </div>

        <!-- Notification Banner -->
        <div style="background-color: {{ $alert['is_urgent'] ? '#fee2e2' : '#fef3c7' }}; border-left: 5px solid {{ $alert['is_urgent'] ? '#ef4444' : '#f59e0b' }}; padding: 14px 20px;">
            <strong style="color: {{ $alert['is_urgent'] ? '#991b1b' : '#92400e' }}; font-size: 14px; text-transform: uppercase;">
                {{ $alert['status_label'] }}: {{ $alert['name'] }}
            </strong>
            <div style="font-size: 13px; color: {{ $alert['is_urgent'] ? '#b91c1c' : '#b45309' }}; margin-top: 4px;">
                {{ $alert['description'] }}
            </div>
        </div>

        <!-- Body -->
        <div style="padding: 24px;">
            <p style="margin-top: 0; font-size: 14px;">Kepada Yth. <strong>PIC / Manajemen Lab {{ $lpk->name }}</strong>,</p>
            
            <p style="font-size: 14px; color: #334155;">
                Berdasarkan siklus pemeliharaan akreditasi Komite Akreditasi Nasional (KAN), sistem mencatat bahwa Lembaga Penilaian Kesesuaian (LPK) Saudara saat ini telah memasuki masa jatuh tempo untuk pelaksanaan <strong>{{ $alert['name'] }}</strong>.
            </p>

            <!-- Detail LPK Box -->
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin: 20px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; width: 40%;">Nomor Registrasi LPK</td>
                        <td style="padding: 6px 0; font-weight: bold; color: #0f172a;">{{ $lpk->registration_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Nama Lembaga (LPK)</td>
                        <td style="padding: 6px 0; font-weight: bold; color: #0f172a;">{{ $lpk->name }}</td>
                    </tr>
                    @if($lpk->scope)
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Ruang Lingkup</td>
                        <td style="padding: 6px 0; color: #334155;">{{ $lpk->scope }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Tanggal Terbit Sertifikat</td>
                        <td style="padding: 6px 0; color: #334155;">{{ $lpk->certificate_date ? $lpk->certificate_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Masa Berlaku Akreditasi</td>
                        <td style="padding: 6px 0; font-weight: bold; color: #0f172a;">{{ $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Batas Waktu / Target Kunjungan</td>
                        <td style="padding: 6px 0; font-weight: bold; color: {{ $alert['is_urgent'] ? '#ef4444' : '#d97706' }};">
                            {{ $alert['target_date'] ? $alert['target_date']->format('d/m/Y') : '-' }}
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Petunjuk Tindak Lanjut -->
            <h4 style="margin: 20px 0 10px 0; font-size: 14px; color: #0f172a;">Instruksi Tindak Lanjut:</h4>
            <ol style="font-size: 13.5px; color: #475569; padding-left: 20px; margin: 0 0 20px 0;">
                <li style="margin-bottom: 6px;">Memastikan kesiapan sistem manajemen mutu dan rekaman teknis laboratorium/inspeksi sesuai standar ISO/IEC terkait.</li>
                <li style="margin-bottom: 6px;">Menyiapkan konfirmasi personel penanggung jawab teknis dan jadwal penerimaan tim asesor penilikan KAN.</li>
                <li style="margin-bottom: 6px;">Memastikan berkas kelengkapan akreditasi dan rekaman uji banding / uji profisiensi telah terbarui.</li>
            </ol>

            @if($lpk->drive_url)
            <div style="text-align: center; margin: 24px 0;">
                <a href="{{ $lpk->drive_url }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 10px 22px; border-radius: 6px; font-size: 13.5px; font-weight: 600;">
                    Buka Berkas Google Drive LPK &rarr;
                </a>
            </div>
            @endif

            <p style="font-size: 13px; color: #64748b; margin-top: 24px; border-top: 1px dashed #cbd5e1; padding-top: 16px;">
                <em>Catatan: Pemberitahuan ini dikirimkan secara resmi oleh sistem pemantauan akreditasi SIMASADI KAN. Harap segera berkoordinasi dengan Sekretariat KAN untuk sinkronisasi jadwal penugasan asesor.</em>
            </p>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 18px 24px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;">
            <p style="margin: 0 0 4px 0;"><strong>Sekretariat Komite Akreditasi Nasional (KAN)</strong></p>
            <p style="margin: 0;">Gedung BSN, Kompleks Puspiptek Serpong, Tangerang Selatan | Email: sekretariat@kan.or.id</p>
        </div>
    </div>
</body>
</html>
