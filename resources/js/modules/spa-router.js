import { setDrawerState } from './sidebar.js';
import { returnModalToPlaceholder, closeModal } from './modals.js';
import { returnPopoverToPlaceholder, closeEventPopover, initGcalComponents } from './calendar.js';
import { initCustomSelects } from './custom-select.js';
import { closeAllCustomPickers, initCustomPickers } from './custom-picker.js';

// ==========================================================================
// Custom Skeleton Loading Layouts per Menu (Synced with Latest 2026 UI)
// ==========================================================================
const getSkeletonDashboardHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 280px; height: 32px; border-radius: 6px;"></span>
            <span class="skeleton-box" style="width: 440px; height: 16px; margin-top: 6px; border-radius: 4px;"></span>
        </div>
        <div style="display: flex; gap: 10px;">
            <span class="skeleton-box" style="width: 130px; height: 38px; border-radius: 7px;"></span>
            <span class="skeleton-box" style="width: 140px; height: 38px; border-radius: 7px;"></span>
        </div>
    </div>

    <div class="metric-grid" style="margin-bottom: 24px;">
        ${[1, 2, 3, 4].map(() => `
            <div class="skeleton-metric-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="skeleton-box" style="width: 120px; height: 13px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px;"></span>
                </div>
                <div style="margin-top: 8px;">
                    <span class="skeleton-box" style="width: 60px; height: 34px; border-radius: 6px; display: block; margin-bottom: 6px;"></span>
                    <span class="skeleton-box" style="width: 130px; height: 12px; border-radius: 4px;"></span>
                </div>
            </div>
        `).join('')}
    </div>

    <div class="panel" style="margin-bottom: 24px; padding: 18px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <span class="skeleton-box" style="width: 340px; height: 18px; border-radius: 4px; display: block; margin-bottom: 6px;"></span>
                <span class="skeleton-box" style="width: 260px; height: 13px; border-radius: 4px;"></span>
            </div>
            <span class="skeleton-box" style="width: 120px; height: 24px; border-radius: 999px;"></span>
        </div>
        <div style="display: grid; gap: 12px;">
            ${[1, 2].map(() => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="skeleton-box" style="width: 36px; height: 24px; border-radius: 6px;"></span>
                        <div>
                            <span class="skeleton-box" style="width: 200px; height: 15px; border-radius: 4px; display: block; margin-bottom: 5px;"></span>
                            <span class="skeleton-box" style="width: 160px; height: 12px; border-radius: 4px;"></span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <span class="skeleton-box" style="width: 80px; height: 32px; border-radius: 6px;"></span>
                        <span class="skeleton-box" style="width: 90px; height: 32px; border-radius: 6px;"></span>
                    </div>
                </div>
            `).join('')}
        </div>
    </div>

    <div class="dashboard-overview-grid">
        <div class="panel" style="padding: 18px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="skeleton-box" style="width: 160px; height: 18px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 50px; height: 20px; border-radius: 999px;"></span>
                </div>
                <span class="skeleton-box" style="width: 80px; height: 14px; border-radius: 4px;"></span>
            </div>
            <div style="display: grid; gap: 10px;">
                ${[1, 2, 3, 4].map(() => `
                    <div style="padding: 12px; border: 1px solid var(--line); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <div style="display: flex; gap: 8px; margin-bottom: 6px;">
                                <span class="skeleton-box" style="width: 90px; height: 14px; border-radius: 4px;"></span>
                                <span class="skeleton-box" style="width: 60px; height: 18px; border-radius: 999px;"></span>
                            </div>
                            <span class="skeleton-box" style="width: 210px; height: 16px; border-radius: 4px; display: block; margin-bottom: 6px;"></span>
                            <span class="skeleton-box" style="width: 170px; height: 12px; border-radius: 4px;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 16px; height: 16px; border-radius: 4px;"></span>
                    </div>
                `).join('')}
            </div>
        </div>

        <div class="panel" style="padding: 18px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="skeleton-box" style="width: 140px; height: 18px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 60px; height: 20px; border-radius: 999px;"></span>
                </div>
                <span class="skeleton-box" style="width: 95px; height: 14px; border-radius: 4px;"></span>
            </div>
            <div style="display: grid; gap: 10px;">
                ${[1, 2, 3, 4].map(() => `
                    <div style="padding: 12px; border: 1px solid var(--line); border-radius: 8px; display: flex; align-items: center; gap: 14px;">
                        <span class="skeleton-box" style="width: 48px; height: 48px; border-radius: 8px; flex-shrink: 0;"></span>
                        <div style="flex: 1;">
                            <span class="skeleton-box" style="width: 180px; height: 15px; border-radius: 4px; display: block; margin-bottom: 6px;"></span>
                            <span class="skeleton-box" style="width: 140px; height: 12px; border-radius: 4px;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 70px; height: 22px; border-radius: 999px; flex-shrink: 0;"></span>
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
            <span class="skeleton-box" style="width: 160px; height: 30px; border-radius: 6px;"></span>
            <span class="skeleton-box" style="width: 420px; height: 15px; margin-top: 6px; border-radius: 4px;"></span>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <span class="skeleton-box" style="width: 145px; height: 38px; border-radius: 7px;"></span>
            <span class="skeleton-box" style="width: 105px; height: 38px; border-radius: 7px;"></span>
            <span class="skeleton-box" style="width: 125px; height: 38px; border-radius: 7px;"></span>
        </div>
    </div>

    <div class="panel" style="padding: 18px 20px;">
        <div class="skeleton-filter-bar">
            <div class="skeleton-filter-inputs">
                <div>
                    <span class="skeleton-box" style="width: 60px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 70px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 100px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 80px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 12px;">
                <span class="skeleton-box" style="width: 95px; height: 36px; border-radius: 7px;"></span>
                <span class="skeleton-box" style="width: 75px; height: 36px; border-radius: 7px;"></span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <div class="skeleton-table-head">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <span class="skeleton-box" style="width: 16px; height: 16px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 140px; height: 13px; border-radius: 3px;"></span>
                </div>
                <span class="skeleton-box" style="width: 90px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 110px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 100px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 80px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 50px; height: 13px; border-radius: 3px;"></span>
            </div>
            ${[1, 2, 3, 4, 5, 6].map(() => `
                <div class="skeleton-table-row">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <span class="skeleton-box" style="width: 16px; height: 16px; border-radius: 4px;"></span>
                        <div>
                            <span class="skeleton-box" style="width: 190px; height: 15px; border-radius: 4px; display: block; margin-bottom: 5px;"></span>
                            <span class="skeleton-box" style="width: 110px; height: 11px; border-radius: 3px; display: block;"></span>
                        </div>
                    </div>
                    <span class="skeleton-box" style="width: 75px; height: 22px; border-radius: 999px;"></span>
                    <span class="skeleton-box" style="width: 95px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 85px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 80px; height: 22px; border-radius: 999px;"></span>
                    <div style="display: flex; gap: 6px;">
                        <span class="skeleton-box" style="width: 28px; height: 28px; border-radius: 6px;"></span>
                        <span class="skeleton-box" style="width: 28px; height: 28px; border-radius: 6px;"></span>
                    </div>
                </div>
            `).join('')}
        </div>

        <div style="border-top: 1px solid var(--line); margin-top: 20px; padding-top: 16px; display: flex; justify-content: space-between; align-items: center;">
            <span class="skeleton-box" style="width: 200px; height: 14px; border-radius: 4px;"></span>
            <div style="display: flex; gap: 6px;">
                <span class="skeleton-box" style="width: 34px; height: 34px; border-radius: 6px;"></span>
                <span class="skeleton-box" style="width: 34px; height: 34px; border-radius: 6px;"></span>
                <span class="skeleton-box" style="width: 34px; height: 34px; border-radius: 6px;"></span>
                <span class="skeleton-box" style="width: 34px; height: 34px; border-radius: 6px;"></span>
            </div>
        </div>
    </div>
</div>
`;

const getSkeletonCalendarHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 170px; height: 30px; border-radius: 6px;"></span>
            <span class="skeleton-box" style="width: 420px; height: 15px; margin-top: 6px; border-radius: 4px;"></span>
        </div>
    </div>

    <div class="gcal-shell">
        <aside class="gcal-sidebar">
            <span class="skeleton-box" style="width: 100%; height: 42px; border-radius: 8px; margin-bottom: 14px;"></span>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span class="skeleton-box" style="width: 110px; height: 15px; border-radius: 4px;"></span>
                    <div style="display: flex; gap: 4px;">
                        <span class="skeleton-box" style="width: 22px; height: 22px; border-radius: 4px;"></span>
                        <span class="skeleton-box" style="width: 22px; height: 22px; border-radius: 4px;"></span>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;">
                    ${Array.from({ length: 28 }).map(() => `
                        <span class="skeleton-box" style="width: 100%; aspect-ratio: 1; border-radius: 4px;"></span>
                    `).join('')}
                </div>
            </div>
            <div>
                <span class="skeleton-box" style="width: 90px; height: 13px; border-radius: 3px; display: block; margin-bottom: 10px;"></span>
                ${[1, 2, 3].map(() => `
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span class="skeleton-box" style="width: 14px; height: 14px; border-radius: 3px;"></span>
                        <span class="skeleton-box" style="width: 120px; height: 12px; border-radius: 3px;"></span>
                    </div>
                `).join('')}
            </div>
        </aside>

        <main class="gcal-main">
            <div class="gcal-toolbar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="skeleton-box" style="width: 70px; height: 34px; border-radius: 6px;"></span>
                    <div style="display: flex; gap: 4px;">
                        <span class="skeleton-box" style="width: 32px; height: 32px; border-radius: 6px;"></span>
                        <span class="skeleton-box" style="width: 32px; height: 32px; border-radius: 6px;"></span>
                    </div>
                    <span class="skeleton-box" style="width: 130px; height: 22px; border-radius: 4px;"></span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <span class="skeleton-box" style="width: 60px; height: 32px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 65px; height: 32px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 55px; height: 32px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 65px; height: 32px; border-radius: 6px;"></span>
                </div>
            </div>

            <div class="skeleton-calendar-headings">
                ${['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].map(() => `
                    <span class="skeleton-box" style="width: 40px; height: 12px; margin: auto; border-radius: 3px;"></span>
                `).join('')}
            </div>

            <div class="skeleton-calendar-grid">
                ${Array.from({ length: 28 }).map((_, i) => `
                    <div class="skeleton-calendar-cell">
                        <span class="skeleton-box" style="width: 22px; height: 22px; border-radius: 4px;"></span>
                        ${i % 4 === 1 ? '<span class="skeleton-box" style="width: 90%; height: 18px; border-radius: 4px; margin-top: 4px;"></span>' : ''}
                        ${i % 7 === 3 ? '<span class="skeleton-box" style="width: 80%; height: 18px; border-radius: 4px; margin-top: 2px;"></span>' : ''}
                    </div>
                `).join('')}
            </div>
        </main>
    </div>
</div>
`;

const getSkeletonDetailHtml = () => `
<div class="skeleton-wrapper">
    <div style="margin-bottom: 12px;">
        <span class="skeleton-box" style="width: 100px; height: 18px; border-radius: 4px;"></span>
    </div>

    <div class="panel" style="padding: 20px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="skeleton-box" style="width: 280px; height: 28px; border-radius: 6px; display: block; margin-bottom: 10px;"></span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <span class="skeleton-box" style="width: 110px; height: 22px; border-radius: 999px;"></span>
                    <span class="skeleton-box" style="width: 130px; height: 22px; border-radius: 999px;"></span>
                    <span class="skeleton-box" style="width: 90px; height: 22px; border-radius: 999px;"></span>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <span class="skeleton-box" style="width: 100px; height: 36px; border-radius: 7px;"></span>
                <span class="skeleton-box" style="width: 90px; height: 36px; border-radius: 7px;"></span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
        ${[1, 2, 3].map(() => `
            <div class="panel" style="padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span class="skeleton-box" style="width: 120px; height: 14px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 65px; height: 20px; border-radius: 999px;"></span>
                </div>
                <span class="skeleton-box" style="width: 150px; height: 18px; border-radius: 4px; display: block; margin-bottom: 8px;"></span>
                <span class="skeleton-box" style="width: 100px; height: 12px; border-radius: 4px;"></span>
            </div>
        `).join('')}
    </div>

    <div class="detail-grid">
        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 140px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            ${[1, 2, 3, 4, 5].map(() => `
                <div class="skeleton-detail-item">
                    <span class="skeleton-box" style="width: 90px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 180px; height: 14px; border-radius: 3px;"></span>
                </div>
            `).join('')}
        </div>

        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 160px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            <div style="display: grid; gap: 12px;">
                ${[1, 2, 3].map(() => `
                    <div style="padding: 12px; border: 1px solid var(--line); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span class="skeleton-box" style="width: 150px; height: 15px; border-radius: 4px; display: block; margin-bottom: 4px;"></span>
                            <span class="skeleton-box" style="width: 100px; height: 12px; border-radius: 3px;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 70px; height: 22px; border-radius: 999px;"></span>
                    </div>
                `).join('')}
            </div>
        </div>
    </div>
</div>
`;

const getSkeletonFormHtml = () => `
<div class="skeleton-wrapper">
    <div style="margin-bottom: 12px;">
        <span class="skeleton-box" style="width: 110px; height: 18px; border-radius: 4px;"></span>
    </div>

    <div class="panel" style="padding: 20px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="skeleton-box" style="width: 240px; height: 26px; border-radius: 6px; display: block; margin-bottom: 6px;"></span>
                <span class="skeleton-box" style="width: 380px; height: 14px; border-radius: 4px;"></span>
            </div>
            <div style="display: flex; gap: 8px;">
                <span class="skeleton-box" style="width: 80px; height: 38px; border-radius: 7px;"></span>
                <span class="skeleton-box" style="width: 135px; height: 38px; border-radius: 7px;"></span>
            </div>
        </div>
    </div>

    <div class="detail-grid">
        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 160px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            <div style="display: grid; gap: 16px;">
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 110px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 130px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 95px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 120px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 80px; border-radius: 7px;"></span>
                </div>
            </div>
        </div>

        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 180px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            <div style="display: grid; gap: 16px;">
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 100px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 115px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 105px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 90px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
            </div>
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
        else if (path.startsWith('/users')) title = 'Manajemen Pengguna';
        else if (path.startsWith('/profile')) title = 'Profil Pengguna';
        return { type: 'form', title };
    }
    if (path.match(/\/(lpks|accreditations|assessments|users)\/\d+$/)) {
        let title = 'Detail Data';
        if (path.startsWith('/lpks')) title = 'Data LPK';
        else if (path.startsWith('/accreditations')) title = 'Proses Akreditasi';
        else if (path.startsWith('/assessments')) title = 'Program Asesmen';
        else if (path.startsWith('/users')) title = 'Detail Anggota';
        return { type: 'detail', title };
    }
    if (path.startsWith('/profile')) return { type: 'form', title: 'Profil Pengguna' };
    if (path.startsWith('/assessments')) return { type: 'table', title: 'Program Asesmen' };
    if (path.startsWith('/lpks')) return { type: 'table', title: 'Data LPK' };
    if (path.startsWith('/accreditations')) return { type: 'table', title: 'Proses Akreditasi' };
    if (path.startsWith('/users')) return { type: 'table', title: 'Manajemen Pengguna' };

    return { type: 'table', title: 'Workspace' };
};

const renderSkeletonForType = (type) => {
    switch (type) {
        case 'dashboard':
            return getSkeletonDashboardHtml();
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
// In-Memory Page Cache & Hover Prefetch Controller
// ==========================================================================
const PAGE_CACHE_TTL = 60 * 1000; // 60 seconds TTL
const pageCache = new Map();
const prefetchInFlight = new Map();

const clearPageCache = () => {
    pageCache.clear();
    prefetchInFlight.clear();
};
window.clearPageCache = clearPageCache;

const isCacheableUrl = (urlStr) => {
    try {
        const u = new URL(urlStr, window.location.origin);
        if (u.origin !== window.location.origin) return false;
        if (u.pathname.includes('/logout') || u.pathname.includes('/export') || u.pathname.includes('/download')) return false;
        return true;
    } catch {
        return false;
    }
};

const prefetchUrl = (urlStr) => {
    if (!isCacheableUrl(urlStr)) return;
    try {
        const u = new URL(urlStr, window.location.origin);
        const cleanUrl = u.href;

        const cached = pageCache.get(cleanUrl);
        if (cached && (Date.now() - cached.timestamp < PAGE_CACHE_TTL)) {
            return;
        }

        if (prefetchInFlight.has(cleanUrl)) {
            return;
        }

        const fetchPromise = fetch(cleanUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(async (response) => {
            prefetchInFlight.delete(cleanUrl);
            if (!response.ok || response.redirected) return null;
            const text = await response.text();
            pageCache.set(cleanUrl, {
                htmlText: text,
                timestamp: Date.now()
            });
            return text;
        }).catch(() => {
            prefetchInFlight.delete(cleanUrl);
            return null;
        });

        prefetchInFlight.set(cleanUrl, fetchPromise);
    } catch {
        // Silently ignore invalid URLs
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

        if (typeof closeAllCustomPickers === 'function') closeAllCustomPickers();
        else window.closeAllCustomPickers?.();

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
        document.documentElement.classList.remove('modal-open');
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

            // Preserve active category checkbox filters, selected LPK, and selected PIC
            const activeCatStates = {};
            document.querySelectorAll('[data-filter-cat]').forEach((cb) => {
                activeCatStates[cb.dataset.filterCat] = cb.checked;
            });
            const selectedLpk = document.getElementById('gcal-filter-lpk')?.value;
            const selectedPic = document.getElementById('gcal-filter-pic')?.value;

            if (selectedPic && !targetUrlObj.searchParams.has('pic_id')) {
                targetUrlObj.searchParams.set('pic_id', selectedPic);
                url = targetUrlObj.toString();
            }

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Partial-Content': 'calendar'
                }
            });

            if (!response.ok || response.redirected) {
                window.location.href = response.url || url;
                return;
            }

            const htmlText = await response.text();
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

            // Re-initialize calendar components and custom dropdowns
            if (typeof initGcalComponents === 'function') initGcalComponents();
            if (typeof initCustomSelects === 'function') initCustomSelects(document);
            if (typeof initCustomPickers === 'function') initCustomPickers(document);

            if (pushState) {
                window.history.pushState({ url }, '', url);
            }

            window.dispatchEvent(new CustomEvent('simasadi:page-loaded'));
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
        let htmlText = null;

        const cleanTargetUrl = targetUrlObj.href;
        const cached = pageCache.get(cleanTargetUrl);

        if (cached && (Date.now() - cached.timestamp < PAGE_CACHE_TTL)) {
            htmlText = cached.htmlText;
        } else if (prefetchInFlight.has(cleanTargetUrl)) {
            htmlText = await prefetchInFlight.get(cleanTargetUrl);
        }

        if (!htmlText) {
            // Debounced skeleton: Only show skeleton if fetch takes longer than 150ms
            const skeletonTimer = setTimeout(() => {
                skeletonRendered = true;
                pageWrap.innerHTML = renderSkeletonForType(type);
            }, 150);

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

            htmlText = await response.text();
            if (isCacheableUrl(url)) {
                pageCache.set(cleanTargetUrl, {
                    htmlText,
                    timestamp: Date.now()
                });
            }
        }
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

        // Re-trigger buttery-smooth page-enter animation
        pageWrap.classList.remove('page-enter-active');
        void pageWrap.offsetWidth;
        pageWrap.classList.add('page-enter-active');

        // Update URL state BEFORE executing page scripts so window.location (pathname, search, hash)
        // reflects the destination page when inline scripts and page-loaded handlers run
        if (pushState) {
            window.history.pushState({ url }, '', url);
        }

        // Re-execute script tags within swapped content (since innerHTML disables scripts)
        pageWrap.querySelectorAll('script').forEach((oldScript) => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach((attr) => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        // Re-initialize all interactive components (tables, calendars, filters)
        if (typeof onNavigateCallback === 'function') {
            onNavigateCallback();
        } else if (typeof window.initPageComponents === 'function') {
            window.initPageComponents();
        }

        window.dispatchEvent(new CustomEvent('simasadi:page-loaded'));

        // Handle target hash scroll if navigation included an anchor fragment
        if (targetUrlObj.hash) {
            setTimeout(() => {
                try {
                    const hashId = targetUrlObj.hash.slice(1);
                    const targetElement = document.getElementById(hashId) || document.querySelector(targetUrlObj.hash);
                    if (targetElement) {
                        const topbar = document.querySelector('.topbar');
                        const topbarOffset = (topbar ? topbar.offsetHeight : 64) + 16;
                        const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
                        window.scrollTo({
                            top: Math.max(0, targetPosition - topbarOffset),
                            behavior: 'smooth'
                        });
                    }
                } catch (_) {
                    // Ignore selector errors
                }
            }, 60);
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

        // Skip pagination clicks inside partial table containers (handled by live-filter module)
        if (link.closest('.lpk-table-container .pagination, .lpk-table-container nav[role="navigation"]')) return;

        event.preventDefault();
        navigateTo(link.href);
    });

    // Intercept GET filter forms for smooth skeleton transitions
    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const form = event.target.closest('form');
        if (!form || form.method.toUpperCase() !== 'GET') return;
        if (form.hasAttribute('data-partial-filter')) return;

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

    // Prefetch on hover (pointerenter) and mobile touchstart for instant navigation
    const handlePrefetch = (event) => {
        const link = event.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (link.closest('form')) return;

        prefetchUrl(link.href);
    };

    document.addEventListener('pointerenter', handlePrefetch, { passive: true, capture: true });
    document.addEventListener('touchstart', handlePrefetch, { passive: true, capture: true });

    // Invalidate page cache when any mutative form is submitted
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form');
        if (form && form.method.toUpperCase() !== 'GET') {
            clearPageCache();
        }
    }, { capture: true });

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

export { navigateTo, clearPageCache, prefetchUrl };
