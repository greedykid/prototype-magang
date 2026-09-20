// ==========================================================================
// Universal Button Loading Spinner Feedback on Action / Submission
// Automatically gives immediate visual feedback on buttons with delay (e.g. perbarui status)
// ==========================================================================

export const initButtonLoader = () => {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form || event.defaultPrevented) return;

        // Skip unconfirmed SweetAlert forms (e.g. logout)
        if (form.matches('#logout-form, .logout-form') && form.dataset.confirmed !== 'true') {
            return;
        }

        // Don't trigger loading state if native HTML5 validation fails
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        // Identify the submit button (using modern submitter API with fallback)
        const submitBtn = event.submitter || 
                          (document.activeElement && form.contains(document.activeElement) && (document.activeElement.type === 'submit' || document.activeElement.matches('button:not([type]), button[type="submit"]')) ? document.activeElement : null) ||
                          form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');

        if (submitBtn && !submitBtn.classList.contains('is-loading')) {
            submitBtn.classList.add('is-loading');
            submitBtn.setAttribute('aria-busy', 'true');

            // Dynamically inject .btn-spinner if not already present
            let spinner = submitBtn.querySelector('.btn-spinner');
            if (!spinner) {
                spinner = document.createElement('span');
                spinner.className = 'btn-spinner';
                spinner.setAttribute('aria-hidden', 'true');
                spinner.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`;
                submitBtn.prepend(spinner);
            }
        }
    });

    // Restore button states if page is restored from back-forward cache (bfcache)
    window.addEventListener('pageshow', () => {
        window.closeEventPopover?.();
        window.closeModal?.();
        document.querySelectorAll('.button.is-loading, button.is-loading').forEach((btn) => {
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
        });
    });
};
