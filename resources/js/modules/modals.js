import { initCustomSelects } from './custom-select.js';

// ==========================================================================
// SIMASADI Viewport-Centric Modal Dialog System
// Teleports modals to <body> and manages viewport centering & scroll lock
// ==========================================================================

export function returnModalToPlaceholder(modal) {
    if (!modal) return;
    if (modal._simasadiPlaceholder && modal._simasadiPlaceholder.parentNode) {
        modal._simasadiPlaceholder.parentNode.insertBefore(modal, modal._simasadiPlaceholder);
        modal._simasadiPlaceholder.remove();
        modal._simasadiPlaceholder = null;
    }
}

let savedModalScrollY = 0;

export function openModal(modalId) {
    let modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
    if (!modal) {
        console.warn('Modal element not found:', modalId);
        return;
    }

    // Clean up any duplicate modals with same ID if present
    if (typeof modalId === 'string') {
        const modals = document.querySelectorAll(`#${modalId}`);
        if (modals.length > 1) {
            for (let i = 0; i < modals.length - 1; i++) {
                modals[i].remove();
            }
            modal = document.getElementById(modalId);
        }
    }

    // Capture current scroll offset before body scroll-lock applies
    if (!document.querySelector('.simasadi-modal.is-active')) {
        savedModalScrollY = window.scrollY || document.documentElement.scrollTop || 0;
    }

    // Teleport to document.body so modal escapes any ancestor containing block,
    // positioning relative to the entire viewport (covering sidebar, topbar, and centered).
    // A placeholder comment node is left behind so the modal can be restored to its exact place in the DOM upon closing.
    if (modal.parentElement && modal.parentElement !== document.body) {
        if (!modal._simasadiPlaceholder) {
            const placeholder = document.createComment(`simasadi-modal-placeholder-${modal.id || 'dialog'}`);
            modal.parentElement.insertBefore(placeholder, modal);
            modal._simasadiPlaceholder = placeholder;
        }
        document.body.appendChild(modal);
    }

    // Initialize any custom selects inside this modal if not yet initialized
    initCustomSelects(modal);

    modal.classList.add('is-active');
    document.documentElement.classList.add('modal-open');
    document.body.classList.add('modal-open');

    // Auto-focus first input on non-touch devices without causing scroll jumps
    const isTouch = ('ontouchstart' in window || navigator.maxTouchPoints > 0 || window.innerWidth <= 768);
    if (!isTouch) {
        const focusable = modal.querySelector('.custom-select-trigger, input:not([type="hidden"]):not(.custom-select-native), select:not(.custom-select-native), textarea, button.primary:not([data-modal-close])');
        if (focusable) {
            setTimeout(() => {
                try {
                    focusable.focus({ preventScroll: true });
                } catch {
                    focusable.focus();
                }
            }, 60);
        }
    }
}

export function closeModal(modalIdOrEl) {
    if (!modalIdOrEl) {
        document.querySelectorAll('.simasadi-modal').forEach((m) => {
            m.classList.remove('is-active');
            returnModalToPlaceholder(m);
        });
        document.documentElement.classList.remove('modal-open');
        document.body.classList.remove('modal-open');
        if (typeof savedModalScrollY === 'number' && savedModalScrollY > 0) {
            window.scrollTo({ top: savedModalScrollY, behavior: 'instant' });
        }
        return;
    }

    let modal;
    if (typeof modalIdOrEl === 'string') {
        modal = document.getElementById(modalIdOrEl);
    } else if (modalIdOrEl instanceof Element) {
        modal = modalIdOrEl.classList.contains('simasadi-modal') ? modalIdOrEl : modalIdOrEl.closest('.simasadi-modal');
    }

    if (modal) {
        modal.classList.remove('is-active');
        returnModalToPlaceholder(modal);
    } else {
        document.querySelectorAll('.simasadi-modal').forEach((m) => {
            m.classList.remove('is-active');
            returnModalToPlaceholder(m);
        });
    }

    // Remove modal-open only if no other modal is currently active
    if (!document.querySelector('.simasadi-modal.is-active')) {
        document.documentElement.classList.remove('modal-open');
        document.body.classList.remove('modal-open');
        if (typeof savedModalScrollY === 'number' && savedModalScrollY > 0) {
            window.scrollTo({ top: savedModalScrollY, behavior: 'instant' });
        }
    }
}

// Expose globally for inline blade onclick attributes (e.g. window.openModal('modal-id'))
if (typeof window !== 'undefined') {
    window.returnModalToPlaceholder = returnModalToPlaceholder;
    window.openModal = openModal;
    window.closeModal = closeModal;
}

export const initModalListeners = () => {
    // Global event delegation for modal closing (backdrop click, close buttons, Escape key)
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList && e.target.classList.contains('simasadi-modal')) {
            window.closeModal(e.target);
        }
        const closeBtn = e.target.closest('[data-modal-close], .simasadi-modal-close');
        if (closeBtn) {
            const modal = closeBtn.closest('.simasadi-modal');
            if (modal) {
                window.closeModal(modal);
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.simasadi-modal.is-active');
            if (activeModal) {
                window.closeModal(activeModal);
            }
        }
    });

    // Prevent wheel and touch scroll on the backdrop from bubbling to the background page
    document.addEventListener('wheel', (e) => {
        const activeModal = document.querySelector('.simasadi-modal.is-active');
        if (activeModal && e.target === activeModal) {
            e.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('touchmove', (e) => {
        const activeModal = document.querySelector('.simasadi-modal.is-active');
        if (activeModal && e.target === activeModal) {
            e.preventDefault();
        }
    }, { passive: false });
};
