# DOKUMEN PERANCANGAN SISTEM SIMASADI
## Bagian 1: Gambaran Umum Website

---

### 1.1 Identitas Sistem
* **Nama Sistem:** SIMASADI (Sistem Informasi Manajemen Akreditasi & Surveilen Lembaga Penilaian Kesesuaian)
* **Instansi / Konteks:** Kedeputian Bidang Akreditasi: Badan Standardisasi Nasional (BSN) / Komite Akreditasi Nasional (KAN)
* **Tipe Aplikasi:** Web-based Enterprise Monitoring & Administration Workspace (Responsive Desktop & Mobile)
* **Status Pengembangan:** Prototype Operasional & Validasi Alur Kerja Terverifikasi (Magang)

---

### 1.2 Latar Belakang
Komite Akreditasi Nasional (KAN) di bawah naungan Badan Standardisasi Nasional (BSN) bertanggung jawab memberikan dan memelihara status akreditasi bagi Lembaga Penilaian Kesesuaian (LPK) di seluruh Indonesia. LPK tersebut mencakup Laboratorium Pengujian (ISO/IEC 17025), Laboratorium Kalibrasi (ISO/IEC 17025), Laboratorium Medis (ISO 15189), Lembaga Sertifikasi Produk (ISO/IEC 17065), Lembaga Sertifikasi Sistem Manajemen (ISO/IEC 17021-1), Lembaga Inspeksi (ISO/IEC 17020), Penyelenggara Uji Kemahiran (ISO/IEC 17043), serta Produsen Bahan Acuan (ISO 17034).

Dalam tata kelola pengawasan berkala dan kepatuhan akreditasi, sekretariat KAN, tim asesor, dan PIC pendamping LPK menghadapi berbagai tantangan operasional:
1. **Penegakan Regulasi KAN U-01 yang Kompleks:** Kebutuhan standarisasi alur untuk 8 tipe proses asesmen resmi KAN, penegakan Service Level Agreement (SLA) Tindakan Perbaikan (TP & VTP), serta penghitungan toleransi pengisian dokumen dan konsekuensi pembekuan status.
2. **Kebutuhan Monitoring Siklus Hidup Akreditasi Bertahap:** Penentuan status kepatuhan LPK membutuhkan aturan bertingkat yang jelas: masa toleransi pengisian (sampai akhir bulan kunjungan), masa pembekuan sementara dengan jendela penyelesaian 1 tahun, hingga sanksi pencabutan akreditasi bila kewajiban tidak diselesaikan.
3. **Akuntabilitas Keuangan & Dokumen Sah:** Diperlukan pencatatan laporan biaya perjalanan dinas asesor yang patuh terhadap Standar Biaya Masukan (SBM PMK), transparansi billing PNBP jasa akreditasi (SIMPONI Kemenkeu), serta penandatanganan elektronik dokumen SK (BSrE BSSN) yang dapat diverifikasi publik melalui QR Code.
4. **Sentralisasi Data & Integrasi Multi-Peran:** Diperlukan ruang kerja digital yang mampu menghubungkan Administrator Unit Akreditasi, PIC Unit Teknis, Asesor KAN di lapangan, serta perwakilan LPK pemegang sertifikat.

SIMASADI dibangun untuk menjadi platform operasional terpadu yang memvalidasi seluruh alur bisnis, aturan regulasi, dan antarmuka kerja tersebut secara presisi.

---

### 1.3 Tujuan Sistem
1. **Sentralisasi Data Master LPK & Skema KAN:** Menyediakan basis data terstruktur nomor registrasi resmi KAN (`no_reg`), skema akreditasi, masa berlaku sertifikat, penugasan PIC pendamping, dan berkas arsip Google Drive terpadu.
2. **Otomasi Siklus Pengawasan & 8 Proses Asesmen:** Memfasilitasi penjadwalan dan pemantauan 8 proses asesmen resmi KAN (Akreditasi Awal, Surveilen 1, Surveilen 1 + PRL, Surveilen 2, Surveilen 2 + PRL, Surveilen Tidak Terjadwal, Perluasan Ruang Lingkup, dan Re-Akreditasi).
3. **Penegakan Toleransi Kunjungan & Siklus Pembekuan Bertahap:** Menerapkan mesin status kepatuhan dinamis yang menghitung toleransi pengisian sampai akhir bulan kunjungan, memberikan jendela kesempatan penyelesaian 1 tahun bagi LPK yang berstatus dibekukan (`SUSPENDED`), dan mencabut status akreditasi (`REVOKED`) jika batas 1 tahun terlewati.
4. **Penegakan SLA Tindakan Perbaikan (TP & VTP):** Menghitung batas waktu awal SLA secara otomatis (3 bulan untuk AA, 2 bulan untuk asesmen lainnya), mengontrol syarat perpanjangan maksimal 1 bulan bersurat resmi hanya bagi LPK yang telah menunjukkan progres perbaikan temuan, serta melarang perpanjangan jika laporan perbaikan nihil/kosong.
5. **Kepatuhan Finansial SBM PMK & Billing PNBP:** Mengelola verifikasi biaya perjalanan dinas asesor dan mencatat realisasi setoran kas negara melalui kode billing SIMPONI 15 digit ber-NTPN sah sebagai syarat gerbang rilis SK (*Release Readiness Gate*).
6. **Aksesibilitas Multi-Peran & Verifikasi Publik:** Menyediakan portal mandiri bagi LPK (`/portal`) dan portal bagi asesor (`/assessor`) yang terintegrasi dengan kode QR verifikasi status dokumen secara real-time.

---

### 1.4 Ruang Lingkup Sistem

#### 1.4.1 Dalam Scope (*In-Scope*)
* **Manajemen Autentikasi & RBAC 4 Peran:** Pengelolaan hak akses berbasis sesi untuk Admin Unit Akreditasi Lab (`admin`), PIC Laboratorium / Unit Teknis (`pic`), Asesor KAN (`assessor`), dan Perwakilan LPK (`lpk`).
* **Manajemen Master Data LPK:** Pengelolaan nomor registrasi resmi KAN, skema akreditasi, alamat, email, kontak, penugasan PIC, masa berlaku sertifikat (otomasi +5 tahun), dan kalkulasi status dinamis (`ACTIVE`, `SURVEILLANCE_DUE`, `SURVEILLANCE_OVERDUE`, `SUSPENDED`, `REVOKED`, `EXPIRED`, `INACTIVE`).
* **Keterangan Operasional Otomatis (`dynamic_keterangan`):** Ringkasan operasional ringkas dan tegas dalam format cetak tebal tanpa imbuhan teks awalan yang berulang.
* **Siklus Hidup 3 Tahap Pengawasan:**
  * Tahap 1: Toleransi pengisian hingga akhir bulan tanggal kunjungan (`submission_due_date = end_at->endOfMonth()`).
  * Tahap 2: Pembekuan otomatis (`SUSPENDED`, badge ungu kontras) dengan jendela penyelesaian 1 tahun disertai hitung mundur waktu.
  * Tahap 3: Pencabutan akreditasi otomatis (`REVOKED`) bila kesempatan 1 tahun habis tanpa penyelesaian.
  * Auto-Realisasi LPK Aktif: Data surveilen dan TP lampau dari LPK yang saat ini aktif otomatis dianggap telah selesai (`COMPLETED` / `SATISFIED`).
* **Pelacakan SLA Tindakan Perbaikan (TP & VTP):** SLA dasar 3 bulan (AA) dan 2 bulan (lainnya), perpanjangan maksimal 1 bulan bersyarat ada progres temuan dan surat permohonan resmi, larangan perpanjangan jika perbaikan kosong, serta status otomatis tanpa input manual.
* **Alur Evaluasi Hasil Asesmen (EHA) & Penerbitan SK:** Penjadwalan sidang EHA, nomor SK, tanggal terbit SK, dan lead time terbit SK (`sk_lead_time_days`).
* **Biaya Asesor SBM PMK & SIMPONI Kemenkeu:** Pelaporan biaya perjalanan dinas, status verifikasi SBM, penerbitan billing SIMPONI, dan Quality Gate kesiapan terbit dokumen akreditasi.
* **Kalender Kerja Interaktif 5 Jenis Event:** Visualisasi multi-event (Asesmen Lapangan, Pengingat Surveilen, Pengingat Batas TP, Overdue TP, dan Pengingat SK) dengan navigasi pemilih cepat bulan dan tahun.
* **Impor Massal & Live Sync Data:** Impor LPK dan Asesmen (CSV/XLSX/Google Sheets) serta live feeds CSV terproteksi API key untuk Google Sheets `=IMPORTDATA`.

#### 1.4.2 Luar Scope (*Out-of-Scope*)
* Integrasi langsung secara online ke core-banking gateway perbankan real-time (verifikasi pembayaran menggunakan simulasi pencatatan nomor transaksi NTPN 16 karakter sah).
* Penyimpanan fisik dokumen ke storage cloud komersial multi-region (arsip dokumen ditautkan langsung melalui Google Drive terpadu instansi).

---

### 1.5 Profil Pengguna (Aktor Sistem)

| Aktor | Peran Utama | Hak Akses & Tanggung Jawab |
|---|---|---|
| **Admin Unit Akreditasi Lab** (`admin`) | Penanggung jawab tata kelola sistem & akreditasi | Akses penuh: mengelola direktori master LPK, impor massal data, penjadwalan 8 tipe asesmen KAN, alur EHA, verifikasi biaya SBM PMK, billing SIMPONI, penandatanganan SK BSrE, manajemen akun pengguna, dan konfigurasi sistem. |
| **PIC Laboratorium / Unit Teknis** (`pic`) | Pendamping operasional teknis LPK | Mengakses LPK binaan, memantau tenggat surveilen dan toleransi pengisian, menginput laporan biaya asesor mandiri, mencatat kemajuan perbaikan temuan asesmen, dan menginput nomor surat permohonan perpanjangan waktu SLA. |
| **Asesor KAN** (`assessor`) | Tenaga ahli / Lead Assessor KAN | Mengakses Portal Asesor (`/assessor`): melihat penugasan asesmen lapangan, meninjau profil teknis LPK yang diases, mengisi evaluasi asesmen, dan memantau status pemenuhan tindakan perbaikan. |
| **Lembaga Penilaian Kesesuaian** (`lpk`) | Pemegang sertifikat akreditasi KAN | Mengakses Portal LPK Mandiri (`/portal`): memantau status aktif sertifikat, hitung mundur batas toleransi pengisian surveilen, memantau SLA tindakan perbaikan, dan mengakses verifikasi QR Code keabsahan akreditasi. |

---

### 1.6 Arsitektur & Tumpukan Teknologi
* **Backend Framework:** Laravel 12 / 13 (PHP 8.2+ / PHP 8.3+)
* **Arsitektur:** Model-View-Controller (MVC) Monolith dengan Blade Engine
* **Basis Data:** SQLite 3 (Database file lokal efisien dan portabel)
* **Frontend Assets & Bundler:** Vite, Vanilla CSS modern berbasis Design Tokens, Vanilla JavaScript modular
* **Komponen & Desain:** Custom Design System berbasis CSS Variables, SweetAlert2 untuk feedback interaktif, Font Instrument Sans, dan SVG Icons native Blade
* **Pengujian Otomatis:** PHPUnit 11 (119 Feature Tests, 680 Assertions, 100% Passed)
