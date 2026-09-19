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
    <label class="full">
        Catatan
        <textarea name="notes" rows="4">{{ old('notes', $lpk->notes) }}</textarea>
    </label>
    <div class="form-actions full">
        <a class="button ghost" href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$lpk->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $lpk->exists ? 'Simpan perubahan' : 'Simpan data LPK' }}</span>
        </button>
    </div>
</form>
@endsection
