# Product Requirement Document (PRD)
## SIMASADI: Sistem Informasi Manajemen Akreditasi & Surveilen Lembaga Penilaian Kesesuaian
### Komite Akreditasi Nasional (KAN) / Badan Standardisasi Nasional (BSN)

---

## 1. Dokumen Ringkasan Eksekutif (Executive Summary)

### 1.1 Latar Belakang & Identitas Sistem
**SIMASADI** adalah sistem informasi dan administrasi operasional terpadu yang dirancang khusus untuk memfasilitasi, memantau, dan memvalidasi siklus hidup akreditasi Lembaga Penilaian Kesesuaian (LPK) di bawah naungan Komite Akreditasi Nasional (KAN) dan Badan Standardisasi Nasional (BSN). LPK mencakup:
* **LP**: Laboratorium Pengujian (ISO/IEC 17025)
* **LK**: Laboratorium Kalibrasi (ISO/IEC 17025)
* **LSPr**: Lembaga Sertifikasi Produk (ISO/IEC 17065)
* **LI**: Lembaga Inspeksi (ISO/IEC 17020)

Aplikasi ini mengintegrasikan pengawasan berkala (Surveilen KAN), agenda asesmen lapangan, kepatuhan keuangan berbasis Standar Biaya Masukan (SBM PMK), penagihan PNBP (SIMPONI Kemenkeu), penandatanganan elektronik dokumen SK (BSrE BSSN), penelusuran isu/ketidaksesuaian, hingga pemantauan operasional teknis.

### 1.2 Tujuan Dokumen PRD
Dokumen ini berfungsi sebagai spesifikasi kebutuhan produk (PRD) yang merangkum seluruh domain bisnis, arsitektur data, logika kalkulasi, alur kerja antar-peran, dan kebutuhan fungsional dari sistem prototipe yang ada saat ini. Dokumen ini menjadi **fondasi acuan teknis** untuk pembangunan proyek baru menggunakan **MoonShine Laravel Admin Panel (v3)** sebagai antarmuka administrasi korporat yang modern, ringan, dan cepat.

---

## 2. Profil Pengguna & Hak Akses (Role-Based Access Control)

Sistem SIMASADI mengimplementasikan pemisahan hak akses berbasis **2 peran (*roles*) nyata** yang terdaftar di database:

| Peran (*Role*) | Akun Referensi | Deskripsi Tanggung Jawab | Hak Akses Utama | Batasan Keamanan |
| :--- | :--- | :--- | :--- | :--- |
| **Admin Unit Akreditasi Lab** (`admin`) | `admin@simasadi.local` | Penanggung jawab administrasi operasional akreditasi dan pemeliharaan teknis sistem BSN. | Akses penuh (*Full Control*) ke seluruh modul: Registrasi, ubah, dan hapus LPK; impor massal LPK; penjadwalan asesmen; pemrosesan amandemen; penerbitan billing PNBP; verifikasi biaya perjalanan dinas SBM; penandatanganan e-Sign SK; ekspor data; pemantauan server KANMIS; histori backup database; dan pemicu simulasi/notifikasi surveilen. | Tanpa batasan hak akses di sistem. |
| **PIC Laboratorium** (`pic`) | `pic@simasadi.local` | Narahubung / personel teknis laboratorium penguji & kalibrasi LPK. | Akses operasional laboratorium: melihat profil dan daftar LPK (*read-only*), memantau tenggat waktu surveilen, melihat kalender agenda asesmen, pelaporan biaya perjalanan dinas mandiri untuk asesmen terkait, pencatatan isu/kendala operasional, dan penambahan log tindak lanjut (*follow-up*). | Dibatasi secara ketat (*403 Forbidden*): dilarang menghapus LPK, dilarang mengimpor data LPK, dilarang mengakses modul teknis server (Layanan KANMIS & Histori Backup), dan dilarang memverifikasi biaya SBM sendiri (*no self-verification*). |

---

## 3. Spesifikasi Kebutuhan Fungsional (Functional Requirements)

### 3.1 Modul 1: Manajemen Master Lembaga Penilaian Kesesuaian (LPK)
* **Karakteristik Data**:
  * Nomor Akreditasi / Registrasi resmi KAN (misal: `LP-001-IDN`, `LK-015-IDN`, `LSPr-014-IDN`) bersifat unik (*unique*).
  * Nama Lembaga, Ruang Lingkup Akreditasi (*Scope*), Alamat Lengkap, Email PIC, Nomor Telepon/Fax.
  * Tanggal Terbit Sertifikat Akreditasi (`certificate_date`) dan Tanggal Kedaluwarsa Sertifikat (`expired_at`).
  * Aturan Otomasi Kedaluwarsa: Jika `expired_at` dikosongkan pada form pendaftaran, sistem otomatis menghitung `+5 tahun` dari `certificate_date`.
  * Integrasi Dokumen: Menggunakan satu tautan Google Drive terpadu (`drive_url`) untuk menyimpan berkas sertifikat, SK amandemen, dan lampiran ruang lingkup.
* **Smart Dynamic Status Engine (Mesin Status Akreditasi Dinamis)**:
  Status LPK tidak lagi statis di database, melainkan dihitung secara dinamis di memori berdasarkan kepatuhan siklus KAN:
  1. `INACTIVE` (Badge Abu-abu): Jika status master dinonaktifkan oleh administrator.
  2. `EXPIRED` (Badge Merah): Jika masa berlaku sertifikat akreditasi telah habis (`now > expired_at`).
  3. `SURVEILLANCE_OVERDUE` (Badge Merah): LPK berstatus aktif, namun telah melewati target pelaksanaan Surveilen 1 (bulan 15) atau Surveilen 2 (bulan 36) tanpa agenda asesmen.
  4. `SURVEILLANCE_DUE` (Badge Kuning): LPK yang memasuki jendela notifikasi pengawasan aktif (bulan 14 untuk S1, bulan 35 untuk S2, atau 1 bulan sebelum habis untuk Re-Akreditasi).
  5. `ACTIVE` (Badge Hijau): LPK aktif dan seluruh siklus pengawasan terpenuhi / terjadwal aman.
* **Impor Massal Data Master (Smart Upsert)**:
  * Mendukung unggah berkas `.csv` lokal (dengan deteksi otomatis pemisah koma `,` atau titik koma `;`).
  * Mendukung penarikan langsung dari tautan publik Google Sheets via HTTP.
  * Logika *Smart Upsert*: Jika nomor registrasi sudah ada di database, perbarui data profilnya; jika belum ada, buat entri baru.
  * Tersedia fitur unduh template resmi `template-import-lpk.csv`.

---

### 3.2 Modul 2: Siklus Pengawasan KAN (Surveillance Milestone Engine)
* **Aturan Siklus Pengawasan KAN**:
  Sistem menghitung tonggak jadwal pengawasan mengacu pada tanggal sertifikat:
  1. **Surveilen 1 (S1)**:
     * Jendela Notifikasi: Bulan ke-14 setelah tanggal sertifikat.
     * Target Pelaksanaan: Bulan ke-15 setelah tanggal sertifikat.
     * Evaluasi Asesmen: Dianggap selesai/terjadwal jika terdapat agenda asesmen dengan tipe `SURVEILLANCE` atau memuat kata "S1"/"Surveilen" pada rentang bulan 10 s/d 24.
  2. **Surveilen 2 (S2)**:
     * Jendela Notifikasi: Bulan ke-35 setelah tanggal sertifikat.
     * Target Pelaksanaan: Bulan ke-36 setelah tanggal sertifikat.
     * Evaluasi Asesmen: Dianggap selesai/terjadwal jika terdapat asesmen pada rentang bulan 25 s/d 44.
  3. **Re-Akreditasi (RA)**:
     * Jendela Notifikasi: 1 bulan sebelum masa berlaku sertifikat habis.
     * Target Pelaksanaan: Sebelum sertifikat kedaluwarsa (tahun ke-5).
* **Otomasi Notifikasi & Pengingat**:
  * Perintah CLI terjadwal: `php artisan lpk:check-surveillance` (dijalankan harian via cron).
  * Pengiriman email otomatis ke PIC Lab menggunakan mailable `SurveillanceReminderMail`.
  * Fitur simulasi pengiriman email 1-klik di halaman detail LPK (dikirimkan ke Mailtrap Sandbox untuk pengujian).
  * **Banner Peringatan Persisten (*Persistent Alert Banner*)**: Muncul mencolok di posisi teratas dashboard utama dan halaman rincian LPK jika ada jadwal pengawasan yang *DUE* atau *OVERDUE*. Banner ini tidak dapat ditutup (*cannot be dismissed*) sampai agenda asesmen dijadwalkan di sistem.

---

### 3.3 Modul 3: Penjadwalan Asesmen & Kalender Kerja
* **Pengelolaan Asesmen Lapangan**:
  * Tipe Asesmen: `INITIAL` (Asesmen Awal), `SURVEILLANCE` (Surveilen), `REASSESSMENT` (Re-Akreditasi/Asesmen Ulang).
  * Atribut: Judul agenda, LPK terkait, Tim Asesor Kepala (*Lead Assessor*), Lokasi (On-site / Online), Tanggal & Jam Mulai (`start_at`), Tanggal & Jam Selesai (`end_at`), dan Status (`PLANNED`, `SCHEDULED`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`).
  * Validasi: Tanggal selesai tidak boleh lebih lampau daripada tanggal mulai.
* **Kalender Kerja Interaktif**:
  * Integrasi data ganda: Menampilkan agenda asesmen resmi, agenda internal KAN, dan penanda tanggal jatuh tempo surveilen LPK secara otomatis.
  * Fitur lompat cepat: Dropdown pemilih bulan dan tahun langsung (*Direct Month & Year Selector*) untuk navigasi cepat ke tahun lampau atau mendatang.
  * Tambah agenda instan: Klik pada tanggal tertentu di kalender langsung membuka formulir dengan tanggal yang terisi otomatis.

---

### 3.4 Modul 4: Proses Akreditasi & Gate Kesiapan Terbit (Release Readiness Gate)
* **Siklus Hidup Akreditasi**:
  * Tahapan: `NOT_STARTED` -> `IN_PROGRESS` -> `UNDER_REVIEW` -> `COMPLETED` -> `OUTPUT_RELEASED`.
  * Milestones administratif: Tanggal Mulai Pengajuan (`start_date`), Tanggal Rapat Panitia Teknis (`pantek_at`), Tanggal Target SK (`target_output_at`), dan Tanggal Realisasi Rilis SK (`output_released_at`).
* **Quality Gate Kesiapan Terbit Output Akreditasi (*Release Readiness Gate*)**:
  Sistem menerapkan proteksi kepatuhan ketat sebelum SK Akreditasi dapat dirilis (`OUTPUT_RELEASED`):
  1. **Syarat Billing PNBP**: Tagihan jasa akreditasi harus berstatus `PAID` (lunas kas negara).
  2. **Syarat Biaya Asesor (SBM)**: Seluruh laporan biaya perjalanan dinas tim asesor untuk asesmen terkait harus berstatus `TERVERIFIKASI`.
  3. **Syarat TTE**: Dokumen SK telah dibubuhi Tanda Tangan Elektronik resmi.

---

### 3.5 Modul 5: Kepatuhan Keuangan SBM & Billing PNBP SIMPONI
* **Pelaporan Biaya Perjalanan Dinas Asesor (Kepatuhan SBM PMK)**:
  * Komponen Biaya: Transportasi (Tiket/Tol/BBM), Akomodasi/Penginapan Hotel, Uang Harian Asesor, dan Biaya Paket Data/Komunikasi Lapangan.
  * Total biaya dihitung otomatis (`total_cost = transport + accommodation + daily_allowance + package_data`).
  * Alur Verifikasi SBM: `BELUM_DILAPORKAN` -> `MENUNGGU_VERIFIKASI` -> `TERVERIFIKASI` atau `PERLU_REVISI`.
  * Bukti dukung: Unggah berkas kuitansi/tiket (`receipt_path`) beserta catatan verifikator dan penanda waktu verifikasi (`verified_at`, `verified_by`).
* **Realisasi Penerimaan Negara Bukan Pajak (SIMPONI Kemenkeu)**:
  * Penerbitan Kode Billing SIMPONI 15 digit unik.
  * Penetapan nominal tarif jasa akreditasi sesuai regulasi PP PNBP BSN.
  * Status pembayaran: `UNPAID` (Belum Bayar), `PAID` (Lunas), `EXPIRED` (Kedaluwarsa).
  * Simulasi Pelunasan Kas Negara: Pencatatan Nomor Transaksi Penerimaan Negara (NTPN 16 digit), Nomor Transaksi Bank (NTB), dan kanal pembayaran (Bank BUMN / Pos / E-Banking).

---

### 3.6 Modul 6: Tanda Tangan Elektronik Dokumen SK (e-Sign BSrE)
* **Simulasi Sertifikasi BSrE (Balai Sertifikasi Elektronik - BSSN)**:
  * Data Penandatangan: Nama Ketua KAN, Jabatan Resmi, NIP Penandatangan.
  * Integritas Dokumen: Pembentukan hash kriptografi SHA-256 unik (`verify_hash`) berbasis nomor SK, LPK, dan stempel waktu penandatanganan.
  * Nomor Seri Sertifikat Digital BSrE dan stempel waktu (*signed_at*).
* **Verifikasi Publik 1-Klik**:
  * Rute publik `/verify-sk/{hash}` yang dapat diakses oleh publik atau pemindai QR Code untuk memeriksa keaslian SK Akreditasi secara real-time tanpa perlu login.

---

### 3.7 Modul 7: Pengajuan Amandemen Ruang Lingkup
* Administrasi penambahan atau pengurangan ruang lingkup akreditasi laboratorium.
* Atribut: Nomor Surat Pengajuan (`submission_number`), Rincian Perubahan Lingkup (`scope_detail`), Tanggal Pengajuan, Target Penyelesaian, dan Catatan.
* Alur Status: `SUBMITTED` -> `UNDER_REVIEW` -> `APPROVED` atau `REJECTED`.

---

### 3.8 Modul 8: Pelaporan Masalah & Log Tindak Lanjut (Issue Tracker)
* Pencatatan kendala operasional, temuan ketidaksesuaian asesmen, atau komplain laboratorium.
* Atribut: Judul Isu, Deskripsi Lengkap, LPK terkait, Tingkat Urgensi/Prioritas (`LOW`, `MEDIUM`, `HIGH`), Status (`OPEN`, `IN_PROGRESS`, `RESOLVED`), dan Tenggat Waktu (`due_date`).
* Log Kronologis Berantai: Setiap isu dapat memiliki riwayat catatan tindak lanjut (*Issue Follow-ups*) yang mencatat nama staf, catatan perkembangan, dan perubahan status isu.

---

### 3.9 Modul 9: Integrasi Ekspor CSV & Live Feed Google Sheets
* **Live CSV Feeds (`=IMPORTDATA`)**:
  * Endpoint publik khusus yang dilindungi parameter kunci rahasia (`?key=simasadi-live`):
    * `/feeds/expenses.csv`: Real-time data laporan biaya asesor.
    * `/feeds/lpks.csv`: Real-time data master LPK dan status akreditasi.
    * `/feeds/assessments.csv`: Real-time jadwal asesmen lapangan.
  * Spreadsheet Google Sheets terhubung secara otomatis tanpa perlu ekspor-impor manual berulang kali.
* **Ekspor CSV Terotentikasi**:
  * Fitur unduh berkas CSV dengan format UTF-8 BOM untuk memastikan karakter teks terbuka rapi di Microsoft Excel.

---

### 3.10 Modul 10: Pemantauan Kinerja Sistem (Monitoring KANMIS & Backup)
* **Pencatatan Kesehatan Layanan KANMIS**:
  * Pencatatan status ketersediaan endpoint/sistem internal KANMIS (`status_code`, `response_time_ms`, `is_success`).
* **Log Pencadangan Data (Database Backups)**:
  * Histori file backup database (`filename`, `size`, `status`: SUCCESS/FAILED, nama staf pencatat, waktu selesai).

---

## 4. Struktur Basis Data & Hubungan Antar-Entitas (Data Architecture & ERD)

Berikut adalah ringkasan skema tabel database (13 tabel relasional):

```
+-----------------------------------------------------------------------------------+
|                                    USERS                                          |
| id, name, email, password, role (admin/pic), timestamps                           |
+-----------------------------------------------------------------------------------+
                                         │
                 ┌───────────────────────┼───────────────────────────┐
                 ▼                       ▼                           ▼
        +------------------+   +-------------------+       +--------------------+
        |   ASSESSMENTS    |   |  ISSUE_FOLLOWUPS  |       |      BACKUPS       |
        | created_by (FK)  |   |   user_id (FK)    |       |  recorded_by (FK)  |
        +------------------+   +-------------------+       +--------------------+
                 │                       ▲                           
                 │ (1 to 1)              │ (1 to N)                  
                 ▼                       │                           
        +-------------------+            │                           
        |ASSESSMENT_EXPENSES|            │                           
        |reported_by (FK)   |            │                           
        |verified_by (FK)   |            │                           
        +-------------------+            │                           
                                         │                           
+-----------------------------------------------------------------------------------+
|                                     LPKS                                          |
| id, registration_number (UNIQUE), name, scope, address, email, phone, status,     |
| certificate_date, expired_at, drive_url, last_surveillance_notified_at, notes     |
+-----------------------------------------------------------------------------------+
       │                    │                     │                   │
       ▼ (1 to N)           ▼ (1 to N)            ▼ (1 to N)          ▼ (1 to N)
+--------------+    +----------------+    +----------------+   +--------------------+
|  AMENDMENTS  |    |     ISSUES     |    |  ASSESSMENTS   |   |   ACCREDITATIONS   |
| lpk_id (FK)  |    |  lpk_id (FK)   |    |  lpk_id (FK)   |   |    lpk_id (FK)     |
+--------------+    +----------------+    +----------------+   +--------------------+
                                                                     │
                                        ┌────────────────────────────┴────────────────────────┐
                                        ▼ (1 to N)                                            ▼ (1 to 1)
                             +------------------------+                           +------------------------+
                             | ACCREDITATION_BILLINGS |                           |ACCREDITATION_SIGNATURES|
                             | accreditation_id (FK)  |                           | accreditation_id (FK)  |
                             | billing_code, status   |                           | verify_hash, is_signed |
                             +------------------------+                           +------------------------+
```

---

## 5. Rencana Arsitektur & Implementasi Berbasis MoonShine v3

Di proyek baru nanti, antarmuka akan dibangun menggunakan **MoonShine Laravel Admin Panel (v3)**. Berikut pemetaan sumber daya (*Resources*) dan komponen MoonShine yang akan dibuat:

### 5.1 Daftar MoonShine Resources
1. **`LpkResource`**:
   * Menampilkan tabel master LPK dengan dekorator badge warna untuk `dynamic_status`.
   * Kolom tabel: No. Registrasi (link ke detail), Nama LPK, Status Dinamis, Masa Berlaku, Link Berkas Drive.
   * Filter cepat: Status LPK, Status Surveilen (Due/Overdue), Masa Berlaku (Mendekati kedaluwarsa).
   * Tombol Aksi Kustom: Tombol "Simulasi Email Pengawasan" dan "Ekspor CSV".
2. **`AssessmentResource`**:
   * Form pembuatan agenda asesmen dengan relasi select LPK dan pemilih tanggal range.
   * Tab detail menampilkan rincian laporan biaya SBM (`AssessmentExpense`).
3. **`AssessmentExpenseResource`**:
   * Form rincian biaya: Transportasi, Akomodasi, Uang Harian, Paket Data.
   * Aksi Verifikator: Tombol modal verifikasi khusus Admin Unit Akreditasi Lab untuk menyetujui atau meminta revisi.
4. **`AccreditationResource`**:
   * Alur proses tahapan akreditasi.
   * Sub-komponen: Riwayat billing PNBP dan form tanda tangan digital BSrE.
   * Tombol Kunci: Validasi *Release Readiness Gate* sebelum tombol "Rilis SK Akreditasi" aktif.
5. **`AmendmentResource`**:
   * Pengelolaan permohonan amandemen ruang lingkup LPK dengan status badge workflow.
6. **`IssueResource`**:
   * Pelacakan isu ketidaksesuaian dengan indikator prioritas (High, Medium, Low).
   * Sub-tabel *IssueFollowup* di halaman detail isu.
7. **`CalendarEventResource` & Custom MoonShine Page**:
   * Halaman kustom kalender kerja interaktif dengan pemilih langsung bulan dan tahun.
8. **`MonitoringResource` & `BackupResource`**:
   * Khusus peran Admin Unit Akreditasi Lab untuk mencatat status KANMIS dan cadangan basis data.

### 5.2 Dashboard Utama MoonShine (Metrics & Analytics)

Dashboard awal MoonShine dirancang dengan penekanan kuat pada **peringatan dini (*early warning system*)** agar Admin Unit Akreditasi Lab tidak melewatkan jadwal pengawasan LPK:

1. **Persistent Alert Banner Paling Atas (Komponen Peringatan Prioritas Tinggi)**:
   * **Posisi**: Terletak di urutan paling atas halaman dashboard sebelum baris metrik angka.
   * **Kondisi Tampil**: Muncul otomatis jika terdapat satu atau lebih LPK aktif yang memiliki jadwal surveilen/re-akreditasi berstatus `DUE` atau `OVERDUE`.
   * **Visual & Tampilan**:
     * Kotak alert berbingkai merah/rose kontras (`border: #f43f5e`, `background: #fff1f2`, `color: #9f1239`).
     * Ikon peringatan (*warning icon*) mencolok.
     * Judul tegas: `Peringatan Siklus Pengawasan Akreditasi: [X] Kunjungan Memerlukan Tindak Lanjut!`.
     * Teks panduan: Penjelasan bahwa jadwal telah memasuki Bulan ke-14 (S1), Bulan ke-35 (S2), atau 1 Bulan sebelum kedaluwarsa (Re-Akreditasi).
     * Tombol Aksi Langsung: `[ Tinjau LPK Jatuh Tempo → ]` yang mengarahkan langsung ke `LpkResource` dengan filter aktif `?surveillance=NEEDS_ACTION`.
   * **Sifat Persisten**: Notifikasi ini tidak dapat ditutup secara manual (*cannot be dismissed*) sampai agenda asesmen dijadwalkan di sistem.

2. **Kartu Metrik Angka Utama (*ValueMetric Cards*)**:
   * **Total LPK Terdaftar**: Jumlah seluruh laboratorium/lembaga terakreditasi.
   * **LPK Perlu Tindak Lanjut Surveilen (*High Priority Warning*)**: Angka LPK yang jatuh tempo/lewat jadwal dengan aksen warna merah/bahaya. Mengeklik kartu ini langsung membuka tabel LPK yang perlu tindakan.
   * **Tagihan PNBP Belum Bayar**: Jumlah tagihan SIMPONI yang masih `UNPAID`.
   * **Biaya Asesor Menunggu Verifikasi SBM**: Jumlah laporan biaya perjalanan dinas yang menunggu persetujuan verifikator.

3. **Widget Tabel Cepat: LPK Jatuh Tempo & Lewat Jadwal (*Actionable Table Widget*)**:
   * Tabel langsung di dashboard awal yang menampilkan 5-10 LPK yang paling mendesak untuk ditindaklanjuti.
   * Kolom: No. Registrasi, Nama LPK, Tonggak Siklus (S1 / S2 / RA), Status Badge (`Lewat Jadwal` warna merah atau `Jatuh Tempo` warna kuning), dan Tombol Pintas `+ Jadwalkan Asesmen`.

4. **Grafik Analitik (*LineChartMetric & DonutMetric*)**:
   * Distribusi proporsional status LPK: *Aktif*, *Lewat Jadwal Surveilen*, *Jatuh Tempo Surveilen*, *Kedaluwarsa*, dan *Tidak Aktif*.
   * Tren penyelesaian asesmen per bulan/kuartal.

5. **Indikator Lonceng Notifikasi di Header Navigasi Atas (*Topbar Notification Bell*)**:
   * MoonShine layout dilengkapi ikon lonceng di navbar atas dengan badge counter angka merah jika ada alert aktif.
   * Dropdown lonceng menampilkan ringkasan singkat LPK yang jatuh tempo dan tautan cepat ke modul terkait, dapat dipantau dari halaman mana pun di dalam aplikasi.

---

## 6. Kebutuhan Non-Fungsional (Non-Functional Requirements)

1. **Performa & Kecepatan (High Performance)**:
   * Target waktu muat halaman awal (*Time to First Byte / TTFB*) di bawah 150 milidetik di lingkungan intranet instansi BSN.
   * Memanfaatkan Server-Side Rendering (SSR) bawaan MoonShine + Alpine.js untuk meminimalkan beban bundle JavaScript klien.
   * Seluruh kueri tabel yang memuat data relasional wajib menggunakan *Eager Loading* (`with(['assessments', 'accreditations'])`) untuk mencegah masalah N+1 Query.
   * Penerapan indeks basis data (*database index*) pada kolom: `registration_number`, `status`, `expired_at`, dan `start_at`.
2. **Keamanan Sistem (Enterprise Security)**:
   * Proteksi CSRF pada seluruh permintaan mutasi data (*POST/PUT/DELETE*).
   * Otentikasi berbasis sesi aman (*Session-based authentication*) dengan proteksi *throttling* percobaan login gagal.
   * Validasi ketat peran (*Role Middleware*) pada level rute dan level tombol aksi antarmuka.
   * Pencegahan kerentanan IDOR (*Insecure Direct Object References*) pada akses dokumen dan data LPK.
3. **Kepatuhan Audit & Standar Regulasi (Regulatory Compliance)**:
   * Kepatuhan formula SBM mengacu pada Peraturan Menteri Keuangan (PMK) Standar Biaya Masukan.
   * Kepatuhan siklus pengawasan mengacu pada pedoman Komite Akreditasi Nasional (KAN).
   * Penelusuran audit jejak digital (*Audit Trail*): Pencatatan `verified_by`, `verified_at`, `signed_at`, dan nilai hash SHA-256 dokumen.
4. **Ketahanan & Pencadangan Data (Reliability)**:
   * Mekanisme pencadangan basis data rutin dan pencatatan riwayat berkas cadangan secara transparan.
   * Kemudahan migrasi data menggunakan skema seeders dan modul impor CSV massal.

---

## 7. Rencana Tahapan Migrasi ke Proyek MoonShine Baru

1. **Inisialisasi Proyek Baru**:
   * Instalasi Laravel versi terbaru pada direktori baru.
   * Instalasi paket `moonshine/moonshine`.
2. **Porting Struktur Data & Model**:
   * Menyalin file migrasi basis data (13 tabel relasional).
   * Menyalin Model Eloquent beserta accessor bisnis (`getDynamicStatusAttribute`, `getSurveillanceMilestonesAttribute`, `getActiveSurveillanceAlerts`).
3. **Penyusunan MoonShine Resources**:
   * Menghasilkan resources untuk LPK, Akreditasi, Asesmen, Biaya, Billing, dan Isu.
   * Mengonfigurasi dekorator badge status dinamis dan filter kustom.
4. **Implementasi Aksi Khusus & Quality Gate**:
   * Menghubungkan logika verifikasi SBM dan pengecekan kesiapan rilis SK (*Release Readiness Gate*).
   * Menyiapkan endpoint verifikasi publik e-Sign `/verify-sk/{hash}` dan live feeds CSV Google Sheets.
5. **Verifikasi & Pengujian Regresi**:
   * Memastikan seluruh alur operasional berjalan cepat, responsif, dan sesuai dengan dokumen PRD ini.
