# SIMASADI
### Sistem Informasi Manajemen Akreditasi & Surveilen Lembaga Penilaian Kesesuaian
**Badan Standardisasi Nasional (BSN) / Komite Akreditasi Nasional (KAN)**

SIMASADI adalah platform operasional terpadu berbasis web yang dirancang untuk mengelola, memantau, dan memvalidasi seluruh siklus hidup akreditasi Lembaga Penilaian Kesesuaian (LPK) di Indonesia. Sistem ini mencakup 8 tipe proses asesmen resmi KAN, penegakan Service Level Agreement (SLA) Tindakan Perbaikan (TP & VTP), aturan toleransi pengisian dan pembekuan bertahap, alur Evaluasi Hasil Asesmen (EHA), serta integrasi live feed Google Sheets.

---

## 📚 Dokumentasi Perancangan Sistem (SDLC)

Seluruh rancangan sistem telah disusun secara komprehensif mengikuti standar siklus hidup pengembangan perangkat lunak (SDLC) dan dapat diakses pada folder [`docs/`](docs/):

* 📑 **[Dokumen Master: PERANCANGAN_SISTEM.md](docs/PERANCANGAN_SISTEM.md)**: Rangkuman utuh seluruh perancangan sistem SIMASADI.
* 🌐 **[Bagian 1: Gambaran Umum Website](docs/01-gambaran-umum.md)**: Latar belakang KAN/BSN, tujuan, ruang lingkup (*in-scope* & *out-of-scope*), profil 4 aktor sistem, dan arsitektur umum.
* 📅 **[Bagian 2: Tahap Perencanaan (Planning)](docs/02-tahap-perencanaan.md)**: Metodologi Iterative Agile Prototyping, linimasa pengembangan fase 1 sampai 6, studi kelayakan teknis/operasional, serta analisis risiko dan mitigasi.
* 🔍 **[Bagian 3: Tahap Analisis (Analysis)](docs/03-tahap-analisis.md)**: Analisis masalah dengan PIECES Framework & Fishbone Diagram, spesifikasi Kebutuhan Fungsional, dan Kebutuhan Non-Fungsional.
* 📐 **[Bagian 4: Tahap Perancangan (Design)](docs/04-tahap-perancangan.md)**: Struktur Navigasi (Sitemap), Use Case Diagram, Activity Diagrams, Sequence Diagrams, Entity Relationship Diagram (ERD), Class Diagram, dan Wireframes antarmuka desktop & mobile.
* 🧪 **[Bagian 5: Implementasi dan Pengujian](docs/05-implementasi-dan-pengujian.md)**: Spesifikasi teknologi, struktur direktori, detail implementasi komponen UI, matriks kasus uji otomatis PHPUnit, uji responsivitas antarmuka manual, dan bukti hasil eksekusi uji.

---

## ✨ Fitur Utama Sistem

1. **8 Tipe Proses Asesmen Resmi KAN (KAN U-01):**
   * Akreditasi Awal (AA / `INITIAL`)
   * Surveilen 1 (S1 / `SURVEILLANCE`)
   * Surveilen 1 + Perluasan Ruang Lingkup (S1 + PRL / `SURVEILLANCE_PRL`)
   * Surveilen 2 (S2 / `SURVEILLANCE_2`)
   * Surveilen 2 + Perluasan Ruang Lingkup (S2 + PRL / `SURVEILLANCE_2_PRL`)
   * Surveilen Tidak Terjadwal (STT / `UNSCHEDULED_SURVEILLANCE`)
   * Perluasan Ruang Lingkup (PRL / `SCOPE_EXTENSION`)
   * Re-Akreditasi / Akreditasi Ulang (RA / `REASSESSMENT`)

2. **Toleransi Pengisian & Siklus Hidup 3 Tahap Surveilen:**
   * **Tahap 1 (Toleransi Berjalan)**: Batas waktu pengisian dokumen surveilen berlaku maksimal 4 bulan dari bulan ke-15 siklus akreditasi atau tanggal kunjungan, tersimpan pada kolom `submission_due_date` yang fleksibel dapat diedit sesuai kondisi proses di lapangan.
   * **Tahap 2 (Dibekukan / Jendela Penyelesaian 1 Tahun)**: Jika melewati batas toleransi pengisian tanpa penyelesaian, status asesmen dan LPK otomatis berubah menjadi **DIBEKUKAN** (`SUSPENDED`, badge warna ungu kontras `#f3e8ff` dengan teks `#6b21a8`). LPK diberikan hak dan kesempatan menyelesaikan asesmennya selama 1 tahun penuh dengan indikator hitung mundur (*countdown*).
   * **Tahap 3 (Dicabut)**: Jika setelah 1 tahun masa pembekuan terlampaui asesmen belum selesai, status akreditasi LPK otomatis beralih menjadi **DICABUT** (`REVOKED`, badge merah).
   * **Aturan Auto-Realisasi LPK Aktif**: Seluruh data surveilen dan tindakan perbaikan masa lalu (`end_at < now()->startOfYear()`) dari LPK yang saat ini berstatus AKTIF otomatis dianggap terealisasi (`COMPLETED` dan `SATISFIED`).
   * **Deadline Proses Akreditasi Sesuai Siklus (Baseline Masa Akreditasi)**:
     - **Proses Periodik 5 Tahunan (Survailen 1, Survailen 2, Re-Akreditasi)**: Menggunakan baseline tanggal terbit sertifikat (`certificate_date`) dan masa berlaku (`expired_at`).
       - S1 (Survailen 1): Notifikasi aktif bulan ke-13/14, target pelaksanaan asesmen bulan ke-15 sampai 18, toleransi maksimal bulan ke-24.
       - S2 (Survailen 2): Notifikasi aktif bulan ke-34/35, target pelaksanaan asesmen bulan ke-36 sampai 39, toleransi maksimal bulan ke-48.
       - RA (Re-Akreditasi): Notifikasi aktif bulan ke-48 sampai 51 (pengajuan dokumen re-akreditasi), pelaksanaan asesmen tuntas sebelum bulan ke-60 (jatuh tempo sertifikat).
     - **Proses Non-Periodik / Ad-Hoc (Akreditasi Awal, PRL, STT)**: Berjalan berdasarkan permohonan baru atau kebutuhan insidental (bukan siklus tahunan sertifikat berjalan). Namun setelah asesmen lapangan dilaksanakan, tenggat waktu penyelesaian tindakan perbaikannya (TP & VTP) ditegakkan menggunakan baseline tanggal realisasi asesmen.

3. **Mesin Penegakan Batas Waktu Tindakan Perbaikan (TP & VTP):**
   * **Baseline Perhitungan**: Menggunakan tanggal selesai realisasi pelaksanaan asesmen (`end_at`).
   * **Matriks Batas Waktu & Notifikasi Pengingat**:
     - **Akreditasi Awal (AA)**: Batas waktu dasar 3 bulan kalender sejak `end_at`. Pengingat ke-1 (Notifikasi 1) dijadwalkan pada 2 bulan setelah realisasi asesmen, dan Notifikasi Final jatuh tepat pada batas waktu 3 bulan.
     - **Proses Lainnya (S1, S2, PRL, RA, STT)**: Batas waktu dasar 2 bulan kalender sejak `end_at`. Pengingat ke-1 (Notifikasi 1) dijadwalkan pada 1 bulan setelah realisasi asesmen, dan Notifikasi Final jatuh tepat pada batas waktu 2 bulan.
   * **Perpanjangan Waktu Resmi (+1 Bulan)**:
     - Maksimal perpanjangan adalah 1 bulan kalender (`effective_tp_due_date = tp_due_date + 1 bulan`).
     - Syarat ketat perpanjangan: LPK telah melakukan perbaikan sebagian temuan ketidaksesuaian asesmen (ada progres riil) dan menyertakan Nomor Surat Permohonan Resmi (`tp_extension_letter_no`). Pengisian nomor surat resmi secara otomatis menambahkan perpanjangan +1 bulan dan memunculkan badge penanda `(+1 Bulan Surat Resmi)`. Jika perbaikan nihil (kosong), permohonan ditolak dan proses dapat dihentikan oleh PIC.
   * **Implementasi Khusus PIC (Role Scoping)**:
     - Dasbor operasional, Kalender Kerja, dan tabel asesmen menyaring data secara otomatis berdasarkan LPK binaan yang ditugaskan kepada PIC bersangkutan (mencakup peran Lead PIC dan Viewer PIC via pembatasan `accessibleBy`). PIC hanya menerima peringatan dan agenda untuk LPK di bawah tanggung jawabnya.
   * **Status Otomatis**: Tanpa input manual status TP. Status otomatis "Sedang Berlangsung" sebelum jatuh tempo, "Dibekukan" bila melewati batas waktu tanpa pemenuhan, dan "Selesai/Memenuhi" saat tanggal pemenuhan (`tp_satisfied_at`) dicatat.
   * **Kanal & Ketersediaan Notifikasi**:
     - **In-App UI (Dasbor & Kalender)**: Sudah aktif penuh. Menampilkan banner pengawasan persisten, kartu peringatan TP mendesak, badge hitung mundur jatuh tempo, serta event kalender pengingat TP dan overdue TP.
     - **Notifikasi Email**: Pengiriman email otomatis saat ini telah beroperasi untuk pengingat siklus pengawasan surveilen LPK, sedangkan otomasi pengiriman email untuk pengingat tindakan perbaikan (TP) asesmen disiapkan pada rencana rilis berikutnya.

4. **Alur Evaluasi Hasil Asesmen (EHA) & Penerbitan SK:**
   * Pencatatan tanggal rencana sidang EHA, tanggal realisasi EHA, dan rekomendasi tim panitia teknis.
   * Penerbitan Nomor SK KAN, Tanggal Terbit SK, dan kalkulasi otomatis Lead Time Terbit SK (`sk_lead_time_days`).

5. **Kalender Kerja Interaktif 5 Tipe Event:**
   * Menyajikan 5 jenis kegiatan: Asesmen Lapangan (Biru), Pengingat Surveilen (Kuning/Oranye), Pengingat Batas TP (Indigo), Overdue TP (Merah/Ungu), dan Pengingat Penerbitan SK (Hijau/Emerald).
   * Fitur pemilih cepat bulan dan tahun dinamis serta pembuatan agenda instan per tanggal klik.

6. **Impor Massal & Live Feed Google Sheets:**
   * Impor massal data LPK dan Asesmen dari file CSV, XLSX, atau URL Google Sheets live (*Smart Upsert*).
   * Live CSV Feeds terproteksi API key (`/feeds/lpks.csv`, `/feeds/assessments.csv`) untuk formula `=IMPORTDATA` Google Sheets secara real-time.

7. **Sistem Kolaborasi Tim & Tautan Akun Multi-PIC (Viewer Role):**
   * Fasilitas penautan akun antar-PIC di mana Lead PIC dapat menambahkan PIC lain sebagai *Viewer* (hanya-lihat) untuk memantau LPK binaannya.
   * Hak akses *Viewer* dibatasi secara ketat (*read-only*): tidak dapat mengubah/menghapus LPK, mengelola anggota tim, atau menjadwalkan asesmen.
   * Halaman khusus `/account-links` untuk meninjau relasi tautan akun dan beban kerja tim secara terpusat.

8. **Sistem Desain Mode Gelap & Terang Adaptif (1-Click Instant Theme):**
   * Beralih tema instan antara Mode Terang dan Mode Gelap melalui tombol navbar tanpa *reload* halaman.
   * Deteksi otomatis preferensi sistem operasi (`prefers-color-scheme: dark`) dan persistensi di `localStorage`.
   * Adaptasi penuh pada seluruh komponen antarmuka, tabel, modal dialog, formulir, badge status, SweetAlert2, dan *empty state*.

9. **Peringatan Pengawasan Persisten Wajib (Non-Dismissible):**
   * Banner peringatan siklus pengawasan KAN (Bulan ke-14 S1, Bulan ke-35 S2, dan 1 Bulan sebelum kedaluwarsa) tampil persisten dan tidak dapat disembunyikan sementara, menjamin kepatuhan tindak lanjut regulasi.

10. **Navigasi Mobile Ergonomis & Quick Action Sheet:**
    * Menggantikan drawer samping dengan Bottom Navigation Bar (`.bottom-nav`) dan Quick Action Modal Sheet untuk kemudahan navigasi satu tangan pada smartphone (`<= 640px`).
    * Filter data mobile bertransformasi menjadi *bottom sheet drawer* yang meluncur dari bawah dengan drag handle yang taktil.

11. **Sistem Desain Tombol Taktil & Visual Canvas Login:**
    * Seluruh tombol kembali (*back button*) distandarisasi ke *tactile navigation button* ber-radius 10px (`var(--radius-md)` / rounded-lg), selaras dengan sistem tombol utama dan menghilangkan gaya pill yang tidak konsisten.
    * Halaman Login (`/login`) mengadopsi gradien biru primer elegan (`#1972f5` ke `#0456ce`) pada canvas Mode Terang yang menyatu dengan tombol aksi utama, serta latar modern zinc/charcoal (`#111215`) pada Mode Gelap.

12. **Optimasi Performa Kueri & Validasi Impor Tangguh:**
    * Pembatasan rentang kueri historis TP pada kalender (`whereBetween` dan `subMonths(5)`) untuk mencegah lonjakan konsumsi memori pada ratusan riwayat asesmen.
    * Proteksi *inverted dates* pada modul impor asesmen yang otomatis menyesuaikan tanggal selesai jika mendahului tanggal mulai (`end_at < start_at` disesuaikan otomatis ke `start_at + 2 hari`).
    * Cron background `lpk:check-surveillance` dioptimalkan dengan pemrosesan bongkahan data (`chunk(100)`) dan *eager loading* relasi.

---

## 👥 Profil Pengguna & Hak Akses (Role-Based Access Control)

Sistem mengimplementasikan pemisahan hak akses berbasis 2 peran operasional nyata dengan scope terpadu (`scopeManageableBy`):

| Peran (*Role*) | Akun Login | Deskripsi Hak Akses Utama | Batasan Keamanan |
|---|---|---|---|
| **Ketua Tim (Admin Unit)** (`admin`) | `admin@simasadi.local` | Akses penuh (*Full Control*): Registrasi, ubah, hapus LPK; impor massal LPK & Asesmen untuk seluruh laboratorium; penjadwalan 8 tipe asesmen KAN; pemrosesan EHA; penugasan PIC; rilis SK; pembatasan tunggal 1 akun Ketua Tim; manajemen akun pengguna; ekspor live feeds. | Sistem membatasi hanya 1 akun Ketua Tim yang dapat aktif dalam basis data. |
| **PIC Laboratorium** (`pic`) | `pic@simasadi.local` | Pengelolaan operasional LPK binaan: monitoring direktori LPK binaan (dan LPK tertaut sebagai viewer), penjadwalan agenda kalender & asesmen untuk LPK kelolaan (`manageableBy`), impor massal asesmen khusus LPK kelolaan, pemantauan batas toleransi surveilen, pelacakan progres TP, dan input nomor surat permohonan perpanjangan waktu. | Dibatasi secara ketat (*403 Forbidden*): dilarang mengimpor data master LPK baru lintas unit, dilarang mengakses/memodifikasi LPK di luar tanggung jawabnya tanpa tautan viewer/kelola, dan dilarang mengelola pengguna sistem. |

*(Kata sandi bawaan untuk seluruh akun pengujian:* `password`*)*

---

## 🛠️ Tumpukan Teknologi

* **Backend:** PHP 8.2+ / PHP 8.3+, Laravel 12 / 13 Framework
* **Basis Data:** SQLite 3 (Database file lokal efisien)
* **Frontend:** Blade Templating, Vanilla CSS (Modern CSS Variables & Design Tokens), Vanilla JavaScript modular
* **Navigasi Mobile:** Ergonomic Bottom Navigation Bar (`.bottom-nav`) & Quick Actions Modal Sheet
* **Asset Bundler:** Vite 6 / 8
* **Tipografi:** Instrument Sans (WOFF2/WOFF)
* **Library Tambahan:** SweetAlert2 (Notifikasi & modal interaktif terpusat)
* **Testing Suite:** 
  * Backend: PHPUnit 11 (183 Feature & Unit Tests)
  * Frontend: Node.js Test Runner (8 Client Router Cache & Datatable Parser Tests)

---

## 🚀 Panduan Instalasi & Menjalankan

### Menggunakan Lingkungan Lokal:

1. **Clone repository:**
   ```bash
   git clone https://github.com/greedykid/prototype-magang.git
   cd prototype-magang
   ```

2. **Install dependency PHP & Node.js:**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment & Basis Data:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   touch database/database.sqlite
   php artisan migrate --seed
   ```

4. **Kompilasi Asset Frontend:**
   ```bash
   npm run build
   ```

5. **Jalankan Server Aplikasi:**
   ```bash
   php artisan serve
   ```
   Buka peramban di `http://127.0.0.1:8000`.

### Menggunakan Docker:

Proyek telah dilengkapi dengan konfigurasi Docker Compose:
```bash
docker compose up -d
docker exec prototype-magang-laravel php artisan migrate --seed
docker exec prototype-magang-laravel npm run build
```

---

## 🧪 Pengujian Otomatis

Seluruh alur fungsional sistem, logika SLA KAN, kalkulasi status otomatis, pembatasan hak akses peran, hingga penanganan caching dan penguraian tabel klien telah diverifikasi dengan dua rangkaian pengujian otomatis:

### 1. Backend Feature & Unit Tests (PHPUnit)
```bash
docker exec prototype-magang-laravel php artisan test
```
**Hasil Pengujian Backend Terkini:**
```text
Tests:    183 passed (1102 assertions)
Duration: ~88s
Status:   100% Passed
```

### 2. Frontend Client Logic Tests (Node.js Test Runner)
```bash
node --test tests/frontend/*.test.mjs
```
**Hasil Pengujian Frontend Terkini:**
```text
✔ parseCellValue parses currency strings correctly
✔ parseCellValue handles blank or placeholder values
✔ parseCellValue parses dates correctly
✔ parseCellValue falls back to lowercased text
✔ router cache TTL constants are optimized
✔ getCleanPageUrl removes hash fragments
✔ getCalendarCacheKey standardizes and sorts query parameters
✔ isCacheableUrl validates safe URLs and excludes unsafe routes
ℹ tests 8 passed, 0 failed (100% Passed)
```

Untuk memformat kode PHP sesuai standar PSR-12:
```bash
docker exec prototype-magang-laravel vendor/bin/pint --format agent
```

---

## 📂 Struktur Penting Proyek

* `app/Http/Controllers`: Kontroler logika sistem (LpkController, AssessmentController, CalendarEventController, LpkImportController, AssessmentImportController, UserAccountLinkController, GoogleSheetsReportController, dll.).
* `app/Models`: Model Eloquent relasional (Lpk, Assessment, Accreditation, CalendarEvent, User, LpkMember, UserAccountLink).
* `database/migrations`: Skema migrasi tabel basis data relasional (users, lpks, assessments, accreditations, calendar_events, lpk_members, user_account_links).
* `database/seeders`: Pembangkitan data awal master LPK, akun 2 peran resmi (Ketua Tim & PIC), asesmen historis terealisasi, dan agenda kalender.
* `docs/`: Dokumentasi perancangan sistem dan arsitektur perangkat lunak lengkap (SDLC bagian 1 sampai 5).
* `resources/css`: Sistem desain antarmuka, variabel token warna tema terang & gelap, tata letak grid, dan responsivitas mobile.
* `resources/js`: Logika interaktif antarmuka, modul theme switcher (`modules/theme.js`), sinkronisasi toggle mobile, filter drawer, dan modul kalender.
* `resources/views`: Template antarmuka Blade Laravel.
* `tests/Feature`: Test suite otomatis komprehensif (183 pengujian) untuk seluruh fitur operasional, hak akses, dan kepatuhan regulasi KAN.

---

## 📌 Status Proyek

Proyek ini adalah **prototype operasional magang** yang terverifikasi penuh untuk validasi alur dan antarmuka operasional akreditasi KAN-BSN.

## 📄 Lisensi

Proyek ini dilisensikan di bawah lisensi [MIT](LICENSE).
