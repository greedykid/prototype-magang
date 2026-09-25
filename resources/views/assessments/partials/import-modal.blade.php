<div class="simasadi-modal" id="modal-import-assessments" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box modal-lg">
        <div class="simasadi-modal-head">
            <div class="modal-head-title">
                <x-icon name="upload" size="20" />
                <h4>Impor Massal Data Program Asesmen</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-import-assessments')" aria-label="Tutup modal">&times;</button>
        </div>

        <form class="modal-import-form" method="POST" action="{{ route('assessments.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-import-body">
                <p class="modal-import-lead">
                    Impor daftar agenda asesmen (Asesmen Awal, Surveilen, Re-asesmen) secara massal dari berkas Excel (.xlsx), CSV (.csv), atau tautan Google Sheets publik.
                </p>

                {{-- Langkah 1: Unduh Template --}}
                <div class="import-step-card">
                    <div>
                        <strong class="import-step-title">Langkah 1: Gunakan Template Resmi</strong>
                        <span class="import-step-desc">Unduh format template agar nama kolom sesuai dengan data LPK dan tanggal asesmen.</span>
                    </div>
                    <div class="import-template-actions">
                        <a href="{{ route('assessments.import.template', ['format' => 'xlsx']) }}" class="button secondary button-sm" title="Unduh format Microsoft Excel">
                            <x-icon name="download" size="13" />
                            <span>Template Excel (.xlsx)</span>
                        </a>
                        <a href="{{ route('assessments.import.template', ['format' => 'csv']) }}" class="button secondary button-sm" title="Unduh format teks CSV">
                            <x-icon name="download" size="13" />
                            <span>Template CSV</span>
                        </a>
                    </div>
                </div>

                {{-- Langkah 2: Pilihan Sumber Data --}}
                <div class="import-step-card bordered">
                    <strong class="import-step-header">
                        Langkah 2: Pilih Sumber Data Impor
                    </strong>

                    {{-- Opsi A: Berkas Excel / CSV --}}
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
                            Maksimal 10MB. Format kolom: Nomor Registrasi LPK, Judul Agenda, Jenis Asesmen, Tanggal Mulai, Tanggal Selesai, Lokasi, Status, Ketua Asesor, Status TP.
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
                            Pastikan tautan Google Sheets disetel ke <em>"Anyone with the link can view"</em>.
                        </small>
                    </div>
                </div>

                {{-- Catatan Integrasi LPK --}}
                <div class="import-info-note">
                    <strong>Pencocokan LPK Otomatis:</strong> Agenda asesmen akan langsung dikaitkan dengan LPK berdasarkan <strong>Nomor Registrasi LPK</strong> (contoh: <code>LP-077-IDN</code>). Pastikan data master LPK sudah diimpor terlebih dahulu.
                </div>
            </div>

            <div class="import-modal-footer">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-import-assessments')">Batal</button>
                <button type="submit" class="button primary">
                    <x-icon name="upload" size="14" />
                    <span>Mulai Proses Impor</span>
                </button>
            </div>
        </form>
    </div>
</div>
