@extends('layouts.app')

@section('title', $formTitle . ' | SIMASADI')

@php
    $ms = $lpk->exists ? $lpk->surveillance_milestones : [];
    $s1 = $ms['s1'] ?? null;
    $s2 = $ms['s2'] ?? null;
    $ra = $ms['ra'] ?? null;

    $s1Completed = ($s1['status'] ?? '') === 'COMPLETED_OR_SCHEDULED' || ($s1['status'] ?? '') === 'COMPLETED';
    $s1Date = $s1 && $s1['target_date'] ? $s1['target_date']->translatedFormat('d M Y') : ($lpk->certificate_date ? $lpk->certificate_date->copy()->addMonths(18)->translatedFormat('d M Y') : '18 Jan 2025');
    $s1StatusLabel = $s1Completed ? 'Selesai' : (($s1['status'] ?? '') === 'DUE' ? 'Jatuh Tempo' : (($s1['status'] ?? '') === 'OVERDUE' ? 'Terlambat' : 'Selesai'));

    $s2Completed = ($s2['status'] ?? '') === 'COMPLETED_OR_SCHEDULED' || ($s2['status'] ?? '') === 'COMPLETED';
    $s2Date = $s2 && $s2['target_date'] ? $s2['target_date']->translatedFormat('d M Y') : ($lpk->certificate_date ? $lpk->certificate_date->copy()->addMonths(39)->translatedFormat('d M Y') : '18 Jan 2028');
    $s2StatusLabel = $s2Completed ? 'Selesai' : (($s2['status'] ?? '') === 'DUE' ? 'Jatuh Tempo' : (($s2['status'] ?? '') === 'OVERDUE' ? 'Terlambat' : 'Terjadwal'));

    $raDate = $ra && $ra['target_date'] ? $ra['target_date']->translatedFormat('d M Y') : ($lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : ($lpk->certificate_date ? $lpk->certificate_date->copy()->addMonths(51)->translatedFormat('d M Y') : '18 Jun 2029'));
@endphp

@section('content')
<div class="lpk-form-v2-container">
    {{-- Breadcrumb Navigasi Kembali --}}
    <div class="lpk-form-v2-header">
        <a href="{{ route('lpks.index') }}" class="lpk-form-v2-breadcrumb">
            <x-icon name="arrow-left" size="14" />
            <span>Daftar Laboratorium</span>
        </a>

        {{-- Header Title & Chips / Actions --}}
        <div class="lpk-form-v2-title-row">
            <div class="lpk-form-v2-title-group">
                <h1 class="lpk-form-v2-title">{{ $lpk->exists ? 'Ubah Data LPK' : 'Tambah LPK Baru' }}</h1>

                @if($lpk->exists)
                    <div class="lpk-form-v2-meta-row">
                        <span class="lpk-chip-neutral">{{ $lpk->no_reg ?: 'No. Reg: -' }}</span>
                        <span class="lpk-chip-neutral">{{ $lpk->accreditation_type ?: 'Laboratorium Penguji (LP)' }}</span>
                        @if($lpk->status === 'ACTIVE' || $lpk->status === 'AKTIF')
                            <span class="lpk-chip-active">Aktif</span>
                        @elseif($lpk->status === 'SUSPENDED')
                            <span class="lpk-chip-suspended">Dibekukan</span>
                        @else
                            <span class="lpk-chip-inactive">{{ $lpk->status_label ?: 'Tidak Aktif' }}</span>
                        @endif
                        <span class="lpk-meta-text">
                            Terakhir diubah {{ $lpk->updated_at ? $lpk->updated_at->diffForHumans() : 'baru saja' }}{{ $lpk->pic ? ' oleh ' . $lpk->pic->name : '' }}
                        </span>
                    </div>
                @else
                    <p class="lpk-meta-text" style="margin: 2px 0 0;">
                        Lengkapi data legalitas, masa berlaku sertifikat, kontak, dan ruang lingkup akreditasi lembaga baru.
                    </p>
                @endif
            </div>

            @if($lpk->exists)
                <div class="lpk-form-v2-header-actions">
                    <button type="button" class="lpk-form-v2-btn-action" onclick="window.openModal('modal-lpk-history')">
                        <x-icon name="clock" size="15" />
                        <span>Riwayat perubahan</span>
                    </button>
                    <a href="{{ route('lpks.show', $lpk) }}" class="lpk-form-v2-btn-action">
                        <x-icon name="external-link" size="15" />
                        <span>Lihat detail</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Form Utama --}}
    <form method="POST" action="{{ $lpk->exists ? route('lpks.update', $lpk) : route('lpks.store') }}" id="lpk-main-form">
        @csrf
        @if($lpk->exists)
            @method('PUT')
        @endif

        <div class="lpk-form-v2-grid">
            {{-- KOLOM KIRI (~65% / 750px) --}}
            <div class="lpk-form-v2-col">
                {{-- KARTU 1: Profil & Legalitas LPK --}}
                <div class="lpk-form-v2-card">
                    <div class="lpk-form-v2-card-head">
                        <div class="lpk-form-v2-card-icon">
                            <x-icon name="file-text" size="18" />
                        </div>
                        <div class="lpk-form-v2-card-head-text">
                            <h2>Profil &amp; Legalitas LPK</h2>
                            <p>Identitas lembaga dan nomor registrasi di Komite Akreditasi Nasional (KAN).</p>
                        </div>
                    </div>

                    {{-- Nama LPK / Laboratorium --}}
                    <div class="lpk-form-v2-field">
                        <label class="lpk-form-v2-label" for="lpk-name">
                            <span>Nama LPK / Laboratorium</span>
                            <span class="lpk-form-v2-req">*</span>
                        </label>
                        <input id="lpk-name" name="name" value="{{ old('name', $lpk->name) }}" required maxlength="255" placeholder="Contoh: UPT Laboratorium Pengujian Konstruksi" class="lpk-form-v2-input @error('name') is-invalid @enderror">
                        <span class="lpk-form-v2-helper">Gunakan nama resmi lembaga sesuai sertifikat akreditasi KAN.</span>
                        @error('name')
                            <span class="lpk-form-v2-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Baris: No. Reg LPK & Jenis Akreditasi --}}
                    <div class="lpk-form-v2-row-2col">
                        <div class="lpk-form-v2-field">
                            <label class="lpk-form-v2-label" for="lpk-no-reg">
                                <span>No. Reg LPK (ID Unik KAN)</span>
                            </label>
                            <input id="lpk-no-reg" name="no_reg" value="{{ old('no_reg', $lpk->no_reg) }}" maxlength="30" placeholder="Contoh: LPK-0312" class="lpk-form-v2-input @error('no_reg') is-invalid @enderror">
                            <span class="lpk-form-v2-helper">Kosongkan jika sertifikat belum terbit (Asesmen Awal).</span>
                            @error('no_reg')
                                <span class="lpk-form-v2-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="lpk-form-v2-field">
                            <label class="lpk-form-v2-label" for="lpk-accreditation-type">
                                <span>Jenis Akreditasi</span>
                            </label>
                            <select id="lpk-accreditation-type" name="accreditation_type" class="lpk-form-v2-select @error('accreditation_type') is-invalid @enderror">
                                @foreach(\App\Models\Lpk::ACCREDITATION_TYPES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('accreditation_type', $lpk->accreditation_type ?: 'Laboratorium Penguji') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('accreditation_type')
                                <span class="lpk-form-v2-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Baris: No. Akreditasi KAN & Status Akreditasi --}}
                    <div class="lpk-form-v2-row-2col">
                        <div class="lpk-form-v2-field">
                            <label class="lpk-form-v2-label" for="lpk-accreditation-number">
                                <span>No. Akreditasi KAN</span>
                            </label>
                            <input id="lpk-accreditation-number" name="accreditation_number" value="{{ old('accreditation_number', $lpk->accreditation_number ?: $lpk->registration_number) }}" maxlength="50" placeholder="Contoh: LP-077-IDN" class="lpk-form-v2-input @error('accreditation_number') is-invalid @enderror">
                            @error('accreditation_number')
                                <span class="lpk-form-v2-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="lpk-form-v2-field">
                            <label class="lpk-form-v2-label" for="lpk-status">
                                <span>Status Akreditasi</span>
                            </label>
                            <select id="lpk-status" name="status" class="lpk-form-v2-select @error('status') is-invalid @enderror">
                                <option value="ACTIVE" @selected(old('status', $lpk->status ?: 'ACTIVE') === 'ACTIVE')>Aktif</option>
                                <option value="SUSPENDED" @selected(old('status', $lpk->status) === 'SUSPENDED')>Dibekukan</option>
                                <option value="INACTIVE" @selected(old('status', $lpk->status) === 'INACTIVE')>Tidak Aktif</option>
                            </select>
                            @error('status')
                                <span class="lpk-form-v2-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- PIC Internal (Jika Admin) --}}
                    @if(auth()->user()?->isAdmin() && isset($pics) && $pics->isNotEmpty())
                        <div class="lpk-form-v2-field">
                            <label class="lpk-form-v2-label" for="lpk-pic-id">
                                <span>PIC Penanggung Jawab Internal</span>
                            </label>
                            <select id="lpk-pic-id" name="pic_id" class="lpk-form-v2-select @error('pic_id') is-invalid @enderror">
                                <option value="">-- Belum Ditugaskan --</option>
                                @foreach($pics as $p)
                                    <option value="{{ $p->id }}" @selected(old('pic_id', $lpk->pic_id) == $p->id)>{{ $p->name }} ({{ $p->email }})</option>
                                @endforeach
                            </select>
                            @error('pic_id')
                                <span class="lpk-form-v2-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>

                {{-- KARTU 2: Ruang Lingkup Akreditasi (Scope) --}}
                <div class="lpk-form-v2-card">
                    <div class="lpk-form-v2-card-head">
                        <div class="lpk-form-v2-card-icon">
                            <x-icon name="layers" size="18" />
                        </div>
                        <div class="lpk-form-v2-card-head-text">
                            <h2>Ruang Lingkup Akreditasi (Scope)</h2>
                            <p>Kompetensi teknis, matriks bahan, metode standar, dan spesifikasi pengukuran yang diakreditasi.</p>
                        </div>
                    </div>

                    <div class="lpk-form-v2-field">
                        <label class="lpk-form-v2-label" for="lpk-scope">
                            <span>Uraian Lingkup Akreditasi</span>
                        </label>
                        <textarea id="lpk-scope" name="scope" rows="7" maxlength="4000" class="lpk-form-v2-textarea lpk-form-v2-textarea-scope @error('scope') is-invalid @enderror" placeholder="Baja lembaran lapis seng, baja lembaran dan gulungan lapis paduan aluminium-seng, pipa baja untuk konstruksi umum...">{{ old('scope', $lpk->scope) }}</textarea>
                        @error('scope')
                            <span class="lpk-form-v2-error">{{ $message }}</span>
                        @enderror

                        <div class="lpk-form-v2-scope-meta">
                            <span class="lpk-form-v2-scope-hint">
                                <x-icon name="info" size="13" />
                                <span>Pisahkan setiap ruang lingkup dengan koma (,).</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (~35% / 368px) --}}
            <div class="lpk-form-v2-col">
                {{-- KARTU 3: Masa Berlaku Sertifikat --}}
                <div class="lpk-form-v2-card">
                    <div class="lpk-form-v2-card-head">
                        <div class="lpk-form-v2-card-icon">
                            <x-icon name="calendar" size="18" />
                        </div>
                        <div class="lpk-form-v2-card-head-text">
                            <h2>Masa Berlaku Sertifikat</h2>
                            <p>Dihitung otomatis 5 tahun dari tanggal terbit.</p>
                        </div>
                    </div>

                    <div class="lpk-form-v2-row-2col">
                        <div class="lpk-form-v2-field">
                            <label class="lpk-form-v2-label" for="lpk-certificate-date">
                                <span>Terbit</span>
                            </label>
                            <input id="lpk-certificate-date" type="date" name="certificate_date" value="{{ old('certificate_date', $lpk->certificate_date?->format('Y-m-d')) }}" class="lpk-form-v2-input @error('certificate_date') is-invalid @enderror">
                            @error('certificate_date')
                                <span class="lpk-form-v2-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="lpk-form-v2-field">
                            <label class="lpk-form-v2-label" for="lpk-expired-at">
                                <span>Berlaku sampai</span>
                            </label>
                            <input id="lpk-expired-at" type="date" name="expired_at" value="{{ old('expired_at', $lpk->expired_at?->format('Y-m-d')) }}" class="lpk-form-v2-input @error('expired_at') is-invalid @enderror">
                            @error('expired_at')
                                <span class="lpk-form-v2-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="lpk-form-v2-callout-info">
                        <x-icon name="info" size="15" />
                        <span>Tanggal dihitung ulang hanya bila sertifikat diterbitkan kembali oleh KAN.</span>
                    </div>

                    <div class="lpk-form-v2-section-title">SIKLUS PENGAWASAN KAN</div>

                    <div class="lpk-form-v2-milestones">
                        {{-- Surveilen 1 --}}
                        <div class="lpk-form-v2-milestone-item">
                            <div class="lpk-form-v2-ms-icon-wrap {{ $s1Completed ? 'is-completed' : 'is-scheduled' }}">
                                @if($s1Completed)
                                    <x-icon name="check-circle" size="16" />
                                @else
                                    <x-icon name="calendar" size="14" />
                                @endif
                            </div>
                            <div class="lpk-form-v2-ms-content">
                                <span class="lpk-form-v2-ms-title">Surveilen 1</span>
                                <span class="lpk-form-v2-ms-desc">{{ $s1Date }} &bull; {{ $s1StatusLabel }}</span>
                            </div>
                        </div>

                        {{-- Surveilen 2 --}}
                        <div class="lpk-form-v2-milestone-item">
                            <div class="lpk-form-v2-ms-icon-wrap {{ $s2Completed ? 'is-completed' : 'is-scheduled' }}">
                                @if($s2Completed)
                                    <x-icon name="check-circle" size="16" />
                                @else
                                    <x-icon name="calendar" size="14" />
                                @endif
                            </div>
                            <div class="lpk-form-v2-ms-content">
                                <span class="lpk-form-v2-ms-title">Surveilen 2</span>
                                <span class="lpk-form-v2-ms-desc">{{ $s2Date }} &bull; {{ $s2StatusLabel }}</span>
                            </div>
                        </div>

                        {{-- Re-akreditasi --}}
                        <div class="lpk-form-v2-milestone-item">
                            <div class="lpk-form-v2-ms-icon-wrap is-warning">
                                <x-icon name="alert-triangle" size="15" />
                            </div>
                            <div class="lpk-form-v2-ms-content">
                                <span class="lpk-form-v2-ms-title">Re-akreditasi</span>
                                <span class="lpk-form-v2-ms-desc">Batas waktu {{ $raDate }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KARTU 4: Kontak & Fasilitas --}}
                <div class="lpk-form-v2-card">
                    <div class="lpk-form-v2-card-head">
                        <div class="lpk-form-v2-card-icon">
                            <x-icon name="mail" size="18" />
                        </div>
                        <div class="lpk-form-v2-card-head-text">
                            <h2>Kontak &amp; Fasilitas</h2>
                            <p>Informasi narahubung operasional dan domisili laboratorium.</p>
                        </div>
                    </div>

                    <div class="lpk-form-v2-field">
                        <label class="lpk-form-v2-label" for="lpk-email">
                            <span>Email resmi laboratorium</span>
                        </label>
                        <input id="lpk-email" type="email" name="email" value="{{ old('email', $lpk->email) }}" maxlength="100" placeholder="admin@uptlpk.unp.ac.id" class="lpk-form-v2-input @error('email') is-invalid @enderror">
                        @error('email')
                            <span class="lpk-form-v2-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="lpk-form-v2-field">
                        <label class="lpk-form-v2-label" for="lpk-phone">
                            <span>Nomor telepon / hotline</span>
                        </label>
                        <input id="lpk-phone" name="phone" value="{{ old('phone', $lpk->phone) }}" maxlength="30" placeholder="Contoh: (0751) 7055412" class="lpk-form-v2-input @error('phone') is-invalid @enderror">
                        @error('phone')
                            <span class="lpk-form-v2-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="lpk-form-v2-field">
                        <label class="lpk-form-v2-label" for="lpk-address">
                            <span>Alamat lengkap fasilitas</span>
                        </label>
                        <textarea id="lpk-address" name="address" rows="3" maxlength="500" class="lpk-form-v2-textarea lpk-form-v2-textarea-address @error('address') is-invalid @enderror" placeholder="Kawasan PUSPITEK, Gedung 220, Jl. Raya Puspitek, Setu, Tangerang Selatan, Banten 15314">{{ old('address', $lpk->address) }}</textarea>
                        @error('address')
                            <span class="lpk-form-v2-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Zona Bahaya (Danger Zone - Hanya Saat Edit & Punya Izin Hapus) --}}
    @if($lpk->exists && $lpk->canDelete(auth()->user()))
        <div class="lpk-form-v2-danger-card">
            <div class="lpk-form-v2-danger-left">
                <div class="lpk-form-v2-danger-icon">
                    <x-icon name="alert-triangle" size="20" />
                </div>
                <div class="lpk-form-v2-danger-text">
                    <span class="lpk-form-v2-danger-title">Zona Bahaya</span>
                    <span class="lpk-form-v2-danger-desc">Menghapus LPK akan menghapus seluruh data akreditasi, agenda asesmen, dan rekam jejak terkait secara permanen.</span>
                </div>
            </div>
            <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="lpk-form-v2-btn-danger-outline">
                    <x-icon name="trash" size="15" />
                    <span>Hapus LPK ini</span>
                </button>
            </form>
        </div>
    @endif

    {{-- Bilah Aksi Melayang (Sticky Form Footer) --}}
    <div class="lpk-form-v2-sticky-bar">
        <div class="lpk-form-v2-sticky-info">
            <x-icon name="info" size="16" />
            <span>Setiap perubahan tercatat pada log audit akreditasi beserta penggunanya.</span>
        </div>
        <div class="lpk-form-v2-sticky-actions">
            <span id="lpk-dirty-pill" class="lpk-form-v2-dirty-pill">
                <span class="dirty-dot">•</span>
                <span id="lpk-dirty-count">0</span> perubahan belum disimpan
            </span>
            <a class="lpk-form-v2-btn-cancel" href="{{ $lpk->exists ? route('lpks.show', $lpk) : route('lpks.index') }}">
                Batal
            </a>
            <button class="lpk-form-v2-btn-submit" type="submit" form="lpk-main-form" id="lpk-btn-submit">
                <x-icon name="check" size="16" />
                <span>{{ $lpk->exists ? 'Simpan perubahan' : 'Simpan data LPK' }}</span>
            </button>
        </div>
    </div>

    {{-- Modal Riwayat Perubahan (Edit Only) --}}
    @if($lpk->exists)
        <div class="simasadi-modal" id="modal-lpk-history" role="dialog" aria-modal="true" aria-labelledby="modal-history-heading">
            <div class="simasadi-modal-backdrop" onclick="window.closeModal('modal-lpk-history')"></div>
            <div class="simasadi-modal-box" style="max-width: 580px; width: 100%;">
                <div class="simasadi-modal-head">
                    <div class="modal-title-group">
                        <div class="modal-title-icon-badge" style="background: var(--surface-subtle); color: var(--primary);">
                            <x-icon name="clock" size="18" />
                        </div>
                        <div>
                            <h4 id="modal-history-heading" style="margin: 0; font-size: 16px; font-weight: 700;">Riwayat Perubahan &amp; Audit</h4>
                            <p style="margin: 2px 0 0; font-size: 12px; color: var(--muted);">Rekam jejak aktivitas dan pembaruan data akreditasi</p>
                        </div>
                    </div>
                    <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-lpk-history')" aria-label="Tutup modal">&times;</button>
                </div>
                <div class="simasadi-modal-body" style="padding: 20px 24px;">
                    <div style="background: var(--surface-subtle); border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <span style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; font-weight: 600;">Nomor Registrasi</span>
                            <span style="font-size: 13px; font-weight: 600; color: var(--ink);">{{ $lpk->no_reg ?: ($lpk->registration_number ?: '-') }}</span>
                        </div>
                        <div>
                            <span style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; font-weight: 600;">Status Akreditasi</span>
                            <span style="font-size: 13px; font-weight: 600; color: var(--ink);">{{ $lpk->status_label }}</span>
                        </div>
                    </div>

                    <div class="lpk-v2-audit-timeline">
                        <div class="lpk-v2-audit-item">
                            <div class="lpk-v2-audit-marker"></div>
                            <div class="lpk-v2-audit-body">
                                <span class="lpk-v2-audit-title">Pembaruan Terakhir</span>
                                <span class="lpk-v2-audit-sub">{{ $lpk->updated_at ? $lpk->updated_at->translatedFormat('d F Y, H:i') : '-' }} ({{ $lpk->updated_at ? $lpk->updated_at->diffForHumans() : 'baru saja' }})</span>
                                <span style="font-size: 11.5px; color: var(--muted);">Operator / PIC: {{ $lpk->pic ? $lpk->pic->name : 'Sistem / Admin' }}</span>
                            </div>
                        </div>

                        @if($lpk->relationLoaded('assessments') && $lpk->assessments->isNotEmpty())
                            @foreach($lpk->assessments->take(2) as $asm)
                                <div class="lpk-v2-audit-item">
                                    <div class="lpk-v2-audit-marker" style="border-color: #10b981;"></div>
                                    <div class="lpk-v2-audit-body">
                                        <span class="lpk-v2-audit-title">Agenda Asesmen: {{ $asm->title ?: $asm->assessment_type }}</span>
                                        <span class="lpk-v2-audit-sub">{{ $asm->start_at ? $asm->start_at->translatedFormat('d F Y') : 'Jadwal belum ditentukan' }} &bull; Status: {{ $asm->status }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <div class="lpk-v2-audit-item">
                            <div class="lpk-v2-audit-marker" style="border-color: #94a3b8;"></div>
                            <div class="lpk-v2-audit-body">
                                <span class="lpk-v2-audit-title">Pendaftaran Lembaga Pertama Kali</span>
                                <span class="lpk-v2-audit-sub">{{ $lpk->created_at ? $lpk->created_at->translatedFormat('d F Y, H:i') : '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="simasadi-modal-foot" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; border-top: 1px solid var(--line); background: var(--surface-subtle);">
                    <span style="font-size: 11.5px; color: var(--muted);">Semua log tersimpan aman di database SIMASADI</span>
                    <button type="button" class="button ghost button-sm" data-modal-close onclick="window.closeModal('modal-lpk-history')">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mainForm = document.getElementById('lpk-main-form');
    if (!mainForm) return;

    // 1. Otomatisasi Perhitungan Masa Berlaku (+5 Tahun)
    const certInput = document.getElementById('lpk-certificate-date');
    const expInput = document.getElementById('lpk-expired-at');

    if (certInput && expInput) {
        certInput.addEventListener('change', function () {
            if (this.value) {
                const parts = this.value.split('-');
                if (parts.length === 3) {
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10);
                    const d = parseInt(parts[2], 10);
                    if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                        const targetYear = y + 5;
                        const isLeapFeb29 = (m === 2 && d === 29);
                        const finalDay = isLeapFeb29 ? 28 : d;
                        const pad = (n) => String(n).padStart(2, '0');
                        expInput.value = `${targetYear}-${pad(m)}-${pad(finalDay)}`;
                        checkDirty();
                    }
                }
            }
        });
    }

    // 3. Pendeteksi Perubahan Form (Dirty State Tracker)
    const trackedIds = [
        'lpk-name',
        'lpk-no-reg',
        'lpk-accreditation-type',
        'lpk-accreditation-number',
        'lpk-status',
        'lpk-pic-id',
        'lpk-scope',
        'lpk-certificate-date',
        'lpk-expired-at',
        'lpk-email',
        'lpk-phone',
        'lpk-address'
    ];

    const initialValues = {};
    trackedIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            initialValues[id] = el.value;
        }
    });

    const dirtyPill = document.getElementById('lpk-dirty-pill');
    const dirtyCountEl = document.getElementById('lpk-dirty-count');
    const submitBtn = document.getElementById('lpk-btn-submit');

    function checkDirty() {
        let changedCount = 0;
        trackedIds.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.value !== (initialValues[id] ?? '')) {
                changedCount++;
            }
        });

        if (dirtyPill && dirtyCountEl) {
            if (changedCount > 0) {
                dirtyCountEl.textContent = changedCount;
                dirtyPill.classList.add('is-visible');
            } else {
                dirtyPill.classList.remove('is-visible');
            }
        }
    }

    trackedIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', checkDirty);
            el.addEventListener('change', checkDirty);
        }
    });

    // Pastikan tombol submit selalu men-trigger form submission
    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            if (!mainForm.checkValidity()) {
                mainForm.reportValidity();
                return;
            }
        });
    }
});
</script>
@endsection
