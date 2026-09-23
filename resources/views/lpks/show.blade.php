@extends('layouts.app')

@section('title', $lpk->name . ' | SIMASADI')

@section('content')
<div class="page-heading" style="display: block; margin-bottom: 24px;">
    <div>
        <a class="back-link" href="{{ route('lpks.index') }}">Semua LPK</a>
        <h1 style="margin-top: 8px; margin-bottom: 6px; font-size: 23px; line-height: 1.35; font-weight: 700; word-break: break-word; max-width: 950px;">{{ $lpk->name }}</h1>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
            <p class="lede" style="margin-bottom: 0;">{{ $lpk->registration_number }} &middot; Lembaga Penilaian Kesesuaian Terakreditasi</p>
            <x-status :value="$lpk->dynamic_status" />
        </div>
    </div>
    @if(auth()->user()?->isAdmin())
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 14px;">
            <a class="button secondary" href="{{ route('lpks.edit', $lpk) }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 7px 16px;">
                <x-icon name="edit" size="16" />
                <span>Ubah data</span>
            </a>
            <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0; display: inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="button danger" style="background: #e53e3e; border-color: #c53030; color: #ffffff; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 7px 16px;">
                    <x-icon name="trash" size="16" />
                    <span>Hapus LPK</span>
                </button>
            </form>
            <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin: 0; display: inline-block;">
                @csrf
                <input type="hidden" name="is_simulation" value="1">
                <button type="submit" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 7px 16px; border-color: #6366f1; color: #4338ca; background: #eef2ff;" title="Simulasikan pengiriman notifikasi pengawasan ke Mailtrap Sandbox">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <span>Simulasi Notifikasi Email</span>
                </button>
            </form>
        </div>
    @endif
</div>

@php
    $milestones = $lpk->surveillance_milestones;
    $activeAlerts = $lpk->getActiveSurveillanceAlerts();
@endphp

@if(!empty($activeAlerts))
    <div class="panel" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1.5px solid #f43f5e; border-left: 6px solid #e11d48; margin-bottom: 24px; padding: 18px 22px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div style="flex: 1 1 480px; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <strong style="color: #9f1239; font-size: 15px;">
                        Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN
                    </strong>
                </div>
                <p style="margin: 0; font-size: 13px; color: #881337; line-height: 1.5;">
                    Notifikasi aktif untuk 
                    @foreach($activeAlerts as $a)
                        <span style="display: inline-block; background: rgba(225, 29, 72, 0.12); color: #9f1239; font-weight: 700; padding: 1px 8px; border-radius: 4px; font-size: 12px; margin: 1px 2px;">{{ $a['name'] }} ({{ $a['status_label'] }})</span>@if(!$loop->last) @endif
                    @endforeach
                    . Peringatan ini aktif sampai agenda kunjungan asesmen tercatat.
                </p>
                @if($lpk->last_surveillance_notified_at)
                    <div style="margin-top: 8px; color: #9f1239; font-size: 11.5px; display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Email pemberitahuan terakhir dikirim ke PIC Lab: <strong>{{ $lpk->last_surveillance_notified_at->format('d M Y H:i') }}</strong></span>
                    </div>
                @endif
            </div>
            @if(auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin: 0; flex-shrink: 0;">
                    @csrf
                    <button type="submit" class="button primary" style="background-color: #e11d48; border-color: #e11d48; font-size: 12.5px; padding: 7px 16px; min-height: 36px; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Kirim Notifikasi Email PIC Lab</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif

<section class="panel" style="margin-bottom: 24px;">
    <div class="panel-head" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line, #e2e8f0); padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="eyebrow">SIKLUS KAN U-01</span>
            <h2 style="margin: 0; font-size: 16px;">Siklus Pengawasan &amp; Re-Akreditasi</h2>
        </div>
        @if(auth()->user()?->isAdmin())
            <a href="{{ route('assessments.index') }}" class="button secondary" style="font-size: 12.5px; padding: 4px 12px; min-height: 32px; display: inline-flex; align-items: center; gap: 6px;">
                <span>Jadwalkan Asesmen Kunjungan</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        @endif
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
        <!-- S1 Card -->
        @php($s1 = $milestones['s1'])
        <div style="border: 1.5px solid {{ in_array($s1['status'], ['DUE', 'OVERDUE']) ? '#f43f5e' : 'var(--line, #e2e8f0)' }}; border-radius: 8px; padding: 16px; background: {{ in_array($s1['status'], ['DUE', 'OVERDUE']) ? '#fff1f2' : 'var(--surface-subtle, #f8fafc)' }}; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px; flex-wrap: wrap;">
                    <strong style="font-size: 14px; color: var(--text, #0f172a);">{{ $s1['name'] }}</strong>
                    @if($s1['status'] === 'COMPLETED_OR_SCHEDULED')
                        <span class="status status-completed" style="font-size: 11px;">Terjadwal / Selesai</span>
                    @elseif($s1['status'] === 'OVERDUE')
                        <span class="status status-danger" style="font-size: 11px; font-weight: 700;">Lewat Jadwal</span>
                    @elseif($s1['status'] === 'DUE')
                        <span class="status status-warn" style="font-size: 11px; font-weight: 700;">Notif Aktif (Bulan 14)</span>
                    @else
                        <span class="status" style="font-size: 11px; background: #e2e8f0; color: #475569;">Akan Datang</span>
                    @endif
                </div>
                <p style="margin: 0 0 10px; font-size: 12.5px; color: var(--muted, #64748b);">{{ $s1['description'] }}</p>
                <div style="font-size: 12px; border-top: 1px dashed var(--line, #cbd5e1); padding-top: 8px;">
                    <div>Waktu Notifikasi: <strong>{{ $s1['notice_date'] ? $s1['notice_date']->format('d M Y') : '-' }}</strong></div>
                    <div>Target Kunjungan: <strong>{{ $s1['target_date'] ? $s1['target_date']->format('d M Y') : '-' }}</strong></div>
                </div>
            </div>
            @if(auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin-top: 12px;">
                    @csrf
                    <input type="hidden" name="is_simulation" value="1">
                    <input type="hidden" name="code" value="s1">
                    <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email S1 ke PIC Lab">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Simulasi Email S1</span>
                    </button>
                </form>
            @endif
        </div>

        <!-- S2 Card -->
        @php($s2 = $milestones['s2'])
        <div style="border: 1.5px solid {{ in_array($s2['status'], ['DUE', 'OVERDUE']) ? '#f43f5e' : 'var(--line, #e2e8f0)' }}; border-radius: 8px; padding: 16px; background: {{ in_array($s2['status'], ['DUE', 'OVERDUE']) ? '#fff1f2' : 'var(--surface-subtle, #f8fafc)' }}; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px; flex-wrap: wrap;">
                    <strong style="font-size: 14px; color: var(--text, #0f172a);">{{ $s2['name'] }}</strong>
                    @if($s2['status'] === 'COMPLETED_OR_SCHEDULED')
                        <span class="status status-completed" style="font-size: 11px;">Terjadwal / Selesai</span>
                    @elseif($s2['status'] === 'OVERDUE')
                        <span class="status status-danger" style="font-size: 11px; font-weight: 700;">Lewat Jadwal</span>
                    @elseif($s2['status'] === 'DUE')
                        <span class="status status-warn" style="font-size: 11px; font-weight: 700;">Notif Aktif (Bulan 35)</span>
                    @else
                        <span class="status" style="font-size: 11px; background: #e2e8f0; color: #475569;">Akan Datang</span>
                    @endif
                </div>
                <p style="margin: 0 0 10px; font-size: 12.5px; color: var(--muted, #64748b);">{{ $s2['description'] }}</p>
                <div style="font-size: 12px; border-top: 1px dashed var(--line, #cbd5e1); padding-top: 8px;">
                    <div>Waktu Notifikasi: <strong>{{ $s2['notice_date'] ? $s2['notice_date']->format('d M Y') : '-' }}</strong></div>
                    <div>Target Kunjungan: <strong>{{ $s2['target_date'] ? $s2['target_date']->format('d M Y') : '-' }}</strong></div>
                </div>
            </div>
            @if(auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin-top: 12px;">
                    @csrf
                    <input type="hidden" name="is_simulation" value="1">
                    <input type="hidden" name="code" value="s2">
                    <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email S2 ke PIC Lab">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Simulasi Email S2</span>
                    </button>
                </form>
            @endif
        </div>

        <!-- RA Card -->
        @php($ra = $milestones['ra'])
        <div style="border: 1.5px solid {{ in_array($ra['status'], ['DUE', 'EXPIRED']) ? '#f43f5e' : 'var(--line, #e2e8f0)' }}; border-radius: 8px; padding: 16px; background: {{ in_array($ra['status'], ['DUE', 'EXPIRED']) ? '#fff1f2' : 'var(--surface-subtle, #f8fafc)' }}; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px; flex-wrap: wrap;">
                    <strong style="font-size: 14px; color: var(--text, #0f172a);">{{ $ra['name'] }}</strong>
                    @if($ra['status'] === 'COMPLETED_OR_SCHEDULED')
                        <span class="status status-completed" style="font-size: 11px;">Terjadwal / Selesai</span>
                    @elseif($ra['status'] === 'EXPIRED')
                        <span class="status status-danger" style="font-size: 11px; font-weight: 700;">Sertifikat Kedaluwarsa</span>
                    @elseif($ra['status'] === 'DUE')
                        <span class="status status-warn" style="font-size: 11px; font-weight: 700;">Notif Aktif (1 Bulan Sebelum Habis)</span>
                    @else
                        <span class="status" style="font-size: 11px; background: #e2e8f0; color: #475569;">Akan Datang</span>
                    @endif
                </div>
                <p style="margin: 0 0 10px; font-size: 12.5px; color: var(--muted, #64748b);">{{ $ra['description'] }}</p>
                <div style="font-size: 12px; border-top: 1px dashed var(--line, #cbd5e1); padding-top: 8px;">
                    <div>Waktu Notifikasi: <strong>{{ $ra['notice_date'] ? $ra['notice_date']->format('d M Y') : '-' }}</strong></div>
                    <div>Masa Berlaku Habis: <strong>{{ $ra['target_date'] ? $ra['target_date']->format('d M Y') : '-' }}</strong></div>
                </div>
            </div>
            @if(auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin-top: 12px;">
                    @csrf
                    <input type="hidden" name="is_simulation" value="1">
                    <input type="hidden" name="code" value="ra">
                    <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email RA ke PIC Lab">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Simulasi Email RA</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>

<div class="detail-grid" style="align-items: start;">
    <section class="panel">
        <span class="eyebrow">INFORMASI DASAR</span>
        <dl class="detail-list">
            <div>
                <dt>Status</dt>
                <dd><x-status :value="$lpk->dynamic_status" /></dd>
            </div>
            <div style="align-items: start;">
                <dt style="padding-top: 2px;">Ruang Lingkup Akreditasi</dt>
                <dd>@if($lpk->scope)<div style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.6; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line, #e2e8f0); border-radius: 6px; padding: 10px 14px; max-height: 280px; overflow-y: auto;">{{ $lpk->scope }}</div>@else<span style="color: var(--muted);">Belum diisi</span>@endif</dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd>{{ $lpk->email ?: 'Belum diisi' }}</dd>
            </div>
            <div>
                <dt>Telepon</dt>
                <dd>{{ $lpk->phone ?: 'Belum diisi' }}</dd>
            </div>
            <div style="align-items: start;">
                <dt>Alamat</dt>
                <dd>{{ $lpk->address ?: 'Belum diisi' }}</dd>
            </div>
            <div>
                <dt style="white-space: nowrap;">Tanggal Terbit Sertifikat</dt>
                <dd>@if($lpk->certificate_date)<strong>{{ $lpk->certificate_date->format('d M Y') }}</strong>@else<span style="color: var(--muted);">Belum ditentukan</span>@endif</dd>
            </div>
            <div>
                <dt style="white-space: nowrap;">Masa Berlaku Akreditasi</dt>
                <dd>@if($lpk->expired_at)<strong>{{ $lpk->expired_at->format('d M Y') }}</strong>@if($lpk->isExpired())<span class="status status-danger" style="margin-left: 8px; font-size: 11px; vertical-align: middle;">Kedaluwarsa</span>@elseif($lpk->isExpiringSoon())<span class="status status-warn" style="margin-left: 8px; font-size: 11px; vertical-align: middle;">Mendekati Kedaluwarsa</span>@endif @else<span style="color: var(--muted);">Belum ditentukan</span>@endif</dd>
            </div>
            <div>
                <dt style="white-space: nowrap;" title="Sertifikat, Amandemen & Lampiran">Berkas Akreditasi</dt>
                <dd>@if($lpk->drive_url && str_starts_with($lpk->drive_url, 'http'))<a href="{{ $lpk->drive_url }}" target="_blank" rel="noopener noreferrer" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 4px 12px; min-height: 32px;" title="Buka Berkas Sertifikat, Amandemen & Lampiran di Google Drive"><x-icon name="sheets" size="14" /><span>Buka Berkas di Google Drive &rarr;</span></a>@else<span style="color: var(--muted);">Belum ada tautan berkas</span>@endif</dd>
            </div>
        </dl>
    </section>

    @if(auth()->user()?->isAdmin())
        <section class="panel" style="align-self: start;">
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
    @endif
</div>
@endsection
