@extends('layouts.app')

@section('title', $lpk->name . ' | SIMASADI')

@section('content')
<div class="page-heading" style="display: block; margin-bottom: 24px;">
    <div>
        <a class="back-link" href="{{ route('lpks.index') }}">Semua LPK</a>
        <h1 style="margin-top: 8px; margin-bottom: 6px; font-size: 23px; line-height: 1.35; font-weight: 700; word-break: break-word; max-width: 950px;">{{ $lpk->name }}</h1>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
            <span style="background: #e2e8f0; color: #0f172a; font-weight: 700; font-size: 12px; padding: 2px 8px; border-radius: 4px;">No Reg: {{ $lpk->no_reg ?: $lpk->registration_number }}</span>
            @if($lpk->accreditation_number)
                <span style="background: #f1f5f9; color: #0f172a; font-weight: 700; font-size: 12px; padding: 2px 8px; border-radius: 4px; border: 1px solid #cbd5e1;">No Akreditasi: {{ $lpk->accreditation_number }}</span>
            @endif
            @if($lpk->accreditation_type)
                <span style="background: #eef2ff; color: #4338ca; font-weight: 600; font-size: 12px; padding: 2px 8px; border-radius: 4px;">{{ $lpk->accreditation_type }}</span>
            @endif
            <x-status :value="$lpk->dynamic_status" />
        </div>
    </div>
    @if(auth()->user()?->isAdmin() || (auth()->user()?->isPic() && $lpk->isManagedBy(auth()->user())))
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
            @if(auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin: 0; display: inline-block;">
                    @csrf
                    <input type="hidden" name="is_simulation" value="1">
                    <button type="submit" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 7px 16px; border-color: #6366f1; color: #4338ca; background: #eef2ff;" title="Simulasikan pengiriman notifikasi pengawasan ke Mailtrap Sandbox">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Simulasi Notifikasi Email</span>
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>

@php
    $milestones = $lpk->surveillance_milestones;
    $activeAlerts = $lpk->getActiveSurveillanceAlerts();
    $s1 = $milestones['s1'];
    $s2 = $milestones['s2'];
    $ra = $milestones['ra'];
    $linkedS1 = $lpk->assessments->first(function ($a) use ($s1) {
        return str_contains(strtolower($a->title), 's1')
            || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s1['target_date'] && abs($a->start_at->diffInMonths($s1['target_date'])) <= 3);
    });
    $linkedS2 = $lpk->assessments->first(function ($a) use ($s2, $linkedS1) {
        return (str_contains(strtolower($a->title), 's2')
            || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s2['target_date'] && abs($a->start_at->diffInMonths($s2['target_date'])) <= 3))
            && $a->id !== ($linkedS1?->id ?? null);
    });
    $linkedRA = $lpk->assessments->first(function ($a) {
        return str_contains(strtolower($a->title), 'ra')
            || str_contains(strtolower($a->title), 're-akreditasi')
            || str_contains(strtolower($a->assessment_type), 're-');
    });
@endphp

@if(!empty($activeAlerts))
    <div class="panel" style="background: #fff1f2; border: 1px solid #fecdd3; margin-bottom: 24px; padding: 18px 22px; border-radius: 8px; box-shadow: 0 1px 3px rgba(225, 29, 72, 0.05);">
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
            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="button secondary" style="font-size: 12.5px; padding: 4px 12px; min-height: 32px; display: inline-flex; align-items: center; gap: 6px;">
                <x-icon name="plus" size="14" />
                <span>Jadwalkan Asesmen Kunjungan</span>
            </a>
        @endif
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
        <!-- S1 Card -->
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
                @if($linkedS1)
                    <div style="margin-top: 8px; font-size: 11.5px; background: rgba(0,0,0,0.03); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span style="color: var(--muted, #64748b);">Agenda Asesmen:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <a href="{{ route('assessments.show', $linkedS1) }}" style="font-weight: 600; text-decoration: underline; color: var(--primary, #0284c7); display: inline-flex; align-items: center; gap: 3px;">
                                <span>{{ $linkedS1->status === 'PLANNED' ? 'Rencana (Bulan 15)' : $linkedS1->status_label }}</span>
                                <x-icon name="chevron-right" size="12" />
                            </a>
                            <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $linkedS1->start_at->toDateString(), 'highlight' => $linkedS1->id, 'selected' => 1]) }}"
                               class="assessment-cal-shortcut"
                               style="height: 22px; padding: 2px 7px; font-size: 11px;"
                               title="Lihat agenda S1 di kalender">
                                <x-icon name="calendar" size="11" />
                                <span>Kalender</span>
                            </a>
                        </div>
                    </div>
                @elseif(!empty($s1['target_date']))
                    <div style="margin-top: 8px; display: flex; justify-content: flex-end;">
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $s1['target_date']->toDateString(), 'highlight' => 'lpk_jt_s1_' . $lpk->id, 'selected' => 1]) }}"
                           class="assessment-cal-shortcut"
                           style="height: 22px; padding: 2px 7px; font-size: 11px; color: #475569; background: #f8fafc; border-color: #cbd5e1;"
                           title="Lihat target S1 di kalender">
                            <x-icon name="calendar" size="11" />
                            <span>Buka di Kalender</span>
                        </a>
                    </div>
                @endif
            </div>
            @if(auth()->user()?->isAdmin())
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 6px;">
                    @if(!$linkedS1)
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S1', 'target_date' => $s1['target_date']?->format('Y-m-d')]) }}" class="button primary button-sm" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px;">
                            <x-icon name="plus" size="13" />
                            <span>Jadwalkan Kunjungan S1</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}">
                        @csrf
                        <input type="hidden" name="is_simulation" value="1">
                        <input type="hidden" name="code" value="s1">
                        <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email S1 ke PIC Lab">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span>Simulasi Email S1</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- S2 Card -->
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
                @if($linkedS2)
                    <div style="margin-top: 8px; font-size: 11.5px; background: rgba(0,0,0,0.03); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span style="color: var(--muted, #64748b);">Agenda Asesmen:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <a href="{{ route('assessments.show', $linkedS2) }}" style="font-weight: 600; text-decoration: underline; color: var(--primary, #0284c7); display: inline-flex; align-items: center; gap: 3px;">
                                <span>{{ $linkedS2->status === 'PLANNED' ? 'Rencana (Bulan 36)' : $linkedS2->status_label }}</span>
                                <x-icon name="chevron-right" size="12" />
                            </a>
                            <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $linkedS2->start_at->toDateString(), 'highlight' => $linkedS2->id, 'selected' => 1]) }}"
                               class="assessment-cal-shortcut"
                               style="height: 22px; padding: 2px 7px; font-size: 11px;"
                               title="Lihat agenda S2 di kalender">
                                <x-icon name="calendar" size="11" />
                                <span>Kalender</span>
                            </a>
                        </div>
                    </div>
                @elseif(!empty($s2['target_date']))
                    <div style="margin-top: 8px; display: flex; justify-content: flex-end;">
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $s2['target_date']->toDateString(), 'highlight' => 'lpk_jt_s2_' . $lpk->id, 'selected' => 1]) }}"
                           class="assessment-cal-shortcut"
                           style="height: 22px; padding: 2px 7px; font-size: 11px; color: #475569; background: #f8fafc; border-color: #cbd5e1;"
                           title="Lihat target S2 di kalender">
                            <x-icon name="calendar" size="11" />
                            <span>Buka di Kalender</span>
                        </a>
                    </div>
                @endif
            </div>
            @if(auth()->user()?->isAdmin())
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 6px;">
                    @if(!$linkedS2)
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S2', 'target_date' => $s2['target_date']?->format('Y-m-d')]) }}" class="button primary button-sm" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px;">
                            <x-icon name="plus" size="13" />
                            <span>Jadwalkan Kunjungan S2</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}">
                        @csrf
                        <input type="hidden" name="is_simulation" value="1">
                        <input type="hidden" name="code" value="s2">
                        <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email S2 ke PIC Lab">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span>Simulasi Email S2</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- RA Card -->
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
                @if($linkedRA)
                    <div style="margin-top: 8px; font-size: 11.5px; background: rgba(0,0,0,0.03); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span style="color: var(--muted, #64748b);">Agenda Asesmen:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <a href="{{ route('assessments.show', $linkedRA) }}" style="font-weight: 600; text-decoration: underline; color: var(--primary, #0284c7); display: inline-flex; align-items: center; gap: 3px;">
                                <span>{{ $linkedRA->status === 'PLANNED' ? 'Rencana (Bulan 54)' : $linkedRA->status_label }}</span>
                                <x-icon name="chevron-right" size="12" />
                            </a>
                            <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $linkedRA->start_at->toDateString(), 'highlight' => $linkedRA->id, 'selected' => 1]) }}"
                               class="assessment-cal-shortcut"
                               style="height: 22px; padding: 2px 7px; font-size: 11px;"
                               title="Lihat agenda RA di kalender">
                                <x-icon name="calendar" size="11" />
                                <span>Kalender</span>
                            </a>
                        </div>
                    </div>
                @elseif(!empty($ra['target_date']))
                    <div style="margin-top: 8px; display: flex; justify-content: flex-end;">
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $ra['target_date']->toDateString(), 'highlight' => 'lpk_jt_ra_' . $lpk->id, 'selected' => 1]) }}"
                           class="assessment-cal-shortcut"
                           style="height: 22px; padding: 2px 7px; font-size: 11px; color: #475569; background: #f8fafc; border-color: #cbd5e1;"
                           title="Lihat target RA di kalender">
                            <x-icon name="calendar" size="11" />
                            <span>Buka di Kalender</span>
                        </a>
                    </div>
                @endif
            </div>
            @if(auth()->user()?->isAdmin())
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 6px;">
                    @if(!$linkedRA)
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'RA', 'target_date' => $ra['target_date']?->format('Y-m-d')]) }}" class="button primary button-sm" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px;">
                            <x-icon name="plus" size="13" />
                            <span>Jadwalkan Re-asesmen</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}">
                        @csrf
                        <input type="hidden" name="is_simulation" value="1">
                        <input type="hidden" name="code" value="ra">
                        <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email RA ke PIC Lab">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span>Simulasi Email RA</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</section>

<div class="detail-grid" style="align-items: start;">
    <section class="panel">
        <span class="eyebrow">INFORMASI DASAR</span>
        <dl class="detail-list">
            <div>
                <dt>No Reg LPK (ID Unik KAN)</dt>
                <dd><strong style="font-family: ui-monospace, SFMono-Regular, monospace; font-size: 14px; color: #0f172a;">{{ $lpk->no_reg ?: $lpk->registration_number }}</strong></dd>
            </div>
            <div>
                <dt>No Akreditasi KAN</dt>
                <dd>
                    @if($lpk->accreditation_number)
                        <strong style="color: #0f172a; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 14px;">{{ $lpk->accreditation_number }}</strong>
                    @else
                        <span style="color: var(--muted);">-</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Jenis Akreditasi</dt>
                <dd>{{ $lpk->accreditation_type ?: 'Laboratorium Penguji' }}</dd>
            </div>
            <div>
                <dt>Status Akreditasi</dt>
                <dd><x-status :value="$lpk->dynamic_status" /></dd>
            </div>
            @if($lpk->pic)
                <div>
                    <dt>PIC Penanggung Jawab</dt>
                    <dd><strong>{{ $lpk->pic->name }}</strong> <span style="font-size: 12px; color: var(--muted);">({{ $lpk->pic->email }})</span></dd>
                </div>
            @endif
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
                <dd>
                    <strong>{{ $lpk->masa_akreditasi_label }}</strong>
                    @if($lpk->isExpired())
                        <span class="status status-danger" style="margin-left: 8px; font-size: 11px; vertical-align: middle;">Kedaluwarsa</span>
                    @elseif($lpk->isExpiringSoon())
                        <span class="status status-warn" style="margin-left: 8px; font-size: 11px; vertical-align: middle;">Mendekati Kedaluwarsa</span>
                    @endif
                </dd>
            </div>
            <div style="align-items: start;">
                <dt style="padding-top: 6px;">Keterangan</dt>
                <dd style="width: 100%;">
                    <div class="lpk-detail-keterangan-stack">
                        {{-- Status Operasional Otomatis --}}
                        @php
                            $ketLower = strtolower($lpk->dynamic_keterangan);
                            if (str_contains($ketLower, 'jatuh tempo') || str_contains($ketLower, 'terlampaui') || str_contains($ketLower, 'lewat jadwal') || str_contains($ketLower, 'kedaluwarsa') || str_contains($ketLower, 'dicabut') || str_contains($ketLower, 'dibekukan')) {
                                $theme = 'rose';
                                $catLabel = 'Jatuh Tempo';
                            } elseif (str_contains($ketLower, 'segera berakhir') || str_contains($ketLower, 'reminder') || str_contains($ketLower, 'mendekati kedaluwarsa') || str_contains($ketLower, 'masa berlaku berakhir')) {
                                $theme = 'amber';
                                $catLabel = 'Reminder';
                            } elseif (str_contains($ketLower, 'batas tp') || str_contains($ketLower, 'penyusunan tp') || str_contains($ketLower, 'verifikasi tp') || str_contains($ketLower, 'perpanjangan tp')) {
                                $theme = 'cyan';
                                $catLabel = 'Batas TP';
                            } else {
                                $theme = 'emerald';
                                $catLabel = 'Pelaksanaan';
                            }

                            $hasColon = str_contains($lpk->dynamic_keterangan, ':');
                            if ($hasColon) {
                                [$processName, $statusDetail] = explode(':', $lpk->dynamic_keterangan, 2);
                                $processName = trim($processName);
                                $statusDetail = trim($statusDetail);
                            } else {
                                $processName = null;
                                $statusDetail = trim($lpk->dynamic_keterangan);
                            }
                        @endphp

                        <div class="lpk-keterangan-card lpk-keterangan-auto theme-{{ $theme }}">
                            <div class="lpk-keterangan-head">
                                <div class="lpk-keterangan-head-left">
                                    <span class="lpk-keterangan-type-label">Status Siklus (Otomatis)</span>
                                    @if($processName)
                                        <span class="lpk-keterangan-process-badge theme-{{ $theme }}">{{ $processName }}</span>
                                    @endif
                                </div>
                                <span class="lpk-keterangan-cat-tag theme-{{ $theme }}">
                                    <span class="lpk-keterangan-cat-dot"></span>
                                    <span>{{ $catLabel }}</span>
                                </span>
                            </div>
                            <div class="lpk-keterangan-body">
                                {{ $statusDetail }}
                            </div>
                        </div>

                        {{-- Catatan Manual PIC --}}
                        <div class="lpk-keterangan-card lpk-keterangan-pic {{ $lpk->notes ? 'has-notes' : 'is-empty' }}">
                            <div class="lpk-keterangan-head">
                                <div class="lpk-keterangan-head-left">
                                    <span class="lpk-keterangan-type-label">Catatan Khusus PIC</span>
                                </div>
                                <button type="button" class="button secondary button-xs lpk-keterangan-edit-btn" onclick="window.openModal('modal-input-keterangan')">
                                    <x-icon name="edit" size="13" />
                                    <span>{{ $lpk->notes ? 'Ubah Keterangan' : 'Input Keterangan' }}</span>
                                </button>
                            </div>
                            <div class="lpk-keterangan-body {{ $lpk->notes ? '' : 'is-empty' }}">
                                @if($lpk->notes)
                                    {!! nl2br(e(trim($lpk->notes))) !!}
                                @else
                                    <em>Belum ada keterangan.</em>
                                @endif
                            </div>
                        </div>
                    </div>
                </dd>
            </div>
        </dl>
    </section>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section class="panel" style="align-self: stretch;">
            <div class="panel-head">
                <div>
                    <span class="eyebrow">PROGRAM ASESMEN</span>
                    <h2>Daftar Asesmen Surveilen</h2>
                </div>
                <a href="{{ route('assessments.index', ['lpk_id' => $lpk->id]) }}">Semua asesmen</a>
            </div>

            @forelse($lpk->assessments->sortBy('start_at') as $item)
                <div class="assessment-list-row clickable-row" data-href="{{ route('assessments.show', $item) }}" tabindex="0" role="link" aria-label="{{ $item->title }}">
                    <div class="assessment-list-content">
                        <a href="{{ route('assessments.show', $item) }}" class="assessment-list-title">
                            {{ $item->title }}
                        </a>
                        <div class="assessment-list-meta">
                            <span>{{ $lpk->registration_number }} &bull; {{ $item->assessment_type_label }} &bull; {{ $item->start_at->format('d M Y') }}</span>
                            @if($item->sk_number)
                                <span class="assessment-list-sk">
                                    SK KAN: {{ $item->sk_number }} @if($item->sk_date)({{ $item->sk_date->format('d/m/Y') }})@endif
                                    @if($item->sk_lead_time_days !== null)
                                        &bull; <span style="font-weight: 500; color: #475569;">Durasi: {{ $item->sk_lead_time_days }} hari</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="assessment-list-actions">
                        <div class="assessment-list-badges">
                            <x-status :value="$item->status" />
                            @if($item->is_submission_overdue)
                                <span class="badge-tp badge-tp-danger" style="font-size: 10px;" title="Toleransi pengisian asesmen telah terlampaui">Lewat Toleransi</span>
                            @endif
                        </div>
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $item->start_at->toDateString(), 'highlight' => $item->id, 'selected' => 1]) }}"
                           class="assessment-cal-shortcut"
                           title="Lihat agenda ini di kalender">
                            <x-icon name="calendar" size="12" />
                            <span>Kalender</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty">Belum ada agenda asesmen untuk LPK ini.</div>
            @endforelse
        </section>

        @if(auth()->user()?->isAdmin())
            <section class="panel" style="align-self: stretch;">
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
</div>

{{-- Modal: Input / Ubah Keterangan LPK --}}
<div class="simasadi-modal" id="modal-input-keterangan" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box" style="max-width: 540px;">
        <div class="simasadi-modal-head">
            <h4>{{ $lpk->notes ? 'Ubah Keterangan LPK' : 'Input Keterangan LPK' }}</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-input-keterangan')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('lpks.notes.update', $lpk) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <p style="font-size: 13px; color: var(--muted); margin: 0;">
                    Keterangan atau catatan monitoring internal untuk <strong>{{ $lpk->name }}</strong> (No Reg: {{ $lpk->no_reg ?: $lpk->registration_number }}).
                </p>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                        <span style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase;">
                            Keterangan Otomatis:
                        </span>
                        <button type="button" class="button secondary" style="font-size: 11px; padding: 2px 8px; min-height: 24px;" onclick="document.getElementById('input-notes-textarea').value = {{ json_encode($lpk->dynamic_keterangan) }};">
                            Gunakan Keterangan Otomatis
                        </button>
                    </div>
                    <div style="font-size: 13px; color: #0f172a; line-height: 1.45;">
                        @if(str_contains($lpk->dynamic_keterangan, ':'))
                            @php
                                [$processName, $statusDetail] = explode(':', $lpk->dynamic_keterangan, 2);
                            @endphp
                            <strong style="color: #0f172a; font-weight: 700;">{{ $processName }}:</strong><span style="color: #334155; font-weight: 500;">{{ $statusDetail }}</span>
                        @else
                            <strong style="color: #0f172a; font-weight: 700;">{{ $lpk->dynamic_keterangan }}</strong>
                        @endif
                    </div>
                </div>

                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Catatan Khusus PIC (Opsional)</span>
                    <textarea id="input-notes-textarea" name="notes" rows="4" placeholder="Masukkan catatan tambahan PIC jika ada..." style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 6px; font-family: inherit; font-size: 13.5px; line-height: 1.5; resize: vertical;">{{ old('notes', $lpk->notes) }}</textarea>
                    <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Catatan manual akan selalu ditampilkan berdampingan dengan status otomatis sistem.</small>
                </label>
            </div>
            <div class="modal-form-actions" style="margin-top: 18px; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-input-keterangan')">Batal</button>
                <button type="submit" class="button primary">
                    <span>Simpan Keterangan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
