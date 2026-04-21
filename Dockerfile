ARG PHP_VERSION=8.1.33
ARG COMPOSER_VERSION=2.3.7
ARG NODE_VERSION=22.19.0

# Composer dependencies
FROM composer:${COMPOSER_VERSION} AS composer-dependencies

WORKDIR /app
COPY . /app
RUN composer install --prefer-dist --no-interaction --ignore-platform-reqs

# Node dependencies for MCP server
FROM node:${NODE_VERSION}-alpine AS node-dependencies
WORKDIR /app/mcp-server
COPY mcp-server/package*.json ./
RUN npm install --production

# PHP build + Node
FROM php:${PHP_VERSION}-fpm-alpine
ARG MCP_PORT=5137
ENV MCP_PORT=${MCP_PORT}

# Install PHP extensions
RUN apk add -U --no-cache \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        zip \
        curl \
        unzip \
        nginx \
        supervisor \
        bash \
        autoconf \
        build-base \
        linux-headers \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install zip \
    && docker-php-source delete \
    && rm -rf /etc/apk/cache/*

RUN pecl install xdebug-3.2.2 \
    && docker-php-ext-enable xdebug \
    && apk del autoconf build-base linux-headers  

COPY docker/app/config/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN touch /tmp/xdebug.log && chmod 777 /tmp/xdebug.log

# Copy Node from official image
COPY --from=node-dependencies /usr/local/bin/node /usr/local/bin/node
COPY --from=node-dependencies /usr/local/lib/node_modules /usr/local/lib/node_modules

# Copy Laravel + composer vendor
WORKDIR /var/www/html
COPY . .
COPY --from=composer-dependencies /app/vendor /var/www/html/vendor

# Copy MCP server + node_modules
COPY mcp-server /var/www/html/mcp-server
COPY --from=node-dependencies /app/mcp-server/node_modules /var/www/html/mcp-server/node_modules

# Set permissions
WORKDIR /var/www/html
RUN chmod 777 -R storage public

# Set environment variables
COPY .env /var/www/html/.env
RUN php artisan key:generate
# Setup supervisord
RUN mkdir -p /var/log/supervisor \
    && touch /var/log/supervisor/mcp-server.log \
    && touch /var/log/supervisor/mcp-server-error.log \
    && chmod 777 /var/log/supervisor/*.log

COPY --chmod=0777 docker/app/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chmod=0777 docker/app/supervisor/schedule.sh /etc/supervisor/conf.d/schedule.sh

# Setting nginx
COPY --chmod=0777 docker/app/nginx/default.conf /etc/nginx/http.d/default.conf

# Generate keys
RUN php artisan passport:keys --force

# Generate swagger
RUN php artisan l5-swagger:generate

# Expose MCP port
EXPOSE ${MCP_PORT}

## Start supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
