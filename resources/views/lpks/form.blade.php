@extends('layouts.app')

@section('title', $formTitle . ' | SIMASADI')

@section('content')
<div class="lpk-form-container">
    {{-- Header Card dengan Breadcrumb, Informasi Status & Tombol Cepat --}}
    <div class="lpk-form-header">
        <div class="lpk-header-back-wrap">
            <a href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}" class="lpk-back-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                <span>{{ $lpk->exists ? 'Kembali ke detail LPK' : 'Semua LPK' }}</span>
            </a>
        </div>

        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
                <h1>{{ $formTitle }}</h1>
                <p class="lpk-form-desc">Lengkapi dan perbarui data legalitas, masa berlaku sertifikat, kontak, dan ruang lingkup akreditasi.</p>

                @if($lpk->exists)
                    <div class="lpk-header-badges">
                        <span class="lpk-badge-reg">No. Reg: {{ $lpk->no_reg ?: ($lpk->registration_number ?: '-') }}</span>
                        @if($lpk->accreditation_number)
                            <span class="lpk-badge-acc">No. Akreditasi: {{ $lpk->accreditation_number }}</span>
                        @else
                            <span class="lpk-badge-acc" style="color: #64748b; background: #f8fafc;">Asesmen Awal</span>
                        @endif
                        @if($lpk->accreditation_type)
                            <span class="lpk-badge-type">{{ $lpk->accreditation_type }}</span>
                        @endif
                        <x-status :value="$lpk->dynamic_status" />
                    </div>
                @endif
            </div>

            <div class="lpk-header-actions">
                <a class="button ghost" href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}">
                    Batal
                </a>
                <button class="button primary" type="submit" form="lpk-main-form">
                    <x-icon :name="$lpk->exists ? 'check' : 'plus'" size="16" />
                    <span>{{ $lpk->exists ? 'Simpan perubahan' : 'Simpan data LPK' }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Main Multi-Card Form --}}
    <form method="POST" action="{{ $lpk->exists ? route('lpks.update', $lpk) : route('lpks.store') }}" id="lpk-main-form">
        @csrf
        @if($lpk->exists)
            @method('PUT')
        @endif

        <div class="lpk-form-layout">
            {{-- KOLOM KIRI (Primary Data, Scope & Berkas Drive) --}}
            <div class="lpk-form-column">
                {{-- KARTU 1: Identitas & Legalitas Laboratorium --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="16" height="20" x="4" y="2" rx="2" ry="2"/>
                                <path d="M9 22v-4h6v4"/>
                                <path d="M8 6h.01"/>
                                <path d="M16 6h.01"/>
                                <path d="M8 10h.01"/>
                                <path d="M16 10h.01"/>
                                <path d="M8 14h.01"/>
                                <path d="M16 14h.01"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Profil &amp; Legalitas Laboratorium</h2>
                            <p>Identitas resmi lembaga dan nomor registrasi di Komite Akreditasi Nasional (KAN).</p>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-name">
                            <span>Nama LPK / Laboratorium</span>
                            <span class="lpk-required-dot">*</span>
                        </label>
                        <input id="lpk-name" name="name" value="{{ old('name', $lpk->name) }}" required placeholder="Contoh: Balai Besar Pengujian Minyak dan Gas Bumi LEMIGAS">
                        <span class="lpk-field-hint">Nama lengkap unit atau balai pengujian yang tercantum pada sertifikat akreditasi.</span>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="lpk-accreditation-type">
                                <span>Jenis Akreditasi</span>
                            </label>
                            <select id="lpk-accreditation-type" name="accreditation_type">
                                @foreach(\App\Models\Lpk::ACCREDITATION_TYPES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('accreditation_type', $lpk->accreditation_type ?: 'Laboratorium Penguji') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="lpk-field-hint">Skema akreditasi resmi KAN (LP, LK, LM, dsb).</span>
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="lpk-no-reg">
                                <span>No. Reg LPK (ID Unik KAN)</span>
                            </label>
                            <input id="lpk-no-reg" name="no_reg" value="{{ old('no_reg', $lpk->no_reg) }}" placeholder="Contoh: 3434">
                            <span class="lpk-field-hint">Nomor registrasi unik pada basis data KAN.</span>
                        </div>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="lpk-accreditation-number">
                                <span>No. Akreditasi KAN</span>
                            </label>
                            <input id="lpk-accreditation-number" name="accreditation_number" value="{{ old('accreditation_number', $lpk->accreditation_number ?: $lpk->registration_number) }}" placeholder="Contoh: LP-021-IDN">
                            <span class="lpk-field-hint">Kosongkan jika sertifikat belum terbit (masih Asesmen Awal).</span>
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="lpk-status">
                                <span>Status Akreditasi</span>
                            </label>
                            <select id="lpk-status" name="status">
                                <option value="ACTIVE" @selected(old('status', $lpk->status ?: 'ACTIVE') === 'ACTIVE')>Aktif</option>
                                <option value="SUSPENDED" @selected(old('status', $lpk->status) === 'SUSPENDED')>Dibekukan</option>
                                <option value="INACTIVE" @selected(old('status', $lpk->status) === 'INACTIVE')>Tidak Aktif</option>
                            </select>
                            <span class="lpk-field-hint">Status kepatuhan administratif LPK.</span>
                        </div>
                    </div>

                    @if(auth()->user()?->isAdmin() && isset($pics) && $pics->isNotEmpty())
                        <div class="lpk-field">
                            <label class="lpk-label" for="lpk-pic-id">
                                <span>PIC Penanggung Jawab Internal</span>
                            </label>
                            <select id="lpk-pic-id" name="pic_id">
                                <option value="">-- Belum Ditugaskan --</option>
                                @foreach($pics as $p)
                                    <option value="{{ $p->id }}" @selected(old('pic_id', $lpk->pic_id) == $p->id)>{{ $p->name }} ({{ $p->email }})</option>
                                @endforeach
                            </select>
                            <span class="lpk-field-hint">Petugas atau analis laboratorium yang ditugaskan mengawal proses LPK ini.</span>
                        </div>
                    @endif
                </div>

                {{-- KARTU 2: Ruang Lingkup Akreditasi (Scope) --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                                <polyline points="2 17 12 22 22 17"/>
                                <polyline points="2 12 12 17 22 12"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Ruang Lingkup Akreditasi (Scope)</h2>
                            <p>Kompetensi teknis pengujian, matriks bahan, metode standar, dan spesifikasi pengukuran.</p>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-scope">
                            <span>Uraian Lengkap Ruang Lingkup</span>
                        </label>
                        <textarea id="lpk-scope" name="scope" rows="8" class="lpk-textarea-scope" placeholder="Masukkan ruang lingkup akreditasi laboratorium secara lengkap (bidang pengujian/kalibrasi, bahan/produk yang diuji, parameter/spesifikasi pengujian, metode uji standar SNI/ISO/IEC/ASTM, dsb.)...">{{ old('scope', $lpk->scope) }}</textarea>
                        <div class="lpk-scope-meta">
                            <span>Tips: Salin langsung lampiran ruang lingkup dari lampiran resmi sertifikat KAN.</span>
                            <span id="scope-char-count"></span>
                        </div>
                    </div>
                </div>

                {{-- KARTU 3: Berkas Digital & Google Drive --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                <line x1="12" y1="11" x2="12" y2="17"/>
                                <line x1="9" y1="14" x2="15" y2="14"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Berkas &amp; Tautan Digital Terpadu</h2>
                            <p>Tautan Google Drive penyimpanan berkas sertifikat, lampiran amandemen, dan dokumen mutu.</p>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-drive-url">
                            <span>Tautan Google Drive Dokumen</span>
                        </label>
                        <div class="lpk-drive-input-wrap">
                            <input id="lpk-drive-url" type="url" name="drive_url" value="{{ old('drive_url', $lpk->drive_url) }}" placeholder="https://drive.google.com/... (Tautan berkas atau folder Google Drive)">
                            @if($lpk->drive_url)
                                <a href="{{ $lpk->drive_url }}" target="_blank" rel="noopener noreferrer" class="lpk-drive-preview-btn" title="Buka tautan Google Drive di tab baru">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    <span>Buka</span>
                                </a>
                            @endif
                        </div>
                        <span class="lpk-field-hint">Tempelkan tautan folder Google Drive terpadu yang memuat Sertifikat Akreditasi, Amandemen, dan Lampiran.</span>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (Masa Berlaku, Jadwal KAN & Kontak Lokasi) --}}
            <div class="lpk-form-column">
                {{-- KARTU 4: Periode Sertifikat & Siklus KAN --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Masa Berlaku Sertifikat KAN</h2>
                            <p>Periode 5 tahun akreditasi dan perhitungan siklus pengawasan berkala.</p>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-certificate-date">
                            <span>Tanggal Terbit Sertifikat</span>
                        </label>
                        <input id="lpk-certificate-date" type="date" name="certificate_date" value="{{ old('certificate_date', $lpk->certificate_date?->format('Y-m-d')) }}">
                        <span class="lpk-field-hint">Tanggal penetapan SK atau sertifikat akreditasi terbit.</span>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-expired-at">
                            <span>Masa Berlaku Akreditasi</span>
                        </label>
                        <input id="lpk-expired-at" type="date" name="expired_at" value="{{ old('expired_at', $lpk->expired_at?->format('Y-m-d')) }}">
                        <span class="lpk-field-hint">Otomatis dihitung +5 tahun jika dikosongkan dan tanggal terbit diisi.</span>
                    </div>

                    {{-- Panduan Visual Siklus Pengawasan KAN --}}
                    <div class="kan-cycle-box">
                        <div class="kan-cycle-box-header">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--maroon)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            <span>Acuan Siklus Pengawasan KAN (5 Tahun):</span>
                        </div>

                        <div class="kan-cycle-steps">
                            <div class="kan-cycle-step-item">
                                <span class="kan-step-badge">Bulan 15-18</span>
                                <div class="kan-step-info">
                                    <span class="kan-step-title">Surveilen 1 (S1)</span>
                                    <span class="kan-step-timing">Pelaksanaan asesmen pengawasan berkala tahap pertama.</span>
                                </div>
                            </div>

                            <div class="kan-cycle-step-item">
                                <span class="kan-step-badge">Bulan 36-39</span>
                                <div class="kan-step-info">
                                    <span class="kan-step-title">Surveilen 2 (S2)</span>
                                    <span class="kan-step-timing">Pelaksanaan asesmen pengawasan berkala tahap kedua.</span>
                                </div>
                            </div>

                            <div class="kan-cycle-step-item">
                                <span class="kan-step-badge">Bulan 48-54</span>
                                <div class="kan-step-info">
                                    <span class="kan-step-title">Re-Akreditasi (RA)</span>
                                    <span class="kan-step-timing">Upload berkas mulai Bulan ke-48 (maksimal Bulan 51), asesmen maksimal Bulan 54.</span>
                                </div>
                            </div>
                        </div>

                        <div class="kan-cycle-notice">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span>Notifikasi email dikirim otomatis pada Bulan ke-14, 35, dan jelang Re-Akreditasi.</span>
                        </div>
                    </div>
                </div>

                {{-- KARTU 5: Kontak & Lokasi Fasilitas --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Kontak &amp; Alamat Fasilitas</h2>
                            <p>Informasi narahubung operasional dan domisili laboratorium.</p>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-email">
                            <span>Email Resmi Laboratorium</span>
                        </label>
                        <input id="lpk-email" type="email" name="email" value="{{ old('email', $lpk->email) }}" placeholder="laboratorium@instansi.go.id">
                        <span class="lpk-field-hint">Alamat surat elektronik untuk pengiriman surat resmi &amp; notifikasi pengawasan.</span>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-phone">
                            <span>Nomor Telepon / Hotline</span>
                        </label>
                        <input id="lpk-phone" name="phone" value="{{ old('phone', $lpk->phone) }}" placeholder="Contoh: (022) 2503171 / 08123456789">
                        <span class="lpk-field-hint">Nomor kontak langsung fasilitas pengujian.</span>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="lpk-address">
                            <span>Alamat Lengkap Fasilitas</span>
                        </label>
                        <textarea id="lpk-address" name="address" rows="3" class="lpk-textarea-address" placeholder="Jalan, nomor gedung, kelurahan, kecamatan, kota/kabupaten, dan provinsi...">{{ old('address', $lpk->address) }}</textarea>
                        <span class="lpk-field-hint">Lokasi fisik tempat pengujian atau kalibrasi diselenggarakan.</span>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Danger Zone (Hapus Data LPK untuk Admin) --}}
    @if($lpk->exists && auth()->user()?->isAdmin())
        <div class="lpk-danger-card">
            <div class="lpk-danger-text">
                <span class="lpk-danger-title">Zona Bahaya: Hapus Data Lembaga (LPK)</span>
                <span class="lpk-danger-desc">Menghapus lembaga ini akan menghapus seluruh data proses akreditasi, agenda asesmen, dan rekam jejak terkait secara permanen.</span>
            </div>
            <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="button danger" style="background: #dc2626; border-color: #b91c1c; color: #ffffff;">
                    <x-icon name="trash" size="16" />
                    <span>Hapus LPK ini</span>
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
