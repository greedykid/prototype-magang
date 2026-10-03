const THEME_KEY = 'simasadi_theme';

export function getStoredTheme() {
    return localStorage.getItem(THEME_KEY);
}

export function getPreferredTheme() {
    const saved = getStoredTheme();
    if (saved === 'dark' || saved === 'light') {
        return saved;
    }
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

export function applyTheme(theme, save = true) {
    const activeTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', activeTheme);
    if (save) {
        try {
            localStorage.setItem(THEME_KEY, activeTheme);
        } catch (e) {
            console.warn('Unable to persist theme to localStorage', e);
        }
    }
    syncThemeButtonUI(activeTheme);
}

export function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const nextTheme = current === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme, true);
    return nextTheme;
}

export function syncThemeButtonUI(theme) {
    const btn = document.getElementById('theme-toggle-btn');
    if (!btn) return;

    const isDark = theme === 'dark';
    const label = isDark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap';
    btn.setAttribute('aria-label', label);
    btn.setAttribute('title', label);
    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
}

export function initTheme() {
    // 1. Sync button UI with current theme on init
    const currentTheme = document.documentElement.getAttribute('data-theme') || getPreferredTheme();
    syncThemeButtonUI(currentTheme);

    // 2. Attach global click listener for theme toggle button
    if (!window._simasadiThemeListenerAttached) {
        document.addEventListener('click', (event) => {
            const btn = event.target.closest('#theme-toggle-btn');
            if (btn) {
                event.preventDefault();
                toggleTheme();
            }
        });

        // 3. Listen to system preference changes if user has not explicitly locked theme
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!getStoredTheme()) {
                    applyTheme(e.matches ? 'dark' : 'light', false);
                }
            });
        }

        window._simasadiThemeListenerAttached = true;
    }
}
