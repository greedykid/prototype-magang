// ==========================================================================
// Live Partial Filter & Instant Pagination Controller
// ==========================================================================

let activeAbortControllers = new Map();
let searchDebounceTimers = new Map();

/**
 * Execute partial fetch and update the targeted container
 */
export const executePartialFilter = async (form, pushHistory = false) => {
    const targetSelector = form.dataset.target || '#lpk-table-container';
    const container = document.querySelector(targetSelector);
    if (!container) return;

    const formId = form.id || 'default-form';

    // Cancel in-flight request for this form
    if (activeAbortControllers.has(formId)) {
        activeAbortControllers.get(formId).abort();
    }
    const abortController = new AbortController();
    activeAbortControllers.set(formId, abortController);

    // Cancel pending debounce
    if (searchDebounceTimers.has(formId)) {
        clearTimeout(searchDebounceTimers.get(formId));
    }

    const url = new URL(form.action || window.location.href, window.location.origin);
    const formData = new FormData(form);
    let hasActiveFilter = false;

    for (const [key, val] of formData.entries()) {
        const trimmed = typeof val === 'string' ? val.trim() : '';
        if (trimmed) {
            url.searchParams.set(key, trimmed);
            if (key !== 'page') {
                hasActiveFilter = true;
            }
        } else {
            url.searchParams.delete(key);
        }
    }

    // Always reset page to 1 when changing filters
    url.searchParams.delete('page');

    const resetBtn = form.querySelector('#lpk-filter-reset-btn') || form.querySelector('[data-role="reset-filter"]');
    const loadingEl = form.querySelector('#lpk-filter-loading') || form.querySelector('.filter-live-indicator');

    // Immediately update active filter badges so UI reflects user selection instantly
    form._updateFilterBadges?.();

    // Visual loading state
    container.classList.add('is-loading');
    container.setAttribute('aria-busy', 'true');
    if (loadingEl) loadingEl.style.display = 'inline-flex';

    try {
        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Partial-Content': 'table'
            },
            signal: abortController.signal
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const html = await response.text();
        container.innerHTML = html;

        // Update browser URL quietly without reload
        if (pushHistory) {
            window.history.pushState({ url: url.toString(), partialTarget: targetSelector }, '', url.toString());
        } else {
            window.history.replaceState({ url: url.toString(), partialTarget: targetSelector }, '', url.toString());
        }

        // Toggle Reset Button visibility
        if (resetBtn) {
            resetBtn.style.display = hasActiveFilter ? 'inline-flex' : 'none';
        }

        // Update active filter badges bar
        form._updateFilterBadges?.();

        // Re-init datatable if present
        if (typeof window.initDataTables === 'function') {
            window.initDataTables();
        }
    } catch (err) {
        if (err.name === 'AbortError') {
            // Superseded by newer keystroke or change
            return;
        }
        console.error('Partial filter error:', err);
    } finally {
        activeAbortControllers.delete(formId);
        container.classList.remove('is-loading');
        container.removeAttribute('aria-busy');
        if (loadingEl) loadingEl.style.display = 'none';
    }
};

/**
 * Handle pagination links clicked inside the partial container
 */
export const executePaginationClick = async (link, targetContainer) => {
    const url = new URL(link.href, window.location.origin);
    const form = targetContainer.closest('.panel')?.querySelector('form[data-partial-filter="true"]') || document.querySelector('form[data-partial-filter="true"]');
    const formId = form ? (form.id || 'default-form') : 'pagination';

    if (activeAbortControllers.has(formId)) {
        activeAbortControllers.get(formId).abort();
    }
    const abortController = new AbortController();
    activeAbortControllers.set(formId, abortController);

    targetContainer.classList.add('is-loading');
    targetContainer.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Partial-Content': 'table'
            },
            signal: abortController.signal
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const html = await response.text();
        targetContainer.innerHTML = html;

        window.history.pushState({ url: url.toString() }, '', url.toString());

        // Re-init datatable if present
        if (typeof window.initDataTables === 'function') {
            window.initDataTables();
        }

        // Smooth scroll to top of table
        targetContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (err) {
        if (err.name === 'AbortError') return;
        console.error('Partial pagination error, falling back:', err);
        window.location.href = link.href;
    } finally {
        activeAbortControllers.delete(formId);
        targetContainer.classList.remove('is-loading');
        targetContainer.removeAttribute('aria-busy');
    }
};

let liveFilterInitialized = false;

export const initLiveFilters = () => {
    if (liveFilterInitialized) return;
    liveFilterInitialized = true;

    // 1. Debounced text / search input
    document.addEventListener('input', (event) => {
        const form = event.target.closest('form[data-partial-filter="true"]');
        if (!form) return;

        const input = event.target;
        if (!input.matches('input[type="search"], input[type="text"], input[name="search"]')) return;

        form._updateFilterBadges?.();

        const formId = form.id || 'default-form';
        if (searchDebounceTimers.has(formId)) {
            clearTimeout(searchDebounceTimers.get(formId));
        }

        // 280ms debounce is optimal for responsiveness without unnecessary requests
        const timer = setTimeout(() => {
            executePartialFilter(form, false);
        }, 280);
        searchDebounceTimers.set(formId, timer);
    });

    let isResetting = false;

    // 2. Immediate change on dropdowns and date inputs
    document.addEventListener('change', (event) => {
        if (isResetting) return;
        const form = event.target.closest('form[data-partial-filter="true"]');
        if (!form) return;

        const el = event.target;
        if (el.matches('select, input[type="date"], input[type="radio"], input[type="checkbox"]')) {
            form._updateFilterBadges?.();
            executePartialFilter(form, false);
        }
    });

    // 3. Form submit (e.g. user hits Enter in search box)
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-partial-filter="true"]');
        if (!form) return;

        event.preventDefault();
        executePartialFilter(form, false);
    });

    // 4. Reset Button click
    document.addEventListener('click', (event) => {
        const resetBtn = event.target.closest('#lpk-filter-reset-btn, [data-role="reset-filter"]');
        if (!resetBtn) return;

        const form = resetBtn.closest('form') || resetBtn.closest('.panel')?.querySelector('form[data-partial-filter="true"]') || document.querySelector('form[data-partial-filter="true"]');
        if (!form) return;

        event.preventDefault();

        // Instantly remove active filters bar for immediate UI responsiveness
        document.querySelectorAll('.active-filters-bar').forEach((bar) => bar.remove());

        // Cancel any pending search debounce
        const formId = form.id || 'default-form';
        if (searchDebounceTimers.has(formId)) {
            clearTimeout(searchDebounceTimers.get(formId));
        }

        // Set isResetting to avoid triggering individual change fetches
        isResetting = true;

        // Reset all inputs
        form.querySelectorAll('input:not([type="hidden"])').forEach((input) => {
            input.value = '';
        });
        form.querySelectorAll('select').forEach((sel) => {
            sel.value = '';
            sel.selectedIndex = 0;
            // Notify custom-select to reset its label without bubbling to document
            sel.dispatchEvent(new Event('change', { bubbles: false }));
        });

        isResetting = false;

        // Trigger a single clean partial refresh
        executePartialFilter(form, true);
    });

    // 5. Pagination link clicks inside partial table container
    document.addEventListener('click', (event) => {
        const link = event.target.closest('.lpk-table-container .pagination a, .lpk-table-container nav[role="navigation"] a');
        if (!link) return;

        const container = link.closest('.lpk-table-container');
        if (!container) return;

        event.preventDefault();
        executePaginationClick(link, container);
    });
};
