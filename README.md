# SIMASADI
### Sistem Informasi dan Administrasi Akreditasi
**Badan Standardisasi Nasional (BSN) / Komite Akreditasi Nasional (KAN)**

SIMASADI adalah workspace operasional terpadu berbasis web yang dirancang untuk memantau, mengelola, dan memvalidasi alur kerja administrasi akreditasi Lembaga Penilaian Kesesuaian (LPK), program asesmen, pelaporan masalah, pengajuan amandemen, agenda kalender kerja, serta pemantauan layanan pendukung (KANMIS) dan histori pencatatan backup.

Aplikasi ini dikembangkan sebagai prototype lokal berkinerja tinggi guna mengeksplorasi antarmuka modern yang responsif, adaptif lintas perangkat, dan ramah pengguna sebelum terhubung ke sistem backend resmi.

---

## 📚 Dokumentasi Perancangan Sistem (SDLC)

Seluruh rancangan sistem telah disusun secara komprehensif mengikuti standar siklus hidup pengembangan perangkat lunak (SDLC) dan dapat diakses pada folder [`docs/`](docs/):

* 📑 **[Dokumen Master: PERANCANGAN_SISTEM.md](docs/PERANCANGAN_SISTEM.md)** — Rangkuman utuh seluruh perancangan sistem SIMASADI.
* 🌐 **[Bagian 1: Gambaran Umum Website](docs/01-gambaran-umum.md)** — Latar belakang KAN/BSN, tujuan, ruang lingkup (*in-scope* & *out-of-scope*), profil aktor sistem, dan arsitektur umum.
* 📅 **[Bagian 2: Tahap Perencanaan (Planning)](docs/02-tahap-perencanaan.md)** — Metodologi Iterative Agile Prototyping, linimasa pengembangan fase 1–6, studi kelayakan teknis/operasional, serta analisis risiko & mitigasi.
* 🔍 **[Bagian 3: Tahap Analisis (Analysis)](docs/03-tahap-analisis.md)** — Analisis masalah dengan PIECES Framework & Fishbone Diagram, spesifikasi Kebutuhan Fungsional (`REQ-F-01` s/d `REQ-F-24`), dan Kebutuhan Non-Fungsional (`REQ-NF-01` s/d `REQ-NF-09`).
* 📐 **[Bagian 4: Tahap Perancangan (Design)](docs/04-tahap-perancangan.md)** — Struktur Navigasi (Sitemap), Use Case Diagram, Activity Diagrams, Sequence Diagrams, Entity Relationship Diagram (ERD 11 tabel), Class Diagram, dan Wireframes antarmuka desktop & mobile.
* 🧪 **[Bagian 5: Implementasi dan Pengujian](docs/05-implementasi-dan-pengujian.md)** — Spesifikasi teknologi, struktur direktori, detail implementasi komponen UI, matriks kasus uji otomatis PHPUnit (16 feature tests), uji responsivitas antarmuka manual, dan bukti hasil eksekusi uji.

---

## ✨ Fitur Utama Sistem

1. **Dashboard Metrik & Analisis:**
   * Ringkasan KPI instan (Total LPK, Akreditasi Aktif, Asesmen Terjadwal, Isu Terbuka).
   * Analisis visual status akreditasi dalam progress bar persentase proporsional.
   * Daftar cepat masalah prioritas tinggi dan aktivitas asesmen terbaru.
2. **Manajemen Data LPK:**
   * Pencatatan nomor registrasi, nama lembaga, kategori (LSPro, Lab Kalibrasi, Lab Uji, dll.), kota, provinsi, dan jumlah lingkup akreditasi.
   * Detail profil LPK terhubung ke riwayat proses akreditasi dan asesmen terkait.
3. **Proses Akreditasi & Program Asesmen:**
   * Pemantauan siklus akreditasi dengan tahapan monitoring dan tanggal target penyelesaian.
   * Penjadwalan asesmen surveilen, asesmen awal, dan penugasan asesor kepala (*lead assessor*).
4. **Kalender Kerja Interaktif:**
   * Visualisasi bulanan kegiatan kerja dengan penanda hari ini (*today*).
   * **Pembuatan agenda instan:** Klik langsung pada kotak tanggal tertentu pada kalender untuk membuka form tambah agenda dengan tanggal mulai dan tanggal selesai yang otomatis terisi.
   * Mode tampilan ganda: Mode Kalender Kotak dan Mode Agenda List (ramah ponsel).
5. **Pelaporan Masalah & Log Tindak Lanjut (*Issue Tracking*):**
   * Pelaporan kendala operasional berstatus (*Open, In Progress, Resolved*) dan berprioritas (*Low, Medium, High*).
   * Form pencatatan catatan tindak lanjut (*follow-up*) kronologis berantai.
6. **Pengajuan Amandemen:**
   * Administrasi permohonan amandemen ruang lingkup akreditasi berserta nomor surat pengajuan.
7. **Monitoring Layanan KANMIS & Histori Backup:**
   * Pencatatan status ketersediaan sistem KANMIS internal (*Up, Degraded, Down*).
   * Histori pencadangan data manual (*Success, Failed, Unknown*) beserta staf pencatat dan ukuran berkas.
8. **Pelaporan Biaya Perjalanan Dinas Asesor (*Cost Reporting* - Kepatuhan SBM):**
   * Pelaporan rincian biaya: Uang Harian, Transportasi (Tiket/Tol/BBM), Akomodasi/Hotel, dan Paket Data/Komunikasi per asesmen.
   * Status verifikasi kepatuhan: `Belum Dilaporkan`, `Menunggu Verifikasi SBM`, `Terverifikasi SBM`, dan `Perlu Revisi`.
   * Form input biaya dan modal verifikasi persetujuan oleh Sekretariat KAN mengacu pada Standar Biaya Masukan (SBM) Peraturan Menteri Keuangan.
9. **Realisasi Billing PNBP (SIMPONI Kemenkeu):**
   * Penerbitan Kode Billing SIMPONI 15 digit dan tarif PNBP jasa akreditasi sesuai PP PNBP BSN.
   * Masa berlaku pembayaran, status tagihan (`Belum Bayar`, `Terbayar`, `Kadaluarsa`).
   * Formulir simulasi pelunasan kas negara dengan pencatatan Nomor Transaksi Penerimaan Negara (NTPN 16-karakter) dan kanal perbankan.
10. **Tanda Tangan Elektronik Dokumen SK (e-Sign BSrE):**
   * Pembubuhan tanda tangan elektronik tersertifikasi Balai Sertifikasi Elektronik (BSrE - BSSN) atas nama Ketua Komite Akreditasi Nasional.
   * Visualisasi Segel Digital (*Digital Seal Badge*), NIP, nomor seri sertifikat, serta nilai hash SHA-256 integritas dokumen.
   * Pratinjau QR Code simulasi dan rute publik verifikasi keaslian dokumen SK (`/verify-sk/{hash}`).
11. **Quality Gate Kesiapan Terbit Output Akreditasi (*Release Readiness Gate*):**
   * Verifikasi otomatis kepatuhan administratif dan finansial: SK Akreditasi hanya dapat dirilis (`OUTPUT_RELEASED`) jika realisasi billing PNBP telah terbayar (`PAID`) dan seluruh biaya asesmen telah berstatus `TERVERIFIKASI`.
12. **Pengalaman Pengguna (UX) Modern & Bebas Slop:**
   * **Dual-Mode Tampilan:** Bebas beralih antara mode **Tabel** (padat dan tabular) dan mode **Grid Cards** (kartu visual 2-kolom terstruktur) dengan preferensi tersimpan di `localStorage`.
   * **Penataan Sejajar di Mobile:** Tombol filter dan toggle mode tabel/grid berdampingan rapi (sejajar) di layar ponsel (`<= 600px`).
   * **Mobile Filter Drawer:** Panel filter meluncur dari sisi kanan layar (*slide-out drawer*) mirip aplikasi native.
   * **Active Filter Chips:** Label indikator filter aktif yang dapat dihapus per kriteria secara instan.
   * **Tombol Detail Interaktif:** Aksi tombol badge ungu beranimasi panah pada tabel.
   * **Custom Searchable Select:** Dropdown pencarian opsi cepat tanpa ketergantungan library berat.
   * **Notifikasi Elegan:** Notifikasi toast dan dialog terpadu menggunakan SweetAlert2.
13. **Integrasi Google Sheets Live Sync (`=IMPORTDATA`) & Ekspor CSV:**
    * **Live Data Feed:** Endpoint CSV langsung untuk formula spreadsheet `=IMPORTDATA("http://.../feeds/expenses.csv?key=simasadi-live")` yang otomatis memperbarui data di Google Sheets secara real-time.
    * **Unduh CSV UTF-8 BOM:** Ekspor rekapitulasi Biaya Asesor (SBM), Data Master LPK, dan Program Asesmen yang langsung kompatibel dengan Microsoft Excel dan Google Drive.
    * **Modal Dialog 1-Klik:** Panduan 3-langkah cepat dan tombol salin formula ke papan klip (*clipboard*).
14. **Impor Massal Data Master LPK (Smart Upsert):**
    * **Dukungan Ganda:** Impor dari unggahan berkas `.csv` (koma maupun titik koma) atau penarikan langsung dari tautan publik Google Sheets via HTTP client.
    * **Pencegahan Duplikasi (Smart Upsert):** Pembaruan otomatis profil LPK jika Nomor Registrasi sudah ada, atau pembuatan catatan baru jika belum terdaftar.
    * **Template Resmi Siap Unduh:** Fasilitas pengunduhan file `template-import-lpk.csv` dengan contoh struktur data LPK nyata.

---

## 🔄 Alur Kerja Utama Berdasarkan Peran (Role-Based Flowcharts)

Aplikasi SIMASADI mengimplementasikan pemisahan hak akses dan tanggung jawab operasional (*Role-Based Access Control / RBAC*) yang ketat antara pihak Sekretariat Akreditasi KAN dan pihak Laboratorium Terakreditasi.

Sistem dirancang secara presisi dengan **2 peran utama**:

### 👥 Kredensial Akun Pengguna

| Peran (*Role*) | Akun Login | Hak Akses Utama | Batasan Keamanan |
|---|---|---|---|
| **Admin Unit Akreditasi Lab** | `admin@simasadi.local` | Akses penuh: Tata kelola master LPK (input/edit/hapus/impor), notifikasi pengawasan surveilen, penjadwalan asesmen, verifikasi SBM biaya asesor, proses akreditasi & billing PNBP SIMPONI, amandemen lingkup, e-Sign BSrE SK, monitoring KANMIS & backup, kalender, ekspor Google Sheets. | Tanpa batasan (Akses Administrator Unit). |
| **PIC Laboratorium** | `pic@simasadi.local` | Monitoring operasional: Dashboard ringkasan & peringatan surveilen, direktori data laboratorium terakreditasi & dokumen Google Drive, jadwal asesmen lapangan KAN, kalender pengawasan, serta pelaporan kendala (Issue Tracker). | Ditolak (403) dan disembunyikan dari seluruh fungsi administratif sekretariat (tambah/ubah/hapus LPK, penjadwalan asesmen, verifikasi SBM, billing PNBP, amandemen, serta monitoring layanan KANMIS & backup). |

*(Kata sandi bawaan untuk seluruh akun:* `password`*)*

---

### 1. Alur Kerja Administrator Unit Akreditasi Lab (`admin@simasadi.local`)

Administrator Unit bertindak sebagai pengelola utama di lingkungan Direktorat Akreditasi Laboratorium KAN/BSN yang memfasilitasi seluruh siklus pengawasan LPK, penjadwalan asesmen, verifikasi finansial SBM, billing PNBP, hingga penerbitan SK Akreditasi bertanda tangan elektronik BSrE.

```mermaid
flowchart TD
    StartAdmin(["Mulai: Login sebagai Admin Unit Lab"]) --> DashboardAdmin["Akses Dashboard: Pantau KPI, Status Pengawasan, & Distribusi Akreditasi"]

    DashboardAdmin --> PilihanKelola{"Pilih Modul Pengelolaan"}

    %% 1. Master Data LPK
    PilihanKelola -->|"Master LPK"| KelolaLPK["Manajemen Lembaga Penilaian Kesesuaian (LPK)"]
    KelolaLPK --> InputLPK{"Metode Tambah Data"}
    InputLPK -->|"Input Manual"| FormLPK["Isi Profil LPK & Ruang Lingkup"]
    InputLPK -->|"Impor Massal"| ImportLPK["Unggah File CSV / Sync Google Sheets (Smart Upsert)"]
    FormLPK --> DataLPKAktif["Master Data LPK Aktif"]
    ImportLPK --> DataLPKAktif
    DataLPKAktif --> CekSurveilen{"Deteksi Jatuh Tempo Surveilen (S1/S2/RA)?"}
    CekSurveilen -->|"Ya"| KirimReminder["Kirim Notifikasi Email Peringatan ke PIC Lab"]
    CekSurveilen -->|"Hapus LPK"| HapusLPK["Hapus Data LPK (Konfirmasi Modal SweetAlert2)"]

    %% 2. Agenda Asesmen & Verifikasi Biaya
    PilihanKelola -->|"Program Asesmen"| JadwalAsesmen["Jadwalkan Kunjungan Asesmen Lapangan (KAN U-01)"]
    JadwalAsesmen --> InputBiaya["Asesor / Tim Menginput Rincian Realisasi Biaya SBM"]
    InputBiaya --> VerifBiaya{"Verifikasi Kepatuhan SBM (Sekretariat KAN)"}
    VerifBiaya -->|"Perlu Revisi"| TolakBiaya["Kembalikan ke Pelapor dengan Catatan Perbaikan"]
    TolakBiaya --> InputBiaya
    VerifBiaya -->|"Sesuai Pagu SBM"| SetujuiBiaya["Setujui Realisasi Biaya (Status: TERVERIFIKASI)"]

    %% 3. Akreditasi, PNBP & e-Sign
    PilihanKelola -->|"Proses Akreditasi"| BuatAkreditasi["Buka Proses Akreditasi Baru / Re-Akreditasi"]
    BuatAkreditasi --> TerbitBilling["Terbitkan Kode Billing SIMPONI (PNBP 15-Digit)"]
    TerbitBilling --> BayarBilling{"Konfirmasi Pembayaran Kas Negara"}
    BayarBilling -->|"Lunas"| CatatNTPN["Input Nomor NTPN 16-Karakter (Status: PAID)"]
    BayarBilling -->|"Belum Bayar"| MonitorBilling["Pantau Masa Aktif Billing"]
    MonitorBilling --> BayarBilling

    SetujuiBiaya --> QualityGate{"Quality Gate: Kesiapan Rilis SK"}
    CatatNTPN --> QualityGate
    QualityGate -->|"Syarat Lengkap (PAID + TERVERIFIKASI)"| ESignSK["Pembubuhan e-Sign BSrE & Segel Digital Dokumen SK"]
    QualityGate -->|"Syarat Belum Terpenuhi"| TahanSK["Rilis SK Ditahan Sistem (Quality Gate Lock)"]

    %% 4. Monitoring Sistem & Integrasi
    PilihanKelola -->|"Sistem & Integrasi"| MonitoringSistem["Monitoring Layanan KANMIS, Pencadangan Backup, & Live CSV Feed"]

    ESignSK --> SelesaiAdmin(["Selesai: SK Resmi Diterbitkan & Data Tersinkronisasi"])
    MonitoringSistem --> SelesaiAdmin
    KirimReminder --> SelesaiAdmin
    HapusLPK --> SelesaiAdmin
```

---

### 2. Alur Kerja PIC Laboratorium (`pic@simasadi.local`)

PIC Laboratorium bertindak sebagai perwakilan resmi dari Laboratorium Terakreditasi yang menggunakan SIMASADI untuk memantau status kepatuhan akreditasi, meninjau jadwal kunjungan surveilen KAN, mengakses dokumen legalitas, dan melaporkan kendala operasional.

```mermaid
flowchart TD
    StartPIC(["Mulai: Login sebagai PIC Laboratorium"]) --> DashboardPIC["Akses Dashboard Ringkasan Lab Terakreditasi"]

    DashboardPIC --> CekNotif{"Terdapat Peringatan Pengawasan KAN?"}
    CekNotif -->|"Ada Jatuh Tempo (S1/S2/RA)"| BukaRoadmap["Buka Roadmap Siklus Pengawasan & Re-Akreditasi"]
    CekNotif -->|"Siklus Aman"| MenuPIC{"Pilih Kebutuhan Informasi"}
    BukaRoadmap --> MenuPIC

    %% 1. Direktori Lab & Dokumen Drive
    MenuPIC -->|"Data Laboratorium"| LihatProfil["Buka Direktori Data Laboratorium"]
    LihatProfil --> TinjauDetail["Tinjau Profil, Nomor Akreditasi, Ruang Lingkup, & Masa Berlaku SK"]
    TinjauDetail --> BukaDrive["Akses Berkas Sertifikat & Lampiran Resmi di Google Drive"]

    %% 2. Jadwal Asesmen
    MenuPIC -->|"Jadwal Asesmen"| CekAgenda["Buka Daftar Jadwal Asesmen Lapangan"]
    CekAgenda --> RincianAgenda["Lihat Waktu Kunjungan, Jenis Asesmen, & Asesor Kepala (Lead Assessor)"]

    %% 3. Kalender Pengawasan
    MenuPIC -->|"Kalender Pengawasan"| BukaKalender["Lihat Kalender Pengawasan Kerja (Tampilan Bulan/Minggu/Hari)"]

    %% 4. Pusat Kendala
    MenuPIC -->|"Pusat Kendala"| LaporKendala{"Menemukan Kendala Operasional?"}
    LaporKendala -->|"Ya"| BuatTiket["Buat Laporan Masalah Baru (Issue Tracker)"]
    BuatTiket --> CatatFollowup["Kirim Catatan Tindak Lanjut untuk Koordinasi dengan Sekretariat KAN"]
    LaporKendala -->|"Tidak"| SelesaiPIC(["Selesai: Pemantauan Kepatuhan Laboratorium Berjalan Lancar"])
    CatatFollowup --> SelesaiPIC
    BukaDrive --> SelesaiPIC
    RincianAgenda --> SelesaiPIC
    BukaKalender --> SelesaiPIC
```

---

## 🛠️ Tumpukan Teknologi

* **Backend:** PHP 8.3+, Laravel 13 Framework
* **Basis Data:** SQLite 3 (Database lokal nir-konfigurasi)
* **Frontend:** Blade Templating, Vanilla CSS (Modern CSS Variables & Design Tokens), Vanilla JavaScript modular
* **Asset Bundler:** Vite 8
* **Tipografi:** Instrument Sans (WOFF2/WOFF)
* **Library Tambahan:** SweetAlert2 (Notifikasi interaktif)
* **Testing:** PHPUnit 11 (Feature & Unit Testing)

---

## 📋 Persyaratan Sistem

* PHP 8.3 atau lebih baru dengan ekstensi `pdo_sqlite` aktif.
* Composer 2.x
* Node.js (v18+) dan npm

---

## 🚀 Panduan Instalasi & Menjalankan

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
   *(Perintah di atas akan menyiapkan skema database dan mengisikan data awal akun serta data contoh LPK, akreditasi, asesmen, dan kalender).*

4. **Menjalankan Server Aplikasi:**
   Jalankan backend Laravel:
   ```bash
   php artisan serve
   ```
   Jalankan server Vite untuk frontend:
   ```bash
   npm run dev
   ```
   Atau lakukan build asset produksi:
   ```bash
   npm run build
   ```

5. **Akses Aplikasi:**
   Buka peramban di `http://127.0.0.1:8000`. Sistem akan mengarahkan ke halaman login.
   * *Akun login:* Silakan periksa kredensial pada `database/seeders/DatabaseSeeder.php` atau gunakan akun admin default yang digenerate oleh seeder.

---

## 🧪 Pengujian Otomatis

Seluruh alur fungsional sistem telah diverifikasi dengan test suite otomatis PHPUnit:

```bash
php artisan test
```

Untuk menjalankan pengujian dengan tampilan ringkas:
```bash
php artisan test --compact
```

Untuk memformat kode PHP sesuai standar PSR-12:
```bash
vendor/bin/pint --format agent
```

---

## 📂 Struktur Penting Proyek

* `app/Http/Controllers`: Kontroler logika sistem (Auth, LPK, Asesmen, Kalender, Isu, dll.).
* `app/Models`: Model Eloquent relasional (Lpk, Accreditation, Assessment, Issue, CalendarEvent, dll.).
* `database/migrations`: Skema 14 tabel basis data.
* `database/seeders`: Pembangkit data awal simulasi prototype.
* `docs/`: Seluruh dokumen perancangan sistem dan arsitektur perangkat lunak (SDLC).
* `resources/css/app.css`: Sistem desain visual, variabel warna, tipografi, dan media query responsif.
* `resources/js/app.js`: Logika interaktif antarmuka, sinkronisasi toggle mobile, filter drawer, dan SPA transition.
* `resources/views`: Template antarmuka Blade Laravel.
* `tests/Feature`: Pengujian otomatis alur kerja utama aplikasi.

---

## 📌 Status Proyek

Proyek ini adalah **prototype operasional magang** untuk validasi alur dan antarmuka operasional akreditasi KAN-BSN. Seluruh data yang digunakan merupakan data simulasi lokal.

## 📄 Lisensi

Proyek ini dilisensikan di bawah lisensi [MIT](LICENSE).
