<div class="simasadi-modal" id="modal-import-lpk" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box modal-lg">
        <div class="simasadi-modal-head">
            <div class="modal-head-title">
                <x-icon name="upload" size="20" />
                <h4>Impor Massal Data Master LPK</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-import-lpk')" aria-label="Tutup modal">&times;</button>
        </div>

        <form method="POST" action="{{ route('lpks.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-import-body">
                <p class="modal-import-lead">
                    Impor daftar Lembaga Penilaian Kesesuaian (LPK) secara massal dari berkas Excel (.xlsx), CSV (.csv), atau tautan Google Sheets publik.
                </p>

                {{-- Langkah 1: Unduh Template --}}
                <div class="import-step-card">
                    <div>
                        <strong class="import-step-title">Langkah 1: Gunakan Template Resmi</strong>
                        <span class="import-step-desc">Unduh contoh format Excel atau CSV untuk memastikan susunan kolom sesuai.</span>
                    </div>
                    <div class="import-template-actions">
                        <a href="{{ route('lpks.import.template', ['format' => 'xlsx']) }}" class="button secondary button-sm" title="Unduh format Microsoft Excel">
                            <x-icon name="download" size="13" />
                            <span>Template Excel (.xlsx)</span>
                        </a>
                        <a href="{{ route('lpks.import.template', ['format' => 'csv']) }}" class="button secondary button-sm" title="Unduh format teks CSV">
                            <x-icon name="download" size="13" />
                            <span>Template CSV</span>
                        </a>
                    </div>
                </div>

                {{-- Langkah 2: Pilihan Input Data --}}
                <div class="import-step-card bordered">
                    <strong class="import-step-header">
                        Langkah 2: Pilih Sumber Data Impor
                    </strong>

                    {{-- Opsi A: Unggah Berkas Excel atau CSV --}}
                    <div class="import-form-group">
                        <label class="import-form-label">
                            Pilihan A: Unggah Berkas Excel (.xlsx) atau CSV (.csv)
                        </label>
                        <input
                            type="file"
                            name="csv_file"
                            accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain"
                            class="import-file-input"
                        >
                        <small class="import-form-help">
                            Maksimal 10MB. Format didukung: Excel (.xlsx) atau CSV (.csv pemisah koma / titik koma).
                        </small>
                    </div>

                    <div class="import-or-divider">
                        <span>ATAU</span>
                    </div>

                    {{-- Opsi B: Tautan Google Sheets --}}
                    <div class="import-form-group">
                        <label class="import-form-label">
                            Pilihan B: Tautan Publik Google Sheets
                        </label>
                        <input
                            type="url"
                            name="sheets_url"
                            placeholder="https://docs.google.com/spreadsheets/d/.../edit"
                            class="import-url-input"
                        >
                        <small class="import-form-help">
                            Pastikan hak akses spreadsheet diatur ke <em>"Anyone with the link can view"</em>.
                        </small>
                    </div>
                </div>

                {{-- Catatan Smart Upsert --}}
                <div class="import-info-note">
                    <strong>Pencegahan Duplikasi (Smart Upsert):</strong> Jika Nomor Registrasi LPK sudah terdaftar, sistem akan memperbarui profil LPK tersebut. Jika belum ada, sistem akan membuat catatan LPK baru.
                </div>
            </div>

            <div class="import-modal-footer">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-import-lpk')">Batal</button>
                <button type="submit" class="button primary">
                    <x-icon name="upload" size="14" />
                    <span>Mulai Proses Impor</span>
                </button>
            </div>
        </form>
    </div>
</div>
