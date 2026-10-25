#!/bin/sh
set -e

cd /var/www/html

BERKAS_DB="${DB_DATABASE:-/var/www/data/database.sqlite}"

if [ -z "${APP_KEY:-}" ]; then
    echo "✗ APP_KEY belum diisi." >&2
    echo "  Isi di berkas .env pada host, lalu jalankan ulang container." >&2
    exit 1
fi

echo "→ Menyiapkan direktori penyimpanan…"
mkdir -p \
    "$(dirname "$BERKAS_DB")" \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/app/private \
    storage/logs \
    bootstrap/cache

rm -f bootstrap/cache/*.php

chown -R www-data:www-data "$(dirname "$BERKAS_DB")" storage bootstrap/cache

if [ ! -f "$BERKAS_DB" ]; then
    echo "→ Menyiapkan basis data baru…"
    if [ -f "database/database.sqlite" ] && [ -s "database/database.sqlite" ]; then
        echo "→ Menyalin basis data awal dari host…"
        cp database/database.sqlite "$BERKAS_DB"
    else
        touch "$BERKAS_DB"
    fi
    chown www-data:www-data "$BERKAS_DB"
fi

echo "→ Menemukan paket Laravel…"
php artisan package:discover --no-interaction

php artisan config:clear --no-interaction >/dev/null 2>&1 || true
php artisan view:clear --no-interaction >/dev/null 2>&1 || true

echo "→ Menjalankan migrasi database…"
php artisan migrate --force --no-interaction

JUMLAH_USER=$(php -r '
    try {
        $pdo = new PDO("sqlite:".getenv("DB_DATABASE"));
        echo (int) $pdo->query("select count(*) from users")->fetchColumn();
    } catch (Throwable $e) {
        echo 0;
    }
')

if [ "$JUMLAH_USER" -eq 0 ]; then
    echo "→ Menjalankan seeder awal…"
    php artisan db:seed --force --no-interaction
fi

php artisan storage:link --no-interaction >/dev/null 2>&1 || true

echo "✓ Aplikasi SIMASADI siap menerima permintaan."
exec "$@"
