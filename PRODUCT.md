# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users
- Admin Unit Akreditasi Lab (admin): Mengelola penjadwalan, validasi berkas LPK, konfirmasi usulan tim asesmen, monitoring SLA tindakan perbaikan, dan pengesahan sertifikat.
- PIC Laboratorium (pic): Mengisi form usulan asesmen, mengunggah bukti tindakan perbaikan (TP) temuan ketidaksesuaian, memonitor tenggat waktu / masa toleransi surveilen.
- Asesor KAN (assessor): Melakukan tinjauan dokumen dan asesmen lapangan, mencatat temuan ketidaksesuaian (K/TB), memvalidasi tindakan perbaikan lab.
- LPK (Lembaga Penilaian Kesesuaian): Entitas laboratorium terakreditasi KAN yang dipantau status akreditasinya (Aktif, Dibekukan, Dicabut).

## Product Purpose
Sistem Informasi Manajemen Asesmen & Akreditasi Laboratorium Terpadu (SIMASADI) mengotomatisasi dan mendokumentasikan seluruh siklus akreditasi laboratorium sesuai standar KAN (Komite Akreditasi Nasional) U-01 dan ISO/IEC 17025. Sistem memastikan kepatuhan regulasi waktu (SLA), siklus toleransi surveilen, serta transparansi pelaporan dan verifikasi publik.

## Positioning
Satu-satunya sistem manajemen akreditasi laboratorium yang mengintegrasikan otomasi aturan KAN U-01 secara deterministik: penegakan SLA tindakan perbaikan (3 bulan untuk Asesmen Awal, 2 bulan untuk jenis asesmen lainnya, plus perpanjangan bersyarat 1 bulan jika ada progres riil), mesin status toleransi surveilen 3 tahap (Bulan Kunjungan -> Pembekuan 1 Tahun dengan hitung mundur -> Pencabutan Akreditasi), perhitungan biaya PNBP berbasis SBM PMK, integrasi billing SIMPONI, dan verifikasi sertifikat publik via QR code berstandar BSrE.

## Operating Context
- Digunakan dalam operasional berkala dan terjadwal oleh sekretariat KAN, asesor, dan personil laboratorium di seluruh Indonesia.
- Menangani 8 jenis asesmen resmi KAN: Asesmen Awal, Survailen 1, Survailen 2, Re-Akreditasi, Perluasan Ruang Lingkup, Penambahan Asesor/Penyaksian Asesmen, Survailen Tidak Terjadwal, dan Asesmen Tidak Terjadwal.
- Regulasi acuan: KAN U-01, ISO/IEC 17025, SBM Kemenkeu PMK, Peraturan Pemerintah PNBP.
- Lingkungan teknis: Web application (Laravel, Blade, MySQL/SQLite, Tailwind/CSS modern).

## Capabilities and Constraints
- Penegakan SLA Tindakan Perbaikan:
  * Asesmen Awal (AA): SLA default 3 bulan.
  * 7 jenis asesmen lainnya: SLA default 2 bulan.
  * Perpanjangan (+1 bulan) hanya dapat diajukan jika laboratorium telah menunjukkan progres perbaikan nyata (misalnya sebagian temuan sudah terverifikasi memenuhi). Jika laboratorium nihil perbaikan sama sekali dalam batas waktu awal, perpanjangan ditolak dan proses dihentikan.
  * Status Tindakan Perbaikan bersifat otomatis dan readonly: "Direncanakan" (sebelum tanggal mulai / belum lewat batas waktu), "Sedang Berlangsung" (dalam proses evaluasi / belum selesai diverifikasi), atau "Selesai".
- Siklus Toleransi Surveilen & Status Akreditasi LPK:
  * Tanggal Maksimal Toleransi jatuh pada akhir bulan pelaksanaan surveilen.
  * Jika lewat batas toleransi tanpa realisasi, status LPK otomatis beralih menjadi "Dibekukan".
  * LPK dibekukan diberikan masa tenggang toleransi pembekuan 1 tahun dengan countdown timer.
  * Jika dalam 1 tahun surveilen tidak diselesaikan, status akreditasi LPK resmi "Dicabut".
  * Jika status akreditasi LPK tercatat Aktif, seluruh surveilen/tindakan perbaikan periode sebelumnya dianggap otomatis telah terealisasi secara konsisten.
- Billing & Keuangan:
  * Generate tagihan billing SIMPONI otomatis sesuai kalkulasi tarif SBM PMK (honor asesor, transport riil/lumpsum, akomodasi, biaya verifikasi).
- Sertifikasi:
  * Penerbitan e-Sertifikat dengan TTE BSrE dan tautan portal verifikasi publik QR Code.

## Brand Commitments
- Nama resmi: SIMASADI (Sistem Informasi Manajemen Asesmen & Akreditasi Terpadu).
- Nuansa profesional, andal, berstandar kepatuhan tinggi instansi pemerintah (BSN / KAN).
- Perbedaan visual yang tegas pada badge status risiko (badge Dibekukan tidak menggunakan warna merah yang sama dengan status kritis/gagal/dicabut, melainkan oranye/amber khusus penangguhan).

## Evidence on Hand
- PRD lengkap: prd.md
- Ringkasan Arsitektur: RINGKASAN_ARSITEKTUR_SIMASADI.docx
- Rencana implementasi dan alur kerja di doc/
- Rangkaian pengujian otomatis: 120 unit/feature test cases lulus (100% pass) memverifikasi kalkulasi SLA, transisi status pembekuan, perpanjangan bersyarat, dan kalkulator SBM.

## Product Principles
- Deterministic Compliance: Aturan KAN U-01 dan SLA ditegakkan oleh sistem tanpa ambiguitas atau bypass manual yang tidak terdokumentasi.
- Integrity First: Status laboratorium dan tindakan perbaikan mencerminkan realitas faktual; tidak ada status aktif palsu jika kewajiban surveilen diabaikan.
- Clarity of Action: Pengguna (LPK maupun asesor) selalu tahu persis sisa waktu (countdown), konsekuensi hukum/akreditasi, dan prasyarat tindakan berikutnya.
- Accessible & Resilient: Antarmuka yang efisien untuk beban kerja tinggi, cepat dipahami saat asesmen lapangan, serta ramah perangkat kerja administrasi.

## Accessibility & Inclusion
- Standar aksesibilitas Web Content Accessibility Guidelines (WCAG 2.1 AA).
- Kontras warna yang jelas untuk indikator status, label, badge, dan countdown peringatan.
- Navigasi keyboard yang konsisten untuk entri data tabel temuan asesmen dan formulir perbaikan.
