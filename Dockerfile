FROM php:8.4-fpm

# Cài đặt các package cần thiết
RUN apt-get update && apt-get install -y \
    curl \
    zip \
    unzip \
    git \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev

RUN docker-php-ext-install pdo pdo_mysql

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Đặt thư mục làm việc
WORKDIR /var/www/html

# Copy toàn bộ source code Laravel vào container
COPY . .

# Chạy Composer install
# RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-fileinfo
RUN composer install --ignore-platform-req=ext-fileinfo
# Phân quyền thư mục storage và bootstrap/cache
RUN chmod -R 777 storage bootstrap/cache

CMD ["php-fpm"]
