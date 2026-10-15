<div class="lpk-show-container">
    {{-- Navigasi Breadcrumb Kembali (Notion Style) --}}
    <div class="lpk-header-back-wrap">
        @if(isset($owner))
            <a href="{{ route('account-links.show', [$owner, 'tab' => 'assessments']) }}" class="lpk-back-btn" title="Kembali ke Program Asesmen Akun Tertaut">
                <x-icon name="chevron-left" size="16" />
                <span>Program Asesmen Akun: {{ $owner->name }}</span>
            </a>
        @else
            <a href="{{ route('assessments.index') }}" class="lpk-back-btn" title="Kembali ke {{ auth()->check() && auth()->user()->isPic() ? 'Jadwal Asesmen' : 'Program Asesmen' }}">
                <x-icon name="chevron-left" size="16" />
                <span>{{ auth()->check() && auth()->user()->isPic() ? 'Jadwal Asesmen' : 'Program Asesmen' }}</span>
            </a>
        @endif
    </div>

    {{-- Header Card dengan Judul, Status & Tombol Aksi --}}
    <div class="lpk-show-header assessment-header-hero">
        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
                <div class="assessment-header-title-row">
                    <h1 class="assessment-title">{{ $assessment->display_title }}</h1>
                    <div class="assessment-status-badge">
                        @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                            <span class="status status-revoked" title="Telah melewati batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen">
                                Dicabut (Lewat 1 Tahun)
                            </span>
                        @elseif($assessment->is_submission_overdue)
                            <span class="status status-suspended" title="Toleransi pengisian asesmen telah terlampaui, sisa kesempatan penyelesaian 1 tahun">
                                Dibekukan (Toleransi Terlampaui)
                            </span>
                        @else
                            <x-status :value="$assessment->status" />
                        @endif
                    </div>
                </div>

                <div class="assessment-header-meta">
                    <span class="assessment-meta-item">
                        <x-icon name="calendar" size="14" />
                        <span>
                            @if($assessment->start_at->isSameDay($assessment->end_at))
                                {{ $assessment->start_at->format('d M Y') }}
                            @else
                                {{ $assessment->start_at->format('d M') }} &ndash; {{ $assessment->end_at->format('d M Y') }}
                            @endif
                        </span>
                    </span>

                    @if($assessment->location)
                        <span class="meta-dot">&bull;</span>
                        <span class="assessment-meta-item">
                            <x-icon name="map-pin" size="14" />
                            <span>{{ $assessment->location }}</span>
                        </span>
                    @endif

                    @if(isset($owner))
                        <span class="meta-dot">&bull;</span>
                        <span class="badge" style="background: var(--mint, #d1fae5); color: var(--green, #047857); border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11px; font-weight: 600; padding: 1px 7px; border-radius: var(--radius-pill, 9999px);">
                            Viewer: {{ $owner->name }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="lpk-header-actions">
                <a class="button ghost" href="{{ route('calendar.index', ['view' => 'month', 'date' => $assessment->start_at->toDateString(), 'highlight' => $assessment->id, 'selected' => 1]) }}" title="Lompat ke tanggal agenda di kalender">
                    <x-icon name="calendar" size="14" />
                    <span>Buka di Kalender</span>
                </a>
                @if(!isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user())))
                    <a class="button secondary" href="{{ route('assessments.edit', $assessment) }}">
                        <x-icon name="edit" size="14" />
                        <span>Ubah asesmen</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Banner Notifikasi Status Pembekuan / Pencabutan --}}
    @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
        <div class="lpk-alert-callout">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <strong>Perhatian: Status Akreditasi Dicabut</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    Telah melewati batas waktu 1 tahun kesempatan penyelesaian masa pembekuan surveilen (batas akhir: <strong>{{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}</strong>) tanpa penyelesaian.
                </p>
            </div>
            @if(!isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user())))
                <div class="lpk-alert-callout-actions">
                    <a href="{{ route('assessments.create', ['lpk_id' => $assessment->lpk_id, 'assessment_type' => 'INITIAL', 'title' => 'Akreditasi Awal - ' . ($assessment->lpk?->name ?? '')]) }}" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-danger" title="Daftarkan permohonan asesmen akreditasi awal baru">
                        <x-icon name="plus" size="14" />
                        <span>Ajukan Akreditasi Awal</span>
                    </a>
                    <a href="{{ route('assessments.edit', $assessment) }}" class="button secondary button-sm lpk-alert-action-btn" title="Ubah atau perbarui data asesmen">
                        <x-icon name="edit" size="14" />
                        <span>Perbarui Data Asesmen</span>
                    </a>
                </div>
            @endif
        </div>
    @elseif($assessment->status === 'SUSPENDED' || $assessment->is_tp_overdue || $assessment->is_submission_overdue)
        <div class="lpk-alert-callout is-suspended">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <strong>Perhatian: Status Asesmen / Akreditasi Dibekukan</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    @if($assessment->is_submission_overdue)
                        Toleransi pengisian dokumen surveilen ({{ $assessment->submission_due_date ? $assessment->submission_due_date->format('d M Y') : '-' }}) telah terlampaui. Laboratorium diberikan masa tenggang toleransi 1 tahun untuk menuntaskan surveilen (batas akhir: <strong>{{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}</strong>, sisa <strong>{{ $assessment->days_remaining_suspension }} hari</strong>). Apabila kewajiban tidak dipenuhi dalam 1 tahun, status akreditasi resmi dicabut.
                    @else
                        Batas waktu awal tindakan perbaikan ({{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-' }}) telah terlampaui dan belum dinyatakan memenuhi. Laboratorium wajib segera menindaklanjuti temuan atau mengajukan permohonan perpanjangan waktu (+1 bulan) bersyarat ada progres perbaikan nyata.
                    @endif
                </p>
            </div>
            @if(!isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user())))
                <div class="lpk-alert-callout-actions">
                    <button type="button" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-suspended" onclick="window.openModal('modal-tp-tracking')" title="Buka modal untuk menandai status selesai, input tanggal laporan, atau input SK KAN">
                        <x-icon name="check-circle" size="14" />
                        <span>{{ $assessment->is_submission_overdue ? 'Tuntaskan Asesmen' : 'Selesaikan Status TP' }}</span>
                    </button>
                    <a href="{{ route('assessments.edit', $assessment) }}#form-sk-block" class="button secondary button-sm lpk-alert-action-btn" title="Langsung menuju blok pengisian Surat Keputusan (SK) KAN di formulir ubah">
                        <x-icon name="accreditations" size="14" />
                        <span>Input SK KAN</span>
                    </a>
                    <a href="{{ route('assessments.edit', $assessment) }}" class="button secondary button-sm lpk-alert-action-btn" title="Ubah tanggal pelaksanaan atau rincian agenda asesmen">
                        <x-icon name="edit" size="14" />
                        <span>Perbarui Asesmen</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- 2-Column Responsive Layout --}}
    <div class="lpk-show-grid assessment-show-grid">
        {{-- KOLOM KIRI: Informasi Pelaksanaan, Tim Asesor, Evaluasi EHA & SK KAN --}}
        <div class="lpk-show-col">
            {{-- KARTU 1: Detail Pelaksanaan Asesmen --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Informasi Agenda &amp; Pelaksanaan</h2>
                        <p>LPK terakreditasi, tanggal pelaksanaan, lokasi, dan tim asesor asesmen.</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    {{-- LPK Terakreditasi Highlight Card --}}
                    <div class="lpk-meta-item full-width" style="margin-bottom: 6px;">
                        <span class="lpk-meta-label">LPK Terakreditasi</span>
                        <div class="lpk-meta-value assessment-lpk-meta-value" style="width: 100%;">
                            @if(isset($owner))
                                <a href="{{ route('account-links.lpks.show', [$owner, $assessment->lpk]) }}" class="assessment-lpk-highlight-link" title="Buka detail LPK milik {{ $owner->name }}" style="display: flex; flex-direction: row; flex-wrap: nowrap; align-items: center; justify-content: space-between; width: 100%; box-sizing: border-box;">
                                    <div class="assessment-lpk-content" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; flex: 1 1 auto; min-width: 0;">
                                        <div class="assessment-lpk-badge">
                                            <x-icon name="building" size="14" />
                                            <span>{{ $assessment->lpk->registration_number }}</span>
                                        </div>
                                        <span class="assessment-lpk-name">{{ $assessment->lpk->name }}</span>
                                    </div>
                                    <x-icon name="chevron-right" size="18" class="assessment-lpk-arrow" style="flex: 0 0 auto; flex-shrink: 0; align-self: center; margin-left: 12px;" />
                                </a>
                            @else
                                <a href="{{ route('lpks.show', $assessment->lpk) }}" class="assessment-lpk-highlight-link" title="Buka detail LPK" style="display: flex; flex-direction: row; flex-wrap: nowrap; align-items: center; justify-content: space-between; width: 100%; box-sizing: border-box;">
                                    <div class="assessment-lpk-content" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; flex: 1 1 auto; min-width: 0;">
                                        <div class="assessment-lpk-badge">
                                            <x-icon name="building" size="14" />
                                            <span>{{ $assessment->lpk->registration_number }}</span>
                                        </div>
                                        <span class="assessment-lpk-name">{{ $assessment->lpk->name }}</span>
                                    </div>
                                    <x-icon name="chevron-right" size="18" class="assessment-lpk-arrow" style="flex: 0 0 auto; flex-shrink: 0; align-self: center; margin-left: 12px;" />
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Jenis Asesmen (KAN U-01)</span>
                        <div class="lpk-meta-value">
                            {{ $assessment->assessment_type_label }}
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Tanggal Pelaksanaan</span>
                        <div class="lpk-meta-value">
                            @if($assessment->start_at->isSameDay($assessment->end_at))
                                <strong>{{ $assessment->start_at->format('d M Y') }}</strong>
                            @else
                                <strong>{{ $assessment->start_at->format('d M Y') }}</strong> sampai <strong>{{ $assessment->end_at->format('d M Y') }}</strong>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Lokasi Kunjungan</span>
                        <div class="lpk-meta-value">
                            {{ $assessment->location ?: 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Tim Asesmen</span>
                        <div class="lpk-meta-value">
                            {{ $assessment->assessment_team ?: $assessment->lead_assessor ?: 'Belum diisi' }}
                        </div>
                    </div>

                    @if($assessment->submission_due_date && $assessment->status !== 'COMPLETED')
                        <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 10px; margin-top: 4px;">
                            <span class="lpk-meta-label">Toleransi Pengisian</span>
                            <div class="lpk-meta-value">
                                <strong>Maksimal {{ $assessment->submission_due_date->format('d M Y') }}</strong>
                                <span style="color: var(--muted); font-size: 12px; margin-left: 4px;">(masa pengisian bulan {{ str_contains(strtolower($assessment->assessment_type ?: ''), 's2') || str_contains(strtolower($assessment->title ?: ''), 's2') ? '36-39' : '15-18' }} siklus KAN)</span>
                                @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                                    <div style="margin-top: 4px; font-size: 12px; color: var(--danger-text, #ef4444); font-weight: 600;">
                                        &bull; Batas 1 tahun kesempatan pembekuan ({{ $assessment->suspension_resolution_deadline?->format('d M Y') }}) telah berakhir: Akreditasi dicabut.
                                    </div>
                                @elseif($assessment->is_submission_overdue || $assessment->status === 'SUSPENDED')
                                    <div style="margin-top: 4px; font-size: 12px; color: var(--terracotta, #c084fc); font-weight: 600;">
                                        &bull; Kesempatan penyelesaian pembekuan: 1 tahun s/d {{ $assessment->suspension_resolution_deadline?->format('d M Y') }} (sisa {{ $assessment->days_remaining_suspension }} hari).
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($assessment->notes)
                        <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 12px; margin-top: 4px;">
                            <span class="lpk-meta-label">Catatan Pelaksanaan</span>
                            <div class="assessment-notes-box">
                                {{ $assessment->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- KARTU 2: Laporan Asesmen, Evaluasi Hasil Asesmen (EHA) & SK KAN --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-rose">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Laporan Asesmen, Evaluasi EHA &amp; SK KAN</h2>
                        <p>Dokumen pelaporan asesmen, evaluasi panitia teknis, dan keputusan akreditasi.</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Laporan Asesmen</span>
                        <div class="lpk-meta-value">
                            @if($assessment->report_date)
                                Diterbitkan pada <strong>{{ $assessment->report_date->format('d M Y') }}</strong>
                            @else
                                <span style="color: var(--muted);">Belum diterbitkan</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Evaluasi Hasil Asesmen (EHA)</span>
                        <div class="lpk-meta-value">
                            @if($assessment->eha_date || ($assessment->eha_status && $assessment->eha_status !== 'BELUM_EHA') || $assessment->eha_notes)
                                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                                    <span class="badge-tp badge-tp-neutral">{{ $assessment->eha_status_label }}</span>
                                    @if($assessment->eha_date)
                                        <span style="color: var(--muted); font-size: 12px;">(Tanggal: {{ $assessment->eha_date->format('d M Y') }})</span>
                                    @endif
                                </div>
                                @if($assessment->eha_notes)
                                    <div style="font-size: 12.5px; color: var(--muted); margin-top: 4px; line-height: 1.45;">{{ $assessment->eha_notes }}</div>
                                @endif
                            @else
                                <span style="color: var(--muted);">Belum EHA</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 10px; margin-top: 4px;">
                        <span class="lpk-meta-label">Surat Keputusan (SK)</span>
                        <div class="lpk-meta-value">
                            @if($assessment->sk_number || $assessment->sk_date)
                                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    @if($assessment->sk_number)
                                        <strong style="color: var(--green); font-size: 14px;">{{ $assessment->sk_number }}</strong>
                                    @endif
                                    @if($assessment->sk_date)
                                        <span style="color: var(--muted); font-size: 12.5px;">(Terbit: {{ $assessment->sk_date->format('d M Y') }})</span>
                                    @endif
                                    @if($assessment->sk_lead_time_label)
                                        <span class="badge-tp badge-tp-neutral" style="font-size: 11px;" title="Rentang waktu pelaksanaan asesmen lapangan hingga terbit SK KAN">
                                             Rentang Proses: {{ $assessment->sk_lead_time_label }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span style="color: var(--muted);">Belum terbit</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Tindakan Perbaikan & Verifikasi (TP & VTP) Standar KAN --}}
        <div class="lpk-show-col">
            @include('assessments.partials.tp-tracking')
        </div>
    </div>
</div>

@if(!isset($owner))
    @include('assessments.partials.modal-tp-tracking')
@endif
