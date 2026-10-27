@php
    if ($lpk->locations->isEmpty()) {
        $lpk->ensureMainLocation();
        $lpk->load('locations.assessments');
    }
    $canManage = $lpk->canManage(Auth::user());
    $locations = $lpk->locations->sortByDesc('is_main');
    $auditSummary = $lpk->multilocationAuditSummary();
@endphp

<div class="lpk-form-card" id="card-locations-multisite">
    <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 14px;">
        <div class="lpk-card-icon-wrap icon-wrap-blue">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 21h18"/>
                <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                <path d="M9 9h1"/>
                <path d="M9 13h1"/>
                <path d="M9 17h1"/>
                <path d="M14 9h1"/>
                <path d="M14 13h1"/>
                <path d="M14 17h1"/>
            </svg>
        </div>
        <div class="lpk-card-header-text" style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h2 style="font-size: 15.5px; margin: 0; color: var(--ink); display: flex; align-items: center; gap: 6px;">
                        <span>Fasilitas &amp; Daftar Cabang</span>
                        <span style="font-size: 12px; font-weight: 600; padding: 1px 7px; border-radius: 10px; background: var(--surface-subtle); border: 1px solid var(--line); color: var(--muted);">
                            {{ $locations->count() }} Lokasi
                        </span>
                    </h2>
                    <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--muted);">
                        Pemantauan audit seluruh cabang fasilitas dalam siklus 5 tahun.
                    </p>
                </div>
                @if($canManage)
                    <button type="button" class="button secondary button-xs" onclick="window.openCreateLocationModal()" style="display: inline-flex; align-items: center; gap: 4px;">
                        <x-icon name="plus" size="12" />
                        <span>Tambah Cabang</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Kepatuhan Audit KAN jika Multilokasi --}}
    @if($lpk->isMultilocation())
        <div class="lpk-multisite-compliance-box {{ $auditSummary['is_fully_assessed'] ? 'is-complete' : 'is-pending' }}">
            <x-icon name="{{ $auditSummary['is_fully_assessed'] ? 'check-circle' : 'alert-triangle' }}" size="16" class="lpk-multisite-compliance-icon" />
            <div class="lpk-multisite-compliance-text">
                <strong class="lpk-multisite-compliance-title">Kepatuhan Asesmen Cabang:</strong>
                @if($auditSummary['is_fully_assessed'])
                    <span class="lpk-compliance-complete-text">
                        Lengkap: Seluruh ({{ $auditSummary['total'] }}) lokasi cabang telah diasesmen pada siklus ini.
                    </span>
                @else
                    <span class="lpk-compliance-pending-text">
                        <strong>{{ $auditSummary['assessed'] }} dari {{ $auditSummary['total'] }}</strong> lokasi telah diasesmen. <strong>{{ $auditSummary['unassessed'] }} cabang</strong> belum dikunjungi pada siklus ini.
                    </span>
                @endif
            </div>
        </div>
    @endif

    {{-- Daftar Kartu Lokasi --}}
    <div class="lpk-locations-list">
        @forelse($locations as $loc)
            @php
                $latestAssessment = $loc->latestAssessment();
                $isAssessedInCycle = $loc->isAssessedInCycle($lpk->cycle_base_date, $lpk->cycle_expired_at);
            @endphp
            <div class="lpk-location-row">
                <div class="lpk-loc-header">
                    <div class="lpk-loc-title-group">
                        <h4 class="lpk-loc-title">{{ $loc->name }}</h4>
                        @if($loc->is_main)
                            <span class="badge-loc-type is-main">Lokasi Utama</span>
                        @else
                            <span class="badge-loc-type is-branch">{{ $loc->location_type_label }}</span>
                        @endif
                    </div>

                    @if($canManage)
                        <div class="lpk-loc-actions">
                            <button type="button" class="button ghost button-xs lpk-loc-btn-edit" onclick='window.openEditLocationModal(@json($loc))' title="Ubah data lokasi ini">
                                <x-icon name="edit" size="12" />
                                <span>Ubah</span>
                            </button>
                            @if(!$loc->is_main && $locations->count() > 1 && $loc->assessments->count() === 0)
                                <form method="POST"
                                      action="{{ route('lpks.locations.destroy', [$lpk, $loc]) }}"
                                      data-confirm-delete
                                      data-confirm-title="Hapus Lokasi Cabang?"
                                      data-confirm-html="Apakah Anda yakin ingin menghapus lokasi cabang <strong>{{ e($loc->name) }}</strong>? Tindakan ini tidak dapat dibatalkan."
                                      data-confirm-btn="Ya, Hapus Cabang"
                                      style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button ghost button-xs text-danger" title="Hapus cabang" aria-label="Hapus cabang {{ $loc->name }}">
                                        <x-icon name="trash" size="12" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="lpk-loc-address-row">
                    <x-icon name="map-pin" size="13" class="lpk-loc-address-icon" />
                    <span class="lpk-loc-address-text">{{ $loc->address }}</span>
                </div>

                @if($loc->contact_person || $loc->contact_phone || $loc->contact_email)
                    <div class="lpk-loc-contacts-row">
                        @if($loc->contact_person)
                            <span class="lpk-loc-contact-chip">
                                <x-icon name="user" size="11" />
                                <span>{{ $loc->contact_person }}</span>
                            </span>
                        @endif
                        @if($loc->contact_phone)
                            <span class="lpk-loc-contact-chip">
                                <x-icon name="phone" size="11" />
                                <span>{{ $loc->contact_phone }}</span>
                            </span>
                        @endif
                        @if($loc->contact_email)
                            <span class="lpk-loc-contact-chip">
                                <x-icon name="mail" size="11" />
                                <span>{{ $loc->contact_email }}</span>
                            </span>
                        @endif
                    </div>
                @endif

                <div class="lpk-loc-status-footer">
                    <div class="lpk-loc-audit-info">
                        @if($latestAssessment)
                            <span class="lpk-audit-status-tag is-assessed">
                                <x-icon name="check-circle" size="12" />
                                <span>Terakhir Diasesmen: <strong>{{ $latestAssessment->start_at->format('d/m/Y') }}</strong> ({{ $latestAssessment->short_type }})</span>
                            </span>
                        @else
                            <span class="lpk-audit-status-tag is-unassessed">
                                <x-icon name="alert-triangle" size="12" />
                                <span>Belum pernah diasesmen di siklus ini</span>
                            </span>
                        @endif
                    </div>

                    @if($canManage && !$latestAssessment)
                        <div class="lpk-loc-schedule-action">
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'lpk_location_id' => $loc->id]) }}" class="button secondary button-xs" title="Jadwalkan agenda kunjungan asesmen untuk cabang ini">
                                <x-icon name="plus" size="11" />
                                <span>Jadwalkan</span>
                            </a>
                        </div>
                    @endif
                </div>

                @if($loc->scope_notes)
                    <div class="lpk-location-scope-notes">
                        <strong>Lingkup Khusus:</strong> {{ $loc->scope_notes }}
                    </div>
                @endif
            </div>
        @empty
            <div style="text-align: center; padding: 18px; color: var(--muted); font-size: 13px;">
                Belum ada data lokasi fasilitas yang terdaftar.
            </div>
        @endforelse
    </div>
</div>
