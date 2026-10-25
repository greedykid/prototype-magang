/**
 * Route Matcher & Layout Selector
 * Maps URL paths to skeleton layout types and page header titles.
 */
export const getPageTypeAndTitle = (url) => {
    const path = new URL(url, window.location.origin).pathname;

    if (path === '/' || path === '/dashboard') {
        return { type: 'dashboard', title: 'Ringkasan' };
    }
    if (path.startsWith('/calendar')) {
        if (path.includes('/create') || path.includes('/edit')) {
            return { type: 'form', title: 'Kalender Kegiatan' };
        }
        if (path.match(/\/calendar\/events\/\d+$/)) {
            return { type: 'detail', title: 'Detail Kegiatan' };
        }
        return { type: 'calendar', title: 'Kalender Kegiatan' };
    }
    if (path.includes('/create') || path.includes('/edit')) {
        let title = 'Formulir';
        if (path.startsWith('/lpks')) title = 'Daftar Laboratorium';
        else if (path.startsWith('/assessments')) title = 'Daftar Asesmen';
        else if (path.startsWith('/users')) title = 'Manajemen Pengguna';
        else if (path.startsWith('/profile')) title = 'Profil Pengguna';
        return { type: 'form', title };
    }
    if (path.match(/\/(lpks|assessments|users|account-links)\/\d+$/)) {
        let title = 'Detail Data';
        if (path.startsWith('/lpks')) title = 'Daftar Laboratorium';
        else if (path.startsWith('/assessments')) title = 'Daftar Asesmen';
        else if (path.startsWith('/users')) title = 'Detail Anggota';
        else if (path.startsWith('/account-links')) title = 'Akun Tertaut';
        return { type: 'detail', title };
    }
    if (path.startsWith('/profile')) return { type: 'form', title: 'Profil Pengguna' };
    if (path.startsWith('/assessments')) return { type: 'table', title: 'Daftar Asesmen' };
    if (path.startsWith('/lpks')) return { type: 'table', title: 'Daftar Laboratorium' };
    if (path.startsWith('/users')) return { type: 'table', title: 'Manajemen Pengguna' };
    if (path.startsWith('/account-links')) return { type: 'table', title: 'Akun Tertaut' };

    return { type: 'table', title: 'Workspace' };
};
