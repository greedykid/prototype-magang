{{-- Modal: Kelola Lokasi & Cabang LPK (Sistem Multilokasi KAN U-05) --}}
<div class="simasadi-modal" id="modal-manage-location" role="dialog" aria-modal="true" aria-labelledby="modal-location-heading">
    <div class="simasadi-modal-box" style="max-width: min(580px, 100%); width: 100%; box-sizing: border-box;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge badge-blue" aria-hidden="true">
                    <x-icon name="building" size="17" />
                </div>
                <h4 id="modal-location-heading">Tambah Lokasi / Cabang Fasilitas</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-manage-location')" aria-label="Tutup modal">&times;</button>
        </div>

        <form id="form-manage-location" method="POST" action="{{ route('lpks.locations.store', $lpk) }}">
            @csrf
            <div id="location-method-spoof"></div>

            <div class="simasadi-modal-body">
                <div style="display: grid; gap: 16px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    {{-- Grup 1: Informasi Fasilitas & Alamat --}}
                    <div style="display: grid; gap: 10px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted);">
                            Informasi Fasilitas &amp; Alamat
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
                            <label class="modal-field-label">
                                <span>Nama Lokasi / Cabang <strong style="color: var(--danger, #ef4444);">*</strong></span>
                                <input type="text" id="loc_input_name" name="name" required maxlength="255" placeholder="Contoh: Lab Cabang Surabaya" class="modal-form-input">
                            </label>

                            <label class="modal-field-label">
                                <span>Jenis Fasilitas <strong style="color: var(--danger, #ef4444);">*</strong></span>
                                <select id="loc_input_type" name="location_type" required class="modal-form-input">
                                    @foreach(\App\Models\LpkLocation::LOCATION_TYPES as $typeVal => $typeLabel)
                                        <option value="{{ $typeVal }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <label class="modal-field-label">
                            <span>Alamat Lengkap Fasilitas <strong style="color: var(--danger, #ef4444);">*</strong></span>
                            <textarea id="loc_input_address" name="address" required rows="2" maxlength="1000" placeholder="Jl. Alamat lengkap gedung / kawasan industri..." class="modal-form-input" style="min-height: 56px; resize: vertical;"></textarea>
                        </label>
                    </div>

                    {{-- Grup 2: Kontak & Operasional (Opsional) --}}
                    <div style="display: grid; gap: 10px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted);">
                            Kontak &amp; Operasional (Opsional)
                        </div>

                        <label class="modal-field-label">
                            <span>Penanggung Jawab (PIC)</span>
                            <input type="text" id="loc_input_contact_person" name="contact_person" maxlength="150" placeholder="Nama kepala cabang atau manajer operasional" class="modal-form-input">
                        </label>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <label class="modal-field-label">
                                <span>Nomor Telepon</span>
                                <input type="text" id="loc_input_contact_phone" name="contact_phone" maxlength="50" placeholder="(031) 8432xxx" class="modal-form-input">
                            </label>

                            <label class="modal-field-label">
                                <span>Email Resmi</span>
                                <input type="email" id="loc_input_contact_email" name="contact_email" maxlength="100" placeholder="cabang.sby@lpk.co.id" class="modal-form-input">
                            </label>
                        </div>

                        <label class="modal-field-label">
                            <span>Catatan Ruang Lingkup Khusus</span>
                            <textarea id="loc_input_scope_notes" name="scope_notes" rows="2" maxlength="2000" placeholder="Lingkup pengujian atau parameter spesifik yang dilayani di fasilitas cabang ini..." class="modal-form-input" style="min-height: 56px; resize: vertical;"></textarea>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-form-actions">
                <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('modal-manage-location')">Batal</button>
                <button type="submit" id="btn-save-location" class="button primary">Simpan Lokasi</button>
            </div>
        </form>
    </div>
</div>

<script>
window.openCreateLocationModal = function() {
    const form = document.getElementById('form-manage-location');
    const heading = document.getElementById('modal-location-heading');
    const spoof = document.getElementById('location-method-spoof');
    const btnSave = document.getElementById('btn-save-location');

    if (!form) return;

    form.reset();
    form.action = "{{ route('lpks.locations.store', $lpk) }}";
    if (spoof) spoof.innerHTML = '';
    if (heading) heading.textContent = 'Tambah Cabang Fasilitas';
    if (btnSave) btnSave.textContent = 'Simpan Lokasi';

    // Default jenis fasilitas ke Laboratorium Cabang
    const selectType = document.getElementById('loc_input_type');
    if (selectType) {
        selectType.value = '{{ \App\Models\LpkLocation::TYPE_BRANCH_LAB }}';
    }

    window.openModal('modal-manage-location');
};

window.openEditLocationModal = function(data) {
    const form = document.getElementById('form-manage-location');
    const heading = document.getElementById('modal-location-heading');
    const spoof = document.getElementById('location-method-spoof');
    const btnSave = document.getElementById('btn-save-location');

    if (!form || !data) return;

    form.action = `/lpks/{{ $lpk->id }}/locations/${data.id}`;
    if (spoof) spoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
    if (heading) heading.textContent = 'Ubah Data Lokasi Fasilitas';
    if (btnSave) btnSave.textContent = 'Simpan Perubahan';

    document.getElementById('loc_input_name').value = data.name || '';

    // Sinkronisasi jenis fasilitas: jika is_main true maka HEAD_OFFICE, jika tidak maka BRANCH_LAB
    const selectType = document.getElementById('loc_input_type');
    if (selectType) {
        selectType.value = data.is_main ? '{{ \App\Models\LpkLocation::TYPE_HEAD_OFFICE }}' : '{{ \App\Models\LpkLocation::TYPE_BRANCH_LAB }}';
    }

    document.getElementById('loc_input_address').value = data.address || '';
    document.getElementById('loc_input_contact_person').value = data.contact_person || '';
    document.getElementById('loc_input_contact_phone').value = data.contact_phone || '';
    document.getElementById('loc_input_contact_email').value = data.contact_email || '';
    document.getElementById('loc_input_scope_notes').value = data.scope_notes || '';

    window.openModal('modal-manage-location');
};
</script>