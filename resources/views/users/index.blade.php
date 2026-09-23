@extends('layouts.app')

@section('title', 'Manajemen Pengguna | SIMASADI')

@section('content')
<x-page-header
    title="Manajemen Pengguna & PIC"
    subtitle="Kelola akun pengguna, hak akses peran Administrator Unit dan PIC Laboratorium."
>
    <a class="button primary" href="{{ route('users.create') }}">
        <x-icon name="plus" size="16" />
        <span>Tambah Pengguna</span>
    </a>
</x-page-header>

{{-- Kartu Metrik Ringkas --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <x-icon name="users" size="22" />
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">Total Pengguna</div>
            <strong style="font-size: 22px; color: #1e293b; line-height: 1.2;">{{ $totalCount }}</strong>
        </div>
    </div>

    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #ede9fe; color: #6d28d9; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <x-icon name="shield" size="22" />
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">Admin Unit Lab</div>
            <strong style="font-size: 22px; color: #1e293b; line-height: 1.2;">{{ $adminCount }}</strong>
        </div>
    </div>

    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <x-icon name="user" size="22" />
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">PIC Laboratorium</div>
            <strong style="font-size: 22px; color: #1e293b; line-height: 1.2;">{{ $picCount }}</strong>
        </div>
    </div>
</div>

<section class="panel">
    {{-- Form Filter & Pencarian --}}
    <form class="table-filters" method="GET" action="{{ route('users.index') }}">
        <input type="hidden" name="per_page" value="{{ $perPage }}">
        <div class="table-filter-grid">
            <label>
                Cari Pengguna
                <input type="text" name="search" value="{{ $search }}" placeholder="Nama atau alamat email...">
            </label>

            <label>
                Peran Pengguna
                <select name="role">
                    <option value="">Semua Peran</option>
                    <option value="admin" @selected($role === 'admin')>Administrator Unit</option>
                    <option value="pic" @selected($role === 'pic')>PIC Laboratorium</option>
                </select>
            </label>
        </div>

        <div class="table-filter-actions">
            <button class="button secondary" type="submit">Terapkan filter</button>
            @if($search || $role)
                <a class="button ghost" href="{{ route('users.index') }}">Reset filter</a>
            @endif
        </div>
    </form>

    {{-- Tabel Pengguna --}}
    @if($users->count() > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="min-width: 220px;">Identitas Pengguna</th>
                        <th style="min-width: 160px;">Peran &amp; Hak Akses</th>
                        <th style="min-width: 140px;">Terdaftar Sejak</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $userItem)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="user-avatar" aria-hidden="true" style="flex-shrink: 0;">
                                        {{ $userItem->initials }}
                                    </span>
                                    <div style="min-width: 0;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <strong style="color: #1e293b; font-size: 13.5px;">{{ $userItem->name }}</strong>
                                            @if(auth()->id() === $userItem->id)
                                                <span style="font-size: 10px; font-weight: 700; background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px;">Akun Anda</span>
                                            @endif
                                        </div>
                                        <div style="color: var(--muted); font-size: 12px; margin-top: 2px;">{{ $userItem->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-role {{ $userItem->role_badge_class }}">
                                    {{ $userItem->role_label }}
                                </span>
                            </td>
                            <td style="color: #475569; font-size: 12.5px;">
                                {{ $userItem->created_at ? $userItem->created_at->translatedFormat('d M Y') : 'Sistem Bawaan' }}
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <a href="{{ route('users.edit', $userItem) }}" class="button secondary" style="padding: 6px 10px; min-height: 32px;" title="Edit data pengguna">
                                        <x-icon name="edit" size="14" />
                                        <span class="sr-only">Edit</span>
                                    </a>

                                    @if(auth()->id() !== $userItem->id)
                                        <form method="POST" action="{{ route('users.destroy', $userItem) }}" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna {{ addslashes($userItem->name) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button secondary" style="padding: 6px 10px; min-height: 32px; color: #b91c1c; border-color: #fecaca;" title="Hapus pengguna">
                                                <x-icon name="trash" size="14" />
                                                <span class="sr-only">Hapus</span>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="button secondary" disabled style="padding: 6px 10px; min-height: 32px; opacity: 0.4; cursor: not-allowed;" title="Anda tidak dapat menghapus akun Anda sendiri">
                                            <x-icon name="trash" size="14" />
                                            <span class="sr-only">Tidak dapat dihapus</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $users->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div style="text-align: center; padding: 48px 16px;">
            <div style="width: 52px; height: 52px; border-radius: 26px; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <x-icon name="users" size="24" />
            </div>
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">Tidak ada pengguna ditemukan</h3>
            <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px 0;">
                @if($search || $role)
                    Tidak ada pengguna yang cocok dengan kriteria filter pencarian Anda.
                @else
                    Belum ada data pengguna yang terdaftar di sistem.
                @endif
            </p>
            @if($search || $role)
                <a href="{{ route('users.index') }}" class="button secondary">Reset Filter</a>
            @else
                <a href="{{ route('users.create') }}" class="button primary">Tambah Pengguna Pertama</a>
            @endif
        </div>
    @endif
</section>
@endsection
