#!/usr/bin/env bash
set -e

# Port dinamis (Render/Back4App/Railway memberi $PORT). Default: 80.
PORT="${PORT:-80}"
echo "[TIXORA] Menyiapkan Apache untuk mendengarkan port ${PORT}..."
sed -ri -e "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri -e "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/*.conf

# Kunci QR harus tetap sama antar-restart, kalau tidak QR tiket lama jadi tidak valid.
# Production: wajib set QR secret & public URL, refuse start jika kosong
if [ "${APP_ENV}" = "production" ]; then
    if [ -z "${TIXORA_QR_SECRET}" ]; then
        echo "[FATAL] APP_ENV=production tapi TIXORA_QR_SECRET belum diset."
        echo "[FATAL] Set Environment Variable TIXORA_QR_SECRET (min 32 karakter acak)."
        exit 1
    fi
    if [ "${#TIXORA_QR_SECRET}" -lt 32 ]; then
        echo "[FATAL] TIXORA_QR_SECRET terlalu pendek (${#TIXORA_QR_SECRET} karakter, butuh >= 32)."
        exit 1
    fi
    if [ -z "${TIXORA_PUBLIC_URL}" ]; then
        echo "[FATAL] APP_ENV=production tapi TIXORA_PUBLIC_URL belum diset."
        echo "[FATAL] Set Environment Variable TIXORA_PUBLIC_URL (mis. https://tixora.example.id)."
        exit 1
    fi
elif [ -z "${TIXORA_QR_SECRET}" ]; then
    echo "[WARN] TIXORA_QR_SECRET belum diisi. Isi dengan string acak >= 32 karakter di Environment Variables."
fi

# Migrasi otomatis: aktif secara default, matikan dengan AUTO_MIGRATE=false.
AUTO_MIGRATE="${AUTO_MIGRATE:-true}"
if [ "${AUTO_MIGRATE}" = "true" ] || [ "${AUTO_MIGRATE}" = "1" ]; then
    if [ -n "${DATABASE_URL}" ] || [ -n "${DB_HOST}" ]; then
        echo "[TIXORA] Menjalankan migrasi database..."
        php /var/www/html/bin/migrate.php || echo "[WARN] Migrasi gagal/selesai dengan catatan. Periksa log di atas."
    else
        echo "[WARN] AUTO_MIGRATE aktif tetapi DATABASE_URL / DB_HOST belum diisi. Migrasi dilewati."
    fi
fi

# Folder upload harus ada dan dapat ditulis web server.
mkdir -p /var/www/html/api/public/uploads
chown -R www-data:www-data /var/www/html/api/public/uploads || true

echo "[TIXORA] Menjalankan web server pada port ${PORT}..."

# Nonaktifkan MPM ganda yang disuntikkan oleh Railway
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

exec apache2-foreground
