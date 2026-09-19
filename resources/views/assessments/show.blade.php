@extends('layouts.app')

@section('title', $assessment->title . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('assessments.index') }}">Semua asesmen</a>
        <h1>{{ $assessment->title }}</h1>
        <p class="lede">{{ $assessment->lpk->name }}</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a class="button secondary" href="{{ route('assessments.edit', $assessment) }}">Ubah asesmen</a>
    </div>
</div>

<section class="panel detail-list">
    <div>
        <dt>Status</dt>
        <dd><x-status :value="$assessment->status" /></dd>
    </div>
    <div>
        <dt>Jenis</dt>
        <dd>{{ $assessment->assessment_type }}</dd>
    </div>
    <div>
        <dt>Waktu</dt>
        <dd>{{ $assessment->start_at->format('d M Y, H:i') }} sampai {{ $assessment->end_at->format('d M Y, H:i') }}</dd>
    </div>
    <div>
        <dt>Lokasi</dt>
        <dd>{{ $assessment->location ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Lead assessor</dt>
        <dd>{{ $assessment->lead_assessor ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Catatan</dt>
        <dd>{{ $assessment->notes ?: 'Belum ada catatan.' }}</dd>
    </div>
</section>

{{-- FITUR 1: Pelaporan Biaya Perjalanan Dinas Asesor (Cost Reporting) --}}
@php
    $expense = $assessment->expense;
    $expenseStatus = $expense ? $expense->status : 'BELUM_DILAPORKAN';
@endphp

<section class="simasadi-subcard" id="biaya-asesor-section">
    <div class="simasadi-subcard-header">
        <div>
            <h3>
                <x-icon name="assessments" size="20" />
                Pelaporan Biaya Perjalanan Dinas Asesor (Cost Reporting)
            </h3>
            <span style="display: block; font-size: 12.5px; color: var(--muted); margin-top: 3px;">
                Penggantian biaya transport, akomodasi, dan uang harian sesuai Standar Biaya Masukan (SBM) Kemenkeu
            </span>
        </div>
        <div class="header-actions">
            <x-status :value="$expenseStatus" />
            <button type="button" class="button secondary" onclick="document.getElementById('modal-report-expense').classList.add('is-active')" style="min-height: 36px; padding: 6px 14px; font-size: 13px;">
                <x-icon name="edit" size="14" />
                {{ $expense ? 'Perbarui Biaya' : 'Input Biaya Asesor' }}
            </button>
            @if($expense && $expense->total_cost > 0)
                <button type="button" class="button primary" onclick="document.getElementById('modal-verify-expense').classList.add('is-active')" style="min-height: 36px; padding: 6px 14px; font-size: 13px;">
                    <x-icon name="check" size="14" />
                    Verifikasi SBM
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
                        Diverifikasi pada {{ $expense->verified_at->format('d M Y H:i') }}
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

{{-- Modal 1: Input / Perbarui Laporan Biaya Asesor --}}
<div class="simasadi-modal" id="modal-report-expense" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Laporan Biaya Perjalanan Dinas Asesor</h4>
            <button type="button" class="simasadi-modal-close" onclick="document.getElementById('modal-report-expense').classList.remove('is-active')">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.expenses.store', $assessment) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Uang Harian Asesor (Rp)</span>
                    <input type="number" name="daily_allowance" min="0" step="1000" value="{{ old('daily_allowance', $expense->daily_allowance ?? 860000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Mengacu pada SBM uang harian per hari penugasan asesor.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Biaya Transportasi (Rp)</span>
                    <input type="number" name="transport_cost" min="0" step="1000" value="{{ old('transport_cost', $expense->transport_cost ?? 1250000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Tiket pesawat/kereta, taksi bandara, atau BBM/tol.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Biaya Akomodasi / Hotel (Rp)</span>
                    <input type="number" name="accommodation_cost" min="0" step="1000" value="{{ old('accommodation_cost', $expense->accommodation_cost ?? 750000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Biaya penginapan hotel per malam sesuai plafon SBM.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Paket Data & Komunikasi (Rp)</span>
                    <input type="number" name="package_data_cost" min="0" step="1000" value="{{ old('package_data_cost', $expense->package_data_cost ?? 150000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Kebutuhan koordinasi data asesmen daring/lapangan.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Catatan Bukti Kwitansi / No. SPPD</span>
                    <input type="text" name="receipt_note" placeholder="Contoh: Tiket Garuda GA-412 & Kwitansi Hotel Santika #8192" value="{{ old('receipt_note', $expense->receipt_note ?? '') }}" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" onclick="document.getElementById('modal-report-expense').classList.remove('is-active')">Batal</button>
                <button type="submit" class="button primary">Simpan & Ajukan Verifikasi</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Verifikasi Kepatuhan SBM oleh Sekretariat KAN --}}
@if($expense)
<div class="simasadi-modal" id="modal-verify-expense" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Verifikasi Kepatuhan SBM (Sekretariat KAN)</h4>
            <button type="button" class="simasadi-modal-close" onclick="document.getElementById('modal-verify-expense').classList.remove('is-active')">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.expenses.verify', $assessment) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
                    Total tagihan yang dilaporkan: <strong style="color: var(--ink);">Rp {{ number_format($expense->total_cost, 0, ',', '.') }}</strong>
                </p>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Keputusan Verifikasi</span>
                    <select name="status" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                        <option value="TERVERIFIKASI" {{ $expense->status === 'TERVERIFIKASI' ? 'selected' : '' }}>Terverifikasi Sesuai SBM (Disetujui)</option>
                        <option value="PERLU_REVISI" {{ $expense->status === 'PERLU_REVISI' ? 'selected' : '' }}>Perlu Revisi Bukti Kwitansi / Nominal</option>
                    </select>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Catatan Verifikator</span>
                    <textarea name="verification_notes" rows="3" placeholder="Contoh: Bukti tiket pesawat dan hotel telah lengkap dan sesuai pagu PMK No. 49." style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">{{ $expense->verification_notes }}</textarea>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" onclick="document.getElementById('modal-verify-expense').classList.remove('is-active')">Batal</button>
                <button type="submit" class="button primary">Simpan Keputusan</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
