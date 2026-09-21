FROM php:8.3-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libwebp-dev libonig-dev libcurl4-openssl-dev && docker-php-ext-configure gd --with-jpeg --with-webp && docker-php-ext-install pdo_mysql gd mbstring curl && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www
COPY . /var/www
RUN mkdir -p storage public/uploads && chown -R www-data:www-data storage public/uploads
COPY infra/php.ini /usr/local/etc/php/conf.d/portal.ini
CMD ["sh", "infra/start.sh"]
