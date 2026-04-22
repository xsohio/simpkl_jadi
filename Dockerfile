FROM php:8.2-apache

# Install PHP extensions untuk MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy semua file project
COPY . /var/www/html/

# Konfigurasi Apache DocumentRoot ke root folder
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Ganti port Apache ke 8080 (Railway butuh ini)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

# Set permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 8080
