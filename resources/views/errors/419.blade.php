@extends('errors.minimal')

@section('title', '419 Sesi Kedaluwarsa | SIMASADI')

@section('content')
    <div class="error-page-wrapper">
        <div class="error-card" role="alert">
            <div class="error-header">
                <div class="error-icon-box session-expired" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="error-title-wrap">
                    <span class="error-code-badge session-expired">419 &bull; Sesi Kedaluwarsa</span>
                    <h1 class="error-title">Sesi Anda Telah Berakhir</h1>
                    <p class="error-desc">
                        Untuk menjaga keamanan transaksi dan kerahasiaan data akreditasi laboratorium, sesi kerja aktif telah ditutup otomatis karena batas waktu tidak aktif tercapai.
                    </p>
                </div>
            </div>

            <div class="error-context-box">
                <div class="error-context-row">
                    <span class="error-context-label">Penyebab</span>
                    <span class="error-context-val">Token verifikasi formulir kedaluwarsa</span>
                </div>
                <div class="error-context-row">
                    <span class="error-context-label">Status Data</span>
                    <span class="error-context-val">Perubahan terakhir yang belum disimpan perlu dikirim ulang</span>
                </div>
                <div class="error-context-row">
                    <span class="error-context-label">Langkah Selanjutnya</span>
                    <span class="error-context-val" style="text-align: right; max-width: 380px;">
                        Muat ulang halaman untuk memperbarui sesi, atau masuk kembali menggunakan akun Anda.
                    </span>
                </div>
            </div>

            <div class="error-actions">
                <button type="button" onclick="window.location.reload()" class="button primary">
                    <x-icon name="refresh" size="16" />
                    <span>Perbarui Sesi &amp; Muat Ulang</span>
                </button>
                <a href="{{ route('login') }}" class="button secondary">
                    <span>Masuk ke Akun Resmi</span>
                </a>
            </div>

            <div class="error-meta-footer">
                <span>Kebijakan Keamanan Sistem Akreditasi BSN/KAN</span>
                <span>Standar Perlindungan Sesi Pengguna</span>
            </div>
        </div>
    </div>
@endsection
