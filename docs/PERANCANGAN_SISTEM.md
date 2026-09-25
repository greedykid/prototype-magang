# DOKUMEN MASTER PERANCANGAN SISTEM
# SIMASADI (Sistem Informasi Manajemen Akreditasi & Surveilen LPK)

> **Badan Standardisasi Nasional (BSN) / Komite Akreditasi Nasional (KAN)**  
> **Status:** Prototype Magang Operasional Terverifikasi Penuh  
> **Versi:** 2.0 (September 2026)  
> **Dokumen Terkait:** Tersedia dalam berkas modular di direktori [`docs/`](docs/)

---

## Daftar Isi Dokumen Perancangan

1. **[Bagian 1: Gambaran Umum Website](01-gambaran-umum.md)**
   * 1.1 Identitas Sistem
   * 1.2 Latar Belakang Masalah
   * 1.3 Tujuan & Manfaat Sistem
   * 1.4 Ruang Lingkup (*In-Scope* & *Out-of-Scope*)
   * 1.5 Profil Pengguna (4 Aktor Sistem: Admin, PIC, Assessor, LPK)
   * 1.6 Arsitektur & Tumpukan Teknologi Singkat

2. **[Bagian 2: Tahap Perencanaan (Planning)](02-tahap-perencanaan.md)**
   * 2.1 Metodologi Pengembangan (Iterative Agile Prototyping Berbasis Regulasi)
   * 2.2 Rencana Garis Waktu Pengembangan (Timeline Fase 1 sampai 6)
   * 2.3 Analisis Kelayakan Sistem (Teknis, Operasional, Regulasi KAN U-01)
   * 2.4 Manajemen Risiko & Rencana Mitigasi

3. **[Bagian 3: Tahap Analisis (Analysis)](03-tahap-analisis.md)**
   * 3.1 Analisis Masalah (PIECES Framework & Fishbone Diagram)
   * 3.2 Analisis Kebutuhan Fungsional (`REQ-F-01` s/d `REQ-F-32`)
   * 3.3 Analisis Kebutuhan Non-Fungsional (`REQ-NF-01` s/d `REQ-NF-10`)

4. **[Bagian 4: Tahap Perancangan (Design)](04-tahap-perancangan.md)**
   * 4.1 Struktur Navigasi (Sitemap Utama, Sub-Halaman, & Portal Khusus)
   * 4.2 Use Case Diagram (4 Aktor & Batasan Sistem)
   * 4.3 Activity Diagram (Toleransi 3 Tahap Surveilen & Validasi SLA TP)
   * 4.4 Sequence Diagram (Realisasi Billing SIMPONI & Quality Gate SK)
   * 4.5 Rancangan Basis Data (Entity Relationship Diagram: ERD 9 Tabel)
   * 4.6 Class Diagram (Model Eloquent, Accessor Bisnis, dan Controller)
   * 4.7 Rancangan Antarmuka Pengguna (Wireframe Desktop & Mobile)

5. **[Bagian 5: Implementasi dan Pengujian (Implementation & Testing)](05-implementasi-dan-pengujian.md)**
   * 5.1 Implementasi Sistem (Teknologi, Struktur Folder, Fitur Kunci UI)
   * 5.2 Pengujian Sistem (Matriks Feature Test Otomatis PHPUnit & Uji Manual)
   * 5.3 Bukti Eksekusi Test Suite (119 Tests Passed, 680 Assertions, 100% Pass Rate)

---

## Ringkasan Eksekutif Sistem

SIMASADI dibangun untuk menjawab tantangan pengelolaan administratif akreditasi Lembaga Penilaian Kesesuaian (LPK) di lingkungan Komite Akreditasi Nasional (KAN) dan Badan Standardisasi Nasional (BSN). Melalui arsitektur modern berbasis Laravel dan sistem desain antarmuka responsif tanpa framework CSS yang memberatkan, sistem ini mewujudkan:

* **Penerapan 8 Tipe Proses Asesmen KAN (KAN U-01):** Standarisasi alur untuk Akreditasi Awal, Surveilen 1, Surveilen 1 + PRL, Surveilen 2, Surveilen 2 + PRL, Surveilen Tidak Terjadwal, Perluasan Ruang Lingkup, dan Re-Akreditasi.
* **Penegakan Toleransi Surveilen 3 Tahap:** Masa toleransi kunjungan (akhir bulan kunjungan), pembekuan otomatis bertahap (`SUSPENDED`, badge ungu kontras) dengan jendela penyelesaian 1 tahun disertai countdown, pencabutan akreditasi (`REVOKED`) jika batas 1 tahun habis, serta auto-realisasi data lampau bagi LPK yang berstatus aktif saat ini.
* **Mesin Penegakan SLA Tindakan Perbaikan (TP & VTP):** Menghitung otomatis batas SLA dasar (3 bulan AA, 2 bulan lainnya), mengunci perpanjangan maksimal 1 bulan bersurat resmi hanya bagi LPK yang telah menunjukkan progres perbaikan nyata, serta menampilkan status otomatis tanpa beban input manual.
* **Alur Evaluasi Hasil Asesmen & SK KAN:** Pencatatan sidang EHA, nomor SK, tanggal terbit SK, perhitungan otomatis lead time penerbitan SK, serta portal publik/LPK ber-QR Code untuk verifikasi keabsahan secara instan.
* **Kepatuhan Finansial Penuh:** Pelaporan dan verifikasi biaya perjalanan dinas asesor berbasis SBM PMK Kemenkeu, penerbitan billing PNBP SIMPONI 15 digit ber-NTPN sah, serta Quality Gate kesiapan rilis dokumen akreditasi.
* **Impor Massal & Integrasi Terbuka:** Impor cerdas data LPK dan Asesmen (CSV/XLSX/Google Sheets) serta live feeds CSV untuk formula `=IMPORTDATA` Google Sheets secara real-time.
* **Kualitas Teruji Menyeluruh:** Diverifikasi dengan 119 skenario pengujian otomatis (*feature tests*) dengan tingkat keberhasilan 100% (680 assertions).

Seluruh bab perancangan dapat diakses secara mendalam melalui tautan berkas di atas.
