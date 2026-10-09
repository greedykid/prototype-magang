import test from 'node:test';
import assert from 'node:assert/strict';

// Set up global browser mocks for Node environment
globalThis.window = {
    location: {
        origin: 'http://localhost:8086'
    }
};

const {
    isCacheableUrl,
    getCleanPageUrl,
    getCalendarCacheKey,
    PAGE_CACHE_TTL,
    CALENDAR_CACHE_TTL
} = await import('../../resources/js/modules/spa/router-cache.js');

test('router cache TTL constants are optimized', () => {
    assert.equal(PAGE_CACHE_TTL, 30000); // 30s
    assert.equal(CALENDAR_CACHE_TTL, 60000); // 60s
});

test('getCleanPageUrl removes hash fragments', () => {
    const clean = getCleanPageUrl('http://localhost:8086/lpks?tab=active#section-2');
    assert.equal(clean, 'http://localhost:8086/lpks?tab=active');
});

test('getCalendarCacheKey standardizes and sorts query parameters', () => {
    const key1 = getCalendarCacheKey('http://localhost:8086/assessments/calendar-events?end=2026-12-31&start=2026-01-01');
    const key2 = getCalendarCacheKey('http://localhost:8086/assessments/calendar-events?start=2026-01-01&end=2026-12-31');
    assert.equal(key1, key2);
    assert.equal(key1, '/assessments/calendar-events?end=2026-12-31&start=2026-01-01');
});

test('isCacheableUrl validates safe URLs and excludes unsafe routes', () => {
    assert.equal(isCacheableUrl('http://localhost:8086/lpks'), true);
    assert.equal(isCacheableUrl('http://localhost:8086/assessments'), true);
    assert.equal(isCacheableUrl('http://localhost:8086/logout'), false);
    assert.equal(isCacheableUrl('http://localhost:8086/reports/lpks/export'), false);
    assert.equal(isCacheableUrl('https://google.com/search'), false);
});
