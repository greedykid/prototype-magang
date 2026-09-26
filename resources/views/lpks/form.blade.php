@extends('layouts.app')

@section('title', $formTitle . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}">Kembali ke daftar LPK</a>
        <h1>{{ $formTitle }}</h1>
        <p class="lede">Lengkapi data legalitas, profil, ruang lingkup, dan masa berlaku akreditasi LPK.</p>
    </div>
</div>

<form class="panel form-grid" method="POST" action="{{ $lpk->exists ? route('lpks.update', $lpk) : route('lpks.store') }}">
    @csrf
    @if($lpk->exists) @method('PUT') @endif
    <label class="full">
        Nama LPK / Laboratorium
        <input name="name" value="{{ old('name', $lpk->name) }}" required placeholder="Contoh: Balai Besar Pengujian Eksplorasi Minyak dan Gas Bumi LEMIGAS">
    </label>
    <label>
        Jenis Akreditasi
        <select name="accreditation_type">
            @foreach(\App\Models\Lpk::ACCREDITATION_TYPES as $key => $label)
                <option value="{{ $key }}" @selected(old('accreditation_type', $lpk->accreditation_type ?: 'Laboratorium Penguji') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Skema akreditasi resmi KAN.</small>
    </label>
    <label>
        No Reg LPK (ID Unik KAN)
        <input name="no_reg" value="{{ old('no_reg', $lpk->no_reg) }}" placeholder="Contoh: 3344">
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Nomor registrasi identitas unik LPK di sistem KAN.</small>
    </label>
    <label>
        No Akreditasi KAN
        <input name="accreditation_number" value="{{ old('accreditation_number', $lpk->accreditation_number ?: $lpk->registration_number) }}" placeholder="Contoh: LP-1519-IDN">
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Kosongkan jika sertifikat belum terbit (masih Asesmen Awal).</small>
    </label>
    <label class="full">
        Ruang Lingkup Akreditasi (Scope)
        <textarea name="scope" rows="7" style="min-height: 160px; font-family: inherit; font-size: 13.5px; line-height: 1.6;" placeholder="Masukkan ruang lingkup akreditasi laboratorium secara lengkap (bidang pengujian/kalibrasi, bahan/produk yang diuji, parameter/spesifikasi pengujian, metode uji standar SNI/ISO/IEC/ASTM, dsb.)...">{{ old('scope', $lpk->scope) }}</textarea>
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Tuliskan ruang lingkup kompetensi laboratorium secara rinci (bidang pengujian/kalibrasi, bahan/matriks, metode standar, dan spesifikasi pengukuran).</small>
    </label>
    <label>
        Email
        <input type="email" name="email" value="{{ old('email', $lpk->email) }}">
    </label>
    <label>
        Telepon
        <input name="phone" value="{{ old('phone', $lpk->phone) }}">
    </label>
    <label>
        Status
        <select name="status">
            <option value="ACTIVE" @selected(old('status', $lpk->status ?: 'ACTIVE') === 'ACTIVE')>Aktif</option>
            <option value="SUSPENDED" @selected(old('status', $lpk->status) === 'SUSPENDED')>Dibekukan</option>
            <option value="INACTIVE" @selected(old('status', $lpk->status) === 'INACTIVE')>Tidak Aktif</option>
        </select>
    </label>
    @if(auth()->user()?->isAdmin() && isset($pics) && $pics->isNotEmpty())
        <label>
            PIC Penanggung Jawab
            <select name="pic_id">
                <option value="">-- Belum Ditugaskan --</option>
                @foreach($pics as $p)
                    <option value="{{ $p->id }}" @selected(old('pic_id', $lpk->pic_id) == $p->id)>{{ $p->name }} ({{ $p->email }})</option>
                @endforeach
            </select>
            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Pilih PIC laboratorium penanggung jawab.</small>
        </label>
    @endif
    <label class="full">
        Alamat
        <textarea name="address" rows="3">{{ old('address', $lpk->address) }}</textarea>
    </label>
    <label>
        Tanggal Terbit Sertifikat Akreditasi
        <input type="date" name="certificate_date" value="{{ old('certificate_date', $lpk->certificate_date?->format('Y-m-d')) }}">
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Tanggal SK / sertifikat akreditasi terbit.</small>
    </label>
    <label>
        Masa berlaku akreditasi
        <input type="date" name="expired_at" value="{{ old('expired_at', $lpk->expired_at?->format('Y-m-d')) }}">
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Otomatis +5 tahun jika dikosongkan dan tanggal terbit diisi.</small>
    </label>

    <div class="full" style="background: #f8faff; border: 1px solid #c7d2fe; border-radius: 8px; padding: 14px 16px; margin: -6px 0 2px;">
        <div style="display: flex; align-items: flex-start; gap: 10px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5645d4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div style="font-size: 12px; line-height: 1.5; color: #334155;">
                <strong style="color: #0f172a; display: block; margin-bottom: 4px; font-size: 12.5px;">Acuan Siklus Pengawasan &amp; Re-Akreditasi KAN:</strong>
                <ul style="margin: 0; padding-left: 18px; color: #475569;">
                    <li><strong>Surveilen 1 (S1):</strong> Pelaksanaan asesmen pada Bulan ke 15-18.</li>
                    <li><strong>Surveilen 2 (S2):</strong> Pelaksanaan asesmen pada Bulan ke 36-39.</li>
                    <li><strong>Re-Akreditasi (RA):</strong> Upload dokumen mulai Bulan ke-48 (maksimal Bulan ke-51 harus lengkap), asesmen re-akreditasi maksimal Bulan ke-54.</li>
                </ul>
                <div style="margin-top: 6px; font-size: 11px; color: #64748b;">
                    <em>Catatan: Pengingat email dikirim otomatis pada Bulan ke-14, Bulan ke-35, dan jelang Re-Akreditasi.</em>
                </div>
            </div>
        </div>
    </div>
    <label class="full">
        Link Google Drive Dokumen (Sertifikat Akreditasi, Amandemen & Lampiran)
        <input type="url" name="drive_url" value="{{ old('drive_url', $lpk->drive_url) }}" placeholder="https://drive.google.com/... (Tautan berkas atau folder Google Drive)">
        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Tempelkan satu tautan Google Drive terpadu yang memuat Sertifikat Akreditasi serta Amandemen & Lampiran Sertifikat.</small>
    </label>
    <div class="form-actions full">
        <a class="button ghost" href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$lpk->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $lpk->exists ? 'Simpan perubahan' : 'Simpan data LPK' }}</span>
        </button>
    </div>
</form>

@if($lpk->exists && auth()->user()?->isAdmin())
    <div class="panel" style="margin-top: 24px; border: 1px solid #feb2b2; background: #fff5f5; border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <strong style="color: #9b2c2c; font-size: 14.5px; display: block;">Zona Bahaya: Hapus Data Lembaga (LPK)</strong>
            <p style="margin: 4px 0 0; color: #742a2a; font-size: 13px;">Menghapus lembaga ini akan menghapus seluruh data proses akreditasi, agenda asesmen, dan rekam jejak terkait.</p>
        </div>
        <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="button danger" style="background: #e53e3e; border-color: #c53030; color: #ffffff;">
                <x-icon name="trash" size="16" />
                <span>Hapus LPK ini</span>
            </button>
        </form>
    </div>
@endif
@endsection
