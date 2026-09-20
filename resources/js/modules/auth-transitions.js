import Swal from 'sweetalert2';

// ==========================================================================
// Smooth Login Submitting Motion (With Active Moving Spinner)
// ==========================================================================
export const initAuthTransitions = () => {
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('#login-form');
        if (!form) return;

        if (form.dataset.submitting === 'true') {
            return; // Native submission in progress
        }

        if (!form.checkValidity()) {
            return; // Allow native HTML5 validation tooltips to show
        }

        event.preventDefault();
        form.dataset.submitting = 'true';

        const submitBtn = form.querySelector('#login-submit-btn') || form.querySelector('button[type="submit"]');
        const loginCard = document.querySelector('#login-card');

        if (submitBtn) {
            submitBtn.classList.add('is-loading');
        }
        if (loginCard) {
            loginCard.classList.add('is-submitting');
        }

        // Allow the spinner to rotate and user to feel smooth feedback before unload
        setTimeout(() => {
            form.submit();
        }, 480);
    });

    // ==========================================================================
    // Smooth Logout Curtain Exit Transition with SweetAlert2 Confirmation
    // ==========================================================================
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('#logout-form, .logout-form');
        if (!form) return;

        if (form.dataset.confirmed === 'true') {
            return; // Native submission in progress after confirmation
        }

        event.preventDefault();

        Swal.fire({
            title: 'Keluar dari workspace?',
            text: 'Anda akan mengakhiri sesi aktif di SIMASADI.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#5645d4',
            cancelButtonColor: '#71717a',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'simasadi-swal-popup',
                confirmButton: 'simasadi-swal-btn',
                cancelButton: 'simasadi-swal-cancel-btn',
                title: 'simasadi-swal-title',
                htmlContainer: 'simasadi-swal-text'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.dataset.confirmed = 'true';
                const curtain = document.querySelector('#logout-curtain');
                if (curtain) {
                    curtain.classList.add('is-active');
                    curtain.setAttribute('aria-hidden', 'false');
                }
                setTimeout(() => {
                    form.submit();
                }, 450);
            }
        });
    });
};
