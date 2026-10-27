<div class="simasadi-modal" id="modal-quick-add-event" role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
    <div class="simasadi-modal-box" style="max-width: 580px;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge badge-blue" aria-hidden="true">
                    <x-icon name="calendar" size="17" />
                </div>
                <div>
                    <h4 id="quick-add-title" style="margin: 0; font-size: 17px; font-weight: 700;">Buat Agenda Kegiatan Baru</h4>
                    <p id="quick-add-subtitle" style="margin: 2px 0 0 0; font-size: 12.5px; color: var(--muted);">Jadwalkan kegiatan internal atau koordinasi monitoring akreditasi.</p>
                </div>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal(this); if (typeof window.clearCalendarRangeHighlight === 'function') window.clearCalendarRangeHighlight();" aria-label="Tutup modal">&times;</button>
        </div>

        <form method="POST" action="{{ route('calendar.events.store') }}">
            @csrf
            <input type="hidden" name="event_type" id="quick-input-type" value="AGENDA_INTERNAL">

            <div class="simasadi-modal-body">
                <div class="form-grid" style="gap: 12px;">
                    <label class="full">
                        Judul Kegiatan
                        <input type="text" name="title" id="quick-input-title" required maxlength="255" placeholder="Contoh: Rapat Koordinasi Internal / Penilaian Lapangan">
                    </label>

                    <label>
                        Tanggal Mulai
                        <input type="date" name="start_date" id="quick-input-start-date" value="{{ $activeDate->toDateString() }}" required>
                    </label>

                    <label>
                        Tanggal Selesai
                        <input type="date" name="end_date" id="quick-input-end-date" value="{{ $activeDate->toDateString() }}" required>
                    </label>

                    <label class="full">
                        Lokasi / Tautan Rapat
                        <input type="text" name="location" id="quick-input-location" maxlength="255" placeholder="Contoh: Gedung BSN Lt. 3 / Zoom Meeting">
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

                    <label class="full">
                        Catatan
                        <textarea name="notes" id="quick-input-notes" rows="3" maxlength="2000" placeholder="Tambahkan catatan agenda, pokok pembahasan, atau keterangan penting..."></textarea>
                    </label>
                </div>
            </div>

            <div class="modal-form-actions">
                <button type="button" class="button ghost" data-modal-close onclick="window.closeModal(this); if (typeof window.clearCalendarRangeHighlight === 'function') window.clearCalendarRangeHighlight();">
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