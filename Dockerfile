# PSAU Admission System - Dockerfile for Render
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Create uploads directory
RUN mkdir -p uploads images && chown -R www-data:www-data uploads images

# Configure Apache
RUN a2enmod rewrite
COPY .htaccess /var/www/html/.htaccess

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
