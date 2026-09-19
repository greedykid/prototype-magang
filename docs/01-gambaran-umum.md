# DOKUMEN PERANCANGAN SISTEM SIMASADI
## Bagian 1: Gambaran Umum Website

---

### 1.1 Identitas Sistem
* **Nama Sistem:** SIMASADI (Sistem Informasi dan Administrasi Akreditasi)
* **Instansi / Konteks:** Kedeputian Bidang Akreditasi - Badan Standardisasi Nasional (BSN) / Komite Akreditasi Nasional (KAN)
* **Tipe Aplikasi:** Web-based Enterprise Monitoring & Administration Workspace (Responsive Desktop & Mobile)
* **Status Pengembangan:** Prototype Operasional & Validasi Alur Kerja (Magang)

---

### 1.2 Latar Belakang
Komite Akreditasi Nasional (KAN) di bawah naungan Badan Standardisasi Nasional (BSN) bertanggung jawab memberikan akreditasi kepada Lembaga Penilaian Kesesuaian (LPK) di seluruh Indonesia, seperti Laboratorium Pengujian, Laboratorium Kalibrasi, Lembaga Sertifikasi Produk (LSPro), Lembaga Sertifikasi Sistem Manajemen, dan Lembaga Inspeksi.

Dalam operasional harian, tim sekretariat dan asesor menghadapi sejumlah tantangan administratif dan pengawasan, antara lain:
1. **Fragmentasi Data Operasional:** Pencatatan status akreditasi, penjadwalan surveilen/asesmen, pengajuan amandemen ruang lingkup, dan penanganan isu operasional sering kali tersebar di berbagai berkas manual, spreadsheet, atau kanal komunikasi informal.
2. **Kebutuhan Monitoring Progres Cepat:** Pimpinan dan staf membutuhkan visibilitas cepat terhadap metrik penting (jumlah LPK aktif, program asesmen berjalan, target jatuh tempo amandemen, serta histori backup data).
3. **Kebutuhan Validasi Alur & Pengalaman Pengguna:** Sebelum diintegrasikan dengan sistem backend resmi yang kompleks (KANMIS eksternal), diperlukan prototype operasional lokal yang interaktif, berkinerja tinggi, responsif, dan mudah digunakan oleh staf baik dari desktop kerja maupun perangkat mobile di lapangan.

SIMASADI dirancang sebagai ruang kerja internal (*workspace*) terpadu untuk memvalidasi alur kerja, interaksi data, dan antarmuka operasional akreditasi tersebut.

---

### 1.3 Tujuan Sistem
1. **Sentralisasi Data LPK & Akreditasi:** Menyediakan repositori lokal terstruktur untuk data LPK, siklus proses akreditasi, masa berlaku sertifikat, dan lingkup akreditasi.
2. **Otomasi Penjadwalan & Kalender Kegiatan:** Menyediakan kalender kerja interaktif dengan fitur pembuatan agenda instan per tanggal untuk surveilen, asesmen awal, dan peninjauan lapangan.
3. **Pelacakan Isu & Catatan Tindak Lanjut (*Issue Tracking*):** Memfasilitasi pelaporan masalah operasional dan pencatatan riwayat follow-up secara kronologis hingga terselesaikan (*resolved*).
4. **Monitoring Layanan & Histori Backup:** Memberikan fasilitas pencatatan manual status ketersediaan layanan pendukung (KANMIS) dan integritas riwayat backup data.
5. **Aksesibilitas & Fleksibilitas Antarmuka:** Menyediakan antarmuka modern dengan mode ganda (Tabel & Grid Card), pencarian dan filter *server-side*, serta navigasi mobile *drawer* yang intuitif dan bebas hambatan.

---

### 1.4 Ruang Lingkup Sistem

#### 1.4.1 Dalam Scope (*In-Scope*)
* **Manajemen Autentikasi:** Otentikasi pengguna internal berbasis sesi Laravel dengan proteksi *guest* & *auth*.
* **Dashboard Metrik & Analisis:** Visualisasi KPI utama (LPK terdaftar, proses aktif, asesmen terjadwal, masalah terbuka), analisis status akreditasi dalam bentuk progress bar proporsional, daftar masalah prioritas, dan aktivitas terkini.
* **Manajemen Data LPK:** Penambahan, pengubahan, pencarian, dan visualisasi detail profil LPK beserta relasi akreditasi dan asesmen terkait.
* **Siklus Proses Akreditasi:** Pemantauan status akreditasi (Belum Mulai, Berjalan, Selesai), tanggal mulai, dan target selesai.
* **Program Asesmen:** Pengelolaan jadwal asesmen (Surveilen, Asesmen Awal, Reassessment) terhubung ke LPK dan penanggalan spesifik.
* **Kalender Kerja Interaktif:** Tampilan kalender bulanan dengan grid 7 hari, indikator hari ini (*today*), visualisasi agenda berstatus, dan mode agenda khusus mobile.
* **Pengajuan Amandemen:** Pencatatan amandemen lingkup akreditasi beserta nomor permohonan dan penargetan waktu.
* **Pelaporan Masalah Operasional:** Pelaporan isu berstatus (*Open, In Progress, Resolved*) dan berprioritas (*Low, Medium, High*) disertai form follow-up berantai.
* **Monitoring Layanan & Backup:** Pencatatan status ketersediaan layanan KANMIS dan histori log backup data manual.
* **Fitur UX Modern:**
  * Toggle tampilan data: **Tabel** (mode data masif) vs **Grid Cards** (mode kartu informasi ringkas) dengan penyimpanan preferensi di `localStorage`.
  * Filter multi-kriteria *server-side* dengan *Drawer* geser di mobile dan baris *active filter chips* yang dapat dihapus per kriteria.
  * Dropdown *custom searchable select* untuk pemilihan data instan.
  * Notifikasi toast menggunakan SweetAlert2.

#### 1.4.2 Luar Scope (*Out-of-Scope*)
* Integrasi langsung dengan API eksternal resmi KANMIS (dicatat secara representasional/mock lokal).
* Eksekusi pencadangan database fisik otomatis ke storage cloud (halaman backup mencatat status histori administratif).
* Pembayaran Penerimaan Negara Bukan Pajak (PNBP) biaya akreditasi secara online.
* Penandatanganan digital sertifikat akreditasi (e-Sign/BSrE).

---

### 1.5 Profil Pengguna (Aktor Sistem)

| Aktor | Peran Utama | Hak Akses & Tanggung Jawab |
|---|---|---|
| **Administrator Sistem** | Pengelola sistem & konfigurasi | Mengelola seluruh modul data, memantau histori backup, status layanan, dan integritas aplikasi. |
| **Staf Administrasi Akreditasi** | Operator administrasi harian | Menginput dan memperbarui profil LPK, mencatat permohonan amandemen, mencatat proses akreditasi, dan memfilter data. |
| **Auditor / Asesor KAN** | Pelaksana asesmen & pengawas lapangan | Melihat agenda pada kalender kegiatan, memperbarui status program asesmen, melaporkan kendala/masalah operasional, dan menambahkan catatan tindak lanjut. |

---

### 1.6 Arsitektur & Tumpukan Teknologi Singkat
* **Backend Framework:** Laravel 13 (PHP 8.3+)
* **Arsitektur:** Model-View-Controller (MVC) Monolith dengan Blade Engine
* **Database:** SQLite 3 (Database file lokal efisien, nir-konfigurasi server pihak ketiga)
* **Frontend Assets & Bundler:** Vite 8, Vanilla CSS modern berbasis Design Tokens, Vanilla JavaScript modular
* **Komponen & Desain:** Custom Design System berbasis variabel CSS, SweetAlert2 untuk feedback interaktif, Font Instrument Sans, dan SVG Icons native Blade.
