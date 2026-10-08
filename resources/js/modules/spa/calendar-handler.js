import {
    getCalendarCacheKey,
    calendarCache,
    CALENDAR_CACHE_TTL,
    calendarPrefetchInFlight,
    scheduleAdjacentCalendarPrefetch
} from './router-cache.js';
import { initGcalComponents } from '../calendar.js';
import { initCustomSelects } from '../custom-select.js';
import { initCustomPickers } from '../custom-picker.js';

/**
 * In-Calendar Seamless Zero-Delay Month & View Transition Handler
 */
export const handleCalendarTransition = async ({ url, targetUrlObj, pageWrap, pushState = true }) => {
    // Strictly preserve the user's current scroll position so switching months never jumps
    const currentScrollY = window.scrollY || document.documentElement.scrollTop || 0;

    // Blur active element so browser does not attempt automatic focus rescue scrolling
    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }

    // Preserve active category checkbox filters, selected LPK, and selected PIC
    const activeCatStates = {};
    document.querySelectorAll('[data-filter-cat]').forEach((cb) => {
        activeCatStates[cb.dataset.filterCat] = cb.checked;
    });
    const selectedLpk = document.getElementById('gcal-filter-lpk')?.value;
    const selectedPic = document.getElementById('gcal-filter-pic')?.value;

    let targetUrl = url;
    if (selectedPic && !targetUrlObj.searchParams.has('pic_id')) {
        targetUrlObj.searchParams.set('pic_id', selectedPic);
        targetUrl = targetUrlObj.toString();
    }

    const cacheKey = getCalendarCacheKey(targetUrl);
    let htmlText = null;
    const cached = calendarCache.get(cacheKey);
    const isCacheHit = Boolean(cached && (Date.now() - cached.timestamp < CALENDAR_CACHE_TTL));

    if (isCacheHit) {
        // INSTANT 0ms DOM SWAP - NO NETWORK DELAY, NO OPACITY DIMMING
        htmlText = cached.htmlText;
    } else {
        // Cache miss: tampilkan indikator loading hanya saat menunggu jaringan
        const curShell = document.querySelector('.gcal-shell');
        if (curShell) {
            curShell.classList.add('gcal-is-updating');
        }

        if (calendarPrefetchInFlight.has(cacheKey)) {
            htmlText = await calendarPrefetchInFlight.get(cacheKey);
        }

        if (!htmlText) {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Partial-Content': 'calendar'
                }
            });

            if (!response.ok || response.redirected) {
                window.location.href = response.url || targetUrl;
                return;
            }

            htmlText = await response.text();
        }

        // Simpan ke cache untuk navigasi kembali yang instan
        calendarCache.set(cacheKey, {
            htmlText,
            timestamp: Date.now()
        });
    }

    const activeShell = document.querySelector('.gcal-shell');

    let newShell = null;
    let activeDateVal = null;
    let pageTitleVal = null;

    // Fast parse: Check if partial view was returned directly
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = htmlText.trim();
    newShell = tempDiv.querySelector('.gcal-shell') ||
        (tempDiv.firstElementChild?.classList?.contains('gcal-shell') ? tempDiv.firstElementChild : null);

    if (newShell) {
        activeDateVal = newShell.dataset.activeDate;
        if (newShell.dataset.monthLabel) {
            pageTitleVal = `Kalender Kegiatan (${newShell.dataset.monthLabel}) | SIMASADI`;
        }
    } else {
        // Fallback in case a full page was returned
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');
        newShell = doc.querySelector('.gcal-shell');
        if (doc.title) pageTitleVal = doc.title;
        const newStartDate = doc.querySelector('#quick-input-start-date');
        if (newStartDate) activeDateVal = newStartDate.value;
    }

    if (newShell && activeShell) {
        newShell.classList.remove('gcal-is-updating');
        activeShell.replaceWith(newShell);
    } else {
        const newContent = tempDiv.querySelector('#page-content-wrapper') || tempDiv.querySelector('.page-wrap');
        if (newContent) {
            pageWrap.innerHTML = newContent.innerHTML;
            pageWrap.classList.remove('page-enter-active');
            void pageWrap.offsetWidth;
            pageWrap.classList.add('page-enter-active');
        }
    }

    // Sync date in quick-add modal
    if (activeDateVal) {
        const curStartDate = document.getElementById('quick-input-start-date');
        if (curStartDate) curStartDate.value = activeDateVal;
        const curEndDate = document.getElementById('quick-input-end-date');
        if (curEndDate) curEndDate.value = activeDateVal;
    }

    // Update document title
    if (pageTitleVal) {
        document.title = pageTitleVal;
    }

    // Restore active filter checkbox states and LPK selection
    document.querySelectorAll('[data-filter-cat]').forEach((cb) => {
        if (cb.dataset.filterCat in activeCatStates) {
            cb.checked = activeCatStates[cb.dataset.filterCat];
        }
    });
    const lpkSelect = document.getElementById('gcal-filter-lpk');
    if (lpkSelect && selectedLpk !== undefined) {
        lpkSelect.value = selectedLpk;
    }
    const picSelect = document.getElementById('gcal-filter-pic');
    if (picSelect && selectedPic !== undefined) {
        picSelect.value = selectedPic;
    }

    if (pushState) {
        window.history.pushState({ url: targetUrl }, '', targetUrl);
    }

    // Re-initialize calendar components and custom dropdowns
    if (typeof initGcalComponents === 'function') initGcalComponents();
    if (typeof initCustomSelects === 'function') initCustomSelects(document);
    if (typeof initCustomPickers === 'function') initCustomPickers(document);

    // Firmly lock and restore the exact scroll position down to the pixel
    window.scrollTo({ top: currentScrollY, behavior: 'instant' });
    requestAnimationFrame(() => {
        window.scrollTo({ top: currentScrollY, behavior: 'instant' });
    });

    // Rantai prefetch otomatis: Unduh bulan berikutnya & sebelumnya di latar belakang
    scheduleAdjacentCalendarPrefetch();

    window.dispatchEvent(new CustomEvent('simasadi:page-loaded'));
};
