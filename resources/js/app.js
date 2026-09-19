import Swal from 'sweetalert2';
window.Swal = Swal;

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
// Custom Dropdown Selection Component Logic
// ==========================================================================
const closeAllCustomSelects = (exceptWrapper = null) => {
    document.querySelectorAll('.custom-select-wrapper.is-open').forEach((w) => {
        if (w !== exceptWrapper) {
            w.classList.remove('is-open', 'dropup');
            w.querySelector('.custom-select-trigger')?.setAttribute('aria-expanded', 'false');
        }
    });
    document.querySelectorAll('.has-open-select').forEach((el) => {
        if (!el.querySelector('.custom-select-wrapper.is-open')) {
            el.classList.remove('has-open-select');
        }
    });
};

let globalSelectListenersAdded = false;
const setupGlobalSelectListeners = () => {
    if (globalSelectListenersAdded) return;
    globalSelectListenersAdded = true;

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.custom-select-wrapper')) {
            closeAllCustomSelects();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllCustomSelects();
        }
    });

    window.addEventListener('resize', () => {
        closeAllCustomSelects();
    });
};

const getStatusDotHtml = (val) => {
    if (!val) return '';
    const v = String(val).toUpperCase();
    const map = {
        'ACTIVE': 'dot-active',
        'SUCCESS': 'dot-success',
        'COMPLETED': 'dot-completed',
        'RESOLVED': 'dot-resolved',
        'APPROVED': 'dot-active',
        'IN_PROGRESS': 'dot-in_progress',
        'SCHEDULED': 'dot-in_progress',
        'UNDER_REVIEW': 'dot-in_progress',
        'OPEN': 'dot-open',
        'PLANNED': 'dot-planned',
        'SUBMITTED': 'dot-submitted',
        'NEED_REVISION': 'dot-need_revision',
        'INACTIVE': 'dot-inactive',
        'CANCELLED': 'dot-cancelled',
        'FAILED': 'dot-failed',
        'REJECTED': 'dot-rejected',
        'NOT_STARTED': 'dot-not_started',
        'HIGH': 'dot-high',
        'MEDIUM': 'dot-medium',
        'LOW': 'dot-low',
    };
    const dotClass = map[v];
    return dotClass ? `<span class="status-dot ${dotClass}" aria-hidden="true"></span>` : '';
};

const createCustomSelect = (select) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'custom-select-wrapper';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    select.classList.add('custom-select-native');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'custom-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const valueSpan = document.createElement('span');
    valueSpan.className = 'custom-select-value';

    const arrowSpan = document.createElement('span');
    arrowSpan.className = 'custom-select-arrow';
    arrowSpan.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

    trigger.append(valueSpan, arrowSpan);
    wrapper.appendChild(trigger);

    const menu = document.createElement('div');
    menu.className = 'custom-select-menu';
    menu.setAttribute('role', 'listbox');

    const optionsCount = select.options.length;
    let searchInput = null;
    let emptyNotice = null;

    if (optionsCount > 6) {
        const searchWrap = document.createElement('div');
        searchWrap.className = 'custom-select-search-wrap';
        searchWrap.innerHTML = '<svg class="custom-select-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>';

        searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'custom-select-search-input';
        searchInput.placeholder = 'Cari pilihan...';
        searchInput.autocomplete = 'off';

        searchWrap.appendChild(searchInput);
        menu.appendChild(searchWrap);
    }

    const optionsList = document.createElement('div');
    optionsList.className = 'custom-select-options';
    menu.appendChild(optionsList);

    emptyNotice = document.createElement('div');
    emptyNotice.className = 'custom-select-empty';
    emptyNotice.textContent = 'Tidak ada hasil ditemukan';
    emptyNotice.style.display = 'none';
    menu.appendChild(emptyNotice);

    wrapper.appendChild(menu);

    const checkIconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>';
    const optionElements = [];

    const buildOptions = () => {
        optionsList.innerHTML = '';
        optionElements.length = 0;

        Array.from(select.options).forEach((opt, idx) => {
            const optionEl = document.createElement('div');
            optionEl.className = 'custom-select-option';
            optionEl.setAttribute('role', 'option');
            optionEl.dataset.value = opt.value;
            optionEl.dataset.index = String(idx);

            const isSelected = opt.selected;
            optionEl.setAttribute('aria-selected', String(isSelected));
            if (isSelected) optionEl.classList.add('is-selected');

            const dotHtml = getStatusDotHtml(opt.value);
            const labelText = opt.textContent.trim();

            optionEl.innerHTML = `
                <span class="custom-select-option-content">
                    ${dotHtml}
                    <span class="custom-select-option-text">${labelText}</span>
                </span>
                <span class="custom-select-check">${checkIconSvg}</span>
            `;

            optionEl.addEventListener('click', (e) => {
                e.stopPropagation();
                selectOption(opt.value);
            });

            optionsList.appendChild(optionEl);
            optionElements.push(optionEl);
        });
    };

    const syncFromNative = () => {
        const selectedOpt = select.options[select.selectedIndex] || select.options[0];
        if (selectedOpt) {
            const dotHtml = getStatusDotHtml(selectedOpt.value);
            const labelText = selectedOpt.textContent.trim();
            valueSpan.innerHTML = `${dotHtml}<span>${labelText}</span>`;
            valueSpan.classList.toggle('is-placeholder', !selectedOpt.value && select.required);
        } else {
            valueSpan.innerHTML = '<span>Pilih...</span>';
            valueSpan.classList.add('is-placeholder');
        }

        optionElements.forEach((el) => {
            const isSelected = el.dataset.value === select.value;
            el.classList.toggle('is-selected', isSelected);
            el.setAttribute('aria-selected', String(isSelected));
        });
    };

    const selectOption = (val) => {
        if (select.value !== val) {
            select.value = val;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            select.dispatchEvent(new Event('input', { bubbles: true }));
        }
        wrapper.classList.remove('is-invalid');
        syncFromNative();
        closeMenu();
        trigger.focus();
    };

    const filterOptions = (term) => {
        const query = term.toLowerCase().trim();
        let visibleCount = 0;
        optionElements.forEach((el) => {
            const text = el.querySelector('.custom-select-option-text')?.textContent.toLowerCase() || '';
            const matches = !query || text.includes(query);
            el.classList.toggle('is-hidden', !matches);
            if (matches) visibleCount++;
        });
        if (emptyNotice) {
            emptyNotice.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    };

    const openMenu = () => {
        closeAllCustomSelects(wrapper);

        const rect = trigger.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;
        if (spaceBelow < 260 && spaceAbove > spaceBelow) {
            wrapper.classList.add('dropup');
        } else {
            wrapper.classList.remove('dropup');
        }

        wrapper.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');

        const parentContainer = wrapper.closest('.panel, .card, section, .detail-grid > *, .content-grid > *');
        if (parentContainer) {
            parentContainer.classList.add('has-open-select');
        }
        const parentForm = wrapper.closest('.inline-form, form');
        if (parentForm) {
            parentForm.classList.add('has-open-select');
        }

        if (searchInput) {
            searchInput.value = '';
            filterOptions('');
            setTimeout(() => searchInput.focus(), 30);
        } else {
            const selectedOption = optionsList.querySelector('.custom-select-option.is-selected');
            if (selectedOption) {
                selectedOption.scrollIntoView({ block: 'nearest' });
            }
        }
    };

    const closeMenu = () => {
        wrapper.classList.remove('is-open', 'dropup');
        trigger.setAttribute('aria-expanded', 'false');
        optionElements.forEach((el) => el.classList.remove('is-focused'));

        const parentContainer = wrapper.closest('.panel, .card, section, .detail-grid > *, .content-grid > *');
        if (parentContainer && !parentContainer.querySelector('.custom-select-wrapper.is-open')) {
            parentContainer.classList.remove('has-open-select');
        }
        const parentForm = wrapper.closest('.inline-form, form');
        if (parentForm && !parentForm.querySelector('.custom-select-wrapper.is-open')) {
            parentForm.classList.remove('has-open-select');
        }
    };

    const toggleMenu = () => {
        if (wrapper.classList.contains('is-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    };

    const navigateOptions = (direction) => {
        const visible = optionElements.filter((el) => !el.classList.contains('is-hidden'));
        if (!visible.length) return;
        const currentIndex = visible.findIndex((el) => el.classList.contains('is-focused') || el.classList.contains('is-selected'));
        let nextIndex = currentIndex + direction;
        if (nextIndex < 0) nextIndex = visible.length - 1;
        if (nextIndex >= visible.length) nextIndex = 0;

        visible.forEach((el) => el.classList.remove('is-focused'));
        const nextEl = visible[nextIndex];
        nextEl.classList.add('is-focused');
        nextEl.scrollIntoView({ block: 'nearest' });
    };

    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu();
    });

    trigger.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!wrapper.classList.contains('is-open')) {
                openMenu();
            } else {
                navigateOptions(e.key === 'ArrowUp' ? -1 : 1);
            }
        } else if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            if (!wrapper.classList.contains('is-open')) {
                openMenu();
            } else {
                const focused = optionElements.find((el) => el.classList.contains('is-focused'));
                if (focused) {
                    selectOption(focused.dataset.value);
                } else {
                    closeMenu();
                }
            }
        } else if (e.key === 'Escape') {
            closeMenu();
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterOptions(e.target.value);
        });
        searchInput.addEventListener('click', (e) => e.stopPropagation());
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeMenu();
                trigger.focus();
            } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                navigateOptions(e.key === 'ArrowUp' ? -1 : 1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const focused = optionElements.find((el) => el.classList.contains('is-focused')) ||
                                optionElements.find((el) => !el.classList.contains('is-hidden'));
                if (focused) {
                    selectOption(focused.dataset.value);
                }
            }
        });
    }

    menu.addEventListener('click', (e) => e.stopPropagation());

    select.addEventListener('change', () => syncFromNative());
    select.addEventListener('invalid', () => {
        wrapper.classList.add('is-invalid');
        trigger.focus();
    });
    select.form?.addEventListener('reset', () => {
        setTimeout(syncFromNative, 10);
    });

    buildOptions();
    syncFromNative();
};

const initCustomSelects = (container = document) => {
    setupGlobalSelectListeners();
    const selects = container.querySelectorAll('select:not([data-custom-select-init])');
    selects.forEach((select) => {
        if (select.dataset.noCustom !== undefined) return;
        select.dataset.customSelectInit = 'true';
        createCustomSelect(select);
    });
};

// ==========================================================================
// Data Table Sorting & Toolbar Controls ("Show [ 10 ] entries")
// ==========================================================================
const parseCellValue = (rawText) => {
    if (!rawText) return '';
    const text = rawText.trim();
    if (!text || text === '-' || text.toLowerCase() === 'tanpa target' || text.toLowerCase() === 'belum diisi' || text.toLowerCase() === 'belum dicatat') {
        return '';
    }

    // 1. Currency & Numbers (e.g., "Rp. 5.000.000", "Rp 14.000.000", "11992", "42")
    const cleanedNum = text.replace(/^rp\.?\s*/i, '').replace(/\s+/g, '');
    if (/^-?\d{1,3}(\.\d{3})+(,\d+)?$/.test(cleanedNum)) {
        const normalized = cleanedNum.replace(/\./g, '').replace(',', '.');
        const num = parseFloat(normalized);
        if (!isNaN(num)) return num;
    }
    if (/^-?\d+(\.\d+)?$/.test(cleanedNum)) {
        const num = parseFloat(cleanedNum);
        if (!isNaN(num)) return num;
    }

    // 2. Dates (ISO or standard timestamps)
    if (/^\d{4}-\d{2}-\d{2}/.test(text)) {
        const d = Date.parse(text);
        if (!isNaN(d)) return d;
    }

    // Indonesian / English dates: "DD Mmm YYYY" or "DD Mmm YYYY, HH:mm"
    const months = {
        jan: 0, feb: 1, mar: 2, apr: 3, mei: 4, may: 4, jun: 5,
        jul: 6, agu: 7, aug: 7, sep: 8, okt: 9, oct: 9, nov: 10, des: 11, dec: 11
    };
    const dateMatch = text.match(/^(\d{1,2})\s+([a-zA-Z]{3,})\s+(\d{4})(?:[,\s]+(\d{1,2}):(\d{2}))?/i);
    if (dateMatch) {
        const day = parseInt(dateMatch[1], 10);
        const monKey = dateMatch[2].toLowerCase().substring(0, 3);
        const month = months[monKey];
        const year = parseInt(dateMatch[3], 10);
        const hours = dateMatch[4] ? parseInt(dateMatch[4], 10) : 0;
        const mins = dateMatch[5] ? parseInt(dateMatch[5], 10) : 0;
        if (month !== undefined) {
            return new Date(year, month, day, hours, mins).getTime();
        }
    }

    return text.toLowerCase();
};

const sortTableByColumn = (table, colIndex, targetTh) => {
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const currentSort = targetTh.getAttribute('aria-sort') || 'none';
    const nextSort = currentSort === 'ascending' ? 'descending' : 'ascending';

    // Update all sortable headers in this table
    table.querySelectorAll('thead th.is-sortable').forEach((th) => {
        th.setAttribute('aria-sort', 'none');
    });
    targetTh.setAttribute('aria-sort', nextSort);

    const rows = Array.from(tbody.querySelectorAll('tr'));
    if (!rows.length) return;

    rows.sort((rowA, rowB) => {
        const cellA = rowA.children[colIndex];
        const cellB = rowB.children[colIndex];
        if (!cellA || !cellB) return 0;

        const textA = (cellA.querySelector('strong')?.textContent || cellA.textContent).trim();
        const textB = (cellB.querySelector('strong')?.textContent || cellB.textContent).trim();

        const valA = parseCellValue(textA);
        const valB = parseCellValue(textB);

        let result = 0;
        if (typeof valA === 'number' && typeof valB === 'number') {
            result = valA - valB;
        } else {
            result = String(valA).localeCompare(String(valB), 'id', { numeric: true, sensitivity: 'base' });
        }

        return nextSort === 'ascending' ? result : -result;
    });

    // Reorder rows in DOM
    rows.forEach((row) => tbody.appendChild(row));
};

const initSortableHeaders = (table) => {
    const theadThs = table.querySelectorAll('thead th');
    if (!theadThs.length) return;

    const sortSvg = `
        <span class="table-sort-icon" aria-hidden="true">
            <svg class="sort-arrows-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path class="sort-arrow-up" d="M7 20V4m0 0l-3 3m3-3l3 3" />
                <path class="sort-arrow-down" d="M17 4v16m0 0l-3-3m3 3l3-3" />
            </svg>
        </span>
    `;

    theadThs.forEach((th, colIndex) => {
        if (th.dataset.sortInit === 'true') return;
        th.dataset.sortInit = 'true';

        const text = th.textContent.trim();
        const isActionCol = !text ||
            th.querySelector('.sr-only') ||
            /^(aksi|action|buka|detail)$/i.test(text);

        if (isActionCol) return;

        th.classList.add('is-sortable');
        th.setAttribute('tabindex', '0');
        th.setAttribute('role', 'columnheader');
        th.setAttribute('aria-sort', 'none');

        const label = document.createElement('span');
        label.className = 'th-label';
        while (th.firstChild) {
            label.appendChild(th.firstChild);
        }

        const wrap = document.createElement('div');
        wrap.className = 'th-content';
        wrap.appendChild(label);
        wrap.insertAdjacentHTML('beforeend', sortSvg);

        th.appendChild(wrap);

        th.addEventListener('click', () => {
            sortTableByColumn(table, colIndex, th);
        });

        th.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                sortTableByColumn(table, colIndex, th);
            }
        });
    });
};

const initTableToolbar = (tableWrap) => {
    if (tableWrap.previousElementSibling?.classList.contains('table-toolbar')) return;

    const url = new URL(window.location.href);
    const currentPerPage = url.searchParams.get('per_page') || '10';

    const toolbar = document.createElement('div');
    toolbar.className = 'table-toolbar';

    const lengthControl = document.createElement('div');
    lengthControl.className = 'table-length-control';
    lengthControl.innerHTML = `
        <span>Show</span>
        <select class="table-length-select" aria-label="Tampilkan baris per halaman">
            <option value="10" ${currentPerPage === '10' ? 'selected' : ''}>10</option>
            <option value="25" ${currentPerPage === '25' ? 'selected' : ''}>25</option>
            <option value="50" ${currentPerPage === '50' ? 'selected' : ''}>50</option>
            <option value="100" ${currentPerPage === '100' ? 'selected' : ''}>100</option>
        </select>
        <span>entries</span>
    `;

    const select = lengthControl.querySelector('select');
    select.addEventListener('change', () => {
        const val = select.value;

        // Ensure active table filters maintain the chosen per_page value
        const filterForm = tableWrap.parentElement?.querySelector('.table-filters') || document.querySelector('.table-filters');
        if (filterForm) {
            let hiddenPerPage = filterForm.querySelector('input[name="per_page"]');
            if (!hiddenPerPage) {
                hiddenPerPage = document.createElement('input');
                hiddenPerPage.type = 'hidden';
                hiddenPerPage.name = 'per_page';
                filterForm.appendChild(hiddenPerPage);
            }
            hiddenPerPage.value = val;
        }

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('per_page', val);
        newUrl.searchParams.delete('page');
        window.location.href = newUrl.toString();
    });

    toolbar.appendChild(lengthControl);
    tableWrap.parentElement?.insertBefore(toolbar, tableWrap);
};

// ==========================================================================
// Page Components Initializer (Idempotent for Initial Load & SPA Transitions)
// ==========================================================================
const initPageComponents = () => {
    // 1. Table view mode toggles, Table sorting, and Table length controls
    document.querySelectorAll('.table-wrap').forEach((tableWrap, index) => {
        const table = tableWrap.querySelector('table');
        if (!table) return;

        // Initialize sortable column headers
        initSortableHeaders(table);

        // Initialize "Show [ 10 ] entries" toolbar
        initTableToolbar(tableWrap);

        if (tableWrap.dataset.initialized === 'true') return;
        tableWrap.dataset.initialized = 'true';

        const headers = [...table.querySelectorAll('thead th')].map((header) => {
            return header.querySelector('.th-label')?.textContent.trim() || header.textContent.trim();
        });
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
        const toolbar = tableWrap.previousElementSibling;
        if (toolbar?.classList.contains('table-toolbar')) {
            toolbar.appendChild(toggle);
        } else {
            tableWrap.parentElement?.insertBefore(toggle, tableWrap);
        }
        setView(initialView, toggle);
    });

    // 2. Table filters & filter drawer
    document.querySelectorAll('.table-filters').forEach((filter) => {
        if (filter.dataset.initialized === 'true') return;
        filter.dataset.initialized = 'true';

        const grid = filter.querySelector('.table-filter-grid');
        if (!grid) return;

        // Create placeholder in panel so we can restore on desktop
        const placeholder = document.createElement('div');
        placeholder.className = 'table-filters-placeholder';
        placeholder.style.display = 'none';
        filter.parentElement?.insertBefore(placeholder, filter);

        const toggle = document.createElement('button');
        toggle.className = 'filter-toggle';
        toggle.type = 'button';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<span class="filter-toggle-icon" aria-hidden="true"></span><span>Filter data</span>';

        const closeButton = document.createElement('button');
        closeButton.className = 'filter-drawer-close';
        closeButton.type = 'button';
        closeButton.setAttribute('aria-label', 'Tutup filter');
        closeButton.innerHTML = '<span aria-hidden="true">&times;</span>';
        filter.prepend(closeButton);

        const backdrop = document.createElement('div');
        backdrop.className = 'filter-drawer-backdrop';

        filter.classList.add('filter-drawer');

        const closeDrawer = () => {
            filter.classList.remove('filters-open');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('filter-drawer-open');
        };

        const openDrawer = () => {
            filter.classList.add('filters-open');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('filter-drawer-open');
        };

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (filter.classList.contains('filters-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        closeButton.addEventListener('click', closeDrawer);
        backdrop.addEventListener('click', closeDrawer);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && filter.classList.contains('filters-open')) {
                closeDrawer();
            }
        });

        // Ensure drawer and backdrop are mounted directly to document.body on mobile screens
        // so they are truly outside the table card / panel container (seperti side drawer sidebar)
        const syncDrawerMount = () => {
            const isMobile = window.matchMedia('(max-width: 900px)').matches;
            if (isMobile) {
                if (filter.parentElement !== document.body) {
                    document.body.appendChild(backdrop);
                    document.body.appendChild(filter);
                }
            } else {
                if (filter.parentElement === document.body && placeholder.parentElement) {
                    placeholder.parentElement.insertBefore(filter, placeholder);
                    closeDrawer();
                }
            }
        };

        syncDrawerMount();
        window.addEventListener('resize', syncDrawerMount);

        // Calculate and display badges for active filters
        const updateFilterBadges = () => {
            const activeFilters = [];
            grid.querySelectorAll('input, select').forEach((field) => {
                if (field.type === 'hidden' || field.name === 'per_page' || field.name === '_token') return;
                const val = field.value ? field.value.trim() : '';
                const parentLabel = field.closest('label');

                // Remove previous badge if any
                parentLabel?.querySelector('.filter-field-badge')?.remove();

                if (val) {
                    let fieldTitle = '';
                    if (parentLabel) {
                        fieldTitle = Array.from(parentLabel.childNodes)
                            .filter(n => n.nodeType === Node.TEXT_NODE)
                            .map(n => n.textContent.trim())
                            .filter(Boolean)
                            .join(' ');
                    }
                    if (!fieldTitle) fieldTitle = field.name;
                    fieldTitle = fieldTitle.replace(/^(Cari\s+)/i, '').trim() || fieldTitle;

                    let displayVal = val;
                    if (field.tagName === 'SELECT') {
                        const opt = field.options[field.selectedIndex];
                        if (opt && opt.value) displayVal = opt.textContent.trim();
                    } else if (field.type === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
                        const parts = val.split('-');
                        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                        if (!isNaN(d.getTime())) {
                            displayVal = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                        }
                    }

                    activeFilters.push({
                        name: field.name,
                        title: fieldTitle,
                        displayValue: displayVal,
                        field: field
                    });
                }
            });

            // Update badge on "Filter data" button
            let countBadge = toggle.querySelector('.filter-count-badge');
            if (activeFilters.length > 0) {
                if (!countBadge) {
                    countBadge = document.createElement('span');
                    countBadge.className = 'filter-count-badge';
                    toggle.appendChild(countBadge);
                }
                countBadge.textContent = String(activeFilters.length);
                toggle.classList.add('has-active-filters');
            } else {
                countBadge?.remove();
                toggle.classList.remove('has-active-filters');
            }

            // Render active filter chips bar (matches user reference image)
            const parentPanel = placeholder.closest('.panel') || filter.closest('.panel');
            if (parentPanel) {
                let bar = parentPanel.querySelector('.active-filters-bar');
                if (activeFilters.length > 0) {
                    if (!bar) {
                        bar = document.createElement('div');
                        bar.className = 'active-filters-bar';
                        const tableTarget = parentPanel.querySelector('.table-wrap, .empty');
                        if (tableTarget) {
                            parentPanel.insertBefore(bar, tableTarget);
                        } else {
                            parentPanel.appendChild(bar);
                        }
                    }

                    const resetUrl = filter.getAttribute('action') || window.location.pathname;

                    bar.innerHTML = `
                        <span class="active-filters-heading">FILTER AKTIF</span>
                        <div class="active-filters-chips">
                            ${activeFilters.map((af) => `
                                <span class="filter-chip" data-field="${af.name}">
                                    <span class="filter-chip-text">${af.title}: ${af.displayValue}</span>
                                    <button type="button" class="filter-chip-remove" aria-label="Hapus filter ${af.title}">&times;</button>
                                </span>
                            `).join('')}
                            <a href="${resetUrl}" class="filter-reset-link">Reset Filter</a>
                        </div>
                    `;

                    bar.querySelectorAll('.filter-chip-remove').forEach((btn) => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const chip = btn.closest('.filter-chip');
                            const fieldName = chip?.dataset.field;
                            const targetField = filter.querySelector(`[name="${fieldName}"]`);
                            if (targetField) {
                                targetField.value = '';
                                if (targetField.tagName === 'SELECT') {
                                    targetField.selectedIndex = 0;
                                }
                                targetField.dispatchEvent(new Event('change', { bubbles: true }));
                                filter.submit();
                            }
                        });
                    });
                } else {
                    bar?.remove();
                }
            }
        };

        const parentPanel = placeholder.closest('.panel') || filter.closest('.panel');
        const tableWrap = parentPanel?.querySelector('.table-wrap');
        if (tableWrap && !parentPanel.querySelector('.table-controls')) {
            const controls = document.createElement('div');
            controls.className = 'table-controls';
            parentPanel.insertBefore(controls, placeholder);
            controls.append(toggle);
        }

        updateFilterBadges();
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

    // 4. Custom Select Dropdowns
    initCustomSelects();

    // 5. Flash Notifications via SweetAlert2
    handleFlashNotifications();
};

function handleFlashNotifications() {
    // 1. Flash Success Notification
    const successEl = document.getElementById('flash-success-data');
    if (successEl) {
        const message = successEl.dataset.message;
        successEl.remove();
        if (message) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                confirmButtonColor: '#5645d4',
                confirmButtonText: 'Selesai',
                timer: 3500,
                timerProgressBar: true,
                customClass: {
                    popup: 'simasadi-swal-popup',
                    confirmButton: 'simasadi-swal-btn',
                    title: 'simasadi-swal-title',
                    htmlContainer: 'simasadi-swal-text'
                }
            });
        }
    }

    // 2. Flash Error Notification
    const errorEl = document.getElementById('flash-error-data');
    if (errorEl) {
        const message = errorEl.dataset.message;
        errorEl.remove();
        if (message) {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: message,
                confirmButtonColor: '#5645d4',
                confirmButtonText: 'Tutup',
                customClass: {
                    popup: 'simasadi-swal-popup',
                    confirmButton: 'simasadi-swal-btn',
                    title: 'simasadi-swal-title',
                    htmlContainer: 'simasadi-swal-text'
                }
            });
        }
    }

    // 3. Validation Errors Notification
    const errorsEl = document.getElementById('flash-errors-data');
    if (errorsEl) {
        const title = errorsEl.dataset.title || 'Periksa Kembali Input';
        let errors = [];
        try {
            errors = JSON.parse(errorsEl.dataset.errors || '[]');
        } catch (e) {
            errors = [errorsEl.dataset.errors];
        }
        errorsEl.remove();
        if (errors.length > 0) {
            const listHtml = '<ul style="text-align: left; margin: 10px 0 0 0; padding-left: 20px; font-size: 13.5px; line-height: 1.6;">' +
                errors.map(err => '<li>' + err + '</li>').join('') +
                '</ul>';

            Swal.fire({
                icon: 'error',
                title: title,
                html: listHtml,
                confirmButtonColor: '#5645d4',
                confirmButtonText: 'Mengerti',
                customClass: {
                    popup: 'simasadi-swal-popup',
                    confirmButton: 'simasadi-swal-btn',
                    title: 'simasadi-swal-title',
                    htmlContainer: 'simasadi-swal-text'
                }
            });
        }
    }
}

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
// Smooth Logout Curtain Exit Transition with SweetAlert2 Confirmation
// ==========================================================================
document.addEventListener('submit', (event) => {
    const form = event.target.closest('#logout-form, .logout-form');
    if (!form) return;

    if (form.dataset.confirmed === 'true') {
        return; // Native submission in progress after confirmation
    }

    event.preventDefault();

    Swal.fire({
        title: 'Keluar dari workspace?',
        text: 'Anda akan mengakhiri sesi aktif di SIMASADI.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#5645d4',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'simasadi-swal-popup',
            confirmButton: 'simasadi-swal-btn',
            cancelButton: 'simasadi-swal-cancel-btn',
            title: 'simasadi-swal-title',
            htmlContainer: 'simasadi-swal-text'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            const curtain = document.querySelector('#logout-curtain');
            if (curtain) {
                curtain.classList.add('is-active');
                curtain.setAttribute('aria-hidden', 'false');
            }
            setTimeout(() => {
                form.submit();
            }, 450);
        }
    });
});


