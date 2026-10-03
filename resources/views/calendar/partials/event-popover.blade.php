<div class="gcal-popover" id="gcal-event-popover" role="dialog" aria-modal="false" aria-labelledby="popover-title" aria-hidden="true" style="display: none;">
    <div class="gcal-popover-card">
        <div class="gcal-popover-head">
            <span class="gcal-popover-cat-pill" id="popover-cat-badge">Agenda</span>
            <button type="button" class="gcal-popover-close" onclick="window.closeEventPopover(this)" aria-label="Tutup">
                <x-icon name="x" size="16" />
            </button>
        </div>

        <div class="gcal-popover-body">
            <h3 id="popover-title">Nama Lembaga Pemohon</h3>
            <span id="popover-lpk" style="display: none;"></span>
            <div class="gcal-popover-row" id="popover-time-wrap">
                <x-icon name="calendar" size="16" />
                <span id="popover-time">Senin, 21 September 2026 - 09:00 - 11:00 WIB</span>
            </div>
            <div class="gcal-popover-row" id="popover-agenda-wrap">
                <x-icon name="assessments" size="16" />
                <span id="popover-agenda">Judul Agenda</span>
            </div>
            <div class="gcal-popover-row" id="popover-location-wrap">
                <x-icon name="map-pin" size="16" />
                <span id="popover-location">Lokasi Asesmen</span>
            </div>
            <div class="gcal-popover-notes" id="popover-notes-wrap" style="display: none;">
                <p id="popover-notes"></p>
            </div>
        </div>

        <div class="gcal-popover-footer">
            <a href="#" target="_blank" rel="noopener noreferrer" class="button secondary btn-sm gcal-btn-gcal" id="popover-gcal-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" x2="16" y1="2" y2="6"/>
                    <line x1="8" x2="8" y1="2" y2="6"/>
                    <line x1="3" x2="21" y1="10" y2="10"/>
                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                </svg>
                <span>Google Calendar</span>
            </a>
            <div class="gcal-popover-actions-right">
                <a href="#" class="button secondary btn-sm" id="popover-edit-link" onclick="window.closeEventPopover(this)" style="display: none;">
                    <x-icon name="edit" size="14" />
                    <span id="popover-edit-label">Ubah</span>
                </a>
                <a href="#" class="button primary btn-sm" id="popover-detail-link" onclick="window.closeEventPopover(this)">
                    <span>Detail Lengkap</span>
                </a>
            </div>
        </div>
    </div>
</div>