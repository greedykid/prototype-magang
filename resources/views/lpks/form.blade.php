@extends('layouts.app')

@section('title', $formTitle . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}">Kembali ke daftar LPK</a>
        <h1>{{ $formTitle }}</h1>
        <p class="lede">Gunakan data contoh terlebih dahulu saat mempelajari alur.</p>
    </div>
</div>

<form class="panel form-grid" method="POST" action="{{ $lpk->exists ? route('lpks.update', $lpk) : route('lpks.store') }}">
    @csrf
    @if($lpk->exists) @method('PUT') @endif
    <label>
        Nomor registrasi
        <input name="registration_number" value="{{ old('registration_number', $lpk->registration_number) }}" required>
    </label>
    <label>
        Nama LPK
        <input name="name" value="{{ old('name', $lpk->name) }}" required>
    </label>
    <label>
        Email
        <input type="email" name="email" value="{{ old('email', $lpk->email) }}">
    </label>
    <label>
        Telepon
        <input name="phone" value="{{ old('phone', $lpk->phone) }}">
    </label>
    <label>
        Status
        <select name="status">
            <option value="ACTIVE" @selected(old('status', $lpk->status ?: 'ACTIVE') === 'ACTIVE')>ACTIVE</option>
            <option value="INACTIVE" @selected(old('status', $lpk->status) === 'INACTIVE')>INACTIVE</option>
        </select>
    </label>
    <label class="full">
        Alamat
        <textarea name="address" rows="3">{{ old('address', $lpk->address) }}</textarea>
    </label>
    <label>
        Masa berlaku akreditasi (Expired)
        <input type="date" name="expired_at" value="{{ old('expired_at', $lpk->expired_at?->format('Y-m-d')) }}">
    </label>
    <label class="full">
        Link Google Drive Sertifikat Akreditasi
        <input type="url" name="certificate_drive_url" value="{{ old('certificate_drive_url', $lpk->certificate_drive_url) }}" placeholder="https://drive.google.com/... (Tautan berkas sertifikat akreditasi)">
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Tempelkan tautan Google Drive berkas PDF sertifikat akreditasi resmi.</small>
    </label>
    <label class="full">
        Link Google Drive Amandemen & Lampiran Sertifikat
        <input type="url" name="amendment_drive_url" value="{{ old('amendment_drive_url', $lpk->amendment_drive_url) }}" placeholder="https://drive.google.com/... (Tautan berkas amandemen atau folder berkas)">
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Tempelkan tautan Google Drive amandemen lampiran ruang lingkup sertifikat.</small>
    </label>
    <div class="form-actions full">
        <a class="button ghost" href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$lpk->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $lpk->exists ? 'Simpan perubahan' : 'Simpan data LPK' }}</span>
        </button>
    </div>
</form>

@if($lpk->exists && auth()->user()?->hasRole(['admin', 'staf']))
    <div class="panel" style="margin-top: 24px; border: 1px solid #feb2b2; background: #fff5f5; border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <strong style="color: #9b2c2c; font-size: 14.5px; display: block;">Zona Bahaya: Hapus Data Lembaga (LPK)</strong>
            <p style="margin: 4px 0 0; color: #742a2a; font-size: 13px;">Menghapus lembaga ini akan menghapus seluruh data proses akreditasi, agenda asesmen, dan rekam jejak terkait.</p>
        </div>
        <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data LPK {{ addslashes($lpk->name) }} secara permanen?');" style="margin: 0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="button danger" style="background: #e53e3e; border-color: #c53030; color: #ffffff;">
                <x-icon name="trash" size="16" />
                <span>Hapus LPK ini</span>
            </button>
        </form>
    </div>
@endif
@endsection
