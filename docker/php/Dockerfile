FROM php:5.6-apache

# Debian 9 (stretch) esta archivado. Tres consecuencias:
#  - los repos viven en archive.debian.org y stretch-updates ya no existe;
#  - los indices tienen fecha caducada  -> Check-Valid-Until=false;
#  - las claves de firma expiraron en 2022 -> [trusted=yes], que desactiva la
#    verificacion GPG. Es un entorno local y desechable; no replicar en produccion.
RUN set -eux; \
    printf '%s\n' \
        'deb [trusted=yes] http://archive.debian.org/debian stretch main' \
        'deb [trusted=yes] http://archive.debian.org/debian-security stretch/updates main' \
        > /etc/apt/sources.list; \
    apt-get update -o Acquire::Check-Valid-Until=false; \
    apt-get install -y --no-install-recommends libpq-dev; \
    docker-php-ext-install pdo pdo_pgsql pgsql mbstring; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

# El contexto de build es ./docker (ver docker-compose.yml), no ./docker/php.
COPY php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY apache/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
