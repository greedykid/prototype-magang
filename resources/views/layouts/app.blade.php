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
                <div class="persistent-surveillance-banner" role="alert" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1.5px solid #f43f5e; border-left: 6px solid #e11d48; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.08);">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="background-color: #e11d48; color: #fff; border-radius: 6px; padding: 6px 10px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                PERINGATAN KAN
                            </div>
                            <div>
                                <strong style="color: #9f1239; font-size: 14.5px; display: block; margin-bottom: 2px;">
                                    Peringatan Siklus Pengawasan Akreditasi: {{ count($globalSurveillanceAlerts) }} Kunjungan Memerlukan Tindak Lanjut!
                                </strong>
                                <span style="font-size: 13px; color: #881337; line-height: 1.5;">
                                    Sesuai siklus KAN, notifikasi aktif di <strong>Bulan ke-14 (S1)</strong>, <strong>Bulan ke-35 (S2)</strong>, dan <strong>1 Tahun sebelum kedaluwarsa (Re-Akreditasi)</strong>. Notifikasi ini persisten dan tidak dapat diabaikan hingga jadwal kunjungan diagendakan.
                                </span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="{{ route('lpks.index') }}" class="button primary" style="background-color: #e11d48; border-color: #e11d48; font-size: 12.5px; padding: 6px 14px; min-height: 34px;">
                                Tinjau LPK Jatuh Tempo &rarr;
                            </a>
                        </div>
                    </div>
                </div>
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
