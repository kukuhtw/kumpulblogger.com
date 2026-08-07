FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        default-mysql-client \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" curl gd mbstring mysqli pdo_mysql zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html \
    PORT=8080

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/myadnetwork.ini
COPY docker/entrypoint.sh /usr/local/bin/myadnetwork-entrypoint
COPY docker/db-prepare.sh /usr/local/bin/myadnetwork-db-prepare
COPY docker/cron-loop.sh /usr/local/bin/myadnetwork-cron-loop
COPY . /var/www/html

RUN chmod +x /usr/local/bin/myadnetwork-entrypoint /usr/local/bin/myadnetwork-db-prepare /usr/local/bin/myadnetwork-cron-loop \
    && mkdir -p \
        /var/www/html/public_html/uploads \
        /var/www/html/public_html/ai_images \
        /var/www/html/public_html/banner_mini \
        /var/www/html/public_html/voice \
        /var/www/html/public_html/JSON \
        /var/www/html/public_html/logs \
    && chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html
EXPOSE 8080

ENTRYPOINT ["myadnetwork-entrypoint"]
CMD ["apache2-foreground"]
