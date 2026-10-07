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
                    <tr class="clickable-row" data-href="{{ route('account-links.assessments.show', [$owner, $assessment]) }}" data-id="{{ $assessment->id }}" tabindex="0" role="link" title="Klik baris untuk melihat detail asesmen {{ $assessment->display_title }}">
                        <td>
                            <a href="{{ route('account-links.assessments.show', [$owner, $assessment]) }}" class="hover-underline" style="font-size: 13.5px; font-weight: 700; color: var(--ink); text-decoration: none; display: block;">
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
                                <div style="margin-top: 4px;">
                                    <span class="badge-tp badge-tp-success" style="font-size: 10px; display: inline-block;" title="SK: {{ $assessment->sk_number }} {{ $assessment->sk_date ? '(' . $assessment->sk_date . ')' : '' }} {{ $assessment->sk_lead_time_label ? '| Rentang: ' . $assessment->sk_lead_time_label : '' }}">
                                        SK: {{ $assessment->sk_number }}
                                        @if($assessment->sk_lead_time_days !== null)
                                            ({{ $assessment->sk_lead_time_days }} hr)
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('account-links.lpks.show', [$owner, $assessment->lpk]) }}" class="hover-underline" style="color: var(--ink); font-size: 13px; font-weight: 700; text-decoration: none; display: block;" title="Buka rincian LPK">
                                {{ $assessment->lpk->name }}
                            </a>
                            <span style="display: block; font-size: 11.5px; color: var(--muted); font-weight: 500; margin-top: 2px;">{{ $assessment->lpk->registration_number }}</span>
                        </td>
                        <td style="font-size: 12.5px; color: var(--ink);">
                            {{ $assessment->start_at->isSameDay($assessment->end_at) ? $assessment->start_at->format('d M Y') : $assessment->start_at->format('d M Y') . ' - ' . $assessment->end_at->format('d M Y') }}
                        </td>
                        <td>
                            @php $badge = $assessment->tp_sla_badge; @endphp
                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                <span class="badge-tp badge-tp-{{ $badge['type'] }}" title="{{ $badge['detail'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                @if($assessment->effective_tp_due_date && $assessment->tp_status !== \App\Models\Assessment::TP_STATUS_NONE)
                                    <small style="color: var(--muted); font-size: 11px;">
                                        Batas: {{ $assessment->effective_tp_due_date->format('d/m/Y') }}
                                    </small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                <x-status :value="$assessment->status" />
                                <span class="badge badge-viewer" title="Akses Viewer: Hanya Lihat">
                                    Viewer
                                </span>
                                @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                                    <span class="badge-tp badge-tp-danger" style="font-size: 10px; display: inline-block;" title="Telah melewati batas toleransi pembekuan 1 tahun tanpa penyelesaian">
                                        Akreditasi Dicabut
                                    </span>
                                @elseif($assessment->is_submission_overdue)
                                    <span class="badge-tp badge-tp-suspended" style="font-size: 10px; display: inline-block;" title="Toleransi pengisian dokumen surveilen telah terlampaui (akhir bulan kunjungan), sisa kesempatan pembekuan 1 tahun">
                                        Toleransi Terlampaui (Dibekukan)
                                    </span>
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
    <div style="padding: 48px 16px; text-align: center; background: var(--surface-subtle, var(--surface)); border: 1px dashed var(--line); border-radius: 8px; margin-top: 16px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 9999px; background: var(--neutral-chip-bg, #f1f5f9); color: var(--muted); margin-bottom: 12px;">
            <x-icon name="assessments" size="24" />
        </div>
        <div style="font-size: 14px; font-weight: 600; color: var(--ink);">Tidak Ada Program Asesmen Ditemukan</div>
        <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0; line-height: 1.5;">
            @if(($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? ''))
                Tidak ada agenda asesmen milik akun {{ $owner->name }} yang sesuai dengan kriteria filter saat ini.
            @else
                Akun {{ $owner->name }} saat ini belum memiliki agenda program asesmen terdaftar.
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
