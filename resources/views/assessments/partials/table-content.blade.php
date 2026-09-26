@if($assessments->count())
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Agenda</th>
                    <th>LPK</th>
                    <th>Waktu</th>
                    <th>Tindakan Perbaikan (TP)</th>
                    <th>Status Asesmen</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assessments as $assessment)
                    <tr class="clickable-row" data-href="{{ route('assessments.show', $assessment) }}" tabindex="0" role="link" title="Klik baris untuk melihat detail asesmen {{ $assessment->title }}">
                        <td>
                            <strong>{{ $assessment->title }}</strong>
                            <span>{{ $assessment->assessment_type_label }}</span>
                            @if($assessment->sk_number)
                                <div style="margin-top: 3px;">
                                    <span class="badge-tp badge-tp-success" style="font-size: 10px; display: inline-block;" title="SK: {{ $assessment->sk_number }} {{ $assessment->sk_date ? '(' . $assessment->sk_date->format('d/m/Y') . ')' : '' }} {{ $assessment->sk_lead_time_label ? '• Rentang: ' . $assessment->sk_lead_time_label : '' }}">
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
                        <td>{{ $assessment->start_at->format('d M Y, H:i') }} WIB</td>
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
        {{ ($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? '') ? 'Tidak ada program asesmen yang cocok dengan filter.' : 'Belum ada program asesmen.' }}
        @if(($search ?? '') || ($lpkId ?? '') || ($assessmentType ?? '') || ($status ?? '') || ($tpFilter ?? '') || ($startFrom ?? '') || ($startTo ?? ''))
            <button type="button" class="button ghost empty-action" id="empty-reset-filter-btn" data-role="reset-filter">Reset filter</button>
        @endif
    </div>
@endif
