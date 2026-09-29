@extends('layouts.app')

@section('title', $lpk->name . ' | SIMASADI')

@section('content')
<div class="lpk-show-container">
    {{-- Header Card dengan Breadcrumb, Nama LPK, Identitas & Tombol Aksi --}}
    <div class="lpk-show-header">
        <div class="lpk-header-back-wrap">
            <a href="{{ route('lpks.index') }}" class="lpk-back-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                <span>Semua LPK</span>
            </a>
        </div>

        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
                <h1>{{ $lpk->name }}</h1>

                <div class="lpk-header-badges">
                    <span class="lpk-badge-reg">No Reg: {{ $lpk->no_reg ?: ($lpk->registration_number ?: '-') }}</span>
                    @if($lpk->accreditation_number)
                        <span class="lpk-badge-acc">No Akreditasi: {{ $lpk->accreditation_number }}</span>
                    @else
                        <span class="lpk-badge-acc" style="color: #64748b; background: #f8fafc;">Asesmen Awal</span>
                    @endif
                    @if($lpk->accreditation_type)
                        <span class="lpk-badge-type">{{ $lpk->accreditation_type }}</span>
                    @endif
                    <x-status :value="$lpk->dynamic_status" />
                </div>
            </div>

            @if(auth()->user()?->isAdmin() || (auth()->user()?->isPic() && $lpk->isManagedBy(auth()->user())))
                <div class="lpk-header-actions">
                    @if(auth()->user()?->isAdmin())
                        <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin: 0; display: inline-block;">
                            @csrf
                            <input type="hidden" name="is_simulation" value="1">
                            <button type="submit" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 6px 14px; border-color: #c7d2fe; color: #4338ca; background: #eef2ff;" title="Simulasikan pengiriman notifikasi pengawasan ke Mailtrap Sandbox">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>Simulasi Notifikasi Email</span>
                            </button>
                        </form>
                    @endif
                    <a class="button secondary" href="{{ route('lpks.edit', $lpk) }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 6px 14px;">
                        <x-icon name="edit" size="14" />
                        <span>Ubah data</span>
                    </a>
                    <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0; display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="button danger" style="background: #dc2626; border-color: #b91c1c; color: #ffffff; display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 6px 14px;">
                            <x-icon name="trash" size="14" />
                            <span>Hapus LPK</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @php
        $milestones = $lpk->surveillance_milestones;
        $activeAlerts = $lpk->getActiveSurveillanceAlerts();
        $s1 = $milestones['s1'];
        $s2 = $milestones['s2'];
        $ra = $milestones['ra'];
        $certDate = $lpk->certificate_date ?: ($lpk->expired_at ? $lpk->expired_at->copy()->subYears(5) : null);
        $expDate = $lpk->expired_at ?: ($certDate ? $certDate->copy()->addYears(5) : null);

        $linkedS1 = $lpk->assessments->first(function ($a) use ($s1, $certDate) {
            $inCycle = ! $certDate || ($a->start_at && $a->start_at->gte($certDate->copy()->subMonths(2)) && $a->start_at->lte($certDate->copy()->addMonths(26)));

            return $inCycle && (
                str_contains(strtolower($a->title), 's1')
                || str_contains(strtolower($a->assessment_type), 'surveilen 1')
                || $a->assessment_type === \App\Models\Assessment::TYPE_SURVEILEN_1
                || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s1['target_date'] && abs($a->start_at->diffInMonths($s1['target_date'])) <= 6)
            );
        });
        $linkedS2 = $lpk->assessments->first(function ($a) use ($s2, $certDate, $linkedS1) {
            $inCycle = ! $certDate || ($a->start_at && $a->start_at->gte($certDate->copy()->addMonths(24)) && $a->start_at->lte($certDate->copy()->addMonths(46)));

            return $inCycle
                && $a->id !== ($linkedS1?->id ?? null)
                && (
                    str_contains(strtolower($a->title), 's2')
                    || str_contains(strtolower($a->assessment_type), 'surveilen 2')
                    || $a->assessment_type === \App\Models\Assessment::TYPE_SURVEILEN_2
                    || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s2['target_date'] && abs($a->start_at->diffInMonths($s2['target_date'])) <= 6)
                );
        });
        $linkedRA = $lpk->assessments->first(function ($a) use ($ra, $certDate, $expDate) {
            $inCycle = ! $certDate || ($a->start_at && $a->start_at->gte($certDate->copy()->addMonths(42)) && (! $expDate || $a->start_at->lte($expDate->copy()->addMonths(6))));

            return $inCycle && (
                str_contains(strtolower($a->title), 're-akreditasi')
                || str_contains(strtolower($a->title), 'reakreditasi')
                || str_contains(strtolower($a->title), '(ra)')
                || str_contains(strtolower($a->title), ' ra ')
                || in_array($a->assessment_type, [\App\Models\Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT'], true)
                || ($ra['target_date'] && $a->start_at && abs($a->start_at->diffInMonths($ra['target_date'])) <= 6)
            );
        });

        $resolveMilestoneCardState = function (?App\Models\Assessment $linked, array $milestone, string $typeCode): array {
            if ($linked) {
                if ($linked->status === 'SUSPENDED') {
                    return [
                        'card_class' => 'is-suspended',
                        'badge_class' => 'status-suspended',
                        'badge_label' => 'Dibekukan',
                    ];
                }
                if ($linked->status === 'REVOKED') {
                    return [
                        'card_class' => 'is-due',
                        'badge_class' => 'status-danger',
                        'badge_label' => 'Dicabut',
                    ];
                }
                if ($linked->status === 'COMPLETED') {
                    return [
                        'card_class' => 'is-completed',
                        'badge_class' => 'status-completed',
                        'badge_label' => 'Selesai',
                    ];
                }
                if ($linked->status === 'IN_PROGRESS') {
                    return [
                        'card_class' => 'is-in-progress',
                        'badge_class' => 'status-in_progress',
                        'badge_label' => 'Sedang Berlangsung',
                    ];
                }
                if ($linked->status === 'SCHEDULED') {
                    if ($linked->start_at && $linked->start_at->isPast()) {
                        return [
                            'card_class' => 'is-due',
                            'badge_class' => 'status-danger',
                            'badge_label' => 'Lewat Jadwal',
                        ];
                    }

                    return [
                        'card_class' => 'is-completed',
                        'badge_class' => 'status-completed',
                        'badge_label' => 'Terjadwal',
                    ];
                }
            }

            $mStatus = $milestone['status'] ?? 'UPCOMING';
            if ($mStatus === 'SUSPENDED') {
                return [
                    'card_class' => 'is-suspended',
                    'badge_class' => 'status-suspended',
                    'badge_label' => 'Dibekukan',
                ];
            }
            if ($mStatus === 'COMPLETED_OR_SCHEDULED') {
                return [
                    'card_class' => 'is-completed',
                    'badge_class' => 'status-completed',
                    'badge_label' => 'Terealisasi',
                ];
            }
            if ($mStatus === 'OVERDUE') {
                return [
                    'card_class' => 'is-due',
                    'badge_class' => 'status-danger',
                    'badge_label' => 'Lewat Jadwal',
                ];
            }
            if ($mStatus === 'EXPIRED') {
                return [
                    'card_class' => 'is-due',
                    'badge_class' => 'status-danger',
                    'badge_label' => 'Sertifikat Kedaluwarsa',
                ];
            }
            if ($mStatus === 'DUE') {
                $lbl = match ($typeCode) {
                    'S1' => 'Notif Aktif (Bulan 14)',
                    'S2' => 'Notif Aktif (Bulan 35)',
                    default => 'Notif Aktif (1 Bulan Sebelum Habis)',
                };
                return [
                    'card_class' => 'is-due',
                    'badge_class' => 'status-warn',
                    'badge_label' => $lbl,
                ];
            }
            return [
                'card_class' => '',
                'badge_class' => '',
                'badge_label' => 'Akan Datang',
            ];
        };

        $s1CardState = $resolveMilestoneCardState($linkedS1, $s1, 'S1');
        $s2CardState = $resolveMilestoneCardState($linkedS2, $s2, 'S2');
        $raCardState = $resolveMilestoneCardState($linkedRA, $ra, 'RA');

        $isMilestoneResolved = function (array $state): bool {
            return in_array($state['badge_label'], ['Selesai', 'Terealisasi', 'Terjadwal / Selesai'], true);
        };

        $currentFocusCode = null;
        if (! $isMilestoneResolved($s1CardState)) {
            $currentFocusCode = 'S1';
        } elseif (! $isMilestoneResolved($s2CardState)) {
            $currentFocusCode = 'S2';
        } else {
            $currentFocusCode = 'RA';
        }
    @endphp

    {{-- Alert Persisten Pengawasan KAN --}}
    @if(!empty($activeAlerts))
        <div class="lpk-alert-callout">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <strong>Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    Notifikasi aktif untuk 
                    @foreach($activeAlerts as $a)
                        <span class="lpk-alert-tag">{{ $a['name'] }} ({{ $a['status_label'] }})</span>@if(!$loop->last) @endif
                    @endforeach
                    . Peringatan ini aktif sampai agenda kunjungan asesmen tercatat.
                </p>
                @if($lpk->last_surveillance_notified_at)
                    <div class="lpk-alert-callout-meta">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Email pemberitahuan terakhir dikirim ke PIC Lab: <strong>{{ $lpk->last_surveillance_notified_at->format('d M Y') }}</strong></span>
                    </div>
                @endif
            </div>
            @if(auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin: 0; flex-shrink: 0;">
                    @csrf
                    <button type="submit" class="button primary" style="background-color: #e11d48; border-color: #e11d48; font-size: 12.5px; padding: 7px 16px; min-height: 36px; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>Kirim Notifikasi Email PIC Lab</span>
                    </button>
                </form>
            @endif
        </div>
    @endif

    {{-- Siklus KAN U-01: Roadmap & Milestone Card --}}
    <div class="lpk-form-card">
        <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 14px; margin-bottom: 16px;">
            <div class="lpk-card-icon-wrap" style="background: #eef2ff; color: #4338ca;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="lpk-card-header-text" style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span class="eyebrow" style="color: #4338ca;">SIKLUS KAN U-01</span>
                        <h2 style="font-size: 16px; margin: 2px 0 0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span>Siklus Pengawasan &amp; Re-Akreditasi</span>
                            @if($lpk->certificate_date && $lpk->expired_at)
                                <span style="font-size: 13px; font-weight: 500; color: var(--muted); margin-left: 6px;">(Periode {{ $lpk->certificate_date->format('Y') }} - {{ $lpk->expired_at->format('Y') }})</span>
                            @endif
                            @php
                                $focusLabel = match($currentFocusCode) {
                                    'S1' => 'Surveilen 1',
                                    'S2' => 'Surveilen 2',
                                    default => 'Re-Akreditasi',
                                };
                            @endphp
                            <span style="font-size: 11.5px; font-weight: 600; padding: 2px 9px; border-radius: 9999px; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; display: inline-flex; align-items: center; gap: 5px;">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #4f46e5;"></span>
                                Fokus Siklus: {{ $focusLabel }}
                            </span>
                        </h2>
                    </div>
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="button secondary" style="font-size: 12px; padding: 5px 12px; min-height: 32px; display: inline-flex; align-items: center; gap: 6px;">
                            <x-icon name="plus" size="14" />
                            <span>Jadwalkan Asesmen Kunjungan</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="lpk-milestones-grid">
            <!-- S1 Card -->
            <div class="lpk-milestone-card {{ $s1CardState['card_class'] }}">
                <div class="lpk-milestone-card-top">
                    <div class="lpk-milestone-header">
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <strong class="lpk-milestone-title">{{ $s1['name'] }}</strong>
                            @if($currentFocusCode === 'S1')
                                <span style="font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 9999px; background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;">Fokus Siklus</span>
                            @endif
                        </div>
                        <span class="status {{ $s1CardState['badge_class'] }}" style="font-size: 11px;">
                            {{ $s1CardState['badge_label'] }}
                        </span>
                    </div>
                    <p class="lpk-milestone-desc">{{ $s1['description'] }}</p>

                    <div class="lpk-milestone-dates">
                        <div class="lpk-milestone-date-row">
                            <span>Tanggal Notifikasi:</span>
                            <strong>{{ $s1['notice_date'] ? $s1['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-milestone-date-row">
                            <span>Target Kunjungan (Bulan 15):</span>
                            <strong>{{ ($s1['visit_target_date'] ?? null) ? $s1['visit_target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-milestone-date-row">
                            <span>Batas Jatuh Tempo (Bulan 18):</span>
                            <strong>{{ $s1['target_date'] ? $s1['target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                    </div>

                    @if($linkedS1)
                        <div class="lpk-milestone-agenda-bar">
                            <span class="lpk-milestone-agenda-label">Agenda Asesmen:</span>
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <a href="{{ route('assessments.show', $linkedS1) }}" class="lpk-milestone-agenda-link">
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
                        <div style="display: flex; justify-content: flex-end; margin-top: 6px;">
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
                    <div class="lpk-milestone-actions">
                        @if(!$linkedS1)
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S1', 'target_date' => ($s1['visit_target_date'] ?? $s1['target_date'])?->format('Y-m-d')]) }}" class="button primary button-sm" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px;">
                                <x-icon name="plus" size="13" />
                                <span>Jadwalkan Kunjungan S1</span>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}">
                            @csrf
                            <input type="hidden" name="is_simulation" value="1">
                            <input type="hidden" name="code" value="s1">
                            <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email S1 ke PIC Lab">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>Simulasi Email S1</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- S2 Card -->
            <div class="lpk-milestone-card {{ $s2CardState['card_class'] }}">
                <div class="lpk-milestone-card-top">
                    <div class="lpk-milestone-header">
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <strong class="lpk-milestone-title">{{ $s2['name'] }}</strong>
                            @if($currentFocusCode === 'S2')
                                <span style="font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 9999px; background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;">Fokus Siklus</span>
                            @endif
                        </div>
                        <span class="status {{ $s2CardState['badge_class'] }}" style="font-size: 11px;">
                            {{ $s2CardState['badge_label'] }}
                        </span>
                    </div>
                    <p class="lpk-milestone-desc">{{ $s2['description'] }}</p>

                    <div class="lpk-milestone-dates">
                        <div class="lpk-milestone-date-row">
                            <span>Tanggal Notifikasi:</span>
                            <strong>{{ $s2['notice_date'] ? $s2['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-milestone-date-row">
                            <span>Target Kunjungan (Bulan 36):</span>
                            <strong>{{ ($s2['visit_target_date'] ?? null) ? $s2['visit_target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-milestone-date-row">
                            <span>Batas Jatuh Tempo (Bulan 39):</span>
                            <strong>{{ $s2['target_date'] ? $s2['target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                    </div>

                    @if($linkedS2)
                        <div class="lpk-milestone-agenda-bar">
                            <span class="lpk-milestone-agenda-label">Agenda Asesmen:</span>
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <a href="{{ route('assessments.show', $linkedS2) }}" class="lpk-milestone-agenda-link">
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
                        <div style="display: flex; justify-content: flex-end; margin-top: 6px;">
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
                    <div class="lpk-milestone-actions">
                        @if(!$linkedS2)
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S2', 'target_date' => ($s2['visit_target_date'] ?? $s2['target_date'])?->format('Y-m-d')]) }}" class="button primary button-sm" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px;">
                                <x-icon name="plus" size="13" />
                                <span>Jadwalkan Kunjungan S2</span>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}">
                            @csrf
                            <input type="hidden" name="is_simulation" value="1">
                            <input type="hidden" name="code" value="s2">
                            <button type="submit" class="button secondary" style="width: 100%; font-size: 11.5px; padding: 4px 10px; min-height: 28px; justify-content: center; gap: 5px; border-color: #cbd5e1; color: #334155; background: #ffffff;" title="Simulasikan kirim email S2 ke PIC Lab">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>Simulasi Email S2</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- RA Card -->
            <div class="lpk-milestone-card {{ $raCardState['card_class'] }}">
                <div class="lpk-milestone-card-top">
                    <div class="lpk-milestone-header">
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <strong class="lpk-milestone-title">{{ $ra['name'] }}</strong>
                            @if($currentFocusCode === 'RA')
                                <span style="font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 9999px; background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;">Fokus Siklus</span>
                            @endif
                        </div>
                        <span class="status {{ $raCardState['badge_class'] }}" style="font-size: 11px;">
                            {{ $raCardState['badge_label'] }}
                        </span>
                    </div>
                    <p class="lpk-milestone-desc">{{ $ra['description'] }}</p>

                    <div class="lpk-milestone-dates">
                        <div class="lpk-milestone-date-row">
                            <span>Tanggal Notifikasi:</span>
                            <strong>{{ $ra['notice_date'] ? $ra['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-milestone-date-row">
                            <span>Batas Kunjungan RA (Bulan 54):</span>
                            <strong>{{ $ra['target_date'] ? $ra['target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-milestone-date-row">
                            <span>Masa Berlaku Habis:</span>
                            <strong>{{ ($expDate ?: ($ra['tolerance_date'] ?? null)) ? ($expDate ?: $ra['tolerance_date'])->format('d M Y') : '-' }}</strong>
                        </div>
                    </div>

                    @if($linkedRA)
                        <div class="lpk-milestone-agenda-bar">
                            <span class="lpk-milestone-agenda-label">Agenda Asesmen:</span>
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <a href="{{ route('assessments.show', $linkedRA) }}" class="lpk-milestone-agenda-link">
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
                        <div style="display: flex; justify-content: flex-end; margin-top: 6px;">
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
                    <div class="lpk-milestone-actions">
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
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>Simulasi Email RA</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 2-Column Responsive Layout --}}
    <div class="lpk-show-grid">
        {{-- KOLOM KIRI: Informasi Dasar, Legalitas, Narahubung, Ruang Lingkup & Keterangan --}}
        <div class="lpk-show-col">
            {{-- KARTU 1: Profil & Legalitas Laboratorium --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="16" height="20" x="4" y="2" rx="2" ry="2"/>
                            <path d="M9 22v-4h6v4"/>
                            <path d="M8 6h.01"/>
                            <path d="M16 6h.01"/>
                            <path d="M8 10h.01"/>
                            <path d="M16 10h.01"/>
                            <path d="M8 14h.01"/>
                            <path d="M16 14h.01"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Profil &amp; Legalitas Laboratorium</h2>
                        <p>Identitas resmi lembaga dan nomor registrasi di Komite Akreditasi Nasional (KAN).</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">No Reg LPK (ID Unik KAN)</span>
                        <div class="lpk-meta-value">
                            <strong style="font-family: ui-monospace, SFMono-Regular, monospace; font-size: 13.5px; color: #0f172a;">{{ $lpk->no_reg ?: $lpk->registration_number }}</strong>
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">No Akreditasi KAN</span>
                        <div class="lpk-meta-value">
                            @if($lpk->accreditation_number)
                                <strong style="color: #0f172a; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 13.5px;">{{ $lpk->accreditation_number }}</strong>
                            @else
                                <span style="color: var(--muted);">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Jenis Akreditasi</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->accreditation_type ?: 'Laboratorium Penguji' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Status Akreditasi</span>
                        <div class="lpk-meta-value">
                            <x-status :value="$lpk->dynamic_status" />
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Tanggal Terbit Sertifikat</span>
                        <div class="lpk-meta-value">
                            @if($lpk->certificate_date)
                                <strong>{{ $lpk->certificate_date->format('d M Y') }}</strong>
                            @else
                                <span style="color: var(--muted);">Belum ditentukan</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Masa Berlaku Akreditasi</span>
                        <div class="lpk-meta-value">
                            <strong>{{ $lpk->masa_akreditasi_label }}</strong>
                            @if($lpk->isExpired())
                                <span class="status status-danger" style="margin-left: 6px; font-size: 11px; vertical-align: middle;">Kedaluwarsa</span>
                            @elseif($lpk->isExpiringSoon())
                                <span class="status status-warn" style="margin-left: 6px; font-size: 11px; vertical-align: middle;">Mendekati Kedaluwarsa</span>
                            @endif
                        </div>
                    </div>

                    @if($lpk->drive_url)
                        <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 12px; margin-top: 4px;">
                            <span class="lpk-meta-label">Link Dokumen Google Drive</span>
                            <div class="lpk-meta-value" style="display: flex; align-items: center; gap: 8px;">
                                <a href="{{ $lpk->drive_url }}" target="_blank" rel="noopener noreferrer" class="button secondary button-xs" style="color: #1e40af; border-color: #bfdbfe; background: #eff6ff; display: inline-flex; align-items: center; gap: 6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                    <span>Buka Berkas di Google Drive</span>
                                </a>
                                <span style="color: var(--muted); font-size: 12px;">(Sertifikat Akreditasi, Amandemen &amp; Lampiran)</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- KARTU 2: Narahubung & Alamat Fasilitas --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap" style="background: #ecfdf5; color: #047857;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Kontak &amp; Alamat Fasilitas</h2>
                        <p>Informasi penanggung jawab teknis dan domisili fisik fasilitas pengujian.</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">PIC Penanggung Jawab</span>
                        <div class="lpk-meta-value">
                            @if($lpk->pic)
                                <strong>{{ $lpk->pic->name }}</strong>
                                <div style="font-size: 12px; color: var(--muted);">{{ $lpk->pic->email }}</div>
                            @else
                                <span style="color: var(--muted);">Belum ditentukan</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Email Resmi Laboratorium</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->email ?: 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Nomor Telepon / Hotline</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->phone ?: 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Kota / Lokasi</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->address ? Str::limit($lpk->address, 35) : 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 10px;">
                        <span class="lpk-meta-label">Alamat Lengkap Fasilitas</span>
                        <div class="lpk-meta-value" style="color: #334155; line-height: 1.55;">
                            {{ $lpk->address ?: 'Belum diisi' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU 3: Ruang Lingkup Akreditasi --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap" style="background: #f0fdf4; color: #166534;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Ruang Lingkup Akreditasi</h2>
                        <p>Bidang pengujian, kalibrasi, inspeksi, atau komoditas yang diakui dalam sertifikat KAN.</p>
                    </div>
                </div>

                <div>
                    @if($lpk->scope)
                        <div class="lpk-scope-box">{{ $lpk->scope }}</div>
                    @else
                        <div style="background: #f8fafc; border: 1px dashed var(--line); border-radius: 8px; padding: 16px; text-align: center; color: var(--muted); font-size: 13px;">
                            Belum ada rincian ruang lingkup akreditasi yang diinput.
                        </div>
                    @endif
                </div>
            </div>

            {{-- KARTU 4: Status Siklus Monitoring & Catatan PIC --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap" style="background: #fffbeb; color: #b45309;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Status Siklus &amp; Catatan Monitoring</h2>
                        <p>Pemantauan status pengawasan KAN otomatis dan catatan internal pengelola LPK.</p>
                    </div>
                </div>

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

                        // Menentukan target tanggal dan highlight untuk pintasan kalender
                        $calTargetDate = null;
                        $calHighlight = null;
                        $activeAss = $lpk->getActiveOrUpcomingAssessment();

                        if ($activeAss) {
                            if ($activeAss->effective_tp_due_date && $activeAss->tp_status && !in_array($activeAss->tp_status, [\App\Models\Assessment::TP_STATUS_NONE, \App\Models\Assessment::TP_STATUS_SATISFIED], true)) {
                                $calTargetDate = $activeAss->effective_tp_due_date->toDateString();
                                $calHighlight = 'tp_' . $activeAss->id;
                            } elseif ($activeAss->start_at) {
                                $calTargetDate = $activeAss->start_at->toDateString();
                                $calHighlight = (string) $activeAss->id;
                            } elseif ($activeAss->end_at) {
                                $calTargetDate = $activeAss->end_at->toDateString();
                                $calHighlight = (string) $activeAss->id;
                            } else {
                                $calTargetDate = now()->toDateString();
                                $calHighlight = (string) $activeAss->id;
                            }
                        } else {
                            $survAlerts = $lpk->getActiveSurveillanceAlerts();
                            if (!empty($survAlerts)) {
                                $firstAlert = reset($survAlerts);
                                $calTargetDate = $firstAlert['target_date']?->toDateString() ?? now()->toDateString();
                                $calHighlight = 'lpk_jt_' . strtolower($firstAlert['code'] ?? 's1') . '_' . $lpk->id;
                            } else {
                                $calTargetDate = $lpk->expired_at?->toDateString() ?? $lpk->certificate_date?->toDateString() ?? now()->toDateString();
                                $calHighlight = null;
                            }
                        }

                        $calParams = ['view' => 'month', 'date' => $calTargetDate, 'selected' => 1];
                        if ($calHighlight) {
                            $calParams['highlight'] = $calHighlight;
                        }
                        $calStatusSiklusUrl = route('calendar.index', $calParams);
                    @endphp

                    <div class="lpk-keterangan-card lpk-keterangan-auto theme-{{ $theme }}">
                        <div class="lpk-keterangan-head">
                            <div class="lpk-keterangan-head-left">
                                <span class="lpk-keterangan-type-label">Status Siklus (Otomatis)</span>
                                @if($processName)
                                    <span class="lpk-keterangan-process-badge theme-{{ $theme }}">{{ $processName }}</span>
                                @endif
                            </div>
                            <div class="lpk-keterangan-head-right">
                                <span class="lpk-keterangan-cat-tag theme-{{ $theme }}">
                                    <span>{{ $catLabel }}</span>
                                </span>
                                <a href="{{ $calStatusSiklusUrl }}"
                                   class="button secondary button-xs lpk-keterangan-cal-btn"
                                   title="Buka langsung agenda siklus ini di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Lihat di Kalender</span>
                                </a>
                            </div>
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
            </div>
        </div>

        {{-- KOLOM KANAN: Program Asesmen Surveilen & Proses Akreditasi --}}
        <div class="lpk-show-col">
            {{-- KARTU: Program Asesmen Surveilen --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 14px;">
                    <div class="lpk-card-icon-wrap" style="background: #f0f9ff; color: #0284c7;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <span class="eyebrow" style="color: #0284c7;">PROGRAM ASESMEN</span>
                                <h2 style="font-size: 15.5px; margin: 2px 0 0;">Daftar Asesmen Surveilen</h2>
                            </div>
                            <a href="{{ route('assessments.index', ['lpk_id' => $lpk->id]) }}" style="font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none;">
                                Semua asesmen &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @forelse($lpk->assessments->sortBy('start_at') as $item)
                        <div class="assessment-list-row clickable-row" data-href="{{ route('assessments.show', $item) }}" tabindex="0" role="link" aria-label="{{ $item->title }}" style="background: #ffffff; border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: border-color 140ms ease, box-shadow 140ms ease;">
                            <div class="assessment-list-content">
                                <a href="{{ route('assessments.show', $item) }}" class="assessment-list-title" style="font-size: 13.5px; font-weight: 600; color: var(--ink); text-decoration: none;">
                                    {{ $item->title }}
                                </a>
                                <div class="assessment-list-meta" style="font-size: 12px; color: var(--muted); margin-top: 3px;">
                                    <span>{{ $lpk->registration_number }} &bull; {{ $item->assessment_type_label }} &bull; {{ $item->start_at->format('d M Y') }}</span>
                                    @if($item->sk_number)
                                        <div class="assessment-list-sk" style="margin-top: 4px; font-size: 11.5px; color: #334155;">
                                            SK KAN: {{ $item->sk_number }} @if($item->sk_date)({{ $item->sk_date->format('d/m/Y') }})@endif
                                            @if($item->sk_lead_time_days !== null)
                                                &bull; <span style="font-weight: 600; color: #047857;">Durasi: {{ $item->sk_lead_time_days }} hari</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="assessment-list-actions" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--line); padding-top: 8px; margin-top: 2px;">
                                <div class="assessment-list-badges" style="display: flex; align-items: center; gap: 6px;">
                                    <x-status :value="$item->status" />
                                    @if($item->is_submission_overdue)
                                        <span class="badge-tp badge-tp-danger" style="font-size: 10px;" title="Toleransi pengisian asesmen telah terlampaui">Lewat Toleransi</span>
                                    @endif
                                </div>
                                <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $item->start_at->toDateString(), 'highlight' => $item->id, 'selected' => 1]) }}"
                                   class="assessment-cal-shortcut"
                                   style="height: 24px; padding: 2px 8px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"
                                   title="Lihat agenda ini di kalender">
                                    <x-icon name="calendar" size="12" />
                                    <span>Kalender</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="empty" style="text-align: center; padding: 24px 16px; color: var(--muted); font-size: 13px; background: #f8fafc; border: 1px dashed var(--line); border-radius: 8px;">
                            Belum ada agenda asesmen untuk LPK ini.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- KARTU: Proses Terkait (Akreditasi) --}}
            @if(auth()->user()?->isAdmin())
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 14px;">
                        <div class="lpk-card-icon-wrap" style="background: #f5f3ff; color: #7c3aed;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text" style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <span class="eyebrow" style="color: #7c3aed;">AKREDITASI</span>
                                    <h2 style="font-size: 15.5px; margin: 2px 0 0;">Proses terkait</h2>
                                </div>
                                <a href="{{ route('accreditations.index') }}" style="font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none;">
                                    Semua proses &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @forelse($lpk->accreditations as $item)
                            <a class="list-row" href="{{ route('accreditations.show', $item) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border: 1px solid var(--line); border-radius: 6px; text-decoration: none; transition: background 140ms ease;">
                                <div>
                                    <strong style="color: var(--ink); font-size: 13px; display: block;">Proses akreditasi #{{ $item->id }}</strong>
                                    <span style="color: var(--muted); font-size: 11.5px;">Target {{ $item->target_date?->format('d M Y') ?: 'Belum ditentukan' }}</span>
                                </div>
                                <x-status :value="$item->status" />
                            </a>
                        @empty
                            <div class="empty" style="text-align: center; padding: 20px 16px; color: var(--muted); font-size: 13px; background: #f8fafc; border: 1px dashed var(--line); border-radius: 8px;">
                                Belum ada proses akreditasi.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
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
