@extends('layouts.app')

@section('title', $formTitle . ' | SIMASADI')

@section('content')
<div class="lpk-header-back-wrap">
    <a href="{{ route('users.index') }}" class="lpk-back-btn" title="Kembali ke Manajemen Anggota">
        <x-icon name="chevron-left" size="16" />
        <span>Manajemen Anggota</span>
    </a>
</div>

<div class="page-heading">
    <div>
        <h1>{{ $formTitle }}</h1>
        <p class="lede">
            @if($user->exists)
                Perbarui informasi akun, hak akses peran, atau atur ulang kata sandi pengguna.
            @else
                Daftarkan akun pengguna baru untuk staf Ketua Tim atau PIC Laboratorium.
            @endif
        </p>
    </div>
</div>

<section class="panel" style="max-width: 680px;">
    @if($errors->any())
        <div class="alert danger" style="margin-bottom: 20px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 13px;">
            <strong style="display: block; margin-bottom: 6px;">Terdapat kesalahan pada formulir:</strong>
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="form-grid">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <label class="full">
            Nama Lengkap
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" placeholder="Contoh: Ahmad Hidayat, S.T.">
            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Nama resmi personel atau perwakilan laboratorium.</small>
        </label>

        <label class="full">
            Alamat Email
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email" placeholder="Contoh: ahmad@lab-penguji.co.id">
            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Digunakan sebagai identitas masuk (login) ke aplikasi.</small>
        </label>

        <label class="full">
            Peran &amp; Hak Akses Pengguna
            @php
                $adminExists = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)
                    ->when($user->exists, fn ($q) => $q->where('id', '!=', $user->id))
                    ->exists();
            @endphp
            <select name="role" required>
                @if(!$adminExists)
                    <option value="admin" @selected(old('role', $user->role ?: 'admin') === 'admin')>
                        Ketua Tim (Dit. Akreditasi Laboratorium KAN)
                    </option>
                @else
                    <option value="admin" disabled style="color: var(--muted);">
                        Ketua Tim (Maksimal 1 akun di sistem: sudah terisi)
                    </option>
                @endif
                <option value="pic" @selected(old('role', $user->role ?: ($adminExists ? 'pic' : 'admin')) === 'pic')>
                    PIC Laboratorium (Laboratorium Penguji / Kalibrasi / Medik)
                </option>
            </select>
            @if($user->exists && auth()->id() === $user->id)
                <small style="color: #b91c1c; font-size: 11.5px; display: block; margin-top: 4px;">
                    Catatan: Anda tidak dapat mengubah peran akun Anda sendiri untuk menghindari terkunci dari hak akses Ketua Tim.
                </small>
            @else
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">
                    Pilih peran sesuai tanggung jawab penugasan pengguna di sistem SIMASADI.
                </small>
            @endif
        </label>

        <label class="full">
            Kata Sandi {{ $user->exists ? '(Opsional)' : '' }}
            <div class="password-input-wrap">
                <input type="password" name="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" placeholder="{{ $user->exists ? 'Biarkan kosong jika tidak ingin mengubah kata sandi' : 'Minimal 8 karakter' }}">
                <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                    <span class="eye-show" aria-hidden="true"><x-icon name="eye" size="16" /></span>
                    <span class="eye-hide" aria-hidden="true" style="display: none;"><x-icon name="eye-off" size="16" /></span>
                </button>
            </div>
            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">
                {{ $user->exists ? 'Isi bidang ini hanya jika ingin mengatur ulang (reset) kata sandi pengguna.' : 'Kata sandi awal untuk masuk ke akun. Minimal 8 karakter.' }}
            </small>
        </label>

        <label class="full">
            Konfirmasi Kata Sandi {{ $user->exists ? '(Opsional)' : '' }}
            <div class="password-input-wrap">
                <input type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" placeholder="Ulangi kata sandi">
                <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                    <span class="eye-show" aria-hidden="true"><x-icon name="eye" size="16" /></span>
                    <span class="eye-hide" aria-hidden="true" style="display: none;"><x-icon name="eye-off" size="16" /></span>
                </button>
            </div>
        </label>

        <div class="form-actions full" style="margin-top: 12px; display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('users.index') }}" class="button ghost">Batal</a>
            <button type="submit" class="button primary">
                <x-icon name="check" size="16" />
                <span>{{ $user->exists ? 'Perbarui Pengguna' : 'Simpan Pengguna Baru' }}</span>
            </button>
        </div>
    </form>
</section>
@endsection
