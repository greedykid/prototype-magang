@php
    $assessments = $accreditation->lpk->assessments;
    $hasUnverifiedExpenses = $assessments->contains(function ($item) {
        return $item->expense && $item->expense->status !== 'TERVERIFIKASI';
    });
    $isPnbpPaid = $latestBilling && $latestBilling->status === 'PAID';
    $isSigned = $signature && $signature->is_signed;
    $isGatePassed = $isPnbpPaid && ! $hasUnverifiedExpenses && $isSigned;
@endphp

{{-- REKOMENDASI OPSIONAL: Quality Gate Kesiapan Terbit Output Akreditasi --}}
<section class="readiness-gate-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 8px;">
        <div class="subcard-title-group">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--ink); margin: 0; display: flex; align-items: flex-start; gap: 8px; line-height: 1.35;">
                <x-icon name="check" size="18" style="flex-shrink: 0; margin-top: 2px;" />
                <span>Audit Kesiapan Terbit SK & Sertifikat Akreditasi (Quality Gate)</span>
            </h3>
            <span class="subcard-subtitle">
                Verifikasi kepatuhan administratif & finansial sebelum penyerahan SK resmi KAN ke LPK
            </span>
        </div>
        <span class="status {{ $isGatePassed ? 'status-completed' : 'status-in_progress' }}">
            {{ $isGatePassed ? 'Siap Dirilis ke LPK' : 'Menunggu Pemenuhan Syarat' }}
        </span>
    </div>

    <div class="readiness-checklist">
        <div class="readiness-item {{ $isPnbpPaid ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ $isPnbpPaid ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">1. Realisasi Billing PNBP</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ $isPnbpPaid ? 'Lunas (NTPN: ' . $latestBilling->ntpn . ')' : 'Belum Lunas / Belum Terbit' }}
                </span>
            </div>
        </div>

        <div class="readiness-item {{ ! $hasUnverifiedExpenses ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ ! $hasUnverifiedExpenses ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">2. Administrasi Biaya Asesor</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ ! $hasUnverifiedExpenses ? 'Seluruh biaya terverifikasi SBM' : 'Ada asesmen belum verifikasi biaya' }}
                </span>
            </div>
        </div>

        <div class="readiness-item {{ $isSigned ? 'is-done' : 'is-pending' }}">
            <span class="readiness-icon">{{ $isSigned ? '✓' : '!' }}</span>
            <div style="min-width: 0;">
                <strong style="font-size: 13px; display: block;">3. TTE SK Akreditasi (BSrE)</strong>
                <span style="font-size: 11.5px; color: var(--muted);">
                    {{ $isSigned ? 'Ditandatangani secara digital' : 'Menunggu pembubuhan TTE' }}
                </span>
            </div>
        </div>
    </div>
</section>
