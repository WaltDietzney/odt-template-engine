FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    zip \
    libzip-dev \
    && docker-php-ext-install zip \
    && apt-get install -y libxml2-dev libxslt-dev  libjpeg-dev libpng-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip \
        xml \
        xsl \
        gd \
        mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer-Dateien in den Container kopieren
#COPY composer.json /var/www/html
COPY php.ini /usr/local/etc/php/

# Composer Install ausführen
# RUN composer install

# Change your working directory here
COPY ./path-of-your-working-directory/ /var/www/html/

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8095", "-t", "/var/www/html"]
