@extends('layouts.app')

@section('title', 'Detail Akreditasi #' . $accreditation->id . ' | SIMASADI')

@section('content')
<x-page-header
    :backUrl="route('accreditations.index')"
    backText="Semua proses"
    :title="'Proses akreditasi #' . $accreditation->id"
    :subtitle="$accreditation->lpk->name . ' (' . $accreditation->lpk->registration_number . ')'"
>
    <x-status :value="$accreditation->status" />
</x-page-header>

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
@endphp

@include('accreditations.partials.quality-gate')


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
                <a href="{{ route('accreditations.esign.verify', $signature->verify_hash) }}" target="_blank" class="button ghost" style="display: inline-flex; align-items: center; gap: 4px;">
                    <span>Verifikasi Publik</span>
                    <x-icon name="chevron-right" size="14" />
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

@include('accreditations.partials.modals')


@endsection
