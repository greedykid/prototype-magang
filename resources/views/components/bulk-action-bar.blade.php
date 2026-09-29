@props([
    'tableId',
    'exportUrl',
    'deleteUrl',
    'entityName' => 'data',
    'canDelete' => true,
])

<div
    id="{{ $tableId }}-bulk-bar"
    class="table-bulk-bar floating-bulk-bar"
    data-table-target="{{ $tableId }}"
    data-export-url="{{ $exportUrl }}"
    data-delete-url="{{ $deleteUrl }}"
    data-entity-name="{{ $entityName }}"
    role="toolbar"
    aria-label="Aksi massal {{ $entityName }}"
    aria-live="polite"
    style="display: none;"
>
    <div class="table-bulk-bar-inner floating-bulk-bar-inner">
        <div class="bulk-bar-info">
            <span class="bulk-count-badge" id="{{ $tableId }}-selected-count">0</span>
            <span class="bulk-count-label">{{ $entityName }} terpilih</span>
        </div>

        <div class="bulk-bar-actions">
            <button
                type="button"
                class="button secondary button-sm bulk-export-trigger"
                data-action="export"
                title="Unduh data terpilih dalam format CSV"
            >
                <x-icon name="export" size="14" />
                <span>Ekspor CSV</span>
            </button>

            @if($canDelete)
                <button
                    type="button"
                    class="button danger button-sm bulk-delete-trigger"
                    data-action="delete"
                    title="Hapus seluruh data terpilih"
                >
                    <x-icon name="trash" size="14" />
                    <span>Hapus Terpilih</span>
                </button>
            @endif

            <button
                type="button"
                class="button ghost button-sm bulk-clear-trigger"
                data-action="clear"
                title="Batalkan seluruh pilihan"
                aria-label="Batalkan pilihan"
            >
                <x-icon name="x" size="14" />
                <span>Batal</span>
            </button>
        </div>
    </div>
</div>
