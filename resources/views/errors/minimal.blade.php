<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pemberitahuan Sistem') | SIMASADI</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-bsn.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="standalone-error-page">
    <div style="width: 100%; display: flex; justify-content: center;">
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="standalone-error-brand">
            <img src="{{ asset('images/logo-bsn.png') }}" alt="Logo BSN">
            <div>
                <strong>SIMASADI</strong>
                <div style="font-size: 11px; color: var(--muted, #64748b);">Unit Akreditasi Laboratorium KAN</div>
            </div>
        </a>
    </div>

    <div style="width: 100%; display: flex; justify-content: center;">
        @yield('content')
    </div>

    <footer class="standalone-error-foot">
        <p style="margin: 0;">&copy; {{ date('Y') }} Badan Standardisasi Nasional &bull; Komite Akreditasi Nasional</p>
    </footer>
</body>
</html>
