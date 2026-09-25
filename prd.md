# Product Requirement Document (PRD)
## SIMASADI: Sistem Informasi Manajemen Akreditasi & Surveilen Lembaga Penilaian Kesesuaian
### Komite Akreditasi Nasional (KAN) / Badan Standardisasi Nasional (BSN)

---

## 1. Dokumen Ringkasan Eksekutif (Executive Summary)

### 1.1 Latar Belakang & Identitas Sistem
**SIMASADI** adalah sistem informasi dan administrasi operasional terpadu yang dirancang khusus untuk memfasilitasi, memantau, dan memvalidasi siklus hidup akreditasi Lembaga Penilaian Kesesuaian (LPK) di bawah naungan Komite Akreditasi Nasional (KAN) dan Badan Standardisasi Nasional (BSN). LPK yang dikelola mencakup berbagai skema akreditasi KAN, antara lain:
* **LP**: Laboratorium Pengujian (SNI ISO/IEC 17025)
* **LK**: Laboratorium Kalibrasi (SNI ISO/IEC 17025)
* **LM**: Laboratorium Medis (SNI ISO 15189)
* **LSPr**: Lembaga Sertifikasi Produk, Proses, dan Jasa (SNI ISO/IEC 17065)
* **LSSM**: Lembaga Sertifikasi Sistem Manajemen Mutu (SNI ISO/IEC 17021-1)
* **LSE**: Lembaga Sertifikasi Sistem Manajemen Lingkungan (SNI ISO/IEC 17021-1)
* **LI**: Lembaga Inspeksi (SNI ISO/IEC 17020)
* **PTP**: Penyelenggara Uji Kemahiran (SNI ISO/IEC 17043)
* **BPP**: Produsen Bahan Acuan (SNI ISO 17034)

Aplikasi ini mengintegrasikan seluruh siklus pengawasan berkala (Surveilen KAN), 8 proses asesmen resmi KAN, penegakan Service Level Agreement (SLA) Tindakan Perbaikan (TP) dan Verifikasi Tindakan Perbaikan (VTP), alur Evaluasi Hasil Asesmen (EHA), kepatuhan keuangan berbasis Standar Biaya Masukan (SBM PMK), penagihan PNBP (SIMPONI Kemenkeu), penandatanganan elektronik dokumen SK (BSrE BSSN), portal publik/asesor dengan verifikasi QR Code, hingga pelaporan otomatis Google Sheets.

### 1.2 Tujuan Dokumen PRD
Dokumen ini berfungsi sebagai spesifikasi kebutuhan produk komprehensif yang merangkum seluruh domain bisnis, arsitektur data, logika kalkulasi hukum akreditasi KAN, alur kerja antar-peran, dan kebutuhan fungsional dari sistem operasional SIMASADI. Dokumen ini menjadi acuan teknis valid bagi pengembangan sistem berjalan maupun migrasi platform di lingkungan KAN dan BSN.

---

## 2. Profil Pengguna & Hak Akses (Role-Based Access Control)

Sistem SIMASADI mengimplementasikan pemisahan hak akses berbasis **4 peran (*roles*) nyata**:

| Peran (*Role*) | Akun Referensi | Deskripsi Tanggung Jawab | Hak Akses Utama | Batasan Keamanan |
| :--- | :--- | :--- | :--- | :--- |
| **Admin Unit Akreditasi Lab** (`admin`) | `admin@simasadi.local` | Penanggung jawab administrasi operasional akreditasi, verifikasi kepatuhan, dan pemeliharaan teknis sistem BSN. | Akses penuh (*Full Control*) ke seluruh modul: Registrasi, ubah, dan hapus LPK; impor massal LPK dan Asesmen (CSV/XLSX/Google Sheets); penjadwalan 8 tipe asesmen KAN; pemrosesan EHA; penugasan PIC; verifikasi biaya perjalanan dinas SBM; penerbitan billing PNBP SIMPONI; pencatatan SK KAN; ekspor data Google Sheets live feed; manajemen pengguna; dan pemicu simulasi/notifikasi surveilen. | Tanpa batasan hak akses di sistem. |
| **PIC Laboratorium / Unit Teknis** (`pic`) | `pic@simasadi.local` | Narahubung / personel operasional unit teknis yang mendampingi LPK binaan. | Akses pengelolaan LPK binaannya: melihat profil LPK kelolaan, memantau tenggat waktu surveilen dan toleransi pengisian, melihat kalender agenda kerja, pelaporan biaya perjalanan dinas mandiri, pencatatan kemajuan tindakan perbaikan (TP), serta penginputan nomor surat permohonan perpanjangan waktu. | Dibatasi secara ketat (*403 Forbidden*): dilarang mengimpor data LPK/asesmen massal, dilarang mengakses data LPK di luar tanggung jawabnya, dilarang memverifikasi biaya SBM sendiri (*no self-verification*), dan dilarang mengelola akun pengguna sistem. |
| **Asesor KAN** (`assessor`) | `assessor@simasadi.local` | Tenaga ahli / asesor kepala (*Lead Assessor*) yang ditugaskan KAN untuk melakukan asesmen lapangan atau audit dokumen. | Akses ke Portal Asesor (`/assessor`): melihat penugasan asesmen lapangan yang sedang dan akan berjalan, meninjau profil LPK yang diases, mengisi evaluasi teknis, serta memantau status pemenuhan tindakan perbaikan dari LPK terkait. | Dibatasi hanya pada data asesmen di mana dirinya ditugaskan sebagai asesor/lead assessor. |
| **Lembaga Penilaian Kesesuaian** (`lpk`) | `lpk@simasadi.local` | Entitas laboratorium atau lembaga inspeksi terakreditasi pemegang sertifikat KAN. | Akses ke Portal LPK Mandiri (`/portal`): memantau status aktif sertifikat akreditasi, memantau hitung mundur batas waktu surveilen, memeriksa batas waktu SLA tindakan perbaikan, serta mengakses verifikasi QR Code keabsahan akreditasi resmi. | Akses terbatas hanya pada data entitas lembaganya sendiri secara transparan (*read-only status & compliance tracking*). |

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
* **Keterangan Operasional Otomatis (`dynamic_keterangan`)**:
  Sistem menghasilkan ringkasan operasional otomatis yang langsung berfokus pada isi agenda utama dalam format teks tebal (contoh: **Surveilen 1: Terjadwal 29/03/2027** atau **Tindakan Perbaikan: Jatuh Tempo 15/05/2026**), tanpa imbuhan awalan teks administratif yang redundan.
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
* **Aturan Tonggak Siklus Pengawasan Berkala**:
  * **Surveilen 1 (S1)**: Jendela notifikasi bulan ke-14; target pelaksanaan bulan ke-15; validasi asesmen pada rentang bulan 10 sampai dengan 24 dari tanggal sertifikat.
  * **Surveilen 2 (S2)**: Jendela notifikasi bulan ke-35; target pelaksanaan bulan ke-36; validasi asesmen pada rentang bulan 25 sampai dengan 44 dari tanggal sertifikat.
  * **Re-Akreditasi (RA)**: Jendela notifikasi 1 bulan sebelum habis; target pelaksanaan sebelum sertifikat kedaluwarsa (tahun ke-5).

---

### 3.3 Modul 3: Toleransi Pengisian & Siklus Hidup 3 Tahap Pengawasan
Sistem memberlakukan aturan siklus hidup bertingkat (*Three-Stage Surveillance Lifecycle*) untuk menegakkan disiplin akreditasi:
1. **Tahap 1: Toleransi Kunjungan Berjalan (Normal / In Progress)**:
   * Batas waktu pengisian dokumen asesmen surveilen dihitung sampai dengan **akhir bulan tanggal kunjungan** (`submission_due_date = end_at->endOfMonth()`).
   * *Contoh*: Asesmen kunjungan lapangan dilaksanakan pada 29 Maret 2027, maka batas akhir pengisian toleransi adalah 31 Maret 2027.
   * Selama tanggal saat ini belum melewati batas akhir bulan kunjungan, status asesmen tetap `PLANNED` atau `IN_PROGRESS`.
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

### 3.4 Modul 4: Pelacakan Tindakan Perbaikan (TP & VTP) Berbasis SLA KAN
* **Batas Waktu Awal SLA (Service Level Agreement Dasar)**:
  * **3 Bulan Kalender**: Khusus proses Akreditasi Awal (AA).
  * **2 Bulan Kalender**: Untuk seluruh proses asesmen lainnya (Surveilen 1, Surveilen 2, STT, PRL, dan Re-Akreditasi).
* **Ketentuan Perpanjangan Waktu (Extension SLA)**:
  * Durasi perpanjangan maksimal adalah **1 bulan kalender** (`tp_extension_months = 1`).
  * Syarat perpanjangan sangat ketat:
    1. Laboratorium harus sudah menunjukkan progres perbaikan nyata terhadap temuan ketidaksesuaian asesmen (contoh: dari 10 temuan ketidaksesuaian, telah diselesaikan 8 temuan, menyisakan 2 temuan yang memerlukan perpanjangan).
    2. Wajib menyertakan Nomor Surat Permohonan Perpanjangan Resmi dari LPK (`tp_extension_letter_no`).
  * **Larangan Perpanjangan**: Jika selama masa 2 atau 3 bulan awal laboratorium tidak melakukan perbaikan sama sekali (kosong / tanpa tindak lanjut), proses TIDAK BOLEH diperpanjang dan harus langsung dihentikan/status dibekukan.
  * Total durasi kumulatif maksimal dengan perpanjangan: AA menjadi 4 bulan, proses lainnya menjadi 3 bulan.
* **Otomasi Status Tindakan Perbaikan Tanpa Input Manual**:
  * Status TP tidak diinput secara manual pada form, melainkan dihitung otomatis oleh sistem (*read-only dynamic status*):
    * Selama tanggal dinyatakan memenuhi (`tp_satisfied_at`) belum diisi:
      - Jika belum melewati batas waktu SLA: status berbunyi **Sedang Berlangsung** (`IN_PROGRESS`).
      - Jika telah melewati batas waktu SLA (baik batas awal maupun perpanjangan): status otomatis berubah menjadi **Dibekukan** (`SUSPENDED`, badge ungu).
    * Jika tanggal dinyatakan memenuhi (`tp_satisfied_at`) telah terisi: status berubah menjadi **Memenuhi / Selesai** (`COMPLETED` / `SATISFIED`).
* **Pengingat Kalender Otomatis (Reminder Engine)**:
  * Pengingat TP Awal: 2 bulan untuk AA, 1 bulan untuk asesmen lainnya.
  * Pengingat Jatuh Tempo TP: Tepat pada tanggal batas akhir SLA.
  * Pengingat Penerbitan SK: 10 hari kalender setelah tanggal TP dinyatakan memenuhi (khusus S1, S2, dan STT).

---

### 3.5 Modul 5: Alur Evaluasi Hasil Asesmen (EHA) & Penerbitan SK KAN
* **Pencatatan Evaluasi Hasil Asesmen (EHA)**:
  * Tanggal Rencana EHA (`eha_scheduled_date`) dan Tanggal Realisasi EHA (`eha_completed_at`).
  * Catatan dan rekomendasi sidang panitia teknis / tim evaluasi (`eha_notes`).
* **Penerbitan SK Akreditasi KAN**:
  * Nomor SK resmi KAN (`sk_number`) dan Tanggal Terbit SK (`sk_issued_at`).
  * Perhitungan Otomatis Lead Time Terbit SK (`sk_lead_time_days`): Menghitung selisih hari kerja/kalender sejak pemenuhan tindakan perbaikan hingga SK resmi diterbitkan.
* **Verifikasi Publik & Portal LPK dengan QR Code**:
  * Endpoint publik dan halaman portal mandiri (`/portal`) yang menampilkan kode QR resmi untuk memverifikasi keaslian akreditasi LPK, ruang lingkup, dan status kepatuhan secara instan.

---

### 3.6 Modul 6: Kepatuhan Keuangan SBM & Billing PNBP SIMPONI
* **Pelaporan Biaya Perjalanan Dinas Asesor (Standar Biaya Masukan PMK)**:
  * Terintegrasi langsung pada tabel `assessment_expenses` terhubung ke `assessments`.
  * Komponen Biaya: Transportasi (Tiket/Tol/BBM), Uang Harian Asesor, Honorarium Asesor / Tenaga Ahli, dan Paket Data/Akomodasi.
  * Total biaya terakumulasi otomatis (`total_cost = transport + accommodation + daily_allowance + package_data`).
  * Alur Verifikasi SBM: `BELUM_DILAPORKAN` -> `MENUNGGU_VERIFIKASI` -> `TERVERIFIKASI` atau `PERLU_REVISI`.
  * Pencatatan verifikator resmi: `verified_by`, `verified_at`, dan catatan perbaikan bila ada ketidaksesuaian pagu SBM.
* **Penerbitan Billing PNBP (SIMPONI Kemenkeu)**:
  * Penerbitan Kode Billing SIMPONI 15 digit berawalan digit '8'.
  * Status pembayaran: `UNPAID` (Belum Bayar), `PAID` (Lunas), `EXPIRED` (Kedaluwarsa).
  * Konfirmasi pelunasan kas negara: Pencatatan Nomor Transaksi Penerimaan Negara (NTPN 16 digit), Nomor Transaksi Bank (NTB), dan kanal pembayaran.
* **Quality Gate Kesiapan Terbit Dokumen (Release Readiness Gate)**:
  * SK Akreditasi hanya dapat dirilis bila billing PNBP telah `PAID` dan laporan biaya asesor telah `TERVERIFIKASI` sesuai ketentuan audit BSN.

---

### 3.7 Modul 7: Penjadwalan & Kalender Interaktif Multi-Event
* **Dukungan 5 Tipe Event Kalender**:
  1. `assessment`: Kunjungan Asesmen Lapangan Resmi KAN (Warna Biru / Blue).
  2. `surveillance_reminder`: Pengingat Jadwal Kunjungan Surveilen LPK (Warna Kuning / Oranye).
  3. `tp_reminder`: Pengingat Batas Tindakan Perbaikan (Warna Indigo / Biru Muda).
  4. `tp_overdue`: Batas Waktu Terlampaui Tindakan Perbaikan (Warna Merah / Ungu).
  5. `sk_reminder`: Pengingat Target Penerbitan SK KAN (Warna Hijau / Emerald).
* **Navigasi Cepat**:
  * Dropdown pemilih langsung bulan dan tahun (*Direct Month & Year Selector*) dengan rentang tahun dinamis yang menjangkau tahun kedaluwarsa LPK terjauh.
  * Pembuatan agenda instan dengan mengklik kotak tanggal pada kalender.

---

### 3.8 Modul 8: Impor Massal Asesmen & LPK
* **Impor Data Asesmen Massal**:
  * Fitur unggah CSV untuk penjadwalan banyak asesmen sekaligus dengan pemetaan otomatis: nomor registrasi LPK, tipe asesmen KAN, nama asesor kepala, tanggal mulai, dan tanggal selesai.
* **Impor Data LPK (Smart Upsert)**:
  * Mendukung pemrosesan ribuan data LPK dengan template baku `.csv` dan `.xlsx`.
  * Penarikan berkas dari tautan publik Google Sheets secara langsung.

---

### 3.9 Modul 9: Live Feed Google Sheets & Laporan
* **Live CSV Feeds (`=IMPORTDATA`)**:
  * Endpoint publik terlindungi API key parameter rahasia:
    * `/feeds/expenses.csv?key=simasadi-live`: Rekapitulasi laporan biaya asesor.
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
       │                                  │                                   │
       │ (1 to N) Penugasan PIC           │ (1 to N) Pelapor / Verifikator    │ (1 to N) Pembuat Event
       ▼                                  ▼                                   ▼
+-----------------------+      +---------------------+             +--------------------+
|         LPKS          |      | ASSESSMENT_EXPENSES |             |  CALENDAR_EVENTS   |
| id (PK), no_reg,      |      | id (PK), total_cost,|             | id (PK), lpk_id,   |
| registration_number,  |      | transport, hotel,   |             | event_type,        |
| name, kan_schema,     |      | daily_allowance,    |             | title, start_at,   |
| scope, address, email,|      | status, verified_by |             | end_at, is_all_day |
| status, expired_at,   |      +---------------------+             +--------------------+
| pic_user_id (FK),     |                 ▲                                   ▲
| certificate_date      |                 │                                   │
+-----------------------+                 │ (1 to 1)                          │
       │                                  │                                   │
       ├──────────────────────────────────┼───────────────────────────────────┘
       │ (1 to N)                         │
       ▼                                  │
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
       ├─────────────────────────────────────────┐
       ▼ (1 to N)                                ▼ (1 to 1)
+-----------------------------------+     +-----------------------------------------------+
|       ACCREDITATION_BILLINGS      |     |           ACCREDITATION_SIGNATURES            |
| id (PK), accreditation_id (FK),   |     | id (PK), accreditation_id (FK), signer_name,  |
| billing_code (15 digit), status,  |     | cert_serial, signed_at, verify_hash (SHA-256) |
| amount, ntpn (16 digit), paid_at  |     +-----------------------------------------------+
+-----------------------------------+
```

---

## 5. Ringkasan Antarmuka & Indikator Kepatuhan

1. **Persistent Alert Banner Prioritas Tinggi**:
   * Posisi paling atas pada dashboard utama dan halaman rincian LPK.
   * Aktif otomatis jika ada pengawasan berstatus `SURVEILLANCE_DUE`, `SURVEILLANCE_OVERDUE`, atau batas SLA TP mendekati jatuh tempo. Banner tidak dapat ditutup sebelum ditindaklanjuti.
2. **Sistem Pewarnaan Badge Kepatuhan Kontras**:
   * `ACTIVE` / `COMPLETED`: Badge Hijau (`#dcfce7`, teks `#15803d`).
   * `SURVEILLANCE_DUE` / `IN_PROGRESS`: Badge Kuning (`#fef9c3`, teks `#854d0e`).
   * `SURVEILLANCE_OVERDUE` / `EXPIRED`: Badge Merah (`#fee2e2`, teks `#b91c1c`).
   * `SUSPENDED` (Dibekukan): Badge Ungu (`#f3e8ff`, teks `#6b21a8`) untuk membedakan secara tegas kondisi pembekuan sementara dari kedaluwarsa.
   * `REVOKED` (Dicabut): Badge Merah Gelap (`#450a0a`, latar `#fecaca`).
   * `INACTIVE`: Badge Abu-abu (`#f3f4f6`, teks `#4b5563`).
3. **Baris Tabel Interaktif (Clickable Rows)**:
   * Baris tabel pada modul LPK dan Asesmen dapat diklik langsung untuk membuka rincian tanpa harus mengarahkan kursor tepat pada tombol teks detail.

---

## 6. Kebutuhan Non-Fungsional (Non-Functional Requirements)

1. **Performa & Responsivitas**:
   * Waktu render halaman utama di bawah 200 ms pada lingkungan intranet instansi.
   * Implementasi Eager Loading (`with(['lpk', 'creator', 'expenses'])`) untuk meniadakan N+1 Query.
2. **Keamanan & Integritas Data**:
   * Proteksi token CSRF pada seluruh transaksi POST/PUT/DELETE.
   * Middleware otorisasi peran berbasis hak akses pengguna (`admin`, `pic`, `assessor`, `lpk`).
   * Pencegahan akses lintas data antar PIC pada tingkat kueri basis data.
3. **Kepatuhan Audit & Standar Regulasi**:
   * Formula SBM mengacu pada Peraturan Menteri Keuangan Standar Biaya Masukan.
   * Logika tahapan pengawasan dan penegakan SLA mengacu pada pedoman Komite Akreditasi Nasional (KAN U-01).
   * Validitas dokumen digital diverifikasi dengan enkripsi hash SHA-256 BSrE BSSN.
4. **Keandalan Uji Otomatis**:
   * Dilengkapi rangkaian pengujian otomatis (*Feature Tests*) dengan cakupan 119 pengujian dan 680 asersi yang lulus 100%.
