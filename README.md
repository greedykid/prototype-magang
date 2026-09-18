# SIMASADI

Prototype workspace untuk monitoring internal LPK, akreditasi, asesmen, masalah, backup, amandemen, dan agenda kerja.

SIMASADI dibuat sebagai aplikasi Laravel lokal untuk memvalidasi alur kerja dan tampilan operasional sebelum terhubung ke sistem resmi.

## Fitur

- Dashboard ringkasan monitoring.
- Manajemen data LPK.
- Program asesmen dan proses akreditasi.
- Pelaporan masalah beserta follow-up.
- Monitoring layanan KANMIS dan histori backup.
- Pengajuan amandemen.
- Kalender agenda kerja bulanan.
- Klik langsung pada kotak tanggal kalender untuk membuat agenda dengan tanggal otomatis terisi.
- Filter server-side pada tabel utama dengan pagination.
- Login lokal untuk akses prototype.

## Teknologi

- PHP 8.3 atau lebih baru.
- Laravel 13.
- SQLite sebagai database lokal.
- Blade untuk templating.
- Vite untuk asset frontend.
- Instrument Sans untuk typography antarmuka.

## Persyaratan

- PHP 8.3+ dengan extension SQLite.
- Composer.
- Node.js dan npm.

## Instalasi

Clone repository lalu masuk ke direktori aplikasi:

```bash
git clone https://github.com/greedykid/prototype-magang.git
cd prototype-magang
```

Install dependency PHP dan JavaScript:

```bash
composer install
npm install
```

Siapkan environment dan database:

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

Jika project sudah pernah disiapkan, gunakan `php artisan migrate` tanpa `--seed` untuk menjalankan migration baru saja.

## Menjalankan Aplikasi

Jalankan server Laravel:

```bash
php artisan serve
```

Untuk development frontend dengan Vite:

```bash
npm run dev
```

Atau build asset produksi:

```bash
npm run build
```

Buka alamat yang ditampilkan oleh Laravel, biasanya `http://127.0.0.1:8000`.

## Akun Prototype

Seeder menyediakan akun lokal untuk login. Periksa `database/seeders/DatabaseSeeder.php` untuk kredensial yang tersedia pada environment Anda.

Data pada aplikasi ini adalah data contoh lokal dan belum terhubung ke sistem resmi.

## Kalender

Pada halaman **Kalender**, klik tanggal yang diinginkan untuk membuka form **Tambah agenda**. Tanggal mulai dan tanggal selesai akan otomatis mengikuti tanggal yang dipilih, sedangkan jam mulai dan jam selesai tetap harus diisi manual.

Agenda yang sudah tersimpan dapat dibuka dari kalender untuk melihat detail atau mengubah data.

## Pengujian

Jalankan seluruh feature test dengan:

```bash
php artisan test --compact
```

Format kode PHP dengan:

```bash
vendor/bin/pint --format agent
```

## Struktur Penting

- `app/Http/Controllers`: controller aplikasi.
- `app/Models`: model Eloquent.
- `database/migrations`: struktur database.
- `database/seeders`: data awal prototype.
- `resources/views`: halaman Blade.
- `resources/css/app.css`: styling antarmuka.
- `tests/Feature`: pengujian alur aplikasi.
- `resources/css/app.css`: arah visual dan keputusan desain antarmuka.

## Status Project

Project ini adalah prototype magang untuk eksplorasi alur dan antarmuka. Fitur keamanan, integrasi sistem eksternal, deployment produksi, dan manajemen data resmi belum menjadi bagian dari scope prototype ini.

## Lisensi

Prototype ini menggunakan lisensi MIT.
