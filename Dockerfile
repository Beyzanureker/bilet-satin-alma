# Apache ve PHP 8.2 imajını kullan
FROM php:8.2-apache

# Gerekli PHP eklentilerini (SQLite) kur
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# Proje dosyalarını Apache'nin web kök dizinine kopyala
COPY . /var/www/html/

# Veritabanı dosyasına yazma izni ver
# Önce dosyanın var olduğundan emin olalım (ilk build sırasında olmayabilir)
RUN touch /var/www/html/database.sqlite && \
    chown www-data:www-data /var/www/html/database.sqlite && \
    chmod 664 /var/www/html/database.sqlite

# Apache mod_rewrite'ı etkinleştir (genellikle iyi bir fikirdir)
RUN a2enmod rewrite

# 80 portunu aç
EXPOSE 80