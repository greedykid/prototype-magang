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

        return `${dateStr} • ${startTimeStr} - ${endTimeStr} WIB`;
    } catch {
        const pad = (num) => String(num).padStart(2, '0');
        return `${pad(start.getDate())}/${pad(start.getMonth() + 1)}/${start.getFullYear()} • ${pad(start.getHours())}:${pad(start.getMinutes())} - ${pad(end.getHours())}:${pad(end.getMinutes())} WIB`;
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
    const titleEl = popover.querySelector('#popover-title');
    if (titleEl) titleEl.textContent = eventData.title;

    const timeEl = popover.querySelector('#popover-time');
    if (timeEl) timeEl.textContent = formatIndonesianDateTimeRange(startDate, endDate);

    // 3. LPK Name
    const lpkEl = popover.querySelector('#popover-lpk');
    if (lpkEl) lpkEl.textContent = eventData.lpk_name || 'Lembaga Penilaian Kesesuaian';

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
        if (eventData.edit_url) {
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

function quickAddAt(dateStr, timeStr = '09:00') {
    const startDateInput = document.getElementById('quick-input-start-date');
    const endDateInput = document.getElementById('quick-input-end-date');
    const startTimeInput = document.getElementById('quick-input-start-time');
    const endTimeInput = document.getElementById('quick-input-end-time');

    if (startDateInput) startDateInput.value = dateStr;
    if (endDateInput) endDateInput.value = dateStr;

    if (timeStr && startTimeInput) {
        startTimeInput.value = timeStr;
        if (endTimeInput) {
            const [h, m] = timeStr.split(':').map(Number);
            const endH = Math.min(23, (h || 9) + 2);
            endTimeInput.value = `${String(endH).padStart(2, '0')}:${String(m || 0).padStart(2, '0')}`;
        }
    }

    openModal('modal-quick-add-event');
}
window.quickAddAt = quickAddAt;

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
        closeEventPopover();
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

// Client-side Instant Filter for Calendar Categories & LPK
function initGcalFilters() {
    const categoryCheckboxes = document.querySelectorAll('[data-filter-cat]');
    const lpkSelect = document.getElementById('gcal-filter-lpk');

    if (!categoryCheckboxes.length && !lpkSelect) return;

    function applyGcalFilters() {
        const activeCategories = new Set();
        categoryCheckboxes.forEach((cb) => {
            if (cb.checked) {
                activeCategories.add(cb.dataset.filterCat);
            }
        });
        const selectedLpk = lpkSelect ? lpkSelect.value.trim() : '';

        // Filter event chips in Month view, cards in Week/Day views, and rows in Agenda view
        const eventElements = document.querySelectorAll('[data-cat]');
        eventElements.forEach((el) => {
            const cat = el.dataset.cat;
            const lpk = el.dataset.lpkId ? String(el.dataset.lpkId) : '';

            let visible = activeCategories.has(cat);
            if (selectedLpk && lpk !== selectedLpk) {
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

function initGcalComponents() {
    initGcalLiveTimeLine();
    initGcalFilters();
    initGcalMonthYearPicker();
}

export { showEventPopover, returnPopoverToPlaceholder, closeEventPopover, quickAddAt, initGcalLiveTimeLine, initGcalFilters, initGcalMonthYearPicker, initGcalComponents };
