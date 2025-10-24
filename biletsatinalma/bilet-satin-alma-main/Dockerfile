# 1. PHP + Apache imajını kullan
FROM php:8.2-apache

# 2. Sistem paketlerini ve SQLite kütüphanesini yükle
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# 3. Proje dosyalarını container’a kopyala
COPY . /var/www/html/

# 4. Apache portunu aç
EXPOSE 80
