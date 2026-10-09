import Swal from 'sweetalert2';

// State map: tableId -> Set of selected ID strings
const selectedMap = new Map();
const lastCheckedMap = new Map();

/**
 * Get or initialize selected IDs Set for a table
 */
function getSelectedSet(tableId) {
    if (!selectedMap.has(tableId)) {
        selectedMap.set(tableId, new Set());
    }
    return selectedMap.get(tableId);
}

/**
 * Sync UI for a table: row checkboxes, select-all state, row highlight, and floating bulk bar
 */
export function syncTableState(tableId) {
    const table = document.getElementById(tableId);
    const bulkBar = document.getElementById(`${tableId}-bulk-bar`);
    const countEl = document.getElementById(`${tableId}-selected-count`);
    const selectedSet = getSelectedSet(tableId);

    if (table) {
        const rowCheckboxes = Array.from(table.querySelectorAll('.table-row-select'));
        const selectAllCheckboxes = Array.from(document.querySelectorAll(`.table-select-all[data-table-id="${tableId}"]`));

        let checkedCountOnPage = 0;

        rowCheckboxes.forEach((cb) => {
            const isChecked = selectedSet.has(cb.value);
            cb.checked = isChecked;
            const row = cb.closest('tr');
            if (row) {
                row.classList.toggle('is-selected', isChecked);
            }
            if (isChecked) {
                checkedCountOnPage++;
            }
        });

        selectAllCheckboxes.forEach((selectAllCheckbox) => {
            if (rowCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCountOnPage === rowCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCountOnPage > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        });
    }

    // Sync floating bulk action bar
    if (bulkBar) {
        const totalSelected = selectedSet.size;
        if (countEl) {
            countEl.textContent = totalSelected.toString();
        }

        if (totalSelected > 0) {
            bulkBar.style.display = 'flex';
            bulkBar.removeAttribute('aria-hidden');
        } else {
            bulkBar.style.display = 'none';
            bulkBar.setAttribute('aria-hidden', 'true');
        }
    }
}

/**
 * Clear all selected items for a given table
 */
export function clearTableSelection(tableId) {
    const selectedSet = getSelectedSet(tableId);
    selectedSet.clear();
    lastCheckedMap.delete(tableId);
    syncTableState(tableId);
}

/**
 * Handle bulk export
 */
function handleBulkExport(bulkBar) {
    const tableId = bulkBar.dataset.tableTarget;
    const exportUrl = bulkBar.dataset.exportUrl;
    const selectedSet = getSelectedSet(tableId);

    if (!selectedSet || selectedSet.size === 0) {
        return;
    }

    const ids = Array.from(selectedSet).join(',');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Create a temporary hidden POST form to avoid HTTP 414 URI Too Long on large selections
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = exportUrl;
    form.style.display = 'none';

    if (csrfToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        form.appendChild(tokenInput);
    }

    const idsInput = document.createElement('input');
    idsInput.type = 'hidden';
    idsInput.name = 'ids';
    idsInput.value = ids;
    form.appendChild(idsInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => form.remove(), 1000);
}

/**
 * Handle bulk delete
 */
async function handleBulkDelete(bulkBar) {
    const tableId = bulkBar.dataset.tableTarget;
    const deleteUrl = bulkBar.dataset.deleteUrl;
    const entityName = bulkBar.dataset.entityName || 'data';
    const selectedSet = getSelectedSet(tableId);

    if (!selectedSet || selectedSet.size === 0) {
        return;
    }

    const ids = Array.from(selectedSet).map((id) => parseInt(id, 10));
    const count = ids.length;

    const result = await Swal.fire({
        title: `Hapus ${count} ${entityName} terpilih?`,
        text: `Data ${entityName.toLowerCase()} yang dipilih akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Ya, Hapus Data',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'simasadi-swal-popup',
            confirmButton: 'simasadi-swal-btn simasadi-swal-danger-btn',
            cancelButton: 'simasadi-swal-cancel-btn',
            title: 'simasadi-swal-title',
            htmlContainer: 'simasadi-swal-text',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
        const response = await fetch(deleteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ ids }),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal menghapus data.');
        }

        // Clear selection
        clearTableSelection(tableId);

        // Invalidate router cache so counts and metrics across other views update
        if (typeof window.clearPageCache === 'function') window.clearPageCache();
        if (typeof window.clearCalendarCache === 'function') window.clearCalendarCache();

        // Show success alert
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: data.message || `${count} data berhasil dihapus.`,
            timer: 2200,
            showConfirmButton: false,
            customClass: { popup: 'simasadi-swal-popup' },
        });

        // Trigger live refresh if available
        const table = document.getElementById(tableId);
        const container = table?.closest('.lpk-table-container') || table?.closest('.assessment-table-container');
        const form = container?.closest('.panel')?.querySelector('form[data-partial-filter="true"]');

        if (form && typeof window.executePartialFilter === 'function') {
            window.executePartialFilter(form, false);
        } else {
            setTimeout(() => window.location.reload(), 1200);
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal Menghapus Data',
            text: err.message || 'Terjadi kesalahan pada server saat menghapus data terpilih.',
            customClass: { popup: 'simasadi-swal-popup' },
        });
    }
}

let tableMultiselectInitialized = false;

/**
 * Initialize event listeners for table multiselection
 */
export function initTableMultiselect() {
    // Sync all active tables on page
    document.querySelectorAll('table[id]').forEach((table) => {
        syncTableState(table.id);
    });

    if (tableMultiselectInitialized) {
        return;
    }
    tableMultiselectInitialized = true;

    // 1. Select-All checkbox toggle
    document.addEventListener('change', (event) => {
        const selectAll = event.target.closest('.table-select-all');
        if (!selectAll) return;

        const tableId = selectAll.dataset.tableId;
        if (!tableId) return;

        const table = document.getElementById(tableId);
        if (!table) return;

        const selectedSet = getSelectedSet(tableId);
        const rowCheckboxes = Array.from(table.querySelectorAll('.table-row-select'));

        if (selectAll.checked) {
            rowCheckboxes.forEach((cb) => selectedSet.add(cb.value));
        } else {
            rowCheckboxes.forEach((cb) => selectedSet.delete(cb.value));
        }

        syncTableState(tableId);
    });

    // 2. Individual Row checkbox change with Shift-Click range selection
    document.addEventListener('click', (event) => {
        const checkbox = event.target.closest('.table-row-select');
        if (!checkbox) return;

        const tableId = checkbox.dataset.tableId;
        if (!tableId) return;

        const table = document.getElementById(tableId);
        if (!table) return;

        const selectedSet = getSelectedSet(tableId);
        const rowCheckboxes = Array.from(table.querySelectorAll('.table-row-select'));
        const lastChecked = lastCheckedMap.get(tableId);

        if (event.shiftKey && lastChecked && lastChecked !== checkbox && rowCheckboxes.includes(lastChecked)) {
            const startIdx = rowCheckboxes.indexOf(lastChecked);
            const endIdx = rowCheckboxes.indexOf(checkbox);
            const range = rowCheckboxes.slice(Math.min(startIdx, endIdx), Math.max(startIdx, endIdx) + 1);

            const targetState = checkbox.checked;
            range.forEach((cb) => {
                cb.checked = targetState;
                if (targetState) {
                    selectedSet.add(cb.value);
                } else {
                    selectedSet.delete(cb.value);
                }
            });
        } else {
            if (checkbox.checked) {
                selectedSet.add(checkbox.value);
            } else {
                selectedSet.delete(checkbox.value);
            }
        }

        lastCheckedMap.set(tableId, checkbox);
        syncTableState(tableId);
    });

    // 3. Floating bulk action buttons
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.bulk-export-trigger, .bulk-delete-trigger, .bulk-clear-trigger');
        if (!btn) return;

        const bulkBar = btn.closest('.table-bulk-bar, .floating-bulk-bar');
        if (!bulkBar) return;

        const action = btn.dataset.action;
        const tableId = bulkBar.dataset.tableTarget;

        if (action === 'export') {
            handleBulkExport(bulkBar);
        } else if (action === 'delete') {
            handleBulkDelete(bulkBar);
        } else if (action === 'clear') {
            clearTableSelection(tableId);
        }
    });

    // 4. Keyboard accessibility: Escape clears active selection
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        // Find visible bulk bars
        document.querySelectorAll('.table-bulk-bar, .floating-bulk-bar').forEach((bar) => {
            if (bar.style.display !== 'none') {
                const tableId = bar.dataset.tableTarget;
                if (tableId) {
                    clearTableSelection(tableId);
                }
            }
        });
    });

    // 5. Re-sync whenever live-filter or pagination updates table HTML
    document.addEventListener('table-content-updated', (event) => {
        const container = event.detail?.container;
        if (container) {
            const table = container.querySelector('table[id]');
            if (table) {
                syncTableState(table.id);
            }
        } else {
            document.querySelectorAll('table[id]').forEach((table) => {
                syncTableState(table.id);
            });
        }
    });
}
