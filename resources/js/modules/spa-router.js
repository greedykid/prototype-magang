import { setDrawerState } from './sidebar.js';
import { returnModalToPlaceholder, closeModal } from './modals.js';
import { returnPopoverToPlaceholder, closeEventPopover, initGcalComponents } from './calendar.js';
import { initCustomSelects } from './custom-select.js';

// ==========================================================================
// Custom Skeleton Loading Layouts per Menu
// ==========================================================================
const getSkeletonDashboardHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 120px; height: 13px;"></span>
            <span class="skeleton-box" style="width: 320px; height: 38px; margin-top: 4px;"></span>
            <span class="skeleton-box" style="width: 440px; height: 16px; margin-top: 4px;"></span>
        </div>
        <span class="skeleton-box" style="width: 175px; height: 44px; border-radius: 8px;"></span>
    </div>

    <div class="metric-grid">
        ${[1, 2, 3, 4].map(() => `
            <div class="skeleton-metric-card">
                <span class="skeleton-box" style="width: 110px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 60px; height: 42px; margin: 10px 0 6px;"></span>
                <span class="skeleton-box" style="width: 140px; height: 12px;"></span>
            </div>
        `).join('')}
    </div>

    <div class="metric-grid secondary-metrics" style="margin-top: 14px; margin-bottom: 28px;">
        ${[1, 2, 3, 4].map(() => `
            <div class="skeleton-metric-card" style="min-height: 105px;">
                <span class="skeleton-box" style="width: 115px; height: 13px;"></span>
                <span class="skeleton-box" style="width: 45px; height: 30px; margin: 8px 0 4px;"></span>
                <span class="skeleton-box" style="width: 85px; height: 11px;"></span>
            </div>
        `).join('')}
    </div>

    <div class="panel" style="margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <span class="skeleton-box" style="width: 125px; height: 12px; display: block; margin-bottom: 6px;"></span>
                <span class="skeleton-box" style="width: 230px; height: 24px; display: block;"></span>
            </div>
            <span class="skeleton-box" style="width: 85px; height: 14px;"></span>
        </div>
        <div style="display: grid; gap: 18px;">
            ${[90, 65, 40].map((width) => `
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span class="skeleton-box" style="width: 130px; height: 13px;"></span>
                        <span class="skeleton-box" style="width: 25px; height: 13px;"></span>
                    </div>
                    <span class="skeleton-box" style="width: 100%; height: 10px; border-radius: 999px;"></span>
                </div>
            `).join('')}
        </div>
    </div>

    <div class="content-grid">
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <div>
                    <span class="skeleton-box" style="width: 110px; height: 11px; display: block; margin-bottom: 5px;"></span>
                    <span class="skeleton-box" style="width: 170px; height: 20px; display: block;"></span>
                </div>
                <span class="skeleton-box" style="width: 70px; height: 14px;"></span>
            </div>
            <div style="display: grid;">
                ${[1, 2, 3].map(() => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-top: 1px solid var(--line);">
                        <div>
                            <span class="skeleton-box" style="width: 190px; height: 16px; display: block; margin-bottom: 6px;"></span>
                            <span class="skeleton-box" style="width: 120px; height: 12px; display: block;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 80px; height: 24px; border-radius: 999px;"></span>
                    </div>
                `).join('')}
            </div>
        </div>
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <div>
                    <span class="skeleton-box" style="width: 110px; height: 11px; display: block; margin-bottom: 5px;"></span>
                    <span class="skeleton-box" style="width: 140px; height: 20px; display: block;"></span>
                </div>
            </div>
            <div style="display: grid;">
                ${[1, 2].map(() => `
                    <div style="padding: 14px 0; border-top: 1px solid var(--line); display: grid; gap: 6px;">
                        <span class="skeleton-box" style="width: 180px; height: 15px;"></span>
                        <span class="skeleton-box" style="width: 130px; height: 12px;"></span>
                        <span class="skeleton-box" style="width: 100%; height: 36px; margin-top: 4px;"></span>
                    </div>
                `).join('')}
            </div>
        </div>
    </div>
</div>
`;

const getSkeletonTableHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 100px; height: 12px;"></span>
            <span class="skeleton-box" style="width: 230px; height: 36px; margin-top: 4px;"></span>
            <span class="skeleton-box" style="width: 380px; height: 15px; margin-top: 4px;"></span>
        </div>
        <span class="skeleton-box" style="width: 140px; height: 44px; border-radius: 8px;"></span>
    </div>

    <div class="panel">
        <div class="skeleton-filter-bar">
            <div class="skeleton-filter-inputs">
                <div>
                    <span class="skeleton-box" style="width: 60px; height: 12px; display: block; margin-bottom: 7px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 50px; height: 12px; display: block; margin-bottom: 7px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 65px; height: 12px; display: block; margin-bottom: 7px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 55px; height: 12px; display: block; margin-bottom: 7px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 14px;">
                <span class="skeleton-box" style="width: 120px; height: 44px; border-radius: 8px;"></span>
                <span class="skeleton-box" style="width: 75px; height: 44px; border-radius: 8px;"></span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <div class="skeleton-table-head">
                <span class="skeleton-box" style="width: 130px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 80px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 90px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 90px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 50px; height: 14px;"></span>
            </div>
            ${[1, 2, 3, 4, 5, 6].map(() => `
                <div class="skeleton-table-row">
                    <div>
                        <span class="skeleton-box" style="width: 170px; height: 16px; display: block; margin-bottom: 4px;"></span>
                        <span class="skeleton-box" style="width: 110px; height: 12px; display: block;"></span>
                    </div>
                    <span class="skeleton-box" style="width: 75px; height: 24px; border-radius: 999px;"></span>
                    <span class="skeleton-box" style="width: 85px; height: 14px;"></span>
                    <span class="skeleton-box" style="width: 80px; height: 14px;"></span>
                    <span class="skeleton-box" style="width: 55px; height: 14px;"></span>
                </div>
            `).join('')}
        </div>

        <div class="pagination-container" style="border-top: 1px solid var(--line); margin-top: 22px; padding-top: 18px; display: flex; justify-content: space-between; align-items: center;">
            <span class="skeleton-box" style="width: 220px; height: 15px;"></span>
            <div style="display: flex; gap: 6px;">
                <span class="skeleton-box" style="width: 38px; height: 38px; border-radius: 8px;"></span>
                <span class="skeleton-box" style="width: 38px; height: 38px; border-radius: 8px;"></span>
                <span class="skeleton-box" style="width: 38px; height: 38px; border-radius: 8px;"></span>
                <span class="skeleton-box" style="width: 38px; height: 38px; border-radius: 8px;"></span>
            </div>
        </div>
    </div>
</div>
`;

const getSkeletonCalendarHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 110px; height: 12px;"></span>
            <span class="skeleton-box" style="width: 240px; height: 36px; margin-top: 4px;"></span>
            <span class="skeleton-box" style="width: 390px; height: 15px; margin-top: 4px;"></span>
        </div>
        <span class="skeleton-box" style="width: 140px; height: 44px; border-radius: 8px;"></span>
    </div>

    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 22px; flex-wrap: wrap; gap: 14px;">
            <div>
                <span class="skeleton-box" style="width: 190px; height: 28px; display: block; margin-bottom: 6px;"></span>
                <span class="skeleton-box" style="width: 210px; height: 13px; display: block;"></span>
            </div>
            <div style="display: flex; gap: 8px;">
                <span class="skeleton-box" style="width: 90px; height: 42px; border-radius: 8px;"></span>
                <span class="skeleton-box" style="width: 75px; height: 42px; border-radius: 8px;"></span>
                <span class="skeleton-box" style="width: 90px; height: 42px; border-radius: 8px;"></span>
            </div>
        </div>

        <div class="skeleton-calendar-headings">
            ${['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].map(() => `
                <span class="skeleton-box" style="width: 45px; height: 12px; margin: auto;"></span>
            `).join('')}
        </div>

        <div class="skeleton-calendar-grid">
            ${Array.from({ length: 28 }).map((_, i) => `
                <div class="skeleton-calendar-cell">
                    <span class="skeleton-box" style="width: 24px; height: 24px; border-radius: 6px;"></span>
                    ${i % 4 === 1 ? '<span class="skeleton-box" style="width: 100%; height: 18px; border-radius: 4px;"></span>' : ''}
                    ${i % 7 === 3 ? '<span class="skeleton-box" style="width: 85%; height: 18px; border-radius: 4px;"></span>' : ''}
                </div>
            `).join('')}
        </div>
    </div>
</div>
`;

const getSkeletonDetailHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 120px; height: 14px; margin-bottom: 6px;"></span>
            <span class="skeleton-box" style="width: 270px; height: 36px;"></span>
            <span class="skeleton-box" style="width: 190px; height: 14px; margin-top: 4px;"></span>
        </div>
        <span class="skeleton-box" style="width: 110px; height: 44px; border-radius: 8px;"></span>
    </div>

    <div class="detail-grid">
        <div class="panel">
            <span class="skeleton-box" style="width: 130px; height: 12px; display: block; margin-bottom: 20px;"></span>
            <div class="skeleton-detail-item">
                <span class="skeleton-box" style="width: 70px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 85px; height: 24px; border-radius: 999px;"></span>
            </div>
            <div class="skeleton-detail-item">
                <span class="skeleton-box" style="width: 65px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 160px; height: 14px;"></span>
            </div>
            <div class="skeleton-detail-item">
                <span class="skeleton-box" style="width: 80px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 130px; height: 14px;"></span>
            </div>
            <div class="skeleton-detail-item">
                <span class="skeleton-box" style="width: 75px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 240px; height: 14px;"></span>
            </div>
            <div class="skeleton-detail-item">
                <span class="skeleton-box" style="width: 80px; height: 14px;"></span>
                <span class="skeleton-box" style="width: 190px; height: 38px;"></span>
            </div>
        </div>

        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <span class="skeleton-box" style="width: 90px; height: 12px; display: block; margin-bottom: 6px;"></span>
                    <span class="skeleton-box" style="width: 150px; height: 22px; display: block;"></span>
                </div>
                <span class="skeleton-box" style="width: 85px; height: 14px;"></span>
            </div>
            <div style="display: flex; flex-direction: column;">
                ${[1, 2, 3].map(() => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-top: 1px solid var(--line);">
                        <div>
                            <span class="skeleton-box" style="width: 170px; height: 15px; display: block; margin-bottom: 4px;"></span>
                            <span class="skeleton-box" style="width: 110px; height: 12px; display: block;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 75px; height: 24px; border-radius: 999px;"></span>
                    </div>
                `).join('')}
            </div>
        </div>
    </div>
</div>
`;

const getSkeletonFormHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 130px; height: 14px; margin-bottom: 6px;"></span>
            <span class="skeleton-box" style="width: 240px; height: 36px;"></span>
            <span class="skeleton-box" style="width: 320px; height: 15px; margin-top: 4px;"></span>
        </div>
    </div>

    <div class="panel form-grid">
        <div class="skeleton-form-field">
            <span class="skeleton-box" style="width: 110px; height: 14px;"></span>
            <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
        </div>
        <div class="skeleton-form-field">
            <span class="skeleton-box" style="width: 90px; height: 14px;"></span>
            <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
        </div>
        <div class="skeleton-form-field">
            <span class="skeleton-box" style="width: 75px; height: 14px;"></span>
            <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
        </div>
        <div class="skeleton-form-field">
            <span class="skeleton-box" style="width: 85px; height: 14px;"></span>
            <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
        </div>
        <div class="skeleton-form-field">
            <span class="skeleton-box" style="width: 65px; height: 14px;"></span>
            <span class="skeleton-box" style="width: 100%; height: 44px; border-radius: 8px;"></span>
        </div>
        <div class="skeleton-form-field full">
            <span class="skeleton-box" style="width: 75px; height: 14px;"></span>
            <span class="skeleton-box" style="width: 100%; height: 85px; border-radius: 8px;"></span>
        </div>
        <div class="skeleton-form-field full">
            <span class="skeleton-box" style="width: 85px; height: 14px;"></span>
            <span class="skeleton-box" style="width: 100%; height: 95px; border-radius: 8px;"></span>
        </div>
        <div class="form-actions full" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
            <span class="skeleton-box" style="width: 80px; height: 44px; border-radius: 8px;"></span>
            <span class="skeleton-box" style="width: 140px; height: 44px; border-radius: 8px;"></span>
        </div>
    </div>
</div>
`;

const getSkeletonServicesHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 130px; height: 12px;"></span>
            <span class="skeleton-box" style="width: 230px; height: 36px; margin-top: 4px;"></span>
            <span class="skeleton-box" style="width: 400px; height: 15px; margin-top: 4px;"></span>
        </div>
    </div>

    <div class="panel">
        <div style="display: flex; flex-direction: column;">
            ${[1, 2, 3, 4, 5].map(() => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--line);">
                    <div>
                        <span class="skeleton-box" style="width: 190px; height: 16px; display: block; margin-bottom: 5px;"></span>
                        <span class="skeleton-box" style="width: 260px; height: 12px; display: block;"></span>
                    </div>
                    <span class="skeleton-box" style="width: 80px; height: 24px; border-radius: 999px;"></span>
                </div>
            `).join('')}
        </div>
    </div>
</div>
`;

// ==========================================================================
// Route Matcher & Layout Selector
// ==========================================================================
const getPageTypeAndTitle = (url) => {
    const path = new URL(url, window.location.origin).pathname;

    if (path === '/' || path === '/dashboard') {
        return { type: 'dashboard', title: 'Ringkasan' };
    }
    if (path.startsWith('/monitoring/services')) {
        return { type: 'services', title: 'Layanan KANMIS' };
    }
    if (path.startsWith('/monitoring/backups')) {
        return { type: 'table', title: 'Riwayat Backup' };
    }
    if (path.startsWith('/calendar')) {
        if (path.includes('/create') || path.includes('/edit')) {
            return { type: 'form', title: 'Kalender Kegiatan' };
        }
        if (path.match(/\/calendar\/events\/\d+$/)) {
            return { type: 'detail', title: 'Detail Kegiatan' };
        }
        return { type: 'calendar', title: 'Kalender Kegiatan' };
    }
    if (path.includes('/create') || path.includes('/edit')) {
        let title = 'Formulir';
        if (path.startsWith('/lpks')) title = 'Data LPK';
        else if (path.startsWith('/accreditations')) title = 'Proses Akreditasi';
        else if (path.startsWith('/assessments')) title = 'Program Asesmen';
        else if (path.startsWith('/issues')) title = 'Masalah';
        else if (path.startsWith('/amendments')) title = 'Amandemen';
        return { type: 'form', title };
    }
    if (path.match(/\/(lpks|accreditations|assessments|issues|amendments)\/\d+$/)) {
        let title = 'Detail Data';
        if (path.startsWith('/lpks')) title = 'Data LPK';
        else if (path.startsWith('/accreditations')) title = 'Proses Akreditasi';
        else if (path.startsWith('/assessments')) title = 'Program Asesmen';
        else if (path.startsWith('/issues')) title = 'Masalah';
        else if (path.startsWith('/amendments')) title = 'Amandemen';
        return { type: 'detail', title };
    }
    if (path.startsWith('/assessments')) return { type: 'table', title: 'Program Asesmen' };
    if (path.startsWith('/lpks')) return { type: 'table', title: 'Data LPK' };
    if (path.startsWith('/accreditations')) return { type: 'table', title: 'Proses Akreditasi' };
    if (path.startsWith('/issues')) return { type: 'table', title: 'Masalah' };
    if (path.startsWith('/amendments')) return { type: 'table', title: 'Amandemen' };

    return { type: 'table', title: 'Workspace' };
};

const renderSkeletonForType = (type) => {
    switch (type) {
        case 'dashboard':
            return getSkeletonDashboardHtml();
        case 'services':
            return getSkeletonServicesHtml();
        case 'calendar':
            return getSkeletonCalendarHtml();
        case 'detail':
            return getSkeletonDetailHtml();
        case 'form':
            return getSkeletonFormHtml();
        case 'table':
        default:
            return getSkeletonTableHtml();
    }
};

// ==========================================================================
// SPA-Style Transition & Navigation Controller
// ==========================================================================
let isNavigating = false;
let onNavigateCallback = null;

const navigateTo = async (url, pushState = true) => {
    const pageWrap = document.querySelector('#page-content-wrapper') || document.querySelector('.page-wrap');
    if (!pageWrap) {
        window.location.href = url;
        return;
    }

    if (isNavigating) return;
    isNavigating = true;

    try {
        // Immediately close and clean up any active popovers and modals before navigation
        if (typeof closeEventPopover === 'function') closeEventPopover();
        else window.closeEventPopover?.();

        if (typeof closeModal === 'function') closeModal();
        else window.closeModal?.();

        window.closeNotificationDropdown?.();

        document.querySelectorAll('body > .gcal-popover, body > #gcal-event-popover').forEach((p) => {
            if (typeof returnPopoverToPlaceholder === 'function') returnPopoverToPlaceholder(p);
            if (p.parentElement === document.body) {
                p.remove();
            }
        });
        document.querySelectorAll('body > .simasadi-modal').forEach((m) => {
            if (typeof returnModalToPlaceholder === 'function') returnModalToPlaceholder(m);
            if (m.parentElement === document.body) {
                m.remove();
            }
        });
        document.body.classList.remove('modal-open');

        const currentUrlObj = new URL(window.location.href);
        const targetUrlObj = new URL(url, window.location.origin);
        const isCalendarToCalendar = currentUrlObj.pathname === '/calendar' &&
                                     targetUrlObj.pathname === '/calendar' &&
                                     !targetUrlObj.pathname.includes('/create') &&
                                     !targetUrlObj.pathname.includes('/edit') &&
                                     !targetUrlObj.pathname.match(/\/calendar\/events\/\d+/);

        // =========================================================================
        // IN-CALENDAR SEAMLESS TRANSITION
        // =========================================================================
        if (isCalendarToCalendar) {
            const curShell = document.querySelector('.gcal-shell');
            if (curShell) {
                curShell.classList.add('gcal-is-updating');
            }

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok || response.redirected) {
                window.location.href = response.url || url;
                return;
            }

            const htmlText = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');

            const newShell = doc.querySelector('.gcal-shell');
            const activeShell = document.querySelector('.gcal-shell');
            if (newShell && activeShell) {
                activeShell.replaceWith(newShell);
            } else {
                const newContent = doc.querySelector('#page-content-wrapper') || doc.querySelector('.page-wrap');
                if (newContent) {
                    pageWrap.innerHTML = newContent.innerHTML;
                }
            }

            // Sync date in quick-add modal
            const newStartDate = doc.querySelector('#quick-input-start-date');
            const curStartDate = document.getElementById('quick-input-start-date');
            if (newStartDate && curStartDate) {
                curStartDate.value = newStartDate.value;
            }
            const newEndDate = doc.querySelector('#quick-input-end-date');
            const curEndDate = document.getElementById('quick-input-end-date');
            if (newEndDate && curEndDate) {
                curEndDate.value = newEndDate.value;
            }

            // Update document title
            if (doc.title) {
                document.title = doc.title;
            }

            // Re-initialize calendar components
            if (typeof initGcalComponents === 'function') initGcalComponents();
            if (typeof initCustomSelects === 'function') initCustomSelects(document);

            if (pushState) {
                window.history.pushState({ url }, '', url);
            }
            return;
        }

        // =========================================================================
        // FAST MENU TRANSITION ACROSS APPLICATION
        // =========================================================================
        const { type, title } = getPageTypeAndTitle(url);

        // Update context title & active nav link immediately
        const contextTitle = document.querySelector('.context-title');
        if (contextTitle && title) {
            contextTitle.textContent = title;
        }

        const navLinks = document.querySelectorAll('#primary-navigation a');
        const targetPath = targetUrlObj.pathname;
        navLinks.forEach((link) => {
            const linkPath = new URL(link.href, window.location.origin).pathname;
            const isActive = (linkPath === '/' || linkPath === '/dashboard')
                ? (targetPath === '/' || targetPath === '/dashboard')
                : (targetPath.startsWith(linkPath) && linkPath !== '/');
            link.classList.toggle('active', isActive);
        });

        // Close mobile drawer if open
        if (typeof setDrawerState === 'function') {
            setDrawerState(false);
        } else if (typeof window.setDrawerState === 'function') {
            window.setDrawerState(false);
        }

        window.scrollTo({ top: 0, behavior: 'instant' });

        const originalContent = pageWrap.innerHTML;
        let skeletonRendered = false;

        // Debounced skeleton: Only show skeleton if fetch takes longer than 80ms
        // This ensures local navigation is instantaneous without flickering or lag!
        const skeletonTimer = setTimeout(() => {
            skeletonRendered = true;
            pageWrap.innerHTML = renderSkeletonForType(type);
        }, 80);

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        clearTimeout(skeletonTimer);

        if (response.redirected) {
            if (skeletonRendered) {
                pageWrap.innerHTML = originalContent;
            }
            window.location.href = response.url;
            return;
        }

        const htmlText = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');

        const newContent = doc.querySelector('#page-content-wrapper') || doc.querySelector('.page-wrap');
        if (!newContent) {
            // Non-HTML or outside app-shell layout (e.g. login redirect)
            if (skeletonRendered) {
                pageWrap.innerHTML = originalContent;
            }
            window.location.href = response.url || url;
            return;
        }

        // Update document title
        if (doc.title) {
            document.title = doc.title;
        }

        // Update context title from fetched page
        const newContextTitle = doc.querySelector('.context-title');
        if (newContextTitle && contextTitle) {
            contextTitle.textContent = newContextTitle.textContent;
        }

        // Swap content into page wrapper
        pageWrap.innerHTML = newContent.innerHTML;

        // Re-initialize all interactive components (tables, calendars, filters)
        if (typeof onNavigateCallback === 'function') {
            onNavigateCallback();
        } else if (typeof window.initPageComponents === 'function') {
            window.initPageComponents();
        }

        if (pushState) {
            window.history.pushState({ url }, '', url);
        }
    } catch (err) {
        console.error('Page transition error, falling back:', err);
        window.location.href = url;
    } finally {
        document.querySelector('.gcal-shell')?.classList.remove('gcal-is-updating');
        isNavigating = false;
    }
};

let routerInitialized = false;
export const initSpaRouter = (callback) => {
    if (callback) {
        onNavigateCallback = callback;
    }

    if (routerInitialized) return;
    routerInitialized = true;

    // Intercept internal link clicks
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link) return;

        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) return;

        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

        const url = new URL(link.href, window.location.origin);
        if (url.origin !== window.location.origin) return;

        // Skip form-submitting links or logout buttons
        if (link.closest('form')) return;

        event.preventDefault();
        navigateTo(link.href);
    });

    // Intercept GET filter forms for smooth skeleton transitions
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form');
        if (!form || form.method.toUpperCase() !== 'GET') return;

        const url = new URL(form.action || window.location.href, window.location.origin);
        const formData = new FormData(form);
        for (const [key, val] of formData.entries()) {
            if (val) {
                url.searchParams.set(key, val);
            } else {
                url.searchParams.delete(key);
            }
        }

        event.preventDefault();
        navigateTo(url.toString());
    });

    // Support browser Back and Forward buttons
    window.addEventListener('popstate', () => {
        window.closeEventPopover?.();
        window.closeModal?.();
        navigateTo(window.location.href, false);
    });

    // Handle BFCache recovery if page was restored from browser cache with pending skeleton
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            const pageWrap = document.querySelector('#page-content-wrapper') || document.querySelector('.page-wrap');
            if (pageWrap && pageWrap.querySelector('.skeleton-wrapper')) {
                navigateTo(window.location.href, false);
            }
        }
    });
};

export { navigateTo };
