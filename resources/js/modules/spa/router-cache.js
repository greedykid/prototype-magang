/**
 * In-Memory Page Cache, Calendar Cache & Navigation Prewarm Engine
 * Delivers zero-delay (0ms) instant page switches across all application menus.
 */

export const PAGE_CACHE_TTL = 30 * 1000; // 30 seconds TTL (ensures fresh operational data)
export const pageCache = new Map();
export const prefetchInFlight = new Map();

export const clearPageCache = () => {
    pageCache.clear();
    prefetchInFlight.clear();
};

export const calendarCache = new Map();
export const CALENDAR_CACHE_TTL = 60 * 1000; // 60 seconds TTL
export const calendarPrefetchInFlight = new Map();

export const clearCalendarCache = () => {
    calendarCache.clear();
    calendarPrefetchInFlight.clear();
};

/**
 * Standardize cache key for calendar requests with sorted parameters
 */
export const getCalendarCacheKey = (rawUrl) => {
    try {
        const u = new URL(rawUrl, window.location.origin);
        const params = new URLSearchParams(u.search);
        params.sort();
        return `${u.pathname}?${params.toString()}`;
    } catch {
        return rawUrl;
    }
};

/**
 * Standardize clean URL for general page cache (strips anchor fragments)
 */
export const getCleanPageUrl = (urlStr) => {
    try {
        const u = new URL(urlStr, window.location.origin);
        u.hash = '';
        return u.href;
    } catch {
        return urlStr;
    }
};

/**
 * Check whether a URL is safe and eligible for SPA caching
 */
export const isCacheableUrl = (urlStr) => {
    try {
        const u = new URL(urlStr, window.location.origin);
        if (u.origin !== window.location.origin) return false;
        if (
            u.pathname.includes('/logout') ||
            u.pathname.includes('/export') ||
            u.pathname.includes('/download') ||
            u.pathname.includes('/create') ||
            u.pathname.includes('/edit') ||
            u.pathname.includes('/password')
        ) {
            return false;
        }
        return true;
    } catch {
        return false;
    }
};

/**
 * Store current active page in memory cache immediately with pristine server HTML
 */
export const cacheCurrentPage = () => {
    try {
        const currentUrl = window.location.href;
        if (!isCacheableUrl(currentUrl)) return;
        const cleanUrl = getCleanPageUrl(currentUrl);
        if (!pageCache.has(cleanUrl)) {
            prefetchUrl(cleanUrl);
        }
    } catch (_) {
        // Silently ignore prefetch errors
    }
};

/**
 * Prefetch partial calendar view for zero-delay month switches
 */
export const prefetchCalendarPartial = async (rawUrl) => {
    try {
        const u = new URL(rawUrl, window.location.origin);
        if (u.pathname !== '/calendar') return null;

        const cacheKey = getCalendarCacheKey(rawUrl);
        const cached = calendarCache.get(cacheKey);
        if (cached && (Date.now() - cached.timestamp < CALENDAR_CACHE_TTL)) {
            return cached.htmlText;
        }

        if (calendarPrefetchInFlight.has(cacheKey)) {
            return calendarPrefetchInFlight.get(cacheKey);
        }

        const fetchPromise = fetch(u.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Partial-Content': 'calendar'
            }
        }).then(async (response) => {
            calendarPrefetchInFlight.delete(cacheKey);
            if (!response.ok || response.redirected) return null;
            const text = await response.text();
            calendarCache.set(cacheKey, {
                htmlText: text,
                timestamp: Date.now()
            });
            return text;
        }).catch(() => {
            calendarPrefetchInFlight.delete(cacheKey);
            return null;
        });

        calendarPrefetchInFlight.set(cacheKey, fetchPromise);
        return fetchPromise;
    } catch {
        return null;
    }
};

/**
 * Prefetch full page HTML for seamless instant menu switching
 */
export const prefetchUrl = (urlStr) => {
    if (!isCacheableUrl(urlStr)) return;
    try {
        const cleanUrl = getCleanPageUrl(urlStr);

        const cached = pageCache.get(cleanUrl);
        if (cached && (Date.now() - cached.timestamp < PAGE_CACHE_TTL)) {
            return;
        }

        if (prefetchInFlight.has(cleanUrl)) {
            return;
        }

        const fetchPromise = fetch(cleanUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(async (response) => {
            prefetchInFlight.delete(cleanUrl);
            if (!response.ok || response.redirected) return null;
            const text = await response.text();
            pageCache.set(cleanUrl, {
                htmlText: text,
                timestamp: Date.now()
            });
            return text;
        }).catch(() => {
            prefetchInFlight.delete(cleanUrl);
            return null;
        });

        prefetchInFlight.set(cleanUrl, fetchPromise);
    } catch {
        // Silently ignore invalid URLs
    }
};

/**
 * Prefetch previous & next months when calendar shell is present
 */
export const scheduleAdjacentCalendarPrefetch = () => {
    const runPrefetch = () => {
        const shell = document.querySelector('.gcal-shell');
        if (!shell) return;

        // 1. Simpan partial kalender yang sedang aktif ke cache jika belum ada
        const currentKey = getCalendarCacheKey(window.location.href);
        if (!calendarCache.has(currentKey)) {
            prefetchCalendarPartial(window.location.href);
        }

        // 2. Prefetch link panah bulan sebelumnya dan berikutnya dari toolbar & mini sidebar, serta tombol hari ini
        const arrowLinks = document.querySelectorAll(
            '.gcal-nav-arrows a.gcal-arrow-btn, .gcal-mini-nav a.gcal-mini-nav-btn, .gcal-nav-group a.gcal-btn-today'
        );
        arrowLinks.forEach((link) => {
            if (link.href) {
                prefetchCalendarPartial(link.href);
            }
        });
    };

    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(runPrefetch, { timeout: 800 });
    } else {
        setTimeout(runPrefetch, 60);
    }
};

/**
 * Proactively prewarm all primary navigation links and current page during idle time
 * Ensures all menu switches are 100% instant (0ms) from memory.
 */
export const scheduleNavigationPrewarm = () => {
    const runPrewarm = () => {
        // 1. Cache halaman yang saat ini aktif ke memory jika belum ada
        cacheCurrentPage();

        // 2. Jika di halaman kalender, jalankan prefetch bulan sekitar
        if (window.location.pathname === '/calendar') {
            scheduleAdjacentCalendarPrefetch();
        }

        // 3. Prewarm semua tautan navigasi utama di sidebar, topbar & bottom nav
        const primaryLinks = document.querySelectorAll(
            '#primary-navigation a[href], .brand a[href], .brand-mark[href], .topbar a[href], .user-nav a[href], .bottom-nav a[href], .bottom-sheet a[href]'
        );

        primaryLinks.forEach((link) => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download')) return;
            if (link.closest('form')) return;

            try {
                const u = new URL(link.href, window.location.origin);
                if (u.origin !== window.location.origin) return;
                if (getCleanPageUrl(u.href) === getCleanPageUrl(window.location.href)) return;

                if (u.pathname === '/calendar') {
                    prefetchCalendarPartial(link.href);
                } else if (isCacheableUrl(link.href)) {
                    prefetchUrl(link.href);
                }
            } catch (_) {
                // Ignore invalid URLs
            }
        });
    };

    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(runPrewarm, { timeout: 1200 });
    } else {
        setTimeout(runPrewarm, 150);
    }
};
