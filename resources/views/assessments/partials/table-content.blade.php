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
                    <tr class="clickable-row" data-href="{{ route('assessments.show', $assessment) }}" data-id="{{ $assessment->id }}" tabindex="0" role="link" title="Klik baris untuk melihat detail asesmen {{ $assessment->display_title }}">
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
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-success" style="font-size: 10px; display: inline-block;" title="SK: {{ $assessment->sk_number }} {{ $assessment->sk_date ? '(' . $assessment->sk_date->format('d/m/Y') . ')' : '' }} {{ $assessment->sk_lead_time_label ? '| Rentang: ' . $assessment->sk_lead_time_label : '' }}">
                                        SK: {{ $assessment->sk_number }}
                                        @if($assessment->sk_lead_time_days !== null)
                                            ({{ $assessment->sk_lead_time_days }} hr)
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $assessment->lpk->name }}</strong>
                            <span style="display: block; font-size: 11.5px; color: var(--muted); font-weight: 500;">{{ $assessment->lpk->registration_number }}</span>
                        </td>
                        <td>{{ $assessment->start_at->isSameDay($assessment->end_at) ? $assessment->start_at->format('d M Y') : $assessment->start_at->format('d M Y') . ' - ' . $assessment->end_at->format('d M Y') }}</td>
                        <td>
                            @php
                                $badge = $assessment->tp_sla_badge;
                                $hasDueDate = $assessment->effective_tp_due_date && $assessment->tp_status !== \App\Models\Assessment::TP_STATUS_NONE;
                                $isTpDone = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_SATISFIED || !empty($assessment->tp_satisfied_at);
                            @endphp
                            @if($hasDueDate)
                                <strong style="font-size: 13.5px; font-weight: 700; color: var(--ink); display: block;" title="{{ $badge['label'] }} - {{ $badge['detail'] }}">{{ $assessment->effective_tp_due_date->format('d/m/Y') }}</strong>
                                @if($isTpDone)
                                    <span class="badge badge-countdown status-completed" style="font-size: 10px; padding: 1px 6px; margin-top: 3px; display: inline-block;">Memenuhi</span>
                                @else
                                    @php
                                        $diffTpDays = (int) now()->startOfDay()->diffInDays($assessment->effective_tp_due_date->startOfDay(), false);
                                    @endphp
                                    <span class="badge badge-countdown {{ $diffTpDays < 0 ? 'status-danger' : ($diffTpDays <= 14 ? 'status-warn' : 'status-scheduled') }}" style="font-size: 10px; padding: 1px 6px; margin-top: 3px; display: inline-block;">
                                        {{ $diffTpDays < 0 ? 'Terlewat ' . abs($diffTpDays) . ' Hari' : ($diffTpDays === 0 ? 'Hari Ini' : 'H-' . $diffTpDays) }}
                                    </span>
                                @endif
                            @else
                                <span style="color: var(--muted); font-size: 13px;">-</span>
                            @endif
                        </td>
                        <td>
                            <x-status :value="$assessment->status" />
                            @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-danger" style="font-size: 10px; display: inline-block;" title="Telah melewati batas toleransi pembekuan 1 tahun tanpa penyelesaian">
                                        Akreditasi Dicabut
                                    </span>
                                </div>
                            @elseif($assessment->is_submission_overdue)
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-suspended" style="font-size: 10px; display: inline-block;" title="Toleransi pengisian dokumen surveilen telah terlampaui (akhir bulan kunjungan), sisa kesempatan pembekuan 1 tahun">
                                        Toleransi Terlampaui (Dibekukan)
                                    </span>
                                </div>
                            @elseif($assessment->is_eha_needs_verification)
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-warning" style="font-size: 10px; display: inline-block;" title="Rapat Panitia Teknis telah dilaksanakan dengan hasil memerlukan verifikasi lanjutan sebelum rekomendasi akreditasi">
                                        Perlu Verifikasi Panitia Teknis
                                    </span>
                                </div>
                            @elseif($assessment->is_eha_special_notes)
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-warning" style="font-size: 10px; display: inline-block;" title="Rapat Panitia Teknis telah dilaksanakan dengan catatan khusus yang perlu ditindaklanjuti">
                                        Catatan Khusus Panitia Teknis
                                    </span>
                                </div>
                            @elseif($assessment->is_waiting_eha)
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-warning" style="font-size: 10px; display: inline-block;" title="Tindakan perbaikan telah memenuhi, menunggu pelaksanaan Rapat Panitia Teknis">
                                        Menunggu Rapat Panitia Teknis
                                    </span>
                                </div>
                            @elseif($assessment->is_waiting_sk)
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-warning" style="font-size: 10px; display: inline-block;" title="Rapat Panitia Teknis telah selesai / direkomendasikan, menunggu penetapan SK resmi dari KAN">
                                        Menunggu SK KAN
                                    </span>
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
