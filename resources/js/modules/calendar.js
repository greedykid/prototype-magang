import { openModal } from './modals.js';

// SIMASADI Google Calendar Experience & Interactivity Engine
// Multi-view navigation, quick popovers, real-time live WIB time line,
// instant category & LPK filtering, and direct Google Calendar export
// ==========================================================================

const formatIndonesianDateTimeRange = (start, end) => {
    try {
        const dtfDate = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
        const dtfTime = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });

        const dateStr = dtfDate.format(start);
        const startTimeStr = dtfTime.format(start);
        const endTimeStr = dtfTime.format(end);

        return `${dateStr} · ${startTimeStr} - ${endTimeStr} WIB`;
    } catch {
        const pad = (num) => String(num).padStart(2, '0');
        return `${pad(start.getDate())}/${pad(start.getMonth() + 1)}/${start.getFullYear()} · ${pad(start.getHours())}:${pad(start.getMinutes())} - ${pad(end.getHours())}:${pad(end.getMinutes())} WIB`;
    }
};

const toGoogleCalendarUtc = (date) => {
    return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
};

function showEventPopover(triggerEl, eventData) {
    if (!eventData) return;

    // Deduplicate: If any duplicate popovers exist from prior transitions, keep only the latest one
    const allPopovers = document.querySelectorAll('#gcal-event-popover');
    if (allPopovers.length > 1) {
        let kept = false;
        allPopovers.forEach((p) => {
            if (!kept && p.closest('#page-content-wrapper, .page-wrap')) {
                kept = true;
            } else if (kept) {
                p.remove();
            } else {
                p.remove();
            }
        });
    }

    const popover = document.getElementById('gcal-event-popover');
    if (!popover) return;

    const startDate = new Date(eventData.start_at);
    const endDate = new Date(eventData.end_at);

    // 1. Badge & Header
    const catBadge = popover.querySelector('#popover-cat-badge');
    if (catBadge) {
        catBadge.textContent = eventData.category_label || 'Agenda';
        catBadge.className = `gcal-popover-cat-pill theme-${eventData.color_theme || 'indigo'}`;
    }

    // 2. Title & Date/Time
    // Teks bold utama (#popover-title) menampilkan Nama Lab/LPK agar lebih terhighlight
    const titleEl = popover.querySelector('#popover-title');
    const hasLpk = Boolean(eventData.lpk_name && eventData.lpk_name.trim() && eventData.lpk_name !== 'Internal SIMASADI');

    if (titleEl) {
        titleEl.textContent = hasLpk ? eventData.lpk_name : (eventData.title || 'Agenda Kegiatan');
    }

    const timeEl = popover.querySelector('#popover-time');
    if (timeEl) timeEl.textContent = formatIndonesianDateTimeRange(startDate, endDate);

    // 3. Agenda / Title Row
    // Menampilkan judul agenda (misal "S1", "Surveilen 1", "Batas TP") pada baris agenda khusus
    const agendaWrap = popover.querySelector('#popover-agenda-wrap');
    const agendaEl = popover.querySelector('#popover-agenda');
    const lpkEl = popover.querySelector('#popover-lpk');

    if (lpkEl) {
        lpkEl.textContent = eventData.lpk_name || '';
    }

    if (agendaEl) {
        if (hasLpk && eventData.title) {
            agendaEl.textContent = eventData.title;
            if (agendaWrap) agendaWrap.style.display = 'flex';
        } else if (!hasLpk && eventData.category_label) {
            agendaEl.textContent = eventData.category_label;
            if (agendaWrap) agendaWrap.style.display = 'flex';
        } else if (agendaWrap) {
            agendaWrap.style.display = 'none';
        }
    }

    // 4. Location
    const locWrap = popover.querySelector('#popover-location-wrap');
    const locEl = popover.querySelector('#popover-location');
    if (locWrap && locEl) {
        if (eventData.location && eventData.location.trim()) {
            locEl.textContent = eventData.location;
            locWrap.style.display = 'flex';
        } else {
            locWrap.style.display = 'none';
        }
    }

    // 5. Notes / Description
    const notesWrap = popover.querySelector('#popover-notes-wrap');
    const notesEl = popover.querySelector('#popover-notes');
    if (notesWrap && notesEl) {
        if (eventData.notes && eventData.notes.trim()) {
            notesEl.textContent = eventData.notes;
            notesWrap.style.display = 'block';
        } else {
            notesWrap.style.display = 'none';
        }
    }

    // 6. Direct Google Calendar Add Link
    const gcalLink = popover.querySelector('#popover-gcal-link');
    if (gcalLink) {
        const detailsText = `Kategori: ${eventData.category_label || 'Agenda'}\nLPK: ${eventData.lpk_name || '-'}\nLokasi: ${eventData.location || '-'}\nCatatan: ${eventData.notes || '-'}\n\nDisinkronkan dari Kalender SIMASADI KAN`;
        const gcalUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(eventData.title)}&dates=${toGoogleCalendarUtc(startDate)}/${toGoogleCalendarUtc(endDate)}&details=${encodeURIComponent(detailsText)}&location=${encodeURIComponent(eventData.location || '')}`;
        gcalLink.href = gcalUrl;
    }

    // 7. Action Links: Detail & Edit
    const detailLink = popover.querySelector('#popover-detail-link');
    if (detailLink) {
        detailLink.href = eventData.url || '#';
    }

    const editLink = popover.querySelector('#popover-edit-link');
    const editLabel = popover.querySelector('#popover-edit-label') || editLink?.querySelector('span');
    if (editLink) {
        if (eventData.edit_url && eventData.can_manage !== false && eventData.can_edit !== false) {
            editLink.href = eventData.edit_url;
            if (editLabel) {
                editLabel.textContent = eventData.action_label || 'Ubah';
            }
            editLink.style.display = 'inline-flex';
        } else {
            editLink.style.display = 'none';
        }
    }

    // Teleport popover to document.body so it escapes any ancestor containing block,
    // ensuring getBoundingClientRect coordinates align 1-to-1 with the browser viewport.
    if (popover.parentElement && popover.parentElement !== document.body) {
        if (!popover._simasadiPlaceholder) {
            const placeholder = document.createComment('gcal-popover-placeholder');
            popover.parentElement.insertBefore(placeholder, popover);
            popover._simasadiPlaceholder = placeholder;
        }
        document.body.appendChild(popover);
    }

    // 8. Display & Positioning
    popover.style.display = 'block';
    popover.setAttribute('aria-hidden', 'false');

    // Never position the popover container itself
    popover.style.position = '';
    popover.style.left = '';
    popover.style.top = '';
    popover.style.right = '';
    popover.style.bottom = '';
    popover.style.zIndex = '';

    const card = popover.querySelector('.gcal-popover-card');
    if (card) {
        if (triggerEl && window.innerWidth > 768) {
            const rect = triggerEl.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const cardWidth = Math.min(380, viewportWidth - 32);
            const gap = 10;
            const margin = 16;
            const topbarOffset = 70;

            // Pastikan popover tidak pernah melebihi tinggi layar yang tersedia di laptop
            const maxAvailableHeight = Math.max(240, viewportHeight - topbarOffset - margin);
            card.style.maxHeight = `${maxAvailableHeight}px`;

            // Hitung posisi horizontal (kiri atau kanan chip)
            const spaceRight = viewportWidth - rect.right - gap - margin;
            const spaceLeft = rect.left - gap - margin;

            let left;
            if (spaceRight >= cardWidth) {
                left = rect.right + gap;
            } else if (spaceLeft >= cardWidth) {
                left = rect.left - cardWidth - gap;
            } else {
                if (spaceRight >= spaceLeft) {
                    left = Math.min(rect.right + gap, viewportWidth - cardWidth - margin);
                } else {
                    left = Math.max(margin, rect.left - cardWidth - gap);
                }
            }
            left = Math.max(margin, Math.min(viewportWidth - cardWidth - margin, left));

            // Ukur ketinggian render aktual kartu setelah display block & maxHeight terpasang
            const cardHeight = card.offsetHeight || card.getBoundingClientRect().height || 320;

            // Posisikan secara vertikal dengan jaminan tidak terpotong di batas bawah maupun topbar
            let top = rect.top - 8;
            if (top + cardHeight > viewportHeight - margin) {
                top = viewportHeight - cardHeight - margin;
            }
            if (top < topbarOffset) {
                top = topbarOffset;
            }

            card.style.position = 'fixed';
            card.style.left = `${Math.round(left)}px`;
            card.style.top = `${Math.round(top)}px`;
            card.style.zIndex = '9999';
        } else {
            card.style.position = '';
            card.style.left = '';
            card.style.top = '';
            card.style.maxHeight = '';
            card.style.zIndex = '';
        }
    }
}
window.showEventPopover = showEventPopover;

function returnPopoverToPlaceholder(popover) {
    if (!popover) return;
    if (popover._simasadiPlaceholder && popover._simasadiPlaceholder.parentNode) {
        popover._simasadiPlaceholder.parentNode.insertBefore(popover, popover._simasadiPlaceholder);
        popover._simasadiPlaceholder.remove();
        popover._simasadiPlaceholder = null;
    }
}
window.returnPopoverToPlaceholder = returnPopoverToPlaceholder;

function closeEventPopover(triggerEl) {
    if (triggerEl && triggerEl.closest) {
        const specific = triggerEl.closest('.gcal-popover');
        if (specific) {
            specific.style.display = 'none';
            specific.setAttribute('aria-hidden', 'true');
            const c = specific.querySelector('.gcal-popover-card');
            if (c) {
                c.style.position = '';
                c.style.left = '';
                c.style.top = '';
                c.style.zIndex = '';
            }
            returnPopoverToPlaceholder(specific);
        }
    }

    // Close and reset ALL popovers in the DOM to guarantee zero stray cards
    document.querySelectorAll('.gcal-popover, #gcal-event-popover').forEach((popover) => {
        popover.style.display = 'none';
        popover.setAttribute('aria-hidden', 'true');
        const card = popover.querySelector('.gcal-popover-card');
        if (card) {
            card.style.position = '';
            card.style.left = '';
            card.style.top = '';
            card.style.zIndex = '';
        }
        returnPopoverToPlaceholder(popover);
    });
}
window.closeEventPopover = closeEventPopover;

function quickAddAtRange(startDateStr, endDateStr, timeStr = '09:00') {
    const startDateInput = document.getElementById('quick-input-start-date');
    const endDateInput = document.getElementById('quick-input-end-date');
    const startTimeInput = document.getElementById('quick-input-start-time');
    const endTimeInput = document.getElementById('quick-input-end-time');

    if (startDateInput) startDateInput.value = startDateStr;
    if (endDateInput) endDateInput.value = endDateStr;

    if (timeStr && startTimeInput) {
        startTimeInput.value = timeStr;
        if (endTimeInput) {
            const [h, m] = timeStr.split(':').map(Number);
            const endH = Math.min(23, (h || 9) + 2);
            endTimeInput.value = `${String(endH).padStart(2, '0')}:${String(m || 0).padStart(2, '0')}`;
        }
    }

    const modalHeading = document.getElementById('quick-add-title');
    const modalSubtitle = document.getElementById('quick-add-subtitle');

    if (startDateStr === endDateStr) {
        if (modalHeading) modalHeading.textContent = 'Buat Agenda Kegiatan Baru';
        if (modalSubtitle) modalSubtitle.textContent = 'Jadwalkan kegiatan internal atau koordinasi monitoring akreditasi.';
    } else {
        const startD = new Date(startDateStr);
        const endD = new Date(endDateStr);
        const diffDays = Math.round(Math.abs(endD - startD) / (1000 * 60 * 60 * 24)) + 1;

        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        const formattedStart = startD.toLocaleDateString('id-ID', options);
        const formattedEnd = endD.toLocaleDateString('id-ID', options);

        if (modalHeading) modalHeading.textContent = `Buat Agenda Rentang (${diffDays} Hari)`;
        if (modalSubtitle) modalSubtitle.textContent = `Rentang kegiatan: ${formattedStart} s.d. ${formattedEnd}`;
    }

    initQuickAddDateSync();
    openModal('modal-quick-add-event');
}
window.quickAddAtRange = quickAddAtRange;

function quickAddAt(dateStr, timeStr = '09:00') {
    quickAddAtRange(dateStr, dateStr, timeStr);
}
window.quickAddAt = quickAddAt;

function initQuickAddDateSync() {
    const startInput = document.getElementById('quick-input-start-date');
    const endInput = document.getElementById('quick-input-end-date');
    const subtitle = document.getElementById('quick-add-subtitle');
    const heading = document.getElementById('quick-add-title');

    if (!startInput || !endInput || startInput._syncBound) return;
    startInput._syncBound = true;

    const handleDateChange = () => {
        if (!startInput.value) return;
        if (!endInput.value || endInput.value < startInput.value) {
            endInput.value = startInput.value;
        }

        if (startInput.value === endInput.value) {
            if (heading && !heading.textContent.includes('Ubah')) {
                heading.textContent = 'Buat Agenda Kegiatan Baru';
            }
            if (subtitle) {
                subtitle.textContent = 'Jadwalkan kegiatan internal atau koordinasi monitoring akreditasi.';
            }
        } else {
            const s = new Date(startInput.value);
            const e = new Date(endInput.value);
            const diffDays = Math.round(Math.abs(e - s) / (1000 * 60 * 60 * 24)) + 1;
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            if (heading && !heading.textContent.includes('Ubah')) {
                heading.textContent = `Buat Agenda Rentang (${diffDays} Hari)`;
            }
            if (subtitle) {
                subtitle.textContent = `Rentang kegiatan: ${s.toLocaleDateString('id-ID', options)} s.d. ${e.toLocaleDateString('id-ID', options)}`;
            }
        }
    };

    startInput.addEventListener('change', handleDateChange);
    endInput.addEventListener('change', handleDateChange);
}

function openCreateDropdown(toggleBtn) {
    const menu = document.getElementById('gcal-create-menu');
    const btn = toggleBtn || document.getElementById('gcal-btn-create-toggle');
    if (!menu) return;

    menu.classList.remove('is-closing');
    menu.classList.add('is-open');
    menu.style.display = 'block';
    btn?.setAttribute('aria-expanded', 'true');
    btn?.classList.add('is-active');
}
window.openCreateDropdown = openCreateDropdown;

function closeCreateDropdown() {
    const menu = document.getElementById('gcal-create-menu');
    const toggleBtn = document.getElementById('gcal-btn-create-toggle');
    if (!menu) return;

    if (!menu.classList.contains('is-open') || menu.classList.contains('is-closing')) return;

    menu.classList.add('is-closing');
    toggleBtn?.setAttribute('aria-expanded', 'false');
    toggleBtn?.classList.remove('is-active');

    const handleEnd = () => {
        menu.classList.remove('is-open', 'is-closing');
        menu.style.display = 'none';
        menu.removeEventListener('animationend', handleEnd);
    };

    menu.addEventListener('animationend', handleEnd, { once: true });
    setTimeout(() => {
        if (menu.classList.contains('is-closing')) {
            menu.classList.remove('is-open', 'is-closing');
            menu.style.display = 'none';
        }
    }, 150);
}
window.closeCreateDropdown = closeCreateDropdown;

function toggleCreateDropdown(btn) {
    const menu = document.getElementById('gcal-create-menu');
    const toggleBtn = btn || document.getElementById('gcal-btn-create-toggle');
    if (!menu) return;

    if (menu.classList.contains('is-open') && !menu.classList.contains('is-closing')) {
        closeCreateDropdown();
    } else {
        openCreateDropdown(toggleBtn);
    }
}
window.toggleCreateDropdown = toggleCreateDropdown;

function updateQuickAddType(type) {
    const titleInput = document.getElementById('quick-input-title');
    const modalHeading = document.getElementById('quick-add-title');
    if (!titleInput) return;

    const prefixes = [
        'PRL - ', 'STT - ', 'Surveilen 1 - ', 'Surveilen 1 + PRL - ',
        'Surveilen 2 - ', 'Surveilen 2 + PRL - ', 'Re-Akreditasi - ', 'Akreditasi Awal - ', 'Rapat ',
        'Agenda Internal', 'Penambahan Ruang Lingkup', 'Surveilen Tidak Terjadwal'
    ];
    const isPrefixedOrEmpty = !titleInput.value || prefixes.some(p => titleInput.value.startsWith(p));

    const t = (type || '').toLowerCase();
    if (t.includes('prl') && !t.includes('surveilen 1') && !t.includes('surveilen 2')) {
        titleInput.placeholder = 'Contoh: PRL - Penambahan Ruang Lingkup Laboratorium';
        if (isPrefixedOrEmpty) titleInput.value = 'Penambahan Ruang Lingkup';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Penambahan Ruang Lingkup';
    } else if (t.includes('stt') || t.includes('tidak terjadwal')) {
        titleInput.placeholder = 'Contoh: STT - Surveilen Tidak Terjadwal Lapangan';
        if (isPrefixedOrEmpty) titleInput.value = 'Surveilen Tidak Terjadwal';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Surveilen Tidak Terjadwal';
    } else if (t.includes('surveilen 1 + prl')) {
        titleInput.placeholder = 'Contoh: Surveilen 1 + PRL - Nama Laboratorium';
        if (isPrefixedOrEmpty) titleInput.value = 'Surveilen 1 + PRL - ';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Surveilen 1 + PRL';
    } else if (t.includes('surveilen 2 + prl')) {
        titleInput.placeholder = 'Contoh: Surveilen 2 + PRL - Nama Laboratorium';
        if (isPrefixedOrEmpty) titleInput.value = 'Surveilen 2 + PRL - ';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Surveilen 2 + PRL';
    } else if (t.includes('surveilen 1')) {
        titleInput.placeholder = 'Contoh: Surveilen 1 (S1) - Nama Laboratorium';
        if (isPrefixedOrEmpty) titleInput.value = 'Surveilen 1 - ';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Surveilen 1 (S1)';
    } else if (t.includes('surveilen 2')) {
        titleInput.placeholder = 'Contoh: Surveilen 2 (S2) - Nama Laboratorium';
        if (isPrefixedOrEmpty) titleInput.value = 'Surveilen 2 - ';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Surveilen 2 (S2)';
    } else if (t.includes('re-akreditasi') || t.includes('reakreditasi')) {
        titleInput.placeholder = 'Contoh: Re-Akreditasi (RA) - Nama Laboratorium';
        if (isPrefixedOrEmpty) titleInput.value = 'Re-Akreditasi - ';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Re-Akreditasi';
    } else if (t.includes('akreditasi awal')) {
        titleInput.placeholder = 'Contoh: Akreditasi Awal - Nama Laboratorium';
        if (isPrefixedOrEmpty) titleInput.value = 'Akreditasi Awal - ';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Akreditasi Awal';
    } else if (t.includes('internal') || t.includes('agenda')) {
        titleInput.placeholder = 'Contoh: Rapat Internal Koordinasi Tim';
        if (isPrefixedOrEmpty) titleInput.value = 'Agenda Internal';
        if (modalHeading) modalHeading.textContent = 'Tambah Agenda: Kegiatan Internal';
    } else {
        titleInput.placeholder = 'Contoh: Rapat Internal Koordinasi Tim';
        if (prefixes.some(p => titleInput.value.startsWith(p))) {
            titleInput.value = '';
        }
        if (modalHeading) modalHeading.textContent = 'Buat Agenda Kegiatan Baru';
    }
}
window.updateQuickAddType = updateQuickAddType;

function openQuickAddWithType(type = 'PRL') {
    closeCreateDropdown();
    const typeSelect = document.getElementById('quick-input-type');
    const titleInput = document.getElementById('quick-input-title');
    const modalHeading = document.getElementById('quick-add-title');

    let targetValue = '';
    let defaultTitle = '';
    let headingText = '';

    if (type === 'PRL') {
        targetValue = 'Perluasan Ruang Lingkup (PRL)';
        defaultTitle = 'Penambahan Ruang Lingkup';
        headingText = 'Tambah Agenda: Penambahan Ruang Lingkup';
    } else if (type === 'STT') {
        targetValue = 'Surveilen Tidak Terjadwal (STT)';
        defaultTitle = 'Surveilen Tidak Terjadwal';
        headingText = 'Tambah Agenda: Surveilen Tidak Terjadwal';
    } else if (type === 'AGENDA_INTERNAL') {
        targetValue = 'AGENDA_INTERNAL';
        defaultTitle = 'Agenda Internal';
        headingText = 'Tambah Agenda: Kegiatan Internal';
    } else {
        targetValue = type;
        defaultTitle = type;
        headingText = 'Buat Agenda Kegiatan Baru';
    }

    if (modalHeading && headingText) {
        modalHeading.textContent = headingText;
    }

    if (typeSelect) {
        let matched = false;
        for (const opt of typeSelect.options) {
            if (opt.value === targetValue) {
                typeSelect.value = opt.value;
                matched = true;
                break;
            }
        }
        if (!matched) {
            for (const opt of typeSelect.options) {
                if (opt.value.toLowerCase().includes(type.toLowerCase())) {
                    typeSelect.value = opt.value;
                    matched = true;
                    break;
                }
            }
        }
        if (!matched) {
            typeSelect.value = targetValue;
        }

        // Dispatch change event to immediately sync custom-select UI
        typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        typeSelect.dispatchEvent(new Event('input', { bubbles: true }));
    }

    if (titleInput) {
        titleInput.value = defaultTitle;
    }

    openModal('modal-quick-add-event');

    setTimeout(() => {
        if (titleInput) {
            titleInput.focus();
            titleInput.setSelectionRange(titleInput.value.length, titleInput.value.length);
        }
    }, 150);
}
window.openQuickAddWithType = openQuickAddWithType;

// Mini-calendar date cell click: highlight selected date
document.addEventListener('click', (e) => {
    const miniCell = e.target.closest('.gcal-mini-cell');
    if (miniCell) {
        document.querySelectorAll('.gcal-mini-cell.active').forEach((el) => el.classList.remove('active'));
        miniCell.classList.add('active');
    }
});

// Global click & Escape handlers for popover closing
document.addEventListener('click', (e) => {
    const createMenu = document.getElementById('gcal-create-menu');
    const createBtn = document.getElementById('gcal-btn-create-toggle');
    if (createMenu && createMenu.classList.contains('is-open') && !createMenu.classList.contains('is-closing')) {
        if (!createMenu.contains(e.target) && e.target !== createBtn && !createBtn?.contains(e.target)) {
            closeCreateDropdown();
        }
    }

    const activePopovers = Array.from(document.querySelectorAll('.gcal-popover')).filter(
        (p) => p.style.display !== 'none' && p.getAttribute('aria-hidden') !== 'true'
    );
    if (!activePopovers.length) return;

    // Mobile: clicking backdrop
    if (window.innerWidth <= 768) {
        for (const popover of activePopovers) {
            if (e.target === popover) {
                closeEventPopover(popover);
                return;
            }
        }
    }

    // Ignore clicks on event trigger elements (let showEventPopover handle opening/switching)
    if (e.target.closest('.gcal-event-chip, .gcal-timed-card, .gcal-agenda-row, [onclick*="showEventPopover"]')) {
        return;
    }

    // Check if click was inside any popover card
    const clickedInsideCard = activePopovers.some((popover) => {
        const card = popover.querySelector('.gcal-popover-card');
        return card && card.contains(e.target);
    });

    if (!clickedInsideCard) {
        closeEventPopover();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeCreateDropdown();
        closeEventPopover();
        if (typeof window.clearCalendarRangeHighlight === 'function') {
            window.clearCalendarRangeHighlight();
        }
        if (typeof window.clearRangeAnchor === 'function') {
            window.clearRangeAnchor();
        }
    }
});

// Popover dipertahankan saat scroll agar tidak menutup tiba-tiba di layar laptop/desktop.
// Pengguna dapat menutup popover melalui tombol silang [x], klik di luar popover, atau tombol Escape.

window.addEventListener('resize', () => {
    const activePopovers = document.querySelectorAll('.gcal-popover:not([style*="display: none"])');
    if (activePopovers.length) {
        closeEventPopover();
    }
});

// Real-time Live WIB Red Line Time Indicator for Week & Day grids
function initGcalLiveTimeLine() {
    function updateTimeLine() {
        const lines = document.querySelectorAll('.gcal-current-time-line');
        if (!lines.length) return;

        // Current time in WIB (UTC+7)
        const now = new Date();
        const utcMinutes = now.getUTCHours() * 60 + now.getUTCMinutes();
        const wibMinutes = (utcMinutes + 7 * 60) % (24 * 60);

        const startMinutes = 7 * 60; // 07:00 WIB
        const totalMinutes = 12 * 60; // 07:00 to 19:00 WIB (720 min)

        if (wibMinutes < startMinutes || wibMinutes > startMinutes + totalMinutes) {
            lines.forEach((l) => { l.style.display = 'none'; });
            return;
        }

        const pct = ((wibMinutes - startMinutes) / totalMinutes) * 100;
        lines.forEach((l) => {
            l.style.display = 'flex';
            l.style.top = `${pct}%`;
        });
    }

    updateTimeLine();
    if (!window.__gcalLiveTimer) {
        window.__gcalLiveTimer = setInterval(updateTimeLine, 30000);
    }
}

// Client-side Instant Filter for Calendar Categories, LPK, and PIC
function initGcalFilters() {
    const categoryCheckboxes = document.querySelectorAll('[data-filter-cat]');
    const lpkSelect = document.getElementById('gcal-filter-lpk');
    const picSelect = document.getElementById('gcal-filter-pic');

    if (!categoryCheckboxes.length && !lpkSelect && !picSelect) return;

    function applyGcalFilters() {
        const activeCategories = new Set();
        categoryCheckboxes.forEach((cb) => {
            if (cb.checked) {
                activeCategories.add(cb.dataset.filterCat);
            }
        });
        const selectedLpk = lpkSelect ? lpkSelect.value.trim() : '';
        const selectedPic = picSelect ? picSelect.value.trim() : '';

        // Filter event chips in Month view, cards in Week/Day views, and rows in Agenda view
        const eventElements = document.querySelectorAll('[data-cat]');
        eventElements.forEach((el) => {
            const cat = el.dataset.cat;
            const lpk = el.dataset.lpkId ? String(el.dataset.lpkId) : '';
            const pic = el.dataset.picId ? String(el.dataset.picId) : '';

            let visible = activeCategories.has(cat);
            if (selectedLpk && lpk !== selectedLpk) {
                visible = false;
            }
            if (selectedPic && pic !== selectedPic) {
                visible = false;
            }

            el.style.display = visible ? '' : 'none';
        });

        // Hide empty Agenda date groups if all items within are filtered out
        document.querySelectorAll('.gcal-agenda-group').forEach((group) => {
            const rows = group.querySelectorAll('.gcal-agenda-row');
            const hasVisibleRow = Array.from(rows).some((r) => r.style.display !== 'none');
            group.style.display = hasVisibleRow ? '' : 'none';
        });
    }

    categoryCheckboxes.forEach((cb) => {
        if (!cb.dataset.gcalFilterInit) {
            cb.dataset.gcalFilterInit = 'true';
            cb.addEventListener('change', applyGcalFilters);
        }
    });

    if (lpkSelect && !lpkSelect.dataset.gcalFilterInit) {
        lpkSelect.dataset.gcalFilterInit = 'true';
        lpkSelect.addEventListener('change', applyGcalFilters);
    }

    if (picSelect && !picSelect.dataset.gcalFilterInit) {
        picSelect.dataset.gcalFilterInit = 'true';
        picSelect.addEventListener('change', () => {
            applyGcalFilters();
            const url = new URL(window.location.href);
            if (picSelect.value) {
                url.searchParams.set('pic_id', picSelect.value);
            } else {
                url.searchParams.delete('pic_id');
            }
            window.history.replaceState({}, '', url.toString());
        });
    }

    applyGcalFilters();
}

// Month & Year Direct Selector Navigation
function initGcalMonthYearPicker() {
    const monthSelect = document.getElementById('gcal-select-month');
    const yearSelect = document.getElementById('gcal-select-year');
    if (!monthSelect || !yearSelect) return;

    function handleMonthYearChange() {
        const selectedMonth = monthSelect.value;
        const selectedYear = yearSelect.value;
        const baseUrl = monthSelect.dataset.calendarBaseUrl || '/calendar';
        const urlParams = new URLSearchParams(window.location.search);

        const currentView = urlParams.get('view') || 'month';
        urlParams.set('view', currentView);

        if (currentView === 'month') {
            urlParams.set('month', `${selectedYear}-${selectedMonth}`);
            urlParams.delete('date');
            urlParams.delete('selected');
        } else {
            const currentDateStr = urlParams.get('date');
            let day = 1;
            if (currentDateStr && /^\d{4}-\d{2}-\d{2}$/.test(currentDateStr)) {
                day = parseInt(currentDateStr.split('-')[2], 10) || 1;
            }
            const maxDays = new Date(parseInt(selectedYear, 10), parseInt(selectedMonth, 10), 0).getDate();
            const validDay = Math.min(day, maxDays);
            const paddedDay = String(validDay).padStart(2, '0');

            urlParams.set('date', `${selectedYear}-${selectedMonth}-${paddedDay}`);
            urlParams.delete('month');
        }

        const targetUrl = `${baseUrl}?${urlParams.toString()}`;
        if (typeof window.navigateTo === 'function') {
            window.navigateTo(targetUrl);
        } else {
            window.location.href = targetUrl;
        }
    }

    if (!monthSelect.dataset.gcalPickerInit) {
        monthSelect.dataset.gcalPickerInit = 'true';
        monthSelect.addEventListener('change', handleMonthYearChange);
    }
    if (!yearSelect.dataset.gcalPickerInit) {
        yearSelect.dataset.gcalPickerInit = 'true';
        yearSelect.addEventListener('change', handleMonthYearChange);
    }
}

function initCalendarHighlight() {
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (!highlightId) return;

    const rawId = highlightId.replace(/^assessment-/, '');
    const selector = `[data-event-id="${highlightId}"], [data-event-id="${rawId}"], [data-event-id="assessment-${rawId}"]`;
    const targetEl = document.querySelector(selector);

    if (targetEl) {
        targetEl.classList.add('is-highlight-target');

        setTimeout(() => {
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }, 120);

        setTimeout(() => {
            const activePopovers = Array.from(document.querySelectorAll('.gcal-popover')).filter(
                (p) => p.style.display !== 'none' && p.getAttribute('aria-hidden') !== 'true'
            );
            if (!activePopovers.length && typeof targetEl.click === 'function') {
                targetEl.click();
            }
        }, 360);
    }
}

const RANGE_ANCHOR_KEY = 'simasadi_calendar_range_anchor';
const RANGE_PENDING_KEY = 'simasadi_calendar_range_pending';

function getRangeAnchor() {
    try {
        return sessionStorage.getItem(RANGE_ANCHOR_KEY) || window._calendarRangeAnchor || null;
    } catch {
        return window._calendarRangeAnchor || null;
    }
}

function isRangePendingStart() {
    try {
        return sessionStorage.getItem(RANGE_PENDING_KEY) === '1' || Boolean(window._calendarRangePendingStart);
    } catch {
        return Boolean(window._calendarRangePendingStart);
    }
}

function setRangePendingStart(val) {
    window._calendarRangePendingStart = Boolean(val);
    try {
        if (val) {
            sessionStorage.setItem(RANGE_PENDING_KEY, '1');
        } else {
            sessionStorage.removeItem(RANGE_PENDING_KEY);
        }
    } catch (_) {}
    renderRangeFloatingPill();
    updateAnchorCellHighlight();
}

function setRangeAnchor(dateStr) {
    if (!dateStr) {
        clearRangeAnchor();
        return;
    }
    window._calendarRangePendingStart = false;
    try {
        sessionStorage.removeItem(RANGE_PENDING_KEY);
    } catch (_) {}

    window._calendarRangeAnchor = dateStr;
    window._lastClickedCalendarDate = dateStr;
    try {
        sessionStorage.setItem(RANGE_ANCHOR_KEY, dateStr);
    } catch (_) {}
    renderRangeFloatingPill();
    updateAnchorCellHighlight();
}

function clearCalendarRangeHighlight() {
    document.querySelectorAll('.gcal-cell-range-selected, .gcal-cell-range-start, .gcal-cell-range-end, .gcal-cell-anchor-start').forEach((el) => {
        el.classList.remove('gcal-cell-range-selected', 'gcal-cell-range-start', 'gcal-cell-range-end', 'gcal-cell-anchor-start');
    });
    document.body.classList.remove('is-selecting-calendar-range');
}
window.clearCalendarRangeHighlight = clearCalendarRangeHighlight;

function clearRangeAnchor(keepHighlight = false) {
    window._calendarRangeAnchor = null;
    window._calendarRangePendingStart = false;
    try {
        sessionStorage.removeItem(RANGE_ANCHOR_KEY);
        sessionStorage.removeItem(RANGE_PENDING_KEY);
    } catch (_) {}
    renderRangeFloatingPill();
    updateAnchorCellHighlight();
    if (!keepHighlight) {
        clearCalendarRangeHighlight();
    }
}
window.getRangeAnchor = getRangeAnchor;
window.setRangeAnchor = setRangeAnchor;
window.clearRangeAnchor = clearRangeAnchor;
window.clearCalendarRangeHighlight = clearCalendarRangeHighlight;
window.isRangePendingStart = isRangePendingStart;
window.setRangePendingStart = setRangePendingStart;

function toggleRangeSelectionMode() {
    const currentAnchor = getRangeAnchor();
    const isPending = isRangePendingStart();
    if (currentAnchor || isPending) {
        clearRangeAnchor();
    } else {
        setRangePendingStart(true);
    }
}
window.toggleRangeSelectionMode = toggleRangeSelectionMode;

function updateAnchorCellHighlight() {
    const anchor = getRangeAnchor();
    const isPending = isRangePendingStart();
    document.querySelectorAll('.gcal-month-cell').forEach((cell) => {
        const isAnchor = Boolean(anchor && cell.dataset.date === anchor);
        cell.classList.toggle('gcal-cell-anchor-start', isAnchor);
    });

    const rangeBtn = document.getElementById('gcal-btn-range-mode');
    if (rangeBtn) {
        rangeBtn.classList.toggle('is-active', Boolean(anchor || isPending));
    }
}

function renderRangeFloatingPill() {
    const anchor = getRangeAnchor();
    const isPending = isRangePendingStart();
    let pill = document.getElementById('gcal-range-floating-pill');

    if (!anchor && !isPending) {
        if (pill) pill.remove();
        return;
    }

    if (!pill) {
        pill = document.createElement('div');
        pill.id = 'gcal-range-floating-pill';
        pill.className = 'gcal-range-floating-pill';
        const targetContainer = document.querySelector('.gcal-main') || document.querySelector('.page-wrap');
        const toolbar = document.querySelector('.gcal-toolbar');
        if (toolbar && toolbar.nextSibling) {
            toolbar.parentNode.insertBefore(pill, toolbar.nextSibling);
        } else if (targetContainer) {
            targetContainer.prepend(pill);
        }
    }

    if (anchor) {
        const startD = new Date(anchor + 'T00:00:00');
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        const formatted = startD.toLocaleDateString('id-ID', options);

        pill.innerHTML = `
            <div class="gcal-range-pill-content">
                <span class="gcal-range-pill-badge">Mode Rentang</span>
                <span>Tanggal Mulai: <strong>${formatted}</strong> &bull; Klik tanggal selesai di bulan/tahun mana saja (atau Shift+Klik)</span>
            </div>
            <div class="gcal-range-pill-actions">
                <button type="button" class="gcal-range-pill-btn-close" onclick="window.clearRangeAnchor()" title="Batalkan rentang">&times;</button>
            </div>
        `;
    } else {
        pill.innerHTML = `
            <div class="gcal-range-pill-content">
                <span class="gcal-range-pill-badge">Mode Rentang</span>
                <span>Silakan klik <strong>tanggal awal</strong> di kalender (atau Shift+Klik langsung pada tanggal).</span>
            </div>
            <div class="gcal-range-pill-actions">
                <button type="button" class="gcal-range-pill-btn-close" onclick="window.clearRangeAnchor()" title="Batalkan rentang">&times;</button>
            </div>
        `;
    }
}

function initCalendarRangeSelection() {
    const monthGrid = document.querySelector('.gcal-month-grid');
    if (!monthGrid) return;

    renderRangeFloatingPill();
    updateAnchorCellHighlight();

    let isMouseDown = false;
    let isDragging = false;
    let justDragged = false;
    let dragStartDate = null;
    let currentHoverDate = null;
    let startX = 0;
    let startY = 0;

    const updateHighlight = (d1, d2) => {
        if (!d1 || !d2) return;
        const [minD, maxD] = [d1, d2].sort();
        const cells = monthGrid.querySelectorAll('.gcal-month-cell');
        cells.forEach((cell) => {
            const d = cell.dataset.date;
            if (!d) return;
            if (d >= minD && d <= maxD) {
                cell.classList.add('gcal-cell-range-selected');
                cell.classList.toggle('gcal-cell-range-start', d === minD);
                cell.classList.toggle('gcal-cell-range-end', d === maxD);
            } else {
                cell.classList.remove('gcal-cell-range-selected', 'gcal-cell-range-start', 'gcal-cell-range-end');
            }
        });
    };

    const clearDragHighlight = () => {
        monthGrid.querySelectorAll('.gcal-cell-range-selected, .gcal-cell-range-start, .gcal-cell-range-end').forEach((el) => {
            el.classList.remove('gcal-cell-range-selected', 'gcal-cell-range-start', 'gcal-cell-range-end');
        });
        document.body.classList.remove('is-selecting-calendar-range');
        updateAnchorCellHighlight();
    };

    // Live hover preview when anchor is active
    if (monthGrid._gridOverHandler) {
        monthGrid.removeEventListener('mouseover', monthGrid._gridOverHandler);
    }
    if (monthGrid._gridLeaveHandler) {
        monthGrid.removeEventListener('mouseleave', monthGrid._gridLeaveHandler);
    }

    const handleGridMouseOver = (e) => {
        const anchor = getRangeAnchor();
        if (!anchor || isMouseDown) return;
        const cell = e.target.closest('.gcal-month-cell');
        if (!cell || !cell.dataset.date) return;
        updateHighlight(anchor, cell.dataset.date);
    };

    const handleGridMouseLeave = () => {
        const anchor = getRangeAnchor();
        if (!anchor || isMouseDown) return;
        clearDragHighlight();
    };

    monthGrid._gridOverHandler = handleGridMouseOver;
    monthGrid._gridLeaveHandler = handleGridMouseLeave;
    monthGrid.addEventListener('mouseover', handleGridMouseOver);
    monthGrid.addEventListener('mouseleave', handleGridMouseLeave);

    // Capture-phase click listener: handles Shift+Click, Range Mode, and normal cell clicks
    if (monthGrid._rangeCaptureClickHandler) {
        monthGrid.removeEventListener('click', monthGrid._rangeCaptureClickHandler, true);
    }

    const handleCaptureClick = (e) => {
        if (justDragged) {
            justDragged = false;
            return;
        }

        // Never intercept clicks on event chips (let showEventPopover handle it)
        if (e.target.closest('.gcal-event-chip')) return;

        const cell = e.target.closest('.gcal-month-cell');
        if (!cell || !cell.dataset.date) return;

        const clickedDate = cell.dataset.date;
        const activeAnchor = getRangeAnchor();
        const isPending = isRangePendingStart();
        const isShift = Boolean(e.shiftKey);

        const isBadge = Boolean(e.target.closest('.gcal-day-badge'));
        const isAddBtn = Boolean(e.target.closest('.gcal-cell-add-btn'));

        // If in range mode, or anchor already set, or user is holding Shift:
        if (activeAnchor || isPending || isShift) {
            e.preventDefault();
            e.stopPropagation();

            // Scenario A: Start anchor is already set -> This click completes the range (End Date)!
            // Only now does the modal appear!
            if (activeAnchor) {
                if (activeAnchor !== clickedDate) {
                    const [d1, d2] = [activeAnchor, clickedDate].sort();
                    updateHighlight(d1, d2);
                    clearRangeAnchor(true);
                    quickAddAtRange(d1, d2);
                } else {
                    clearRangeAnchor(false);
                    quickAddAt(clickedDate, '09:00');
                }
                return;
            }

            // Scenario B: No anchor yet -> This click sets the START date!
            // Modal does NOT appear yet until the end date is clicked!
            setRangePendingStart(false);
            setRangeAnchor(clickedDate);
            return;
        }

        // Normal click behavior (no Shift, no range mode):
        if (isBadge) {
            // Let the day badge anchor link navigate to day view
            return;
        }

        if (isAddBtn) {
            // The plus button already has inline onclick="window.quickAddAt(...)"
            return;
        }

        // Clicked on empty cell space
        window._lastClickedCalendarDate = clickedDate;
        quickAddAt(clickedDate, '09:00');
    };

    monthGrid._rangeCaptureClickHandler = handleCaptureClick;
    monthGrid.addEventListener('click', handleCaptureClick, true);

    // Mousedown listener for in-month drag-to-select
    monthGrid.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;

        if (e.target.closest('button, a, input, select, .gcal-event-chip, .gcal-more-chip, .gcal-day-badge, .gcal-cell-add-btn')) {
            return;
        }

        const cell = e.target.closest('.gcal-month-cell');
        if (!cell || !cell.dataset.date) return;

        if (getRangeAnchor() || isRangePendingStart()) return;

        isMouseDown = true;
        isDragging = false;
        justDragged = false;
        dragStartDate = cell.dataset.date;
        currentHoverDate = cell.dataset.date;
        startX = e.clientX;
        startY = e.clientY;
        window._lastClickedCalendarDate = dragStartDate;
    });

    const handleMouseMove = (e) => {
        if (!isMouseDown) return;

        const dist = Math.hypot(e.clientX - startX, e.clientY - startY);
        if (dist > 8 && !isDragging) {
            isDragging = true;
            document.body.classList.add('is-selecting-calendar-range');
            updateHighlight(dragStartDate, dragStartDate);
        }

        if (!isDragging) return;

        const elemUnder = document.elementFromPoint(e.clientX, e.clientY);
        const cell = elemUnder ? elemUnder.closest('.gcal-month-cell') : null;
        if (cell && cell.dataset.date && cell.dataset.date !== currentHoverDate) {
            currentHoverDate = cell.dataset.date;
            updateHighlight(dragStartDate, currentHoverDate);
        }
    };

    const handleMouseUp = (e) => {
        if (!isMouseDown) return;

        const wasDragging = isDragging;
        const sDate = dragStartDate;
        const hDate = currentHoverDate;

        isMouseDown = false;
        isDragging = false;
        dragStartDate = null;
        currentHoverDate = null;

        if (wasDragging && sDate && hDate && sDate !== hDate) {
            e.preventDefault();
            justDragged = true;
            const [d1, d2] = [sDate, hDate].sort();
            updateHighlight(d1, d2);
            quickAddAtRange(d1, d2);
        } else {
            clearDragHighlight();
        }
    };

    if (monthGrid._docRangeMoveHandler) {
        document.removeEventListener('mousemove', monthGrid._docRangeMoveHandler);
    }
    if (monthGrid._docRangeUpHandler) {
        document.removeEventListener('mouseup', monthGrid._docRangeUpHandler);
    }

    monthGrid._docRangeMoveHandler = handleMouseMove;
    monthGrid._docRangeUpHandler = handleMouseUp;

    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);

    // Automatically clear range highlights whenever modal-quick-add-event is closed or hidden
    const quickAddModal = document.getElementById('modal-quick-add-event');
    if (quickAddModal && !quickAddModal._rangeObserverAttached) {
        quickAddModal._rangeObserverAttached = true;

        const observer = new MutationObserver(() => {
            if (!quickAddModal.classList.contains('is-active') && quickAddModal.style.display !== 'flex') {
                clearCalendarRangeHighlight();
            }
        });
        observer.observe(quickAddModal, { attributes: true, attributeFilter: ['class', 'style'] });

        quickAddModal.querySelectorAll('[data-modal-close], .simasadi-modal-close, button[type="button"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (btn.textContent.trim().toLowerCase().includes('batal') || btn.classList.contains('simasadi-modal-close') || btn.hasAttribute('data-modal-close')) {
                    clearCalendarRangeHighlight();
                }
            });
        });
    }

    if (!window._rangeModalCloseListenerAttached) {
        window._rangeModalCloseListenerAttached = true;
        window.addEventListener('modal:closed', (e) => {
            if (!e.detail || !e.detail.modalId || e.detail.modalId === 'modal-quick-add-event') {
                clearCalendarRangeHighlight();
            }
        });
    }
}

function initGcalComponents() {
    initGcalLiveTimeLine();
    initGcalFilters();
    initGcalMonthYearPicker();
    initCalendarHighlight();
    initCalendarRangeSelection();
    initQuickAddDateSync();
    if (typeof window.scheduleAdjacentCalendarPrefetch === 'function') {
        window.scheduleAdjacentCalendarPrefetch();
    }
}

export {
    showEventPopover,
    returnPopoverToPlaceholder,
    closeEventPopover,
    quickAddAt,
    quickAddAtRange,
    getRangeAnchor,
    setRangeAnchor,
    clearRangeAnchor,
    clearCalendarRangeHighlight,
    toggleRangeSelectionMode,
    toggleCreateDropdown,
    openCreateDropdown,
    closeCreateDropdown,
    openQuickAddWithType,
    updateQuickAddType,
    initGcalLiveTimeLine,
    initGcalFilters,
    initGcalMonthYearPicker,
    initCalendarHighlight,
    initCalendarRangeSelection,
    initGcalComponents
};
