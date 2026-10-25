/**
 * Real-time Sidebar Operational Clock & Calendar Widget
 * Displays live Jakarta/WIB time (HH:mm:ss WIB) and full date with zero CLS.
 */

const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const MONTHS = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

let clockInterval = null;

function formatJakartaDateTime() {
    const now = new Date();

    // Use Intl API with Asia/Jakarta timezone for absolute accuracy
    try {
        const timeFormatter = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });

        const dateFormatter = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        const timeStr = timeFormatter.format(now).replace(/\./g, ':') + ' WIB';
        const dateStr = dateFormatter.format(now);

        return { timeStr, dateStr };
    } catch {
        // Fallback in case Intl timeZone is unsupported
        // Calculate UTC+7 offset
        const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        const wib = new Date(utc + (3600000 * 7));

        const hh = String(wib.getHours()).padStart(2, '0');
        const mm = String(wib.getMinutes()).padStart(2, '0');
        const ss = String(wib.getSeconds()).padStart(2, '0');
        const timeStr = `${hh}:${mm}:${ss} WIB`;

        const dayName = DAYS[wib.getDay()];
        const day = wib.getDate();
        const monthName = MONTHS[wib.getMonth()];
        const year = wib.getFullYear();
        const dateStr = `${dayName}, ${day} ${monthName} ${year}`;

        return { timeStr, dateStr };
    }
}

export function updateSidebarClock() {
    const timeEl = document.getElementById('sidebar-clock-time');
    const dateEl = document.getElementById('sidebar-date-text');
    const cardEl = document.getElementById('sidebar-datetime-card');

    if (!timeEl && !dateEl) return;

    const { timeStr, dateStr } = formatJakartaDateTime();

    if (timeEl && timeEl.textContent !== timeStr) {
        timeEl.textContent = timeStr;
    }

    if (dateEl && dateEl.textContent !== dateStr) {
        dateEl.textContent = dateStr;
    }

    if (cardEl) {
        cardEl.title = `${dateStr} • ${timeStr}`;
    }
}

export function initSidebarClock() {
    const cardEl = document.getElementById('sidebar-datetime-card');
    if (!cardEl) return;

    // Immediately update on boot
    updateSidebarClock();

    // Avoid multiple intervals
    if (clockInterval) {
        clearInterval(clockInterval);
    }

    clockInterval = setInterval(updateSidebarClock, 1000);

    // Pause clock when tab is hidden to conserve CPU/battery
    if (!window._sidebarClockVisibilityAttached) {
        window._sidebarClockVisibilityAttached = true;
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                updateSidebarClock();
                if (!clockInterval) {
                    clockInterval = setInterval(updateSidebarClock, 1000);
                }
            } else {
                if (clockInterval) {
                    clearInterval(clockInterval);
                    clockInterval = null;
                }
            }
        });
    }
}
