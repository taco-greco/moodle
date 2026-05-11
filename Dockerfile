FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libxml2-dev libzip-dev \
    libicu-dev libldap2-dev libcurl4-openssl-dev \
    libonig-dev libxslt-dev unzip git cron \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions for Moodle
RUN docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
        gd intl mysqli pdo_mysql soap zip \
        opcache ldap mbstring dom xml xsl exif

# PHP config tuning for Moodle
RUN echo "max_input_vars = 5000" >> /usr/local/etc/php/php.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/php.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/php.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/php.ini

# Moodledata directory (outside webroot) - BEFORE COPY
RUN mkdir -p /var/moodledata

# Copy your fork's code
COPY . /var/www/html/

# Fix ownership only - no chmod needed for local testing
RUN chown -R www-data:www-data /var/www/html /var/moodledata

EXPOSE 80
