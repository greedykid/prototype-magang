import Swal from 'sweetalert2';
window.Swal = Swal;

import { initMobileDrawer, initSidebarCollapse, toggleSidebarState } from './modules/sidebar.js';
import { closeAllCustomSelects, initCustomSelects } from './modules/custom-select.js';
import { initCustomPickers, closeAllCustomPickers, syncCustomPickers } from './modules/custom-picker.js';
import { initDataTables, syncViewToggleLocation } from './modules/datatable.js';
import { handleFlashNotifications, initPersistentSurveillanceBanner } from './modules/notifications.js';
import { initModalListeners, openModal, closeModal, returnModalToPlaceholder, copyFormulaToClipboard } from './modules/modals.js';
import { initButtonLoader } from './modules/button-loader.js';
import { initAuthTransitions } from './modules/auth-transitions.js';
import { initConfirmations } from './modules/confirmations.js';
import {
    showEventPopover,
    returnPopoverToPlaceholder,
    closeEventPopover,
    quickAddAt,
    quickAddAtRange,
    getRangeAnchor,
    setRangeAnchor,
    clearRangeAnchor,
    toggleRangeSelectionMode,
    toggleCreateDropdown,
    openCreateDropdown,
    closeCreateDropdown,
    openQuickAddWithType,
    updateQuickAddType,
    initGcalLiveTimeLine,
    initGcalFilters,
    initGcalComponents
} from './modules/calendar.js';
import {
    initSpaRouter,
    navigateTo,
    clearPageCache,
    prefetchUrl,
    clearCalendarCache,
    prefetchCalendarPartial,
    scheduleAdjacentCalendarPrefetch,
    scheduleNavigationPrewarm
} from './modules/spa-router.js';
import { initLiveFilters, executePartialFilter } from './modules/live-filter.js';
import { initTableMultiselect, syncTableState, clearTableSelection } from './modules/table-multiselect.js';
import { initTheme, toggleTheme, applyTheme } from './modules/theme.js';
import { initPasswordToggles } from './modules/password-toggle.js';
import { initFileDropzones, initFileDropzone } from './modules/file-dropzone.js';
import { initBottomNav, syncBottomNavActive, setBottomSheetState } from './modules/bottom-nav.js';
import { initGlobalSearch } from './modules/global-search.js';
import { initSidebarClock } from './modules/sidebar-clock.js';
import { initCharCounters, bindCharCounterEvents } from './modules/char-counter.js';

// ==========================================================================
// Global Window API (for Inline Blade Callbacks, e.g. onclick="window.openModal(...)")
// ==========================================================================
window.closeAllCustomSelects = closeAllCustomSelects;
window.initCustomPickers = initCustomPickers;
window.closeAllCustomPickers = closeAllCustomPickers;
window.syncCustomPickers = syncCustomPickers;
window.initFileDropzones = initFileDropzones;
window.initFileDropzone = initFileDropzone;
window.openModal = openModal;
window.initCharCounters = initCharCounters;
window.closeModal = closeModal;
window.returnModalToPlaceholder = returnModalToPlaceholder;
window.copyFormulaToClipboard = copyFormulaToClipboard;
window.showEventPopover = showEventPopover;
window.closeEventPopover = closeEventPopover;
window.returnPopoverToPlaceholder = returnPopoverToPlaceholder;
window.quickAddAt = quickAddAt;
window.quickAddAtRange = quickAddAtRange;
window.getRangeAnchor = getRangeAnchor;
window.setRangeAnchor = setRangeAnchor;
window.clearRangeAnchor = clearRangeAnchor;
window.toggleRangeSelectionMode = toggleRangeSelectionMode;
window.toggleCreateDropdown = toggleCreateDropdown;
window.openCreateDropdown = openCreateDropdown;
window.closeCreateDropdown = closeCreateDropdown;
window.openQuickAddWithType = openQuickAddWithType;
window.updateQuickAddType = updateQuickAddType;
window.navigateTo = navigateTo;
window.clearPageCache = clearPageCache;
window.prefetchUrl = prefetchUrl;
window.clearCalendarCache = clearCalendarCache;
window.prefetchCalendarPartial = prefetchCalendarPartial;
window.scheduleAdjacentCalendarPrefetch = scheduleAdjacentCalendarPrefetch;
window.scheduleNavigationPrewarm = scheduleNavigationPrewarm;
window.toggleSidebarState = toggleSidebarState;
window.initDataTables = initDataTables;
window.executePartialFilter = executePartialFilter;
window.initTableMultiselect = initTableMultiselect;
window.syncTableState = syncTableState;
window.clearTableSelection = clearTableSelection;
window.toggleTheme = toggleTheme;
window.applyTheme = applyTheme;
window.initPasswordToggles = initPasswordToggles;
window.initBottomNav = initBottomNav;
window.syncBottomNavActive = syncBottomNavActive;
window.setBottomSheetState = setBottomSheetState;

export function closeNotificationDropdown() {
    const currentMenu = document.getElementById('notif-dropdown-menu');
    const currentBtn = document.getElementById('notif-dropdown-btn');
    const backdrop = document.getElementById('notif-dropdown-backdrop');
    if (currentMenu && currentMenu.style.display !== 'none') {
        currentMenu.style.display = 'none';
        currentBtn?.setAttribute('aria-expanded', 'false');
    }
    if (backdrop) {
        backdrop.style.display = 'none';
    }
    const userMenu = document.getElementById('user-dropdown-menu');
    if (!userMenu || userMenu.style.display === 'none') {
        document.querySelector('.topbar')?.classList.remove('has-open-dropdown');
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
    const notifMenu = document.getElementById('notif-dropdown-menu');
    if (!notifMenu || notifMenu.style.display === 'none') {
        document.querySelector('.topbar')?.classList.remove('has-open-dropdown');
    }
}
window.closeUserDropdown = closeUserDropdown;

export function initNotificationDropdown() {
    const btn = document.getElementById('notif-dropdown-btn');
    const menu = document.getElementById('notif-dropdown-menu');
    const backdrop = document.getElementById('notif-dropdown-backdrop');
    const closeBtn = document.getElementById('notif-dropdown-close');
    if (!btn || !menu) return;

    btn.onclick = (e) => {
        e.stopPropagation();
        closeUserDropdown();
        const isHidden = menu.style.display === 'none' || getComputedStyle(menu).display === 'none';
        if (isHidden) {
            menu.style.display = 'flex';
            btn.setAttribute('aria-expanded', 'true');
            document.querySelector('.topbar')?.classList.add('has-open-dropdown');
            if (backdrop && window.innerWidth <= 640) {
                backdrop.style.display = 'block';
            }
        } else {
            closeNotificationDropdown();
        }
    };

    if (closeBtn) {
        closeBtn.onclick = (e) => {
            e.stopPropagation();
            closeNotificationDropdown();
        };
    }

    if (backdrop) {
        backdrop.onclick = (e) => {
            e.stopPropagation();
            closeNotificationDropdown();
        };
    }

    menu.onclick = (e) => {
        if (e.target.closest('a') || e.target.closest('#notif-dropdown-close')) {
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
        if (isHidden) {
            menu.style.display = 'block';
            btn.setAttribute('aria-expanded', 'true');
            document.querySelector('.topbar')?.classList.add('has-open-dropdown');
        } else {
            closeUserDropdown();
        }
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

    // 1b. Custom Date & Clock Pickers
    try {
        initCustomPickers();
    } catch (err) {
        console.error('Error initializing custom pickers:', err);
    }

    // 2. Data Tables: sorting, pagination per-page toolbar, view mode toggles & filter drawer
    try {
        initDataTables();
    } catch (err) {
        console.error('Error initializing datatables:', err);
    }

    // 3. Flash Notifications via SweetAlert2 & Persistent Banners
    handleFlashNotifications();
    initPersistentSurveillanceBanner();

    // 4. Google Calendar Components (live WIB timeline & instant filters)
    if (document.querySelector('.gcal-shell')) {
        initGcalComponents();
    }

    // 5. Topbar Dropdowns & Global Search
    initNotificationDropdown();
    initUserDropdown();
    initGlobalSearch();

    // 6. Table Multiselection
    try {
        initTableMultiselect();
    } catch (err) {
        console.error('Error initializing table multiselect:', err);
    }

    // 7. Theme Switcher Sync
    initTheme();

    // 8. Password Visibility Toggles
    initPasswordToggles();

    // 9. File Dropzones
    initFileDropzones();

    // 10. Mobile Bottom Navigation
    try {
        initBottomNav();
        syncBottomNavActive();
    } catch (err) {
        console.error('Error initializing bottom nav:', err);
    }

    // 11. Sidebar Real-Time Operational Clock & Date
    initSidebarClock();

    // 12. Dashboard Compliance Meter Interactive Hover
    initComplianceMeterInteractions();

    // 13. Dashboard Directory Table Filtering & Search
    initDashboardDirectoryFilter();
};

export const initDashboardDirectoryFilter = () => {
    const directoryPanel = document.querySelector('.dashboard-directory-panel');
    if (!directoryPanel || directoryPanel.dataset.filterInit === 'true') return;
    directoryPanel.dataset.filterInit = 'true';

    const tabButtons = directoryPanel.querySelectorAll('.filter-tab-btn');
    const tableWrap = directoryPanel.querySelector('#directoryTableWrap');
    const tableRows = directoryPanel.querySelectorAll('.dashboard-directory-table tbody tr[data-status]');
    const searchInput = directoryPanel.querySelector('#directorySearchInput');
    const clearBtn = directoryPanel.querySelector('#directorySearchClear');
    const emptyState = directoryPanel.querySelector('#directoryEmptyState');
    const resetBtn = directoryPanel.querySelector('#resetDirectoryFilterBtn');

    let currentFilter = 'all';
    let searchQuery = '';

    const applyFilter = () => {
        let visibleCount = 0;
        tableRows.forEach((row) => {
            const rowStatuses = (row.dataset.status || '').split(' ');
            const rowText = (row.textContent || '').toLowerCase();
            const matchesFilter = (currentFilter === 'all' || rowStatuses.includes(currentFilter));
            const matchesSearch = !searchQuery || rowText.includes(searchQuery);

            if (matchesFilter && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (tableWrap) {
            tableWrap.style.display = visibleCount === 0 ? 'none' : '';
        }
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        }
    };

    // Sinkronisasi angka badge status di dalam kurung tab berdasarkan baris data tabel
    const syncTabCounts = () => {
        let countAll = tableRows.length;
        let countActive = 0;
        let countDue = 0;
        let countSuspended = 0;
        let countRevoked = 0;

        tableRows.forEach((row) => {
            const statuses = (row.dataset.status || '').split(' ');
            if (statuses.includes('active')) countActive++;
            if (statuses.includes('due')) countDue++;
            if (statuses.includes('suspended')) countSuspended++;
            if (statuses.includes('revoked')) countRevoked++;
        });

        const updateTabBadge = (filterName, count) => {
            const badge = directoryPanel.querySelector(`.filter-tab-btn[data-filter="${filterName}"] .tab-count`);
            if (badge) {
                badge.textContent = `(${count})`;
            }
        };

        updateTabBadge('all', countAll);
        updateTabBadge('active', countActive);
        updateTabBadge('due', countDue);
        updateTabBadge('suspended', countSuspended);
        updateTabBadge('revoked', countRevoked);
    };

    syncTabCounts();

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            tabButtons.forEach((b) => {
                b.classList.remove('is-active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('is-active');
            btn.setAttribute('aria-selected', 'true');
            currentFilter = btn.dataset.filter || 'all';
            applyFilter();
        });
    });

    if (searchInput) {
        const syncClearBtn = () => {
            if (clearBtn) {
                clearBtn.style.display = searchInput.value.length > 0 ? 'inline-flex' : 'none';
            }
        };

        searchInput.addEventListener('input', (e) => {
            searchQuery = (e.target.value || '').trim().toLowerCase();
            syncClearBtn();
            applyFilter();
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchQuery = '';
                syncClearBtn();
                searchInput.focus();
                applyFilter();
            });
        }
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            currentFilter = 'all';
            searchQuery = '';
            if (searchInput) {
                searchInput.value = '';
            }
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }
            tabButtons.forEach((b) => {
                const isAll = (b.dataset.filter === 'all');
                b.classList.toggle('is-active', isAll);
                b.setAttribute('aria-selected', isAll ? 'true' : 'false');
            });
            applyFilter();
        });
    }
};

export const initComplianceMeterInteractions = () => {
    const card = document.querySelector('.compliance-meter-card');
    if (!card || card.dataset.meterInit === 'true') return;
    card.dataset.meterInit = 'true';

    const segments = card.querySelectorAll('.compliance-meter-segment');
    const legendItems = card.querySelectorAll('.compliance-legend-item');

    segments.forEach((seg) => {
        const legendKey = seg.dataset.legend;
        if (!legendKey) return;
        const matchingLegend = card.querySelector(`.compliance-legend-item[data-legend="${legendKey}"]`);
        if (!matchingLegend) return;

        seg.addEventListener('mouseenter', () => {
            matchingLegend.classList.add('is-highlighted');
        });
        seg.addEventListener('mouseleave', () => {
            matchingLegend.classList.remove('is-highlighted');
        });
    });

    legendItems.forEach((item) => {
        const legendKey = item.dataset.legend;
        if (!legendKey) return;
        const matchingSeg = card.querySelector(`.compliance-meter-segment[data-legend="${legendKey}"]`);
        if (!matchingSeg) return;

        item.addEventListener('mouseenter', () => {
            matchingSeg.style.transform = 'scaleY(1.4)';
            matchingSeg.style.borderRadius = '2px';
        });
        item.addEventListener('mouseleave', () => {
            matchingSeg.style.transform = '';
            matchingSeg.style.borderRadius = '';
        });
    });

    // 10. Realtime Character Counters & Hard Limits
    try {
        initCharCounters();
    } catch (err) {
        console.error('Error initializing char counters:', err);
    }
};

window.initPageComponents = initPageComponents;

// ==========================================================================
// Clickable Table Rows (Row/Cell Navigation to Details)
// ==========================================================================
export const initClickableRows = () => {
    document.addEventListener('click', (event) => {
        if (!event.target || typeof event.target.closest !== 'function') return;
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
initTheme();
initPasswordToggles();
initBottomNav();
bindCharCounterEvents();
initSpaRouter(initPageComponents);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageComponents);
} else {
    initPageComponents();
}
