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
                                        <a href="{{ route('users.show', $userItem) }}" class="hover-underline" style="color: var(--ink); font-size: 13.5px; font-weight: 700; text-decoration: none;" title="Buka rincian profil dan LPK yang dikerjakan">
                                            {{ $userItem->name }}
                                        </a>
                                        @if(auth()->id() === $userItem->id)
                                            <span class="badge badge-counter" style="font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px;">Akun Anda</span>
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
                                            <span class="badge badge-pic-lead">
                                                {{ $leadCount }} Utama
                                            </span>
                                        @endif
                                        @if($memberCount > 0)
                                            <span class="badge badge-pic-viewer">
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
                        <td style="color: var(--muted); font-size: 12.5px;">
                            {{ $userItem->created_at ? $userItem->created_at->translatedFormat('d M Y') : 'Sistem Bawaan' }}
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <a href="{{ route('users.show', $userItem) }}" class="table-action-btn" title="Lihat rincian LPK" aria-label="Lihat rincian LPK yang dikelola {{ $userItem->name }}">
                                    <x-icon name="lpks" size="14" />
                                    <span class="sr-only">Lihat LPK</span>
                                </a>

                                <a href="{{ route('users.edit', $userItem) }}" class="table-action-btn" title="Edit data anggota" aria-label="Edit data anggota {{ $userItem->name }}">
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
                                        <button type="submit" class="table-action-btn table-action-btn-danger" title="Hapus anggota" aria-label="Hapus akun anggota {{ $userItem->name }}">
                                            <x-icon name="trash" size="14" />
                                            <span class="sr-only">Hapus</span>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="table-action-btn is-disabled" disabled title="Anda tidak dapat menghapus akun Anda sendiri" aria-label="Tidak dapat menghapus akun sendiri">
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
    <div class="empty">
        <div class="empty-icon-wrap" aria-hidden="true">
            <x-icon name="users" size="22" />
        </div>
        <strong class="empty-title">
            {{ $search || $role ? 'Tidak Ada Pengguna yang Cocok' : 'Belum Ada Pengguna' }}
        </strong>
        <p class="empty-desc">
            {{ $search || $role ? 'Tidak ada pengguna yang cocok dengan kriteria filter pencarian Anda.' : 'Belum ada data pengguna yang terdaftar di sistem.' }}
        </p>
        @if($search || $role)
            <a href="{{ route('users.index') }}" class="button secondary empty-action" data-role="reset-filter">
                <x-icon name="x" size="14" />
                <span>Reset filter</span>
            </a>
        @else
            <a href="{{ route('users.create') }}" class="button primary empty-action">
                <x-icon name="plus" size="14" />
                <span>Tambah Pengguna Pertama</span>
            </a>
        @endif
    </div>
@endif
