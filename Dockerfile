FROM php:8.2-apache

# Install dependencies sistem, sertifikat CA untuk SSL (Neon DB), dan ekstensi PostgreSQL
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

# Aktifkan modul Apache rewrite dan headers
RUN a2enmod rewrite headers

# Konfigurasi VirtualHost dan PHP
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/tixora.ini

# Set direktori kerja
WORKDIR /var/www/html

# Salin source code project
COPY . /var/www/html

# Berikan hak akses www-data
RUN chown -R www-data:www-data /var/www/html

# Port default (dapat diubah dinamis oleh Render melalui variabel $PORT)
EXPOSE 80