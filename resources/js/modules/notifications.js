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
}

