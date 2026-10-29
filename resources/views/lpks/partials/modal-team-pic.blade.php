<div class="simasadi-modal" id="modal-manage-team-pic" role="dialog" aria-modal="true" aria-labelledby="modal-team-pic-title">
    <div class="simasadi-modal-backdrop" onclick="window.closeModal('modal-manage-team-pic')"></div>
    <div class="simasadi-modal-box modal-lg" style="max-width: 620px; width: 100%;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge" style="background: var(--surface-subtle); color: var(--primary);">
                    <x-icon name="users" size="18" />
                </div>
                <div>
                    <h4 id="modal-team-pic-title" style="margin: 0; font-size: 16px; font-weight: 700;">Kelola Tim PIC Laboratorium</h4>
                    <p style="margin: 2px 0 0; font-size: 12px; color: var(--muted);">Atur penugasan PIC utama, staf pendamping, dan akses pemantauan (viewer).</p>
                </div>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-manage-team-pic')" aria-label="Tutup modal">&times;</button>
        </div>

        <div class="simasadi-modal-body" style="padding: 20px 24px; display: flex; flex-direction: column; gap: 20px;">
            {{-- Bagian 1: PIC Penanggung Jawab Utama --}}
            <div style="background: var(--surface-subtle); border: 1px solid var(--line); border-radius: var(--radius-md, 8px); padding: 14px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">PIC Penanggung Jawab Utama</span>
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('lpks.edit', $lpk) }}#lpk-pic-id" class="lpk-v2-card-link" style="font-size: 11.5px;">Ganti PIC Utama &rsaquo;</a>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #0075de; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                        {{ $lpk->pic ? $lpk->pic->initials : 'HW' }}
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 13.5px; font-weight: 700; color: var(--ink);">{{ $lpk->pic ? $lpk->pic->name : 'Belum Ditugaskan' }}</span>
                        <span style="font-size: 12px; color: var(--muted);">{{ $lpk->pic ? $lpk->pic->email : '-' }} &bull; {{ $lpk->pic && $lpk->pic->isAdmin() ? 'Ketua Tim (Admin)' : 'Ketua LPK (PIC Utama)' }}</span>
                    </div>
                </div>
            </div>

            {{-- Bagian 2: Daftar Anggota Tim Tambahan & Viewer --}}
            <div>
                <span style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-bottom: 10px;">
                    Anggota Kolaborasi &amp; Viewer ({{ $lpk->members->count() + ($lpk->pic?->linkedViewers?->count() ?? 0) }})
                </span>

                @php
                    $hasAnyMembers = $lpk->members->isNotEmpty() || ($lpk->pic && $lpk->pic->linkedViewers && $lpk->pic->linkedViewers->isNotEmpty());
                @endphp

                @if($hasAnyMembers)
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        {{-- Anggota LPK ($lpk->members) --}}
                        @foreach($lpk->members as $member)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface); border: 1px solid var(--line); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                        {{ $member->initials }}
                                    </div>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-size: 13px; font-weight: 600; color: var(--ink);">{{ $member->name }}</span>
                                        <span style="font-size: 11.5px; color: var(--muted);">{{ $member->email }} &bull; <strong style="color: var(--primary);">{{ $member->pivot->role === 'lead' ? 'PIC Pendamping' : 'Viewer (Tim LPK)' }}</strong></span>
                                    </div>
                                </div>

                                @if($canManageLpk)
                                    <form method="POST" action="{{ route('lpks.members.destroy', [$lpk, $member]) }}" onsubmit="return confirm('Apakah Anda yakin ingin memutuskan akses PIC {{ $member->name }} dari laboratorium ini?');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button ghost button-xs" style="color: #dc2626; border-color: #fecdd3;" title="Putuskan akses">
                                            <x-icon name="trash" size="13" />
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach

                        {{-- Tautan Akun Global Viewer ($lpk->pic->linkedViewers) --}}
                        @if($lpk->pic && $lpk->pic->linkedViewers)
                            @foreach($lpk->pic->linkedViewers as $viewer)
                                @if(! $lpk->members->contains($viewer))
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface); border: 1px dashed var(--line); border-radius: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                                {{ $viewer->initials }}
                                            </div>
                                            <div style="display: flex; flex-direction: column;">
                                                <span style="font-size: 13px; font-weight: 600; color: var(--ink);">{{ $viewer->name }}</span>
                                                <span style="font-size: 11.5px; color: var(--muted);">{{ $viewer->email }} &bull; <em>Viewer Akun Tertaut (Global)</em></span>
                                            </div>
                                        </div>
                                        <span class="lpk-chip-neutral" style="font-size: 10.5px;">Global Viewer</span>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                @else
                    <div style="padding: 16px; background: var(--surface-subtle); border: 1px dashed var(--line); border-radius: 8px; text-align: center; color: var(--muted); font-size: 12.5px;">
                        Belum ada anggota staf atau viewer tambahan yang ditautkan ke LPK ini.
                    </div>
                @endif
            </div>

            {{-- Bagian 3: Tambah Anggota PIC Baru --}}
            @if($canManageLpk)
                @php
                    $availableList = $availablePics ?? \App\Models\User::where('role', \App\Models\User::ROLE_PIC)
                        ->where('id', '!=', $lpk->pic_id)
                        ->whereNotIn('id', $lpk->members->pluck('id'))
                        ->orderBy('name')
                        ->get();
                @endphp

                <div style="border-top: 1px solid var(--line); padding-top: 16px;">
                    <span style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-bottom: 10px;">
                        Tautkan Akun PIC ke Laboratorium
                    </span>

                    @if($availableList->isNotEmpty())
                        <form method="POST" action="{{ route('lpks.members.store', $lpk) }}" style="display: flex; flex-direction: column; gap: 12px;">
                            @csrf
                            <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 10px;">
                                <div>
                                    <label style="display: block; font-size: 11.5px; font-weight: 600; color: var(--ink); margin-bottom: 4px;">Pilih Akun PIC</label>
                                    <select name="user_id" required style="width: 100%; font-size: 12.5px; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--surface); color: var(--ink);">
                                        <option value="">-- Pilih Akun PIC --</option>
                                        @foreach($availableList as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 11.5px; font-weight: 600; color: var(--ink); margin-bottom: 4px;">Peran / Akses</label>
                                    <select name="role" required style="width: 100%; font-size: 12.5px; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--surface); color: var(--ink);">
                                        <option value="viewer">Viewer (Hanya Lihat)</option>
                                        <option value="lead">PIC Pendamping (Kelola)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                                <button type="submit" class="button primary button-sm" style="display: inline-flex; align-items: center; gap: 5px;">
                                    <x-icon name="plus" size="14" />
                                    <span>Tautkan ke Tim PIC</span>
                                </button>
                            </div>
                        </form>
                    @else
                        <p style="margin: 0; font-size: 12px; color: var(--muted);">Semua akun PIC yang tersedia sudah terdaftar pada laboratorium ini.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="simasadi-modal-foot" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; border-top: 1px solid var(--line); background: var(--surface-subtle);">
            <a href="{{ route('account-links.index') }}" class="lpk-v2-card-link" style="font-size: 12px;">Buka Manajemen Tautan Akun Global &rsaquo;</a>
            <button type="button" class="button ghost button-sm" data-modal-close onclick="window.closeModal('modal-manage-team-pic')">Tutup</button>
        </div>
    </div>
</div>
