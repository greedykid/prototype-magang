@extends(auth()->check() ? 'layouts.app' : 'errors.minimal')

@section('title', '403 Akses Dibatasi | SIMASADI')

@section('content')
    <div class="error-page-wrapper">
        <div class="error-card" role="alert">
            <div class="error-header">
                <div class="error-icon-box forbidden" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <div class="error-title-wrap">
                    <span class="error-code-badge forbidden">403 &bull; Batasan Akses</span>
                    <h1 class="error-title">Akses ke Fitur Ini Dibatasi</h1>
                    <p class="error-desc">
                        {{ $exception->getMessage() ?: 'Halaman atau tindakan ini memerlukan wewenang khusus yang tidak tercakup dalam peran akun Anda saat ini.' }}
                    </p>
                </div>
            </div>

            <div class="error-context-box">
                @if(auth()->check())
                    <div class="error-context-row">
                        <span class="error-context-label">Nama Pengguna</span>
                        <span class="error-context-val">{{ auth()->user()->name }} ({{ auth()->user()->email }})</span>
                    </div>
                    <div class="error-context-row">
                        <span class="error-context-label">Peran Akun Aktif</span>
                        <span class="error-context-val">{{ auth()->user()->role_label }}</span>
                    </div>
                @else
                    <div class="error-context-row">
                        <span class="error-context-label">Status Sesi</span>
                        <span class="error-context-val">Belum Masuk (Tamu)</span>
                    </div>
                @endif
                <div class="error-context-row">
                    <span class="error-context-label">Kewenangan yang Diperlukan</span>
                    <span class="error-context-val">Administrator Unit Akreditasi KAN</span>
                </div>
                <div class="error-context-row">
                    <span class="error-context-label">Ketentuan Tata Kelola</span>
                    <span class="error-context-val" style="text-align: right; max-width: 380px;">
                        Tindakan administratif data induk LPK dan konfigurasi sistem dibatasi untuk menjamin integritas catatan akreditasi resmi KAN.
                    </span>
                </div>
            </div>

            <div class="error-actions">
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="button primary">
                        <x-icon name="dashboard" size="16" />
                        <span>Kembali ke Ringkasan</span>
                    </a>
                    <button type="button" onclick="if (window.history.length > 1 && document.referrer && document.referrer.includes(window.location.host)) { window.history.back(); } else { window.location.href = '{{ route('dashboard') }}'; }" class="button secondary">
                        <x-icon name="arrow-left" size="16" />
                        <span>Halaman Sebelumnya</span>
                    </button>
                @else
                    <a href="{{ route('login') }}" class="button primary">
                        <span>Masuk ke Akun Resmi</span>
                    </a>
                @endif
            </div>

            <div class="error-meta-footer">
                <span>Waktu Kejadian: {{ now()->format('d/m/Y H:i:s') }} WIB</span>
                <span>Butuh peningkatan akses? Hubungi Administrator Unit KAN</span>
            </div>
        </div>
    </div>
@endsection
