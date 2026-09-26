import Swal from 'sweetalert2';
window.Swal = Swal;

import { initMobileDrawer, initSidebarCollapse, toggleSidebarState } from './modules/sidebar.js';
import { closeAllCustomSelects, initCustomSelects } from './modules/custom-select.js';
import { initDataTables, syncViewToggleLocation } from './modules/datatable.js';
import { handleFlashNotifications } from './modules/notifications.js';
import { initModalListeners, openModal, closeModal, returnModalToPlaceholder } from './modules/modals.js';
import { initButtonLoader } from './modules/button-loader.js';
import { initAuthTransitions } from './modules/auth-transitions.js';
import { initConfirmations } from './modules/confirmations.js';
import {
    showEventPopover,
    returnPopoverToPlaceholder,
    closeEventPopover,
    quickAddAt,
    toggleCreateDropdown,
    openCreateDropdown,
    closeCreateDropdown,
    openQuickAddWithType,
    updateQuickAddType,
    initGcalLiveTimeLine,
    initGcalFilters,
    initGcalComponents
} from './modules/calendar.js';
import { initSpaRouter, navigateTo } from './modules/spa-router.js';
import { initLiveFilters } from './modules/live-filter.js';

// ==========================================================================
// Global Window API (for Inline Blade Callbacks, e.g. onclick="window.openModal(...)")
// ==========================================================================
window.closeAllCustomSelects = closeAllCustomSelects;
window.openModal = openModal;
window.closeModal = closeModal;
window.returnModalToPlaceholder = returnModalToPlaceholder;
window.showEventPopover = showEventPopover;
window.closeEventPopover = closeEventPopover;
window.returnPopoverToPlaceholder = returnPopoverToPlaceholder;
window.quickAddAt = quickAddAt;
window.toggleCreateDropdown = toggleCreateDropdown;
window.openCreateDropdown = openCreateDropdown;
window.closeCreateDropdown = closeCreateDropdown;
window.openQuickAddWithType = openQuickAddWithType;
window.updateQuickAddType = updateQuickAddType;
window.navigateTo = navigateTo;
window.toggleSidebarState = toggleSidebarState;
window.initDataTables = initDataTables;

export function closeNotificationDropdown() {
    const currentMenu = document.getElementById('notif-dropdown-menu');
    const currentBtn = document.getElementById('notif-dropdown-btn');
    if (currentMenu && currentMenu.style.display !== 'none') {
        currentMenu.style.display = 'none';
        currentBtn?.setAttribute('aria-expanded', 'false');
    }
}
window.closeNotificationDropdown = closeNotificationDropdown;

export function closeUserDropdown() {
    const currentMenu = document.getElementById('user-dropdown-menu');
    const currentBtn = document.getElementById('user-dropdown-btn');
    if (currentMenu && currentMenu.style.display !== 'none') {
        currentMenu.style.display = 'none';
        currentBtn?.setAttribute('aria-expanded', 'false');
    }
}
window.closeUserDropdown = closeUserDropdown;

export function initNotificationDropdown() {
    const btn = document.getElementById('notif-dropdown-btn');
    const menu = document.getElementById('notif-dropdown-menu');
    if (!btn || !menu) return;

    btn.onclick = (e) => {
        e.stopPropagation();
        closeUserDropdown();
        const isHidden = menu.style.display === 'none' || getComputedStyle(menu).display === 'none';
        menu.style.display = isHidden ? 'block' : 'none';
        btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    };

    menu.onclick = (e) => {
        if (e.target.closest('a, button')) {
            closeNotificationDropdown();
        }
    };
}

export function initUserDropdown() {
    const btn = document.getElementById('user-dropdown-btn');
    const menu = document.getElementById('user-dropdown-menu');
    if (!btn || !menu) return;

    btn.onclick = (e) => {
        e.stopPropagation();
        closeNotificationDropdown();
        const isHidden = menu.style.display === 'none' || getComputedStyle(menu).display === 'none';
        menu.style.display = isHidden ? 'block' : 'none';
        btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    };

    menu.onclick = (e) => {
        if (e.target.closest('a, button')) {
            closeUserDropdown();
        }
    };
}

if (!window._dropdownsBound) {
    window._dropdownsBound = true;
    document.addEventListener('click', (e) => {
        const currentNotifMenu = document.getElementById('notif-dropdown-menu');
        const currentNotifBtn = document.getElementById('notif-dropdown-btn');
        if (currentNotifMenu && currentNotifMenu.style.display !== 'none') {
            if (!currentNotifMenu.contains(e.target) && e.target !== currentNotifBtn && !currentNotifBtn?.contains(e.target)) {
                closeNotificationDropdown();
            }
        }

        const currentUserMenu = document.getElementById('user-dropdown-menu');
        const currentUserBtn = document.getElementById('user-dropdown-btn');
        if (currentUserMenu && currentUserMenu.style.display !== 'none') {
            if (!currentUserMenu.contains(e.target) && e.target !== currentUserBtn && !currentUserBtn?.contains(e.target)) {
                closeUserDropdown();
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeNotificationDropdown();
            closeUserDropdown();
        }
    });
}

// ==========================================================================
// Page Components Initializer (Idempotent for Initial Load & SPA Transitions)
// ==========================================================================
export const initPageComponents = () => {
    // 1. Custom Select Dropdowns
    try {
        initCustomSelects();
    } catch (err) {
        console.error('Error initializing custom selects:', err);
    }

    // 2. Data Tables: sorting, pagination per-page toolbar, view mode toggles & filter drawer
    try {
        initDataTables();
    } catch (err) {
        console.error('Error initializing datatables:', err);
    }

    // 3. Flash Notifications via SweetAlert2
    handleFlashNotifications();

    // 4. Google Calendar Components (live WIB timeline & instant filters)
    if (document.querySelector('.gcal-shell')) {
        initGcalComponents();
    }

    // 5. Topbar Dropdowns
    initNotificationDropdown();
    initUserDropdown();
};

window.initPageComponents = initPageComponents;

// ==========================================================================
// Clickable Table Rows (Row/Cell Navigation to Details)
// ==========================================================================
export const initClickableRows = () => {
    document.addEventListener('click', (event) => {
        const row = event.target.closest('tr.clickable-row, tr[data-href], .clickable-row[data-href]');
        if (!row) return;

        // Ignore clicks on nested interactive elements
        if (event.target.closest('a, button, input, select, textarea, label, [data-no-row-click]')) {
            return;
        }

        // Ignore text selection
        const selection = window.getSelection();
        if (selection && selection.toString().trim().length > 0) {
            return;
        }

        const href = row.getAttribute('data-href');
        if (!href) return;

        // Middle click or modifier key click -> open in new tab
        if (event.button === 1 || event.ctrlKey || event.metaKey) {
            window.open(href, '_blank');
            return;
        }

        // Left click
        if (event.button === 0) {
            if (typeof window.navigateTo === 'function') {
                window.navigateTo(href);
            } else {
                window.location.href = href;
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        const row = event.target.closest('tr.clickable-row[tabindex], tr[data-href][tabindex], .clickable-row[tabindex]');
        if (!row || event.target !== row) return;

        const href = row.getAttribute('data-href');
        if (!href) return;

        event.preventDefault();
        if (typeof window.navigateTo === 'function') {
            window.navigateTo(href);
        } else {
            window.location.href = href;
        }
    });
};

// ==========================================================================
// Application Bootstrap
// ==========================================================================
initMobileDrawer();
initSidebarCollapse();
initModalListeners();
initButtonLoader();
initAuthTransitions();
initConfirmations();
initClickableRows();
initLiveFilters();
initSpaRouter(initPageComponents);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageComponents);
} else {
    initPageComponents();
}
