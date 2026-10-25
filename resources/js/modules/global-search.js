/**
 * Global Search Module - Live Search & Interactive Dropdown
 * Handles debounced queries, keyboard navigation, loading indicators, and SPA routing.
 */

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatStatus(status) {
    if (!status) return '';
    const labels = {
        'ACTIVE': 'Aktif',
        'INACTIVE': 'Tidak Aktif',
        'SUSPENDED': 'Dibekukan',
        'PLANNED': 'Direncanakan',
        'SCHEDULED': 'Terjadwal',
        'IN_PROGRESS': 'Dalam Proses',
        'COMPLETED': 'Selesai',
        'REVOKED': 'Dicabut',
    };
    return labels[status.toUpperCase()] || status.replace('_', ' ');
}

export function initGlobalSearch() {
    const container = document.getElementById('topbar-search-container');
    const input = document.getElementById('topbar-search-input');
    const dropdown = document.getElementById('topbar-search-dropdown');
    const spinner = document.getElementById('topbar-search-spinner');
    const clearBtn = document.getElementById('topbar-search-clear');
    const searchIcon = container ? container.querySelector('.topbar-search-icon') : null;
    const mobileTrigger = document.getElementById('topbar-search-mobile-trigger');
    const mobileClose = document.getElementById('topbar-search-mobile-close');
    const mobileBackdrop = document.getElementById('topbar-search-mobile-backdrop');

    if (!container || !input || !dropdown) {
        return;
    }

    if (container.dataset.searchInitialized === 'true') {
        return;
    }
    container.dataset.searchInitialized = 'true';

    let debounceTimer = null;
    let abortController = null;
    let selectedIndex = -1;
    let items = [];
    let currentViewAllUrl = null;

    function openDropdown() {
        dropdown.style.display = 'block';
        input.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
        dropdown.style.display = 'none';
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        selectedIndex = -1;
        currentViewAllUrl = null;
        updateItemSelection();
    }

    function openMobileSearch() {
        container.classList.remove('is-mobile-closing');
        container.classList.add('is-mobile-open');
        const topbar = document.querySelector('.topbar');
        if (topbar) {
            topbar.classList.add('has-mobile-search-open');
        }
        if (mobileTrigger) {
            mobileTrigger.setAttribute('aria-expanded', 'true');
            mobileTrigger.classList.add('is-active');
        }
        if (mobileBackdrop) {
            const topbarHeight = topbar ? topbar.offsetHeight : 56;
            mobileBackdrop.style.top = `${topbarHeight}px`;
            mobileBackdrop.style.display = 'block';
            mobileBackdrop.classList.remove('is-closing');
        }
        setTimeout(() => {
            input.focus();
            const query = input.value.trim();
            if (clearBtn) {
                clearBtn.style.display = query ? 'inline-flex' : 'none';
            }
            if (query.length >= 2 && dropdown.innerHTML.trim() !== '') {
                openDropdown();
            }
        }, 50);
    }

    function closeMobileSearch() {
        if (!container.classList.contains('is-mobile-open')) return;
        container.classList.add('is-mobile-closing');
        const topbar = document.querySelector('.topbar');
        if (mobileTrigger) {
            mobileTrigger.classList.remove('is-active');
        }
        if (mobileBackdrop) {
            mobileBackdrop.classList.add('is-closing');
        }
        closeDropdown();
        setTimeout(() => {
            container.classList.remove('is-mobile-open', 'is-mobile-closing');
            if (topbar) {
                topbar.classList.remove('has-mobile-search-open');
            }
            if (mobileBackdrop) {
                mobileBackdrop.classList.remove('is-closing');
                mobileBackdrop.style.display = 'none';
            }
            if (mobileTrigger) {
                mobileTrigger.setAttribute('aria-expanded', 'false');
                mobileTrigger.focus();
            }
        }, 170);
    }

    function setLoading(isLoading) {
        if (spinner) spinner.style.display = isLoading ? 'inline-block' : 'none';
        if (searchIcon) searchIcon.style.display = isLoading ? 'none' : 'inline-block';
    }

    function updateItemSelection() {
        items.forEach((item, idx) => {
            if (idx === selectedIndex) {
                item.classList.add('is-selected');
                item.setAttribute('aria-selected', 'true');
                item.scrollIntoView({ block: 'nearest' });
                if (item.id) {
                    input.setAttribute('aria-activedescendant', item.id);
                }
            } else {
                item.classList.remove('is-selected');
                item.removeAttribute('aria-selected');
            }
        });

        if (selectedIndex === -1) {
            input.removeAttribute('aria-activedescendant');
        }
    }

    function renderResults(data, query) {
        if (data.total === 0) {
            dropdown.innerHTML = `
                <div class="topbar-search-empty">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-icon"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                    <p class="empty-text">Tidak ditemukan hasil untuk <strong>"${escapeHtml(query)}"</strong></p>
                    <small class="empty-sub">Coba kata kunci lain atau periksa ejaan nama laboratorium / nomor registrasi.</small>
                </div>
            `;
            openDropdown();
            items = [];
            selectedIndex = -1;
            return;
        }

        let html = '';
        let itemIndex = 0;

        // 1. Group LPK
        if (data.lpks && data.lpks.length > 0) {
            html += `<div class="topbar-search-group"><div class="topbar-search-group-title">Laboratorium (${data.lpks.length})</div>`;
            data.lpks.forEach(lpk => {
                html += `
                    <a href="${lpk.url}" id="topbar-search-item-${itemIndex}" class="topbar-search-item" data-index="${itemIndex++}" role="option">
                        <div class="topbar-search-item-icon lpk-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                        </div>
                        <div class="topbar-search-item-info">
                            <div class="topbar-search-item-primary">
                                <strong class="item-title">${escapeHtml(lpk.name)}</strong>
                                <span class="status status-${lpk.status.toLowerCase()}">${formatStatus(lpk.status)}</span>
                            </div>
                            <div class="topbar-search-item-sub">
                                <span class="mono-tag">${escapeHtml(lpk.reg)}</span>
                                <span class="sep">&bull;</span>
                                <span>${escapeHtml(lpk.type)}</span>
                            </div>
                        </div>
                    </a>
                `;
            });
            html += `</div>`;
        }

        // 2. Group Asesmen
        if (data.assessments && data.assessments.length > 0) {
            html += `<div class="topbar-search-group"><div class="topbar-search-group-title">Asesmen (${data.assessments.length})</div>`;
            data.assessments.forEach(assessment => {
                html += `
                    <a href="${assessment.url}" id="topbar-search-item-${itemIndex}" class="topbar-search-item" data-index="${itemIndex++}" role="option">
                        <div class="topbar-search-item-icon assessment-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="8" y="2" rx="1"/><path d="m9 14 2 2 4-4"/></svg>
                        </div>
                        <div class="topbar-search-item-info">
                            <div class="topbar-search-item-primary">
                                <strong class="item-title">${escapeHtml(assessment.title)}</strong>
                                <span class="status status-${assessment.status.toLowerCase()}">${formatStatus(assessment.status)}</span>
                            </div>
                            <div class="topbar-search-item-sub">
                                <span>${escapeHtml(assessment.lpk_name)}</span>
                                <span class="sep">&bull;</span>
                                <span>${escapeHtml(assessment.date)}</span>
                            </div>
                        </div>
                    </a>
                `;
            });
            html += `</div>`;
        }

        // 3. Group Jadwal Asesmen (Kalender)
        if (data.schedules && data.schedules.length > 0) {
            html += `<div class="topbar-search-group"><div class="topbar-search-group-title">Jadwal Kalender (${data.schedules.length})</div>`;
            data.schedules.forEach(schedule => {
                html += `
                    <a href="${schedule.url}" id="topbar-search-item-${itemIndex}" class="topbar-search-item" data-index="${itemIndex++}" role="option">
                        <div class="topbar-search-item-icon schedule-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        </div>
                        <div class="topbar-search-item-info">
                            <div class="topbar-search-item-primary">
                                <strong class="item-title">${escapeHtml(schedule.title)}</strong>
                                <span class="status status-${schedule.status.toLowerCase()}">${formatStatus(schedule.status)}</span>
                            </div>
                            <div class="topbar-search-item-sub">
                                <span>${escapeHtml(schedule.lpk_name)}</span>
                                <span class="sep">&bull;</span>
                                <span>${escapeHtml(schedule.date)}</span>
                            </div>
                        </div>
                    </a>
                `;
            });
            html += `</div>`;
        }

        // 4. Group Accounts
        if (data.accounts && data.accounts.length > 0) {
            html += `<div class="topbar-search-group"><div class="topbar-search-group-title">Tautan Akun (${data.accounts.length})</div>`;
            data.accounts.forEach(account => {
                html += `
                    <a href="${account.url}" id="topbar-search-item-${itemIndex}" class="topbar-search-item" data-index="${itemIndex++}" role="option">
                        <div class="topbar-search-item-icon user-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="topbar-search-item-info">
                            <div class="topbar-search-item-primary">
                                <strong class="item-title">${escapeHtml(account.name)}</strong>
                                <span class="badge-role">${escapeHtml(account.role_label)}</span>
                            </div>
                            <div class="topbar-search-item-sub">
                                <span>${escapeHtml(account.email)}</span>
                            </div>
                        </div>
                    </a>
                `;
            });
            html += `</div>`;
        }

        // 5. Footer "Lihat semua hasil"
        currentViewAllUrl = data.view_all_url || null;

        if (data.view_all_links && data.view_all_links.length > 1) {
            html += `
                <div class="topbar-search-footer">
                    <div class="topbar-search-footer-header">
                        <span class="topbar-search-footer-title">Lihat semua hasil:</span>
                    </div>
                    <div class="topbar-search-footer-links">
                        ${data.view_all_links.map(link => `
                            <a href="${link.url}" class="topbar-search-footer-chip">
                                <span>${escapeHtml(link.label)}</span>
                                <span class="chip-count">${link.count}</span>
                            </a>
                        `).join('')}
                    </div>
                </div>
            `;
        } else if (data.view_all_url) {
            const footerLabel = data.view_all_label || 'Lihat semua hasil';
            html += `
                <div class="topbar-search-footer">
                    <a href="${data.view_all_url}" class="topbar-search-view-all">
                        <span>${escapeHtml(footerLabel)}</span>
                    </a>
                </div>
            `;
        }

        dropdown.innerHTML = html;
        openDropdown();
        items = Array.from(dropdown.querySelectorAll('.topbar-search-item'));
        selectedIndex = -1;
    }

    async function executeSearch(query) {
        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();
        setLoading(true);

        try {
            const response = await fetch(`/search/live?q=${encodeURIComponent(query)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: abortController.signal
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            renderResults(data, query);
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Error fetching live search:', err);
                closeDropdown();
            }
        } finally {
            setLoading(false);
        }
    }

    // Input Typing Handler with Debounce (250ms)
    input.addEventListener('input', () => {
        const query = input.value.trim();

        if (clearBtn) {
            clearBtn.style.display = query ? 'inline-flex' : 'none';
        }

        if (query.length < 2) {
            closeDropdown();
            if (abortController) abortController.abort();
            setLoading(false);
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            executeSearch(query);
        }, 250);
    });

    // Clear Button Handler
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = '';
            clearBtn.style.display = 'none';
            closeDropdown();
            input.focus();
        });
    }

    // Input Focus Handler
    input.addEventListener('focus', () => {
        const query = input.value.trim();
        if (clearBtn) {
            clearBtn.style.display = query ? 'inline-flex' : 'none';
        }
        if (query.length >= 2 && dropdown.innerHTML.trim() !== '') {
            openDropdown();
        }
    });

    // Keyboard Navigation
    input.addEventListener('keydown', (e) => {
        const isDropdownVisible = dropdown.style.display !== 'none';

        if (e.key === 'ArrowDown') {
            if (!isDropdownVisible || items.length === 0) return;
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % items.length;
            updateItemSelection();
        } else if (e.key === 'ArrowUp') {
            if (!isDropdownVisible || items.length === 0) return;
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            updateItemSelection();
        } else if (e.key === 'Enter') {
            if (isDropdownVisible && selectedIndex >= 0 && items[selectedIndex]) {
                e.preventDefault();
                const targetUrl = items[selectedIndex].getAttribute('href');
                closeDropdown();
                if (typeof window.navigateTo === 'function') {
                    window.navigateTo(targetUrl);
                } else {
                    window.location.href = targetUrl;
                }
            } else if (isDropdownVisible && currentViewAllUrl) {
                e.preventDefault();
                const targetUrl = currentViewAllUrl;
                closeDropdown();
                if (typeof window.navigateTo === 'function') {
                    window.navigateTo(targetUrl);
                } else {
                    window.location.href = targetUrl;
                }
            }
            // Otherwise let form submit normally
        } else if (e.key === 'Escape') {
            if (isDropdownVisible) {
                e.preventDefault();
                closeDropdown();
            } else if (container.classList.contains('is-mobile-open')) {
                e.preventDefault();
                closeMobileSearch();
            }
        }
    });

    // Mobile Trigger & Close Handlers
    if (mobileTrigger) {
        mobileTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (container.classList.contains('is-mobile-open')) {
                closeMobileSearch();
            } else {
                openMobileSearch();
            }
        });
    }

    if (mobileClose) {
        mobileClose.addEventListener('click', (e) => {
            e.stopPropagation();
            closeMobileSearch();
        });
    }

    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', () => {
            closeMobileSearch();
        });
    }

    // Handle clicks inside dropdown (SPA navigation)
    dropdown.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.getAttribute('href')) {
            const url = link.getAttribute('href');
            closeDropdown();
            closeMobileSearch();
            if (typeof window.navigateTo === 'function' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                window.navigateTo(url);
            }
        }
    });

    // Close dropdown and mobile search when clicking outside
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target) && (!mobileTrigger || !mobileTrigger.contains(e.target))) {
            closeDropdown();
            if (container.classList.contains('is-mobile-open')) {
                closeMobileSearch();
            }
        }
    });

    // Responsive Window Resize Handler
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900 && container.classList.contains('is-mobile-open')) {
            container.classList.remove('is-mobile-open', 'is-mobile-closing');
            if (mobileBackdrop) {
                mobileBackdrop.classList.remove('is-closing');
                mobileBackdrop.style.display = 'none';
            }
            if (mobileTrigger) {
                mobileTrigger.setAttribute('aria-expanded', 'false');
            }
        }
    });
}
