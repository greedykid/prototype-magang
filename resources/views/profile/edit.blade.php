@extends('layouts.app')

@section('title', 'Profil & Kata Sandi | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">Kembali ke Dasbor</a>
        <h1>Profil &amp; Kata Sandi</h1>
        <p class="lede">Kelola data identitas pengguna dan perbarui kata sandi akses akun SIMASADI Anda.</p>
    </div>
</div>

<div class="profile-layout-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; align-items: start;">
    {{-- Panel Informasi Akun --}}
    <section class="panel">
        <div class="panel-header" style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--line, #e2e8f0);">
            <div class="user-avatar" style="width: 48px; height: 48px; font-size: 16px; flex: 0 0 48px;" aria-hidden="true">
                {{ $user->initials }}
            </div>
            <div>
                <h2 style="font-size: 16px; font-weight: 700; margin: 0; color: #1e293b;">Data Pengguna</h2>
                <div style="margin-top: 4px;">
                    <span class="badge-role {{ $user->role_badge_class }}">
                        {{ $user->role_label }}
                    </span>
                </div>
            </div>
        </div>

        @if($errors->hasBag('default') && ($errors->has('name') || $errors->has('email')))
            <div class="alert danger" style="margin-bottom: 16px; padding: 12px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 13px;">
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

        <form method="POST" action="{{ route('profile.update') }}" class="form-grid">
            @csrf
            @method('PUT')

            <label class="full">
                Nama Lengkap
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Nama resmi yang ditampilkan pada sistem dan riwayat aktivitas.</small>
            </label>

            <label class="full">
                Alamat Email
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Alamat email aktif yang digunakan untuk masuk ke sistem.</small>
            </label>

            <label class="full">
                Peran Pengguna (Hak Akses)
                <input type="text" value="{{ $user->role_label }}" disabled readonly style="background: #f8fafc; color: #64748b; cursor: not-allowed;">
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Hak akses ditetapkan oleh Administrator Unit Akreditasi Laboratorium KAN.</small>
            </label>

            <div class="form-actions full" style="margin-top: 8px; display: flex; justify-content: flex-end;">
                <button type="submit" class="button primary">
                    <x-icon name="check" size="16" />
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </section>

    {{-- Panel Ganti Kata Sandi --}}
    <section class="panel">
        <div class="panel-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--line, #e2e8f0);">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: #e0e7ff; color: #3730a3; display: inline-flex; align-items: center; justify-content: center;">
                <x-icon name="key" size="18" />
            </div>
            <div>
                <h2 style="font-size: 16px; font-weight: 700; margin: 0; color: #1e293b;">Keamanan &amp; Kata Sandi</h2>
                <small style="color: var(--muted); font-size: 12px;">Perbarui kata sandi akun secara berkala.</small>
            </div>
        </div>

        @if($errors->has('current_password') || $errors->has('password'))
            <div class="alert danger" style="margin-bottom: 16px; padding: 12px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 13px;">
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

        <form method="POST" action="{{ route('profile.password.update') }}" class="form-grid">
            @csrf
            @method('PUT')

            <label class="full">
                Kata Sandi Saat Ini
                <input type="password" name="current_password" required autocomplete="current-password" placeholder="Masukkan kata sandi lama Anda">
            </label>

            <label class="full">
                Kata Sandi Baru
                <input type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter">
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Gunakan kombinasi huruf, angka, dan simbol untuk keamanan maksimal.</small>
            </label>

            <label class="full">
                Konfirmasi Kata Sandi Baru
                <input type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi baru">
            </label>

            <div class="form-actions full" style="margin-top: 8px; display: flex; justify-content: flex-end;">
                <button type="submit" class="button primary">
                    <x-icon name="shield" size="16" />
                    <span>Perbarui Kata Sandi</span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
