// ==========================================================================
// Mobile Bottom Navigation & Action Sheet Controller (SIMASADI)
// ==========================================================================

let isSheetOpen = false;

/**
 * Open or close the Bottom Sheet for "Lainnya"
 */
export const setBottomSheetState = (isOpen) => {
    isSheetOpen = isOpen;
    const moreBtn = document.getElementById('bottom-nav-more-btn');
    const sheet = document.getElementById('bottom-nav-sheet');
    const backdrop = document.getElementById('bottom-sheet-backdrop');

    if (!sheet || !backdrop) return;

    if (isOpen) {
        backdrop.style.display = 'block';
        sheet.style.display = 'flex';
        // Force reflow for CSS transition
        void sheet.offsetWidth;
        backdrop.classList.add('is-open');
        sheet.classList.add('is-open');
        moreBtn?.setAttribute('aria-expanded', 'true');
        document.body.classList.add('bottom-sheet-open');
    } else {
        backdrop.classList.remove('is-open');
        sheet.classList.remove('is-open');
        moreBtn?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('bottom-sheet-open');

        setTimeout(() => {
            if (!isSheetOpen) {
                backdrop.style.display = 'none';
                sheet.style.display = 'none';
            }
        }, 260);
    }
};

/**
 * Synchronize active states on bottom nav tabs and sheet items
 */
export const syncBottomNavActive = (targetPath = window.location.pathname) => {
    // 1. Bottom Nav Items
    const navItems = document.querySelectorAll('.bottom-nav .bottom-nav-item[href]');
    let hasDirectActive = false;

    navItems.forEach((item) => {
        try {
            const itemPath = new URL(item.href, window.location.origin).pathname;
            const isActive = (itemPath === '/' || itemPath === '/dashboard')
                ? (targetPath === '/' || targetPath === '/dashboard')
                : (targetPath.startsWith(itemPath) && itemPath !== '/');

            item.classList.toggle('active', isActive);
            if (isActive) {
                item.setAttribute('aria-current', 'page');
                hasDirectActive = true;
            } else {
                item.removeAttribute('aria-current');
            }
        } catch (_) {
            // Ignore URL parsing errors
        }
    });

    // 2. "Lainnya" More Button
    const moreBtn = document.getElementById('bottom-nav-more-btn');
    if (moreBtn) {
        const isMorePath = targetPath.startsWith('/users') ||
                           targetPath.startsWith('/account-links') ||
                           targetPath.startsWith('/profile');

        const shouldHighlightMore = isMorePath || (!hasDirectActive && targetPath !== '/' && targetPath !== '/dashboard');
        moreBtn.classList.toggle('active', shouldHighlightMore);
        if (shouldHighlightMore) {
            moreBtn.setAttribute('aria-current', 'page');
        } else {
            moreBtn.removeAttribute('aria-current');
        }
    }

    // 3. Bottom Sheet Items
    const sheetItems = document.querySelectorAll('.bottom-sheet .bottom-sheet-item[href]');
    sheetItems.forEach((item) => {
        try {
            const itemPath = new URL(item.href, window.location.origin).pathname;
            const isActive = targetPath.startsWith(itemPath) && itemPath !== '/';
            item.classList.toggle('active', isActive);
        } catch (_) {
            // Ignore URL parsing errors
        }
    });
};

/**
 * Initialize listeners for bottom navigation and sheet
 */
let bottomNavListenersInitialized = false;

export const initBottomNav = () => {
    syncBottomNavActive();

    if (bottomNavListenersInitialized) return;
    bottomNavListenersInitialized = true;

    // Toggle button for "Lainnya"
    document.addEventListener('click', (event) => {
        const moreBtn = event.target.closest('#bottom-nav-more-btn');
        if (moreBtn) {
            event.preventDefault();
            event.stopPropagation();
            setBottomSheetState(!isSheetOpen);
            return;
        }

        // Close button inside sheet
        const closeBtn = event.target.closest('#bottom-sheet-close');
        if (closeBtn) {
            event.preventDefault();
            event.stopPropagation();
            setBottomSheetState(false);
            return;
        }

        // Click on backdrop
        const backdrop = event.target.closest('#bottom-sheet-backdrop');
        if (backdrop) {
            event.preventDefault();
            event.stopPropagation();
            setBottomSheetState(false);
            return;
        }

        // Clicking any link inside bottom sheet or bottom nav closes the sheet
        const navLink = event.target.closest('.bottom-sheet a, .bottom-nav a');
        if (navLink) {
            setBottomSheetState(false);
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isSheetOpen) {
            setBottomSheetState(false);
        }
    });

    // Simple touch swipe down to close sheet
    let touchStartY = 0;
    const sheet = document.getElementById('bottom-nav-sheet');
    if (sheet) {
        sheet.addEventListener('touchstart', (e) => {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        sheet.addEventListener('touchmove', (e) => {
            const currentY = e.touches[0].clientY;
            // If dragging down from the top handle bar
            if (currentY - touchStartY > 70 && sheet.scrollTop <= 0) {
                setBottomSheetState(false);
            }
        }, { passive: true });
    }
};

if (typeof window !== 'undefined') {
    window.setBottomSheetState = setBottomSheetState;
    window.syncBottomNavActive = syncBottomNavActive;
}
