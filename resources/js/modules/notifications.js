import Swal from 'sweetalert2';

// ==========================================================================
// Flash Notifications Controller (SweetAlert2)
// Handles success, error, and validation notifications
// ==========================================================================
export function handleFlashNotifications() {
    // 1. Flash Success Notification
    const successEl = document.getElementById('flash-success-data');
    if (successEl) {
        const message = successEl.dataset.message;
        successEl.remove();
        if (message) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                confirmButtonColor: '#5645d4',
                confirmButtonText: 'Selesai',
                timer: 3500,
                timerProgressBar: true,
                heightAuto: false,
                scrollbarPadding: false,
                customClass: {
                    popup: 'simasadi-swal-popup',
                    confirmButton: 'simasadi-swal-btn',
                    title: 'simasadi-swal-title',
                    htmlContainer: 'simasadi-swal-text'
                }
            });
        }
    }

    // 2. Flash Error Notification
    const errorEl = document.getElementById('flash-error-data');
    if (errorEl) {
        window.resetButtonLoaders?.();
        const message = errorEl.dataset.message;
        errorEl.remove();
        if (message) {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: message,
                confirmButtonColor: '#5645d4',
                confirmButtonText: 'Tutup',
                heightAuto: false,
                scrollbarPadding: false,
                customClass: {
                    popup: 'simasadi-swal-popup',
                    confirmButton: 'simasadi-swal-btn',
                    title: 'simasadi-swal-title',
                    htmlContainer: 'simasadi-swal-text'
                }
            });
        }
    }

    // 3. Validation Errors Notification
    const errorsEl = document.getElementById('flash-errors-data');
    if (errorsEl) {
        window.resetButtonLoaders?.();
        const title = errorsEl.dataset.title || 'Periksa Kembali Input';
        let errors = [];
        try {
            errors = JSON.parse(errorsEl.dataset.errors || '[]');
        } catch (e) {
            errors = [errorsEl.dataset.errors];
        }
        errorsEl.remove();
        if (errors.length > 0) {
            const listHtml = '<ul style="text-align: left; margin: 10px 0 0 0; padding-left: 20px; font-size: 13.5px; line-height: 1.6;">' +
                errors.map(err => '<li>' + err + '</li>').join('') +
                '</ul>';

            Swal.fire({
                icon: 'error',
                title: title,
                html: listHtml,
                confirmButtonColor: '#5645d4',
                confirmButtonText: 'Mengerti',
                heightAuto: false,
                scrollbarPadding: false,
                customClass: {
                    popup: 'simasadi-swal-popup',
                    confirmButton: 'simasadi-swal-btn',
                    title: 'simasadi-swal-title',
                    htmlContainer: 'simasadi-swal-text'
                }
            });
        }
    }
}

// ==========================================================================
// Persistent Surveillance Banner Controller
// Notification banner is strictly persistent and cannot be dismissed
// ==========================================================================
export function initPersistentSurveillanceBanner() {
    try {
        sessionStorage.removeItem('simasadi_surveillance_dismissed');
    } catch {
        // Ignore storage exceptions
    }

    if (!window._surveillanceJumpHandlerAttached) {
        window._surveillanceJumpHandlerAttached = true;
        document.addEventListener('click', (event) => {
            const jumpBtn = event.target.closest('.persistent-surveillance-banner-jump-btn');
            if (!jumpBtn) return;

            const href = jumpBtn.getAttribute('href');
            if (!href || !href.startsWith('#')) return;

            const targetId = href.slice(1);
            const targetEl = document.getElementById(targetId) ||
                             document.getElementById('surveillance-section') ||
                             document.getElementById('surveillance-heading');

            if (targetEl) {
                event.preventDefault();
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

                const panel = targetEl.closest('.dashboard-status-panel') || targetEl;
                panel.classList.remove('surveillance-jump-pulse');
                void panel.offsetWidth;
                panel.classList.add('surveillance-jump-pulse');
                setTimeout(() => {
                    panel.classList.remove('surveillance-jump-pulse');
                }, 2000);
            }
        });
    }

    // Ensure surveillance banner is not suppressed by any past session state
    try {
        sessionStorage.removeItem('surveillance_banner_dismissed');
    } catch (_) {}
}

