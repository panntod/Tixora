#!/usr/bin/env bash
set -e

# Render menyediakan environment variable PORT (default dinamis dari platform, misal 10000)
PORT="${PORT:-80}"

echo "[TIXORA] Menyiapkan Apache untuk mendengarkan port ${PORT}..."
sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Jalankan migrasi database otomatis jika AUTO_MIGRATE aktif atau variabel koneksi tersedia
if [ "${AUTO_MIGRATE}" = "true" ] || [ "${AUTO_MIGRATE}" = "1" ] || [ -n "${DATABASE_URL}" ] || [ -n "${DB_HOST}" ]; then
    echo "[TIXORA] Menjalankan migrasi database..."
    php /var/www/html/bin/migrate.php || echo "[WARN] Migrasi database selesai dengan catatan."
fi

# Pastikan direktori uploads tersedia dan dapat ditulis oleh web server
mkdir -p /var/www/html/api/public/uploads
chown -R www-data:www-data /var/www/html/api/public/uploads || true

echo "[TIXORA] Menjalankan web server pada port ${PORT}..."
exec apache2-foreground
