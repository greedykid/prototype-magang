/**
 * Password Visibility Toggle Module
 * Provides accessible, keyboard-friendly toggling between masked and revealed password inputs.
 */

export function initPasswordToggles() {
    if (window._passwordTogglesBound) return;
    window._passwordTogglesBound = true;

    document.addEventListener('click', (event) => {
        const toggleBtn = event.target.closest('.password-toggle-btn, [data-password-toggle]');
        if (!toggleBtn) return;

        event.preventDefault();
        event.stopPropagation();

        // 1. Locate the input
        let input = null;
        const targetId = toggleBtn.getAttribute('data-target');
        if (targetId) {
            input = document.getElementById(targetId);
        }

        if (!input) {
            const wrap = toggleBtn.closest('.password-input-wrap, .profile-field, label');
            if (wrap) {
                input = wrap.querySelector('input[type="password"], input[data-password-input="true"]');
            }
        }

        if (!input) {
            input = toggleBtn.parentElement?.querySelector('input');
        }

        if (!input) return;

        // 2. Toggle state
        const willShow = input.type === 'password';
        input.type = willShow ? 'text' : 'password';
        input.setAttribute('data-password-input', 'true');

        // 3. Update accessibility & tooltips
        toggleBtn.setAttribute('aria-pressed', willShow ? 'true' : 'false');
        const actionLabel = willShow ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
        toggleBtn.setAttribute('aria-label', actionLabel);
        toggleBtn.setAttribute('title', actionLabel);

        // 4. Update icons
        const eyeShow = toggleBtn.querySelector('.eye-show, [data-eye-show]');
        const eyeHide = toggleBtn.querySelector('.eye-hide, [data-eye-hide]');
        if (eyeShow && eyeHide) {
            eyeShow.style.display = willShow ? 'none' : 'inline-flex';
            eyeHide.style.display = willShow ? 'inline-flex' : 'none';
        }

        // 5. Retain focus and cursor position in input
        try {
            const length = input.value.length;
            input.focus();
            input.setSelectionRange(length, length);
        } catch (_) {}
    });

    // Reset password fields back to masked state on form reset
    document.addEventListener('reset', (event) => {
        const form = event.target;
        if (!form || !form.querySelectorAll) return;

        setTimeout(() => {
            const toggles = form.querySelectorAll('.password-toggle-btn, [data-password-toggle]');
            toggles.forEach((toggleBtn) => {
                const wrap = toggleBtn.closest('.password-input-wrap, .profile-field, label');
                const input = wrap?.querySelector('input[data-password-input="true"]');
                if (input) {
                    input.type = 'password';
                }
                toggleBtn.setAttribute('aria-pressed', 'false');
                toggleBtn.setAttribute('aria-label', 'Tampilkan kata sandi');
                toggleBtn.setAttribute('title', 'Tampilkan kata sandi');

                const eyeShow = toggleBtn.querySelector('.eye-show, [data-eye-show]');
                const eyeHide = toggleBtn.querySelector('.eye-hide, [data-eye-hide]');
                if (eyeShow && eyeHide) {
                    eyeShow.style.display = 'inline-flex';
                    eyeHide.style.display = 'none';
                }
            });
        }, 10);
    });
}
