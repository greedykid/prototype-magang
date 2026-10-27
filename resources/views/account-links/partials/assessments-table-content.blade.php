@if($assessments->count())
    <div class="table-wrap" style="margin-top: 16px;">
        <div class="table-mobile-scroll-hint" aria-hidden="true">
            <x-icon name="chevron-left" size="14" />
            <span>Geser ke samping untuk melihat kolom lengkap</span>
            <x-icon name="chevron-right" size="14" />
        </div>
        <table id="linked-assessments-table" class="table-lpks-custom">
            <thead>
                <tr>
                    <th style="min-width: 220px;">AGENDA</th>
                    <th style="min-width: 180px;">LPK</th>
                    <th style="min-width: 140px;">TANGGAL</th>
                    <th style="min-width: 160px;">TINDAKAN PERBAIKAN (TP)</th>
                    <th style="min-width: 130px;">STATUS ASESMEN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assessments as $assessment)
                    <tr class="clickable-row" data-href="{{ route('account-links.assessments.show', [$owner, $assessment]) }}" data-id="{{ $assessment->id }}" tabindex="0" title="Klik baris untuk melihat detail asesmen {{ $assessment->display_title }}">
                        <td>
                            <a href="{{ route('account-links.assessments.show', [$owner, $assessment]) }}" style="font-size: 13.5px; font-weight: 700; color: var(--ink); text-decoration: none; display: block;">
                                {{ $assessment->display_title }}
                            </a>
                            @php
                                $typeLabel = $assessment->assessment_type_label;
                                $displayTitle = $assessment->display_title;
                                $titleLower = strtolower($displayTitle);
                                $typeLower = strtolower($typeLabel);
                                $isRedundantType = str_contains($titleLower, $typeLower)
                                    || (str_contains($titleLower, 's1') && str_contains($typeLower, '1'))
                                    || (str_contains($titleLower, 's2') && str_contains($typeLower, '2'))
                                    || ((str_contains($titleLower, 're-akreditasi') || str_contains($titleLower, 're-asesmen') || str_contains($titleLower, 'ra')) && (str_contains($typeLower, 're-') || str_contains($typeLower, 'ra')));
                            @endphp
                            @if(!$isRedundantType)
                                <span style="display: block; font-size: 11.5px; color: var(--muted); font-weight: 500; margin-top: 2px;">{{ $typeLabel }}</span>
                            @endif
                            @if($assessment->sk_number)
                                <div class="assessment-sk-pill" title="SK: {{ $assessment->sk_number }} {{ $assessment->sk_date ? '(' . $assessment->sk_date . ')' : '' }} {{ $assessment->sk_lead_time_label ? '| Rentang: ' . $assessment->sk_lead_time_label : '' }}">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity: 0.7;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <code class="sk-code">SK: {{ $assessment->sk_number }}</code>
                                    @if($assessment->sk_lead_time_days !== null)
                                        <span class="sk-lead-time">({{ $assessment->sk_lead_time_days }} hr)</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('account-links.lpks.show', [$owner, $assessment->lpk]) }}" style="color: var(--ink); font-size: 13px; font-weight: 700; text-decoration: none; display: block;" title="Buka rincian LPK">
                                {{ $assessment->lpk->name }}
                            </a>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 2px;">
                                <span style="font-size: 11.5px; color: var(--muted); font-weight: 500;">{{ $assessment->lpk->registration_number }}</span>
                                @if($assessment->lpkLocation)
                                    <span class="badge-tp" style="display: inline-flex; align-items: center; gap: 3px; font-size: 10px; padding: 0 5px; background: rgba(2, 132, 199, 0.08); border-color: rgba(2, 132, 199, 0.25); color: var(--primary); font-weight: 600;">
                                        <x-icon name="map-pin" size="10" />
                                        <span>{{ $assessment->lpkLocation->name }}</span>
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td style="font-size: 12.5px; color: var(--ink);">
                            {{ $assessment->start_at->isSameDay($assessment->end_at) ? $assessment->start_at->format('d M Y') : $assessment->start_at->format('d M Y') . ' - ' . $assessment->end_at->format('d M Y') }}
                        </td>
                        <td>
                            @php
                                $badge = $assessment->tp_sla_badge;
                                $hasDueDate = $assessment->effective_tp_due_date && $assessment->tp_status !== \App\Models\Assessment::TP_STATUS_NONE;
                            @endphp
                            @if($hasDueDate)
                                <strong style="font-size: 14px; font-weight: 700; color: var(--ink); display: inline-block;" title="{{ $badge['label'] }} - {{ $badge['detail'] }}">{{ $assessment->effective_tp_due_date->format('d/m/Y') }}</strong>
                            @else
                                <span style="color: var(--muted); font-size: 13px;">-</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <x-status :value="$assessment->status" />
                                    <span class="badge badge-viewer" title="Akses Viewer: Hanya Lihat">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>Viewer
                                    </span>
                                </div>
                                @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                                    <div class="assessment-status-subtag is-danger" title="Telah melewati batas toleransi pembekuan 1 tahun tanpa penyelesaian">
                                        Akreditasi Dicabut
                                    </div>
                                @elseif($assessment->is_submission_overdue)
                                    <div class="assessment-status-subtag is-danger" title="Toleransi pengisian dokumen surveilen telah terlampaui (akhir bulan kunjungan), sisa kesempatan pembekuan 1 tahun">
                                        Toleransi Terlampaui
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-pagination-wrap" style="margin-top: 16px;">
        {{ $assessments->links() }}
    </div>
@else
    <div style="padding: 48px 16px; text-align: center; background: var(--surface-subtle, var(--surface)); border: 1px dashed var(--line); border-radius: var(--radius-xl, 18px); margin-top: 16px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 9999px; background: var(--neutral-chip-bg, #f1f5f9); color: var(--muted); margin-bottom: 12px;">
            <x-icon name="assessments" size="24" />
        </div>
        <div style="font-size: 14px; font-weight: 600; color: var(--ink);">Tidak Ada Asesmen Ditemukan</div>
        <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0; line-height: 1.5;">
            @if(($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? ''))
                Tidak ada agenda asesmen milik akun {{ $owner->name }} yang sesuai dengan kriteria filter saat ini.
            @else
                Akun {{ $owner->name }} saat ini belum memiliki agenda asesmen terdaftar.
            @endif
        </p>
        @if(($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? ''))
            <div style="margin-top: 14px;">
                <button type="button" class="button secondary" data-role="reset-filter" style="font-size: 12px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="x" size="13" />
                    <span>Reset Filter</span>
                </button>
            </div>
        @endif
    </div>
@endif
