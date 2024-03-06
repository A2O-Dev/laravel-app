ARG PHP_VERSION=8.1.0
ARG COMPOSER_VERSION=2.3.7

FROM alpine:3.19.1 AS envs-download

RUN wget -q -t3 'https://packages.doppler.com/public/cli/rsa.8004D9FF50437357.key' -O /etc/apk/keys/cli@doppler-8004D9FF50437357.rsa.pub && \
    echo 'https://packages.doppler.com/public/cli/alpine/any-version/main' | tee -a /etc/apk/repositories && \
    apk add doppler \
    && rm -rf /etc/apk/cache/*

WORKDIR /app
ARG DOPPLER_TOKEN
RUN echo ${DOPPLER_TOKEN} | doppler configure set token --scope /
RUN doppler secrets download --no-file --format env > .env

FROM composer:${COMPOSER_VERSION} AS composer-dependencies

WORKDIR /app
COPY . /app
RUN composer install --prefer-dist --no-interaction --ignore-platform-reqs

FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add -U --no-cache \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        zip \
        curl \
        unzip \
        nginx \
        supervisor \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install zip \
    && docker-php-source delete \
    && rm -rf /etc/apk/cache/*

WORKDIR /var/www/html
COPY . .
COPY --from=composer-dependencies /app/vendor /var/www/html/vendor

# Set environment variables
COPY --from=envs-download /app/.env /var/www/html/.env
RUN php artisan key:generate

# Setting supervisord
RUN mkdir -p /var/log/supervisor
COPY --chmod=0777 docker/app/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chmod=0777 docker/app/supervisor/schedule.sh /etc/supervisor/conf.d/schedule.sh

# Setting nginx
COPY --chmod=0777 docker/app/nginx/default.conf /etc/nginx/http.d/default.conf

# Setting permissions
RUN chmod 777 -R /var/www/html/storage
RUN chmod 777 -R /var/www/html/public

# Generate keys
RUN php artisan passport:keys

# Generate swagger
RUN php artisan l5-swagger:generate

## Start supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
