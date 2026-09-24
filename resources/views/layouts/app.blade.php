<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMASADI Workspace')</title>
    <script>
        if (localStorage.getItem('simasadi_sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-is-collapsed');
        }
    </script>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-bsn.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten</a>

<div class="app-shell">
    @include('layouts.partials.sidebar')

    <main id="main-content" class="main-content">
        @include('layouts.partials.topbar')

        <div class="page-wrap" id="page-content-wrapper">
            @if(!empty($globalSurveillanceAlerts))
                <aside class="persistent-surveillance-banner" role="alert" aria-label="Peringatan Siklus Pengawasan Akreditasi">
                    <div class="persistent-surveillance-banner-inner">
                        <div class="persistent-surveillance-banner-main">
                            <div class="persistent-surveillance-banner-badge">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <span>PERINGATAN KAN</span>
                            </div>
                            <strong class="persistent-surveillance-banner-title">
                                Peringatan Siklus Pengawasan Akreditasi: {{ count($globalSurveillanceAlerts) }} Kunjungan Memerlukan Tindak Lanjut!
                            </strong>
                            <p class="persistent-surveillance-banner-desc">
                                Sesuai siklus KAN, notifikasi aktif di <strong>Bulan ke-14 (S1)</strong>, <strong>Bulan ke-35 (S2)</strong>, dan <strong>1 Bulan sebelum kedaluwarsa (Re-Akreditasi)</strong>. Notifikasi ini persisten dan tidak dapat diabaikan hingga jadwal kunjungan diagendakan.
                            </p>
                        </div>
                        <div class="persistent-surveillance-banner-action">
                            <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="button primary persistent-surveillance-banner-btn">
                                <span>Tinjau LPK Jatuh Tempo</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </aside>
            @endif

            @if(session('success'))
                <div id="flash-success-data" data-message="{{ session('success') }}" style="display: none;"></div>
            @endif
            @if(session('error'))
                <div id="flash-error-data" data-message="{{ session('error') }}" style="display: none;"></div>
            @endif
            @if($errors->any())
                <div id="flash-errors-data" data-errors='@json($errors->all())' data-title="Periksa kembali formulir" style="display: none;"></div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<div class="drawer-backdrop" data-drawer-close></div>

@include('layouts.partials.logout-curtain')
</body>
</html>
