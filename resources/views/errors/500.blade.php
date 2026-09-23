@extends(auth()->check() ? 'layouts.app' : 'errors.minimal')

@section('title', '500 Kendala Server | SIMASADI')

@section('content')
    <div class="error-page-wrapper">
        <div class="error-card" role="alert">
            <div class="error-header">
                <div class="error-icon-box server-error" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 9v4"/>
                        <path d="M12 17h.01"/>
                        <path d="M5 19h14a2 2 0 0 0 1.84-2.75L13.74 4a2 2 0 0 0-3.48 0L3.16 16.25A2 2 0 0 0 5 19Z"/>
                    </svg>
                </div>
                <div class="error-title-wrap">
                    <span class="error-code-badge server-error">500 &bull; Kendala Sistem</span>
                    <h1 class="error-title">Terjadi Kendala pada Layanan</h1>
                    <p class="error-desc">
                        Sistem sedang mengalami gangguan sementara saat memproses permintaan Anda. Catatan transaksi Anda tetap aman dalam basis data.
                    </p>
                </div>
            </div>

            <div class="error-context-box">
                <div class="error-context-row">
                    <span class="error-context-label">Waktu Pencatatan</span>
                    <span class="error-context-val">{{ now()->format('d/m/Y H:i:s') }} WIB</span>
                </div>
                <div class="error-context-row">
                    <span class="error-context-label">Status Penanganan</span>
                    <span class="error-context-val">Log diagnostik otomatis tersimpan</span>
                </div>
                <div class="error-context-row">
                    <span class="error-context-label">Saran Tindakan</span>
                    <span class="error-context-val" style="text-align: right; max-width: 380px;">
                        Coba muat ulang halaman dalam beberapa detik. Jika kendala masih berlanjut, hubungi tim pengelola infrastruktur KAN.
                    </span>
                </div>
            </div>

            <div class="error-actions">
                <button type="button" onclick="window.location.reload()" class="button primary">
                    <x-icon name="refresh" size="16" />
                    <span>Muat Ulang Halaman</span>
                </button>
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="button secondary">
                        <x-icon name="dashboard" size="16" />
                        <span>Kembali ke Ringkasan</span>
                    </a>
                    <a href="{{ route('issues.create') }}" class="button secondary">
                        <x-icon name="issues" size="16" />
                        <span>Laporkan Kendala</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="button secondary">
                        <span>Menuju Halaman Masuk</span>
                    </a>
                @endif
            </div>

            <div class="error-meta-footer">
                <span>SIMASADI &bull; Direktorat Akreditasi Laboratorium KAN</span>
                <span>Pusat Layanan dan Infrastruktur IT BSN</span>
            </div>
        </div>
    </div>
@endsection
