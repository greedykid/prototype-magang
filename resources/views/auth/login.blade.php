<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | SIMASADI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
<main class="login-card" id="login-card">
    <div class="brand">
        <span class="brand-mark">K</span>
        <div>
            <strong>SIMASADI</strong>
            <small>workspace prototype</small>
        </div>
    </div>
    <p class="eyebrow">RUANG KERJA INTERNAL</p>
    <h1>Masuk untuk melanjutkan.</h1>
    <p class="lede">Gunakan akun demo untuk mempelajari alur monitoring.</p>

    @if($errors->any())
        <div class="alert error" role="alert" style="margin-top: 20px;">
            <strong>Gagal masuk:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="form-stack" id="login-form">
        @csrf
        <label>
            Email
            <input type="email" name="email" value="{{ old('email', 'demo@kanmis.local') }}" required autofocus>
        </label>
        <label>
            Password
            <input type="password" name="password" value="password" required>
        </label>
        <label class="check">
            <input type="checkbox" name="remember"> Ingat sesi ini
        </label>
        <button class="button primary login-submit-btn" type="submit" id="login-submit-btn">
            <span class="btn-text">Masuk ke workspace</span>
            <span class="btn-spinner" aria-hidden="true">
                <x-icon name="loader" size="18" />
            </span>
        </button>
    </form>
    <p class="hint">Akun demo: <code>demo@kanmis.local</code> / <code>password</code></p>
</main>
</body>
</html>
