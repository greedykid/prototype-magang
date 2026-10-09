import { setDrawerState } from './sidebar.js';
import { returnModalToPlaceholder, closeModal } from './modals.js';
import { returnPopoverToPlaceholder, closeEventPopover } from './calendar.js';
import { closeAllCustomPickers } from './custom-picker.js';
import { renderSkeletonForType } from './spa/skeletons.js';
import { getPageTypeAndTitle } from './spa/route-matcher.js';
import {
    PAGE_CACHE_TTL,
    pageCache,
    prefetchInFlight,
    clearPageCache,
    clearCalendarCache,
    getCalendarCacheKey,
    getCleanPageUrl,
    cacheCurrentPage,
    prefetchCalendarPartial,
    scheduleAdjacentCalendarPrefetch,
    scheduleNavigationPrewarm,
    isCacheableUrl,
    prefetchUrl
} from './spa/router-cache.js';
import { handleCalendarTransition } from './spa/calendar-handler.js';
import { syncBottomNavActive, setBottomSheetState } from './bottom-nav.js';

let currentNavSequence = 0;
let activeNavAbortController = null;
let isNavigating = false;
let onNavigateCallback = null;

/**
 * Clean up active popovers, modals, and custom pickers prior to navigation
 */
const cleanupActiveOverlays = () => {
    if (typeof closeEventPopover === 'function') closeEventPopover();
    else window.closeEventPopover?.();

    if (document.querySelector('.simasadi-modal.is-active')) {
        if (typeof closeModal === 'function') closeModal();
        else window.closeModal?.();
    }

    if (typeof closeAllCustomPickers === 'function') closeAllCustomPickers();
    else window.closeAllCustomPickers?.();

    if (typeof setBottomSheetState === 'function') setBottomSheetState(false);
    else window.setBottomSheetState?.(false);

    window.closeNotificationDropdown?.();

    document.querySelectorAll('body > .gcal-popover, body > #gcal-event-popover').forEach((p) => {
        if (typeof returnPopoverToPlaceholder === 'function') returnPopoverToPlaceholder(p);
        if (p.parentElement === document.body) {
            p.remove();
        }
    });

    document.querySelectorAll('body > .simasadi-modal').forEach((m) => {
        if (typeof returnModalToPlaceholder === 'function') returnModalToPlaceholder(m);
        if (m.parentElement === document.body) {
            m.remove();
        }
    });

    document.documentElement.classList.remove('modal-open');
    document.body.classList.remove('modal-open');
};

/**
 * Update primary navigation links active state
 */
const updateActiveNavLinks = (targetPath) => {
    const navLinks = document.querySelectorAll('#primary-navigation a');
    navLinks.forEach((link) => {
        const linkPath = new URL(link.href, window.location.origin).pathname;
        const isActive = (linkPath === '/' || linkPath === '/dashboard')
            ? (targetPath === '/' || targetPath === '/dashboard')
            : (targetPath.startsWith(linkPath) && linkPath !== '/');
        link.classList.toggle('active', isActive);
    });

    if (typeof syncBottomNavActive === 'function') {
        syncBottomNavActive(targetPath);
    } else if (typeof window.syncBottomNavActive === 'function') {
        window.syncBottomNavActive(targetPath);
    }
};

/**
 * Re-execute script tags in swapped content to keep inline scripts functioning
 */
const reexecuteScripts = (container) => {
    container.querySelectorAll('script').forEach((oldScript) => {
        const newScript = document.createElement('script');
        Array.from(oldScript.attributes).forEach((attr) => newScript.setAttribute(attr.name, attr.value));
        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
};

/**
 * Handle smooth scroll to target hash anchor if navigation included an anchor fragment
 */
const scrollToAnchorHash = (hash) => {
    if (!hash) return;
    setTimeout(() => {
        try {
            const hashId = hash.slice(1);
            const targetElement = document.getElementById(hashId) || document.querySelector(hash);
            if (targetElement) {
                const topbar = document.querySelector('.topbar');
                const topbarOffset = (topbar ? topbar.offsetHeight : 64) + 16;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
                window.scrollTo({
                    top: Math.max(0, targetPosition - topbarOffset),
                    behavior: 'smooth'
                });
            }
        } catch (_) {
            // Silently ignore selector errors
        }
    }, 60);
};

/**
 * SPA-Style Transition & Navigation Controller
 * Delivers zero-delay (0ms) instant switches across all application menus.
 */
const navigateTo = async (url, pushState = true) => {
    const pageWrap = document.querySelector('#page-content-wrapper') || document.querySelector('.page-wrap');
    if (!pageWrap) {
        window.location.href = url;
        return;
    }

    // Sequence control: cancel any in-flight navigation if user switched quickly
    const thisNavSequence = ++currentNavSequence;
    if (activeNavAbortController) {
        activeNavAbortController.abort();
    }
    activeNavAbortController = new AbortController();
    const { signal } = activeNavAbortController;

    isNavigating = true;

    try {
        cleanupActiveOverlays();

        const currentUrlObj = new URL(window.location.href);
        const targetUrlObj = new URL(url, window.location.origin);
        const isCalendarToCalendar = currentUrlObj.pathname === '/calendar' &&
                                     targetUrlObj.pathname === '/calendar' &&
                                     !targetUrlObj.pathname.includes('/create') &&
                                     !targetUrlObj.pathname.includes('/edit') &&
                                     !targetUrlObj.pathname.match(/\/calendar\/events\/\d+/);

        if (isCalendarToCalendar) {
            await handleCalendarTransition({ url, targetUrlObj, pageWrap, pushState });
            return;
        }

        // Fast menu transition across application
        const { type, title } = getPageTypeAndTitle(url);

        const contextTitle = document.querySelector('.context-title');
        if (contextTitle && title) {
            contextTitle.textContent = title;
        }

        updateActiveNavLinks(targetUrlObj.pathname);

        // Close mobile drawer if open
        if (typeof setDrawerState === 'function') {
            setDrawerState(false);
        } else if (typeof window.setDrawerState === 'function') {
            window.setDrawerState(false);
        }

        window.scrollTo({ top: 0, behavior: 'instant' });

        const originalContent = pageWrap.innerHTML;
        let skeletonRendered = false;
        let htmlText = null;
        let fetchUrlRedirect = null;

        const cleanTargetUrl = getCleanPageUrl(targetUrlObj.href);
        const cached = pageCache.get(cleanTargetUrl);

        if (cached && (Date.now() - cached.timestamp < PAGE_CACHE_TTL)) {
            htmlText = cached.htmlText;
        } else if (prefetchInFlight.has(cleanTargetUrl)) {
            htmlText = await prefetchInFlight.get(cleanTargetUrl);
        }

        if (thisNavSequence !== currentNavSequence) return;

        if (!htmlText) {
            // Debounced skeleton: Only show skeleton if fetch takes longer than 150ms
            const skeletonTimer = setTimeout(() => {
                if (thisNavSequence === currentNavSequence) {
                    skeletonRendered = true;
                    pageWrap.innerHTML = renderSkeletonForType(type);
                }
            }, 150);

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal
            });

            clearTimeout(skeletonTimer);

            if (thisNavSequence !== currentNavSequence) return;

            if (response.redirected) {
                if (skeletonRendered) {
                    pageWrap.innerHTML = originalContent;
                }
                window.location.href = response.url;
                return;
            }

            fetchUrlRedirect = response.url;
            htmlText = await response.text();
            if (isCacheableUrl(url)) {
                pageCache.set(cleanTargetUrl, {
                    htmlText,
                    timestamp: Date.now()
                });
            }
        }

        if (thisNavSequence !== currentNavSequence) return;

        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');

        const newContent = doc.querySelector('#page-content-wrapper') || doc.querySelector('.page-wrap');
        if (!newContent) {
            if (skeletonRendered) {
                pageWrap.innerHTML = originalContent;
            }
            window.location.href = fetchUrlRedirect || url;
            return;
        }

        if (doc.title) {
            document.title = doc.title;
        }

        const newContextTitle = doc.querySelector('.context-title');
        if (newContextTitle && contextTitle) {
            contextTitle.textContent = newContextTitle.textContent;
        }

        const newMetaCsrf = doc.querySelector('meta[name="csrf-token"]');
        if (newMetaCsrf) {
            const currentMeta = document.querySelector('meta[name="csrf-token"]');
            if (currentMeta) {
                currentMeta.setAttribute('content', newMetaCsrf.getAttribute('content'));
            }
        }

        pageWrap.innerHTML = newContent.innerHTML;

        const activeCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (activeCsrfToken) {
            pageWrap.querySelectorAll('input[name="_token"]').forEach(input => {
                input.value = activeCsrfToken;
            });
        }

        pageWrap.classList.remove('page-enter-active');
        void pageWrap.offsetWidth;
        pageWrap.classList.add('page-enter-active');

        if (pushState) {
            window.history.pushState({ url }, '', url);
        }

        reexecuteScripts(pageWrap);

        if (typeof onNavigateCallback === 'function') {
            onNavigateCallback();
        } else if (typeof window.initPageComponents === 'function') {
            window.initPageComponents();
        }

        window.dispatchEvent(new CustomEvent('simasadi:page-loaded'));

        // Schedule proactive idle prewarm for adjacent menus
        scheduleNavigationPrewarm();

        scrollToAnchorHash(targetUrlObj.hash);
    } catch (err) {
        if (err.name === 'AbortError' || thisNavSequence !== currentNavSequence) {
            // Superseded by newer navigation request, ignore silently
            return;
        }
        console.error('Page transition error, falling back:', err);
        window.location.href = url;
    } finally {
        if (thisNavSequence === currentNavSequence) {
            document.querySelector('.gcal-shell')?.classList.remove('gcal-is-updating');
            isNavigating = false;
            activeNavAbortController = null;
        }
    }
};

let routerInitialized = false;
const initSpaRouter = (callback) => {
    if (callback) {
        onNavigateCallback = callback;
    }

    if (routerInitialized) return;
    routerInitialized = true;

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

        if (link.closest('.lpk-table-container .pagination, .lpk-table-container nav[role="navigation"], .assessment-table-container .pagination, .assessment-table-container nav[role="navigation"], .user-table-container .pagination, .user-table-container nav[role="navigation"]')) return;

        event.preventDefault();
        navigateTo(link.href);
    });

    // Intercept GET filter forms for smooth skeleton transitions
    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const form = event.target.closest('form');
        if (!form || form.method.toUpperCase() !== 'GET') return;
        if (form.hasAttribute('data-partial-filter')) return;

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

    // Prefetch on hover (pointerenter), touchstart, and pointerdown (instant mousedown trigger)
    const handlePrefetch = (event) => {
        const link = event.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (link.closest('form')) return;

        try {
            const url = new URL(link.href, window.location.origin);
            if (url.pathname === '/calendar') {
                prefetchCalendarPartial(link.href);
            } else {
                prefetchUrl(link.href);
            }
        } catch {
            // Silently ignore invalid URLs
        }
    };

    document.addEventListener('pointerenter', handlePrefetch, { passive: true, capture: true });
    document.addEventListener('pointerdown', handlePrefetch, { passive: true, capture: true });
    document.addEventListener('touchstart', handlePrefetch, { passive: true, capture: true });

    // Invalidate caches and refresh token when mutative form submits
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form');
        if (form && form.method.toUpperCase() !== 'GET') {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                const tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput && tokenInput.value !== token) {
                    tokenInput.value = token;
                }
            }
            clearPageCache();
            clearCalendarCache();
        }
    }, { capture: true });

    // Support browser Back and Forward buttons
    window.addEventListener('popstate', () => {
        window.closeEventPopover?.();
        window.closeModal?.();
        navigateTo(window.location.href, false);
    });

    // Handle BFCache recovery if page was restored with pending skeleton
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            const pageWrap = document.querySelector('#page-content-wrapper') || document.querySelector('.page-wrap');
            if (pageWrap && pageWrap.querySelector('.skeleton-wrapper')) {
                navigateTo(window.location.href, false);
            }
        }
    });

    // Proactively prewarm all primary navigation links and active page
    scheduleNavigationPrewarm();
};

// Window bindings for backward compatibility and inline Blade usage
window.navigateTo = navigateTo;
window.clearPageCache = clearPageCache;
window.prefetchUrl = prefetchUrl;
window.clearCalendarCache = clearCalendarCache;
window.prefetchCalendarPartial = prefetchCalendarPartial;
window.scheduleAdjacentCalendarPrefetch = scheduleAdjacentCalendarPrefetch;
window.scheduleNavigationPrewarm = scheduleNavigationPrewarm;
window.cacheCurrentPage = cacheCurrentPage;

export {
    initSpaRouter,
    navigateTo,
    clearPageCache,
    prefetchUrl,
    clearCalendarCache,
    getCalendarCacheKey,
    getCleanPageUrl,
    cacheCurrentPage,
    prefetchCalendarPartial,
    scheduleAdjacentCalendarPrefetch,
    scheduleNavigationPrewarm
};
