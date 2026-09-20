<div class="simasadi-modal" id="modal-quick-add-event" role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
    <div class="simasadi-modal-box" style="max-width: 580px;">
        <div class="simasadi-modal-head">
            <div>
                <h4 id="quick-add-title" style="margin: 0; font-size: 18px; font-weight: 700;">Buat Agenda Kegiatan Baru</h4>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted);">Jadwalkan kegiatan internal atau koordinasi monitoring akreditasi.</p>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal(this)" aria-label="Tutup modal">&times;</button>
        </div>

        <form method="POST" action="{{ route('calendar.events.store') }}" style="display: grid; gap: 14px; margin-top: 14px;">
            @csrf
            <div class="form-grid" style="gap: 12px;">
                <label class="full">
                    Judul Kegiatan
                    <input type="text" name="title" id="quick-input-title" required placeholder="Contoh: Rapat Komite Akreditasi Laboratorium">
                </label>

                <label class="full">
                    Lembaga Terkait (LPK)
                    <select name="lpk_id" id="quick-input-lpk" required>
                        <option value="">Pilih Lembaga Penilaian Kesesuaian</option>
                        @foreach($lpks as $lpk)
                            <option value="{{ $lpk->id }}">{{ $lpk->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Tanggal Mulai
                    <input type="date" name="start_date" id="quick-input-start-date" value="{{ $activeDate->toDateString() }}" required>
                </label>

                <label>
                    Jam Mulai (WIB)
                    <input type="time" name="start_time" id="quick-input-start-time" value="09:00" required>
                </label>

                <label>
                    Tanggal Selesai
                    <input type="date" name="end_date" id="quick-input-end-date" value="{{ $activeDate->toDateString() }}" required>
                </label>

                <label>
                    Jam Selesai (WIB)
                    <input type="time" name="end_time" id="quick-input-end-time" value="11:00" required>
                </label>

                <label class="full">
                    Lokasi / Tautan Rapat
                    <input type="text" name="location" id="quick-input-location" placeholder="Contoh: Gedung BSN Lt. 3 / Zoom Meeting">
                </label>

                <label class="full">
                    Status
                    <select name="status" id="quick-input-status">
                        <option value="PLANNED" selected>Direncanakan</option>
                        <option value="IN_PROGRESS">Sedang Berlangsung</option>
                        <option value="COMPLETED">Selesai</option>
                        <option value="CANCELLED">Dibatalkan</option>
                    </select>
                </label>
            </div>

            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal(this)">
                    Batal
                </button>
                <button type="submit" class="button primary">
                    <x-icon name="plus" size="16" />
                    <span>Simpan ke Kalender</span>
                </button>
            </div>
        </form>
    </div>
</div>