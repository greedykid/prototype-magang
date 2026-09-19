# DOKUMEN MASTER PERANCANGAN SISTEM
# SIMASADI (Sistem Informasi dan Administrasi Akreditasi)

> **Badan Standardisasi Nasional (BSN) / Komite Akreditasi Nasional (KAN)**  
> **Status:** Prototype Magang Operasional & Validasi Alur Kerja  
> **Versi:** 1.0 (September 2026)  
> **Dokumen Terkait:** Tersedia dalam berkas modular di direktori [`docs/`](file:///c:/ngoding-ngoding/prototype-magang/prototype-magang/docs/)

---

## Daftar Isi Dokumen Perancangan

1. **[Bagian 1: Gambaran Umum Website](01-gambaran-umum.md)**
   * 1.1 Identitas Sistem
   * 1.2 Latar Belakang Masalah
   * 1.3 Tujuan & Manfaat Sistem
   * 1.4 Ruang Lingkup (In-Scope & Out-of-Scope)
   * 1.5 Profil Pengguna (Aktor Sistem)
   * 1.6 Arsitektur & Tumpukan Teknologi Singkat

2. **[Bagian 2: Tahap Perencanaan (Planning)](02-tahap-perencanaan.md)**
   * 2.1 Metodologi Pengembangan (Iterative Agile Prototyping)
   * 2.2 Rencana Garis Waktu Pengembangan (Timeline Fase 1 - 6)
   * 2.3 Analisis Kelayakan Sistem (Teknis, Operasional, Kebijakan)
   * 2.4 Manajemen Risiko & Rencana Mitigasi

3. **[Bagian 3: Tahap Analisis (Analysis)](03-tahap-analisis.md)**
   * 3.1 Analisis Masalah (PIECES Framework & Fishbone Diagram)
   * 3.2 Analisis Kebutuhan Fungsional (REQ-F-01 s/d REQ-F-24)
   * 3.3 Analisis Kebutuhan Non-Fungsional (REQ-NF-01 s/d REQ-NF-09)

4. **[Bagian 4: Tahap Perancangan (Design)](04-tahap-perancangan.md)**
   * 4.1 Struktur Navigasi (Sitemap & Hierarki Menu)
   * 4.2 Use Case Diagram (Aktor & Batasan Sistem)
   * 4.3 Activity Diagram (Autentikasi, Kalender Interaktif, Issue Tracking)
   * 4.4 Sequence Diagram (Filter Server-side, Follow-up Masalah)
   * 4.5 Rancangan Basis Data (Entity Relationship Diagram - ERD)
   * 4.6 Class Diagram (Model Eloquent, Controller, dan Relasi)
   * 4.7 Rancangan Antarmuka Pengguna (Wireframe Desktop & Mobile)

5. **[Bagian 5: Implementasi dan Pengujian (Implementation & Testing)](05-implementasi-dan-pengujian.md)**
   * 5.1 Implementasi Sistem (Teknologi, Struktur Folder, Fitur Kunci UI)
   * 5.2 Pengujian Sistem (Matriks Feature Test Otomatis PHPUnit & Uji Manual)
   * 5.3 Bukti Eksekusi Test Suite (12 Tests Passed, 42 Assertions)

---

## Ringkasan Eksekutif Sistem

SIMASADI dibangun untuk menjawab tantangan pengelolaan administratif akreditasi Lembaga Penilaian Kesesuaian (LPK) di lingkungan KAN-BSN. Melalui implementasi arsitektur modern berbasis Laravel 13 dan sistem desain antarmuka responsif tanpa framework CSS yang memberatkan, sistem ini berhasil mewujudkan:
* **Monitoring Terpusat:** Akses satu pintu untuk data LPK, siklus akreditasi, dan jadwal asesmen.
* **Efisiensi Alur Lapangan:** Penjadwalan langsung dari grid kalender dan pemantauan kendala dengan log tindak lanjut berantai.
* **Antarmuka Adaptif & Ramah Sentuh:** Tampilan data fleksibel (mode Tabel padat untuk desktop dan mode Grid Card 2-kolom terstruktur untuk tablet/mobile), drawer filter samping yang nyaman, dan tombol aksi yang jelas.
* **Kualitas Teruji:** Diverifikasi dengan 12 skenario pengujian otomatis end-to-end dengan tingkat keberhasilan 100%.

Seluruh dokumen perancangan di atas dapat dibuka dan ditelaah secara mendalam melalui tautan masing-masing berkas di atas.
