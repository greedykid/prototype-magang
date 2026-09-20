// ==========================================================================
// Sidebar & Mobile Navigation Drawer Controllers
// ==========================================================================

export const setDrawerState = (isOpen) => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (!menuToggle || !sidebar) {
        return;
    }

    menuToggle.setAttribute('aria-expanded', String(isOpen));
    sidebar.classList.toggle('is-open', isOpen);
    document.body.classList.toggle('drawer-open', isOpen);
};

if (typeof window !== 'undefined') {
    window.setDrawerState = setDrawerState;
}

export const initMobileDrawer = () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const drawerBackdrop = document.querySelector('[data-drawer-close]');
    const drawerClose = document.querySelector('.drawer-close');

    if (sidebar) {
        menuToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
            setDrawerState(!isOpen);
        });

        sidebar.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                setDrawerState(false);
            }
        });

        drawerClose?.addEventListener('click', (event) => {
            event.stopPropagation();
            setDrawerState(false);
        });

        drawerBackdrop?.addEventListener('click', (event) => {
            event.stopPropagation();
            setDrawerState(false);
        });

        drawerBackdrop?.addEventListener('touchstart', (event) => {
            event.stopPropagation();
            setDrawerState(false);
        }, { passive: true });

        // Close sidebar if user clicks or taps anywhere outside the sidebar
        const handleOutsideInteraction = (event) => {
            if (!sidebar.classList.contains('is-open') && !document.body.classList.contains('drawer-open')) {
                return;
            }

            // Ignore clicks or touches inside sidebar or on the menu toggle button
            if (event.target.closest('.sidebar') || event.target.closest('.menu-toggle')) {
                return;
            }

            setDrawerState(false);
        };

        document.addEventListener('click', handleOutsideInteraction);
        document.addEventListener('touchstart', handleOutsideInteraction, { passive: true });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setDrawerState(false);
            }
        });
    }
};

// ==========================================================================
// Sidebar Collapse & Expand State Controller (Single Button in Navbar)
// ==========================================================================
export const initSidebarCollapse = () => {
    const isCollapsed = localStorage.getItem('simasadi_sidebar_collapsed') === 'true';
    const appShell = document.querySelector('.app-shell');
    if (appShell) {
        appShell.classList.toggle('sidebar-collapsed', isCollapsed);
    }
    document.documentElement.classList.toggle('sidebar-is-collapsed', isCollapsed);

    const toggleBtn = document.querySelector('.navbar-sidebar-toggle');
    const label = isCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar';
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-label', label);
        toggleBtn.title = label;
    }
};

export const toggleSidebarState = (explicitState) => {
    const appShell = document.querySelector('.app-shell');
    const html = document.documentElement;
    const isCurrentlyCollapsed = appShell?.classList.contains('sidebar-collapsed') || html.classList.contains('sidebar-is-collapsed');
    const nextState = typeof explicitState === 'boolean' ? explicitState : !isCurrentlyCollapsed;

    localStorage.setItem('simasadi_sidebar_collapsed', String(nextState));
    if (appShell) {
        appShell.classList.toggle('sidebar-collapsed', nextState);
    }
    html.classList.toggle('sidebar-is-collapsed', nextState);

    const toggleBtn = document.querySelector('.navbar-sidebar-toggle');
    const label = nextState ? 'Perluas sidebar' : 'Ciutkan sidebar';
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-label', label);
        toggleBtn.title = label;
    }
};

// Global click delegation for sidebar collapse and expand
document.addEventListener('click', (event) => {
    const toggleBtn = event.target.closest('.navbar-sidebar-toggle');
    if (toggleBtn) {
        event.preventDefault();
        toggleSidebarState();
        return;
    }

    // Clicking the "K" brand mark when collapsed also expands it
    const brandMark = event.target.closest('.brand-mark');
    if (brandMark && (document.documentElement.classList.contains('sidebar-is-collapsed') || document.querySelector('.app-shell')?.classList.contains('sidebar-collapsed'))) {
        toggleSidebarState(false);
    }
});
