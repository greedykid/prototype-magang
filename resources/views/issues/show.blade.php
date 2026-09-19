@extends('layouts.app')

@section('title', $issue->title . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('issues.index') }}">Semua masalah</a>
        <h1>{{ $issue->title }}</h1>
        <p class="lede">{{ $issue->lpk->name }} &bull; dibuat oleh {{ $issue->creator->name }}</p>
    </div>
    <x-status :value="$issue->status" />
</div>

<div class="detail-grid">
    <section class="panel">
        <span class="eyebrow">RINCIAN MASALAH</span>
        <dl class="detail-list">
            <div>
                <dt>Prioritas</dt>
                <dd>
                    <span class="priority priority-{{ strtolower($issue->priority) }}">
                        {{ ['LOW' => 'Rendah', 'MEDIUM' => 'Sedang', 'HIGH' => 'Tinggi'][$issue->priority] ?? $issue->priority }}
                    </span>
                </dd>
            </div>
            <div>
                <dt>Target</dt>
                <dd>{{ $issue->due_date?->format('d M Y') ?: 'Tanpa target' }}</dd>
            </div>
            <div>
                <dt>Deskripsi</dt>
                <dd>{{ $issue->description }}</dd>
            </div>
        </dl>
        <form method="POST" action="{{ route('issues.update', $issue) }}" class="inline-form">
            @csrf
            @method('PUT')
            <label>
                Status
                <select name="status">
                    <option value="OPEN" @selected($issue->status === 'OPEN')>Terbuka</option>
                    <option value="IN_PROGRESS" @selected($issue->status === 'IN_PROGRESS')>Sedang Ditangani</option>
                    <option value="RESOLVED" @selected($issue->status === 'RESOLVED')>Terselesaikan</option>
                </select>
            </label>
            <button class="button secondary" type="submit">
                <x-icon name="edit" size="16" />
                <span>Perbarui status</span>
            </button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <span class="eyebrow">RIWAYAT</span>
                <h2>Tindak lanjut</h2>
            </div>
        </div>

        @forelse($issue->followups as $followup)
            <div class="note-row">
                <div class="note-row-header">
                    <div class="note-row-author-wrap">
                        <span class="note-author-avatar" aria-hidden="true">{{ substr($followup->user->name ?? 'P', 0, 1) }}</span>
                        <strong class="note-row-author">{{ $followup->user->name }}</strong>
                    </div>
                    <span class="note-row-time">{{ $followup->created_at->format('d M Y, H:i') }} WIB</span>
                </div>
                <div class="note-row-body">
                    <p>{{ $followup->note }}</p>
                </div>
            </div>
        @empty
            <div class="empty">Belum ada tindak lanjut.</div>
        @endforelse

        <form method="POST" action="{{ route('issues.followups.store', $issue) }}" class="form-stack followup-form">
            @csrf
            <label>
                Tambah catatan
                <textarea name="note" rows="4" required placeholder="Tuliskan perkembangan terbaru"></textarea>
            </label>
            <button class="button primary" type="submit">
                <x-icon name="plus" size="16" />
                <span>Tambah tindak lanjut</span>
            </button>
        </form>
    </section>
</div>
@endsection

