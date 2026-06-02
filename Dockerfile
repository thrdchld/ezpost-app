FROM php:8.2-apache

# Ubah port Apache ke 7860 sesuai standar Hugging Face Spaces
RUN sed -i 's/80/7860/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Install ekstensi PDO MySQL untuk koneksi ke TiDB Serverless
RUN docker-php-ext-install pdo pdo_mysql

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Salin semua file aplikasi ke direktori web server
COPY . /var/www/html/

# Buat folder uploads dan berikan izin (Permission) agar PHP bisa menyimpan video
RUN mkdir -p /var/www/html/uploads && \
    chmod -R 777 /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/