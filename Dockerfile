# Basis-Image von PHP mit Apache
FROM php:8.3-apache

# Installiere benötigte PHP-Erweiterungen
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
    && echo memory_limit=32M >> /usr/local/etc/php/conf.d/docker-php.ini \
    && echo "error_reporting=E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/docker-php.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/docker-php.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/docker-php.ini
COPY template/ /var/template_init/
COPY composer.json composer.lock /var/composer/
RUN cd /var/composer && composer install --no-dev --optimize-autoloader \
    && cp -r ./vendor/tinymce/* /var/www/html/
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

COPY src/ /var/www/html/

RUN mkdir /var/db \
    && mkdir /var/template \
    && ln -s /var/template /var/www/html/template_files \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/db \
    && chown -R www-data:www-data /var/template

USER www-data

ENTRYPOINT ["/entrypoint.sh"]