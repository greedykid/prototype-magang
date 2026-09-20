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
    <div class="brand login-brand">
        <img src="{{ asset('images/logo-bsn.png') }}" alt="Logo BSN" class="login-brand-logo">
        <div class="login-brand-text">
            <strong>SIMASADI</strong>
            <small>Badan Standardisasi Nasional</small>
        </div>
    </div>
    <h1>Masuk untuk melanjutkan.</h1>
    <p class="lede">Pilih peran akun di bawah atau masukkan kredensial secara manual.</p>

    @if($errors->any())
        <div id="flash-errors-data" data-errors='@json($errors->all())' data-title="Gagal Masuk" style="display: none;"></div>
    @endif

    <div class="role-selector-wrap" aria-label="Pemilih peran cepat">
        <span class="role-selector-title">Pilih Akun Peran (1-Klik)</span>
        <div class="role-pills-grid" role="group" aria-label="Pilihan peran">
            <button type="button" class="role-pill-btn" data-email="admin@kanmis.local" data-pass="password">
                <div class="role-pill-top">
                    <span class="role-pill-name">Administrator</span>
                    <span class="badge-role badge-role-admin">Admin</span>
                </div>
                <span class="role-pill-desc">Akses penuh & server</span>
            </button>
            <button type="button" class="role-pill-btn" data-email="staf@kanmis.local" data-pass="password">
                <div class="role-pill-top">
                    <span class="role-pill-name">Staf Administrasi</span>
                    <span class="badge-role badge-role-staff">Staf</span>
                </div>
                <span class="role-pill-desc">LPK, Billing, SBM</span>
            </button>
            <button type="button" class="role-pill-btn" data-email="asesor@kanmis.local" data-pass="password">
                <div class="role-pill-top">
                    <span class="role-pill-name">Asesor / Auditor</span>
                    <span class="badge-role badge-role-assessor">Asesor</span>
                </div>
                <span class="role-pill-desc">Asesmen & Biaya SBM</span>
            </button>
            <button type="button" class="role-pill-btn is-active" data-email="demo@kanmis.local" data-pass="password">
                <div class="role-pill-top">
                    <span class="role-pill-name">Akun Demo</span>
                    <span class="badge-role badge-role-default">All</span>
                </div>
                <span class="role-pill-desc">Akses lengkap eksplorasi</span>
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="form-stack" id="login-form">
        @csrf
        <label>
            Email
            <input type="email" name="email" id="login-email-input" value="{{ old('email', 'demo@kanmis.local') }}" required autofocus>
        </label>
        <label>
            Password
            <input type="password" name="password" id="login-password-input" value="password" required>
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
    });
</script>
</body>
</html>
