FROM php:7.4-apache

# Extensiones necesarias: PDO MySQL + mysqli (legacy)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite para el .htaccess
RUN a2enmod rewrite

# Config de Apache para /bialy/
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
