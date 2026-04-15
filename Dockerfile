FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite (useful for clean URLs later)
RUN a2enmod rewrite

# Copy project files into the web root
COPY . /var/www/html/

# Ensure assets/images directory is writable
RUN mkdir -p /var/www/html/assets/images \
    && chown -R www-data:www-data /var/www/html/assets

EXPOSE 80
