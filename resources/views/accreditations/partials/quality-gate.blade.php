@php
    $assessments = $accreditation->lpk->assessments;
    $hasAssessments = $assessments->isNotEmpty();
    $allAssessmentsCompleted = $hasAssessments && $assessments->every(fn ($item) => $item->status === 'COMPLETED');
    $hasNoOverdueTp = ! $assessments->contains(fn ($item) => $item->is_tp_overdue);
    $isEhaRecorded = ! empty($accreditation->pantek_at) || ! empty($accreditation->target_output_at) || ! empty($accreditation->output_released_at);
    $isGatePassed = $allAssessmentsCompleted && $hasNoOverdueTp && $isEhaRecorded;
@endphp

{{-- Quality Gate Kesiapan Terbit Dokumen SK Akreditasi --}}
<section class="readiness-gate-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 8px;">
        <div class="subcard-title-group">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--ink); margin: 0; display: flex; align-items: flex-start; gap: 8px; line-height: 1.35;">
                <x-icon name="check" size="18" style="flex-shrink: 0; margin-top: 2px;" />
                <span>Audit Kesiapan Terbit Dokumen SK (Quality Gate)</span>
            </h3>
            <span class="subcard-subtitle">
                Verifikasi kepatuhan administratif dan evaluasi hasil asesmen sebelum penetapan dan penyerahan SK resmi KAN
            </span>
        </div>
        <span class="status {{ $isGatePassed ? 'status-completed' : 'status-in_progress' }}">
            {{ $isGatePassed ? 'Siap Dirilis ke LPK' : 'Menunggu Pemenuhan Syarat' }}
        </span>
    </div>

    <div class="readiness-checklist">
        <div class="readiness-item {{ $allAssessmentsCompleted ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ $allAssessmentsCompleted ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">1. Ketuntasan Asesmen Lapangan</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ $allAssessmentsCompleted ? 'Seluruh asesmen berstatus selesai' : ($hasAssessments ? 'Masih ada asesmen yang belum selesai' : 'Belum ada agenda asesmen terdaftar') }}
                </span>
            </div>
        </div>

        <div class="readiness-item {{ $hasNoOverdueTp ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ $hasNoOverdueTp ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">2. Pemenuhan Tindakan Perbaikan (TP)</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ $hasNoOverdueTp ? 'Tidak ada temuan melewati batas waktu KAN' : 'Ada tindakan perbaikan yang kedaluwarsa/overdue' }}
                </span>
            </div>
        </div>

        <div class="readiness-item {{ $isEhaRecorded ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ $isEhaRecorded ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">3. Sidang EHA &amp; Target Rilis SK</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ $isEhaRecorded ? 'Jadwal pantek / target output telah ditetapkan' : 'Tanggal pantek / target output belum diisi' }}
                </span>
            </div>
        </div>
    </div>
</section>
