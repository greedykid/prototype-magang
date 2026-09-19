# DOKUMEN PERANCANGAN SISTEM SIMASADI
## Bagian 5: Implementasi dan Pengujian (Implementation & Testing)

---

### 5.1 Implementasi Sistem

#### 5.1.1 Lingkungan Pengembangan & Spesifikasi Perangkat Lunak
Sistem SIMASADI diimplementasikan dengan spesifikasi lingkungan teknis sebagai berikut:

| Komponen | Spesifikasi / Library | Versi | Peran dalam Sistem |
|---|---|---|---|
| **Bahasa Pemrograman** | PHP | 8.3+ | Logika backend server, kontroler, dan pengolahan data model. |
| **Framework Backend** | Laravel Framework | 13.x | Routing, Middleware, Eloquent ORM, Autentikasi sesi, Blade engine. |
| **Basis Data** | SQLite | 3.x | Database file lokal nir-server (`database/database.sqlite`). |
| **Asset Bundler** | Vite | 8.x | Kompilasi SCSS/CSS dan bundling JavaScript instan Hot Module Replacement (HMR). |
| **Desain Antarmuka** | Vanilla CSS (CSS Variables) | Native CSS3 | Sistem desain kustom (Design Tokens), flexbox, CSS grid, dan responsif media queries. |
| **Tipografi** | Instrument Sans | WOFF2 / WOFF | Font korporat modern untuk kejelasan baca data pada antarmuka. |
| **Notifikasi Interaktif** | SweetAlert2 | 11.x | Modal dialog dan toast notifikasi feedback aksi (sukses, validasi, konfirmasi). |
| **Test Runner** | PHPUnit | 11.x | Pengujian otomatis alur fungsional backend (*Feature Testing*). |

#### 5.1.2 Struktur Direktori Proyek
```text
prototype-magang/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Pengendali logika alur (LpkController, AssessmentController, dll.)
│   │   └── Requests/             # Validasi request formulir
│   └── Models/                   # Model Eloquent (Lpk, Assessment, Issue, CalendarEvent, dll.)
├── database/
│   ├── migrations/               # 14 file migrasi skema tabel relasional
│   ├── seeders/                  # Seeder akun awal dan data dummy operasional
│   └── database.sqlite           # File basis data lokal
├── docs/                         # Seluruh dokumentasi perancangan SDLC (.md)
├── resources/
│   ├── css/
│   │   └── app.css               # Seluruh aturan desain antarmuka, variabel token, dan media query
│   ├── js/
│   │   └── app.js                # Modul JavaScript: SPA navigation, sync toggle, filter drawer, custom select
│   └── views/
│       ├── components/           # Blade components: icon, status, priority
│       ├── layouts/              # Template master (app.blade.php) & app-shell
│       ├── lpks/                 # Halaman index, create, edit, show LPK
│       ├── assessments/          # Halaman program asesmen
│       ├── accreditations/       # Halaman proses akreditasi
│       ├── amendments/           # Halaman pengajuan amandemen
│       ├── calendar/             # Halaman kalender dan agenda kerja
│       ├── issues/               # Halaman pelaporan dan log follow-up masalah
│       └── monitoring/           # Halaman layanan KANMIS dan histori backup
├── routes/
│   └── web.php                   # Definisi rute aplikasi terproteksi sesi
└── tests/
    └── Feature/                  # Test suite otomatis (PrototypeFlowTest, CalendarEventTest)
```

#### 5.1.3 Fitur Teknis Kunci yang Diimplementasikan
1. **Penyelarasan Posisi Toggle di Mobile (`syncViewToggleLocation`):**
   * Menggunakan pendeteksi media query JavaScript `window.matchMedia('(max-width: 600px)')`.
   * Pada resolusi mobile, elemen `.table-view-toggle` dipindahkan secara otomatis ke dalam baris `.table-controls` di samping tombol filter.
   * Pada desktop, posisi dikembalikan secara presisi ke toolbar tabel utama.
2. **Arsitektur Grid Card 2-Kolom Terstruktur:**
   * Diimplementasikan murni menggunakan CSS Grid (`grid-template-columns: 84px minmax(0, 1fr)`) untuk memastikan seluruh label dan nilai teratur dalam sumbu vertikal yang konsisten, menghilangkan tampilan zig-zag pada versi sebelumnya.
   * Dilengkapi header badge bertema lavender (`#f0edff` / `#5645d4`) serta footer aksi dengan tombol `Detail →`.
3. **Tombol Detail Interaktif pada Tampilan Tabel:**
   * Ditata sebagai action badge berlatar `#f5f3ff` dengan border `#dcd7fa` dan panah chevron CSS.
   * Dilengkapi efek transisi hover ke latar ungu solid `#5645d4` dengan teks putih dan animasi micro-nudge.
4. **Preservasi Status Filter pada Navigasi Halaman:**
   * Setiap pagination URL mengikat query string aktif menggunakan `$data->withQueryString()->links()`.
   * Pilihan jumlah baris (*entries per page*) disinkronkan ke input tersembunyi formulir pencarian.

---

### 5.2 Pengujian Sistem (Testing)

#### 5.2.1 Strategi Pengujian
Pengujian dilakukan menggunakan pendekatan **Feature Testing Otomatis** (berbasis PHPUnit & Database Refresh) untuk memverifikasi fungsionalitas backend, serta **Manual Cross-Device Testing** untuk menguji ketepatan interaksi visual dan antarmuka.

#### 5.2.2 Matriks Kasus Uji Otomatis (PHPUnit Feature Tests)

Semua kasus uji berikut tercakup dalam berkas pengujian di `tests/Feature/`:

| ID Uji | File Test | Skenario Pengujian | Input / Kondisi | Ekspektasi Hasil | Status |
|---|---|---|---|---|---|
| **TEST-01** | `PrototypeFlowTest` | Pengguna tamu (*guest*) dilarang mengakses dashboard. | Akses URL `GET /dashboard` tanpa login. | HTTP Status 302 Redirect ke `/login`. | **PASSED** |
| **TEST-02** | `PrototypeFlowTest` | Login pengguna internal berhasil dan membuka dashboard. | POST `/login` dengan email valid & password benar. | HTTP Status 302 Redirect ke `/dashboard`, teks "Selamat datang" tampil. | **PASSED** |
| **TEST-03** | `PrototypeFlowTest` | Penambahan data LPK baru ke basis data. | POST `/lpks` (registration_number: `LPK-TEST-001`, name: `LPK Uji Coba`, status: `ACTIVE`). | Redirect sukses, data tersimpan di tabel `lpks`. | **PASSED** |
| **TEST-04** | `PrototypeFlowTest` | Pelaporan masalah operasional dan pencatatan riwayat follow-up. | POST `/issues` lalu dilanjutkan POST `/issues/{id}/follow-ups` dengan catatan tindakan. | Masalah tercatat, follow-up tersimpan di tabel `issue_followups` terhubung ke `user_id`. | **PASSED** |
| **TEST-05** | `PrototypeFlowTest` | Filter data *server-side* berjalan akurat di seluruh 6 tabel master. | Request filter: status LPK, status asesmen + tanggal mulai, masalah due date, backup status, dll. | Hanya baris data yang cocok dengan kriteria filter yang tampil di halaman. | **PASSED** |
| **TEST-06** | `PrototypeFlowTest` | Query string filter tetap terjaga saat navigasi pagination. | Request `/lpks?status=ACTIVE&page=2`. | Link halaman berikutnya tetap menyertakan parameter `status=ACTIVE`. | **PASSED** |
| **TEST-07** | `CalendarEventTest` | Pengguna terotentikasi dapat membuka halaman kalender kegiatan. | Akses `GET /calendar`. | HTTP Status 200 OK, teks "Kalender kegiatan" tampil. | **PASSED** |
| **TEST-08** | `CalendarEventTest` | Pembuatan agenda kegiatan baru pada kalender. | POST `/calendar/events` dengan parameter lpk_id, title, start_at, end_at, status. | Redirect ke detail agenda, data tersimpan di tabel `calendar_events`. | **PASSED** |
| **TEST-09** | `CalendarEventTest` | Klik kotak tanggal kalender otomatis mengisi form pembuatan agenda. | Akses `GET /calendar/events/create?date=2026-09-21`. | Form terbuka dengan input `start_date` dan `end_date` terisi nilai `2026-09-21`. | **PASSED** |
| **TEST-10** | `CalendarEventTest` | Validasi penolakan jam selesai lebih awal dari jam mulai agenda. | POST `/calendar/events` dengan jam selesai < jam mulai. | Sistem menolak submission dengan error validasi waktu. | **PASSED** |
| **TEST-11** | `CalendarEventTest` | Pengguna dapat memperbarui agenda kegiatan yang sudah ada. | PUT `/calendar/events/{id}` dengan status `COMPLETED`. | Data agenda terbarui di basis data. | **PASSED** |
| **TEST-12** | `ExampleTest` | Verifikasi dasar responsivitas lingkungan pengujian. | Akses halaman publik root. | Redirect berjalan normal tanpa exception. | **PASSED** |
| **TEST-13** | `SimasadiFeaturesTest` | Pelaporan rincian biaya perjalanan dinas asesor dan verifikasi kepatuhan SBM Kementerian Keuangan. | POST `/assessments/{id}/expenses` dilanjutkan POST `/assessments/{id}/expenses/verify` (status: `TERVERIFIKASI`). | Total biaya Rp 2.910.000 terhitung otomatis, status berubah menjadi `Terverifikasi SBM`, nama verifikator tercatat. | **PASSED** |
| **TEST-14** | `SimasadiFeaturesTest` | Penerbitan kode billing SIMPONI 15 digit dan simulasi pelunasan kas negara. | POST `/accreditations/{id}/billings` lalu POST `/accreditations/{id}/billings/{billing}/pay`. | Kode 15 digit berawalan '8' terbit, status `UNPAID` $\rightarrow$ `PAID` dengan nomor transaksi NTPN sah tercatat. | **PASSED** |
| **TEST-15** | `SimasadiFeaturesTest` | Pembubuhan tanda tangan elektronik SK Akreditasi bersertifikat BSrE dan verifikasi integritas publik. | POST `/accreditations/{id}/esign` dengan passphrase, lalu akses `GET /verify-sk/{hash}`. | Hash SHA-256 dan nomor seri BSrE terbentuk, status akreditasi `COMPLETED`, halaman publik memvalidasi keaslian dokumen. | **PASSED** |
| **TEST-16** | `SimasadiFeaturesTest` | Logika audit kesiapan rilis output akreditasi (*Release Readiness Quality Gate*). | Evaluasi `$accreditation->isReleaseReady()`. | Mengembalikan `false` jika billing belum bayar atau belum TTE, dan `true` saat PNBP lunas & dokumen bertanda tangan digital. | **PASSED** |

#### 5.2.3 Bukti Hasil Eksekusi Uji Otomatis
Perintah eksekusi:
```bash
php artisan test
```
Hasil eksekusi:
```text
  PASS  Tests\Feature\CalendarEventTest
  ✓ authenticated user can view calendar                                 0.42s  
  ✓ authenticated user can create calendar event                         0.12s  
  ✓ calendar date opens create form with selected date                   0.08s  
  ✓ event rejects end time before start time                             0.09s  
  ✓ authenticated user can update calendar event                         0.10s  

  PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                        0.05s  

  PASS  Tests\Feature\PrototypeFlowTest
  ✓ guest is sent to login                                               0.06s  
  ✓ user can login and view dashboard                                    0.11s  
  ✓ user can create lpk                                                  0.09s  
  ✓ user can create issue and followup                                   0.15s  
  ✓ table filters work across all six index pages                        0.28s  
  ✓ table filter query string is preserved by pagination                 0.12s  

  PASS  Tests\Feature\SimasadiFeaturesTest
  ✓ can report and verify assessment expenses                            0.18s  
  ✓ can generate and pay pnbp billing                                    0.14s  
  ✓ can sign accreditation with bsre esign and verify publicly           0.16s  
  ✓ accreditation release readiness gate logic                           0.11s  

  Tests:    16 passed (73 assertions)
  Duration: 2.03s
```

#### 5.2.4 Matriks Pengujian Antarmuka & Responsivitas Manual

| Skenario Antarmuka | Kondisi Uji / Resolusi | Hasil yang Diamati | Status |
|---|---|---|---|
| **Penempatan Sejajar Toggle & Filter di Layar Mobile** | Layar ponsel (lebar <= 600px) pada halaman Asesmen/LPK | Tombol `[ Filter data ]` dan toggle `[ Tabel \| Grid ]` berposisi sejajar horizontal di baris atas. Dropdown entries berada di bawahnya. | **VALID** |
| **Perapihan Struktur Kartu pada Mode Grid** | Mode Grid pada halaman Asesmen & LPK | Kartu memiliki header abu-abu halus, teks kategori dibungkus lavender pill badge, label & value sejajar dalam grid 2-kolom, footer memiliki tombol `Detail →`. | **VALID** |
| **Tombol Detail Interaktif pada Mode Tabel** | Mode Tabel pada desktop | Tautan "Detail" tampil sebagai action badge berlatar `#f5f3ff` dengan panah chevron. Saat di-hover berubah menjadi ungu solid dengan micro-nudge. | **VALID** |
| **Interaksi Drawer Filter Mobile** | Klik tombol `Filter data` di mobile | Filter drawer meluncur mulus dari sisi kanan layar (*slide-out* 240ms) dengan backdrop gelap, tombol tutup (x) berfungsi, dan tombol ESC dapat menutup drawer. | **VALID** |
| **Persistensi Pilihan Mode Tampilan** | Pengguna memilih mode Grid lalu me-refresh halaman | Preferensi tersimpan di `localStorage`, halaman tetap terbuka dalam mode Grid tanpa kembali ke Tabel. | **VALID** |
| **Kompilasi Asset Vite Tanpa Error** | Eksekusi `npm run build` | Seluruh berkas CSS (142 kB) dan JS (124 kB) terkompilasi sempurna dalam 2.03 detik tanpa peringatan fatal. | **VALID** |
