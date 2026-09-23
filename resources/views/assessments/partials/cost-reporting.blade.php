{{-- FITUR 1: Pelaporan Biaya Perjalanan Dinas Asesor (Cost Reporting) --}}
@php
    $expense = $assessment->expense;
    $expenseStatus = $expense ? $expense->status : 'BELUM_DILAPORKAN';
@endphp

<section class="simasadi-subcard" id="biaya-asesor-section">
    <div class="simasadi-subcard-header">
        <div class="subcard-title-group">
            <h3>
                <x-icon name="assessments" size="20" />
                <span>Pelaporan Biaya Perjalanan Dinas Asesor (Cost Reporting)</span>
            </h3>
            <span class="subcard-subtitle">
                Penggantian biaya transport, akomodasi, dan uang harian sesuai Standar Biaya Masukan (SBM) Kemenkeu
            </span>
        </div>
        <div class="header-actions">
            <x-status :value="$expenseStatus" />
            <button type="button" class="button ghost" onclick="window.openModal('modal-sheets-sync-expenses')" title="Sinkronkan rekap biaya ke Google Sheets" style="display: inline-flex; align-items: center; gap: 6px;">
                <x-icon name="sheets" size="14" style="color: #0f9d58;" />
                <span>Google Sheets</span>
            </button>
            <button type="button" class="button secondary" onclick="window.openModal('modal-report-expense')">
                <x-icon name="edit" size="14" />
                <span>{{ $expense ? 'Perbarui Biaya' : 'Input Biaya Asesor' }}</span>
            </button>
            @if($expense && $expense->total_cost > 0 && auth()->user()?->isAdmin())
                <button type="button" class="button primary" onclick="window.openModal('modal-verify-expense')">
                    <x-icon name="check" size="14" />
                    <span>Verifikasi SBM</span>
                </button>
            @endif
        </div>
    </div>

    <div class="cost-breakdown-grid">
        <div class="cost-item">
            <small>Uang Harian (SBM)</small>
            <strong>Rp {{ number_format($expense->daily_allowance ?? 0, 0, ',', '.') }}</strong>
        </div>
        <div class="cost-item">
            <small>Transportasi (Tiket/Tol/BBM)</small>
            <strong>Rp {{ number_format($expense->transport_cost ?? 0, 0, ',', '.') }}</strong>
        </div>
        <div class="cost-item">
            <small>Akomodasi / Penginapan</small>
            <strong>Rp {{ number_format($expense->accommodation_cost ?? 0, 0, ',', '.') }}</strong>
        </div>
        <div class="cost-item">
            <small>Paket Data / Komunikasi</small>
            <strong>Rp {{ number_format($expense->package_data_cost ?? 0, 0, ',', '.') }}</strong>
        </div>
        <div class="cost-item total-cost-item">
            <small>Total Biaya Realisasi</small>
            <strong>Rp {{ number_format($expense->total_cost ?? 0, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div style="background: #fafaf9; border: 1px solid #edebe6; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px;">
        <div style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <div>
                <span style="font-size: 11.5px; color: var(--muted); text-transform: uppercase; font-weight: 600; display: block;">Bukti Pengeluaran / Kwitansi:</span>
                <span style="font-size: 13.5px; font-weight: 500; color: var(--ink);">
                    {{ $expense?->receipt_note ?: 'Belum ada catatan nomor tiket/kwitansi.' }}
                </span>
            </div>
            <div>
                <span style="font-size: 11.5px; color: var(--muted); text-transform: uppercase; font-weight: 600; display: block;">Catatan Verifikasi Sekretariat KAN:</span>
                <span style="font-size: 13.5px; font-weight: 500; color: {{ $expense?->status === 'TERVERIFIKASI' ? 'var(--green)' : 'var(--ink)' }};">
                    {{ $expense?->verification_notes ?: 'Belum diverifikasi oleh petugas verifikator.' }}
                </span>
                @if($expense?->verified_at)
                    <small style="display: block; font-size: 11px; color: var(--muted); margin-top: 2px;">
                        Diverifikasi pada {{ $expense->verified_at->format('d M Y, H:i') }} WIB
                    </small>
                @endif
            </div>
        </div>
    </div>

    <div class="sbm-callout">
        <x-icon name="issues" size="18" />
        <div>
            <strong>Ketentuan Standar Biaya Masukan (SBM):</strong> Sesuai regulasi KAN & Peraturan Menteri Keuangan, biaya perjalanan dinas tim asesor (transportasi, penginapan, dan uang harian) dibebankan kepada pihak LPK di luar tarif PNBP akreditasi, dan wajib dilaporkan secara akuntabel maksimal 1 hari setelah asesmen lapangan selesai.
        </div>
    </div>
</section>

@include('partials.sheets-modal', [
    'modalId' => 'modal-sheets-sync-expenses',
    'title' => 'Integrasi Google Sheets: Rekapitulasi Biaya SBM Asesor',
    'subtitle' => 'Sinkronkan data biaya perjalanan dinas asesor (SBM Kemenkeu) untuk asesmen ini dan seluruh asesmen lainnya secara live ke Google Sheets.',
    'exportUrl' => route('reports.expenses.export'),
    'feedUrl' => route('feeds.expenses', ['key' => env('SHEETS_FEED_KEY', 'simasadi-live')]),
    'fileName' => 'rekap-biaya-sbm-simasadi.csv',
    'columns' => [
        'ID Asesmen', 'Judul Agenda', 'Nama LPK', 'Nomor Registrasi LPK', 'Jenis Asesmen',
        'Waktu Pelaksanaan', 'Status Asesmen', 'Uang Harian (Rp)', 'Transportasi (Rp)',
        'Akomodasi (Rp)', 'Paket Data (Rp)', 'Total Biaya (Rp)', 'Status SBM', 'Bukti Kwitansi / SPPD',
        'Catatan Verifikator KAN', 'Waktu Verifikasi'
    ]
])
