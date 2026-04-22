FROM php:8.2-fpm-alpine

# Install nginx dan supervisor
RUN apk add --no-cache nginx supervisor

# Install PHP extensions untuk MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Buat direktori yang dibutuhkan
RUN mkdir -p /run/nginx /var/log/supervisor

# Copy config files
COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf

# Copy semua file project
COPY . /var/www/html/

# Set permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
