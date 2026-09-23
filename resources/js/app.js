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
    initGcalLiveTimeLine,
    initGcalFilters,
    initGcalComponents
} from './modules/calendar.js';
import { initSpaRouter, navigateTo } from './modules/spa-router.js';

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
window.navigateTo = navigateTo;
window.toggleSidebarState = toggleSidebarState;

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
    // 1. Data Tables: sorting, pagination per-page toolbar, view mode toggles & filter drawer
    initDataTables();

    // 2. Custom Select Dropdowns
    initCustomSelects();

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
// Application Bootstrap
// ==========================================================================
initMobileDrawer();
initSidebarCollapse();
initModalListeners();
initButtonLoader();
initAuthTransitions();
initConfirmations();
initSpaRouter(initPageComponents);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageComponents);
} else {
    initPageComponents();
}
