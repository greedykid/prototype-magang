<div class="simasadi-modal" id="modal-import-lpk" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box" style="max-width: 600px;">
        <div class="simasadi-modal-head">
            <div style="display: flex; align-items: center; gap: 8px;">
                <x-icon name="upload" size="20" style="color: var(--primary);" />
                <h4 style="margin: 0; font-size: 17px; font-weight: 700;">Impor Massal Data Master LPK</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-import-lpk')" aria-label="Tutup modal">&times;</button>
        </div>

        <form method="POST" action="{{ route('lpks.import') }}" enctype="multipart/form-data">
            @csrf
            <div style="padding: 20px; display: flex; flex-direction: column; gap: 18px;">
                <p style="margin: 0; color: var(--muted); font-size: 13.5px; line-height: 1.5;">
                    Impor daftar Lembaga Penilaian Kesesuaian (LPK) secara massal dari berkas CSV atau tautan Google Sheets publik.
                </p>

                {{-- Langkah 1: Unduh Template --}}
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong style="font-size: 13px; color: #1e293b; display: block;">Langkah 1: Gunakan Template Resmi</strong>
                        <span style="font-size: 12px; color: var(--muted);">Unduh contoh format CSV untuk memastikan kolom sesuai.</span>
                    </div>
                    <a href="{{ route('lpks.import.template') }}" class="button secondary" style="font-size: 12.5px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;">
                        <x-icon name="download" size="14" />
                        <span>Unduh Template CSV</span>
                    </a>
                </div>

                {{-- Langkah 2: Pilihan Input Data --}}
                <div style="border: 1px solid var(--line); border-radius: 8px; padding: 16px; background: #fff;">
                    <strong style="font-size: 13px; color: var(--ink); display: block; margin-bottom: 12px;">
                        Langkah 2: Pilih Sumber Data Impor
                    </strong>

                    {{-- Opsi A: Unggah Berkas CSV --}}
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: var(--ink); margin-bottom: 6px;">
                            Pilihan A: Unggah Berkas CSV (.csv)
                        </label>
                        <input
                            type="file"
                            name="csv_file"
                            accept=".csv,text/csv,text/plain"
                            style="width: 100%; padding: 8px; border: 1px dashed var(--line); border-radius: 6px; background: #fafaf9; font-size: 12.5px;"
                        >
                        <small style="display: block; color: var(--muted); font-size: 11px; margin-top: 4px;">
                            Maksimal 5MB. Format baris: koma (,) atau titik koma (;).
                        </small>
                    </div>

                    <div style="text-align: center; position: relative; margin: 16px 0;">
                        <span style="background: #fff; padding: 0 10px; font-size: 11.5px; font-weight: 600; color: var(--muted); text-transform: uppercase;">
                            ATAU
                        </span>
                    </div>

                    {{-- Opsi B: Tautan Google Sheets --}}
                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: var(--ink); margin-bottom: 6px;">
                            Pilihan B: Tautan Publik Google Sheets
                        </label>
                        <input
                            type="url"
                            name="sheets_url"
                            placeholder="https://docs.google.com/spreadsheets/d/.../edit"
                            style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 12.5px;"
                        >
                        <small style="display: block; color: var(--muted); font-size: 11px; margin-top: 4px;">
                            Pastikan hak akses spreadsheet diatur ke <em>"Anyone with the link can view"</em>.
                        </small>
                    </div>
                </div>

                {{-- Catatan Smart Upsert --}}
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px 12px; font-size: 12px; color: #1e40af; line-height: 1.45;">
                    <strong>Pencegahan Duplikasi (Smart Upsert):</strong> Jika Nomor Registrasi LPK sudah terdaftar, sistem akan memperbarui profil LPK tersebut. Jika belum ada, sistem akan membuat catatan LPK baru.
                </div>
            </div>

            <div class="modal-form-actions" style="border-top: 1px solid var(--line); padding: 14px 20px; background: #fafaf9; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-import-lpk')">Batal</button>
                <button type="submit" class="button primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="upload" size="14" />
                    <span>Mulai Proses Impor</span>
                </button>
            </div>
        </form>
    </div>
</div>
