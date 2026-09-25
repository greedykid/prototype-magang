# DOKUMEN PERANCANGAN SISTEM SIMASADI
## Bagian 5: Implementasi dan Pengujian (Implementation & Testing)

---

### 5.1 Implementasi Sistem

#### 5.1.1 Lingkungan Pengembangan & Spesifikasi Perangkat Lunak
Sistem SIMASADI diimplementasikan dengan spesifikasi lingkungan teknis sebagai berikut:

| Komponen | Spesifikasi / Library | Versi | Peran dalam Sistem |
|---|---|---|---|
| **Bahasa Pemrograman** | PHP | 8.2+ / 8.3+ | Logika backend server, kontroler MVC, dan accessor bisnis pada Model Eloquent. |
| **Framework Backend** | Laravel Framework | 12.x / 13.x | Routing, Middleware RBAC, Eloquent ORM, Autentikasi sesi, Blade templating engine. |
| **Basis Data** | SQLite | 3.x | Database file lokal terpadu nir-konfigurasi (`database/database.sqlite`). |
| **Asset Bundler** | Vite | 6.x / 8.x | Kompilasi SCSS/CSS dan bundling JavaScript Hot Module Replacement (HMR). |
| **Desain Antarmuka** | Vanilla CSS (CSS Variables) | Native CSS3 | Sistem desain kustom (Design Tokens), flexbox, CSS grid, dan responsif media queries. |
| **Tipografi** | Instrument Sans | WOFF2 / WOFF | Font korporat modern untuk kejelasan baca data pada antarmuka. |
| **Notifikasi Interaktif** | SweetAlert2 | 11.x | Modal dialog konfirmasi dan toast feedback aksi (sukses, validasi, konfirmasi). |
| **Test Runner** | PHPUnit | 11.x | Pengujian otomatis alur fungsional, logika regulasi KAN, dan RBAC (*Feature Testing*). |

#### 5.1.2 Struktur Direktori Proyek
```text
prototype-magang/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Pengendali logika alur (LpkController, AssessmentController, dll.)
│   │   ├── Middleware/           # Middleware proteksi peran (RoleMiddleware)
│   │   └── Requests/             # Validasi request formulir
│   └── Models/                   # Model Eloquent (Lpk, Assessment, AssessmentExpense, User, dll.)
├── database/
│   ├── migrations/               # File migrasi skema tabel relasional
│   ├── seeders/                  # Seeder akun 4 peran, data master LPK, dan riwayat asesmen
│   └── database.sqlite           # File basis data lokal
├── docs/                         # Seluruh dokumentasi perancangan SDLC komprehensif (.md)
├── resources/
│   ├── css/
│   │   ├── base/                 # Reset dan token desain variabel warna/tipografi
│   │   ├── components/           # Badges, custom select, modal dialog, tabel, clickable rows
│   │   ├── features/             # Kalender kegiatan, dashboard, dan laporan biaya
│   │   └── layout/               # Shell navigasi sidebar, topbar, dan responsivitas mobile
│   ├── js/
│   │   ├── modules/              # Kalender interaktif, custom select, modal, SPA router
│   │   └── app.js                # Inisialisasi utama JavaScript antarmuka
│   └── views/
│       ├── components/           # Blade components: icon, status badge, alert
│       ├── layouts/              # Template master (app.blade.php) & app-shell
│       ├── lpks/                 # Halaman index, create, edit, show LPK, dan import
│       ├── assessments/          # Halaman program asesmen, pelacakan TP, SBM, dan EHA
│       ├── calendar/             # Halaman kalender interaktif dan modal quick add
│       ├── users/                # Halaman manajemen pengguna dan profil
│       └── portal/               # Portal LPK mandiri dengan kode QR keabsahan dokumen
├── routes/
│   ├── console.php               # Perintah terjadwal CLI (check-surveillance)
│   └── web.php                   # Definisi rute aplikasi terproteksi sesi dan peran
└── tests/
    └── Feature/                  # Test suite otomatis komprehensif (14 file test)
```

#### 5.1.3 Fitur Teknis Kunci yang Diimplementasikan
1. **Mesin Toleransi Surveilen 3 Tahap & Pembekuan Bertahap:**
   * Menghitung batas toleransi pengisian dokumen surveilen sampai akhir bulan tanggal kunjungan (`submission_due_date = end_at->endOfMonth()`).
   * Mengubah status asesmen dan LPK seketika menjadi DIBEKUKAN (`SUSPENDED`, badge ungu kontras `#f3e8ff` dengan teks `#6b21a8`) bila melewati toleransi tanpa penyelesaian, memberikan jendela hak penyelesaian 1 tahun, dan mencabut status akreditasi (`REVOKED`) jika batas 1 tahun terlampaui.
   * Auto-realisasi data historis: bila LPK aktif saat ini, seluruh asesmen surveilen dan TP masa lalu (`end_at < now()->startOfYear()`) otomatis berstatus selesai (`COMPLETED` dan `SATISFIED`).
2. **Mesin Penegakan SLA Tindakan Perbaikan (TP & VTP):**
   * Menghitung otomatis SLA dasar (3 bulan untuk AA, 2 bulan untuk proses lainnya).
   * Membatasi perpanjangan maksimal 1 bulan hanya jika ada progres tindak lanjut nyata terhadap temuan ketidaksesuaian serta menyertakan nomor surat permohonan resmi. Jika perbaikan kosong, perpanjangan ditolak dan LPK langsung dibekukan.
   * Status TP dihitung sepenuhnya oleh sistem tanpa input manual: Sedang Berlangsung, Dibekukan (ungu) bila lewat batas waktu, dan Memenuhi bila telah diselesaikan.
3. **Penyederhanaan Keterangan Operasional Otomatis (`dynamic_keterangan`):**
   * Keterangan diformat langsung pada teks kegiatan inti yang dicetak tebal (contoh: **Surveilen 1: Terjadwal 29/03/2027**), menghapus awalan teks administratif redundan.
4. **Interaktivitas Tabel Penuh (*Clickable Rows*):**
   * Baris tabel pada modul LPK dan Asesmen dapat diklik langsung untuk menuju halaman detail, mempercepat navigasi pengguna tanpa harus mengklik tombol teks kecil.
5. **Penyelarasan Posisi Toggle di Layar Ponsel & Filter Drawer:**
   * Pada resolusi mobile (`<= 600px`), tombol toggle mode tampilan sejajar secara presisi di samping tombol filter data, dan panel filter meluncur mulus dari sisi kanan layar (*slide-out drawer*).

---

### 5.2 Pengujian Sistem (Testing)

#### 5.2.1 Strategi Pengujian
Pengujian dilakukan menggunakan pendekatan **Feature Testing Otomatis** (berbasis PHPUnit & Database Refresh) untuk memverifikasi fungsionalitas backend, aturan regulasi KAN, dan RBAC 4 peran, serta **Manual Cross-Device Testing** untuk menguji ketepatan interaksi visual dan antarmuka.

#### 5.2.2 Matriks Kasus Uji Otomatis (PHPUnit Feature Tests)

Semua pengujian berikut tercakup dalam 14 berkas test di `tests/Feature/`:

| No | File Test | Fokus Skenario Pengujian | Hasil |
|---|---|---|---|
| 1 | `AssessmentImportTest` | Menguji pengunduhan template impor asesmen, validasi header kolom wajib, dan impor massal dari file CSV dengan pemetaan otomatis nomor registrasi LPK dan tipe asesmen KAN. | **PASSED** |
| 2 | `AssessmentStatusAutoTest` | Menguji siklus toleransi akhir bulan kunjungan, pembekuan otomatis (SUSPENDED) dengan jendela 1 tahun, pencabutan akreditasi (REVOKED), dan auto-realisasi data lampau LPK aktif. | **PASSED** |
| 3 | `AssessmentTpVtpTest` | Menguji SLA dasar 3 bulan (AA) dan 2 bulan (lainnya), perpanjangan 1 bulan bersyarat, larangan perpanjangan jika perbaikan kosong, sinkronisasi kalender, dan pengingat SK 10 hari. | **PASSED** |
| 4 | `CalendarEventTest` | Menguji tampilan kalender, pembuatan agenda event, validasi waktu (selesai >= mulai), pemilih langsung bulan dan tahun dinamis, serta rendering 5 tipe kategori warna event. | **PASSED** |
| 5 | `ClickableTableRowsTest` | Menguji interaktivitas seluruh baris tabel master LPK dan program asesmen yang dapat diklik langsung menuju tautan detail. | **PASSED** |
| 6 | `ErrorPageTest` | Menguji rendering halaman kustom 403 Forbidden (pembatasan hak akses) dan 404 Not Found baik untuk sesi login maupun pengunjung publik. | **PASSED** |
| 7 | `ExampleTest` | Menguji pengalihan akses halaman publik utama ke portal login dengan status 302 Redirect yang aman. | **PASSED** |
| 8 | `GoogleSheetsIntegrationTest` | Menguji ekspor CSV terotentikasi, endpoint live feed CSV (`/feeds/*`) dengan validasi secret API key, serta tampilan modal panduan formula `=IMPORTDATA`. | **PASSED** |
| 9 | `LpkImportTest` | Menguji impor massal master LPK dari file CSV, file XLSX, dan live link Google Sheets menggunakan logika *Smart Upsert* serta pembatasan peran PIC dari aksi impor. | **PASSED** |
| 10 | `PrototypeFlowTest` | Menguji alur operasional end-to-end: login, dashboard metrik, pembuatan LPK dengan otomasi masa berlaku (+5 tahun), filter server-side, pengiriman email peringatan surveilen, dan perhitungan lead time SK. | **PASSED** |
| 11 | `RoleAccessControlTest` | Menguji pembatasan hak akses RBAC: Admin unit memiliki akses penuh, PIC laboratorium terisolasi hanya pada LPK kelolaan sendiri dan dilarang mengelola LPK milik PIC lain. | **PASSED** |
| 12 | `SimasadiFeaturesTest` | Menguji pelaporan dan verifikasi biaya perjalanan dinas asesor (SBM PMK), penerbitan billing SIMPONI 15 digit, pencatatan nomor NTPN sah, e-Sign BSrE, dan Quality Gate rilis SK. | **PASSED** |
| 13 | `UserManagementTest` | Menguji manajemen akun pengguna internal oleh Administrator Unit: pencarian, filter, penambahan user baru, pembaruan password, dan proteksi larangan menghapus akun sendiri. | **PASSED** |
| 14 | `UserProfileTest` | Menguji pembaruan profil mandiri pengguna: pengubahan nama, verifikasi keunikan email, dan validasi kecocokan kata sandi lama saat mengganti kata sandi. | **PASSED** |

#### 5.2.3 Bukti Hasil Eksekusi Uji Otomatis

Perintah eksekusi:
```bash
docker exec prototype-magang-laravel php artisan test
```

Hasil eksekusi:
```text
   PASS  Tests\Feature\AssessmentImportTest
  ✓ admin can download import template                                   0.28s  
  ✓ admin can import assessments from csv file                           0.12s  
  ✓ import validates required assessment headers                         0.05s  
  ✓ import matches lpk by registration number                            0.08s  

   PASS  Tests\Feature\AssessmentStatusAutoTest
  ✓ assessment tolerance within visit month stays in progress            0.15s  
  ✓ assessment overdue tolerance auto changes to suspended               0.08s  
  ✓ suspended assessment gives lpk one year resolution window            0.08s  
  ✓ lpk is revoked after one year suspension expires                     0.07s  
  ✓ active lpk past year assessments auto resolve to completed           0.18s  

   PASS  Tests\Feature\AssessmentTpVtpTest
  ✓ authenticated user can view tp tracking tab in assessment show      0.18s  
  ✓ admin can update tp tracking and sla calculation                     0.09s  
  ✓ guest cannot update tp tracking                                      0.09s  
  ✓ assessments index filters by tp status                               0.30s  
  ✓ dashboard surfaces urgent tp alerts                                  0.22s  
  ✓ tp deadlines are synchronized with calendar                          0.28s  
  ✓ tp extension is disallowed when tp status is none                    0.09s  
  ✓ tp extension is allowed when tp status is in progress or under veri… 0.10s  
  ✓ assessment report date and eha fields can be saved and viewed        0.18s  

   PASS  Tests\Feature\CalendarEventTest
  ✓ authenticated user can view calendar                                 0.19s  
  ✓ authenticated user can create calendar event                         0.08s  
  ✓ calendar date opens create form with selected date                   0.07s  
  ✓ event rejects end time before start time                             0.10s  
  ✓ calendar month navigation does not carry over circle highlight unle… 0.32s  
  ✓ calendar displays lpk surveillance and reaccreditation milestones    0.90s  
  ✓ calendar renders month and year direct selectors                     0.13s  
  ✓ calendar year selector dynamically includes years from lpk expiry d… 0.25s  
  ✓ calendar year selector dynamically includes earlier years from lpk…  0.23s  
  ✓ calendar renders all four matrix color categories and reminders      0.82s  

   PASS  Tests\Feature\ClickableTableRowsTest
  ✓ lpk index table rows are clickable with show link                    0.42s  
  ✓ assessments index table rows are clickable with show link            0.25s  

   PASS  Tests\Feature\ErrorPageTest
  ✓ 403 forbidden page renders custom access restriction view            0.09s  
  ✓ 404 not found page renders custom view                               0.07s  
  ✓ guest 404 renders standalone layout                                  0.04s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application sends visitors to login                              0.05s  

   PASS  Tests\Feature\GoogleSheetsIntegrationTest
  ✓ authenticated user can export expenses csv                           0.15s  
  ✓ authenticated user can export lpks csv                               0.10s  
  ✓ authenticated user can export assessments csv                        0.13s  
  ✓ google sheets live feed endpoint with valid key                      0.13s  
  ✓ google sheets live feed endpoint rejects invalid key                 0.04s  
  ✓ ui renders google sheets modal and formula                           0.56s  

   PASS  Tests\Feature\LpkImportTest
  ✓ admin can download import template                                   0.08s  
  ✓ admin can download import template xlsx                              0.05s  
  ✓ admin can import lpks from xlsx file                                 0.08s  
  ✓ pic is forbidden from importing or downloading template              0.11s  
  ✓ admin can import lpks from csv file                                  0.06s  
  ✓ import with semicolon delimiter and smart upsert                     0.13s  
  ✓ import validates required headers                                    0.05s  
  ✓ import from google sheets url                                        0.13s  
  ✓ ui renders import button for admin and hides for pic                 0.11s  
  ✓ import with custom spreadsheet headers and numeric numbers           0.11s  
  ✓ import with plain text link does not save invalid relative url       0.14s  

   PASS  Tests\Feature\PrototypeFlowTest
  ✓ guest is sent to login                                               0.10s  
  ✓ user can login and view dashboard                                    0.16s  
  ✓ user can create lpk                                                  0.06s  
  ✓ table filters work across index pages                                0.78s  
  ✓ table filter query string is preserved by pagination                 1.03s  
  ✓ user can create lpk with expiry and drive link                       0.72s  
  ✓ lpk scope can be searched and exported to csv                        0.37s  
  ✓ lpk calculates correct surveillance and reaccreditation milestones   0.07s  
  ✓ surveillance reminder email can be sent to lab pic                   0.14s  
  ✓ simulation notification can be sent even without active alerts       0.19s  
  ✓ persistent notification renders on ui and cannot be dismissed        0.87s  
  ✓ artisan check surveillance command                                   0.12s  
  ✓ lpk index table headers and columns                                  0.41s  
  ✓ user can input and update keterangan in lpk detail                   0.27s  
  ✓ lpk is expiring soon status accuracy                                 0.05s  
  ✓ lpk index filters by expiry status                                   1.12s  
  ✓ lpk index filters by surveillance status                             1.31s  
  ✓ surveillance notification buttons link to filtered lpk index         3.37s  
  ✓ surveillance reminder simulation email sends successfully            0.15s  
  ✓ lpk dynamic status identifies overdue surveillance and expired       0.36s  
  ✓ lpk index and show render dynamic status badge                       2.22s  
  ✓ lpk automatically generates surveillance and reaccreditation assess… 0.96s  
  ✓ user can input sk number and date when tp completed                  0.75s  
  ✓ assessment sk lead time calculation and display                      0.76s  

   PASS  Tests\Feature\RoleAccessControlTest
  ✓ quick login page renders all role options                            0.13s  
  ✓ admin has full access to monitoring and administration               0.14s  
  ✓ pic role can manage own lpks and assessments but forbidden from oth… 3.86s  
  ✓ role helpers and attributes work correctly                           0.05s  
  ✓ admin can delete lpk                                                 0.08s  
  ✓ pic can delete own lpk but forbidden from deleting other pic lpk     0.20s  

   PASS  Tests\Feature\SimasadiFeaturesTest
  ✓ can report and verify assessment expenses                            0.31s  
  ✓ can generate and pay pnbp billing                                    0.23s  
  ✓ can sign accreditation with bsre esign and verify publicly           0.10s  
  ✓ accreditation release readiness gate logic                           0.06s  

   PASS  Tests\Feature\UserManagementTest
  ✓ guest cannot access user management                                  0.06s  
  ✓ pic cannot access user management                                    0.09s  
  ✓ admin can view user management list                                  0.10s  
  ✓ admin can search and filter users                                    0.11s  
  ✓ admin can create new user                                            0.05s  
  ✓ admin can update user info and password                              0.06s  
  ✓ admin cannot degrade own role                                        0.05s  
  ✓ admin can delete other user                                          0.05s  
  ✓ admin cannot delete own account                                      0.04s  

   PASS  Tests\Feature\UserProfileTest
  ✓ guest cannot access profile                                          0.08s  
  ✓ authenticated user can view profile page                             0.12s  
  ✓ user can update profile info                                         0.05s  
  ✓ user cannot update email to already taken email                      0.05s  
  ✓ user can update password with correct current password               0.05s  
  ✓ user cannot update password with incorrect current password          0.05s  
  ✓ user cannot update password with mismatched confirmation             0.05s  

  Tests:    119 passed (680 assertions)
  Duration: 35.78s
```

#### 5.2.4 Matriks Pengujian Antarmuka & Responsivitas Manual

| Skenario Antarmuka | Kondisi Uji / Resolusi | Hasil yang Diamati | Status |
|---|---|---|---|
| **Pembedaan Warna Badge Pembekuan** | Halaman detail LPK & Asesmen dengan status Dibekukan | Badge berlatar ungu `#f3e8ff` dengan teks `#6b21a8` tampil kontras dan membedakan dengan jelas status dibekukan dari status merah (kedaluwarsa). | **VALID** |
| **Keterangan Operasional Otomatis** | Kartu profil LPK dan tabel asesmen | Keterangan langsung menampilkan kegiatan inti dalam teks tebal tanpa imbuhan awalan teks redundan. | **VALID** |
| **Klik Baris Tabel (*Clickable Table Rows*)** | Klik pada sembarang area baris data pada tabel LPK / Asesmen | Sistem langsung merespons dan membuka halaman detail data terkait dengan kursor pointer interaktif. | **VALID** |
| **Penyelarasan Kontrol Filter & Toggle di Mobile** | Layar smartphone (lebar <= 600px) | Tombol `[ Filter data ]` dan toggle `[ Tabel \| Grid ]` berposisi berdampingan horizontal rapi di baris atas. | **VALID** |
| **Interaksi Drawer Filter Mobile** | Klik tombol `Filter data` di mobile | Filter drawer meluncur mulus dari sisi kanan layar (*slide-out* 240ms) dengan backdrop gelap dan tombol tutup berfungsi baik. | **VALID** |
| **Kompilasi Aset Frontend Vite** | Eksekusi `npm run build` | Seluruh berkas CSS dan JavaScript terkompilasi bersih tanpa ada kesalahan kompilasi. | **VALID** |
