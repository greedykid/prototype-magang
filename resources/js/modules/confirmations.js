import Swal from 'sweetalert2';

// ==========================================================================
// Delete Confirmation Dialogs via SweetAlert2
// Handles destructive action confirmations (e.g. Delete LPK)
// ==========================================================================
export const initConfirmations = () => {
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.form-delete-lpk, [data-confirm-delete]');
        if (!form) return;

        if (form.dataset.confirmed === 'true') {
            return; // Already confirmed, proceed with native form submission
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const lpkName = form.dataset.lpkName || 'LPK';
        const lpkReg = form.dataset.lpkReg ? ` (${form.dataset.lpkReg})` : '';

        Swal.fire({
            title: 'Hapus data LPK?',
            html: `Data LPK <strong>${escapeHtml(lpkName)}</strong>${escapeHtml(lpkReg)} dan seluruh proses akreditasi serta agenda asesmen terkait akan dihapus secara permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#71717a',
            confirmButtonText: 'Ya, Hapus LPK',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'simasadi-swal-popup',
                confirmButton: 'simasadi-swal-btn simasadi-swal-danger-btn',
                cancelButton: 'simasadi-swal-cancel-btn',
                title: 'simasadi-swal-title',
                htmlContainer: 'simasadi-swal-text'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.dataset.confirmed = 'true';

                // Display loading spinner on the submit button for immediate feedback
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('is-loading');
                    submitBtn.setAttribute('aria-busy', 'true');
                    let spinner = submitBtn.querySelector('.btn-spinner');
                    if (!spinner) {
                        spinner = document.createElement('span');
                        spinner.className = 'btn-spinner';
                        spinner.setAttribute('aria-hidden', 'true');
                        spinner.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`;
                        submitBtn.prepend(spinner);
                    }
                }

                form.submit();
            }
        });
    });
};

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
