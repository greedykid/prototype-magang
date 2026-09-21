@props([
    'modalId' => 'modal-sheets-sync',
    'title' => 'Integrasi Google Sheets & Ekspor Data',
    'subtitle' => 'Sinkronkan data secara langsung ke Google Sheets atau unduh arsip berkas CSV.',
    'exportUrl' => '#',
    'feedUrl' => '#',
    'fileName' => 'laporan-simasadi.csv',
    'columns' => []
])

<div class="simasadi-modal" id="{{ $modalId }}" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box" style="max-width: 620px;">
        <div class="simasadi-modal-head">
            <div style="display: flex; align-items: center; gap: 8px;">
                <x-icon name="sheets" size="20" style="color: #0f9d58;" />
                <h4 style="margin: 0; font-size: 17px; font-weight: 700;">{{ $title }}</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('{{ $modalId }}')" aria-label="Tutup modal">&times;</button>
        </div>

        <div style="padding: 20px; display: flex; flex-direction: column; gap: 20px;">
            <p style="margin: 0; color: var(--muted); font-size: 13.5px; line-height: 1.5;">
                {{ $subtitle }}
            </p>

            {{-- Metode 1: Live Feed Google Sheets (Formula =IMPORTDATA) --}}
            <div style="border: 1px solid #c8e6c9; background: #f4fbf5; border-radius: 8px; padding: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="background: #0f9d58; color: #fff; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; text-transform: uppercase;">
                            Rekomendasi
                        </span>
                        <strong style="font-size: 14px; color: #1b5e20;">Sinkronisasi Otomatis Google Sheets (=IMPORTDATA)</strong>
                    </div>
                </div>
                <p style="margin: 0 0 12px; font-size: 12.5px; color: #2e7d32; line-height: 1.45;">
                    Data di spreadsheet akan <strong>otomatis terbarui</strong> dari SIMASADI tanpa perlu mengunggah ulang berkas.
                </p>

                @php
                    $formula = '=IMPORTDATA("' . $feedUrl . '")';
                    $inputFeedId = 'feed-formula-' . md5($modalId);
                @endphp

                <div style="position: relative; display: flex; gap: 8px;">
                    <input
                        type="text"
                        id="{{ $inputFeedId }}"
                        readonly
                        value="{{ $formula }}"
                        style="width: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; padding: 8px 12px; background: #fff; border: 1px solid #a5d6a7; border-radius: 6px; color: #1e3a1e; font-weight: 600;"
                    />
                    <button
                        type="button"
                        class="button primary"
                        style="background: #0f9d58; border-color: #0b8043; white-space: nowrap; padding: 8px 14px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;"
                        onclick="copyFormulaToClipboard('{{ $inputFeedId }}', this)"
                    >
                        <x-icon name="copy" size="14" />
                        <span>Salin Rumus</span>
                    </button>
                </div>

                {{-- Panduan 3 Langkah --}}
                <div style="margin-top: 14px; background: rgba(255,255,255,0.7); border-radius: 6px; padding: 10px 12px; font-size: 12px; color: #2e7d32;">
                    <strong>Cara Penggunaan di Google Sheets:</strong>
                    <ol style="margin: 4px 0 0 16px; padding: 0; line-height: 1.6;">
                        <li>Buka spreadsheet baru di Google Drive (atau ketik <a href="https://sheets.new" target="_blank" rel="noopener noreferrer" style="color: #0b8043; font-weight: 600; text-decoration: underline;">sheets.new</a>).</li>
                        <li>Klik pada sel <strong>A1</strong>.</li>
                        <li>Tempelkan (Paste) rumus di atas, lalu tekan <strong>Enter</strong>. Tabel akan langsung terisi!</li>
                    </ol>
                </div>
            </div>

            {{-- Metode 2: Unduh Berkas CSV --}}
            <div style="border: 1px solid var(--line); background: #fafaf9; border-radius: 8px; padding: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <strong style="font-size: 13.5px; color: var(--ink); display: block;">Unduh Berkas CSV Standar (.csv)</strong>
                        <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 2px;">
                            Kompatibel dengan Microsoft Excel, LibreOffice Calc, atau impor manual Google Drive.
                        </span>
                    </div>
                    <a href="{{ $exportUrl }}" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
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
                            <span style="background: var(--surface-secondary, #eee); padding: 2px 7px; border-radius: 4px; font-size: 11px; border: 1px solid var(--line);">{{ $col }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="modal-form-actions" style="border-top: 1px solid var(--line); padding: 12px 20px; background: #fff; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
            <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('{{ $modalId }}')">Tutup</button>
        </div>
    </div>
</div>

<script>
    function copyFormulaToClipboard(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.select();
        input.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(input.value).then(() => {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>Tersalin!</span>';
            btn.style.background = '#2e7d32';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Rumus Google Sheets berhasil disalin!',
                    text: 'Tempelkan di sel A1 Google Sheets Anda.',
                    showConfirmButton: false,
                    timer: 2500
                });
            }

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '#0f9d58';
            }, 2000);
        }).catch(err => {
            console.error('Gagal menyalin:', err);
        });
    }
</script>
