@extends('layouts.app')

@section('title', 'Profil & Kata Sandi | SIMASADI')

@section('content')
<div class="profile-container">
    {{-- Navigasi Breadcrumb Kembali (Notion Style) --}}
    <div class="lpk-header-back-wrap">
        <a href="{{ route('dashboard') }}" class="lpk-back-btn" title="Kembali ke Dasbor">
            <x-icon name="chevron-left" size="16" />
            <span>Dasbor</span>
        </a>
    </div>

    {{-- Banner Identitas Pengguna (Hero Card) --}}
    <section class="profile-hero-card" aria-label="Identitas Akun">
        <div class="profile-hero-content">
            <div class="profile-hero-avatar" aria-hidden="true">
                {{ $user->initials }}
            </div>
            <div class="profile-hero-details">
                <div class="profile-hero-name-row">
                    <h1>{{ $user->name }}</h1>
                    <span class="badge-role {{ $user->role_badge_class }}">
                        {{ $user->role_label }}
                    </span>
                </div>
                <div class="profile-hero-meta">
                    <span class="profile-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>{{ $user->email }}</span>
                    </span>
                    <span class="profile-meta-divider" aria-hidden="true">&bull;</span>
                    <span class="profile-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Terdaftar sejak {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : 'Sistem Bawaan' }}</span>
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- 2-Column Responsive Layout --}}
    <div class="profile-grid-main">
        {{-- KARTU 1: Informasi Profil Pengguna --}}
        <section class="profile-card" aria-labelledby="profile-info-heading">
            <div class="profile-card-header">
                <div class="profile-card-icon-wrap icon-blue" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="profile-card-header-text">
                    <h2 id="profile-info-heading">Informasi Profil</h2>
                    <p>Perbarui identitas diri dan alamat email resmi Anda.</p>
                </div>
            </div>

            @if($errors->hasBag('default') && ($errors->has('name') || $errors->has('email')))
                <div class="alert danger" style="margin-bottom: 18px;">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach($errors->get('name') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        @foreach($errors->get('email') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
                @csrf
                @method('PUT')

                <div class="profile-fields-wrap">
                    <div class="profile-field">
                        <label class="profile-field-label" for="profile-name">
                            <span>Nama Lengkap</span>
                            <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <input id="profile-name" type="text" name="name" class="profile-input" value="{{ old('name', $user->name) }}" required autocomplete="name" placeholder="Contoh: Ahmad Hidayat, S.T.">
                        <small class="profile-field-hint">Nama resmi yang ditampilkan pada sistem dan riwayat penugasan.</small>
                    </div>

                    <div class="profile-field">
                        <label class="profile-field-label" for="profile-email">
                            <span>Alamat Email</span>
                            <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <input id="profile-email" type="email" name="email" class="profile-input" value="{{ old('email', $user->email) }}" required autocomplete="email" placeholder="Contoh: nama@lab.co.id">
                        <small class="profile-field-hint">Digunakan untuk notifikasi dan autentikasi login SIMASADI.</small>
                    </div>

                    <div class="profile-field">
                        <label class="profile-field-label">
                            <span>Peran &amp; Hak Akses Pengguna</span>
                        </label>
                        <div class="profile-role-display">
                            <div class="profile-role-info">
                                <span class="badge-role {{ $user->role_badge_class }}">
                                    {{ $user->role_label }}
                                </span>
                            </div>
                            <span class="profile-role-lock-tag" title="Hak akses ditetapkan oleh Ketua Tim Akreditasi KAN">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <span>Dikelola Admin</span>
                            </span>
                        </div>
                        <small class="profile-field-hint">Hak akses operasional Anda dikelola langsung oleh Ketua Tim Akreditasi Laboratorium KAN.</small>
                    </div>
                </div>

                <div class="profile-form-actions">
                    <button type="submit" class="button primary">
                        <x-icon name="check" size="16" />
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </section>

        {{-- KARTU 2: Keamanan Akun & Ganti Kata Sandi --}}
        <section class="profile-card" aria-labelledby="profile-security-heading">
            <div class="profile-card-header">
                <div class="profile-card-icon-wrap icon-purple" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/>
                        <path d="m21 2-9.6 9.6"/>
                        <circle cx="7.5" cy="15.5" r="5.5"/>
                    </svg>
                </div>
                <div class="profile-card-header-text">
                    <h2 id="profile-security-heading">Keamanan &amp; Kata Sandi</h2>
                    <p>Perbarui kata sandi akun secara berkala untuk menjaga keamanan akses.</p>
                </div>
            </div>

            @if($errors->has('current_password') || $errors->has('password'))
                <div class="alert danger" style="margin-bottom: 18px;">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach($errors->get('current_password') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        @foreach($errors->get('password') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.password.update') }}" class="profile-form">
                @csrf
                @method('PUT')

                <div class="profile-fields-wrap">
                    <div class="profile-field">
                        <label class="profile-field-label" for="current-password">
                            <span>Kata Sandi Saat Ini</span>
                            <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <div class="password-input-wrap">
                            <input id="current-password" type="password" name="current_password" class="profile-input" required autocomplete="current-password" placeholder="Masukkan kata sandi lama Anda">
                            <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                                <span class="eye-show" aria-hidden="true"><x-icon name="eye" size="16" /></span>
                                <span class="eye-hide" aria-hidden="true" style="display: none;"><x-icon name="eye-off" size="16" /></span>
                            </button>
                        </div>
                    </div>

                    <div class="profile-field">
                        <label class="profile-field-label" for="new-password">
                            <span>Kata Sandi Baru</span>
                            <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <div class="password-input-wrap">
                            <input id="new-password" type="password" name="password" class="profile-input" required autocomplete="new-password" placeholder="Minimal 8 karakter">
                            <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                                <span class="eye-show" aria-hidden="true"><x-icon name="eye" size="16" /></span>
                                <span class="eye-hide" aria-hidden="true" style="display: none;"><x-icon name="eye-off" size="16" /></span>
                            </button>
                        </div>
                        <small class="profile-field-hint">Gunakan kombinasi minimal 8 karakter huruf, angka, dan simbol.</small>
                    </div>

                    <div class="profile-field">
                        <label class="profile-field-label" for="confirm-password">
                            <span>Konfirmasi Kata Sandi Baru</span>
                            <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <div class="password-input-wrap">
                            <input id="confirm-password" type="password" name="password_confirmation" class="profile-input" required autocomplete="new-password" placeholder="Ulangi kata sandi baru">
                            <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                                <span class="eye-show" aria-hidden="true"><x-icon name="eye" size="16" /></span>
                                <span class="eye-hide" aria-hidden="true" style="display: none;"><x-icon name="eye-off" size="16" /></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="profile-form-actions">
                    <button type="submit" class="button primary">
                        <x-icon name="shield" size="16" />
                        <span>Perbarui Kata Sandi</span>
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- KARTU 3: Tautan Akun Kolaborasi (Account Linking) --}}
    <section class="profile-links-card" id="account-links" aria-labelledby="profile-links-heading">
        <div class="profile-links-header">
            <div class="profile-links-header-left">
                <div class="profile-card-icon-wrap icon-emerald" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <div>
                    <h2 id="profile-links-heading" style="font-size: 16px; font-weight: 700; color: var(--ink); margin: 0 0 3px 0;">
                        Tautan Akun Kolaborasi (Viewer per Akun)
                    </h2>
                    <p style="font-size: 12.5px; color: var(--muted); margin: 0;">
                        Tautkan akun PIC lain sebagai viewer untuk memantau seluruh daftar LPK, asesmen, dan kalender kegiatan Anda tanpa berbagi kata sandi.
                    </p>
                </div>
            </div>
            <a href="{{ route('account-links.index') }}" class="button secondary" style="font-size: 12.5px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
                <span>Kelola Layar Penuh</span>
                <x-icon name="chevron-right" size="13" />
            </a>
        </div>

        <div class="profile-links-grid">
            {{-- Kolom 1: Akun Viewer yang Ditautkan (Pemantau Saya) --}}
            <div class="profile-links-column">
                <div class="profile-links-col-header">
                    <h3>Akun Viewer yang Anda Tautkan</h3>
                    <span class="profile-links-count-chip">{{ $linkedViewers->count() }}</span>
                </div>

                @if($availableUsers->isNotEmpty())
                    <form method="POST" action="{{ route('account-links.store') }}" class="profile-link-add-form">
                        @csrf
                        <select name="viewer_id" required class="profile-link-add-select">
                            <option value="">-- Pilih Akun PIC Rekan --</option>
                            @foreach($availableUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="button primary profile-link-add-btn">
                            <x-icon name="plus" size="13" />
                            <span>Tautkan</span>
                        </button>
                    </form>
                @endif

                <div class="profile-links-list">
                    @forelse($linkedViewers as $viewer)
                        <div class="profile-link-item">
                            <div class="profile-link-item-left">
                                <span class="profile-link-avatar" aria-hidden="true">{{ $viewer->initials }}</span>
                                <div class="profile-link-details">
                                    <strong class="profile-link-name">{{ $viewer->name }}</strong>
                                    <small class="profile-link-email">{{ $viewer->email }}</small>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('account-links.destroy', $viewer) }}"
                                  data-confirm-delete
                                  data-confirm-title="Putuskan Akses Viewer?"
                                  data-confirm-text="Apakah Anda yakin ingin memutuskan akses viewer untuk akun {{ $viewer->name }}? Akun ini tidak akan dapat lagi memantau LPK, asesmen, dan kalender kegiatan Anda."
                                  data-confirm-btn="Ya, Putuskan Akses"
                                  style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button-icon-only text-danger" style="background: none; border: none; padding: 6px; cursor: pointer; color: var(--danger-text); display: inline-flex; align-items: center; border-radius: var(--radius-xs, 6px);" title="Putuskan tautan">
                                    <x-icon name="trash" size="14" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="profile-empty-box">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <span>Belum ada akun viewer yang ditautkan ke akun Anda.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Kolom 2: Akses Viewer yang Diterima (Dipantau oleh Saya) --}}
            <div class="profile-links-column">
                <div class="profile-links-col-header">
                    <h3>Akses Viewer yang Diterima</h3>
                    <span class="profile-links-count-chip">{{ $linkedOwners->count() }}</span>
                </div>

                <div class="profile-links-list">
                    @forelse($linkedOwners as $owner)
                        <div class="profile-link-item">
                            <div class="profile-link-item-left">
                                <span class="profile-link-avatar" aria-hidden="true">{{ $owner->initials }}</span>
                                <div class="profile-link-details">
                                    <a href="{{ route('account-links.show', $owner) }}" style="color: var(--ink); text-decoration: none;">
                                        <strong class="profile-link-name">{{ $owner->name }}</strong>
                                    </a>
                                    <small class="profile-link-email">{{ $owner->email }} &bull; <a href="{{ route('account-links.show', $owner) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $owner->lpks_count }} LPK</a></small>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('account-links.destroy', $owner) }}"
                                  data-confirm-delete
                                  data-confirm-title="Lepaskan Akses Pemantauan?"
                                  data-confirm-text="Apakah Anda ingin melepaskan akses pemantauan ke akun {{ $owner->name }}? Seluruh LPK dan agenda dari akun ini tidak akan lagi tampil di halaman Anda."
                                  data-confirm-btn="Ya, Lepas Akses"
                                  style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="table-action-btn table-action-btn-danger" style="font-size: 11.5px; padding: 4px 10px; font-weight: 500;" title="Lepaskan akses pemantauan">
                                    Lepas
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="profile-empty-box">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <span>Belum ada akun lain yang menautkan Anda sebagai viewer.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
