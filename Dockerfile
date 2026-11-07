FROM php:8.2-apache

# Pastikan hanya satu MPM aktif: mpm_prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_event.* \
    /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork \
    && apache2ctl -M | grep mpm_prefork

# Install dependencies dan ekstensi PostgreSQL
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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Aktifkan modul Apache
RUN a2enmod rewrite headers

# Konfigurasi Apache dan PHP
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/tixora.ini

WORKDIR /var/www/html

COPY . /var/www/html

EXPOSE 80