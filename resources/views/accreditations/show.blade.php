@extends('layouts.app')

@section('title', 'Detail Akreditasi #' . $accreditation->id . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('accreditations.index') }}">Semua proses</a>
        <h1>Proses akreditasi #{{ $accreditation->id }}</h1>
        <p class="lede">{{ $accreditation->lpk->name }} ({{ $accreditation->lpk->registration_number }})</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <x-status :value="$accreditation->status" />
    </div>
</div>

<section class="panel detail-list">
    <div>
        <dt>LPK</dt>
        <dd>{{ $accreditation->lpk->name }}</dd>
    </div>
    <div>
        <dt>Status Proses</dt>
        <dd><x-status :value="$accreditation->status" /></dd>
    </div>
    <div>
        <dt>Tanggal mulai</dt>
        <dd>{{ $accreditation->start_date?->format('d M Y') ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Pantek</dt>
        <dd>{{ $accreditation->pantek_at?->format('d M Y') ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Target output</dt>
        <dd>{{ $accreditation->target_output_at?->format('d M Y') ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Output dirilis</dt>
        <dd>{{ $accreditation->output_released_at?->format('d M Y') ?: 'Belum dirilis' }}</dd>
    </div>
    <div>
        <dt>PIC</dt>
        <dd>{{ $accreditation->pic ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Catatan</dt>
        <dd>{{ $accreditation->notes ?: 'Belum ada catatan.' }}</dd>
    </div>
</section>

@php
    $latestBilling = $accreditation->billings->first();
    $signature = $accreditation->signature;
    $assessments = $accreditation->lpk->assessments;
    $hasUnverifiedExpenses = $assessments->contains(function ($item) {
        return $item->expense && $item->expense->status !== 'TERVERIFIKASI';
    });
    $isPnbpPaid = $latestBilling && $latestBilling->status === 'PAID';
    $isSigned = $signature && $signature->is_signed;
    $isGatePassed = $isPnbpPaid && ! $hasUnverifiedExpenses && $isSigned;
@endphp

{{-- REKOMENDASI OPSIONAL: Quality Gate Kesiapan Terbit Output Akreditasi --}}
<section class="readiness-gate-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 8px;">
        <div class="subcard-title-group">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--ink); margin: 0; display: flex; align-items: flex-start; gap: 8px; line-height: 1.35;">
                <x-icon name="check" size="18" style="flex-shrink: 0; margin-top: 2px;" />
                <span>Audit Kesiapan Terbit SK & Sertifikat Akreditasi (Quality Gate)</span>
            </h3>
            <span class="subcard-subtitle">
                Verifikasi kepatuhan administratif & finansial sebelum penyerahan SK resmi KAN ke LPK
            </span>
        </div>
        <span class="status {{ $isGatePassed ? 'status-completed' : 'status-in_progress' }}">
            {{ $isGatePassed ? 'Siap Dirilis ke LPK' : 'Menunggu Pemenuhan Syarat' }}
        </span>
    </div>

    <div class="readiness-checklist">
        <div class="readiness-item {{ $isPnbpPaid ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ $isPnbpPaid ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">1. Realisasi Billing PNBP</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ $isPnbpPaid ? 'Lunas (NTPN: ' . $latestBilling->ntpn . ')' : 'Belum Lunas / Belum Terbit' }}
                </span>
            </div>
        </div>

        <div class="readiness-item {{ ! $hasUnverifiedExpenses ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ ! $hasUnverifiedExpenses ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">2. Administrasi Biaya Asesor</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ ! $hasUnverifiedExpenses ? 'Seluruh biaya terverifikasi SBM' : 'Ada asesmen belum verifikasi biaya' }}
                </span>
            </div>
        </div>

        <div class="readiness-item {{ $isSigned ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ $isSigned ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">3. TTE SK Akreditasi (BSrE)</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ $isSigned ? 'Ditandatangani secara digital' : 'Menunggu pembubuhan TTE' }}
                </span>
            </div>
        </div>
    </div>
</section>

{{-- FITUR 3: Realisasi Billing PNBP (SIMPONI Kemenkeu) --}}
<section class="simasadi-subcard" id="pnbp-billing-section">
    <div class="simasadi-subcard-header">
        <div class="subcard-title-group">
            <h3>
                <x-icon name="services" size="20" />
                <span>Realisasi Billing PNBP (SIMPONI Kemenkeu)</span>
            </h3>
            <span class="subcard-subtitle">
                Penerimaan Negara Bukan Pajak jasa akreditasi berdasarkan PP Tarif BSN & Sistem SIMPONI Kemenkeu
            </span>
        </div>
        <div class="header-actions">
            @if($latestBilling)
                <x-status :value="$latestBilling->status" />
                @if($latestBilling->status === 'UNPAID')
                    <button type="button" class="button primary" onclick="window.openModal('modal-pay-billing')">
                        <x-icon name="check" size="14" />
                        <span>Konfirmasi Pembayaran</span>
                    </button>
                @endif
            @else
                <button type="button" class="button primary" onclick="window.openModal('modal-create-billing')">
                    <x-icon name="plus" size="14" />
                    <span>Terbitkan Kode Billing SIMPONI</span>
                </button>
            @endif
        </div>
    </div>

    @if($latestBilling)
        <div class="pnbp-box">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <span style="font-size: 12.5px; color: var(--muted); font-weight: 600; text-transform: uppercase;">Kode Billing SIMPONI (15 Digit)</span>
                <span style="font-size: 12.5px; color: var(--muted);">
                    Berlaku s.d. <strong>{{ $latestBilling->expired_at->format('d M Y, H:i') }} WIB</strong>
                </span>
            </div>

            <div class="pnbp-code-strip">
                <div>
                    <span class="pnbp-code-val">{{ chunk_split($latestBilling->billing_code, 4, ' ') }}</span>
                    <small style="display: block; color: var(--muted); font-size: 12px; margin-top: 2px;">{{ $latestBilling->tariff_name }}</small>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 12px; color: var(--muted); display: block;">Total Nominal PNBP:</span>
                    <strong style="font-size: 20px; color: var(--maroon-dark);">Rp {{ number_format($latestBilling->amount, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div style="display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 14px; font-size: 13px;">
                <div>
                    <span style="color: var(--muted); display: block;">Status Penyetoran:</span>
                    <strong>{{ $latestBilling->status === 'PAID' ? 'Sudah Disetor ke Kas Negara' : 'Belum Dibayar oleh LPK' }}</strong>
                </div>
                @if($latestBilling->status === 'PAID')
                    <div>
                        <span style="color: var(--muted); display: block;">Nomor Transaksi (NTPN):</span>
                        <strong style="color: var(--green); font-family: monospace;">{{ $latestBilling->ntpn }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--muted); display: block;">Kanal Pembayaran:</span>
                        <strong>{{ $latestBilling->payment_channel }} ({{ $latestBilling->ntb }})</strong>
                    </div>
                    <div>
                        <span style="color: var(--muted); display: block;">Waktu Pembayaran:</span>
                        <strong>{{ $latestBilling->paid_at?->format('d M Y, H:i') }} WIB</strong>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div style="background: #faf9f6; border: 1px dashed var(--line); border-radius: 8px; padding: 24px; text-align: center;">
            <p style="color: var(--muted); margin: 0;">Belum ada kode billing SIMPONI yang diterbitkan untuk proses akreditasi ini.</p>
        </div>
    @endif
</section>

{{-- FITUR 2: Tanda Tangan Elektronik Dokumen SK (e-Sign BSrE) --}}
<section class="simasadi-subcard" id="esign-section">
    <div class="simasadi-subcard-header">
        <div class="subcard-title-group">
            <h3>
                <x-icon name="accreditations" size="20" />
                <span>Tanda Tangan Elektronik Dokumen SK & Sertifikat (e-Sign BSrE)</span>
            </h3>
            <span class="subcard-subtitle">
                Sertifikasi keabsahan dokumen SK Akreditasi berbasis Balai Sertifikasi Elektronik (BSrE - BSSN)
            </span>
        </div>
        <div class="header-actions">
            @if($signature && $signature->is_signed)
                <x-status value="SIGNED" />
                <button type="button" class="button secondary" onclick="window.openModal('modal-qr-preview')">
                    <x-icon name="eye" size="14" />
                    <span>Pratinjau QR & Sertifikat</span>
                </button>
                <a href="{{ route('accreditations.esign.verify', $signature->verify_hash) }}" target="_blank" class="button ghost">
                    <span>Verifikasi Publik &rarr;</span>
                </a>
            @else
                <x-status value="UNSIGNED" />
                <button type="button" class="button primary" onclick="window.openModal('modal-sign-doc')">
                    <x-icon name="check" size="14" />
                    <span>Tandatangani SK Secara Digital (BSrE)</span>
                </button>
            @endif
        </div>
    </div>

    @if($signature && $signature->is_signed)
        <div class="bsre-seal-badge">
            <div class="bsre-seal-top">
                <span class="bsre-logo-pill">BSrE BSSN</span>
                <div>
                    <strong style="font-size: 14.5px; color: var(--ink); display: block;">Dokumen Surat Keputusan (SK) Sah Bertanda Tangan Elektronik</strong>
                    <span style="font-size: 12px; color: var(--muted);">Tersertifikasi digital resmi sesuai UU ITE No. 11/2008</span>
                </div>
            </div>

            <div class="bsre-seal-meta">
                <div class="bsre-meta-row">
                    <span>Nomor SK Akreditasi:</span>
                    <strong>{{ $signature->sk_number }}</strong>
                </div>
                <div class="bsre-meta-row">
                    <span>Pejabat Penandatangan:</span>
                    <strong>{{ $signature->signer_name }}</strong>
                    <small style="color: var(--muted); font-size: 11px;">NIP: {{ $signature->signer_nip ?: '-' }}</small>
                </div>
                <div class="bsre-meta-row">
                    <span>Jabatan:</span>
                    <strong>{{ $signature->signer_title }}</strong>
                </div>
                <div class="bsre-meta-row">
                    <span>Waktu Penandatanganan:</span>
                    <strong>{{ $signature->signed_at?->format('d M Y H:i:s') }} WIB</strong>
                </div>
                <div class="bsre-meta-row">
                    <span>Nomor Seri Sertifikat:</span>
                    <strong style="font-family: monospace;">{{ $signature->certificate_series }}</strong>
                </div>
                <div class="bsre-meta-row">
                    <span>Hash Integritas (SHA-256):</span>
                    <div class="hash-pill" title="{{ $signature->verify_hash }}">
                        {{ substr($signature->verify_hash, 0, 24) }}...{{ substr($signature->verify_hash, -12) }}
                    </div>
                </div>
            </div>
        </div>
    @else
        <div style="background: #fafaf9; border: 1px dashed var(--line); border-radius: 8px; padding: 24px; text-align: center;">
            <p style="color: var(--muted); margin: 0;">
                Dokumen SK Akreditasi belum ditandatangani secara elektronik.
                Pastikan realisasi billing PNBP dan biaya asesor telah terverifikasi sebelum membubuhkan TTE.
            </p>
        </div>
    @endif
</section>

{{-- Modals for Billing PNBP & e-Sign --}}

{{-- Modal 1: Terbitkan Billing SIMPONI --}}
<div class="simasadi-modal" id="modal-create-billing" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Penerbitan Kode Billing PNBP (SIMPONI)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-create-billing')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('accreditations.billings.store', $accreditation) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Jenis Tarif PNBP</span>
                    <input type="text" name="tariff_name" value="PNBP Jasa Akreditasi Laboratorium / Lembaga Sertifikasi (PP PNBP BSN)" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Nominal Tarif (Rp)</span>
                    <input type="number" name="amount" value="7500000" min="100000" step="50000" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Nominal tarif jasa akreditasi sesuai regulasi PP PNBP yang berlaku.</small>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-create-billing')">Batal</button>
                <button type="submit" class="button primary">Terbitkan Kode Billing</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Konfirmasi / Pelunasan Pembayaran PNBP --}}
@if($latestBilling && $latestBilling->status === 'UNPAID')
<div class="simasadi-modal" id="modal-pay-billing" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Konfirmasi Setoran PNBP ke Kas Negara</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-pay-billing')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('accreditations.billings.pay', [$accreditation, $latestBilling]) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <div style="background: #fafaf9; border: 1px solid var(--line); border-radius: 6px; padding: 12px;">
                    <span style="font-size: 12px; color: var(--muted); display: block;">Kode Billing SIMPONI:</span>
                    <strong style="font-size: 16px; font-family: monospace;">{{ $latestBilling->billing_code }}</strong>
                    <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 4px;">
                        Nominal Tagihan: <strong>Rp {{ number_format($latestBilling->amount, 0, ',', '.') }}</strong>
                    </span>
                </div>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Kanal Pembayaran</span>
                    <select name="payment_channel" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                        <option value="Bank Mandiri (ATM / Livin)">Bank Mandiri (ATM / Livin)</option>
                        <option value="BNI (Internet Banking / Teller)">BNI (Internet Banking / Teller)</option>
                        <option value="BRI (BRIMO / Teller)">BRI (BRIMO / Teller)</option>
                        <option value="BCA (KlikBCA / ATM)">BCA (KlikBCA / ATM)</option>
                        <option value="PT Pos Indonesia (Kantor Pos)">PT Pos Indonesia (Kantor Pos)</option>
                    </select>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Nomor Transaksi Penerimaan Negara (NTPN)</span>
                    <input type="text" name="ntpn" placeholder="Kosongkan untuk simulasi otomatis 16-karakter" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">NTPN 16 digit alfanumerik yang tertera pada Bukti Penerimaan Negara (BPN).</small>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-pay-billing')">Batal</button>
                <button type="submit" class="button primary">Konfirmasi Setoran Sah</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Modal 3: Penandatanganan SK Digital (BSrE) --}}
<div class="simasadi-modal" id="modal-sign-doc" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Tanda Tangan Elektronik Dokumen SK KAN (BSrE)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-sign-doc')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('accreditations.esign.sign', $accreditation) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <div style="background: #fdfaf3; border: 1px solid #f6e6be; border-radius: 6px; padding: 12px; font-size: 13px; color: #795200;">
                    Pembubuhan TTE dilakukan atas nama <strong>Ketua Komite Akreditasi Nasional (KAN)</strong> menggunakan sertifikat digital aktif dari Balai Sertifikasi Elektronik (BSrE).
                </div>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Nomor Surat Keputusan (SK)</span>
                    <input type="text" name="sk_number" value="{{ $signature->sk_number ?? ('SK.KAN.' . str_pad($accreditation->id, 3, '0', STR_PAD_LEFT) . '/BSN/IX/' . date('Y')) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Pejabat Penandatangan</span>
                    <input type="text" name="signer_name" value="{{ $signature->signer_name ?? 'Drs. Kukuh S. Achmad, M.Sc.' }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Passphrase Sertifikat BSrE</span>
                    <input type="password" name="passphrase" value="kan-bsre-demo" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Simulasi otentikasi passphrase token sertifikat digital.</small>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-sign-doc')">Batal</button>
                <button type="submit" class="button primary">Tandatangani & Rilis SK</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 4: Pratinjau QR Code Verifikasi Keaslian Dokumen --}}
@if($signature && $signature->is_signed)
<div class="simasadi-modal" id="modal-qr-preview" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box" style="text-align: center;">
        <div class="simasadi-modal-head">
            <h4>Segel Sertifikat Digital BSrE</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-qr-preview')" aria-label="Tutup modal">&times;</button>
        </div>
        <div style="margin: 16px 0;">
            <div style="background: #ffffff; border: 2px solid var(--maroon); border-radius: 12px; display: inline-block; padding: 18px; box-shadow: 0 4px 14px rgba(86, 69, 212, 0.15);">
                {{-- Simulated SVG QR Code --}}
                <svg width="150" height="150" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 0 auto;">
                    <rect width="100" height="100" fill="#ffffff"/>
                    <rect x="5" y="5" width="30" height="30" rx="3" fill="#1a1a1a"/>
                    <rect x="9" y="9" width="22" height="22" rx="2" fill="#ffffff"/>
                    <rect x="13" y="13" width="14" height="14" rx="1" fill="#5645d4"/>
                    <rect x="65" y="5" width="30" height="30" rx="3" fill="#1a1a1a"/>
                    <rect x="69" y="9" width="22" height="22" rx="2" fill="#ffffff"/>
                    <rect x="73" y="13" width="14" height="14" rx="1" fill="#5645d4"/>
                    <rect x="5" y="65" width="30" height="30" rx="3" fill="#1a1a1a"/>
                    <rect x="9" y="69" width="22" height="22" rx="2" fill="#ffffff"/>
                    <rect x="13" y="73" width="14" height="14" rx="1" fill="#5645d4"/>
                    <rect x="42" y="10" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="52" y="18" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="42" y="42" width="16" height="16" rx="2" fill="#5645d4"/>
                    <rect x="65" y="42" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="80" y="52" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="42" y="68" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="55" y="78" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="75" y="72" width="16" height="16" fill="#1a1a1a"/>
                </svg>
            </div>
            <p style="font-size: 13.5px; font-weight: 600; color: var(--ink); margin: 14px 0 4px;">
                {{ $signature->sk_number }}
            </p>
            <span style="font-size: 12px; color: var(--muted); display: block;">
                Scan QR untuk memvalidasi integritas dokumen secara langsung via portal publik BSrE
            </span>
        </div>
        <div class="modal-form-actions" style="justify-content: center;">
            <a href="{{ route('accreditations.esign.verify', $signature->verify_hash) }}" target="_blank" class="button primary">
                Buka Halaman Verifikasi Publik &rarr;
            </a>
            <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-qr-preview')">
                Tutup
            </button>
        </div>
    </div>
</div>
@endif

@endsection
