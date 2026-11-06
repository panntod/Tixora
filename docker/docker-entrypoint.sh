#!/usr/bin/env bash
set -e

# Port dinamis (didukung oleh Back4App, Render, Railway, dll. Default: 80)
PORT="${PORT:-80}"

echo "[TIXORA] Menyiapkan Apache untuk mendengarkan port ${PORT}..."
sed -ri -e "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri -e "s/<VirtualHost \*:[0-9]+>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/*.conf

# Jalankan migrasi database otomatis jika AUTO_MIGRATE aktif dan koneksi database dikonfigurasi
if { [ "${AUTO_MIGRATE}" = "true" ] || [ "${AUTO_MIGRATE}" = "1" ]; } && { [ -n "${DATABASE_URL}" ] || [ -n "${DB_HOST}" ]; }; then
    echo "[TIXORA] Menjalankan migrasi database..."
    php /var/www/html/bin/migrate.php || echo "[WARN] Migrasi database selesai dengan catatan."
elif [ "${AUTO_MIGRATE}" = "true" ] || [ "${AUTO_MIGRATE}" = "1" ]; then
    echo "[WARN] AUTO_MIGRATE aktif namun DATABASE_URL / DB_HOST belum diisi. Melewati migrasi otomatis."
fi

# Pastikan direktori uploads tersedia dan dapat ditulis oleh web server
mkdir -p /var/www/html/api/public/uploads
chown -R www-data:www-data /var/www/html/api/public/uploads || true

echo "[TIXORA] Menjalankan web server pada port ${PORT}..."
exec apache2-foreground
