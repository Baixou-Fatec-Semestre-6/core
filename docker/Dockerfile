# syntax=docker/dockerfile:1

# ---------- Estágio 1: base (PHP + extensões + Composer) ----------
FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug-3.4.2 && docker-php-ext-enable xdebug
RUN pecl install mongodb-2.5.7 && docker-php-ext-enable mongodb

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd sockets opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ---------- Estágio 2: vendor (instalar dependências do Composer) ----------
FROM base AS vendor

# Use --build-arg COMPOSER_FLAGS="" para incluir require-dev (ex.: Faker/seeders)
ARG COMPOSER_FLAGS="--no-dev"

# Apenas os manifestos são copiados, então esta camada fica em cache até que eles mudem
COPY composer.json composer.lock ./
RUN composer install ${COMPOSER_FLAGS} \
    --no-interaction --no-progress --prefer-dist \
    --no-scripts --no-autoloader

# ---------- Estágio 3: imagem final da aplicação ----------
FROM base AS app

ARG COMPOSER_FLAGS="--no-dev"

# Código da aplicação (filtrado pelo .dockerignore) e, depois, o vendor/ já instalado
COPY . /var/www
COPY --from=vendor /var/www/vendor /var/www/vendor

# Gerar o autoloader otimizado e executar o package discovery
RUN composer dump-autoload ${COMPOSER_FLAGS} --optimize --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]
