# Utiliser une image officielle PHP avec Apache et extensions nécessaires
FROM php:8.1-apache

# Installer les dépendances et extensions nécessaires
RUN apt-get update && apt-get install -y \
    zip unzip git libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip mbstring

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copier le projet dans le conteneur
COPY . /var/www/html

# Installer les dépendances PHP avec composer
RUN composer install --no-dev --optimize-autoloader

# Exposer le port 80 (Apache)
EXPOSE 80

# Commande pour lancer Apache en mode foreground
CMD ["apache2-foreground"]
