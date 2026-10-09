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
let lastWindowWidth = typeof window !== 'undefined' ? window.innerWidth : 1024;
const setupGlobalSelectListeners = () => {
    if (globalSelectListenersAdded) return;
    globalSelectListenersAdded = true;

    document.addEventListener('click', (event) => {
        // Delegated trigger click handling (self-healing for any restored DOM)
        const trigger = event.target.closest('.custom-select-trigger');
        if (trigger) {
            const wrapper = trigger.closest('.custom-select-wrapper');
            if (wrapper) {
                event.preventDefault();
                event.stopPropagation();
                if (typeof wrapper._customSelectToggle === 'function') {
                    wrapper._customSelectToggle();
                } else {
                    // Self-healing: if wrapper lost its instance, unwrap and rebuild immediately
                    const nativeSelect = wrapper.querySelector('select.custom-select-native') || wrapper.querySelector('select');
                    if (nativeSelect) {
                        nativeSelect.classList.remove('custom-select-native');
                        delete nativeSelect.dataset.customSelectInit;
                        wrapper.parentNode.insertBefore(nativeSelect, wrapper);
                        wrapper.remove();
                        createCustomSelect(nativeSelect);
                        nativeSelect.closest('.custom-select-wrapper')?._customSelectToggle?.();
                    }
                }
                return;
            }
        }

        // Delegated option click handling
        const optionEl = event.target.closest('.custom-select-option');
        if (optionEl) {
            const wrapper = optionEl.closest('.custom-select-wrapper');
            if (wrapper && typeof wrapper._customSelectChoose === 'function') {
                event.preventDefault();
                event.stopPropagation();
                wrapper._customSelectChoose(optionEl.dataset.value);
                return;
            }
        }

        // Safe outside click detection without using brittle CSS :has()
        const clickedInsideWrapper = !!event.target.closest('.custom-select-wrapper');
        const clickedInsideLabel = !!(event.target.closest('label')?.querySelector('.custom-select-wrapper'));
        if (!clickedInsideWrapper && !clickedInsideLabel) {
            closeAllCustomSelects();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllCustomSelects();
        }
    });

    window.addEventListener('resize', () => {
        // Mobile soft keyboards resize window.innerHeight without altering window.innerWidth.
        // Only close custom selects if the viewport width actually changed significantly (e.g. orientation change).
        const currentWidth = window.innerWidth;
        if (Math.abs(currentWidth - lastWindowWidth) <= 35) {
            return;
        }
        lastWindowWidth = currentWidth;

        // If user is currently typing inside a custom select search field, do not close it
        if (document.activeElement && document.activeElement.closest('.custom-select-wrapper')) {
            return;
        }

        closeAllCustomSelects();
    });
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

    // Connect label to trigger button instead of hidden select to avoid dead clicks & synthetic closures
    const parentLabel = wrapper.closest('label') || (select.id ? document.querySelector(`label[for="${select.id}"]`) : null);
    if (parentLabel) {
        const triggerId = select.id ? `${select.id}-trigger` : `custom-select-trigger-${Math.random().toString(36).slice(2, 8)}`;
        trigger.id = triggerId;
        parentLabel.setAttribute('for', triggerId);
    }

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

    if (optionsCount > 6 && select.dataset.noSearch === undefined) {
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

            const labelText = opt.textContent.trim();
            const badge = opt.dataset.badge || opt.dataset.code;

            let badgeHtml = '';
            let textHtml = labelText;

            if (badge) {
                badgeHtml = `<span class="custom-select-badge">${badge}</span>`;
                if (textHtml.startsWith(badge)) {
                    textHtml = textHtml.slice(badge.length).replace(/^[\s—\-:]+/, '');
                }
            }

            optionEl.innerHTML = `
                <span class="custom-select-option-content">
                    ${badgeHtml}
                    <span class="custom-select-option-text">${textHtml}</span>
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
            const labelText = selectedOpt.textContent.trim();
            const badge = selectedOpt.dataset.badge || selectedOpt.dataset.code;
            if (badge && selectedOpt.value) {
                let textHtml = labelText;
                if (textHtml.startsWith(badge)) {
                    textHtml = textHtml.slice(badge.length).replace(/^[\s—\-:]+/, '');
                }
                valueSpan.innerHTML = `<span class="custom-select-badge-trigger">${badge}</span><span class="custom-select-label-trigger">${textHtml}</span>`;
            } else {
                valueSpan.innerHTML = `<span>${labelText}</span>`;
            }
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
            const text = (el.querySelector('.custom-select-option-content')?.textContent || el.textContent || '').toLowerCase();
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
        const modalBox = wrapper.closest('.simasadi-modal-box');
        let spaceBelow = window.innerHeight - rect.bottom;
        let spaceAbove = rect.top;

        if (modalBox) {
            const modalRect = modalBox.getBoundingClientRect();
            spaceBelow = modalRect.bottom - rect.bottom;
            spaceAbove = rect.top - modalRect.top;
        }

        if (spaceBelow < 190 && spaceAbove > spaceBelow && spaceAbove >= 190) {
            wrapper.classList.add('dropup');
        } else {
            wrapper.classList.remove('dropup');
        }

        wrapper.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');

        let el = wrapper.parentElement;
        while (el && !el.classList.contains('simasadi-modal') && !el.classList.contains('app-shell') && el !== document.body) {
            if (el.tagName === 'LABEL' ||
                el.classList.contains('table-filter-grid') ||
                el.classList.contains('table-filters') ||
                el.classList.contains('table-filter-actions') ||
                el.classList.contains('modal-form-grid-2col') ||
                el.classList.contains('lpk-field-row') ||
                el.classList.contains('lpk-field') ||
                el.classList.contains('form-grid') ||
                el.classList.contains('inline-form') ||
                el.classList.contains('panel') ||
                el.classList.contains('card') ||
                el.classList.contains('gcal-board') ||
                el.classList.contains('gcal-toolbar') ||
                el.classList.contains('gcal-main') ||
                el.tagName === 'SECTION' ||
                el.tagName === 'FORM') {
                el.classList.add('has-open-select');
            }
            el = el.parentElement;
        }

        const isTouchDevice = ('ontouchstart' in window || navigator.maxTouchPoints > 0 || window.innerWidth <= 768);
        if (searchInput) {
            searchInput.value = '';
            filterOptions('');
            // Do not auto-focus on touch/mobile devices to prevent the soft keyboard from immediately covering the dropdown
            if (!isTouchDevice) {
                setTimeout(() => searchInput.focus(), 30);
            }
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

        document.querySelectorAll('.has-open-select').forEach((container) => {
            if (!container.querySelector('.custom-select-wrapper.is-open')) {
                container.classList.remove('has-open-select');
            }
        });
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

    wrapper._customSelectInstance = true;
    wrapper._customSelectToggle = toggleMenu;
    wrapper._customSelectOpen = openMenu;
    wrapper._customSelectClose = closeMenu;
    wrapper._customSelectChoose = selectOption;
    wrapper._customSelectSync = syncFromNative;
    wrapper._nativeSelect = select;

    buildOptions();
    syncFromNative();
};

const initCustomSelects = (container = document) => {
    setupGlobalSelectListeners();

    // Self-healing: if any custom-select-wrapper exists in the container but lost its active JS instance
    // (e.g. from DOM snapshot or partial replacement), unwrap native select and rebuild
    container.querySelectorAll('.custom-select-wrapper').forEach((wrapper) => {
        if (!wrapper._customSelectInstance) {
            const nativeSelect = wrapper.querySelector('select.custom-select-native') || wrapper.querySelector('select');
            if (nativeSelect) {
                nativeSelect.classList.remove('custom-select-native');
                delete nativeSelect.dataset.customSelectInit;
                wrapper.parentNode.insertBefore(nativeSelect, wrapper);
            }
            wrapper.remove();
        }
    });

    const selects = container.querySelectorAll('select:not([data-custom-select-init])');
    selects.forEach((select) => {
        if (select.dataset.noCustom !== undefined) return;
        select.dataset.customSelectInit = 'true';
        createCustomSelect(select);
    });
};


export { closeAllCustomSelects, initCustomSelects };
if (typeof window !== 'undefined') { window.closeAllCustomSelects = closeAllCustomSelects; }
