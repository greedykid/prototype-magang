@if($assessments->count())
    <div class="table-wrap">
        <table id="assessments-table">
            <thead>
                <tr>
                    <th class="col-th-checkbox" data-sortable="false" data-no-row-click="true">
                        <input type="checkbox" class="table-select-all" data-table-id="assessments-table" aria-label="Pilih semua asesmen di halaman ini">
                    </th>
                    <th data-sort-field="title">Agenda</th>
                    <th>Laboratorium</th>
                    <th data-sort-field="start_at">Tanggal</th>
                    <th>Tindakan Perbaikan (TP)</th>
                    <th data-sort-field="status">Status Asesmen</th>
                    <th data-sortable="false">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assessments as $assessment)
                    <tr class="clickable-row" data-href="{{ route('assessments.show', $assessment) }}" data-id="{{ $assessment->id }}" tabindex="0" title="Klik baris untuk melihat detail asesmen {{ $assessment->display_title }}">
                        <td class="col-td-checkbox" data-no-row-click="true">
                            <input type="checkbox" class="table-row-select" data-table-id="assessments-table" value="{{ $assessment->id }}" data-item-name="{{ $assessment->display_title }}" aria-label="Pilih asesmen {{ $assessment->display_title }}">
                        </td>
                        <td>
                            <strong>{{ $assessment->display_title }}</strong>
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
                                <span style="display: block; font-size: 11.5px; color: var(--muted); font-weight: 500;">{{ $typeLabel }}</span>
                            @endif
                            @if($assessment->sk_number)
                                <div class="assessment-sk-pill" title="SK: {{ $assessment->sk_number }} {{ $assessment->sk_date ? '(' . $assessment->sk_date->format('d/m/Y') . ')' : '' }} {{ $assessment->sk_lead_time_label ? '| Rentang: ' . $assessment->sk_lead_time_label : '' }}">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity: 0.7;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <code class="sk-code">SK: {{ $assessment->sk_number }}</code>
                                    @if($assessment->sk_lead_time_days !== null)
                                        <span class="sk-lead-time">({{ $assessment->sk_lead_time_days }} hr)</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $assessment->lpk->name }}</strong>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 2px;">
                                <span style="font-size: 11.5px; color: var(--muted); font-weight: 500;">{{ $assessment->lpk->registration_number }}</span>
                                @if($assessment->lpkLocation)
                                    <span class="badge-tp" style="display: inline-flex; align-items: center; gap: 3px; font-size: 11px; padding: 1px 6px; background: rgba(2, 132, 199, 0.08); border-color: rgba(2, 132, 199, 0.25); color: var(--primary); font-weight: 600; border-radius: var(--radius-pill);">
                                        <x-icon name="map-pin" size="10" />
                                        <span>{{ $assessment->lpkLocation->name }}</span>
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td style="white-space: nowrap;">{{ $assessment->start_at->isSameDay($assessment->end_at) ? $assessment->start_at->format('d M Y') : $assessment->start_at->format('d M Y') . ' - ' . $assessment->end_at->format('d M Y') }}</td>
                        <td>
                            @php
                                $badge = $assessment->tp_sla_badge;
                                $hasDueDate = $assessment->effective_tp_due_date && $assessment->tp_status !== \App\Models\Assessment::TP_STATUS_NONE;
                                $isTpDone = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_SATISFIED || !empty($assessment->tp_satisfied_at);
                            @endphp
                            @if($hasDueDate)
                                <strong style="font-size: 13.5px; font-weight: 600; color: var(--ink); display: block;" title="{{ $badge['label'] }} - {{ $badge['detail'] }}">{{ $assessment->effective_tp_due_date->format('d/m/Y') }}</strong>
                                @if($isTpDone)
                                    <span class="badge badge-countdown status-completed" style="margin-top: 3px; display: inline-flex;">Memenuhi</span>
                                @else
                                    @php
                                        $diffTpDays = (int) now()->startOfDay()->diffInDays($assessment->effective_tp_due_date->startOfDay(), false);
                                    @endphp
                                    <span class="badge badge-countdown {{ $diffTpDays < 0 ? 'status-danger' : ($diffTpDays <= 14 ? 'status-warn' : 'status-scheduled') }}" style="margin-top: 3px; display: inline-flex;">
                                        {{ $diffTpDays < 0 ? 'Terlewat ' . abs($diffTpDays) . ' Hari' : ($diffTpDays === 0 ? 'Hari Ini' : 'H-' . $diffTpDays) }}
                                    </span>
                                @endif
                            @else
                                <span style="color: var(--muted); font-size: 13.5px;">-</span>
                            @endif
                        </td>
                        <td>
                            <x-status :value="$assessment->status" />
                            @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                                <div class="assessment-status-subtag is-danger" title="Telah melewati batas toleransi pembekuan 1 tahun tanpa penyelesaian">
                                    Lewat Batas 1 Thn
                                </div>
                            @elseif($assessment->is_submission_overdue)
                                <div class="assessment-status-subtag is-danger" title="Toleransi pengisian dokumen surveilen telah terlampaui (akhir bulan kunjungan), sisa kesempatan pembekuan 1 tahun">
                                    Toleransi Lewat
                                </div>
                            @elseif($assessment->is_eha_needs_verification)
                                <div class="assessment-status-subtag is-warning" title="Rapat Panitia Teknis telah dilaksanakan dengan hasil memerlukan verifikasi lanjutan sebelum rekomendasi akreditasi">
                                    Verifikasi Panitia Teknis
                                </div>
                            @elseif($assessment->is_eha_special_notes)
                                <div class="assessment-status-subtag is-warning" title="Rapat Panitia Teknis telah dilaksanakan dengan catatan khusus yang perlu ditindaklanjuti">
                                    Catatan Panitia Teknis
                                </div>
                            @elseif($assessment->is_waiting_eha)
                                <div class="assessment-status-subtag is-info" title="Tindakan perbaikan telah memenuhi, menunggu pelaksanaan Rapat Panitia Teknis">
                                    Menunggu Rapat Panitia Teknis
                                </div>
                            @elseif($assessment->is_waiting_sk)
                                <div class="assessment-status-subtag is-info" title="Rapat Panitia Teknis telah selesai / direkomendasikan, menunggu penetapan SK resmi dari KAN">
                                    Menunggu SK KAN
                                </div>
                            @endif
                        </td>
                        <td><a href="{{ route('assessments.show', $assessment) }}">Detail</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $assessments->links() }}
@else
    <div class="empty">
        <div class="empty-icon-wrap" aria-hidden="true">
            <x-icon name="search" size="22" />
        </div>
        <strong class="empty-title">
            {{ ($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? '') ? 'Tidak Ada Asesmen yang Cocok' : 'Belum Ada Daftar Asesmen' }}
        </strong>
        <p class="empty-desc">
            {{ ($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? '') ? 'Tidak ada asesmen yang cocok dengan filter.' : 'Belum ada daftar asesmen.' }}
        </p>
        @if(($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? ''))
            <button type="button" class="button secondary empty-action" id="empty-reset-filter-btn" data-role="reset-filter">
                <x-icon name="x" size="14" />
                <span>Reset filter</span>
            </button>
        @endif
    </div>
@endif
