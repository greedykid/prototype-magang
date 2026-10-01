@extends('layouts.app')

@section('title', 'Profil & Kata Sandi | SIMASADI')

@section('content')
<div class="lpk-header-back-wrap" style="margin-bottom: 12px;">
    <a href="{{ route('dashboard') }}" class="lpk-back-btn">
        <x-icon name="chevron-left" size="14" />
        <span>Kembali ke Dasbor</span>
    </a>
</div>

<div class="page-heading">
    <div>
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
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Hak akses ditetapkan oleh Ketua Tim Akreditasi Laboratorium KAN.</small>
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

    {{-- Panel Tautan Akun Kolaborasi (Account Linking) --}}
    <section class="panel full" id="account-links" style="grid-column: 1 / -1; margin-top: 8px;">
        <div class="panel-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--line, #e2e8f0);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center;">
                    <x-icon name="link" size="18" />
                </div>
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; margin: 0; color: #1e293b;">Tautan Akun Kolaborasi (Viewer per Akun)</h2>
                    <small style="color: var(--muted); font-size: 12px;">Tautkan akun PIC lain sebagai viewer untuk memantau seluruh daftar LPK, asesmen, dan kalender kegiatan Anda.</small>
                </div>
            </div>
            <a href="{{ route('account-links.index') }}" class="button secondary" style="font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;">
                <span>Buka Layar Penuh</span>
                <x-icon name="chevron-right" size="13" />
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            {{-- Kolom 1: Akun Viewer yang Ditautkan --}}
            <div>
                <h3 style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between;">
                    <span>Akun Viewer yang Anda Tautkan</span>
                    <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px;">{{ $linkedViewers->count() }}</span>
                </h3>

                @if($availableUsers->isNotEmpty())
                    <form method="POST" action="{{ route('account-links.store') }}" style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                        @csrf
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <select name="viewer_id" required style="flex: 1; min-width: 160px; font-size: 12px; padding: 6px 10px; border: 1px solid var(--line); border-radius: 6px; background: #ffffff;">
                                <option value="">-- Pilih Akun PIC --</option>
                                @foreach($availableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="button primary" style="font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 4px;">
                                <x-icon name="link" size="13" />
                                <span>Tautkan</span>
                            </button>
                        </div>
                    </form>
                @endif

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @forelse($linkedViewers as $viewer)
                        <div style="background: #ffffff; border: 1px solid var(--line); border-radius: 6px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                            <div style="min-width: 0;">
                                <strong style="font-size: 13px; color: #1e293b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $viewer->name }}</strong>
                                <small style="font-size: 11px; color: var(--muted); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $viewer->email }}</small>
                            </div>
                            <form method="POST" action="{{ route('account-links.destroy', $viewer) }}"
                                  data-confirm-delete
                                  data-confirm-title="Putuskan Akses Viewer?"
                                  data-confirm-text="Apakah Anda yakin ingin memutuskan akses viewer untuk akun {{ $viewer->name }}? Akun ini tidak akan dapat lagi memantau LPK, asesmen, dan kalender kegiatan Anda."
                                  data-confirm-btn="Ya, Putuskan Akses"
                                  style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button-icon-only text-danger" style="background: none; border: none; padding: 4px; cursor: pointer; color: #ef4444; display: inline-flex; align-items: center;" title="Putuskan tautan">
                                    <x-icon name="trash" size="14" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <div style="padding: 14px; text-align: center; background: #f8fafc; border: 1px dashed var(--line); border-radius: 6px; font-size: 12px; color: var(--muted);">
                            Belum ada akun viewer yang ditautkan ke akun Anda.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Kolom 2: Akses Viewer yang Diterima --}}
            <div>
                <h3 style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between;">
                    <span>Akses Viewer yang Diterima</span>
                    <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px;">{{ $linkedOwners->count() }}</span>
                </h3>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @forelse($linkedOwners as $owner)
                        <div style="background: #ffffff; border: 1px solid var(--line); border-radius: 6px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                            <div style="min-width: 0;">
                                <strong style="font-size: 13px; color: #1e293b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $owner->name }}</strong>
                                <small style="font-size: 11px; color: var(--muted); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $owner->email }} &bull; {{ $owner->lpks_count }} LPK</small>
                            </div>
                            <form method="POST" action="{{ route('account-links.destroy', $owner) }}"
                                  data-confirm-delete
                                  data-confirm-title="Lepaskan Akses Pemantauan?"
                                  data-confirm-text="Apakah Anda ingin melepaskan akses pemantauan ke akun {{ $owner->name }}? Seluruh LPK dan agenda dari akun ini tidak akan lagi tampil di halaman Anda."
                                  data-confirm-btn="Ya, Lepas Akses"
                                  style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button secondary" style="font-size: 11px; padding: 3px 8px;" title="Lepaskan akses">
                                    Lepas
                                </button>
                            </form>
                        </div>
                    @empty
                        <div style="padding: 14px; text-align: center; background: #f8fafc; border: 1px dashed var(--line); border-radius: 6px; font-size: 12px; color: var(--muted);">
                            Belum ada akun lain yang menautkan Anda sebagai viewer.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
