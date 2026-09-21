// ==========================================================================
// Data Table Sorting, Toolbar Controls, View Toggles & Filter Drawer
// ==========================================================================

export const createViewToggle = (className, label, views, activeView, onChange) => {
    const toggle = document.createElement('div');
    toggle.className = className;
    toggle.setAttribute('role', 'group');
    toggle.setAttribute('aria-label', label);

    views.forEach(([value, text, iconSvg]) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.view = value;
        if (iconSvg) {
            button.innerHTML = `${iconSvg}<span>${text}</span>`;
        } else {
            button.textContent = text;
        }
        button.setAttribute('aria-pressed', String(value === activeView));
        button.addEventListener('click', () => onChange(value, toggle));
        toggle.append(button);
    });

    return toggle;
};

export const parseCellValue = (rawText) => {
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

export const sortTableByColumn = (table, colIndex, targetTh) => {
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

export const initSortableHeaders = (table) => {
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

export const initTableToolbar = (tableWrap) => {
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

export function syncViewToggleLocation() {
    const isMobile = window.matchMedia('(max-width: 600px)').matches;
    document.querySelectorAll('.table-wrap').forEach((tableWrap) => {
        const parentPanel = tableWrap.closest('.panel') || tableWrap.parentElement;
        if (!parentPanel) return;

        const toggle = parentPanel.querySelector('.table-view-toggle');
        if (!toggle) return;

        let tableControls = parentPanel.querySelector('.table-controls');
        const toolbar = parentPanel.querySelector('.table-toolbar');

        if (isMobile) {
            if (!tableControls) {
                tableControls = document.createElement('div');
                tableControls.className = 'table-controls';
                const firstChild = parentPanel.querySelector('.table-filters-placeholder, .table-filters, .active-filters-bar, .table-toolbar, .table-wrap');
                if (firstChild) {
                    parentPanel.insertBefore(tableControls, firstChild);
                } else {
                    parentPanel.prepend(tableControls);
                }
            }
            if (toggle.parentElement !== tableControls) {
                tableControls.appendChild(toggle);
            }
        } else if (toolbar) {
            if (toggle.parentElement !== toolbar) {
                toolbar.appendChild(toggle);
            }
        }
    });
}

export const initDataTables = () => {
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
            return header.dataset.label || header.querySelector('.th-label')?.textContent.trim() || header.textContent.trim();
        });
        const storageKey = `simasadi-table-view-${window.location.pathname}-${index}`;
        const isMobile = window.matchMedia('(max-width: 600px)').matches;
        const savedView = localStorage.getItem(storageKey);
        const initialView = isMobile ? (savedView || 'grid') : 'table';

        table.querySelectorAll('tbody tr').forEach((row) => {
            row.querySelectorAll('td').forEach((cell, cellIndex) => {
                cell.dataset.label = cell.dataset.label || headers[cellIndex] || '';
            });
        });

        const setView = (view, toggle) => {
            tableWrap.classList.toggle('table-mode-grid', view === 'grid');
            tableWrap.classList.toggle('table-mode-table', view === 'table');
            tableWrap.parentElement?.classList.toggle('showing-table', view === 'table');
            localStorage.setItem(storageKey, view);
            toggle.querySelectorAll('button').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === view)));
        };

        const tableIconSvg = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18M3 9h18M3 15h18"/><rect width="18" height="18" x="3" y="3" rx="2"/></svg>`;
        const gridIconSvg = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>`;

        const toggle = createViewToggle(
            'view-toggle table-view-toggle',
            'Tampilan data',
            [
                ['table', 'Tabel', tableIconSvg],
                ['grid', 'Grid', gridIconSvg]
            ],
            initialView,
            setView
        );
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
        const filterIconSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>';
        toggle.innerHTML = `<span class="filter-toggle-icon" aria-hidden="true">${filterIconSvg}</span><span>Filter data</span>`;

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
        const syncDrawerMount = () => {
            const isMobile = window.matchMedia('(max-width: 600px)').matches;
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

            // Render active filter chips bar
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

    // Sync positioning
    syncViewToggleLocation();
};

let viewToggleResizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(viewToggleResizeTimer);
    viewToggleResizeTimer = setTimeout(syncViewToggleLocation, 50);
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
