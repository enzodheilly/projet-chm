# Étape 1 : builder les dépendances
FROM composer:2 AS vendor

WORKDIR /app

# Copier uniquement les fichiers nécessaires à Composer pour éviter de refaire tout à chaque build
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Étape 2 : image d'exécution PHP + Apache
FROM php:8.1-apache

# Installer extensions PHP nécessaires à Symfony
RUN apt-get update && apt-get install -y \
    git zip unzip curl libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install intl pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Copier le code source
WORKDIR /var/www/html
COPY . /var/www/html

# Copier les vendors de l'étape précédente
COPY --from=vendor /app/vendor /var/www/html/vendor

# Donner les bons droits (création des dossiers si besoin)
RUN mkdir -p /var/www/html/var \
    && chown -R www-data:www-data /var/www/html/var /var/www/html/vendor

# Symfony en prod
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Exposer port 80
EXPOSE 80

# Lancer Apache
CMD ["apache2-foreground"]
