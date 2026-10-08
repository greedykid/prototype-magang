// ==========================================================================
// SIMASADI Drag & Drop File Upload Module
// Aligned with Notion Design System (Subtle Motion, Accessible, Theme-aware)
// ==========================================================================

export function formatBytes(bytes, decimals = 1) {
    if (!+bytes) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

export function initFileDropzone(dropzone) {
    if (!dropzone || dropzone._simasadiDropzoneInitialized) return;
    dropzone._simasadiDropzoneInitialized = true;

    const input = dropzone.querySelector('input[type="file"]');
    if (!input) return;

    const idleContent = dropzone.querySelector('.dropzone-idle-content');
    const activeContent = dropzone.querySelector('.dropzone-active-content');
    const fileNameEl = dropzone.querySelector('.dropzone-file-name');
    const fileSizeEl = dropzone.querySelector('.dropzone-file-size');

    function updateState(file) {
        if (file) {
            if (fileNameEl) fileNameEl.textContent = file.name;
            if (fileSizeEl) fileSizeEl.textContent = formatBytes(file.size);
            if (idleContent) idleContent.style.display = 'none';
            if (activeContent) activeContent.style.display = 'flex';
            dropzone.classList.add('has-file');
        } else {
            if (fileNameEl) fileNameEl.textContent = '';
            if (fileSizeEl) fileSizeEl.textContent = '';
            if (idleContent) idleContent.style.display = 'flex';
            if (activeContent) activeContent.style.display = 'none';
            dropzone.classList.remove('has-file');
        }
    }

    function resetInput() {
        input.value = '';
        updateState(null);
    }

    // Click on dropzone triggers input click, UNLESS clicked on remove button
    dropzone.addEventListener('click', (e) => {
        if (e.target.closest('.dropzone-remove-btn')) {
            e.preventDefault();
            e.stopPropagation();
            resetInput();
            return;
        }
        input.click();
    });

    // Keyboard navigation (Enter / Space)
    dropzone.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            if (e.target.closest('.dropzone-remove-btn')) return;
            e.preventDefault();
            input.click();
        }
    });

    // Input file change event
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (file) {
            // Validasi ukuran berkas maksimal 10MB
            if (file.size > 10 * 1024 * 1024) {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Ukuran Berkas Terlalu Besar',
                        text: `Ukuran berkas (${formatBytes(file.size)}) melebihi batas maksimal 10MB.`,
                        confirmButtonText: 'Mengerti',
                        confirmButtonColor: '#0075de',
                    });
                } else {
                    alert(`Ukuran berkas (${formatBytes(file.size)}) melebihi batas maksimal 10MB.`);
                }
                resetInput();
                return;
            }
            updateState(file);
        } else {
            updateState(null);
        }
    });

    // Drag and Drop event handlers
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'dragend', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('is-dragover');

        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length > 0) {
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // Reset UI when parent form is reset
    const form = dropzone.closest('form');
    if (form) {
        form.addEventListener('reset', () => {
            setTimeout(() => updateState(null), 20);
        });
    }

    // Initial check in case file is prefilled
    if (input.files && input.files[0]) {
        updateState(input.files[0]);
    } else {
        updateState(null);
    }
}

export function initFileDropzones(root = document) {
    const dropzones = (root || document).querySelectorAll('.file-dropzone, [data-dropzone]');
    dropzones.forEach(initFileDropzone);
}
