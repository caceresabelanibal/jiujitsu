FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Límites de subida (fotos de competidores): sube upload_max_filesize/post_max_size
COPY docker/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

# Apache: docroot en /public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/app.conf \
    && a2enconf app

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-dev --no-interaction --no-progress || true

COPY . .
RUN mkdir -p storage/certificates public/uploads && chown -R www-data:www-data storage public/uploads

# Entrypoint: espera la DB, crea el admin inicial (idempotente) y arranca Apache.
# Se copia fuera de /var/www/html para que el bind mount del código no lo tape.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
