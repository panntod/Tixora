FROM php:8.2-apache

# Dependensi sistem, sertifikat CA (SSL ke Neon), dan ekstensi PostgreSQL
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan modul rewrite dan headers, lalu HAPUS PAKSA MPM ganda
RUN a2dismod mpm_event mpm_worker || true \
    && rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite headers
    
# Konfigurasi VirtualHost dan PHP
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/tixora.ini

# Entrypoint disalin ke luar /var/www/html (aman dari volume mount) dan
# CRLF (Windows) diubah ke LF supaya skrip bisa jalan di Linux.
COPY docker/docker-entrypoint.sh /usr/local/bin/tixora-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/tixora-entrypoint.sh \
    && chmod +x /usr/local/bin/tixora-entrypoint.sh

WORKDIR /var/www/html

# Salin source code project
COPY . /var/www/html

# Folder upload + hak akses www-data
RUN mkdir -p /var/www/html/api/public/uploads \
    && chown -R www-data:www-data /var/www/html

# Port default; Render/Back4App dapat menimpa lewat variabel $PORT
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/tixora-entrypoint.sh"]

CMD ["apache2-foreground"]
