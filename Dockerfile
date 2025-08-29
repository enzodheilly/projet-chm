# Utiliser une image officielle PHP avec Apache
FROM php:8.1-apache

# Installer les dépendances nécessaires et extensions PHP
RUN apt-get update && apt-get install -y \
    git zip unzip curl libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install intl pdo_mysql mbstring zip

# Installer Composer globalement
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copier le code de l’application dans le dossier webroot d’Apache
COPY . /var/www/html

# Donner les droits corrects sur le dossier (optionnel mais recommandé)
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/vendor

# Installer les dépendances PHP avec Composer (sans les dépendances dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Exposer le port 80 pour Apache
EXPOSE 80

# Démarrer Apache en mode foreground
CMD ["apache2-foreground"]
