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
<div class="auth-split-layout">
    {{-- KOLOM KIRI: Brand Hero Showcase (Royal Blue) --}}
    <aside class="auth-hero-pane">
        <div class="auth-hero-brand">
            <div class="auth-brand-badge" aria-label="Logo BSN">
                <img src="{{ asset('images/logo-bsn.png') }}" alt="Logo BSN" class="auth-brand-logo" width="86" height="36">
            </div>
            <div class="auth-brand-title-group">
                <span class="auth-brand-app-name">SIMASADI</span>
                <span class="auth-brand-dept-name">Direktorat Akreditasi Laboratorium &bull; BSN / KAN</span>
            </div>
        </div>

        <div class="auth-hero-content">
            <h1 class="auth-hero-heading">
                Satu platform pemantauan siklus pengawasan &amp; asesmen akreditasi laboratorium.
            </h1>
            <p class="auth-hero-description">
                Terintegrasi dengan regulasi KAN U-01 untuk memastikan ketepatan jadwal surveilen, tindak lanjut perbaikan (TP), dan legalitas status akreditasi dalam satu alur terkoordinasi.
            </p>

            <ul class="auth-hero-checklist" role="list">
                <li class="auth-checklist-item">
                    <span class="auth-check-icon" aria-hidden="true">
                        <x-icon name="check" size="14" />
                    </span>
                    <span>Otomatisasi siklus Surveilen 1, Surveilen 2, &amp; Re-akreditasi KAN</span>
                </li>
                <li class="auth-checklist-item">
                    <span class="auth-check-icon" aria-hidden="true">
                        <x-icon name="check" size="14" />
                    </span>
                    <span>Pelacakan tenggat waktu (SLA) &amp; verifikasi Tindakan Perbaikan (TP)</span>
                </li>
                <li class="auth-checklist-item">
                    <span class="auth-check-icon" aria-hidden="true">
                        <x-icon name="check" size="14" />
                    </span>
                    <span>Peringatan dini masa toleransi untuk mencegah pembekuan akreditasi</span>
                </li>
            </ul>
        </div>

        <div class="auth-hero-footer">
            <div class="auth-helpdesk-label">Butuh bantuan akses akun?</div>
            <div class="auth-helpdesk-contact">
                <a href="mailto:laboratorium@bsn.go.id?subject=Bantuan%20Akses%20SIMASADI" class="auth-helpdesk-link">laboratorium@bsn.go.id</a>
                <span class="auth-helpdesk-sep">&bull;</span>
                <span class="auth-helpdesk-phone">(021) 392 7422 ext. Akreditasi</span>
            </div>
        </div>
    </aside>

    {{-- KOLOM KANAN: Formulir Masuk (Clean Form) --}}
    <main class="auth-form-pane">
        <div class="auth-form-topbar">
            <button type="button" id="theme-toggle-btn" class="auth-theme-btn" aria-label="Beralih mode tampilan" title="Beralih mode tampilan">
                <span class="auth-theme-icon-sun" aria-hidden="true"><x-icon name="sun" size="18" /></span>
                <span class="auth-theme-icon-moon" aria-hidden="true"><x-icon name="moon" size="18" /></span>
            </button>
        </div>

        <div class="auth-form-card" id="login-card">
            <div class="auth-form-header">
                <h2 class="auth-form-title">Masuk ke SIMASADI</h2>
                <p class="auth-form-subtitle">Gunakan akun unit internal Direktorat Akreditasi Laboratorium.</p>
            </div>

            @if($errors->any())
                <div class="auth-error-alert" role="alert">
                    <x-icon name="alert-circle" size="16" class="auth-error-icon" />
                    <div class="auth-error-text">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
                <div id="flash-errors-data" data-errors='@json($errors->all())' data-title="Gagal Masuk" style="display: none;"></div>
            @endif

            {{-- Segmented Role Switcher ("Masuk sebagai") --}}
            <div class="auth-role-segmented-box" aria-label="Pemilih peran cepat">
                <span class="auth-field-label">Masuk sebagai</span>
                <div class="auth-segmented-control" role="group" aria-label="Pilihan peran">
                    <button type="button" class="role-pill-btn auth-segmented-btn is-active" data-email="admin@simasadi.local" data-pass="password">
                        <x-icon name="user" size="15" />
                        <span class="role-pill-name">Ketua Tim</span>
                    </button>
                    <button type="button" class="role-pill-btn auth-segmented-btn" data-email="pic@simasadi.local" data-pass="password">
                        <x-icon name="flask" size="15" />
                        <span class="role-pill-name">PIC Laboratorium</span>
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="auth-form-stack" id="login-form">
                @csrf
                <div class="auth-field-group">
                    <label for="login-email-input" class="auth-field-label">Email</label>
                    <div class="auth-input-wrapper">
                        <span class="auth-input-leading-icon" aria-hidden="true">
                            <x-icon name="mail" size="16" />
                        </span>
                        <input type="email"
                               name="email"
                               id="login-email-input"
                               value="{{ old('email', 'admin@simasadi.local') }}"
                               required
                               autofocus
                               placeholder="nama@bsn.go.id"
                               class="auth-text-input">
                    </div>
                </div>

                <div class="auth-field-group">
                    <div class="auth-password-label-row">
                        <label for="login-password-input" class="auth-field-label">Password</label>
                        <button type="button" class="auth-forgot-link" onclick="window.openModal('modal-forgot-password')">
                            Lupa kata sandi?
                        </button>
                    </div>
                    <div class="auth-input-wrapper password-input-wrap">
                        <span class="auth-input-leading-icon" aria-hidden="true">
                            <x-icon name="lock" size="16" />
                        </span>
                        <input type="password"
                               name="password"
                               id="login-password-input"
                               value="password"
                               required
                               placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               class="auth-text-input">
                        <button type="button" class="password-toggle-btn auth-input-trailing-btn" data-target="login-password-input" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                            <span class="eye-show" aria-hidden="true"><x-icon name="eye" size="16" /></span>
                            <span class="eye-hide" aria-hidden="true" style="display: none;"><x-icon name="eye-off" size="16" /></span>
                        </button>
                    </div>
                </div>

                <div class="auth-field-row">
                    <label class="auth-checkbox-label" for="remember">
                        <input type="checkbox" id="remember" name="remember" class="auth-checkbox">
                        <span>Ingat sesi ini</span>
                    </label>
                </div>

                @if(config('services.recaptcha.enabled'))
                    <div class="login-recaptcha-box" style="margin: 10px 0 6px 0; display: flex; flex-direction: column; align-items: center; width: 100%;">
                        <div class="g-recaptcha" id="g-recaptcha-widget" data-sitekey="{{ config('services.recaptcha.site_key') }}" data-theme="light"></div>
                        @error('g-recaptcha-response')
                            <span class="field-error" style="color: var(--danger-text, #ef4444); font-size: 12px; font-weight: 500; margin-top: 6px; text-align: center; display: block;" role="alert">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                @endif

                <button class="button primary auth-submit-btn login-submit-btn" type="submit" id="login-submit-btn">
                    <span class="btn-text">Masuk ke Sistem</span>
                    <x-icon name="arrow-right" size="16" class="btn-arrow-icon" />
                    <span class="btn-spinner" aria-hidden="true" style="display: none;">
                        <x-icon name="loader" size="18" />
                    </span>
                </button>
            </form>

            <div class="auth-form-footer">
                <p class="auth-footer-help">
                    Belum punya akses? <button type="button" class="auth-link-button" onclick="window.openModal('modal-forgot-password')">Hubungi admin unit</button>
                </p>
                <p class="auth-footer-copy">
                    &copy; 2026 Badan Standardisasi Nasional &bull; Kebijakan Privasi
                </p>
            </div>
        </div>
    </main>
</div>

{{-- Modal Bantuan Akun & Lupa Kata Sandi --}}
<div class="simasadi-modal" id="modal-forgot-password" role="dialog" aria-modal="true" aria-labelledby="modal-forgot-title">
    <div class="simasadi-modal-backdrop" onclick="window.closeModal('modal-forgot-password')"></div>
    <div class="simasadi-modal-box" style="max-width: 480px; width: 100%;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge" style="background: rgba(0, 117, 222, 0.1); color: #0075de;">
                    <x-icon name="key" size="18" />
                </div>
                <div>
                    <h4 id="modal-forgot-title" style="margin: 0; font-size: 16px; font-weight: 700;">Bantuan Akun &amp; Kata Sandi</h4>
                    <p style="margin: 2px 0 0; font-size: 12px; color: var(--muted);">Direktorat Akreditasi Laboratorium &bull; BSN / KAN</p>
                </div>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-forgot-password')" aria-label="Tutup modal">&times;</button>
        </div>
        <div class="simasadi-modal-body" style="padding: 20px 24px; font-size: 13.5px; line-height: 1.55; color: var(--ink);">
            <p style="margin: 0 0 12px 0;">
                Pengaturan ulang kata sandi atau permohonan akses akun baru dikelola langsung oleh administrator unit internal Direktorat Akreditasi Laboratorium BSN.
            </p>
            <div style="background: var(--surface-subtle); border: 1px solid var(--line); border-radius: 8px; padding: 14px 16px; margin-bottom: 14px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-bottom: 8px;">Kontak Layanan Bantuan</div>
                <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13px;">
                    <div>Email: <strong><a href="mailto:laboratorium@bsn.go.id?subject=Permohonan%20Reset%20Akun%20SIMASADI" style="color: #0075de; text-decoration: none;">laboratorium@bsn.go.id</a></strong></div>
                    <div>Telepon: <strong>(021) 392 7422 ext. Akreditasi</strong></div>
                    <div>Jam Operasional: <strong>Senin &ndash; Jumat, 08.00 &ndash; 16.00 WIB</strong></div>
                </div>
            </div>
            <div style="font-size: 12px; color: var(--muted); line-height: 1.45;">
                Untuk pengujian prototipe, Anda dapat menggunakan peran <strong>Ketua Tim</strong> atau <strong>PIC Laboratorium</strong> dengan kata sandi bawaan <code>password</code>.
            </div>
        </div>
        <div class="simasadi-modal-foot" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; border-top: 1px solid var(--line); background: var(--surface-subtle);">
            <a href="mailto:laboratorium@bsn.go.id?subject=Permohonan%20Reset%20Akun%20SIMASADI" class="button primary button-sm" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <x-icon name="mail" size="14" />
                <span>Kirim Permohonan Email</span>
            </a>
            <button type="button" class="button ghost button-sm" data-modal-close onclick="window.closeModal('modal-forgot-password')">Tutup</button>
        </div>
    </div>
</div>

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

        // Set dynamic theme for reCAPTCHA before widget render
        const recaptchaWidget = document.getElementById('g-recaptcha-widget');
        if (recaptchaWidget) {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            recaptchaWidget.setAttribute('data-theme', currentTheme === 'dark' ? 'dark' : 'light');
        }
    });
</script>
</body>
</html>
