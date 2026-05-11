FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libxml2-dev libzip-dev \
    libicu-dev libldap2-dev libcurl4-openssl-dev \
    libonig-dev libxslt-dev unzip git cron \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions for Moodle
# dom and xmlreader must be compiled together in PHP 8.3
RUN docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd intl mysqli pdo_mysql soap zip \
        opcache ldap mbstring dom xml xmlreader xsl exif

# PHP config tuning for Moodle
RUN echo "max_input_vars = 5000" >> /usr/local/etc/php/php.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/php.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/php.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/php.ini

# Copy your fork's code
COPY . /var/www/html/

# Moodledata directory (outside webroot)
RUN mkdir -p /var/moodledata \
    && chown -R www-data:www-data /var/www/html /var/moodledata \
    && chmod -R 755 /var/www/html \
    && chmod -R 770 /var/moodledata

EXPOSE 80
