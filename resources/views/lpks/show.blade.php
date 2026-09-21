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
                <dt>Ruang Lingkup</dt>
                <dd>
                    @if($lpk->scope)
                        <strong>{{ $lpk->scope }}</strong>
                    @else
                        <span style="color: var(--muted);">Belum diisi</span>
                    @endif
                </dd>
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
                <dt>Masa berlaku akreditasi (Expired)</dt>
                <dd>
                    @if($lpk->expired_at)
                        <strong>{{ $lpk->expired_at->format('d M Y') }}</strong>
                        @if($lpk->isExpired())
                            <span class="status status-danger" style="margin-left: 6px; font-size: 11px; vertical-align: middle;">Kedaluwarsa</span>
                        @elseif($lpk->isExpiringSoon())
                            <span class="status status-warn" style="margin-left: 6px; font-size: 11px; vertical-align: middle;">Mendekati Expired</span>
                        @else
                            <span class="status status-completed" style="margin-left: 6px; font-size: 11px; vertical-align: middle;">Aktif</span>
                        @endif
                    @else
                        <span style="color: var(--muted);">Belum ditentukan</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Berkas Akreditasi (Sertifikat, Amandemen & Lampiran)</dt>
                <dd>
                    @if($lpk->drive_url)
                        <a href="{{ $lpk->drive_url }}" target="_blank" rel="noopener noreferrer" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 4px 12px; min-height: 32px;">
                            <x-icon name="sheets" size="14" />
                            <span>Buka Berkas di Google Drive &rarr;</span>
                        </a>
                    @else
                        <span style="color: var(--muted);">Belum ada tautan berkas</span>
                    @endif
                </dd>
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
