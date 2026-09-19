const menuToggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#primary-navigation');
const sidebar = document.querySelector('.sidebar');
const drawerBackdrop = document.querySelector('[data-drawer-close]');
const drawerClose = document.querySelector('.drawer-close');

const setDrawerState = (isOpen) => {
    if (!menuToggle || !sidebar) {
        return;
    }

    menuToggle.setAttribute('aria-expanded', String(isOpen));
    sidebar.classList.toggle('is-open', isOpen);
    document.body.classList.toggle('drawer-open', isOpen);
};

if (menuToggle && navigation && sidebar) {
    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        setDrawerState(!isOpen);
    });

    navigation.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            setDrawerState(false);
        }
    });

    drawerClose?.addEventListener('click', () => setDrawerState(false));
    drawerBackdrop?.addEventListener('click', () => setDrawerState(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setDrawerState(false);
        }
    });
}

const createViewToggle = (className, label, views, activeView, onChange) => {
    const toggle = document.createElement('div');
    toggle.className = className;
    toggle.setAttribute('role', 'group');
    toggle.setAttribute('aria-label', label);

    views.forEach(([value, text]) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.view = value;
        button.textContent = text;
        button.setAttribute('aria-pressed', String(value === activeView));
        button.addEventListener('click', () => onChange(value, toggle));
        toggle.append(button);
    });

    return toggle;
};

// ==========================================================================
// Page Components Initializer (Idempotent for Initial Load & SPA Transitions)
// ==========================================================================
const initPageComponents = () => {
    // 1. Table view mode toggles (Table vs Grid)
    document.querySelectorAll('.table-wrap').forEach((tableWrap, index) => {
        if (tableWrap.dataset.initialized === 'true') return;
        tableWrap.dataset.initialized = 'true';

        const table = tableWrap.querySelector('table');
        if (!table) return;

        const headers = [...table.querySelectorAll('thead th')].map((header) => header.textContent.trim());
        const storageKey = `simasadi-table-view-${window.location.pathname}-${index}`;
        const isMobile = window.matchMedia('(max-width: 600px)').matches;
        const savedView = localStorage.getItem(storageKey);
        const initialView = isMobile ? (savedView || 'grid') : 'table';

        table.querySelectorAll('tbody tr').forEach((row) => {
            row.querySelectorAll('td').forEach((cell, cellIndex) => {
                cell.dataset.label = headers[cellIndex] || '';
            });
        });

        const setView = (view, toggle) => {
            tableWrap.classList.toggle('table-mode-grid', view === 'grid');
            tableWrap.classList.toggle('table-mode-table', view === 'table');
            tableWrap.parentElement?.classList.toggle('showing-table', view === 'table');
            localStorage.setItem(storageKey, view);
            toggle.querySelectorAll('button').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === view)));
        };

        const toggle = createViewToggle('view-toggle table-view-toggle', 'Tampilan data', [['table', 'Tabel'], ['grid', 'Grid']], initialView, setView);
        tableWrap.parentElement?.insertBefore(toggle, tableWrap);
        setView(initialView, toggle);
    });

    // 2. Table filters & filter drawer
    document.querySelectorAll('.table-filters').forEach((filter) => {
        if (filter.dataset.initialized === 'true') return;
        filter.dataset.initialized = 'true';

        const grid = filter.querySelector('.table-filter-grid');
        if (!grid) return;

        const toggle = document.createElement('button');
        const hasValues = [...grid.querySelectorAll('input, select')].some((field) => field.value);

        toggle.className = 'filter-toggle';
        toggle.type = 'button';
        toggle.setAttribute('aria-expanded', String(hasValues));
        toggle.innerHTML = '<span class="filter-toggle-icon" aria-hidden="true"></span><span>Filter data</span>';
        filter.classList.toggle('filters-open', hasValues);
        filter.classList.add('filter-drawer');
        filter.insertBefore(toggle, grid);

        toggle.addEventListener('click', () => {
            const isOpen = filter.classList.toggle('filters-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
            document.body.classList.toggle('filter-drawer-open', isOpen);
        });

        const closeButton = document.createElement('button');
        closeButton.className = 'filter-drawer-close';
        closeButton.type = 'button';
        closeButton.setAttribute('aria-label', 'Tutup filter');
        closeButton.innerHTML = '<span aria-hidden="true">&times;</span>';
        filter.prepend(closeButton);
        closeButton.addEventListener('click', () => {
            filter.classList.remove('filters-open');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('filter-drawer-open');
        });

        const backdrop = document.createElement('div');
        backdrop.className = 'filter-drawer-backdrop';
        filter.parentElement?.insertBefore(backdrop, filter.nextSibling);
        backdrop.addEventListener('click', () => closeButton.click());

        const tableWrap = filter.parentElement?.querySelector('.table-wrap');
        const viewToggle = tableWrap?.previousElementSibling;
        if (tableWrap && viewToggle?.classList.contains('table-view-toggle')) {
            const controls = document.createElement('div');
            controls.className = 'table-controls';
            filter.parentElement.insertBefore(controls, filter);
            controls.append(toggle, viewToggle);
        }
    });

    // 3. Calendar View Toggle
    document.querySelectorAll('.calendar-panel').forEach((calendarPanel) => {
        if (calendarPanel.dataset.initialized === 'true') return;
        calendarPanel.dataset.initialized = 'true';

        const storageKey = 'simasadi-calendar-view';
        const savedView = localStorage.getItem(storageKey);
        const initialView = savedView || (window.matchMedia('(max-width: 600px)').matches ? 'agenda' : 'calendar');
        const setView = (view, toggle) => {
            calendarPanel.classList.toggle('calendar-mode-calendar', view === 'calendar');
            calendarPanel.classList.toggle('calendar-mode-agenda', view === 'agenda');
            localStorage.setItem(storageKey, view);
            toggle.querySelectorAll('button').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === view)));
        };
        const toggle = createViewToggle('view-toggle calendar-view-toggle', 'Tampilan kalender', [['calendar', 'Kalender'], ['agenda', 'Agenda']], initialView, setView);

        const toolbar = calendarPanel.querySelector('.calendar-toolbar');
        const calendarActions = toolbar?.querySelector('.calendar-actions');
        if (toolbar && calendarActions) {
            const toolbarControls = document.createElement('div');
            toolbarControls.className = 'calendar-toolbar-controls';
            toolbarControls.append(toggle, calendarActions);
            toolbar.append(toolbarControls);
            setView(initialView, toggle);
        }
    });
};

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('.filter-drawer.filters-open').forEach((filter) => {
            filter.classList.remove('filters-open');
            filter.querySelector('.filter-toggle')?.setAttribute('aria-expanded', 'false');
        });
        document.body.classList.remove('filter-drawer-open');
    }
});

// Run initial component initialization
initPageComponents();

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

const navigateTo = async (url, pushState = true) => {
    const pageWrap = document.querySelector('#page-content-wrapper') || document.querySelector('.page-wrap');
    if (!pageWrap) {
        window.location.href = url;
        return;
    }

    if (isNavigating) return;
    isNavigating = true;

    const { type, title } = getPageTypeAndTitle(url);

    // 1. Immediately render matching skeleton layout
    pageWrap.innerHTML = renderSkeletonForType(type);

    // 2. Immediately update topbar title & active nav link for instant feedback
    const contextTitle = document.querySelector('.context-title');
    if (contextTitle && title) {
        contextTitle.textContent = title;
    }

    const navLinks = document.querySelectorAll('#primary-navigation a');
    const targetPath = new URL(url, window.location.origin).pathname;
    navLinks.forEach((link) => {
        const linkPath = new URL(link.href, window.location.origin).pathname;
        const isActive = (linkPath === '/' || linkPath === '/dashboard')
            ? (targetPath === '/' || targetPath === '/dashboard')
            : (targetPath.startsWith(linkPath) && linkPath !== '/');
        link.classList.toggle('active', isActive);
    });

    // Close mobile drawer if open and scroll to top
    setDrawerState(false);
    window.scrollTo({ top: 0, behavior: 'instant' });

    // 3. Minimum delay for a pleasant, visible skeleton transition (200ms)
    const minWait = new Promise((resolve) => setTimeout(resolve, 200));

    try {
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
        await minWait;

        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');

        const newContent = doc.querySelector('#page-content-wrapper') || doc.querySelector('.page-wrap');
        if (!newContent) {
            window.location.href = url;
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

        // Swap content into page wrapper with gentle fade
        pageWrap.innerHTML = newContent.innerHTML;

        // Re-initialize all interactive components (tables, calendars, filters)
        initPageComponents();

        if (pushState) {
            window.history.pushState({ url }, '', url);
        }
    } catch (err) {
        console.error('Page transition error, falling back:', err);
        window.location.href = url;
    } finally {
        isNavigating = false;
    }
};

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
    navigateTo(window.location.href, false);
});

// ==========================================================================
// Sidebar Collapse & Expand State Controller (Single Button in Navbar)
// ==========================================================================
const initSidebarCollapse = () => {
    const isCollapsed = localStorage.getItem('simasadi_sidebar_collapsed') === 'true';
    const appShell = document.querySelector('.app-shell');
    if (appShell) {
        appShell.classList.toggle('sidebar-collapsed', isCollapsed);
    }
    document.documentElement.classList.toggle('sidebar-is-collapsed', isCollapsed);

    const toggleBtn = document.querySelector('.navbar-sidebar-toggle');
    const label = isCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar';
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-label', label);
        toggleBtn.title = label;
    }
};

const toggleSidebarState = (explicitState) => {
    const appShell = document.querySelector('.app-shell');
    const html = document.documentElement;
    const isCurrentlyCollapsed = appShell?.classList.contains('sidebar-collapsed') || html.classList.contains('sidebar-is-collapsed');
    const nextState = typeof explicitState === 'boolean' ? explicitState : !isCurrentlyCollapsed;

    localStorage.setItem('simasadi_sidebar_collapsed', String(nextState));
    if (appShell) {
        appShell.classList.toggle('sidebar-collapsed', nextState);
    }
    html.classList.toggle('sidebar-is-collapsed', nextState);

    const toggleBtn = document.querySelector('.navbar-sidebar-toggle');
    const label = nextState ? 'Perluas sidebar' : 'Ciutkan sidebar';
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-label', label);
        toggleBtn.title = label;
    }
};

// Initialize on page load
initSidebarCollapse();

// Listen to navbar sidebar collapse/expand toggle button
document.addEventListener('click', (event) => {
    const toggleBtn = event.target.closest('.navbar-sidebar-toggle');
    if (toggleBtn) {
        event.preventDefault();
        toggleSidebarState();
        return;
    }

    // Clicking the "K" brand mark when collapsed also expands it
    const brandMark = event.target.closest('.brand-mark');
    if (brandMark && (document.documentElement.classList.contains('sidebar-is-collapsed') || document.querySelector('.app-shell')?.classList.contains('sidebar-collapsed'))) {
        toggleSidebarState(false);
    }
});

// ==========================================================================
// Smooth Login Submitting Motion (With Active Moving Spinner)
// ==========================================================================
document.addEventListener('submit', (event) => {
    const form = event.target.closest('#login-form');
    if (!form) return;

    if (form.dataset.submitting === 'true') {
        return; // Native submission in progress
    }

    if (!form.checkValidity()) {
        return; // Allow native HTML5 validation tooltips to show
    }

    event.preventDefault();
    form.dataset.submitting = 'true';

    const submitBtn = form.querySelector('#login-submit-btn') || form.querySelector('button[type="submit"]');
    const loginCard = document.querySelector('#login-card');

    if (submitBtn) {
        submitBtn.classList.add('is-loading');
    }
    if (loginCard) {
        loginCard.classList.add('is-submitting');
    }

    // Allow the spinner to rotate and user to feel smooth feedback before unload
    setTimeout(() => {
        form.submit();
    }, 480);
});

// ==========================================================================
// Smooth Logout Curtain Exit Transition (With Active Moving Spinner)
// ==========================================================================
document.addEventListener('submit', (event) => {
    const form = event.target.closest('#logout-form, .logout-form');
    if (!form) return;

    if (form.dataset.submitting === 'true') {
        return; // Native submission in progress
    }

    event.preventDefault();
    form.dataset.submitting = 'true';

    const curtain = document.querySelector('#logout-curtain');
    if (curtain) {
        curtain.classList.add('is-active');
    }

    // Allow curtain to fully fade in and spinner to rotate smoothly before unload
    setTimeout(() => {
        form.submit();
    }, 550);
});


