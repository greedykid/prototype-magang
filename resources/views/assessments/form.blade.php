@extends('layouts.app')

@section('title', 'Asesmen | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('assessments.index') }}">Semua asesmen</a>
        <h1>{{ $assessment->exists ? 'Ubah asesmen' : 'Tambah asesmen' }}</h1>
    </div>
</div>

<form class="panel form-grid" method="POST" action="{{ $assessment->exists ? route('assessments.update', $assessment) : route('assessments.store') }}">
    @csrf
    @if($assessment->exists) @method('PUT') @endif
    <label class="full">
        LPK
        <select name="lpk_id" required>
            <option value="">Pilih LPK</option>
            @foreach($lpks as $lpk)
                <option value="{{ $lpk->id }}" @selected(old('lpk_id', $assessment->lpk_id ?: request('lpk_id')) == $lpk->id)>{{ $lpk->registration_number }} - {{ $lpk->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="full">
        Judul
        <input name="title" value="{{ old('title', $assessment->title) }}" required>
    </label>
    <label>
        Jenis asesmen (Standar KAN U-01)
        <select name="assessment_type" required>
            <option value="">Pilih jenis asesmen</option>
            @foreach(\App\Models\Assessment::TYPES as $typeKey => $typeLabel)
                <option value="{{ $typeKey }}" @selected(
                    old('assessment_type', $assessment->assessment_type ?: request('assessment_type')) === $typeKey ||
                    (old('assessment_type') === null && request('assessment_type') === null && (
                        ($typeKey === 'Asesmen Awal' && $assessment->assessment_type === 'INITIAL') ||
                        ($typeKey === 'Surveilen' && $assessment->assessment_type === 'SURVEILLANCE') ||
                        ($typeKey === 'Re-asesmen' && $assessment->assessment_type === 'REASSESSMENT')
                    ))
                )>
                    {{ $typeLabel }}
                </option>
            @endforeach
        </select>
    </label>
    <label>
        Status
        <select name="status">
            @foreach(['PLANNED', 'SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'] as $status)
                <option @selected(old('status', $assessment->status ?: 'PLANNED') === $status)>
                    {{ ["PLANNED" => "Direncanakan", "SCHEDULED" => "Terjadwal", "IN_PROGRESS" => "Sedang Berlangsung", "COMPLETED" => "Selesai", "CANCELLED" => "Dibatalkan", "SUBMITTED" => "Diajukan", "UNDER_REVIEW" => "Sedang Ditinjau", "NEED_REVISION" => "Perlu Perbaikan", "APPROVED" => "Disetujui", "REJECTED" => "Ditolak"][$status] ?? $status }}
                </option>
            @endforeach
        </select>
    </label>
    <label>
        Mulai
        <input type="datetime-local" name="start_at" value="{{ old('start_at', $assessment->start_at?->format('Y-m-d\TH:i') ?: request('start_at')) }}" required>
    </label>
    <label>
        Selesai
        <input type="datetime-local" name="end_at" value="{{ old('end_at', $assessment->end_at?->format('Y-m-d\TH:i') ?: request('end_at')) }}" required>
    </label>
    <label>
        Lokasi
        <input name="location" value="{{ old('location', $assessment->location) }}">
    </label>
    <label>
        Lead assessor
        <input name="lead_assessor" value="{{ old('lead_assessor', $assessment->lead_assessor) }}">
    </label>
    <label class="full">
        Catatan
        <textarea name="notes" rows="4">{{ old('notes', $assessment->notes) }}</textarea>
    </label>

    {{-- Section: Tindakan Perbaikan (TP & VTP) Sesuai Ketentuan Brosur KAN --}}
    <div class="full" style="padding: 16px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px; margin-top: 6px; display: grid; gap: 14px;">
        <div>
            <h3 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: var(--text);">
                Tindakan Perbaikan &amp; Verifikasi (TP &amp; VTP) - Standar KAN
            </h3>
            <p style="margin: 0; font-size: 12.5px; color: var(--muted); line-height: 1.4;">
                Sesuai Brosur Penguji KAN: Akreditasi Awal batas waktu 3 bulan. Survailen, PRL, dan Reakreditasi batas waktu 2 bulan. Perpanjangan masa perbaikan maksimal 1 bulan berbasis surat permohonan resmi dari LPK.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <label>
                Status tindakan perbaikan
                <select name="tp_status">
                    <option value="NONE" @selected(old('tp_status', $assessment->tp_status ?: 'NONE') === 'NONE')>Nihil / Tidak Ada Temuan</option>
                    <option value="IN_PROGRESS" @selected(old('tp_status', $assessment->tp_status) === 'IN_PROGRESS')>Penyusunan Perbaikan oleh LPK</option>
                    <option value="UNDER_VERIFICATION" @selected(old('tp_status', $assessment->tp_status) === 'UNDER_VERIFICATION')>Dalam Verifikasi Tim Asesor</option>
                    <option value="SATISFIED" @selected(old('tp_status', $assessment->tp_status) === 'SATISFIED')>Dinyatakan Memenuhi (Selesai)</option>
                </select>
            </label>

            <label>
                Batas waktu awal (SLA)
                <input type="date" name="tp_due_date" value="{{ old('tp_due_date', $assessment->tp_due_date?->format('Y-m-d') ?: ($assessment->calculateDefaultTpDueDate()?->format('Y-m-d') ?: '')) }}">
                <small style="font-size: 11px; color: var(--muted); margin-top: 4px; display: block;">Kosongkan jika ingin dihitung otomatis sesuai jenis asesmen.</small>
            </label>

            <label>
                Tanggal dinyatakan memenuhi
                <input type="date" name="tp_satisfied_at" value="{{ old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d')) }}">
                <small style="font-size: 11px; color: var(--muted); margin-top: 4px; display: block;">Diisi apabila tindakan perbaikan telah disetujui.</small>
            </label>
        </div>

        <div style="padding: 12px; background: #ffffff; border: 1px solid var(--line); border-radius: 6px; display: grid; gap: 10px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600;">
                <input type="checkbox" name="tp_has_extension" value="1" @checked(old('tp_has_extension', $assessment->tp_has_extension)) onchange="document.getElementById('form-extension-block').style.display = this.checked ? 'grid' : 'none'" style="width: 18px; height: 18px; min-height: 18px; max-height: 18px; min-width: 18px; max-width: 18px; margin: 0; padding: 0; cursor: pointer; flex-shrink: 0; accent-color: var(--maroon, #e11d48);">
                <span>LPK Mengajukan Perpanjangan Masa Perbaikan (+1 Bulan Sesuai Regulasi KAN)</span>
            </label>

            <div id="form-extension-block" style="display: {{ old('tp_has_extension', $assessment->tp_has_extension) ? 'grid' : 'none' }}; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                <label>
                    Nomor surat permohonan LPK
                    <input type="text" name="tp_extension_letter_no" placeholder="Contoh: 104/LPK-LAB/EXT/IX/2026" value="{{ old('tp_extension_letter_no', $assessment->tp_extension_letter_no) }}">
                </label>
                <label>
                    Tanggal surat
                    <input type="date" name="tp_extension_date" value="{{ old('tp_extension_date', $assessment->tp_extension_date?->format('Y-m-d')) }}">
                </label>
                <label style="grid-column: 1 / -1;">
                    Alasan permohonan perpanjangan
                    <input type="text" name="tp_extension_notes" placeholder="Contoh: Menunggu pengiriman kalibrator eksternal dan uji profisiensi perbaikan." value="{{ old('tp_extension_notes', $assessment->tp_extension_notes) }}">
                </label>
            </div>
        </div>

        {{-- Section: Keputusan Akreditasi / SK KAN --}}
        <div id="form-sk-block" style="padding: 14px; background: #ffffff; border: 1px solid var(--line); border-radius: 6px; display: grid; gap: 10px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <div>
                    <span style="font-size: 13px; font-weight: 700; color: var(--text); display: block;">
                        Surat Keputusan (SK) Hasil Asesmen / Akreditasi KAN
                    </span>
                    <small style="color: var(--muted); font-size: 11.5px;">Diisi jika tindakan perbaikan telah selesai / memenuhi atau SK kelanjutan/re-akreditasi telah diterbitkan.</small>
                </div>
                <span class="badge-tp badge-tp-success" style="font-size: 11px;">Penyelesaian &amp; SK KAN</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                <label>
                    Nomor SK KAN
                    <input type="text" name="sk_number" placeholder="Contoh: SK.KAN.042/BSN/IX/2026" value="{{ old('sk_number', $assessment->sk_number) }}">
                </label>
                <label>
                    Tanggal SK
                    <input type="date" name="sk_date" value="{{ old('sk_date', $assessment->sk_date?->format('Y-m-d')) }}">
                </label>
            </div>
            @if($assessment->sk_lead_time_label)
                <div style="font-size: 12px; color: #15803d; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 6px 10px; border-radius: 4px;">
                    <strong>Rentang Proses:</strong> {{ $assessment->sk_lead_time_label }}
                    (dari pelaksanaan {{ ($assessment->end_at ?? $assessment->start_at)->format('d M Y') }} s/d SK {{ $assessment->sk_date->format('d M Y') }})
                </div>
            @endif
        </div>

        <label>
            Catatan temuan &amp; tindakan perbaikan
            <textarea name="tp_notes" rows="3" placeholder="Rangkuman temuan ketidaksesuaian atau tindakan koreksi yang dilakukan LPK...">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
        </label>
    </div>

    <div class="form-actions full">
        <a class="button ghost" href="{{ route('assessments.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$assessment->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $assessment->exists ? 'Simpan perubahan' : 'Simpan asesmen' }}</span>
        </button>
    </div>
</form>
@endsection
