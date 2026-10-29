@php
    if ($lpk->locations->isEmpty()) {
        $lpk->ensureMainLocation();
        $lpk->load('locations.assessments');
    }
    $canManage = $lpk->canManage(Auth::user());
    $locations = $lpk->locations->sortByDesc('is_main');
    $primaryLoc = $locations->firstWhere('is_main', true) ?: $locations->first();
    $branches = $locations->where('id', '!=', $primaryLoc?->id);
    $auditSummary = $lpk->multilocationAuditSummary();
    $lastAssessedDate = $lpk->assessments->sortByDesc('start_at')->first()?->start_at?->translatedFormat('d M Y');

    // Extract city cleanly from location or lpk address
    $primaryCity = $primaryLoc?->city ?: ($lpk->city ?? null);
    if (!$primaryCity && $primaryLoc?->address) {
        $addrParts = array_map('trim', explode(',', $primaryLoc->address));
        $primaryCity = count($addrParts) >= 2 ? implode(', ', array_slice($addrParts, -2)) : end($addrParts);
    }
    $primaryCity = $primaryCity ?: ($displayCity ?? 'Indonesia');
@endphp

<div class="lpk-v2-card" id="card-locations-multisite">
    <div class="lpk-v2-card-head">
        <h3 class="lpk-v2-card-title">
            <x-icon name="map-pin" size="16" class="text-primary" />
            <span>Lokasi &amp; kontak</span>
            <span class="lpk-v2-roadmap-badge">{{ $locations->count() }} lokasi</span>
        </h3>
        @if($canManage)
            <button type="button" class="lpk-v2-btn-edit" onclick="window.openCreateLocationModal()" style="padding: 4px 10px; font-size: 12px; cursor: pointer;">
                <x-icon name="plus" size="13" />
                <span>Tambah lokasi</span>
            </button>
        @endif
    </div>

    {{-- 1. Fasilitas Utama --}}
    @if($primaryLoc)
        <div class="lpk-v2-loc-primary-box">
            <div class="lpk-v2-loc-head">
                <div class="lpk-v2-loc-name-group">
                    @php
                        $locTitle = $primaryLoc->name;
                        if ($primaryCity && !str_contains(mb_strtolower($locTitle), mb_strtolower($primaryCity))) {
                            $locTitle .= ' — ' . $primaryCity;
                        }
                    @endphp
                    <h4 class="lpk-v2-loc-name">{{ $locTitle }}</h4>
                    <span class="lpk-v2-pill-main">Utama</span>
                    <span class="lpk-v2-pill-synced">Tersinkron</span>
                </div>
                @if($canManage)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" class="lpk-v2-card-link" onclick='window.openEditLocationModal(@json($primaryLoc))' style="background: none; border: none; cursor: pointer; padding: 0;">
                            <x-icon name="edit" size="12" />
                            <span>Ubah</span>
                        </button>
                    </div>
                @endif
            </div>

            <p class="lpk-v2-loc-address">{{ $primaryLoc->address }}</p>

            <div class="lpk-v2-loc-grid-meta">
                <span>
                    <x-icon name="phone" size="12" />
                    <strong>{{ $primaryLoc->contact_phone ?: ($lpk->phone ?: '-') }}</strong>
                </span>
                <span>
                    <x-icon name="mail" size="12" />
                    <strong>{{ $primaryLoc->contact_email ?: ($lpk->email ?: '-') }}</strong>
                </span>
            </div>
        </div>
    @endif

    {{-- 2. Daftar Fasilitas Cabang --}}
    @if($branches->isNotEmpty())
        <div class="lpk-v2-branch-divider">CABANG / SUB-LAB</div>
        <div class="lpk-v2-branch-list">
            @foreach($branches as $branch)
                @php
                    $branchAssessment = $branch->latestAssessment();
                    $isAssessedInCycle = $branch->isAssessedInCycle($lpk->cycle_base_date, $lpk->cycle_expired_at);
                    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($branch->address);
                @endphp
                <div class="lpk-v2-branch-item">
                    <div class="lpk-v2-branch-top">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <strong style="font-size: 13px; color: var(--ink);">{{ $branch->name }}</strong>
                            <span class="lpk-v2-roadmap-badge" style="font-size: 10.5px;">Cabang</span>
                            <span style="font-size: 11px; color: var(--muted);">Diajukan {{ $branch->created_at ? $branch->created_at->translatedFormat('d M Y') : '-' }}</span>
                        </div>
                        <div>
                            @if($branchAssessment)
                                <span class="lpk-v2-status-pill pill-success" style="font-size: 10.5px;">Diasesmen</span>
                            @elseif($isAssessedInCycle)
                                <span class="lpk-v2-status-pill pill-warn" style="font-size: 10.5px;">Dalam proses</span>
                            @else
                                <span class="lpk-v2-status-pill pill-warn" style="font-size: 10.5px; background: #f1f5f9; color: #64748b; border-color: #cbd5e1;">Belum diasesmen</span>
                            @endif
                        </div>
                    </div>

                    <div style="font-size: 12px; color: var(--muted); line-height: 1.4;">
                        {{ $branch->address }}
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 11.5px; color: var(--muted); margin-top: 2px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            @if($branch->contact_phone)
                                <span><x-icon name="phone" size="11" /> {{ $branch->contact_phone }}</span>
                            @endif
                            @if($branch->contact_email)
                                <span><x-icon name="mail" size="11" /> {{ $branch->contact_email }}</span>
                            @endif
                        </div>
                        <div class="lpk-v2-branch-actions">
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" title="Buka di Google Maps">
                                <span>Buka peta</span>
                                <x-icon name="external-link" size="11" />
                            </a>
                            @if($canManage)
                                <button type="button" onclick='window.openEditLocationModal(@json($branch))' title="Ubah cabang">
                                    <span>Ubah</span>
                                    <x-icon name="edit" size="11" />
                                </button>
                                @if($branch->assessments->count() === 0)
                                    <form method="POST"
                                          action="{{ route('lpks.locations.destroy', [$lpk, $branch]) }}"
                                          data-confirm-delete
                                          data-confirm-title="Hapus Lokasi Cabang?"
                                          data-confirm-html="Apakah Anda yakin ingin menghapus lokasi cabang <strong>{{ e($branch->name) }}</strong>?"
                                          data-confirm-btn="Ya, Hapus Cabang"
                                          style="display: inline; margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #dc2626;" title="Hapus cabang">
                                            <x-icon name="trash" size="11" />
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- 3. Footer Lokasi --}}
    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; color: var(--muted); padding-top: 10px; border-top: 1px dashed var(--line); flex-wrap: wrap; gap: 8px;">
        <span style="display: inline-flex; align-items: center; gap: 5px;">
            <x-icon name="check-circle" size="12" class="text-success" />
            <span>Asesmen dikelola per lokasi — Terakhir: {{ $lastAssessedDate ?: 'Belum ada' }}</span>
        </span>
        <a href="#card-asesmen-full" class="lpk-v2-card-link">Kelola semua lokasi &rsaquo;</a>
    </div>
</div>
