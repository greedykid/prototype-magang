@props([
    'modalId' => 'modal-sheets-sync',
    'title' => 'Ekspor Data',
    'subtitle' => 'Unduh data dalam format berkas Excel (.xlsx) atau CSV standar.',
    'exportUrl' => '#',
    'fileName' => 'laporan-simasadi.csv',
    'columns' => [],
])

<div class="simasadi-modal" id="{{ $modalId }}" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="simasadi-modal-box" style="max-width: 620px;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge badge-blue" aria-hidden="true">
                    <x-icon name="download" size="17" />
                </div>
                <h4 id="{{ $modalId }}-title" style="margin: 0; font-size: 17px; font-weight: 700;">{{ $title }}</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('{{ $modalId }}')" aria-label="Tutup modal">&times;</button>
        </div>

        <div class="simasadi-modal-body">
            <div style="display: flex; flex-direction: column; gap: 18px;">
            <p style="margin: 0; color: var(--muted); font-size: 13.5px; line-height: 1.5;">
                {{ $subtitle }}
            </p>

            {{-- Unduh Berkas Spreadsheet & CSV --}}
            <div style="border: 1px solid var(--line); background: var(--surface-subtle, var(--surface)); border-radius: var(--radius-xl, 18px); padding: 16px; display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--line-subtle, var(--line));">
                    <div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <strong style="font-size: 13.5px; color: var(--ink);">Unduh Berkas Excel Spreadsheet (.xlsx)</strong>
                            <span style="background: var(--primary, #0075de); color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: var(--radius-pill, 9999px); text-transform: uppercase;">
                                Rekomendasi
                            </span>
                        </div>
                        <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 3px;">
                            Format resmi Microsoft Excel dengan header rapi, freeze baris atas, dan auto-filter kolom.
                        </span>
                    </div>
                    <a href="{{ $exportUrl }}{{ str_contains($exportUrl, '?') ? '&' : '?' }}format=xlsx" class="button primary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
                        <x-icon name="download" size="15" />
                        <span>Unduh Excel (.xlsx)</span>
                    </a>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <strong style="font-size: 13.5px; color: var(--ink); display: block;">Unduh Berkas CSV Standar (.csv)</strong>
                        <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 2px;">
                            Format teks standar untuk integrasi aplikasi pihak ketiga atau olah data manual.
                        </span>
                    </div>
                    <a href="{{ $exportUrl }}{{ str_contains($exportUrl, '?') ? '&' : '?' }}format=csv" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
                        <x-icon name="download" size="15" />
                        <span>Unduh CSV</span>
                    </a>
                </div>
            </div>

            @if(count($columns) > 0)
                <div style="font-size: 12px; color: var(--muted);">
                    <strong style="color: var(--ink);">Kolom yang disertakan:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px;">
                        @foreach($columns as $col)
                            <span style="background: var(--surface-hover, var(--surface)); padding: 2px 7px; border-radius: var(--radius-pill, 9999px); font-size: 11px; border: 1px solid var(--line); color: var(--ink);">{{ $col }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
            </div>
        </div>

        <div class="modal-form-actions">
            <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('{{ $modalId }}')">Tutup</button>
        </div>
    </div>
</div>
