# Usa la imagen oficial de PHP
FROM php:8.1-apache

# Instala las dependencias necesarias para Symfony
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    unzip \
    git \
    && docker-php-ext-install intl pdo pdo_pgsql opcache

# Habilita mod_rewrite para Apache
RUN a2enmod rewrite

# Configura el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos del proyecto
COPY . /var/www/html

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instala las dependencias de Symfony
RUN composer install --no-dev --optimize-autoloader

# Expone el puerto 80 para la aplicación
EXPOSE 80

# Establece el comando predeterminado para iniciar Apache
CMD ["apache2-foreground"]