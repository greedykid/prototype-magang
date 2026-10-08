/**
 * In-Memory Page Cache, Calendar Cache & Prefetch Controller
 */

export const PAGE_CACHE_TTL = 60 * 1000; // 60 seconds TTL
export const pageCache = new Map();
export const prefetchInFlight = new Map();

export const clearPageCache = () => {
    pageCache.clear();
    prefetchInFlight.clear();
};

export const calendarCache = new Map();
export const CALENDAR_CACHE_TTL = 15 * 60 * 1000; // 15 menit
export const calendarPrefetchInFlight = new Map();

export const clearCalendarCache = () => {
    calendarCache.clear();
    calendarPrefetchInFlight.clear();
};

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

export const scheduleAdjacentCalendarPrefetch = () => {
    const runPrefetch = () => {
        const shell = document.querySelector('.gcal-shell');
        if (!shell) return;

        // 1. Simpan shell yang sedang aktif ke cache jika belum ada
        const currentKey = getCalendarCacheKey(window.location.href);
        if (!calendarCache.has(currentKey)) {
            calendarCache.set(currentKey, {
                htmlText: shell.outerHTML,
                timestamp: Date.now()
            });
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

export const isCacheableUrl = (urlStr) => {
    try {
        const u = new URL(urlStr, window.location.origin);
        if (u.origin !== window.location.origin) return false;
        if (u.pathname.includes('/logout') || u.pathname.includes('/export') || u.pathname.includes('/download')) return false;
        return true;
    } catch {
        return false;
    }
};

export const prefetchUrl = (urlStr) => {
    if (!isCacheableUrl(urlStr)) return;
    try {
        const u = new URL(urlStr, window.location.origin);
        const cleanUrl = u.href;

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
