# SIMASADI
### Sistem Informasi Manajemen Akreditasi & Surveilen Lembaga Penilaian Kesesuaian
**Badan Standardisasi Nasional (BSN) / Komite Akreditasi Nasional (KAN)**

SIMASADI adalah platform operasional terpadu berbasis web yang dirancang untuk mengelola, memantau, dan memvalidasi seluruh siklus hidup akreditasi Lembaga Penilaian Kesesuaian (LPK) di Indonesia. Sistem ini mencakup 8 tipe proses asesmen resmi KAN, penegakan Service Level Agreement (SLA) Tindakan Perbaikan (TP & VTP), aturan toleransi pengisian dan pembekuan bertahap, alur Evaluasi Hasil Asesmen (EHA), kepatuhan keuangan berbasis Standar Biaya Masukan (SBM PMK), penagihan PNBP (SIMPONI Kemenkeu), penandatanganan elektronik dokumen SK (BSrE BSSN), portal publik dan verifikasi QR Code, serta integrasi live feed Google Sheets.

---

## 📚 Dokumentasi Perancangan Sistem (SDLC)

Seluruh rancangan sistem telah disusun secara komprehensif mengikuti standar siklus hidup pengembangan perangkat lunak (SDLC) dan dapat diakses pada folder [`docs/`](docs/):

* 📑 **[Dokumen Master: PERANCANGAN_SISTEM.md](docs/PERANCANGAN_SISTEM.md)**: Rangkuman utuh seluruh perancangan sistem SIMASADI.
* 🌐 **[Bagian 1: Gambaran Umum Website](docs/01-gambaran-umum.md)**: Latar belakang KAN/BSN, tujuan, ruang lingkup (*in-scope* & *out-of-scope*), profil 4 aktor sistem, dan arsitektur umum.
* 📅 **[Bagian 2: Tahap Perencanaan (Planning)](docs/02-tahap-perencanaan.md)**: Metodologi Iterative Agile Prototyping, linimasa pengembangan fase 1 sampai 6, studi kelayakan teknis/operasional, serta analisis risiko dan mitigasi.
* 🔍 **[Bagian 3: Tahap Analisis (Analysis)](docs/03-tahap-analisis.md)**: Analisis masalah dengan PIECES Framework & Fishbone Diagram, spesifikasi Kebutuhan Fungsional (`REQ-F-01` s/d `REQ-F-32`), dan Kebutuhan Non-Fungsional (`REQ-NF-01` s/d `REQ-NF-10`).
* 📐 **[Bagian 4: Tahap Perancangan (Design)](docs/04-tahap-perancangan.md)**: Struktur Navigasi (Sitemap), Use Case Diagram, Activity Diagrams, Sequence Diagrams, Entity Relationship Diagram (ERD), Class Diagram, dan Wireframes antarmuka desktop & mobile.
* 🧪 **[Bagian 5: Implementasi dan Pengujian](docs/05-implementasi-dan-pengujian.md)**: Spesifikasi teknologi, struktur direktori, detail implementasi komponen UI, matriks kasus uji otomatis PHPUnit (119 feature tests, 680 assertions), uji responsivitas antarmuka manual, dan bukti hasil eksekusi uji.

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
   * **Tahap 1 (Toleransi Berjalan)**: Batas waktu pengisian dokumen surveilen berlaku hingga akhir bulan tanggal kunjungan (`submission_due_date = end_at->endOfMonth()`).
   * **Tahap 2 (Dibekukan / Jendela Penyelesaian 1 Tahun)**: Jika melewati batas toleransi pengisian tanpa penyelesaian, status asesmen dan LPK otomatis berubah menjadi **DIBEKUKAN** (`SUSPENDED`, badge warna ungu kontras `#f3e8ff` dengan teks `#6b21a8`). LPK diberikan hak dan kesempatan menyelesaikan asesmennya selama 1 tahun penuh dengan indikator hitung mundur (*countdown*).
   * **Tahap 3 (Dicabut)**: Jika setelah 1 tahun masa pembekuan terlampaui asesmen belum selesai, status akreditasi LPK otomatis beralih menjadi **DICABUT** (`REVOKED`, badge merah).
   * **Aturan Auto-Realisasi LPK Aktif**: Seluruh data surveilen dan tindakan perbaikan masa lalu (`end_at < now()->startOfYear()`) dari LPK yang saat ini berstatus AKTIF otomatis dianggap terealisasi (`COMPLETED` dan `SATISFIED`).

3. **Mesin Penegakan SLA Tindakan Perbaikan (TP & VTP):**
   * **SLA Dasar**: 3 bulan untuk Akreditasi Awal (AA) dan 2 bulan untuk proses asesmen lainnya.
   * **Perpanjangan Waktu**: Maksimal 1 bulan dengan syarat ketat: LPK telah melakukan perbaikan sebagian dari temuan ketidaksesuaian asesmen (misal 8 dari 10 temuan selesai) dan menyertakan Nomor Surat Permohonan Resmi. Jika tidak ada perbaikan sama sekali (kosong) selama 2 atau 3 bulan awal, proses dihentikan dan LPK langsung dibekukan.
   * **Status Otomatis**: Tanpa input manual status TP. Status otomatis "Sedang Berlangsung" sebelum jatuh tempo, "Dibekukan" bila melewati SLA tanpa pemenuhan, dan "Selesai/Memenuhi" saat tanggal pemenuhan (`tp_satisfied_at`) dicatat.
   * **Pengingat Kalender**: Pengingat TP 2 bulan untuk AA, 1 bulan untuk asesmen lain; pengingat jatuh tempo; dan pengingat SK 10 hari kalender setelah pemenuhan TP.

4. **Alur Evaluasi Hasil Asesmen (EHA) & Penerbitan SK:**
   * Pencatatan tanggal rencana sidang EHA, tanggal realisasi EHA, dan rekomendasi tim panitia teknis.
   * Penerbitan Nomor SK KAN, Tanggal Terbit SK, dan kalkulasi otomatis Lead Time Terbit SK (`sk_lead_time_days`).
   * Verifikasi publik dan portal mandiri LPK (`/portal`) dilengkapi kode QR keabsahan dokumen.

5. **Pelaporan Biaya Asesor (SBM PMK) & Billing PNBP (SIMPONI):**
   * Pelaporan rincian biaya: Transportasi, Uang Harian, Honorarium Asesor / Tenaga Ahli, dan Paket Data/Akomodasi.
   * Verifikasi kepatuhan SBM Kementerian Keuangan (`MENUNGGU_VERIFIKASI`, `TERVERIFIKASI`, `PERLU_REVISI`).
   * Penerbitan Kode Billing SIMPONI 15 digit dan pencatatan nomor transaksi kas negara (NTPN 16 digit).
   * Quality Gate Kesiapan Terbit Dokumen: SK Akreditasi terkunci hingga billing PNBP berstatus `PAID` dan biaya SBM berstatus `TERVERIFIKASI`.

6. **Kalender Kerja Interaktif 5 Tipe Event:**
   * Menyajikan 5 jenis kegiatan: Asesmen Lapangan (Biru), Pengingat Surveilen (Kuning/Oranye), Pengingat Batas TP (Indigo), Overdue TP (Merah/Ungu), dan Pengingat Penerbitan SK (Hijau/Emerald).
   * Fitur pemilih cepat bulan dan tahun dinamis serta pembuatan agenda instan per tanggal klik.

7. **Impor Massal & Live Feed Google Sheets:**
   * Impor massal data LPK dan Asesmen dari file CSV, XLSX, atau URL Google Sheets live (*Smart Upsert*).
   * Live CSV Feeds terproteksi API key (`/feeds/expenses.csv`, `/feeds/lpks.csv`, `/feeds/assessments.csv`) untuk formula `=IMPORTDATA` Google Sheets secara real-time.

---

## 👥 Profil Pengguna & Hak Akses (Role-Based Access Control)

Sistem mengimplementasikan pemisahan hak akses berbasis 4 peran nyata:

| Peran (*Role*) | Akun Login | Deskripsi Hak Akses Utama | Batasan Keamanan |
|---|---|---|---|
| **Admin Unit Akreditasi Lab** (`admin`) | `admin@simasadi.local` | Akses penuh (*Full Control*): Registrasi, ubah, hapus LPK; impor massal LPK & Asesmen; penjadwalan 8 tipe asesmen KAN; pemrosesan EHA; penugasan PIC; verifikasi biaya SBM; penerbitan billing SIMPONI; manajemen akun pengguna; ekspor live feeds. | Tanpa batasan akses dalam sistem. |
| **PIC Laboratorium / Unit Teknis** (`pic`) | `pic@simasadi.local` | Monitoring operasional LPK kelolaan: melihat direktori LPK binaan, memantau tenggat waktu surveilen dan toleransi pengisian, pelaporan biaya asesor mandiri, pelacakan progres TP, input nomor surat permohonan perpanjangan waktu. | Dibatasi secara ketat (*403 Forbidden*): dilarang mengimpor data massal, dilarang mengakses LPK di luar tanggung jawabnya, dilarang memverifikasi biaya SBM sendiri, dan dilarang mengelola user. |
| **Asesor KAN** (`assessor`) | `assessor@simasadi.local` | Portal Asesor (`/assessor`): Melihat jadwal penugasan asesmen lapangan, profil LPK binaan yang diases, evaluasi teknis, dan verifikasi pemenuhan tindakan perbaikan. | Terbatas hanya pada asesmen di mana dirinya ditugaskan. |
| **Lembaga Penilaian Kesesuaian** (`lpk`) | `lpk@simasadi.local` | Portal LPK Mandiri (`/portal`): Memantau masa aktif sertifikat, hitung mundur batas surveilen, SLA tindakan perbaikan, dan verifikasi QR Code keabsahan akreditasi. | Terbatas hanya pada data entitas lembaganya sendiri. |

*(Kata sandi bawaan untuk seluruh akun pengujian:* `password`*)*

---

## 🛠️ Tumpukan Teknologi

* **Backend:** PHP 8.2+ / PHP 8.3+, Laravel 12 / 13 Framework
* **Basis Data:** SQLite 3 (Database file lokal efisien)
* **Frontend:** Blade Templating, Vanilla CSS (Modern CSS Variables & Design Tokens), Vanilla JavaScript modular
* **Asset Bundler:** Vite 6 / 8
* **Tipografi:** Instrument Sans (WOFF2/WOFF)
* **Library Tambahan:** SweetAlert2 (Notifikasi & modal interaktif)
* **Testing:** PHPUnit 11 (Feature & Unit Testing)

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

Seluruh alur fungsional sistem, logika SLA KAN, kalkulasi status otomatis, dan pembatasan hak akses peran telah diverifikasi dengan test suite otomatis PHPUnit:

```bash
docker exec prototype-magang-laravel php artisan test
```

**Hasil Pengujian Terkini:**
```text
Tests:    119 passed (680 assertions)
Duration: ~35s
Status:   100% Passed
```

Untuk memformat kode PHP sesuai standar PSR-12:
```bash
docker exec prototype-magang-laravel vendor/bin/pint --format agent
```

---

## 📂 Struktur Penting Proyek

* `app/Http/Controllers`: Kontroler logika sistem (LpkController, AssessmentController, CalendarEventController, LpkImportController, AssessmentImportController, GoogleSheetsReportController, dll.).
* `app/Models`: Model Eloquent relasional (Lpk, Assessment, AssessmentExpense, Accreditation, AccreditationBilling, AccreditationSignature, CalendarEvent, User, Backup).
* `database/migrations`: Skema migrasi tabel basis data relasional.
* `database/seeders`: Pembangkitan data awal master LPK, akun 4 peran, asesmen historis terealisasi, dan agenda kalender.
* `docs/`: Dokumentasi perancangan sistem dan arsitektur perangkat lunak lengkap (SDLC bagian 1 sampai 5).
* `resources/css`: Sistem desain antarmuka, variabel token warna, tata letak grid, dan responsivitas mobile.
* `resources/js`: Logika interaktif antarmuka, sinkronisasi toggle mobile, filter drawer, dan modul kalender.
* `resources/views`: Template antarmuka Blade Laravel.
* `tests/Feature`: Test suite otomatis untuk seluruh fitur operasional dan kepatuhan regulasi.

---

## 📌 Status Proyek

Proyek ini adalah **prototype operasional magang** yang terverifikasi penuh untuk validasi alur dan antarmuka operasional akreditasi KAN-BSN.

## 📄 Lisensi

Proyek ini dilisensikan di bawah lisensi [MIT](LICENSE).
