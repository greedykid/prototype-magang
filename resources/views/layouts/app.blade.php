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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten</a>

<div class="app-shell">
    @include('layouts.partials.sidebar')
    <div class="drawer-backdrop" data-drawer-close></div>

    <main id="main-content" class="main-content">
        @include('layouts.partials.topbar')

        <div class="page-wrap" id="page-content-wrapper">
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

@include('layouts.partials.logout-curtain')
</body>
</html>
