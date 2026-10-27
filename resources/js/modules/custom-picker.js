// ==========================================================================
// SIMASADI Custom Date & Clock Picker Components
// High-polish, accessible, zero-slop pickers for Date and 24-Hour Time
// ==========================================================================

const MONTH_NAMES = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const MONTH_SHORT = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

const DAY_NAMES = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const closeAllCustomPickers = (exceptWrapper = null) => {
    document.querySelectorAll('.custom-datepicker-wrapper.is-open, .custom-clockpicker-wrapper.is-open').forEach((w) => {
        if (w !== exceptWrapper) {
            if (typeof w._customPickerClose === 'function') {
                w._customPickerClose();
            } else {
                w.classList.remove('is-open', 'dropup', 'align-right');
                const popover = w._popover || w.querySelector('.custom-picker-popover');
                if (popover) {
                    if (popover.classList.contains('is-teleported')) {
                        w.appendChild(popover);
                        popover.classList.remove('is-teleported', 'dropup');
                    }
                    popover.style.position = '';
                    popover.style.top = '';
                    popover.style.left = '';
                    popover.style.right = '';
                    popover.style.bottom = '';
                    popover.style.zIndex = '';
                }
                w.querySelector('.custom-picker-trigger')?.setAttribute('aria-expanded', 'false');
            }
        }
    });

    // Clean up any teleported popovers in body that might belong to closed wrappers
    document.querySelectorAll('body > .custom-picker-popover.is-teleported').forEach((popover) => {
        if (popover._wrapper !== exceptWrapper) {
            if (popover._wrapper && typeof popover._wrapper._customPickerClose === 'function') {
                popover._wrapper._customPickerClose();
            } else if (popover._wrapper) {
                popover._wrapper.appendChild(popover);
                popover.classList.remove('is-teleported', 'dropup');
                popover.style.position = '';
                popover.style.top = '';
                popover.style.left = '';
                popover.style.right = '';
                popover.style.bottom = '';
                popover.style.zIndex = '';
            } else {
                popover.remove();
            }
        }
    });

    document.querySelectorAll('.has-open-picker').forEach((el) => {
        if (!el.querySelector('.custom-datepicker-wrapper.is-open, .custom-clockpicker-wrapper.is-open')) {
            el.classList.remove('has-open-picker');
        }
    });
};

let globalPickerListenersAdded = false;
let lastWindowWidth = typeof window !== 'undefined' ? window.innerWidth : 1024;
let lastOpenedAt = 0;
let isProgrammaticScrolling = false;
let programmaticScrollTimer = null;

const setProgrammaticScrolling = (durationMs = 650) => {
    isProgrammaticScrolling = true;
    if (programmaticScrollTimer) {
        clearTimeout(programmaticScrollTimer);
    }
    programmaticScrollTimer = setTimeout(() => {
        isProgrammaticScrolling = false;
    }, durationMs);
};

const setupGlobalPickerListeners = () => {
    if (globalPickerListenersAdded) return;
    globalPickerListenersAdded = true;

    document.addEventListener('click', (event) => {
        if (!event.target || typeof event.target.closest !== 'function') return;
        if (!event.target.closest('.custom-datepicker-wrapper, .custom-clockpicker-wrapper, .custom-picker-popover')) {
            closeAllCustomPickers();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllCustomPickers();
            return;
        }

        const openWrapper = document.querySelector('.custom-datepicker-wrapper.is-open');
        if (!openWrapper) return;

        if (['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            const dayButtons = Array.from(openWrapper.querySelectorAll('.custom-datepicker-day'));
            if (dayButtons.length === 0) return;

            const currentIndex = dayButtons.indexOf(document.activeElement);
            let nextIndex = -1;

            if (currentIndex === -1) {
                const selectedDay = openWrapper.querySelector('.custom-datepicker-day.is-selected');
                const todayDay = openWrapper.querySelector('.custom-datepicker-day.is-today');
                const firstCurrentDay = openWrapper.querySelector('.custom-datepicker-day:not(.is-outside-month)');
                const target = selectedDay || todayDay || firstCurrentDay || dayButtons[0];
                target?.focus();
                event.preventDefault();
                return;
            }

            if (event.key === 'ArrowRight') nextIndex = currentIndex + 1;
            else if (event.key === 'ArrowLeft') nextIndex = currentIndex - 1;
            else if (event.key === 'ArrowDown') nextIndex = currentIndex + 7;
            else if (event.key === 'ArrowUp') nextIndex = currentIndex - 7;
            else if (event.key === 'Home') nextIndex = currentIndex - (currentIndex % 7);
            else if (event.key === 'End') nextIndex = currentIndex + (6 - (currentIndex % 7));

            if (nextIndex >= 0 && nextIndex < dayButtons.length) {
                dayButtons[nextIndex].focus();
                event.preventDefault();
            }
        }
    });

    window.addEventListener('resize', () => {
        const currentWidth = window.innerWidth;
        if (Math.abs(currentWidth - lastWindowWidth) <= 35) return;
        lastWindowWidth = currentWidth;
        closeAllCustomPickers();
    });

    window.addEventListener('scroll', (event) => {
        if (isProgrammaticScrolling) {
            return;
        }
        if (event.target && typeof event.target.closest === 'function' && event.target.closest('.custom-picker-popover')) {
            return;
        }
        if (Date.now() - lastOpenedAt < 400) {
            return;
        }

        // Dynamically update position of open teleported modal picker during modal or window scroll
        const openTeleported = document.querySelector('body > .custom-picker-popover.is-teleported');
        if (openTeleported && openTeleported._wrapper && openTeleported._trigger) {
            adjustPickerPosition(openTeleported._wrapper, openTeleported._trigger);
            return;
        }

        closeAllCustomPickers();
    }, true);
};

const elevateParents = (wrapper, isOpen) => {
    const inModal = Boolean(wrapper.closest('.simasadi-modal'));
    if (inModal) {
        // Popovers in modals are teleported to document.body and float cleanly via position: fixed.
        // Modal containers (modal-body, modal-box, form) MUST retain their natural overflow:auto
        // and default stacking context so other components do NOT leak out or pop forward.
        return;
    }

    let el = wrapper.parentElement;
    while (el && !el.classList.contains('simasadi-modal') && !el.classList.contains('app-shell') && el !== document.body) {
        if (el.tagName === 'LABEL' ||
            el.classList.contains('split-panel') ||
            el.classList.contains('lpk-field-row') ||
            el.classList.contains('lpk-field') ||
            el.classList.contains('lpk-form-card') ||
            el.classList.contains('lpk-form-column') ||
            el.classList.contains('form-grid') ||
            el.classList.contains('inline-form') ||
            el.classList.contains('table-filter-grid') ||
            el.classList.contains('table-filters') ||
            el.classList.contains('panel') ||
            el.classList.contains('card') ||
            el.classList.contains('page-wrap') ||
            el.classList.contains('gcal-board') ||
            el.classList.contains('gcal-toolbar') ||
            el.classList.contains('gcal-main') ||
            el.tagName === 'SECTION') {
            if (isOpen) {
                el.classList.add('has-open-picker');
            } else if (!el.querySelector('.custom-datepicker-wrapper.is-open, .custom-clockpicker-wrapper.is-open')) {
                el.classList.remove('has-open-picker');
            }
        }
        el = el.parentElement;
    }
};

const adjustPickerPosition = (wrapper, trigger, explicitHeight = null, explicitWidth = null) => {
    wrapper.classList.remove('dropup', 'align-right');

    const popover = wrapper._popover || wrapper.querySelector('.custom-picker-popover') || (trigger && trigger.nextElementSibling && trigger.nextElementSibling.classList.contains('custom-picker-popover') ? trigger.nextElementSibling : null);
    if (!popover) return;

    const isTeleported = popover.classList.contains('is-teleported');
    const isClock = wrapper.classList.contains('custom-clockpicker-wrapper');
    const defaultHeight = isClock ? 380 : 360;
    const defaultWidth = 310;
    const popoverHeight = explicitHeight || (popover.offsetHeight > 50 ? popover.offsetHeight : defaultHeight);
    const popoverWidth = explicitWidth || (popover.offsetWidth > 50 ? popover.offsetWidth : defaultWidth);

    const triggerRect = trigger.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    if (isTeleported) {
        // Modal context with viewport teleportation
        const modalBox = document.querySelector('.simasadi-modal.is-active .simasadi-modal-box') || (wrapper.closest('.simasadi-modal') && wrapper.closest('.simasadi-modal').querySelector('.simasadi-modal-box'));
        if (modalBox) {
            const modalBody = modalBox.querySelector('.simasadi-modal-body, .modal-body, .modal-import-body') || modalBox;
            const bodyRect = modalBody.getBoundingClientRect();
            // If the trigger has scrolled out of viewable modal body bounds, close the picker
            if (triggerRect.bottom < bodyRect.top + 4 || triggerRect.top > bodyRect.bottom - 4) {
                if (typeof wrapper._customPickerClose === 'function') {
                    wrapper._customPickerClose();
                }
                return;
            }
        }

        const spaceBelowViewport = viewportHeight - triggerRect.bottom - 12;
        const spaceAboveViewport = triggerRect.top - 12;

        let top;
        let isDropup = false;

        // 1. If space below fits cleanly, open downward
        if (spaceBelowViewport >= popoverHeight) {
            top = triggerRect.bottom + 6;
            isDropup = false;
        }
        // 2. If space below is cramped, but space above fits cleanly, open upward
        else if (spaceAboveViewport >= popoverHeight) {
            top = triggerRect.top - popoverHeight - 6;
            isDropup = true;
        }
        // 3. If neither fits cleanly, pick whichever side has more room
        else if (spaceAboveViewport > spaceBelowViewport) {
            top = triggerRect.top - popoverHeight - 6;
            isDropup = true;
        } else {
            top = triggerRect.bottom + 6;
            isDropup = false;
        }

        // CRITICAL: Strict Viewport Clamping (Vertical)
        // Ensure popover NEVER extends above the viewport top edge (at least 12px margin)
        if (top < 12) {
            top = 12;
        }
        // Ensure popover NEVER extends below the viewport bottom edge (at least 12px margin)
        if (top + popoverHeight > viewportHeight - 12) {
            top = Math.max(12, viewportHeight - popoverHeight - 12);
        }

        // Horizontal positioning: align left with trigger, flip to right if overflow
        let left = triggerRect.left;
        if (left + popoverWidth > viewportWidth - 12) {
            left = triggerRect.right - popoverWidth;
        }
        // Clamp to screen boundaries (Horizontal)
        if (left < 12) left = 12;
        if (left + popoverWidth > viewportWidth - 12) {
            left = Math.max(12, viewportWidth - popoverWidth - 12);
        }

        popover.classList.toggle('dropup', isDropup);
        wrapper.classList.toggle('dropup', isDropup);

        popover.style.position = 'fixed';
        popover.style.top = `${Math.round(top)}px`;
        popover.style.left = `${Math.round(left)}px`;
        popover.style.right = 'auto';
        popover.style.bottom = 'auto';
        popover.style.zIndex = '100050';
        return;
    }

    // Standard non-modal page context (normal relative wrapper positioning)
    popover.style.position = '';
    popover.style.top = '';
    popover.style.bottom = '';
    popover.style.zIndex = '';
    popover.style.left = '';
    popover.style.right = '';

    const topbar = document.querySelector('.topbar');
    const topbarBottom = topbar ? Math.max(0, topbar.getBoundingClientRect().bottom) : 0;
    const spaceBelowViewport = viewportHeight - triggerRect.bottom - 12;
    const spaceAboveViewport = triggerRect.top - Math.max(topbarBottom, 12);

    const docElement = document.documentElement;
    const docHeight = Math.max(docElement ? docElement.scrollHeight : 0, document.body ? document.body.scrollHeight : 0);
    const currentScrollY = window.scrollY || window.pageYOffset || 0;
    const remainingScrollDown = Math.max(0, docHeight - (currentScrollY + viewportHeight));
    const totalSpaceBelow = spaceBelowViewport + remainingScrollDown;

    const isNearBottomEdge = spaceBelowViewport < 160 && totalSpaceBelow < 220;
    const hasAmpleRoomAbove = spaceAboveViewport >= (popoverHeight + 16);

    if (isNearBottomEdge && hasAmpleRoomAbove) {
        wrapper.classList.add('dropup');
        popover.classList.add('dropup');
    } else {
        wrapper.classList.remove('dropup');
        popover.classList.remove('dropup');
    }

    // Horizontal alignment on standard pages
    const parentCard = trigger.closest('.lpk-form-card, .panel, .card, [role="dialog"]');
    const containerRight = parentCard ? (parentCard.getBoundingClientRect().right - 14) : (window.innerWidth - 14);
    const boundaryRight = Math.min(window.innerWidth - 14, containerRight);
    const isRightColumn = window.innerWidth > 640 && Boolean(trigger.closest('.lpk-field-row > .lpk-field:nth-child(2), .lpk-field-row > .lpk-field:last-child, .form-grid > label:last-child, .form-grid > div:last-child'));

    if ((triggerRect.left + popoverWidth > boundaryRight || isRightColumn) && (triggerRect.right - popoverWidth >= 12)) {
        wrapper.classList.add('align-right');
    } else if (triggerRect.left < 12) {
        popover.style.left = `${12 - triggerRect.left}px`;
    }
};

const ensurePickerInModalView = (wrapper, modalBox) => {
    // In modal dialogs, do NOT scroll the internal modal body when a picker opens.
    // The popover floats above form fields and footer buttons via high z-index without shifting scroll position or tabs.
    return;
};

const ensurePickerInView = (wrapper) => {
    const modalBox = wrapper.closest('.simasadi-modal-box, .modal-box') || wrapper.closest('.simasadi-modal') || (wrapper._popover && wrapper._popover.classList.contains('is-teleported'));
    if (modalBox) {
        ensurePickerInModalView(wrapper, modalBox);
        return;
    }

    requestAnimationFrame(() => {
        const popover = wrapper.querySelector('.custom-picker-popover');
        if (!popover || !wrapper.classList.contains('is-open')) return;

        const popoverRect = popover.getBoundingClientRect();
        const topbar = document.querySelector('.topbar');
        const topbarBottom = topbar ? Math.max(0, topbar.getBoundingClientRect().bottom) : 0;

        // If bottom extends below viewport bottom, scroll window smoothly so the whole popover is visible
        if (popoverRect.bottom > window.innerHeight - 12) {
            const scrollDistance = popoverRect.bottom - window.innerHeight + 24;
            setProgrammaticScrolling(650);
            window.scrollBy({ top: scrollDistance, behavior: 'smooth' });
        }
        // If top extends above topbar (or too close to topbar), scroll window up
        else if (popoverRect.top < topbarBottom + 8) {
            const scrollDistance = popoverRect.top - (topbarBottom + 14);
            setProgrammaticScrolling(650);
            window.scrollBy({ top: scrollDistance, behavior: 'smooth' });
        }
    });
};

const attachValueInterceptor = (input, onUpdate) => {
    const originalDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    if (originalDescriptor && originalDescriptor.set) {
        Object.defineProperty(input, 'value', {
            get() {
                return originalDescriptor.get.call(this);
            },
            set(val) {
                originalDescriptor.set.call(this, val);
                onUpdate();
            },
            configurable: true
        });
    }
};

// ==========================================================================
// 1. CUSTOM DATE PICKER
// ==========================================================================
const createCustomDatePicker = (input) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'custom-datepicker-wrapper';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    input.classList.add('custom-picker-native');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'custom-picker-trigger custom-datepicker-trigger';
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', 'false');

    const valueSpan = document.createElement('span');
    valueSpan.className = 'custom-picker-value';

    const iconSpan = document.createElement('span');
    iconSpan.className = 'custom-picker-icon';
    iconSpan.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" x2="16" y1="2" y2="6"/>
            <line x1="8" x2="8" y1="2" y2="6"/>
            <line x1="3" x2="21" y1="10" y2="10"/>
        </svg>
    `;

    trigger.append(valueSpan, iconSpan);
    wrapper.appendChild(trigger);

    // Popover Element
    const popover = document.createElement('div');
    popover.className = 'custom-picker-popover custom-datepicker-popover';
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', 'Pilih Tanggal');
    wrapper.appendChild(popover);

    // Initial View State
    const now = new Date();
    let viewYear = now.getFullYear();
    let viewMonth = now.getMonth();
    let selectedDate = null; // Date object or null
    let currentView = 'days'; // 'days' | 'months' | 'years'
    let yearGridStart = Math.floor(viewYear / 12) * 12;

    const parseInputValue = (val) => {
        if (!val || typeof val !== 'string') return null;
        const parts = val.split('-');
        if (parts.length === 3) {
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const d = parseInt(parts[2], 10);
            if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                return new Date(y, m, d);
            }
        }
        return null;
    };

    const formatDisplay = (date) => {
        if (!date) return '<span class="is-placeholder">Pilih tanggal...</span>';
        const d = date.getDate();
        const m = MONTH_SHORT[date.getMonth()];
        const y = date.getFullYear();
        return `<span>${d} ${m} ${y}</span>`;
    };

    const formatIso = (date) => {
        if (!date) return '';
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    const syncFromNative = () => {
        selectedDate = parseInputValue(input.value);
        if (selectedDate) {
            viewYear = selectedDate.getFullYear();
            viewMonth = selectedDate.getMonth();
        }
        valueSpan.innerHTML = formatDisplay(selectedDate);
        renderCalendar();
    };

    const setDate = (date) => {
        selectedDate = date;
        const iso = formatIso(date);
        if (input.value !== iso) {
            input.value = iso;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        valueSpan.innerHTML = formatDisplay(date);
        closePopover();
        trigger.focus();
    };

    const clearDate = () => {
        selectedDate = null;
        if (input.value !== '') {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        valueSpan.innerHTML = formatDisplay(null);
        closePopover();
        trigger.focus();
    };

    // Render Day View
    const renderDayView = () => {
        // 1. Header with Month & Year Clickable Selectors and Nav Buttons
        const header = document.createElement('div');
        header.className = 'custom-datepicker-header';

        const prevMonthBtn = document.createElement('button');
        prevMonthBtn.type = 'button';
        prevMonthBtn.className = 'custom-picker-nav-btn';
        prevMonthBtn.title = 'Bulan Sebelumnya';
        prevMonthBtn.innerHTML = `
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        `;
        prevMonthBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            viewMonth -= 1;
            if (viewMonth < 0) {
                viewMonth = 11;
                viewYear -= 1;
            }
            renderCalendar();
        };

        const titleWrap = document.createElement('div');
        titleWrap.className = 'custom-datepicker-title-wrap';

        const monthBtn = document.createElement('button');
        monthBtn.type = 'button';
        monthBtn.className = 'custom-datepicker-header-btn custom-datepicker-month-btn';
        monthBtn.title = 'Pilih Bulan Langsung';
        monthBtn.innerHTML = `<span>${MONTH_NAMES[viewMonth]}</span> <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>`;
        monthBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            currentView = 'months';
            renderCalendar();
        };

        const yearBtn = document.createElement('button');
        yearBtn.type = 'button';
        yearBtn.className = 'custom-datepicker-header-btn custom-datepicker-year-btn';
        yearBtn.title = 'Pilih Tahun Langsung';
        yearBtn.innerHTML = `<span>${viewYear}</span> <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>`;
        yearBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            yearGridStart = Math.floor(viewYear / 12) * 12;
            currentView = 'years';
            renderCalendar();
        };

        titleWrap.append(monthBtn, yearBtn);

        const nextMonthBtn = document.createElement('button');
        nextMonthBtn.type = 'button';
        nextMonthBtn.className = 'custom-picker-nav-btn';
        nextMonthBtn.title = 'Bulan Berikutnya';
        nextMonthBtn.innerHTML = `
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        `;
        nextMonthBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            viewMonth += 1;
            if (viewMonth > 11) {
                viewMonth = 0;
                viewYear += 1;
            }
            renderCalendar();
        };

        header.append(prevMonthBtn, titleWrap, nextMonthBtn);
        popover.appendChild(header);

        // 2. Weekday Names (Monday first: Sen, Sel, Rab, Kam, Jum, Sab, Min)
        const weekdaysGrid = document.createElement('div');
        weekdaysGrid.className = 'custom-datepicker-weekdays';
        DAY_NAMES.forEach((day) => {
            const dayCell = document.createElement('div');
            dayCell.className = 'custom-datepicker-weekday';
            dayCell.textContent = day;
            weekdaysGrid.appendChild(dayCell);
        });
        popover.appendChild(weekdaysGrid);

        // 3. Month Day Grid (42 cells)
        const grid = document.createElement('div');
        grid.className = 'custom-datepicker-grid';
        grid.setAttribute('role', 'grid');

        const firstDayOfMonth = (new Date(viewYear, viewMonth, 1).getDay() + 6) % 7;
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
        const daysInPrevMonth = new Date(viewYear, viewMonth, 0).getDate();

        const isCurrentMonthToday = now.getFullYear() === viewYear && now.getMonth() === viewMonth;
        const todayDate = now.getDate();

        for (let i = 0; i < 42; i++) {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'custom-datepicker-day';

            if (i < firstDayOfMonth) {
                const dayNum = daysInPrevMonth - firstDayOfMonth + i + 1;
                cell.textContent = dayNum;
                cell.classList.add('is-outside-month');
                const targetDate = new Date(viewYear, viewMonth - 1, dayNum);
                cell.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setDate(targetDate);
                };
            } else if (i >= firstDayOfMonth + daysInMonth) {
                const dayNum = i - (firstDayOfMonth + daysInMonth) + 1;
                cell.textContent = dayNum;
                cell.classList.add('is-outside-month');
                const targetDate = new Date(viewYear, viewMonth + 1, dayNum);
                cell.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setDate(targetDate);
                };
            } else {
                const dayNum = i - firstDayOfMonth + 1;
                cell.textContent = dayNum;

                if (isCurrentMonthToday && dayNum === todayDate) {
                    cell.classList.add('is-today');
                }

                if (
                    selectedDate &&
                    selectedDate.getFullYear() === viewYear &&
                    selectedDate.getMonth() === viewMonth &&
                    selectedDate.getDate() === dayNum
                ) {
                    cell.classList.add('is-selected');
                    cell.setAttribute('aria-selected', 'true');
                }

                const targetDate = new Date(viewYear, viewMonth, dayNum);
                cell.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setDate(targetDate);
                };
            }

            grid.appendChild(cell);
        }

        popover.appendChild(grid);

        // 4. Action Footer
        const footer = document.createElement('div');
        footer.className = 'custom-picker-footer';

        const todayBtn = document.createElement('button');
        todayBtn.type = 'button';
        todayBtn.className = 'custom-picker-action-btn action-today';
        todayBtn.textContent = 'Hari Ini';
        todayBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            setDate(new Date());
        };

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'custom-picker-action-btn action-clear';
        clearBtn.textContent = 'Hapus';
        clearBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            clearDate();
        };

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'custom-picker-action-btn action-close';
        closeBtn.textContent = 'Tutup';
        closeBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            closePopover();
            trigger.focus();
        };

        footer.append(todayBtn, clearBtn, closeBtn);
        popover.appendChild(footer);
    };

    // Render Direct Year Picker View
    const renderYearView = () => {
        const header = document.createElement('div');
        header.className = 'custom-datepicker-header';

        const prevDecadeBtn = document.createElement('button');
        prevDecadeBtn.type = 'button';
        prevDecadeBtn.className = 'custom-picker-nav-btn';
        prevDecadeBtn.title = '12 Tahun Sebelumnya';
        prevDecadeBtn.innerHTML = `
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        `;
        prevDecadeBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            yearGridStart -= 12;
            renderCalendar();
        };

        const titleSelectWrap = document.createElement('div');
        titleSelectWrap.className = 'custom-datepicker-year-range-wrap';

        const yearSelect = document.createElement('select');
        yearSelect.className = 'custom-datepicker-year-select';
        yearSelect.title = 'Pilih Tahun Cepat';

        for (let y = 2000; y <= 2045; y++) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = `Tahun ${y}`;
            if (y === viewYear) opt.selected = true;
            yearSelect.appendChild(opt);
        }

        yearSelect.onchange = (e) => {
            e.stopPropagation();
            viewYear = parseInt(yearSelect.value, 10);
            yearGridStart = Math.floor(viewYear / 12) * 12;
            currentView = 'days';
            renderCalendar();
        };

        titleSelectWrap.appendChild(yearSelect);

        const nextDecadeBtn = document.createElement('button');
        nextDecadeBtn.type = 'button';
        nextDecadeBtn.className = 'custom-picker-nav-btn';
        nextDecadeBtn.title = '12 Tahun Berikutnya';
        nextDecadeBtn.innerHTML = `
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        `;
        nextDecadeBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            yearGridStart += 12;
            renderCalendar();
        };

        header.append(prevDecadeBtn, titleSelectWrap, nextDecadeBtn);
        popover.appendChild(header);

        // 12-Year Grid
        const grid = document.createElement('div');
        grid.className = 'custom-datepicker-year-grid';

        const curActualYear = now.getFullYear();

        for (let y = yearGridStart; y < yearGridStart + 12; y++) {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'custom-datepicker-year-cell';
            cell.textContent = y;

            if (y === viewYear) {
                cell.classList.add('is-selected');
                cell.setAttribute('aria-selected', 'true');
            }
            if (y === curActualYear) {
                cell.classList.add('is-today');
            }

            cell.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                viewYear = y;
                currentView = 'days';
                renderCalendar();
            };

            grid.appendChild(cell);
        }

        popover.appendChild(grid);

        // Footer in Year View
        const footer = document.createElement('div');
        footer.className = 'custom-picker-footer';

        const curYearBtn = document.createElement('button');
        curYearBtn.type = 'button';
        curYearBtn.className = 'custom-picker-action-btn action-today';
        curYearBtn.textContent = 'Tahun Ini';
        curYearBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            viewYear = now.getFullYear();
            yearGridStart = Math.floor(viewYear / 12) * 12;
            currentView = 'days';
            renderCalendar();
        };

        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'custom-picker-action-btn action-close';
        backBtn.textContent = 'Kembali';
        backBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            currentView = 'days';
            renderCalendar();
        };

        footer.append(curYearBtn, backBtn);
        popover.appendChild(footer);
    };

    // Render Direct Month Picker View
    const renderMonthView = () => {
        const header = document.createElement('div');
        header.className = 'custom-datepicker-header';

        const prevYearBtn = document.createElement('button');
        prevYearBtn.type = 'button';
        prevYearBtn.className = 'custom-picker-nav-btn';
        prevYearBtn.title = 'Tahun Sebelumnya';
        prevYearBtn.innerHTML = `
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        `;
        prevYearBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            viewYear -= 1;
            renderCalendar();
        };

        const yearTitleBtn = document.createElement('button');
        yearTitleBtn.type = 'button';
        yearTitleBtn.className = 'custom-datepicker-header-btn custom-datepicker-year-btn';
        yearTitleBtn.title = 'Pilih Tahun';
        yearTitleBtn.innerHTML = `<span>${viewYear}</span> <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>`;
        yearTitleBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            yearGridStart = Math.floor(viewYear / 12) * 12;
            currentView = 'years';
            renderCalendar();
        };

        const nextYearBtn = document.createElement('button');
        nextYearBtn.type = 'button';
        nextYearBtn.className = 'custom-picker-nav-btn';
        nextYearBtn.title = 'Tahun Berikutnya';
        nextYearBtn.innerHTML = `
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        `;
        nextYearBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            viewYear += 1;
            renderCalendar();
        };

        header.append(prevYearBtn, yearTitleBtn, nextYearBtn);
        popover.appendChild(header);

        // 12-Month Grid
        const grid = document.createElement('div');
        grid.className = 'custom-datepicker-month-grid';

        const curActualMonth = now.getMonth();
        const curActualYear = now.getFullYear();

        MONTH_NAMES.forEach((mName, mIdx) => {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'custom-datepicker-month-cell';
            cell.textContent = MONTH_SHORT[mIdx];
            cell.title = mName;

            if (mIdx === viewMonth) {
                cell.classList.add('is-selected');
                cell.setAttribute('aria-selected', 'true');
            }
            if (mIdx === curActualMonth && viewYear === curActualYear) {
                cell.classList.add('is-today');
            }

            cell.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                viewMonth = mIdx;
                currentView = 'days';
                renderCalendar();
            };

            grid.appendChild(cell);
        });

        popover.appendChild(grid);

        // Footer in Month View
        const footer = document.createElement('div');
        footer.className = 'custom-picker-footer';

        const curMonthBtn = document.createElement('button');
        curMonthBtn.type = 'button';
        curMonthBtn.className = 'custom-picker-action-btn action-today';
        curMonthBtn.textContent = 'Bulan Ini';
        curMonthBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            viewMonth = now.getMonth();
            viewYear = now.getFullYear();
            currentView = 'days';
            renderCalendar();
        };

        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'custom-picker-action-btn action-close';
        backBtn.textContent = 'Kembali';
        backBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            currentView = 'days';
            renderCalendar();
        };

        footer.append(curMonthBtn, backBtn);
        popover.appendChild(footer);
    };

    // Render Controller
    const renderCalendar = () => {
        popover.innerHTML = '';

        if (currentView === 'years') {
            renderYearView();
        } else if (currentView === 'months') {
            renderMonthView();
        } else {
            renderDayView();
        }

        if (wrapper.classList.contains('is-open')) {
            adjustPickerPosition(wrapper, trigger);
            ensurePickerInView(wrapper);
        }
    };

    // References
    wrapper._popover = popover;
    popover._wrapper = wrapper;
    popover._trigger = trigger;

    const openPopover = () => {
        closeAllCustomPickers(wrapper);
        if (typeof window.closeAllCustomSelects === 'function') {
            window.closeAllCustomSelects();
        }
        lastOpenedAt = Date.now();
        currentView = 'days';
        yearGridStart = Math.floor(viewYear / 12) * 12;
        wrapper.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');

        const inModal = Boolean(wrapper.closest('.simasadi-modal'));
        if (inModal) {
            document.body.appendChild(popover);
            popover.classList.add('is-teleported');
        }

        elevateParents(wrapper, true);
        renderCalendar();
    };

    const closePopover = () => {
        wrapper.classList.remove('is-open', 'dropup', 'align-right');
        if (popover.classList.contains('is-teleported')) {
            wrapper.appendChild(popover);
            popover.classList.remove('is-teleported', 'dropup');
        }
        popover.style.position = '';
        popover.style.top = '';
        popover.style.left = '';
        popover.style.right = '';
        popover.style.bottom = '';
        popover.style.zIndex = '';
        trigger.setAttribute('aria-expanded', 'false');
        elevateParents(wrapper, false);
    };

    const togglePopover = () => {
        if (wrapper.classList.contains('is-open')) {
            closePopover();
        } else {
            openPopover();
        }
    };

    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (typeof window.closeAllCustomSelects === 'function') {
            window.closeAllCustomSelects();
        }
        togglePopover();
    });

    popover.addEventListener('click', (e) => e.stopPropagation());
    popover.addEventListener('mousedown', (e) => e.stopPropagation());

    const parentLabel = input.closest('label');
    if (parentLabel) {
        parentLabel.addEventListener('click', (e) => {
            if (e.target.closest('.custom-picker-popover') || e.target.closest('.custom-picker-trigger')) {
                if (e.target.closest('.custom-picker-popover')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });
    }

    input.addEventListener('change', () => syncFromNative());
    input.addEventListener('input', () => syncFromNative());
    input.form?.addEventListener('reset', () => setTimeout(syncFromNative, 20));

    wrapper._customPickerInstance = true;
    wrapper._customPickerToggle = togglePopover;
    wrapper._customPickerOpen = openPopover;
    wrapper._customPickerClose = closePopover;

    attachValueInterceptor(input, () => syncFromNative());

    syncFromNative();
};

// ==========================================================================
// 2. CUSTOM CLOCK PICKER (Analog Dial + Digital Readout)
// ==========================================================================
const createCustomClockPicker = (input) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'custom-clockpicker-wrapper';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    input.classList.add('custom-picker-native');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'custom-picker-trigger custom-clockpicker-trigger';
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', 'false');

    const valueSpan = document.createElement('span');
    valueSpan.className = 'custom-picker-value';

    const iconSpan = document.createElement('span');
    iconSpan.className = 'custom-picker-icon';
    iconSpan.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
    `;

    trigger.append(valueSpan, iconSpan);
    wrapper.appendChild(trigger);

    // Popover
    const popover = document.createElement('div');
    popover.className = 'custom-picker-popover custom-clockpicker-popover';
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', 'Pilih Jam');
    wrapper.appendChild(popover);

    let activeMode = 'hours'; // 'hours' or 'minutes'
    let selectedHour = 9;
    let selectedMinute = 0;
    let hasValue = false;

    const parseInputValue = (val) => {
        if (!val || typeof val !== 'string') return null;
        const parts = val.split(':');
        if (parts.length >= 2) {
            const h = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10);
            if (!isNaN(h) && !isNaN(m) && h >= 0 && h <= 23 && m >= 0 && m <= 59) {
                return { h, m };
            }
        }
        return null;
    };

    const formatDisplay = (h, m, set) => {
        if (!set) return '<span class="is-placeholder">Pilih jam...</span>';
        const strH = String(h).padStart(2, '0');
        const strM = String(m).padStart(2, '0');
        return `<span>${strH}:${strM} <small class="clock-tz">WIB</small></span>`;
    };

    const formatIsoTime = (h, m) => {
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    };

    const syncFromNative = () => {
        const parsed = parseInputValue(input.value);
        if (parsed) {
            selectedHour = parsed.h;
            selectedMinute = parsed.m;
            hasValue = true;
        } else {
            hasValue = false;
        }
        valueSpan.innerHTML = formatDisplay(selectedHour, selectedMinute, hasValue);
        renderClock();
    };

    const applyTime = (h, m) => {
        selectedHour = h;
        selectedMinute = m;
        hasValue = true;
        const timeStr = formatIsoTime(h, m);
        if (input.value !== timeStr) {
            input.value = timeStr;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        valueSpan.innerHTML = formatDisplay(h, m, true);
    };

    const clearTime = () => {
        hasValue = false;
        if (input.value !== '') {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        valueSpan.innerHTML = formatDisplay(selectedHour, selectedMinute, false);
        closePopover();
        trigger.focus();
    };

    // Render Analog Clock UI
    const renderClock = () => {
        popover.innerHTML = '';

        // 1. Digital Time Display Header
        const header = document.createElement('div');
        header.className = 'custom-clockpicker-header';

        const hourDigit = document.createElement('button');
        hourDigit.type = 'button';
        hourDigit.className = `clock-digit clock-hour-btn ${activeMode === 'hours' ? 'is-active' : ''}`;
        hourDigit.textContent = String(selectedHour).padStart(2, '0');
        hourDigit.onclick = (e) => {
            e.stopPropagation();
            activeMode = 'hours';
            renderClock();
        };

        const colon = document.createElement('span');
        colon.className = 'clock-colon';
        colon.textContent = ':';

        const minuteDigit = document.createElement('button');
        minuteDigit.type = 'button';
        minuteDigit.className = `clock-digit clock-minute-btn ${activeMode === 'minutes' ? 'is-active' : ''}`;
        minuteDigit.textContent = String(selectedMinute).padStart(2, '0');
        minuteDigit.onclick = (e) => {
            e.stopPropagation();
            activeMode = 'minutes';
            renderClock();
        };

        const tzBadge = document.createElement('span');
        tzBadge.className = 'clock-tz-badge';
        tzBadge.textContent = 'WIB';

        header.append(hourDigit, colon, minuteDigit, tzBadge);
        popover.appendChild(header);

        // 2. Analog Dial Container
        const dial = document.createElement('div');
        dial.className = 'custom-clockpicker-dial';
        dial.setAttribute('role', 'application');
        dial.setAttribute('aria-label', activeMode === 'hours' ? 'Pilih Jam' : 'Pilih Menit');

        const centerPoint = 110; // 220px total size
        const radiusOuter = 82;
        const radiusInner = 52;

        // Calculate Angle and Hand Length
        let currentAngle = 0;
        let isInnerRadius = false;

        if (activeMode === 'hours') {
            const h = selectedHour;
            currentAngle = (h % 12) * 30; // 30 deg per hour
            isInnerRadius = (h === 0 || h >= 13);
        } else {
            currentAngle = selectedMinute * 6; // 6 deg per minute
            isInnerRadius = false;
        }

        const handLength = isInnerRadius ? radiusInner : radiusOuter;

        // Clock Hand Element
        const hand = document.createElement('div');
        hand.className = 'clock-hand';
        hand.style.height = `${handLength}px`;
        hand.style.transform = `translate(-50%, -100%) rotate(${currentAngle}deg)`;

        // Center Pivot
        const pivot = document.createElement('div');
        pivot.className = 'clock-pivot';
        dial.appendChild(pivot);
        dial.appendChild(hand);

        // Numbers on Dial
        if (activeMode === 'hours') {
            // Outer Ring: 1 to 12
            for (let i = 1; i <= 12; i++) {
                const angleRad = (i * 30 - 90) * (Math.PI / 180);
                const x = centerPoint + radiusOuter * Math.cos(angleRad);
                const y = centerPoint + radiusOuter * Math.sin(angleRad);

                const numEl = document.createElement('div');
                numEl.className = 'clock-number clock-number-outer';
                if (selectedHour === i) numEl.classList.add('is-selected');
                numEl.style.left = `${x}px`;
                numEl.style.top = `${y}px`;
                numEl.textContent = i;
                dial.appendChild(numEl);
            }

            // Inner Ring: 13 to 23, and 00
            const innerHours = [13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 0];
            innerHours.forEach((num, idx) => {
                const hourStep = idx + 1; // 13 is at position 1 (30 deg), 00 is at position 12 (360 deg)
                const angleRad = (hourStep * 30 - 90) * (Math.PI / 180);
                const x = centerPoint + radiusInner * Math.cos(angleRad);
                const y = centerPoint + radiusInner * Math.sin(angleRad);

                const numEl = document.createElement('div');
                numEl.className = 'clock-number clock-number-inner';
                if (selectedHour === num) numEl.classList.add('is-selected');
                numEl.style.left = `${x}px`;
                numEl.style.top = `${y}px`;
                numEl.textContent = String(num).padStart(2, '0');
                dial.appendChild(numEl);
            });
        } else {
            // Minutes Dial: 00, 05, 10, ... 55
            for (let i = 0; i < 60; i += 5) {
                const angleRad = (i * 6 - 90) * (Math.PI / 180);
                const x = centerPoint + radiusOuter * Math.cos(angleRad);
                const y = centerPoint + radiusOuter * Math.sin(angleRad);

                const numEl = document.createElement('div');
                numEl.className = 'clock-number clock-number-outer';
                if (selectedMinute === i) numEl.classList.add('is-selected');
                numEl.style.left = `${x}px`;
                numEl.style.top = `${y}px`;
                numEl.textContent = String(i).padStart(2, '0');
                dial.appendChild(numEl);
            }
        }

        // Pointer Click & Drag Interaction
        const handleDialInteraction = (clientX, clientY, isFinal = false) => {
            const rect = dial.getBoundingClientRect();
            const dialX = clientX - rect.left;
            const dialY = clientY - rect.top;

            const dx = dialX - centerPoint;
            const dy = dialY - centerPoint;
            const distance = Math.sqrt(dx * dx + dy * dy);

            let angle = Math.atan2(dy, dx) * (180 / Math.PI) + 90;
            if (angle < 0) angle += 360;

            if (activeMode === 'hours') {
                const isInner = distance < 68;
                let hourSlot = Math.round(angle / 30) % 12; // 0..11
                if (hourSlot === 0) hourSlot = 12;

                let targetHour = isInner ? (hourSlot === 12 ? 0 : hourSlot + 12) : hourSlot;
                selectedHour = targetHour;
                applyTime(selectedHour, selectedMinute);

                // Auto-advance to minute mode after clicking hour
                if (isFinal) {
                    setTimeout(() => {
                        activeMode = 'minutes';
                        renderClock();
                    }, 180);
                } else {
                    renderClock();
                }
            } else {
                let targetMinute = Math.round(angle / 6) % 60;
                selectedMinute = targetMinute;
                applyTime(selectedHour, selectedMinute);
                renderClock();
            }
        };

        let isDragging = false;

        dial.onmousedown = (e) => {
            e.preventDefault();
            e.stopPropagation();
            isDragging = true;
            handleDialInteraction(e.clientX, e.clientY, false);

            const onMouseMove = (moveEvent) => {
                if (!isDragging) return;
                handleDialInteraction(moveEvent.clientX, moveEvent.clientY, false);
            };

            const onMouseUp = (upEvent) => {
                if (!isDragging) return;
                isDragging = false;
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                handleDialInteraction(upEvent.clientX, upEvent.clientY, true);
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        };

        dial.ontouchstart = (e) => {
            if (!e.touches.length) return;
            e.preventDefault();
            e.stopPropagation();
            isDragging = true;
            const t = e.touches[0];
            handleDialInteraction(t.clientX, t.clientY, false);

            const onTouchMove = (moveEvent) => {
                if (!isDragging || !moveEvent.touches.length) return;
                const touch = moveEvent.touches[0];
                handleDialInteraction(touch.clientX, touch.clientY, false);
            };

            const onTouchEnd = (endEvent) => {
                isDragging = false;
                document.removeEventListener('touchmove', onTouchMove);
                document.removeEventListener('touchend', onTouchEnd);
                const touch = endEvent.changedTouches[0];
                if (touch) {
                    handleDialInteraction(touch.clientX, touch.clientY, true);
                }
            };

            document.addEventListener('touchmove', onTouchMove, { passive: false });
            document.addEventListener('touchend', onTouchEnd, { passive: false });
        };

        popover.appendChild(dial);

        // 3. Quick Preset Chips
        const presetBar = document.createElement('div');
        presetBar.className = 'custom-clockpicker-presets';

        const PRESETS = ['08:00', '09:00', '10:00', '13:00', '14:00', '16:00'];
        PRESETS.forEach((preset) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'clock-preset-chip';
            chip.textContent = preset;
            const [ph, pm] = preset.split(':').map(Number);
            if (hasValue && selectedHour === ph && selectedMinute === pm) {
                chip.classList.add('is-active');
            }
            chip.onclick = (e) => {
                e.stopPropagation();
                applyTime(ph, pm);
                renderClock();
            };
            presetBar.appendChild(chip);
        });

        popover.appendChild(presetBar);

        // 4. Action Footer: "Sekarang", "Hapus", "Selesai"
        const footer = document.createElement('div');
        footer.className = 'custom-picker-footer';

        const nowBtn = document.createElement('button');
        nowBtn.type = 'button';
        nowBtn.className = 'custom-picker-action-btn action-today';
        nowBtn.textContent = 'Sekarang';
        nowBtn.onclick = (e) => {
            e.stopPropagation();
            const cur = new Date();
            applyTime(cur.getHours(), cur.getMinutes());
            renderClock();
        };

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'custom-picker-action-btn action-clear';
        clearBtn.textContent = 'Hapus';
        clearBtn.onclick = (e) => {
            e.stopPropagation();
            clearTime();
        };

        const doneBtn = document.createElement('button');
        doneBtn.type = 'button';
        doneBtn.className = 'custom-picker-action-btn action-close';
        doneBtn.textContent = 'Selesai';
        doneBtn.onclick = (e) => {
            e.stopPropagation();
            if (!hasValue) {
                applyTime(selectedHour, selectedMinute);
            }
            closePopover();
            trigger.focus();
        };

        footer.append(nowBtn, clearBtn, doneBtn);
        popover.appendChild(footer);

        if (wrapper.classList.contains('is-open')) {
            adjustPickerPosition(wrapper, trigger);
            ensurePickerInView(wrapper);
        }
    };

    // References
    wrapper._popover = popover;
    popover._wrapper = wrapper;
    popover._trigger = trigger;

    const openPopover = () => {
        closeAllCustomPickers(wrapper);
        if (typeof window.closeAllCustomSelects === 'function') {
            window.closeAllCustomSelects();
        }
        lastOpenedAt = Date.now();
        wrapper.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');

        const inModal = Boolean(wrapper.closest('.simasadi-modal'));
        if (inModal) {
            document.body.appendChild(popover);
            popover.classList.add('is-teleported');
        }

        elevateParents(wrapper, true);
        renderClock();
    };

    const closePopover = () => {
        wrapper.classList.remove('is-open', 'dropup', 'align-right');
        if (popover.classList.contains('is-teleported')) {
            wrapper.appendChild(popover);
            popover.classList.remove('is-teleported', 'dropup');
        }
        popover.style.position = '';
        popover.style.top = '';
        popover.style.left = '';
        popover.style.right = '';
        popover.style.bottom = '';
        popover.style.zIndex = '';
        trigger.setAttribute('aria-expanded', 'false');
        elevateParents(wrapper, false);
    };

    const togglePopover = () => {
        if (wrapper.classList.contains('is-open')) {
            closePopover();
        } else {
            openPopover();
        }
    };

    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (typeof window.closeAllCustomSelects === 'function') {
            window.closeAllCustomSelects();
        }
        togglePopover();
    });

    popover.addEventListener('click', (e) => e.stopPropagation());
    popover.addEventListener('mousedown', (e) => e.stopPropagation());

    const parentLabel = input.closest('label');
    if (parentLabel) {
        parentLabel.addEventListener('click', (e) => {
            if (e.target.closest('.custom-picker-popover') || e.target.closest('.custom-picker-trigger')) {
                if (e.target.closest('.custom-picker-popover')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });
    }

    input.addEventListener('change', () => syncFromNative());
    input.addEventListener('input', () => syncFromNative());
    input.form?.addEventListener('reset', () => setTimeout(syncFromNative, 20));

    wrapper._customPickerInstance = true;
    wrapper._customPickerToggle = togglePopover;
    wrapper._customPickerOpen = openPopover;
    wrapper._customPickerClose = closePopover;

    attachValueInterceptor(input, () => syncFromNative());

    syncFromNative();
};

// ==========================================================================
// Initialization & Export Helpers
// ==========================================================================
export const initCustomDatePickers = (container = document) => {
    setupGlobalPickerListeners();

    // Self-healing: unwrap stale datepicker wrappers if missing active JS instance
    container.querySelectorAll('.custom-datepicker-wrapper').forEach((wrapper) => {
        if (!wrapper._customPickerInstance) {
            const nativeInput = wrapper.querySelector('input.custom-picker-native') || wrapper.querySelector('input[type="date"]');
            if (nativeInput) {
                nativeInput.classList.remove('custom-picker-native');
                delete nativeInput.dataset.customPickerInit;
                wrapper.parentNode.insertBefore(nativeInput, wrapper);
            }
            wrapper.remove();
        }
    });

    const inputs = container.querySelectorAll('input[type="date"]:not([data-custom-picker-init])');
    inputs.forEach((input) => {
        if (input.dataset.noCustom !== undefined) return;
        input.dataset.customPickerInit = 'true';
        createCustomDatePicker(input);
    });
};

export const initCustomClockPickers = (container = document) => {
    setupGlobalPickerListeners();

    // Self-healing: unwrap stale clockpicker wrappers if missing active JS instance
    container.querySelectorAll('.custom-clockpicker-wrapper').forEach((wrapper) => {
        if (!wrapper._customPickerInstance) {
            const nativeInput = wrapper.querySelector('input.custom-picker-native') || wrapper.querySelector('input[type="time"]');
            if (nativeInput) {
                nativeInput.classList.remove('custom-picker-native');
                delete nativeInput.dataset.customPickerInit;
                wrapper.parentNode.insertBefore(nativeInput, wrapper);
            }
            wrapper.remove();
        }
    });

    const inputs = container.querySelectorAll('input[type="time"]:not([data-custom-picker-init])');
    inputs.forEach((input) => {
        if (input.dataset.noCustom !== undefined) return;
        input.dataset.customPickerInit = 'true';
        createCustomClockPicker(input);
    });
};

export const initCustomPickers = (container = document) => {
    initCustomDatePickers(container);
    initCustomClockPickers(container);
};

export const syncCustomPickers = (container = document) => {
    container.querySelectorAll('.custom-datepicker-wrapper, .custom-clockpicker-wrapper').forEach((w) => {
        const input = w.querySelector('input.custom-picker-native');
        if (input) {
            input.dispatchEvent(new Event('change', { bubbles: false }));
        }
    });
};

export { closeAllCustomPickers };

if (typeof window !== 'undefined') {
    window.initCustomPickers = initCustomPickers;
    window.closeAllCustomPickers = closeAllCustomPickers;
}
