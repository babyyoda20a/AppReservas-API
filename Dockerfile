# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# Extensiones necesarias para MySQL (PDO)
RUN docker-php-ext-install pdo pdo_mysql

# Módulos útiles de Apache (por si usas .htaccess / headers)
RUN a2enmod rewrite headers

# Copia TODO el proyecto al docroot del contenedor
COPY . /var/www/html

# Puerto HTTP del contenedor
EXPOSE 80
