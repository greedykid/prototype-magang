/**
 * Realtime Character Counter & Hard Limit Module
 * Automatically attaches character counters to inputs and textareas with maxlength.
 * Enforces dual-layer hard stop: native maxlength + JS auto-clamp on paste/input.
 */

const EXCLUDED_TYPES = new Set([
    'hidden', 'date', 'time', 'datetime-local', 'month', 'week',
    'checkbox', 'radio', 'file', 'submit', 'button', 'reset',
    'range', 'color', 'image'
]);

function isEligibleField(el) {
    if (!el || !(el instanceof HTMLElement)) return false;
    if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') return false;

    if (el.tagName === 'INPUT') {
        const type = (el.type || 'text').toLowerCase();
        if (EXCLUDED_TYPES.has(type)) return false;
    }

    if (el.readOnly || el.disabled) return false;
    if (el.hasAttribute('data-no-counter') || el.dataset.counter === 'false') return false;
    if (typeof el.closest === 'function' && el.closest('[data-no-counter]')) return false;

    const max = parseInt(el.getAttribute('maxlength'), 10);
    return !isNaN(max) && max > 0;
}

function formatCharNum(val) {
    return val >= 1000 ? new Intl.NumberFormat('id-ID').format(val) : String(val);
}

function attachCounter(field) {
    if (field._charCounter && field._charCounter.isConnected) {
        return field._charCounter;
    }

    const max = parseInt(field.getAttribute('maxlength'), 10);
    if (isNaN(max) || max <= 0) return null;

    const targetElement = field.closest('.password-input-wrap') || field;

    let sibling = targetElement.nextElementSibling;
    if (sibling && sibling.classList.contains('field-char-counter')) {
        field._charCounter = sibling;
        return sibling;
    }

    const counter = document.createElement('span');
    counter.className = 'field-char-counter';
    counter.setAttribute('aria-live', 'polite');
    counter.setAttribute('aria-atomic', 'true');
    counter.innerHTML = `<span class="char-count-current">0</span><span class="char-count-divider">/</span><span class="char-count-max">${formatCharNum(max)}</span><span class="char-count-unit"> karakter</span>`;

    targetElement.insertAdjacentElement('afterend', counter);
    field._charCounter = counter;
    return counter;
}

export function showCounter(field) {
    if (!isEligibleField(field)) return;
    const counter = attachCounter(field);
    if (!counter) return;
    updateCounter(field);
    counter.classList.add('is-visible');
}

export function hideCounter(field) {
    if (field && field._charCounter) {
        field._charCounter.classList.remove('is-visible');
    }
}

export function updateCounter(field) {
    if (!isEligibleField(field)) return;

    const max = parseInt(field.getAttribute('maxlength'), 10);
    if (isNaN(max) || max <= 0) return;

    // Hard stop / clamp value if it exceeds maximum (e.g. from copy-paste)
    if (field.value.length > max) {
        field.value = field.value.slice(0, max);
    }

    const counter = attachCounter(field);
    if (!counter) return;

    const len = field.value.length;
    const currentSpan = counter.querySelector('.char-count-current');
    const maxSpan = counter.querySelector('.char-count-max');

    if (currentSpan) currentSpan.textContent = formatCharNum(len);
    if (maxSpan) maxSpan.textContent = formatCharNum(max);

    const ratio = len / max;
    if (ratio >= 1) {
        counter.classList.remove('is-warning');
        counter.classList.add('is-limit');
        field.classList.add('is-char-limit');
    } else if (ratio >= 0.85) {
        counter.classList.remove('is-limit');
        counter.classList.add('is-warning');
        field.classList.remove('is-char-limit');
    } else {
        counter.classList.remove('is-warning', 'is-limit');
        field.classList.remove('is-char-limit');
    }
}

export function initCharCounters(container = document) {
    const scope = container instanceof HTMLElement || container instanceof Document ? container : document;
    const fields = scope.querySelectorAll('input[maxlength], textarea[maxlength]');

    fields.forEach((field) => {
        if (isEligibleField(field)) {
            updateCounter(field);
            if (document.activeElement === field) {
                showCounter(field);
            } else {
                hideCounter(field);
            }
        }
    });
}

// Global delegated event listeners (bound once)
let isBound = false;
export function bindCharCounterEvents() {
    if (isBound) return;
    isBound = true;

    // Realtime input updates & show counter
    document.addEventListener('input', (event) => {
        if (!event.target || typeof event.target.closest !== 'function') return;
        if (isEligibleField(event.target)) {
            updateCounter(event.target);
            showCounter(event.target);
        }
    }, { passive: true });

    // Focusin: smoothly show counter when typing starts or field is focused
    document.addEventListener('focusin', (event) => {
        if (!event.target || typeof event.target.closest !== 'function') return;
        if (isEligibleField(event.target)) {
            showCounter(event.target);
        }
    }, { passive: true });

    // Focusout: smoothly hide counter when user leaves the field
    document.addEventListener('focusout', (event) => {
        if (!event.target || typeof event.target.closest !== 'function') return;
        if (isEligibleField(event.target)) {
            hideCounter(event.target);
        }
    }, { passive: true });

    // Change event (e.g. autofill) updates count
    document.addEventListener('change', (event) => {
        if (!event.target || typeof event.target.closest !== 'function') return;
        if (isEligibleField(event.target)) {
            updateCounter(event.target);
        }
    }, { passive: true });

    // Reset support
    document.addEventListener('reset', (event) => {
        if (!event.target || typeof event.target.querySelectorAll !== 'function') return;
        setTimeout(() => {
            initCharCounters(event.target);
        }, 10);
    });
}
