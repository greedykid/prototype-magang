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
   * 3.2 Analisis Kebutuhan Fungsional (`REQ-F-01` s/d `REQ-F-28`)
   * 3.3 Analisis Kebutuhan Non-Fungsional (`REQ-NF-01` s/d `REQ-NF-10`)

4. **[Bagian 4: Tahap Perancangan (Design)](04-tahap-perancangan.md)**
   * 4.1 Struktur Navigasi (Sitemap Utama, Sub-Halaman, & Portal Khusus)
   * 4.2 Use Case Diagram (4 Aktor & Batasan Sistem)
   * 4.3 Activity Diagram (Toleransi 3 Tahap Surveilen & Validasi SLA TP)
   * 4.4 Sequence Diagram (Verifikasi Alur EHA & Quality Gate SK)
   * 4.5 Rancangan Basis Data (ERD 6 Tabel & Kamus Data Struktur Kolom)
   * 4.6 Class Diagram (Model Eloquent, Accessor Bisnis, dan Controller)
   * 4.7 Rancangan Antarmuka Pengguna (Wireframe Desktop & Mobile)

5. **[Bagian 5: Implementasi dan Pengujian (Implementation & Testing)](05-implementasi-dan-pengujian.md)**
   * 5.1 Implementasi Sistem (Teknologi, Struktur Folder, Fitur Kunci UI)
   * 5.2 Pengujian Sistem (Matriks Feature Test Otomatis PHPUnit & Uji Manual)
   * 5.3 Bukti Eksekusi Test Suite (183 Tests Passed, 1102 Assertions, 100% Pass Rate)

6. **Berkas Dokumen Pendukung & Referensi:**
   * 📑 **[Ringkasan Arsitektur Sistem (.docx)](RINGKASAN_ARSITEKTUR_SIMASADI.docx)**: Dokumen deskripsi teknis arsitektur aplikasi SIMASADI.
   * 📁 **[Direktori Dokumen Referensi & Presentasi](references/)**: Brosur pengujian laboratorium KAN dan lembar ringkas presentasi (*cheat sheet*).

---

## Ringkasan Eksekutif Sistem

SIMASADI dibangun untuk menjawab tantangan pengelolaan administratif akreditasi Lembaga Penilaian Kesesuaian (LPK) di lingkungan Komite Akreditasi Nasional (KAN) dan Badan Standardisasi Nasional (BSN). Melalui arsitektur modern berbasis Laravel dan sistem desain antarmuka responsif tanpa framework CSS yang memberatkan, sistem ini mewujudkan:

* **Penerapan 8 Tipe Proses Asesmen KAN (KAN U-01):** Standarisasi alur untuk Akreditasi Awal, Surveilen 1, Surveilen 1 + PRL, Surveilen 2, Surveilen 2 + PRL, Surveilen Tidak Terjadwal, Perluasan Ruang Lingkup, dan Re-Akreditasi.
* **Penegakan Toleransi Surveilen 3 Tahap:** Masa toleransi pengisian dokumen (maksimal 4 bulan dari bulan ke-15 siklus akreditasi atau tanggal kunjungan dengan fleksibilitas input manual tersimpan), pembekuan otomatis bertahap (`SUSPENDED`, badge ungu kontras) dengan jendela penyelesaian 1 tahun disertai countdown, pencabutan akreditasi (`REVOKED`) jika batas 1 tahun habis, serta auto-realisasi data lampau bagi LPK yang berstatus aktif saat ini.
* **Mesin Penegakan Batas Waktu Tindakan Perbaikan (TP & VTP):** Menghitung otomatis batas waktu dasar (3 bulan AA, 2 bulan lainnya), mengunci perpanjangan maksimal 1 bulan bersurat resmi (otomatis memperpanjang +1 bulan saat nomor surat resmi diinput), serta menampilkan status otomatis tanpa beban input manual.
* **Sistem Kolaborasi Tim & Tautan Akun Multi-PIC:** Fleksibilitas penautan akun pendampingan laboratorium di mana Lead PIC dapat menautkan PIC lain sebagai Viewer read-only untuk membagi beban pengawasan secara transparan.
* **Sistem Desain Mode Gelap & Terang Adaptif:** Antarmuka ergonomis dengan peralihan instan 1-klik, persistensi preferensi di browser, bebas kedipan layar putih, dan kontras tinggi sesuai standar aksesibilitas WCAG 2.1 AA.
* **Peringatan Pengawasan Persisten Wajib:** Banner peringatan jatuh tempo siklus pengawasan KAN yang tampil permanen dan tidak dapat disembunyikan sementara, menjamin kepatuhan tindak lanjut regulasi.
* **Alur Evaluasi Hasil Asesmen & SK KAN:** Pencatatan sidang EHA, nomor SK, tanggal terbit SK, perhitungan otomatis lead time penerbitan SK, serta Quality Gate kesiapan rilis dokumen.
* **Impor Massal & Integrasi Terbuka:** Impor cerdas data LPK dan Asesmen (CSV/XLSX/Google Sheets) serta live feeds CSV untuk formula `=IMPORTDATA` Google Sheets secara real-time.
* **Kualitas Teruji Menyeluruh:** Diverifikasi dengan 183 skenario pengujian otomatis (*feature tests*) dengan tingkat keberhasilan 100% (1102 assertions).

Seluruh bab perancangan dapat diakses secara mendalam melalui tautan berkas di atas.
