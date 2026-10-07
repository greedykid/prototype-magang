# Product Requirement Document (PRD)
## SIMASADI: Sistem Informasi Manajemen Akreditasi & Surveilen Lembaga Penilaian Kesesuaian
### Komite Akreditasi Nasional (KAN) / Badan Standardisasi Nasional (BSN)

---

## 1. Dokumen Ringkasan Eksekutif (Executive Summary)

### 1.1 Latar Belakang & Identitas Sistem
**SIMASADI** adalah sistem informasi dan manajemen operasional terpadu yang dirancang khusus untuk memfasilitasi, memantau, dan memvalidasi siklus hidup akreditasi Lembaga Penilaian Kesesuaian (LPK) di bawah naungan Komite Akreditasi Nasional (KAN) dan Badan Standardisasi Nasional (BSN). LPK yang dikelola mencakup berbagai skema akreditasi resmi KAN, antara lain:
* **LP**: Laboratorium Pengujian (SNI ISO/IEC 17025)
* **LK**: Laboratorium Kalibrasi (SNI ISO/IEC 17025)
* **LM**: Laboratorium Medis (SNI ISO 15189)
* **LSPr**: Lembaga Sertifikasi Produk, Proses, dan Jasa (SNI ISO/IEC 17065)
* **LSSM**: Lembaga Sertifikasi Sistem Manajemen Mutu (SNI ISO/IEC 17021-1)
* **LSE**: Lembaga Sertifikasi Sistem Manajemen Lingkungan (SNI ISO/IEC 17021-1)
* **LI**: Lembaga Inspeksi (SNI ISO/IEC 17020)
* **PTP**: Penyelenggara Uji Kemahiran (SNI ISO/IEC 17043)
* **BPP**: Produsen Bahan Acuan (SNI ISO 17034)

Aplikasi ini mengintegrasikan seluruh siklus pengawasan berkala (Surveilen KAN), 8 proses asesmen resmi KAN, penegakan Service Level Agreement (SLA) Tindakan Perbaikan (TP) dan Verifikasi Tindakan Perbaikan (VTP), alur Evaluasi Hasil Asesmen (EHA), akses portal khusus asesor, kalender interaktif SPA dengan auto-focus deep-link, mesin live filter parsial instan tanpa reload, hingga pelaporan otomatis Google Sheets.

### 1.2 Tujuan Dokumen PRD
Dokumen ini berfungsi sebagai spesifikasi kebutuhan produk komprehensif yang merangkum seluruh domain bisnis, arsitektur data, logika kalkulasi hukum akreditasi KAN, alur kerja antar-peran, dan kebutuhan fungsional dari sistem operasional SIMASADI terkini. Dokumen ini menjadi acuan teknis baku bagi pengembangan sistem berjalan maupun migrasi platform di lingkungan KAN dan BSN.

---

## 2. Profil Pengguna & Hak Akses (Role-Based Access Control)

Sistem SIMASADI mengimplementasikan pemisahan hak akses berbasis **2 peran (*roles*) operasional**:

| Peran (*Role*) | Akun Referensi | Deskripsi Tanggung Jawab | Hak Akses Utama | Batasan Keamanan |
| :--- | :--- | :--- | :--- | :--- |
| **Ketua Tim (Admin Unit)** (`admin`) | `admin@simasadi.local` | Penanggung jawab tata kelola sistem, administrasi operasional akreditasi, dan verifikasi kepatuhan. | Akses penuh (*Full Control*): Registrasi, ubah, dan hapus LPK; impor massal LPK dan Asesmen (CSV/XLSX/Google Sheets); penjadwalan 8 tipe asesmen KAN; pemrosesan EHA; penugasan PIC; pencatatan SK KAN; ekspor data Google Sheets live feed; manajemen pengguna; pembatasan tunggal 1 akun Ketua Tim; dan pemicu notifikasi surveilen. | Sistem membatasi hanya 1 akun Ketua Tim yang dapat aktif dalam basis data. |
| **PIC Laboratorium** (`pic`) | `pic@simasadi.local` | Narahubung / personel operasional unit teknis yang mendampingi LPK binaan. | Akses pengelolaan LPK binaannya: melihat profil LPK kelolaan (dan LPK tertaut sebagai viewer), memantau tenggat waktu surveilen dan toleransi pengisian, melihat kalender agenda kerja, pencatatan kemajuan tindakan perbaikan (TP), serta penginputan nomor surat permohonan perpanjangan waktu. | Dibatasi secara terarah (*403 Forbidden*): dilarang mengimpor data asesmen massal, dilarang mengakses data LPK di luar tanggung jawabnya tanpa tautan viewer, dan dilarang mengelola akun pengguna sistem. |

---

## 3. Spesifikasi Kebutuhan Fungsional (Functional Requirements)

### 3.1 Modul 1: Manajemen Master Lembaga Penilaian Kesesuaian (LPK)
* **Karakteristik Data**:
  * Nomor Registrasi resmi KAN (`no_reg` dan `registration_number`), bersifat unik (contoh: `LP-001-IDN`, `LK-015-IDN`, `LSPr-014-IDN`).
  * Nama Lembaga, Skema Akreditasi KAN (`kan_schema`: Laboratorium Pengujian ISO/IEC 17025, Laboratorium Kalibrasi ISO/IEC 17025, Lembaga Sertifikasi Produk ISO/IEC 17065, Lembaga Inspeksi ISO/IEC 17020, dll.).
  * Alamat Lengkap, Kota, Provinsi, Alamat Email PIC, Nomor Telepon/Kontak.
  * Penugasan PIC Pendamping (`pic_user_id`) yang terhubung langsung ke entitas pengguna ber-role `pic`.
  * Tanggal Terbit Sertifikat Akreditasi (`certificate_date`) dan Tanggal Kedaluwarsa Sertifikat (`expired_at`).
  * Aturan Otomasi Kedaluwarsa: Jika `expired_at` dikosongkan pada form pendaftaran, sistem otomatis menghitung `+5 tahun` dari `certificate_date`.
  * Integrasi Dokumen: Menggunakan tautan Google Drive terpadu (`drive_url`) untuk berkas sertifikat, lampiran ruang lingkup, dan SK akreditasi.
* **Mesin Status Akreditasi Dinamis (Smart Dynamic Status Engine)**:
  Status LPK tidak statis di basis data, melainkan dievaluasi secara dinamis di memori berdasarkan aturan kepatuhan KAN:
  1. `REVOKED` (Badge Merah Pekat / Dark Red): Akreditasi dicabut karena telah melewati masa toleransi pembekuan 1 tahun tanpa penyelesaian asesmen surveilen.
  2. `SUSPENDED` (Badge Ungu - `#f3e8ff` teks `#6b21a8`): Status LPK dibekukan sementara karena melewati batas toleransi pengisian surveilen atau melewati batas SLA tindakan perbaikan tanpa pemenuhan.
  3. `EXPIRED` (Badge Merah): Masa berlaku sertifikat akreditasi telah habis (`now > expired_at`).
  4. `SURVEILLANCE_OVERDUE` (Badge Merah): LPK berstatus aktif, namun telah melewati target pelaksanaan Surveilen 1 (bulan 15) atau Surveilen 2 (bulan 36) tanpa agenda asesmen.
  5. `SURVEILLANCE_DUE` (Badge Kuning): LPK yang memasuki jendela notifikasi pengawasan aktif (bulan 14 untuk S1, bulan 35 untuk S2, atau 1 bulan sebelum habis untuk Re-Akreditasi).
  6. `ACTIVE` (Badge Hijau): LPK aktif, seluruh siklus pengawasan terpenuhi atau terjadwal aman.
  7. `INACTIVE` (Badge Abu-abu): Status LPK dinonaktifkan secara administratif oleh administrator.
* **Struktur Kartu Keterangan Bertumpuk (Dual Notes Stack)**:
  Pada halaman rincian LPK (`lpks/show`), keterangan disajikan dalam dua kartu berdampingan yang saling melengkapi:
  1. **Status Siklus (Otomatis)**: Menampilkan nama proses dan status kepatuhan terhitung sistem, diberi badge kategori tematik (Jatuh Tempo [Rose], Reminder [Amber], Batas TP [Cyan], Pelaksanaan [Emerald]).
  2. **Catatan Khusus PIC (Manual)**: Area catatan internal yang diinput oleh personel pendamping PIC laboratorium, dilengkapi modal form edit yang memungkinkan PIC langsung menyalin redaksi keterangan otomatis sistem atau memasukkan catatan koordinasi khusus.
* **Impor Massal Data Master (Smart Upsert)**:
  * Mendukung unggah berkas `.csv` (koma `,` atau titik koma `;`) dan berkas spreadsheet `.xlsx`.
  * Mendukung penarikan langsung dari tautan publik Google Sheets via HTTP.
  * Logika *Smart Upsert*: Jika nomor registrasi sudah ada di basis data, perbarui data profilnya; jika belum ada, buat entri baru.
  * Tersedia fitur unduh template resmi `template-import-lpk.csv` dan `template-import-lpk.xlsx`.

---

### 3.2 Modul 2: Siklus Pengawasan & 8 Tipe Proses Asesmen KAN (KAN U-01)
* **8 Tipe Proses Asesmen Resmi KAN**:
  Sistem mendukung penuh klasifikasi 8 proses asesmen sesuai pedoman KAN U-01:
  1. `INITIAL`: Akreditasi Awal (AA)
  2. `SURVEILLANCE`: Surveilen 1 (S1)
  3. `SURVEILLANCE_PRL`: Surveilen 1 + Perluasan Ruang Lingkup (S1 + PRL)
  4. `SURVEILLANCE_2`: Surveilen 2 (S2)
  5. `SURVEILLANCE_2_PRL`: Surveilen 2 + Perluasan Ruang Lingkup (S2 + PRL)
  6. `UNSCHEDULED_SURVEILLANCE`: Surveilen Tidak Terjadwal (STT)
  7. `SCOPE_EXTENSION`: Perluasan Ruang Lingkup (PRL)
  8. `REASSESSMENT`: Re-Akreditasi / Akreditasi Ulang (RA)
* **Standarisasi Penamaan Asesmen**:
  Format judul asesmen distandarisasi menggunakan singkatan resmi KAN:
  * Asesmen Re-Akreditasi distandarisasi ke format: `Asesmen Re-Akreditasi (RA) - [Nama LPK]`.
  * Surveilen 1 distandarisasi ke format: `Asesmen Surveilen 1 (S1) - [Nama LPK]`.
  * Surveilen 2 distandarisasi ke format: `Asesmen Surveilen 2 (S2) - [Nama LPK]`.
* **Klasifikasi Proses Akreditasi Berdasarkan Siklus & Baseline**:
  * **1. Proses Periodik 5 Tahunan (Siklus Pengawasan Rutin)**:
    - Meliputi: Surveilen 1 (S1), Surveilen 2 (S2), dan Re-Akreditasi (RA).
    - Baseline Perhitungan: Menggunakan masa berlaku akreditasi (tanggal terbit sertifikat `certificate_date` dan tanggal kedaluwarsa `expired_at`).
    - *Survailen 1 (S1)*: Jendela notifikasi aktif pada bulan ke-13 sampai 14; target kunjungan asesmen lapangan bulan ke-15 sampai 18; rentang validasi dan batas toleransi maksimal hingga bulan ke-24.
    - *Survailen 2 (S2)*: Jendela notifikasi aktif pada bulan ke-34 sampai 35; target kunjungan asesmen lapangan bulan ke-36 sampai 39; rentang validasi dan batas toleransi maksimal hingga bulan ke-48.
    - *Re-Akreditasi (RA)*: Jendela notifikasi aktif pada bulan ke-48 sampai 51 (pengajuan dokumen permohonan akreditasi ulang); pelaksanaan asesmen lapangan harus tuntas sebelum tanggal kedaluwarsa sertifikat pada bulan ke-60 (`expired_at`).
  * **2. Proses Non-Periodik / Ad-Hoc (Berdasarkan Permohonan atau Insidental)**:
    - Meliputi: Akreditasi Awal (AA), Perluasan Ruang Lingkup (PRL Mandiri), dan Survailen Tidak Terjadwal (STT).
    - Karakteristik: Tidak terikat otomatis pada siklus periodik tahunan sertifikat berjalan. Akreditasi Awal merupakan pendaftaran baru sebelum adanya sertifikat; PRL diajukan kapan saja saat lab mengajukan penambahan parameter atau metode baru; STT dipicu oleh aduan masyarakat, perubahan personel kunci, relokasi, atau verifikasi pemulihan status pembekuan.
    - Batas Waktu Tindakan Perbaikan: Meskipun waktu awal asesmen bersifat ad-hoc/permohonan, penegakan batas waktu tindakan perbaikan temuan asesmen (TP & VTP) tetap berjalan otomatis dan terikat ketat dengan baseline tanggal selesai realisasi asesmen (`end_at`).

---

### 3.3 Modul 3: Toleransi Pengisian & Siklus Hidup 3 Tahap Pengawasan
Sistem memberlakukan aturan siklus hidup bertingkat (*Three-Stage Surveillance Lifecycle*) untuk menegakkan disiplin akreditasi:
1. **Tahap 1: Toleransi Pengisian Berjalan (Normal / In Progress)**:
   * **Standar Regulasi KAN**: Batas waktu maksimal Toleransi Pengisian dokumen surveilen dihitung **4 bulan dari bulan ke-15** siklus akreditasi (Bulan 15 + 4 = Bulan ke-19 dari tanggal sertifikat akreditasi LPK `certificate_date`, atau fallback 4 bulan dari tanggal pelaksanaan asesmen).
   * **Penyimpanan Basis Data & Fleksibilitas Input**: Batas toleransi pengisian dicatat permanen pada kolom `submission_due_date` di tabel `assessments`. Formulir asesmen menampilkan nilai bawaan (*default*) secara otomatis berdasarkan siklus LPK, serta memberikan fleksibilitas penginputan manual (*custom input*) bagi petugas/PIC jika terdapat kondisi khusus penyesuaian proses di lapangan.
   * **Evaluasi Status Berjalan**: Selama tanggal saat ini belum melewati batas toleransi pengisian (`submission_due_date`) dan jadwal pelaksanaan asesmen tidak di masa mendatang, status asesmen tetap `PLANNED` atau `IN_PROGRESS`.
2. **Tahap 2: Pembekuan Akreditasi & Jendela Penyelesaian 1 Tahun (Suspended)**:
   * Jika batas toleransi pengisian (`submission_due_date`) telah terlewati dan asesmen belum dinyatakan selesai, status asesmen dan status LPK seketika beralih secara otomatis menjadi **DIBEKUKAN** (`SUSPENDED`).
   * Badge status ditampilkan dengan warna ungu kontras (`#f3e8ff` dengan teks `#6b21a8`) agar berbeda jelas dengan status merah (kedaluwarsa/lewat jadwal).
   * LPK diberikan hak dan kesempatan untuk menyelesaikan pembekuannya selama **1 tahun penuh** terhitung sejak tanggal toleransi (`submission_due_date + 1 year`).
   * Antarmuka menyajikan informasi hitung mundur masa penyelesaian pembekuan (jumlah hari dan bulan tersisa).
3. **Tahap 3: Pencabutan Status Akreditasi (Revoked)**:
   * Jika kesempatan 1 tahun telah habis (`is_suspension_expired = true`) dan LPK belum juga menyelesaikan kewajiban asesmennya, status akreditasi LPK seketika beralih otomatis menjadi **DICABUT** (`REVOKED`).
   * LPK berstatus dicabut menerima badge merah tua dan dikeluarkan dari daftar lembaga aktif.
4. **Aturan Auto-Realisasi Data Historis LPK Aktif**:
   * Apabila status akreditasi LPK saat ini tercatat AKTIF (`ACTIVE`), maka seluruh data surveilen, tindakan perbaikan, dan asesmen dari tahun-tahun sebelumnya (`end_at < now()->startOfYear()`) secara otomatis diakui telah selesai dan memenuhi (`COMPLETED` dan `SATISFIED`).
   * Aturan ini mencegah anomali di mana LPK aktif memiliki status masa lalu yang menggantung sebagai dibekukan.

---

### 3.4 Modul 4: Pelacakan Tindakan Perbaikan (TP & VTP) Berbasis Batas Waktu KAN
* **Baseline Perhitungan Batas Waktu Tindakan Perbaikan**:
  * Sistem menggunakan tanggal selesai realisasi pelaksanaan asesmen (`end_at`) sebagai baseline tunggal penentuan batas waktu tindakan perbaikan.
* **Matriks Batas Waktu & Skema Notifikasi**:
  * **Akreditasi Awal (AA)**:
    - SLA Dasar: 3 bulan kalender sejak tanggal realisasi asesmen (`end_at`).
    - Notifikasi Pengingat 1: Terbit pada bulan ke-2 (H-1 bulan sebelum batas jatuh tempo awal).
    - Notifikasi Final: Terbit tepat pada batas waktu bulan ke-3.
  * **Proses Asesmen Lainnya (S1, S2, PRL, RA, STT)**:
    - SLA Dasar: 2 bulan kalender sejak tanggal realisasi asesmen (`end_at`).
    - Notifikasi Pengingat 1: Terbit pada bulan ke-1 (H-1 bulan sebelum batas jatuh tempo awal).
    - Notifikasi Final: Terbit tepat pada batas waktu bulan ke-2.
* **Ketentuan Perpanjangan Waktu (Extension)**:
  * Durasi perpanjangan maksimal adalah **1 bulan kalender** (`tp_extension_months = 1`), menghasilkan `effective_tp_due_date = tp_due_date + 1 bulan`.
  * Ketentuan persetujuan perpanjangan:
    1. Laboratorium harus sudah menunjukkan bukti perbaikan nyata terhadap temuan ketidaksesuaian asesmen (telah dilakukan perbaikan sebagian meskipun belum sempurna).
    2. Wajib menyertakan Nomor Surat Permohonan Perpanjangan Resmi dari LPK (`tp_extension_letter_no`).
  * Otomasi Antarmuka: Penginputan nomor surat resmi pada form secara otomatis memperpanjang tanggal jatuh tempo perbaikan sebesar +1 bulan dan memunculkan badge visual `(+1 Bulan Surat Resmi)`.
  * **Larangan Perpanjangan**: Jika selama masa awal (2 bulan atau 3 bulan) laboratorium belum melakukan perbaikan sama sekali (progres kosong), perpanjangan TIDAK DIBERIKAN dan proses dapat dihentikan oleh PIC.
  * **Total Waktu Maksimal Setelah Perpanjangan**:
    * Akreditasi Awal (AA): 3 bulan + 1 bulan = **4 bulan**.
    * S1, S2, PRL, RA, STT: 2 bulan + 1 bulan = **3 bulan**.
* **Implementasi Khusus Peran PIC (Role Scoping)**:
  * Dasbor operasional, Kalender Kerja, dan tabel asesmen menerapkan filter ketat berbasis kepemilikan LPK.
  * Personel PIC (Lead PIC dan Viewer PIC via scope `accessibleBy`) hanya menerima notifikasi, peringatan LPK mendesak, dan event agenda untuk LPK binaan yang ditugaskan kepadanya.
* **Otomasi Status Tindakan Perbaikan Tanpa Input Manual**:
  * Status TP tidak diinput secara manual pada form, melainkan dihitung otomatis oleh sistem (*read-only dynamic status*):
    * Selama tanggal dinyatakan memenuhi (`tp_satisfied_at`) belum diisi:
      - Jika belum melewati batas waktu: status berbunyi **Sedang Berlangsung** (`IN_PROGRESS`).
      - Jika telah melewati batas waktu (baik batas awal maupun perpanjangan): status otomatis berubah menjadi **Dibekukan** (`SUSPENDED`, badge ungu).
    * Jika tanggal dinyatakan memenuhi (`tp_satisfied_at`) telah terisi: status berubah menjadi **Memenuhi / Selesai** (`COMPLETED` / `SATISFIED`).
* **Kanal Notifikasi & Status Implementasi**:
  * **In-App UI (Dasbor Operasional & Kalender SPA)**: Sudah diimplementasikan dan aktif penuh. Menampilkan banner pengawasan persisten wajib, kartu ringkasan tindakan perbaikan mendesak, badge hitung mundur relatif, serta event kalender pengingat TP dan overdue TP.
  * **Notifikasi Email**: Pengiriman email otomatis saat ini aktif untuk pengingat siklus surveilen LPK; otomasi pengiriman email untuk pengingat tenggat tindakan perbaikan (TP & VTP) asesmen disiapkan pada rencana rilis berikutnya.
  * **Pengingat Penerbitan SK**: 10 hari kalender setelah tanggal TP dinyatakan memenuhi (khusus S1, S2, dan STT).

---

### 3.5 Modul 5: Alur Evaluasi Hasil Asesmen (EHA) & Penerbitan SK KAN
* **Pencatatan Evaluasi Hasil Asesmen (EHA)**:
  * Tanggal Rencana EHA (`eha_scheduled_date`) dan Tanggal Realisasi EHA (`eha_completed_at`).
  * Catatan dan rekomendasi sidang panitia teknis / tim evaluasi (`eha_notes`).
* **Penerbitan SK Akreditasi KAN**:
  * Nomor SK resmi KAN (`sk_number`) dan Tanggal Terbit SK (`sk_issued_at`).
  * Perhitungan Otomatis Lead Time Terbit SK (`sk_lead_time_days`): Menghitung selisih hari kerja/kalender sejak pemenuhan tindakan perbaikan hingga SK resmi diterbitkan.

---

### 3.6 Modul 6: Penjadwalan & Kalender Interaktif Multi-Event
* **Dukungan 5 Tipe Event Kalender**:
  1. `assessment`: Kunjungan Asesmen Lapangan Resmi KAN (Warna Biru / Blue).
  2. `surveillance_reminder`: Pengingat Jadwal Kunjungan Surveilen LPK (Warna Kuning / Oranye).
  3. `tp_reminder`: Pengingat Batas Tindakan Perbaikan (Warna Indigo / Biru Muda).
  4. `tp_overdue`: Batas Waktu Terlampaui Tindakan Perbaikan (Warna Merah / Ungu).
  5. `sk_reminder`: Pengingat Target Penerbitan SK KAN (Warna Hijau / Emerald).
* **4 Mode Tampilan Kalender**:
  Mendukung navigasi pergantian tampilan tanpa reload penuh:
  * **Bulan (*Month View*)**: Grid kalender bulanan dengan batas maksimal 3 event utama dan indikator penampung *+X lainnya*. Event target highlight otomatis diprioritaskan tampil di posisi teratas.
  * **Minggu (*Week View*)**: Tampilan kolom per jam dalam rentang 7 hari.
  * **Hari (*Day View*)**: Tampilan detil agenda per jam untuk tanggal terpilih.
  * **Agenda (*Agenda View*)**: Tampilan daftar baris terstruktur kronologis.
* **Arsitektur SPA Partial Kalender**:
  * Navigasi bulan, tahun, tampilan (*view*), dan tanggal menggunakan permintaan AJAX parsial (`view_partial=1`).
  * Server hanya me-render shell kalender (`calendar/partials/calendar-shell.blade.php`), mengganti konten secara instan tanpa flicker dan tanpa memuat ulang seluruh layout aplikasi (*App Shell*).
* **Navigasi Cepat & Resolusi Rentang Tahun Dinamis**:
  * Dropdown pemilih langsung bulan dan tahun (*Direct Month & Year Selector*).
  * Rentang pilihan tahun tidak kaku/statis, melainkan dihitung otomatis oleh server mencakup tahun terlama dari riwayat sertifikat hingga tahun terjauh dari tanggal kedaluwarsa dan jadwal asesmen masa depan di basis data.
* **Pola Deep-Link & Auto-Focus Kalender**:
  * Format tautan langsung: `/calendar?view=month&date=YYYY-MM-DD&highlight={event_id}&selected=1`.
  * **Resolusi Tanggal Otomatis di Backend (*Auto-Date Resolution*)**: Jika dipanggil hanya dengan parameter `?highlight={id}` (contoh: `highlight=12` atau `highlight=assessment-12`), sistem secara otomatis mencari tanggal jadwal entitas tersebut di basis data dan langsung membuka bulan/tahun yang sesuai, bahkan jika jadwalnya 1, 2, atau 3 tahun ke depan.
  * **Umpan Balik Visual & Animasi Pulse**: Tanggal target langsung ditandai dengan badge lingkaran solid navy (`#1e3a5f`), dan chip/kartu event menerima animasi pulse halus 3 siklus (`@keyframes gcal-highlight-pulse`) yang berhenti secara elegan tanpa infinite loop.
  * **Auto-Scroll & Auto-Open Popover**: Halaman secara otomatis menggulir halus (*smooth scroll*) ke posisi elemen target di layar, dan setelah 360ms popover rincian agenda terbuka secara otomatis.

---

### 3.7 Modul 7: Mesin Pencarian & Filter Parsial Live (Partial AJAX Filter Engine)
* **Live Search & Filter Tanpa Full-Page Reload**:
  * Diterapkan pada tabel utama Master LPK (`/lpks`) dan Daftar Asesmen (`/assessments`).
  * Modul JavaScript mandiri (`resources/js/modules/live-filter.js`) mengelola input pencarian teks dengan teknik *debounce* 280 ms dan pemantauan perubahan pada select filter (*status*, *assessment_type*, *tp_status*, *lpk_id*).
  * Permintaan dikirim via AJAX dengan header `X-Requested-With: XMLHttpRequest` dan parameter `partial=1`.
  * Server merespons hanya dengan potongan HTML tabel (`lpks/partials/table-content.blade.php` atau `assessments/partials/table-content.blade.php`), yang langsung diperbarui ke DOM tanpa mengganggu posisi scroll atau fokus pengguna.
* **Sinkronisasi State & Riwayat Peramban (URL State Preservation)**:
  * URL pada address bar otomatis disinkronkan menggunakan `history.replaceState` mencerminkan query aktif, sehingga URL hasil filter dapat disalin atau dibagikan secara akurat.
  * Tombol navigasi pagination tabel otomatis mempertahankan parameter filter aktif.
* **Pembaruan Badge Filter & Indikator Hasil Real-Time**:
  * Badge filter aktif (`.filter-badges` / `.filter-pill`) diperbarui seketika di atas tabel saat filter dipilih atau dihapus, lengkap dengan tombol hapus satu per satu (*cross button*) dan tombol reset total.
  * Teks penghitung total data (*results count*) diperbarui secara real-time.
  * Menampilkan state kosong (*empty state*) yang informatif dan ramah ketika pencarian tidak menghasilkan kecocokan.

---

### 3.8 Modul 8: Impor Massal Asesmen & LPK
* **Impor Data Asesmen Massal**:
  * Fitur unggah CSV untuk penjadwalan banyak asesmen sekaligus dengan pemetaan otomatis: nomor registrasi LPK, tipe asesmen KAN, nama asesor kepala, tanggal mulai, dan tanggal selesai.
* **Impor Data LPK (Smart Upsert)**:
  * Mendukung pemrosesan data LPK dengan template baku `.csv` dan `.xlsx`.
  * Penarikan berkas dari tautan publik Google Sheets secara langsung.

---

### 3.9 Modul 9: Live Feed Google Sheets & Laporan
* **Live CSV Feeds (`=IMPORTDATA`)**:
  * Endpoint publik terlindungi API key parameter rahasia:
    * `/feeds/lpks.csv?key=simasadi-live`: Rekapitulasi direktori master LPK dan status akreditasi.
    * `/feeds/assessments.csv?key=simasadi-live`: Rekapitulasi jadwal asesmen lapangan dan status TP.
* **Ekspor CSV Terotentikasi**:
  * Ekspor data dengan encoding UTF-8 BOM untuk kompatibilitas penuh dengan Microsoft Excel.

---

## 4. Struktur Basis Data & Hubungan Antar-Entitas (Data Architecture & ERD)

SIMASADI menggunakan skema relasional terpadu yang telah dirampingkan dari modul usang dan diperkaya dengan atribut regulasi KAN:

```
+-----------------------------------------------------------------------------------------+
|                                         USERS                                           |
| id (PK), name, email, password, role (admin/pic/assessor/lpk), timestamps               |
+-----------------------------------------------------------------------------------------+
       │                                                                      │
       │ (1 to N) Penugasan PIC                                               │ (1 to N) Pembuat Event
       ▼                                                                      ▼
+-----------------------+                                            +--------------------+
|         LPKS          |                                            |  CALENDAR_EVENTS   |
| id (PK), no_reg,      |                                            | id (PK), lpk_id,   |
| registration_number,  |                                            | event_type,        |
| name, kan_schema,     |                                            | title, start_at,   |
| scope, address, email,|                                            | end_at, is_all_day |
| status, expired_at,   |                                            +--------------------+
| pic_user_id (FK),     |                                                       ▲
| certificate_date,     |                                                       │
| notes                 |                                                       │
+-----------------------+                                                       │
       │                                                                        │
       ├────────────────────────────────────────────────────────────────────────┘
       │ (1 to N)
       ▼
+-----------------------------------------------------------------------------------------+
|                                      ASSESSMENTS                                        |
| id (PK), lpk_id (FK), created_by (FK), title, assessment_type (8 tipe KAN U-01),        |
| start_at, end_at, location, lead_assessor, status (PLANNED/IN_PROGRESS/COMPLETED/etc.), |
| tp_sla_months (2/3 bln), tp_due_date, tp_has_extension, tp_extension_months (max 1),    |
| tp_extension_letter_no, tp_extended_due_date, tp_submitted_at, tp_satisfied_at,        |
| sk_issued_at, sk_number, sk_lead_time_days, eha_scheduled_date, eha_completed_at        |
+-----------------------------------------------------------------------------------------+
       │
       │ (1 to N)
       ▼
+-----------------------------------------------------------------------------------------+
|                                     ACCREDITATIONS                                      |
| id (PK), lpk_id (FK), start_date, pantek_at, target_output_at, output_released_at       |
+-----------------------------------------------------------------------------------------+
       │
       │ (1 to N)
       ▼
+-----------------------------------------------------------------------------------------+
|                                      LPK_MEMBERS                                        |
| id (PK), lpk_id (FK), user_id (FK), role (viewer), created_at, updated_at               |
+-----------------------------------------------------------------------------------------+
       ▲
       │ (1 to N)
+-----------------------------------------------------------------------------------------+
|                                  USER_ACCOUNT_LINKS                                     |
| id (PK), user_id (FK), viewer_id (FK), created_at, updated_at                           |
+-----------------------------------------------------------------------------------------+
```

### 4.7 Modul 7: Sistem Kolaborasi Tim & Tautan Akun Multi-PIC (Viewer Role)
* **Tujuan**: Memfasilitasi pembagian beban kerja pendampingan LPK antar-analis PIC tanpa mengorbankan integritas data.
* **Peran Tim**:
  * **Lead PIC**: Personel utama yang ditugaskan membina LPK (`lpks.pic_id`), memiliki wewenang penuh untuk mengubah profil LPK, mengunggah bukti perbaikan, dan menjadwalkan agenda.
  * **Viewer PIC**: Personel PIC rekanan yang ditautkan ke LPK binaan, memiliki akses baca menyeluruh (*read-only*) untuk meninjau direktori, riwayat asesmen, dan progres perbaikan.
  * **Restriksi Ketat**: Akun Viewer dilarang mengedit profil LPK, dilarang menghapus LPK, dilarang mengelola anggota tim, dan dilarang membuat atau memodifikasi asesmen.
* **Halaman `/account-links`**: Menyediakan dasbor pusat untuk meninjau keterhubungan antar-akun dan distribusi tanggung jawab pendampingan LPK.

### 4.8 Modul 8: Sistem Desain Mode Gelap & Terang Adaptif (Instant Theme Switcher)
* **Tujuan**: Menjamin kenyamanan visual dan keterbacaan tinggi (*ergonomics*) selama sesi pemantauan audit yang panjang.
* **Mekanisme Operasional**:
  * **1-Click Instant Toggle**: Tombol pengubah tema pada navbar atas (ikon Matahari & Bulan) beralih seketika tanpa *reload* halaman.
  * **Persistensi & Deteksi Otomatis**: Mendeteksi preferensi tema sistem operasi (*prefers-color-scheme*) saat pertama kali dibuka, dan menyimpan preferensi pilihan pengguna di `localStorage`.
  * **Anti-Flicker Script**: Pustaka inline pada elemen `<head>` yang menetapkan atribut `data-theme` sebelum halaman dirender untuk mengeliminasi efek kilatan putih (*white flash*).
  * **Adaptasi Semantik Menyeluruh**: Menggunakan variabel token semantik (`var(--surface)`, `var(--ink)`, `var(--muted)`, `var(--line)`) pada seluruh tabel, formulir, modal, kalender, badge, SweetAlert2, hingga *empty state*.

### 4.9 Modul 9: Peringatan Pengawasan Persisten Wajib (Non-Dismissible)
* **Tujuan**: Menjamin tindak lanjut kepatuhan regulasi KAN tanpa ada peringatan kritis yang diabaikan atau disembunyikan.
* **Karakteristik**:
  * Banner peringatan pengawasan KAN tampil persisten di bagian atas konten utama saat terdapat LPK aktif yang memasuki bulan pengawasan (Bulan ke-14 S1, Bulan ke-35 S2, atau 1 Bulan sebelum kedaluwarsa).
  * Tombol silang (*dismiss button*) dan logika penonaktifan sesi ditiadakan secara permanen, sehingga banner hanya akan selesai setelah tindakan penanganan (penjadwalan atau pemenuhan) direalisasikan.

---

## 5. Standar Antarmuka, Interaksi & Aksesibilitas

1. **Persistent Alert Banner Prioritas Tinggi**:
   * Posisi paling atas pada dashboard utama dan halaman rincian LPK.
   * Aktif otomatis jika ada pengawasan berstatus `SURVEILLANCE_DUE`, `SURVEILLANCE_OVERDUE`, atau batas SLA TP mendekati jatuh tempo. Banner wajib dan persisten, tidak dapat ditutup secara sepihak sebelum ditindaklanjuti.
2. **Sistem Pewarnaan Badge Kepatuhan Kontras Tinggi (WCAG AAA/AA)**:
   * `ACTIVE` / `COMPLETED`: Badge Hijau (`#dcfce7`, teks `#15803d`).
   * `SURVEILLANCE_DUE` / `IN_PROGRESS`: Badge Kuning (`#fef9c3`, teks `#854d0e`).
   * `SURVEILLANCE_OVERDUE` / `EXPIRED`: Badge Merah (`#fee2e2`, teks `#b91c1c`).
   * `SUSPENDED` (Dibekukan): Badge Ungu (`#f3e8ff`, teks `#6b21a8`) untuk membedakan secara tegas kondisi pembekuan sementara dari kedaluwarsa.
   * `REVOKED` (Dicabut): Badge Merah Gelap (`#450a0a`, latar `#fecaca`).
   * `INACTIVE`: Badge Abu-abu (`#f3f4f6`, teks `#4b5563`).
3. **Penyelarasan Komponen Kartu & Baris Asesmen**:
   * Elemen baris asesmen menggunakan kontainer mandiri `<div class="assessment-list-row clickable-row">` bebas dari masalah tag tautan bersarang (*no nested anchor tags*).
   * Garis pemisah atas (`border-top`) membentang utuh 100% dari ujung kiri ke kanan panel.
   * Pada sisi kanan, badge status dan tombol pintas kalender (`.assessment-cal-shortcut`) tersusun sejajar secara horizontal (`align-items: center; gap: 8px;`), menghapus penumpukan vertikal tombol di atas badge.
   * Tombol pintas kalender dirancang seragam (tinggi 24px, latar soft sky `#f0f9ff`, border `#bae6fd`, teks aksen `#0284c7`) baik pada kartu milestone pengawasan (S1, S2, RA) maupun pada baris daftar asesmen.
4. **Penyelarasan Avatar & Bentuk Kontrol Navbar**:
   * Wadah profil pengguna pada navbar atas menggunakan sudut membulat persegi (*squircle*) ber-radius 9px yang selaras dengan tombol tema dan tombol notifikasi.
   * Avatar inisial di dalam wadah profil ber-radius 6px, mengeliminasi ketidakserasian bentuk kapsul 999px.
5. **Adaptasi Empty State di Mode Gelap**:
   * Saat filter tabel tidak menemukan data yang cocok, kotak pesan kosong beradaptasi ke latar gelap semantik `var(--surface)` dengan border putus-putus bertekstur halus dan tombol reset filter sekunder.
6. **Interaksi Baris Universal (Universal Clickable Rows)**:
   * Baris tabel pada tabel LPK, tabel Asesmen, dan kartu rincian asesmen dapat diklik langsung untuk membuka halaman detail tujuan.
   * Klik pada elemen interaktif di dalam baris (tombol, tautan kalender, checkbox) secara cerdas tidak memicu navigasi baris induk.
   * Dilengkapi dukungan penuh navigasi keyboard (`tabindex="0"`, Enter / Spasi untuk eksekusi, indikator fokus ring yang tegas).

---

## 6. Kebutuhan Non-Fungsional (Non-Functional Requirements)

1. **Performa & Responsivitas**:
   * Waktu render halaman utama di bawah 150 ms pada lingkungan intranet instansi.
   * Penggantian data tabel parsial (live filter) selesai dalam waktu di bawah 80 ms.
   * Pergantian tampilan kalender via partial AJAX selesai dalam waktu di bawah 100 ms.
   * Implementasi Eager Loading (`with(['lpk', 'creator', 'teamMembers'])`) di seluruh controller untuk meniadakan masalah N+1 Query.
2. **Keamanan & Integritas Data**:
   * Proteksi token CSRF pada seluruh transaksi POST/PUT/DELETE.
   * Middleware otorisasi peran berbasis hak akses pengguna (`admin` dan `pic`).
   * Pencegahan akses lintas data antar PIC pada tingkat kueri basis data.
3. **Kepatuhan Audit & Standar Regulasi**:
   * Logika tahapan pengawasan dan penegakan SLA mengacu pada pedoman Komite Akreditasi Nasional (KAN U-01).
   * Penatausahaan dokumen dan penetapan SK akreditasi mematuhi tata kelola administrasi resmi KAN.
4. **Keandalan Uji Otomatis (Automated Testing Coverage)**:
   * Dilengkapi rangkaian pengujian otomatis (*Feature Tests* & *Unit Tests*) mencakup **183 pengujian** dengan **1102 asersi** yang lulus 100% tanpa kegagalan (`Tests: 183 passed`).
   * Menguji otomasi toleransi surveilen, transisi pembekuan 1 tahun, penolakan perpanjangan TP tanpa progres, kolaborasi tim multi-PIC, live filter parsial, navigasi selector tahun kalender, deep-linking auto-focus kalender, hingga akses kontrol peran.

---

## 7. Arsitektur Penerapan & Infrastruktur Layanan (Deployment Architecture)

1. **Lingkungan Kontainer Docker**:
   * Layanan berjalan pada kontainer Docker mandiri (`composer:2` berbasis Alpine Linux dan PHP 8.5+).
   * Kontainer SIMASADI berjalan mandiri dengan pemetaan port khusus (`0.0.0.0:8001 -> 8000`) dengan nama kontainer `prototype-magang-laravel`.
   * Kebijakan restart kontainer diatur ke `unless-stopped` untuk menjamin ketersediaan layanan tinggi saat server reboot.
2. **Eksposur Domain Publik Aman (Cloudflare Zero Trust Tunnel)**:
   * Akses publik dienkripsi ujung-ke-ujung melalui Cloudflare Tunnel (`cloudflared`).
   * Domain resmi publik: `https://simasadi.drzzy.my.id`.
   * Aturan *Ingress Tunnel* mengarahkan lalu lintas `simasadi.drzzy.my.id` secara langsung ke layanan internal `http://127.0.0.1:8001`, terpisah dari layanan aplikasi internal lain di server host.
