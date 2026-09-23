@extends(auth()->check() ? 'layouts.app' : 'errors.minimal')

@section('title', '404 Halaman Tidak Ditemukan | SIMASADI')

@section('content')
    <div class="error-page-wrapper">
        <div class="error-card" role="alert">
            <div class="error-header">
                <div class="error-icon-box not-found" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </div>
                <div class="error-title-wrap">
                    <span class="error-code-badge not-found">404 &bull; Tidak Ditemukan</span>
                    <h1 class="error-title">Halaman Tidak Ditemukan</h1>
                    <p class="error-desc">
                        Tautan yang Anda tuju tidak tersedia atau telah dipindahkan ke bagian lain di sistem SIMASADI.
                    </p>
                </div>
            </div>

            <div class="error-context-box">
                <div class="error-context-row">
                    <span class="error-context-label">Jalur Akses (URL)</span>
                    <span class="error-context-val" style="word-break: break-all;">/{{ request()->path() }}</span>
                </div>
                <div class="error-context-row">
                    <span class="error-context-label">Status Penelusuran</span>
                    <span class="error-context-val">Objek atau rute tidak terdaftar</span>
                </div>
                <div class="error-context-row">
                    <span class="error-context-label">Saran Tindakan</span>
                    <span class="error-context-val" style="text-align: right; max-width: 380px;">
                        Pastikan tautan sudah benar, atau gunakan menu navigasi untuk mencari data laboratorium dan agenda terkait.
                    </span>
                </div>
            </div>

            <div class="error-actions">
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="button primary">
                        <x-icon name="dashboard" size="16" />
                        <span>Kembali ke Ringkasan</span>
                    </a>
                    <a href="{{ route('lpks.index') }}" class="button secondary">
                        <x-icon name="lpks" size="16" />
                        <span>Daftar Laboratorium</span>
                    </a>
                    <button type="button" onclick="if (window.history.length > 1 && document.referrer && document.referrer.includes(window.location.host)) { window.history.back(); } else { window.location.href = '{{ route('dashboard') }}'; }" class="button secondary">
                        <x-icon name="arrow-left" size="16" />
                        <span>Halaman Sebelumnya</span>
                    </button>
                @else
                    <a href="{{ route('login') }}" class="button primary">
                        <span>Menuju Halaman Masuk</span>
                    </a>
                @endif
            </div>

            <div class="error-meta-footer">
                <span>SIMASADI &bull; Direktorat Akreditasi Laboratorium KAN</span>
                <span>Laporkan jika ini merupakan tautan rusak</span>
            </div>
        </div>
    </div>
@endsection
