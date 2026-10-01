@if($users->count() > 0)
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="min-width: 200px;">Identitas Anggota</th>
                    <th style="min-width: 140px;">Peran &amp; Hak Akses</th>
                    <th style="min-width: 170px;">Laboratorium Ditangani</th>
                    <th style="min-width: 130px;">Terdaftar Sejak</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
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
                                        <a href="{{ route('users.show', $userItem) }}" class="hover-underline" style="color: #1e293b; font-size: 13.5px; font-weight: 700; text-decoration: none;" title="Buka rincian profil dan LPK yang dikerjakan">
                                            {{ $userItem->name }}
                                        </a>
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
                        <td>
                            @php
                                $leadCount = $userItem->lpks_count ?? 0;
                                $memberCount = $userItem->member_lpks_count ?? 0;
                                $totalLab = $leadCount + $memberCount;
                            @endphp
                            @if($totalLab > 0)
                                <a href="{{ route('users.show', $userItem) }}" class="hover-underline" style="display: inline-flex; flex-direction: column; gap: 3px; text-decoration: none;" title="Lihat rincian {{ $totalLab }} laboratorium yang dikerjakan">
                                    <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                                        @if($leadCount > 0)
                                            <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 9999px;">
                                                {{ $leadCount }} Utama
                                            </span>
                                        @endif
                                        @if($memberCount > 0)
                                            <span style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 9999px;">
                                                {{ $memberCount }} Viewer
                                            </span>
                                        @endif
                                    </div>
                                    <span style="font-size: 11px; color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 3px;">
                                        <span>Lihat LPK</span>
                                        <x-icon name="chevron-right" size="12" />
                                    </span>
                                </a>
                            @else
                                <span style="color: var(--muted); font-size: 12px;">0 Lab</span>
                            @endif
                        </td>
                        <td style="color: #475569; font-size: 12.5px;">
                            {{ $userItem->created_at ? $userItem->created_at->translatedFormat('d M Y') : 'Sistem Bawaan' }}
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                <a href="{{ route('users.show', $userItem) }}" class="button secondary" style="padding: 6px 10px; min-height: 32px;" title="Lihat rincian LPK">
                                    <x-icon name="lpks" size="14" />
                                    <span class="sr-only">Lihat LPK</span>
                                </a>

                                <a href="{{ route('users.edit', $userItem) }}" class="button secondary" style="padding: 6px 10px; min-height: 32px;" title="Edit data anggota">
                                    <x-icon name="edit" size="14" />
                                    <span class="sr-only">Edit</span>
                                </a>

                                @if(auth()->id() !== $userItem->id)
                                    <form method="POST" action="{{ route('users.destroy', $userItem) }}"
                                          data-confirm-delete
                                          data-confirm-title="Hapus Akun Pengguna?"
                                          data-confirm-text="Apakah Anda yakin ingin menghapus akun anggota {{ $userItem->name }}?"
                                          data-confirm-btn="Ya, Hapus Akun"
                                          style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button secondary" style="padding: 6px 10px; min-height: 32px; color: #b91c1c; border-color: #fecaca;" title="Hapus anggota">
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
            <a href="{{ route('users.index') }}" class="button secondary" data-role="reset-filter">Reset Filter</a>
        @else
            <a href="{{ route('users.create') }}" class="button primary">Tambah Pengguna Pertama</a>
        @endif
    </div>
@endif
