#!/bin/sh

# Gunakan PORT dari Railway, default 8080
PORT=${PORT:-8080}

# Tulis nginx config dengan port yang benar
cat > /etc/nginx/http.d/default.conf << EOF
server {
    listen ${PORT};
    root /var/www/html;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

# Jalankan php-fpm dan nginx
php-fpm -D
nginx -g "daemon off;"
