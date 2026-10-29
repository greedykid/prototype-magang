<div class="lpk-show-container">
    {{-- Navigasi Breadcrumb Kembali (Notion Style) --}}
    <div class="lpk-header-back-wrap">
        @if(isset($owner))
            <a href="{{ route('account-links.show', [$owner, 'tab' => 'assessments']) }}" class="lpk-back-btn" title="Kembali ke Daftar Asesmen Akun Tertaut">
                <x-icon name="chevron-left" size="16" />
                <span>Program Asesmen Akun: {{ $owner->name }}</span>
            </a>
        @else
            <a href="{{ route('assessments.index') }}" class="lpk-back-btn" title="Kembali ke Daftar Asesmen">
                <x-icon name="chevron-left" size="16" />
                <span>Daftar Asesmen</span>
            </a>
        @endif
    </div>

    {{-- Header Card dengan Judul, Status & Tombol Aksi --}}
    <div class="lpk-show-header assessment-header-hero">
        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
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
                    @elseif($assessment->is_eha_needs_verification)
                        <span class="status status-in_progress" title="Rapat Panitia Teknis telah dilaksanakan dengan hasil memerlukan verifikasi lanjutan">
                            Sedang Berlangsung (Perlu Verifikasi Panitia Teknis)
                        </span>
                    @elseif($assessment->is_eha_special_notes)
                        <span class="status status-in_progress" title="Rapat Panitia Teknis telah dilaksanakan dengan catatan khusus panitia teknis">
                            Sedang Berlangsung (Catatan Khusus Panitia Teknis)
                        </span>
                    @elseif($assessment->is_waiting_eha)
                        <span class="status status-in_progress" title="Tindakan perbaikan telah memenuhi, menunggu pelaksanaan Rapat Panitia Teknis">
                            Sedang Berlangsung (Menunggu Rapat Panitia Teknis)
                        </span>
                    @elseif($assessment->is_waiting_sk)
                        <span class="status status-in_progress" title="Rapat Panitia Teknis telah selesai / merekomendasikan, menunggu penerbitan SK resmi dari KAN">
                            Sedang Berlangsung (Menunggu SK KAN)
                        </span>
                    @else
                        <x-status :value="$assessment->status" />
                    @endif
                </div>

                <div class="assessment-header-meta">
                    <span class="assessment-meta-item">
                        <x-icon name="calendar" size="14" />
                        <span>
                            @if($assessment->start_at->isSameDay($assessment->end_at))
                                {{ $assessment->start_at->format('d M Y') }}
                            @else
                                {{ $assessment->start_at->format('d M') }} - {{ $assessment->end_at->format('d M Y') }}
                            @endif
                        </span>
                    </span>

                    @if($assessment->lpkLocation)
                        <span class="meta-dot">&bull;</span>
                        <span class="assessment-meta-item">
                            <x-icon name="map-pin" size="14" />
                            <strong style="color: var(--primary);">{{ $assessment->lpkLocation->name }}</strong>
                            @if($assessment->location && $assessment->location !== $assessment->lpkLocation->name && $assessment->location !== $assessment->lpkLocation->address)
                                <span style="color: var(--muted); margin-left: 4px;">({{ $assessment->location }})</span>
                            @endif
                        </span>
                    @elseif($assessment->location)
                        <span class="meta-dot">&bull;</span>
                        <span class="assessment-meta-item">
                            <x-icon name="map-pin" size="14" />
                            <span>{{ $assessment->location }}</span>
                        </span>
                    @endif

                    @if(isset($owner))
                        <span class="meta-dot">&bull;</span>
                        <span class="badge" style="background: var(--mint, #d1fae5); color: var(--green, #047857); border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11px; font-weight: 600; padding: 1px 7px; border-radius: var(--radius-pill, 9999px);">
                            Mode Pemantauan (Viewer) - Milik Akun: {{ $owner->name }}
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
        <div class="lpk-alert-callout is-revoked">
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
                        Tenggat waktu tindakan perbaikan ({{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-' }}) telah terlampaui dan belum dinyatakan memenuhi. Laboratorium wajib segera menindaklanjuti temuan atau mengajukan permohonan perpanjangan waktu (+1 bulan) bersyarat ada progres perbaikan nyata.
                    @endif
                </p>
            </div>
            @if(!isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user())))
                <div class="lpk-alert-callout-actions">
                    <button type="button" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-suspended" onclick="window.openModal('modal-tp-tracking', '{{ $assessment->is_submission_overdue ? 'sk' : 'tp' }}')" title="Buka modal pembaruan tindak lanjut untuk verifikasi TP, rapat panitia teknis, atau nomor SK KAN">
                        <x-icon name="check-circle" size="14" />
                        <span>{{ $assessment->is_submission_overdue ? 'Tuntaskan Asesmen' : 'Tindak Lanjut Asesmen' }}</span>
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

    {{-- Stepper 4 Tahapan KAN U-01 (Kunjungan -> TP -> Rapat Panitia Teknis -> SK) --}}
    @php
        $visitStart = $assessment->start_at ? $assessment->start_at->copy()->startOfDay() : null;
        $visitEnd = $assessment->end_at ? $assessment->end_at->copy()->endOfDay() : ($visitStart ? $visitStart->copy()->endOfDay() : null);

        // Tahap 1: Kunjungan Lapangan
        $isFieldDone = ($visitEnd && now()->gt($visitEnd))
            || !empty($assessment->report_date)
            || in_array($assessment->tp_status, [\App\Models\Assessment::TP_STATUS_IN_PROGRESS, \App\Models\Assessment::TP_STATUS_UNDER_VERIFICATION, \App\Models\Assessment::TP_STATUS_SATISFIED], true)
            || $assessment->isPastSurveillanceForActiveLpk();
        $isFieldInProgress = !$isFieldDone && $visitStart && $visitEnd && now()->between($visitStart, $visitEnd);
        $isFieldCurrent = !$isFieldDone && ($isFieldInProgress || in_array($assessment->status, ['SCHEDULED', 'IN_PROGRESS', 'PLANNED']));

        // Tahap 2: Tindakan Perbaikan (TP & VTP)
        $isTpSatisfiedRaw = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_SATISFIED || !empty($assessment->tp_satisfied_at) || $assessment->isPastSurveillanceForActiveLpk();
        $isTpSatisfied = $isFieldDone && $isTpSatisfiedRaw;
        $isTpCurrent = $isFieldDone && !$isTpSatisfied;

        // Tahap 3: Rapat Panitia Teknis
        $isEhaSpecialNotes = $isTpSatisfied && ($assessment->eha_status === \App\Models\Assessment::EHA_STATUS_CATATAN_KHUSUS);
        $isEhaNeedsVerification = $isTpSatisfied && ($assessment->eha_status === \App\Models\Assessment::EHA_STATUS_PERLU_VERIFIKASI);
        $isEhaDoneRaw = (in_array($assessment->eha_status, [\App\Models\Assessment::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
            || (!empty($assessment->eha_date) && !in_array($assessment->eha_status, [\App\Models\Assessment::EHA_STATUS_BELUM, \App\Models\Assessment::EHA_STATUS_PERLU_VERIFIKASI, \App\Models\Assessment::EHA_STATUS_CATATAN_KHUSUS], true)))
            || $assessment->isPastSurveillanceForActiveLpk();
        $isEhaDone = $isTpSatisfied && $isEhaDoneRaw && !$isEhaSpecialNotes && !$isEhaNeedsVerification;
        $isEhaCurrent = $isTpSatisfied && !$isEhaDone;

        // Tahap 4: Surat Keputusan (SK)
        $hasSkData = !empty($assessment->sk_number);
        $isSkDone = ($isEhaDone && $hasSkData) || $assessment->isPastSurveillanceForActiveLpk();
        $isSkCurrent = !$isSkDone && $isEhaDone;
        $isSkDraftRewound = $hasSkData && !$isSkDone;
    @endphp

    <div class="assessment-stepper-cluster">
        <div class="lpk-cycle-timeline-bar assessment-workflow-timeline" aria-label="Alur 4 Tahapan Asesmen KAN U-01">
        {{-- Tahap 1: Kunjungan Lapangan --}}
        <div class="lpk-cycle-step-item {{ $isFieldDone ? 'is-completed' : ($isFieldCurrent ? 'is-current' : 'is-upcoming') }}">
            <div class="lpk-step-bullet">
                @if($isFieldDone)
                    <x-icon name="check" size="13" />
                @else
                    <span>1</span>
                @endif
            </div>
            <div class="lpk-step-label-group">
                <strong class="lpk-step-name">Asesmen Lapangan</strong>
                <span class="lpk-step-timing">
                    @if($isFieldDone)
                        {{ $assessment->end_at ? $assessment->end_at->format('d M Y') : 'Selesai' }}
                    @elseif($isFieldInProgress)
                        Sedang Berlangsung
                    @elseif($assessment->start_at)
                        {{ $assessment->start_at->format('d M Y') }}
                    @else
                        Terjadwal
                    @endif
                </span>
            </div>
        </div>

        <div class="lpk-cycle-step-line {{ $isFieldDone ? 'is-passed' : '' }}"></div>

        {{-- Tahap 2: Tindakan Perbaikan (TP) --}}
        <div class="lpk-cycle-step-item {{ $isTpSatisfied ? 'is-completed' : ($isTpCurrent ? 'is-current' : 'is-upcoming') }}">
            <div class="lpk-step-bullet">
                @if($isTpSatisfied)
                    <x-icon name="check" size="13" />
                @else
                    <span>2</span>
                @endif
            </div>
            <div class="lpk-step-label-group">
                <strong class="lpk-step-name">Perbaikan TP</strong>
                <span class="lpk-step-timing">{{ $isTpSatisfied ? 'Memenuhi' : ($assessment->effective_tp_due_date ? 'Batas: ' . $assessment->effective_tp_due_date->format('d/m/Y') : 'SLA KAN') }}</span>
            </div>
        </div>

        <div class="lpk-cycle-step-line {{ $isTpSatisfied ? 'is-passed' : '' }}"></div>

        {{-- Tahap 3: Rapat Panitia Teknis --}}
        <div class="lpk-cycle-step-item {{ $isEhaDone ? 'is-completed' : ($isEhaCurrent ? 'is-current' : 'is-upcoming') }}">
            <div class="lpk-step-bullet">
                @if($isEhaDone)
                    <x-icon name="check" size="13" />
                @else
                    <span>3</span>
                @endif
            </div>
            <div class="lpk-step-label-group">
                <strong class="lpk-step-name">Rapat Panitia Teknis</strong>
                <span class="lpk-step-timing">{{ $isEhaDone ? 'Rekomendasi' : ($isEhaSpecialNotes ? 'Catatan Khusus' : ($isEhaNeedsVerification ? 'Perlu Verifikasi' : ($assessment->eha_date ? $assessment->eha_date->format('d/m/Y') : 'Panitia Teknis'))) }}</span>
            </div>
        </div>

        <div class="lpk-cycle-step-line {{ $isEhaDone ? 'is-passed' : '' }}"></div>

        {{-- Tahap 4: Surat Keputusan (SK) --}}
        <div class="lpk-cycle-step-item {{ $isSkDone ? 'is-completed' : ($isSkDraftRewound ? 'is-draft-saved' : ($isSkCurrent ? 'is-current' : 'is-upcoming')) }}">
            <div class="lpk-step-bullet">
                @if($isSkDone)
                    <x-icon name="check" size="13" />
                @elseif($isSkDraftRewound)
                    <x-icon name="file-text" size="13" />
                @else
                    <span>4</span>
                @endif
            </div>
            <div class="lpk-step-label-group">
                <strong class="lpk-step-name">SK Akreditasi</strong>
                <span class="lpk-step-timing">
                    @if($isSkDone)
                        {{ $assessment->sk_number ?: 'Terbit' }}
                    @elseif($isSkDraftRewound)
                        Draft SK Tersimpan
                    @elseif($isSkCurrent)
                        Menunggu SK
                    @else
                        Keputusan KAN
                    @endif
                </span>
            </div>
        </div>
    </div>

    @php
        $ctaPhaseClass = 'is-phase-field';
        if (!$isFieldDone) {
            $ctaPhaseClass = 'is-phase-field';
        } elseif (!$isTpSatisfied) {
            $ctaPhaseClass = 'is-phase-tp';
        } elseif ($isEhaNeedsVerification) {
            $ctaPhaseClass = 'is-phase-eha-verify';
        } elseif ($isEhaSpecialNotes) {
            $ctaPhaseClass = 'is-phase-eha-notes';
        } elseif (!$isEhaDone) {
            $ctaPhaseClass = 'is-phase-eha';
        } else {
            $ctaPhaseClass = 'is-phase-sk';
        }
    @endphp

    {{-- Direct CTA Bar Sesuai Tahapan KAN U-01 yang Sedang Aktif --}}
    @if(!$isSkDone)
        <div class="assessment-stepper-cta-box {{ $ctaPhaseClass }}">
            <div class="assessment-stepper-cta-left">
                <div class="assessment-stepper-cta-icon" aria-hidden="true">
                    @if(!$isFieldDone)
                        @if($isFieldInProgress)
                            <x-icon name="clock" size="16" />
                        @else
                            <x-icon name="calendar" size="16" />
                        @endif
                    @elseif(!$isTpSatisfied)
                        <x-icon name="alert-triangle" size="16" />
                    @elseif($isEhaNeedsVerification || $isEhaSpecialNotes)
                        <x-icon name="alert-triangle" size="16" />
                    @elseif(!$isEhaDone)
                        <x-icon name="check-circle" size="16" />
                    @else
                        <x-icon name="accreditations" size="16" />
                    @endif
                </div>
                <div class="assessment-stepper-cta-text">
                    @if(!$isFieldDone)
                        @if($isFieldInProgress)
                            <span class="assessment-stepper-cta-title">Fase Berjalan: Pelaksanaan Asesmen Lapangan Sedang Berlangsung</span>
                            <span class="assessment-stepper-cta-desc">Kunjungan asesmen lapangan sedang berlangsung di lokasi laboratorium ({{ $assessment->start_at?->format('d M') }} - {{ $assessment->end_at?->format('d M Y') }}).</span>
                        @else
                            <span class="assessment-stepper-cta-title">Fase Berjalan: Menunggu Pelaksanaan Kunjungan Lapangan</span>
                            <span class="assessment-stepper-cta-desc">Jadwal kunjungan lapangan direncanakan pada {{ $assessment->start_at ? $assessment->start_at->format('d M Y') : 'jadwal yang telah ditentukan' }}. Pastikan tim asesor dan dokumen telah siap.</span>
                        @endif
                    @elseif(!$isTpSatisfied)
                        @if($isSkDraftRewound)
                            <span class="assessment-stepper-cta-title">Perhatian: Draft SK Tersimpan, Selesaikan Verifikasi TP Terlebih Dahulu</span>
                            <span class="assessment-stepper-cta-desc">Nomor SK KAN telah tersimpan di sistem, namun status kelulusan belum dapat disahkan karena Tindakan Perbaikan (Tahap 2) sedang aktif atau memerlukan verifikasi tindak lanjut.</span>
                        @else
                            <span class="assessment-stepper-cta-title">Fase Berjalan: Tindak Lanjut Perbaikan (TP/VTP)</span>
                            <span class="assessment-stepper-cta-desc">{{ $assessment->effective_tp_due_date ? 'Batas waktu perbaikan s/d ' . $assessment->effective_tp_due_date->format('d M Y') . ($assessment->tp_days_remaining !== null ? ' (' . $assessment->tp_days_remaining . ' hari lagi)' : '') : 'Menunggu penyelesaian dan verifikasi bukti perbaikan temuan asesmen.' }}</span>
                        @endif
                    @elseif($isEhaNeedsVerification)
                        <span class="assessment-stepper-cta-title">Fase Berjalan: Verifikasi Panitia Teknis Lanjutan</span>
                        <span class="assessment-stepper-cta-desc">Rapat Panitia Teknis telah dilaksanakan dengan catatan memerlukan verifikasi lanjutan sebelum rekomendasi akreditasi diterbitkan.</span>
                    @elseif($isEhaSpecialNotes)
                        <span class="assessment-stepper-cta-title">Fase Berjalan: Tindak Lanjut Catatan Khusus Panitia Teknis</span>
                        <span class="assessment-stepper-cta-desc">Rapat Panitia Teknis telah dilaksanakan dengan catatan khusus panitia teknis. Tindak lanjuti catatan sebelum rekomendasi akreditasi diterbitkan.</span>
                    @elseif(!$isEhaDone)
                        @if($isSkDraftRewound)
                            <span class="assessment-stepper-cta-title">Perhatian: Draft SK Tersimpan, Selesaikan Rapat Panitia Teknis Terlebih Dahulu</span>
                            <span class="assessment-stepper-cta-desc">Nomor SK KAN telah tersimpan di sistem, namun asesmen memerlukan rekomendasi Rapat Panitia Teknis (Tahap 3) sebelum dapat disahkan menjadi Selesai.</span>
                        @else
                            <span class="assessment-stepper-cta-title">Fase Berjalan: Rapat Panitia Teknis</span>
                            <span class="assessment-stepper-cta-desc">Perbaikan temuan telah memenuhi. Agenda berikutnya adalah pencatatan Rapat Panitia Teknis.</span>
                        @endif
                    @else
                        <span class="assessment-stepper-cta-title">Fase Berjalan: Penetapan Surat Keputusan (SK) KAN</span>
                        <span class="assessment-stepper-cta-desc">Rapat Panitia Teknis telah merekomendasikan akreditasi. Masukkan nomor dan tanggal SK untuk menuntaskan siklus.</span>
                    @endif
                </div>
            </div>
            @if(!isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user())))
                <div class="assessment-stepper-cta-actions">
                    @if(!$isFieldDone)
                        <button type="button" class="button primary button-sm" onclick="window.openModal('modal-complete-field-assessment')" title="Selesaikan pelaksanaan kunjungan asesmen lapangan">
                            <x-icon name="check-circle" size="14" />
                            <span>Selesaikan Pelaksanaan Lapangan</span>
                        </button>
                    @elseif(!$isTpSatisfied)
                        <button type="button" class="button primary button-sm" onclick="window.openModal('modal-tp-tracking', 'tp')">
                            <x-icon name="check-circle" size="14" />
                            <span>Verifikasi Tindak Lanjut TP</span>
                        </button>
                    @elseif($isEhaNeedsVerification)
                        <button type="button" class="button primary button-sm" onclick="window.openModal('modal-tp-tracking', 'eha')">
                            <x-icon name="alert-triangle" size="14" />
                            <span>Tindak Lanjut Verifikasi Panitia Teknis</span>
                        </button>
                    @elseif($isEhaSpecialNotes)
                        <button type="button" class="button primary button-sm" onclick="window.openModal('modal-tp-tracking', 'eha')">
                            <x-icon name="alert-triangle" size="14" />
                            <span>Tindak Lanjut Catatan Panitia Teknis</span>
                        </button>
                    @elseif(!$isEhaDone)
                        <button type="button" class="button primary button-sm" onclick="window.openModal('modal-tp-tracking', 'eha')">
                            <x-icon name="check-circle" size="14" />
                            <span>Catat Rekomendasi Panitia Teknis</span>
                        </button>
                    @else
                        <button type="button" class="button primary button-sm" onclick="window.openModal('modal-tp-tracking', 'sk')">
                            <x-icon name="check" size="14" />
                            <span>Input SK KAN Resmi</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    @else
        <div class="assessment-stepper-cta-box is-phase-completed">
            <div class="assessment-stepper-cta-left">
                <div class="assessment-stepper-cta-icon" aria-hidden="true">
                    <x-icon name="check" size="16" />
                </div>
                <div class="assessment-stepper-cta-text">
                    <span class="assessment-stepper-cta-title">Siklus Asesmen Telah Tuntas</span>
                    <span class="assessment-stepper-cta-desc">{{ $assessment->sk_number ? 'Surat Keputusan (SK) resmi telah terbit: ' . $assessment->sk_number . ($assessment->sk_date ? ' (' . $assessment->sk_date->format('d M Y') . ')' : '') : 'Seluruh tahapan asesmen telah terlaksana dan dinyatakan selesai.' }}</span>
                </div>
            </div>
            @if(!isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user())))
                <div class="assessment-stepper-cta-actions">
                    <button type="button" class="button secondary button-sm" onclick="window.openModal('modal-tp-tracking', 'sk')">
                        <x-icon name="accreditations" size="14" />
                        <span>Perbarui Data SK</span>
                    </button>
                </div>
            @endif
        </div>
    @endif
    </div>

    {{-- 2-Column Responsive Layout (Cockpit Architecture) --}}
    <div class="lpk-show-grid assessment-show-grid">
        {{-- KOLOM KIRI: Identitas LPK & Agenda Pelaksanaan Asesmen --}}
        <div class="lpk-show-col">
            {{-- KARTU 1: Entitas LPK & Lokasi Fasilitas --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-blue">
                        <x-icon name="building" size="20" />
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Entitas LPK &amp; Lokasi Fasilitas</h2>
                        <p>Informasi lembaga terakreditasi dan detail lokasi pelaksanaan asesmen.</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    {{-- Entitas LPK Highlight Box --}}
                    <div class="lpk-meta-item full-width">
                        <span class="lpk-meta-label">Lembaga Penilaian Kesesuaian (LPK)</span>
                        <div class="assessment-lpk-entity-card">
                            @if(isset($owner))
                                <a href="{{ route('account-links.lpks.show', [$owner, $assessment->lpk]) }}" class="assessment-lpk-highlight-link" title="Buka detail LPK milik {{ $owner->name }}">
                                    <div class="assessment-lpk-content">
                                        <div class="assessment-lpk-badge">
                                            <x-icon name="building" size="14" />
                                            <span>{{ $assessment->lpk->registration_number }}</span>
                                        </div>
                                        <span class="assessment-lpk-name">{{ $assessment->lpk->name }}</span>
                                    </div>
                                    <x-icon name="chevron-right" size="18" class="assessment-lpk-arrow" />
                                </a>
                            @else
                                <a href="{{ route('lpks.show', $assessment->lpk) }}" class="assessment-lpk-highlight-link" title="Buka detail LPK">
                                    <div class="assessment-lpk-content">
                                        <div class="assessment-lpk-badge">
                                            <x-icon name="building" size="14" />
                                            <span>{{ $assessment->lpk->registration_number }}</span>
                                        </div>
                                        <span class="assessment-lpk-name">{{ $assessment->lpk->name }}</span>
                                    </div>
                                    <x-icon name="chevron-right" size="18" class="assessment-lpk-arrow" />
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Lokasi Pelaksanaan Asesmen Box --}}
                    <div class="lpk-meta-item full-width">
                        <span class="lpk-meta-label">Lokasi Pelaksanaan Asesmen</span>
                        @php
                            $loc = $assessment->lpkLocation;
                            $locName = $loc ? $loc->name : ($assessment->location ?: 'Kantor Pusat / Fasilitas Utama');
                            $isMainLoc = $loc ? $loc->is_main : true;
                            $locAddress = $loc ? $loc->address : ($assessment->lpk->address ?: null);
                            $locPic = $loc ? $loc->contact_person : ($assessment->lpk->contact_person ?: null);
                            $locPhone = $loc ? $loc->contact_phone : ($assessment->lpk->contact_phone ?: null);
                            $locEmail = $loc ? $loc->contact_email : ($assessment->lpk->contact_email ?: null);
                            $locScopeNotes = $loc ? $loc->scope_notes : null;
                        @endphp
                        <div class="assessment-location-detail-box">
                            <div class="location-detail-head">
                                <div class="location-detail-name">
                                    <x-icon name="map-pin" size="16" class="location-pin-icon" />
                                    <strong>{{ $locName }}</strong>
                                </div>
                                <span class="badge-tp {{ $isMainLoc ? 'badge-tp-info' : 'badge-tp-neutral' }}" style="font-size: 11px;">
                                    {{ $isMainLoc ? 'Lokasi Utama' : 'Lokasi Cabang' }}
                                </span>
                            </div>

                            @if($locAddress)
                                <p class="location-detail-address">
                                    {{ $locAddress }}
                                </p>
                            @endif

                            @if($locPic || $locPhone || $locEmail)
                                <div class="location-contact-chips">
                                    @if($locPic)
                                        <span class="badge-tp badge-tp-neutral" style="font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                            <x-icon name="user" size="12" />
                                            <span>{{ $locPic }}</span>
                                        </span>
                                    @endif
                                    @if($locPhone)
                                        <span class="badge-tp badge-tp-neutral" style="font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                            <x-icon name="phone" size="12" />
                                            <span>{{ $locPhone }}</span>
                                        </span>
                                    @endif
                                    @if($locEmail)
                                        <span class="badge-tp badge-tp-neutral" style="font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                            <x-icon name="mail" size="12" />
                                            <span>{{ $locEmail }}</span>
                                        </span>
                                    @endif
                                </div>
                            @endif

                            @if($locScopeNotes)
                                <div style="font-size: 11.5px; color: var(--muted); margin-top: 4px; padding-top: 6px; border-top: 1px dashed var(--line);">
                                    <strong style="color: var(--ink);">Ruang Lingkup Khusus:</strong> {{ $locScopeNotes }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU 2: Agenda & Ketentuan Waktu --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-blue">
                        <x-icon name="calendar" size="20" />
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Agenda &amp; Ketentuan Waktu</h2>
                        <p>Jadwal kunjungan lapangan, tim asesor, dan toleransi siklus akreditasi.</p>
                    </div>
                </div>

                <div class="assessment-milestones-list">
                    {{-- Item 1: Jadwal Kunjungan Lapangan --}}
                    <div class="assessment-milestone-item">
                        <div class="milestone-item-header">
                            <div style="display: flex; align-items: center; gap: 7px;">
                                <x-icon name="clock" size="14" style="color: var(--primary);" />
                                <span class="milestone-item-label">Jadwal Kunjungan Lapangan</span>
                            </div>
                            <span class="badge-tp badge-tp-{{ $assessment->status === 'COMPLETED' ? 'satisfied' : ($assessment->status === 'IN_PROGRESS' ? 'warning' : 'neutral') }}">
                                {{ $assessment->status_label }}
                            </span>
                        </div>
                        <div class="milestone-item-value">
                            @if($assessment->start_at && $assessment->end_at)
                                @php
                                    $sDate = $assessment->start_at->copy()->startOfDay();
                                    $eDate = $assessment->end_at->copy()->startOfDay();
                                    $fieldDays = (int) $sDate->diffInDays($eDate) + 1;
                                @endphp
                                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    <strong style="color: var(--ink); font-size: 13.5px;">
                                        {{ $assessment->start_at->translatedFormat('d M Y') }} &ndash; {{ $assessment->end_at->translatedFormat('d M Y') }}
                                    </strong>
                                    <span class="badge-tp badge-tp-neutral" style="font-size: 11px;">
                                        {{ $fieldDays }} Hari Kalender
                                    </span>
                                </div>
                                @if($assessment->status === 'COMPLETED')
                                    <div style="font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 5px; margin-top: 2px;">
                                        <x-icon name="check" size="13" style="color: var(--green);" />
                                        <span>Pelaksanaan audit lapangan telah tuntas dilaksanakan.</span>
                                    </div>
                                @elseif($assessment->status === 'IN_PROGRESS')
                                    <div style="font-size: 12px; color: var(--terracotta, #c084fc); display: flex; align-items: center; gap: 5px; margin-top: 2px;">
                                        <x-icon name="clock" size="13" />
                                        <span>Asesmen lapangan saat ini dalam periode pelaksanaan.</span>
                                    </div>
                                @else
                                    <div style="font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 5px; margin-top: 2px;">
                                        <x-icon name="clock" size="13" />
                                        <span>
                                            @php $daysRemaining = (int) now()->startOfDay()->diffInDays($sDate, false); @endphp
                                            @if($daysRemaining > 0)
                                                Dimulai dalam {{ $daysRemaining }} hari ke depan.
                                            @elseif($daysRemaining === 0)
                                                Pelaksanaan dimulai hari ini.
                                            @else
                                                Jadwal kunjungan telah lewat.
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @elseif($assessment->start_at)
                                <strong style="color: var(--ink); font-size: 13.5px;">
                                    {{ $assessment->start_at->translatedFormat('d M Y') }}
                                </strong>
                            @else
                                <span style="color: var(--muted); font-size: 12px;">Tanggal kunjungan lapangan belum ditentukan.</span>
                            @endif
                        </div>
                    </div>

                    {{-- Item 2: Tim Asesmen & Asesor --}}
                    <div class="assessment-milestone-item">
                        <div class="milestone-item-header">
                            <div style="display: flex; align-items: center; gap: 7px;">
                                <x-icon name="users" size="14" style="color: var(--muted);" />
                                <span class="milestone-item-label">Tim Asesmen &amp; Asesor</span>
                            </div>
                            @if($assessment->lead_assessor || $assessment->assessment_team)
                                <span class="badge-tp badge-tp-satisfied" style="font-size: 11px;">Tim Ditetapkan</span>
                            @else
                                <span class="badge-tp badge-tp-neutral" style="font-size: 11px;">Belum Ditugaskan</span>
                            @endif
                        </div>
                        <div class="milestone-item-value">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-top: 2px;">
                                <div>
                                    <span style="font-size: 10.5px; color: var(--muted); display: block; text-transform: uppercase; font-weight: 700; letter-spacing: 0.03em;">Asesor Kepala (Lead)</span>
                                    <span style="font-size: 12.5px; color: var(--ink); font-weight: 500;">
                                        {{ $assessment->lead_assessor ?: 'Belum ditugaskan' }}
                                    </span>
                                </div>
                                <div>
                                    <span style="font-size: 10.5px; color: var(--muted); display: block; text-transform: uppercase; font-weight: 700; letter-spacing: 0.03em;">Anggota Tim Asesor</span>
                                    <span style="font-size: 12.5px; color: var(--ink); font-weight: 500;">
                                        {{ $assessment->assessment_team ?: 'Belum diisi' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Item 3: Ketentuan Siklus & Toleransi Regulasi KAN U-01 --}}
                    <div class="assessment-milestone-item">
                        <div class="milestone-item-header">
                            <div style="display: flex; align-items: center; gap: 7px;">
                                <x-icon name="shield-check" size="14" style="color: var(--primary);" />
                                <span class="milestone-item-label">Ketentuan Siklus &amp; Toleransi</span>
                            </div>
                            <span class="badge-tp badge-tp-neutral" style="font-size: 11px;">Regulasi KAN</span>
                        </div>
                        <div class="milestone-item-value">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                                    <span style="font-size: 12.5px; color: var(--ink);">
                                        Jenis: <strong>{{ $assessment->assessment_type_label }}</strong>
                                    </span>
                                    @if($assessment->submission_due_date)
                                        <span style="font-size: 11.5px; color: var(--muted);">
                                            Batas Siklus: <strong style="color: var(--ink);">{{ $assessment->submission_due_date->translatedFormat('d M Y') }}</strong>
                                        </span>
                                    @endif
                                </div>

                                @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                                    <div style="margin-top: 4px; font-size: 12px; color: var(--danger-text, #ef4444); font-weight: 600;">
                                        &bull; Batas 1 tahun pembekuan ({{ $assessment->suspension_resolution_deadline?->translatedFormat('d M Y') }}) telah berakhir: Akreditasi dicabut.
                                    </div>
                                @elseif($assessment->is_submission_overdue || $assessment->status === 'SUSPENDED')
                                    <div style="margin-top: 4px; font-size: 12px; color: var(--terracotta, #c084fc); font-weight: 600;">
                                        &bull; Masa toleransi pembekuan: 1 tahun s/d {{ $assessment->suspension_resolution_deadline?->translatedFormat('d M Y') }} (sisa {{ $assessment->days_remaining_suspension }} hari).
                                    </div>
                                @elseif($assessment->status === 'COMPLETED')
                                    <div style="font-size: 11.5px; color: var(--muted); margin-top: 2px;">
                                        Pelaksanaan asesmen tuntas sesuai ketentuan siklus akreditasi KAN.
                                    </div>
                                @else
                                    <div style="font-size: 11.5px; color: var(--muted); margin-top: 2px;">
                                        Wajib diselesaikan dalam toleransi siklus akreditasi KAN untuk mencegah sanksi pembekuan.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Item 4: Catatan Pelaksanaan (jika ada) --}}
                    @if($assessment->notes)
                        <div class="assessment-milestone-item">
                            <div class="milestone-item-header">
                                <div style="display: flex; align-items: center; gap: 7px;">
                                    <x-icon name="file-text" size="14" style="color: var(--muted);" />
                                    <span class="milestone-item-label">Catatan Pelaksanaan</span>
                                </div>
                            </div>
                            <div class="milestone-item-value">
                                <div class="milestone-notes-callout" style="margin-top: 0;">
                                    <x-icon name="info" size="13" class="notes-callout-icon" />
                                    <span>{{ $assessment->notes }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action Footer Symmetrical to Right Card --}}
                @php
                    $canManage = !isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user()));
                @endphp
                <div class="compliance-action-row milestone-actions-equal {{ $canManage ? '' : 'single-action' }}">
                    <a href="{{ route('calendar.index', ['view' => 'month', 'date' => ($assessment->start_at ?: now())->toDateString(), 'highlight' => $assessment->id]) }}"
                       class="button secondary button-sm"
                       title="Buka agenda asesmen ini di Kalender Pengawasan">
                        <x-icon name="calendar" size="13" />
                        <span>Buka Kalender</span>
                    </a>
                    @if($canManage)
                        <a href="{{ route('assessments.edit', $assessment) }}"
                           class="button secondary button-sm"
                           title="Ubah rincian tanggal pelaksanaan atau tim asesor">
                            <x-icon name="edit" size="13" />
                            <span>Perbarui Agenda</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Progres Kepatuhan & Regulasi KAN U-01 (Unified 4-Phase Compliance Engine) --}}
        <div class="lpk-show-col">
            @include('assessments.partials.tp-tracking')
        </div>
    </div>
</div>

@if(!isset($owner))
    @include('assessments.partials.modal-tp-tracking')
    @include('assessments.partials.modal-complete-field')
@endif
