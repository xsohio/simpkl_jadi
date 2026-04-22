FROM php:8.2-fpm-alpine

# Install nginx
RUN apk add --no-cache nginx

# Install PHP extensions untuk MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Buat direktori nginx
RUN mkdir -p /run/nginx

# Copy nginx config
COPY nginx.conf /etc/nginx/http.d/default.conf

# Copy start script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Copy semua file project
COPY . /var/www/html/

# Set permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 8080

CMD ["/start.sh"]
