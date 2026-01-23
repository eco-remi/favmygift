# Étape 1 : Build des dépendances (Vendor)
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
# Installation des dépendances de prod uniquement (pas de dev, optimisé)
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# Étape 2 : L'image finale de production
FROM dunglas/frankenphp

# On définit les variables d'environnement de base pour la prod
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV SERVER_NAME=:8080

# Installation des extensions PHP requises par Symfony (Intl, Zip, OPcache...)
# L'image FrankenPHP intègre un script magique pour ça !
RUN install-php-extensions \
    intl \
    opcache \
    pdo_pgsql

# Configuration de base pour la performance (OPcache)
COPY .docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copie du code source de l'application
WORKDIR /app
COPY . .

# Récupération des vendors depuis l'étape 1
COPY --from=vendor /app/vendor ./vendor

# Finalisation du setup Symfony (cache, assets...)
RUN php bin/console cache:clear
RUN php doctrine:migrations:migrate --env=prod

# C'est prêt ! FrankenPHP se lance automatiquement.