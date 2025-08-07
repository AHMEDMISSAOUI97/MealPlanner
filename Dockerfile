# Use PHP 8.1 with FPM
FROM php:8.1-fpm

# Install necessary extensions
RUN apt-get update && \
    apt-get install -y \
        git \
        unzip \
        curl \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev && \
    # Force reinstallation of glpk-utils for a clean state
    apt-get remove -y glpk-utils && \
    apt-get autoremove -y && \
    apt-get clean && \
    apt-get install -y glpk-utils && \
    # Install PHP extensions
    docker-php-ext-install pdo_mysql gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Expose port
EXPOSE 9000

CMD ["php-fpm"]