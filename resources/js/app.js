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

document.querySelectorAll('.table-filters').forEach((filter) => {
    const grid = filter.querySelector('.table-filter-grid');

    if (!grid) {
        return;
    }

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
    filter.parentElement.insertBefore(backdrop, filter.nextSibling);
    backdrop.addEventListener('click', () => closeButton.click());

});

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

document.querySelectorAll('.table-wrap').forEach((tableWrap, index) => {
    const table = tableWrap.querySelector('table');
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
        tableWrap.parentElement.classList.toggle('showing-table', view === 'table');
        localStorage.setItem(storageKey, view);
        toggle.querySelectorAll('button').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === view)));
    };

    const toggle = createViewToggle('view-toggle table-view-toggle', 'Tampilan data', [['table', 'Tabel'], ['grid', 'Grid']], initialView, setView);
    tableWrap.parentElement.insertBefore(toggle, tableWrap);
    setView(initialView, toggle);
});

document.querySelectorAll('.table-filters').forEach((filter) => {
    const tableWrap = filter.parentElement.querySelector('.table-wrap');
    const viewToggle = tableWrap?.previousElementSibling;
    const filterToggle = filter.querySelector('.filter-toggle');

    if (!tableWrap || !viewToggle?.classList.contains('table-view-toggle') || !filterToggle) {
        return;
    }

    const controls = document.createElement('div');

    controls.className = 'table-controls';
    filter.parentElement.insertBefore(controls, filter);
    controls.append(filterToggle, viewToggle);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('.filter-drawer.filters-open').forEach((filter) => {
            filter.classList.remove('filters-open');
            filter.querySelector('.filter-toggle')?.setAttribute('aria-expanded', 'false');
        });
        document.body.classList.remove('filter-drawer-open');
    }
});

document.querySelectorAll('.calendar-panel').forEach((calendarPanel) => {
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
    const calendarActions = toolbar.querySelector('.calendar-actions');
    const toolbarControls = document.createElement('div');

    toolbarControls.className = 'calendar-toolbar-controls';
    toolbarControls.append(toggle, calendarActions);
    toolbar.append(toolbarControls);
    setView(initialView, toggle);
});
