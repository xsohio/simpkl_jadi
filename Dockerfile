FROM php:8.2-fpm-alpine

# Install nginx dan dependencies
RUN apk add --no-cache nginx

# Install PHP extensions untuk MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Konfigurasi nginx
RUN mkdir -p /run/nginx
RUN echo 'server {
    listen 8080;
    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}' > /etc/nginx/http.d/default.conf

# Copy semua file project
COPY . /var/www/html/

# Set permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Script untuk jalankan nginx + php-fpm bersamaan
RUN echo '#!/bin/sh' > /start.sh && \
    echo 'php-fpm &' >> /start.sh && \
    echo 'nginx -g "daemon off;"' >> /start.sh && \
    chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
