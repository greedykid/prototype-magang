@extends('layouts.app')

@section('title', 'Tautan Akun Kolaborasi | SIMASADI')

@section('content')
<div class="lpk-header-back-wrap">
    <a href="{{ route('dashboard') }}" class="lpk-back-btn" title="Kembali ke Dasbor">
        <x-icon name="chevron-left" size="16" />
        <span>Dasbor</span>
    </a>
</div>

<div class="page-heading">
    <div>
        <h1>Tautan Akun Kolaborasi</h1>
        <p class="lede">Kelola hak akses pemantauan (viewer) per akun. Akun yang ditautkan dapat melihat seluruh daftar laboratorium, program asesmen, dan kalender kegiatan tanpa mengubah data.</p>
    </div>
</div>

<div class="profile-layout-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; align-items: start;">
    {{-- Panel 1: Akun Viewer yang Ditautkan oleh Pengguna Ini --}}
    <section class="panel">
        <div class="panel-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--line, #e2e8f0);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--info-bg, #e0f2fe); color: var(--info-text, #0284c7); border: 1px solid var(--info-border, #bae6fd); display: inline-flex; align-items: center; justify-content: center;">
                    <x-icon name="link" size="18" />
                </div>
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; margin: 0; color: var(--ink);">Akun Viewer yang Anda Tautkan</h2>
                    <small style="color: var(--muted); font-size: 12px;">Beri izin pantau seluruh data akun Anda ke PIC lain</small>
                </div>
            </div>
            <span class="badge" style="background: var(--neutral-chip-bg, #f1f5f9); color: var(--neutral-chip-text, #475569); border: 1px solid var(--neutral-chip-border, #cbd5e1); font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 5px;">
                {{ $linkedViewers->count() }} Akun
            </span>
        </div>

        {{-- Form Tambah Viewer --}}
        @if($availableUsers->isNotEmpty())
            <form method="POST" action="{{ route('account-links.store') }}" style="background: var(--surface-subtle, var(--surface)); border: 1px solid var(--line); border-radius: 8px; padding: 14px; margin-bottom: 18px;">
                @csrf
                <label for="viewer-select" style="display: block; font-size: 12.5px; font-weight: 600; color: var(--ink); margin-bottom: 6px;">
                    Tautkan PIC Baru sebagai Viewer
                </label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <select id="viewer-select" name="viewer_id" required style="flex: 1; min-width: 200px; font-size: 12.5px; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: 6px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        <option value="">-- Pilih Akun PIC --</option>
                        @foreach($availableUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="button primary" style="font-size: 12.5px; padding: 8px 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <x-icon name="link" size="14" />
                        <span>Tautkan Akun</span>
                    </button>
                </div>
                <small style="display: block; margin-top: 6px; font-size: 11.5px; color: var(--muted);">
                    Akun yang ditautkan akan langsung melihat seluruh LPK binaan Anda, program asesmen, dan kalender kegiatan dengan hak baca (Viewer).
                </small>
            </form>
        @endif

        {{-- Daftar Akun Viewer yang Aktif --}}
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @forelse($linkedViewers as $viewer)
                <div style="background: var(--card-bg, var(--surface)); border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                        <div style="width: 36px; height: 36px; border-radius: 9999px; background: var(--neutral-chip-bg, #f1f5f9); color: var(--neutral-chip-text, #475569); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; border: 1px solid var(--neutral-chip-border, #cbd5e1);">
                            {{ $viewer->initials }}
                        </div>
                        <div style="min-width: 0;">
                            <div style="font-size: 13.5px; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $viewer->name }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $viewer->email }}
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                        <span class="badge" style="background: var(--info-bg, #e0f2fe); color: var(--info-text, #0369a1); border: 1px solid var(--info-border, #bae6fd); font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 5px;">
                            Viewer Akun
                        </span>

                        <form method="POST" action="{{ route('account-links.destroy', $viewer) }}"
                              data-confirm-delete
                              data-confirm-title="Putuskan Akses Viewer?"
                              data-confirm-text="Apakah Anda yakin ingin memutuskan akses viewer untuk akun {{ $viewer->name }}? Akun ini tidak akan dapat lagi memantau LPK, asesmen, dan kalender kegiatan Anda."
                              data-confirm-btn="Ya, Putuskan Akses"
                              style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="button-icon-only text-danger" style="background: none; border: none; padding: 6px; cursor: pointer; color: #ef4444; display: inline-flex; align-items: center;" title="Putuskan akses viewer">
                                <x-icon name="trash" size="15" />
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding: 28px 16px; text-align: center; background: var(--surface-subtle, var(--surface)); border: 1px dashed var(--line); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 9999px; background: var(--neutral-chip-bg, #f1f5f9); color: var(--muted); margin-bottom: 10px;">
                        <x-icon name="users" size="22" />
                    </div>
                    <div style="font-size: 13.5px; font-weight: 600; color: var(--ink);">Belum Ada Akun Viewer Tertaut</div>
                    <p style="font-size: 12px; color: var(--muted); margin: 4px 0 0; max-width: 440px; line-height: 1.5;">
                        Seluruh data laboratorium, asesmen, dan kalender kegiatan hanya dapat diakses oleh akun Anda sendiri.
                    </p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Panel 2: Akun yang Menautkan Anda (Akses Diterima) --}}
    <section class="panel">
        <div class="panel-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--line, #e2e8f0);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--mint, #ecfdf5); color: var(--green, #047857); border: 1px solid rgba(16, 185, 129, 0.3); display: inline-flex; align-items: center; justify-content: center;">
                    <x-icon name="eye" size="18" />
                </div>
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; margin: 0; color: var(--ink);">Akses Viewer yang Diterima</h2>
                    <small style="color: var(--muted); font-size: 12px;">Akun PIC lain yang memberikan izin pantau kepada Anda</small>
                </div>
            </div>
            <span class="badge" style="background: var(--neutral-chip-bg, #f1f5f9); color: var(--neutral-chip-text, #475569); border: 1px solid var(--neutral-chip-border, #cbd5e1); font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 5px;">
                {{ $linkedOwners->count() }} Akun
            </span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            @forelse($linkedOwners as $owner)
                <div style="background: var(--card-bg, var(--surface)); border: 1px solid var(--line); border-radius: 8px; padding: 14px 16px; display: flex; flex-direction: column; gap: 12px;">
                    {{-- Baris Atas: Info PIC (Lebar Penuh tanpa Terpotong) --}}
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <div style="width: 40px; height: 40px; border-radius: 9999px; background: var(--mint, #ecfdf5); color: var(--green, #047857); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; border: 1px solid rgba(16, 185, 129, 0.3);">
                                {{ $owner->initials }}
                            </div>
                            <div style="min-width: 0;">
                                <a href="{{ route('account-links.show', $owner) }}" class="hover-underline" style="font-size: 14px; font-weight: 700; color: var(--ink); text-decoration: none; display: block; word-break: break-word;" title="Buka daftar LPK milik {{ $owner->name }}">
                                    {{ $owner->name }}
                                </a>
                                <div style="font-size: 12px; color: var(--muted); margin-top: 2px; word-break: break-all;">
                                    {{ $owner->email }}
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; flex-shrink: 0;">
                            <span class="badge" style="background: var(--info-bg, #e0f2fe); color: var(--info-text, #0284c7); border: 1px solid var(--info-border, #bae6fd); font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 5px;">
                                {{ $owner->lpks_count }} LPK
                            </span>
                            <span class="badge" style="background: var(--surface-subtle, #f8fafc); color: var(--muted, #64748b); border: 1px solid var(--line, #cbd5e1); font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 5px;">
                                {{ $owner->assessments_count ?? 0 }} Asesmen
                            </span>
                        </div>
                    </div>

                    {{-- Baris Bawah: Tombol Aksi --}}
                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--line, #e2e8f0); padding-top: 10px; margin-top: 2px; flex-wrap: wrap;">
                        <a href="{{ route('account-links.show', [$owner, 'tab' => 'lpks']) }}" class="button primary" style="font-size: 12px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;" title="Buka daftar LPK milik {{ $owner->name }}">
                            <x-icon name="building" size="14" />
                            <span>Lihat LPK ({{ $owner->lpks_count }})</span>
                        </a>

                        <a href="{{ route('account-links.show', [$owner, 'tab' => 'assessments']) }}" class="button secondary" style="font-size: 12px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;" title="Buka program asesmen milik {{ $owner->name }}">
                            <x-icon name="assessments" size="14" />
                            <span>Lihat Asesmen ({{ $owner->assessments_count ?? 0 }})</span>
                        </a>

                        <form method="POST" action="{{ route('account-links.destroy', $owner) }}"
                              data-confirm-delete
                              data-confirm-title="Lepaskan Akses Pemantauan?"
                              data-confirm-text="Apakah Anda ingin melepaskan akses pemantauan ke akun {{ $owner->name }}? Seluruh LPK dan agenda dari akun ini tidak akan lagi tampil di halaman Anda."
                              data-confirm-btn="Ya, Lepas Akses"
                              style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="table-action-btn table-action-btn-danger" style="font-size: 12px; padding: 6px 12px; font-weight: 500;" title="Lepaskan akses pemantauan">
                                Lepas Akses
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding: 28px 16px; text-align: center; background: var(--surface-subtle, var(--surface)); border: 1px dashed var(--line); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 9999px; background: var(--neutral-chip-bg, #f1f5f9); color: var(--muted); margin-bottom: 10px;">
                        <x-icon name="eye" size="22" />
                    </div>
                    <div style="font-size: 13.5px; font-weight: 600; color: var(--ink);">Belum Menerima Akses Akun Lain</div>
                    <p style="font-size: 12px; color: var(--muted); margin: 4px 0 0; max-width: 440px; line-height: 1.5;">
                        Saat ada PIC lain yang menautkan Anda sebagai viewer, seluruh LPK, asesmen, dan kalender mereka akan otomatis muncul di sini.
                    </p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
