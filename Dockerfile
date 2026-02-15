# Build & Push pour Coolify — branche feat/symfony
# Image finale : PHP 8.3 + Apache, document root = public/

# Étape 1 : construction des assets et dépendances
FROM composer:2 AS builder

WORKDIR /app

# Dépendances système pour extensions PHP
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libpq-dev \
    icu-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl zip opcache pdo_pgsql pdo_mysql \
    && docker-php-ext-enable intl zip opcache pdo_pgsql pdo_mysql

# Copie des fichiers requis pour composer
COPY composer.json composer.lock symfony.lock ./

# Installation des dépendances (sans dev) pour préparer autoload
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copie du code applicatif
COPY . .

# Génération de l'autoload et scripts post-install
RUN composer dump-autoload --optimize --classmap-authoritative

# Étape 2 : image de production
FROM php:8.3-apache-bookworm

# Extensions PHP nécessaires pour Symfony
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libpq-dev \
    libicu-dev \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl zip opcache pdo_pgsql pdo_mysql gd \
    && docker-php-ext-enable intl zip opcache pdo_pgsql pdo_mysql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache : document root = public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers

WORKDIR /var/www/html

# Copie depuis le builder
COPY --from=builder /app .

# Droits pour var/ (cache, logs)
RUN chown -R www-data:www-data var/ \
    && chmod -R 775 var/

# Variables d'environnement par défaut (surchargeables dans Coolify)
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Au démarrage : vider le cache et lancer Apache
RUN php bin/console cache:clear --env=prod --no-warmup 2>/dev/null || true
RUN php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true

EXPOSE 80

CMD ["apache2-foreground"]
