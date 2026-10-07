<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | SIMASADI</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-bsn.png') }}">
    <script>
        (function () {
            var savedTheme = localStorage.getItem('simasadi_theme');
            var theme = savedTheme || ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    @if(config('services.recaptcha.enabled'))
        <script src="https://www.google.com/recaptcha/api.js?hl=id" async defer></script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
<main class="login-card" id="login-card">
    <div class="brand login-brand">
        <img src="{{ asset('images/logo-bsn.png') }}" alt="Logo BSN" class="login-brand-logo" width="86" height="36">
        <div class="login-brand-text">
            <strong>SIMASADI</strong>
            <small>Unit Akreditasi Laboratorium</small>
        </div>
    </div>
    <h1>Masuk untuk melanjutkan.</h1>
    <p class="lede">Unit Internal Direktorat Akreditasi Laboratorium &bull; KAN</p>

    @if($errors->any())
        <div id="flash-errors-data" data-errors='@json($errors->all())' data-title="Gagal Masuk" style="display: none;"></div>
    @endif

    <div class="role-selector-wrap" aria-label="Pemilih peran cepat">
        <span class="role-selector-title">Pilih Akun Peran (1-Klik)</span>
        <div class="role-pills-grid" role="group" aria-label="Pilihan peran">
            <button type="button" class="role-pill-btn is-active" data-email="admin@simasadi.local" data-pass="password">
                <div class="role-pill-top">
                    <span class="role-pill-name">Ketua Tim</span>
                    <span class="badge-role badge-role-admin">Ketua Tim</span>
                </div>
                <span class="role-pill-desc">Akses penuh koordinasi tim & supervisi unit</span>
            </button>
            <button type="button" class="role-pill-btn" data-email="pic@simasadi.local" data-pass="password">
                <div class="role-pill-top">
                    <span class="role-pill-name">PIC Laboratorium</span>
                    <span class="badge-role badge-role-pic">PIC Lab</span>
                </div>
                <span class="role-pill-desc">Akses monitoring & jadwal lab</span>
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="form-stack" id="login-form">
        @csrf
        <label>
            Email
            <input type="email" name="email" id="login-email-input" value="{{ old('email', 'admin@simasadi.local') }}" required autofocus>
        </label>
        <label>
            Password
            <div class="password-input-wrap">
                <input type="password" name="password" id="login-password-input" value="password" required>
                <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                    <span class="eye-show" aria-hidden="true"><x-icon name="eye" size="16" /></span>
                    <span class="eye-hide" aria-hidden="true" style="display: none;"><x-icon name="eye-off" size="16" /></span>
                </button>
            </div>
        </label>
        <label class="check" for="remember">
            <input type="checkbox" id="remember" name="remember"> Ingat sesi ini
        </label>

        @if(config('services.recaptcha.enabled'))
            <div class="login-recaptcha-box" style="margin: 14px 0 8px 0; display: flex; flex-direction: column; align-items: center; width: 100%;">
                <div class="g-recaptcha" id="g-recaptcha-widget" data-sitekey="{{ config('services.recaptcha.site_key') }}" data-theme="light"></div>
                @error('g-recaptcha-response')
                    <span class="field-error" style="color: #e11d48; font-size: 12.5px; font-weight: 500; margin-top: 6px; text-align: center; display: block;" role="alert">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        @endif

        <button class="button primary login-submit-btn" type="submit" id="login-submit-btn">
            <span class="btn-text">Masuk ke Sistem</span>
            <span class="btn-spinner" aria-hidden="true">
                <x-icon name="loader" size="18" />
            </span>
        </button>
    </form>
    <p class="hint">Kata sandi standar seluruh akun: <code>password</code></p>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const emailInput = document.getElementById('login-email-input');
        const passInput = document.getElementById('login-password-input');
        const roleBtns = document.querySelectorAll('.role-pill-btn');

        roleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                roleBtns.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                if (emailInput) {
                    emailInput.value = btn.getAttribute('data-email') || '';
                    emailInput.focus();
                }
                if (passInput) {
                    passInput.value = btn.getAttribute('data-pass') || '';
                }
            });
        });

        // Set dynamic theme (light / dark) for reCAPTCHA before widget render
        const recaptchaWidget = document.getElementById('g-recaptcha-widget');
        if (recaptchaWidget) {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            recaptchaWidget.setAttribute('data-theme', currentTheme === 'dark' ? 'dark' : 'light');
        }
    });
</script>
</body>
</html>
