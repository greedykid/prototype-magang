@extends('layouts.app')

@section('title', $lpk->name . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('lpks.index') }}">Semua LPK</a>
        <h1>{{ $lpk->name }}</h1>
        <p class="lede">{{ $lpk->registration_number }} &middot; data contoh lokal</p>
    </div>
    @if(auth()->user()?->hasRole(['admin', 'staf']))
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a class="button secondary" href="{{ route('lpks.edit', $lpk) }}">
                <x-icon name="edit" size="16" />
                <span>Ubah data</span>
            </a>
            <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data LPK {{ addslashes($lpk->name) }} ({{ $lpk->registration_number }})? Seluruh data proses terkait akan ikut terhapus.');" style="margin: 0; display: inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="button danger" style="background: #e53e3e; border-color: #c53030; color: #ffffff;">
                    <x-icon name="trash" size="16" />
                    <span>Hapus LPK</span>
                </button>
            </form>
        </div>
    @endif
</div>

<div class="detail-grid">
    <section class="panel">
        <span class="eyebrow">INFORMASI DASAR</span>
        <dl class="detail-list">
            <div>
                <dt>Status</dt>
                <dd><x-status :value="$lpk->status" /></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd>{{ $lpk->email ?: 'Belum diisi' }}</dd>
            </div>
            <div>
                <dt>Telepon</dt>
                <dd>{{ $lpk->phone ?: 'Belum diisi' }}</dd>
            </div>
            <div>
                <dt>Alamat</dt>
                <dd>{{ $lpk->address ?: 'Belum diisi' }}</dd>
            </div>
            <div>
                <dt>Catatan</dt>
                <dd>{{ $lpk->notes ?: 'Belum ada catatan.' }}</dd>
            </div>
        </dl>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <span class="eyebrow">AKREDITASI</span>
                <h2>Proses terkait</h2>
            </div>
            <a href="{{ route('accreditations.index') }}">Semua proses</a>
        </div>

        @forelse($lpk->accreditations as $item)
            <a class="list-row" href="{{ route('accreditations.show', $item) }}">
                <div>
                    <strong>Proses akreditasi #{{ $item->id }}</strong>
                    <span>Target {{ $item->target_date?->format('d M Y') ?: 'Belum ditentukan' }}</span>
                </div>
                <x-status :value="$item->status" />
            </a>
        @empty
            <div class="empty">Belum ada proses akreditasi.</div>
        @endforelse
    </section>
</div>
@endsection
