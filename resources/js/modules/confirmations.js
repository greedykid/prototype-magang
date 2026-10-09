import Swal from 'sweetalert2';

// ==========================================================================
// Delete Confirmation Dialogs via SweetAlert2
// Handles destructive action confirmations (e.g. Delete LPK, Account Links, Team Members, Users)
// ==========================================================================
export const initConfirmations = () => {
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.form-delete-lpk, [data-confirm-delete], [data-confirm]');
        if (!form) return;

        if (form.dataset.confirmed === 'true') {
            return; // Already confirmed, proceed with native form submission
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        let title = form.dataset.confirmTitle;
        let htmlContent = '';
        let confirmBtnText = form.dataset.confirmBtn;
        const isDeleteLpk = form.matches('.form-delete-lpk');

        if (form.dataset.confirmHtml) {
            htmlContent = sanitizeHtml(form.dataset.confirmHtml);
        } else if (form.dataset.confirmText) {
            htmlContent = escapeHtml(form.dataset.confirmText);
        } else if (isDeleteLpk || (!title && form.matches('[data-confirm-delete]:not([data-confirm-title])'))) {
            const lpkName = form.dataset.lpkName || 'LPK';
            const lpkReg = form.dataset.lpkReg ? ` (${form.dataset.lpkReg})` : '';
            title = title || 'Hapus data LPK?';
            htmlContent = `Data LPK <strong>${escapeHtml(lpkName)}</strong>${escapeHtml(lpkReg)} dan seluruh proses akreditasi serta agenda asesmen terkait akan dihapus secara permanen.`;
            confirmBtnText = confirmBtnText || 'Ya, Hapus LPK';
        } else if (form.dataset.confirm) {
            htmlContent = escapeHtml(form.dataset.confirm);
        } else {
            htmlContent = 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        }

        title = title || 'Konfirmasi Tindakan?';
        confirmBtnText = confirmBtnText || 'Ya, Hapus';

        const isDanger = form.dataset.confirmDanger !== 'false';

        Swal.fire({
            title: title,
            html: htmlContent,
            icon: form.dataset.confirmIcon || 'warning',
            showCancelButton: true,
            confirmButtonColor: isDanger ? '#dc2626' : '#5645d4',
            cancelButtonColor: '#71717a',
            confirmButtonText: confirmBtnText,
            cancelButtonText: form.dataset.cancelBtn || 'Batal',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'simasadi-swal-popup',
                confirmButton: `simasadi-swal-btn ${isDanger ? 'simasadi-swal-danger-btn' : ''}`,
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

function sanitizeHtml(dirty) {
    if (!dirty) return '';
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(dirty, 'text/html');
        doc.querySelectorAll('script, iframe, object, embed, link, style').forEach(el => el.remove());
        doc.querySelectorAll('*').forEach(el => {
            Array.from(el.attributes).forEach(attr => {
                if (attr.name.toLowerCase().startsWith('on') || attr.value.toLowerCase().startsWith('javascript:')) {
                    el.removeAttribute(attr.name);
                }
            });
        });
        return doc.body.innerHTML;
    } catch {
        return escapeHtml(dirty);
    }
}
