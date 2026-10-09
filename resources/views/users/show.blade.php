@extends('layouts.app')

@section('title', 'Rincian Anggota - ' . $user->name . ' | SIMASADI')

@section('content')
<div class="lpk-show-container">
    {{-- Navigasi Breadcrumb Kembali (Notion Style) --}}
    <div class="lpk-header-back-wrap">
        <a href="{{ route('users.index') }}" class="lpk-back-btn" title="Kembali ke Manajemen Anggota">
            <x-icon name="chevron-left" size="16" />
            <span>Manajemen Anggota</span>
        </a>
    </div>

    {{-- Header Kartu Profil Anggota --}}
    <div class="lpk-show-header" style="margin-bottom: 20px;">
        <div class="lpk-header-row">
            <div style="display: flex; align-items: center; gap: 16px; min-width: 0;">
                <div class="user-avatar" style="width: 56px; height: 56px; flex: 0 0 56px; font-size: 20px; border-radius: 9999px; border-width: 2px;">
                    {{ $user->initials }}
                </div>
                <div class="lpk-header-title-group">
                    <h1 style="font-size: 20px; margin: 0 0 4px 0;">{{ $user->name }}</h1>
                    <div class="lpk-header-badges">
                        <span class="badge-role {{ $user->role_badge_class }}">
                            {{ $user->role_label }}
                        </span>
                        <span style="font-size: 12.5px; color: var(--muted); display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            {{ $user->email }}
                        </span>
                        <span style="font-size: 12px; color: var(--muted);">
                            Terdaftar sejak: {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : 'Sistem Bawaan' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="lpk-header-actions">
                <a href="{{ route('lpks.index', ['pic_id' => $user->id]) }}" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 6px 14px;">
                    <x-icon name="lpks" size="14" />
                    <span>Filter di Data Lab</span>
                </a>
                <a href="{{ route('users.edit', $user) }}" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 6px 14px;">
                    <x-icon name="edit" size="14" />
                    <span>Ubah Data</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Metrik Ringkas Beban Kerja PIC --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: var(--info-bg, #e0f2fe); color: var(--info-text, #0284c7); border: 1px solid var(--info-border); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <x-icon name="lpks" size="22" />
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">Total LPK Ditangani</div>
                <strong style="font-size: 22px; color: var(--ink); line-height: 1.2;">{{ $totalLpks }}</strong>
                <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">Seluruh lab binaan dan kolaborasi</div>
            </div>
        </div>

        <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: var(--mint, #ecfdf5); color: var(--green, #047857); border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">{{ $user->isAdmin() ? 'Ketua Tim Utama' : 'PIC Utama' }}</div>
                <strong style="font-size: 22px; color: var(--green, #047857); line-height: 1.2;">{{ $totalLead }}</strong>
                <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">{{ $user->isAdmin() ? 'Wewenang penuh kelola LPK & asesmen' : 'PIC utama penanggung jawab LPK' }}</div>
            </div>
        </div>

        <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: var(--neutral-chip-bg, #f1f5f9); color: var(--neutral-chip-text, #475569); border: 1px solid var(--neutral-chip-border); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">{{ $user->isAdmin() ? 'Kolaborasi / Viewer' : 'PIC Kolaborasi' }}</div>
                <strong style="font-size: 22px; color: var(--ink); line-height: 1.2;">{{ $totalViewer }}</strong>
                <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">Hak akses baca pemantauan siklus</div>
            </div>
        </div>
    </div>

    {{-- Tabel Daftar LPK yang Dikerjakan --}}
    <section class="panel table-panel-borderless">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; border-bottom: 1px solid var(--line); padding-bottom: 14px;">
            <div>
                <span class="eyebrow" style="color: var(--primary);">DISTRIBUSI KERJA</span>
                <h2 style="font-size: 16px; margin: 2px 0 0; color: var(--ink);">Daftar Laboratorium yang Dikerjakan</h2>
            </div>
            <span style="font-size: 12.5px; color: var(--muted);">
                Menampilkan <strong>{{ $allLpks->count() }}</strong> laboratorium
            </span>
        </div>

        @if($allLpks->count() > 0)
            <div class="table-wrap">
                <table class="table-member-workload">
                    <thead>
                        <tr>
                            <th data-label="No. Akreditasi" style="min-width: 140px; width: 15%;">NO. AKREDITASI</th>
                            <th data-label="Nama Laboratorium" style="min-width: 250px;">NAMA LABORATORIUM</th>
                            <th data-label="Peran Anggota" style="min-width: 150px; width: 16%;">PERAN ANGGOTA</th>
                            <th data-label="Masa Berlaku" style="min-width: 160px; width: 16%;">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span style="font-weight: 700;">MASA BERLAKU</span>
                                    <span style="font-size: 10px; color: var(--muted); font-weight: 600;">AWAL &amp; AKHIR</span>
                                </div>
                            </th>
                            <th data-label="Status & Keterangan" style="min-width: 180px; width: 20%;" data-sortable="false">STATUS &amp; KETERANGAN</th>
                            <th data-label="Aksi" style="width: 80px; text-align: center;" data-sortable="false">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allLpks as $lpkItem)
                            @php
                                $isLead = ($lpkItem->pic_id !== null && (int)$lpkItem->pic_id === (int)$user->id) || ($lpkItem->pivot && $lpkItem->pivot->role === 'lead');
                            @endphp
                            <tr class="clickable-row" data-href="{{ route('lpks.show', $lpkItem) }}" tabindex="0" role="link" title="Buka rincian {{ $lpkItem->name }}">
                                <td data-sort-value="{{ $lpkItem->accreditation_number ?: $lpkItem->registration_number }}">
                                    <a href="{{ route('lpks.show', $lpkItem) }}" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; font-weight: 700; color: var(--ink); text-decoration: none;">
                                        {{ $lpkItem->accreditation_number ?: ($lpkItem->registration_number ?: '-') }}
                                    </a>
                                </td>
                                <td data-sort-value="{{ $lpkItem->name }}">
                                    <a href="{{ route('lpks.show', $lpkItem) }}" style="font-weight: 700; color: var(--ink); text-decoration: none; font-size: 13.5px; line-height: 1.4; display: block;">
                                        {{ $lpkItem->name }}
                                    </a>
                                    @if($lpkItem->scope)
                                        <div style="font-size: 11px; color: var(--muted); margin-top: 3px; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $lpkItem->scope }}">
                                            {{ $lpkItem->scope }}
                                        </div>
                                    @endif
                                </td>
                                <td data-sort-value="{{ $isLead ? 'Lead' : 'Viewer' }}">
                                    @if($isLead)
                                        <span class="badge badge-pic-lead">
                                            {{ $user->isAdmin() ? 'Ketua Tim Utama' : 'PIC Utama' }}
                                        </span>
                                    @else
                                        <span class="badge badge-pic-viewer">
                                            {{ $user->isAdmin() ? 'Kolaborasi / Viewer' : 'PIC Viewer' }}
                                        </span>
                                    @endif
                                </td>
                                <td data-sort-value="{{ $lpkItem->expired_at ? $lpkItem->expired_at->format('Y-m-d') : '' }}">
                                    <div style="display: flex; flex-direction: column; gap: 3px; font-size: 12px; white-space: nowrap;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span style="color: var(--muted); font-size: 11px; font-weight: 500; min-width: 34px;">Awal:</span>
                                            <span style="font-weight: 600; color: var(--ink);">{{ $lpkItem->certificate_date ? $lpkItem->certificate_date->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span style="color: var(--muted); font-size: 11px; font-weight: 500; min-width: 34px;">Akhir:</span>
                                            <span style="font-weight: 600; {{ $lpkItem->is_expired ? 'color: var(--danger-text, #ef4444);' : 'color: var(--ink);' }}">{{ $lpkItem->expired_at ? $lpkItem->expired_at->format('d/m/Y') : '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                        <x-status :value="$lpkItem->dynamic_status" />
                                        @if($lpkItem->dynamic_keterangan)
                                            <div style="font-size: 11px; color: var(--muted); line-height: 1.35; max-width: 240px;" title="{{ $lpkItem->dynamic_keterangan }}">
                                                {{ $lpkItem->dynamic_keterangan }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <a href="{{ route('lpks.show', $lpkItem) }}" class="button secondary button-xs" style="font-size: 11.5px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;" title="Lihat detail LPK">
                                        <span>Detail</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align: center; padding: 40px 16px; background: var(--surface-subtle); border: 1px dashed var(--line); border-radius: var(--radius-xl, 18px);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-pill, 9999px); background: var(--neutral-chip-bg); color: var(--muted); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                    <x-icon name="lpks" size="22" />
                </div>
                <h3 style="font-size: 14.5px; font-weight: 700; color: var(--ink); margin: 0 0 4px 0;">Belum Ada Laboratorium</h3>
                <p style="font-size: 12.5px; color: var(--muted); margin: 0;">
                    Anggota ini belum memiliki penugasan sebagai Ketua Tim ataupun Viewer pada laboratorium manapun.
                </p>
            </div>
        @endif
    </section>
</div>
@endsection
