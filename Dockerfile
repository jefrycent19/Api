FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar Apache mod_rewrite
RUN a2enmod rewrite

# Copiar archivo del proyecto
COPY . /var/www/html/

# Ajustar permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
