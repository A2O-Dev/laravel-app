ARG PHP_VERSION=8.1.0
ARG COMPOSER_VERSION=2.3.7

# Composer dependencies
FROM composer:${COMPOSER_VERSION} AS composer-dependencies

WORKDIR /app
COPY . /app
RUN composer install --prefer-dist --no-interaction --ignore-platform-reqs

# PHP build + Node
FROM php:${PHP_VERSION}-fpm-alpine
ARG MCP_PORT=5173
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
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install zip \
    && docker-php-source delete \
    && rm -rf /etc/apk/cache/*

# Install Node.js v22.20.0
RUN curl -fsSL https://unofficial-builds.nodejs.org/download/release/v22.19.0/node-v22.19.0-linux-x64-musl.tar.xz \
    | tar -xJ -C /usr/local --strip-components=1 --no-same-owner \
 && ln -s /usr/local/bin/node /usr/bin/node \
 && ln -s /usr/local/bin/npm /usr/bin/npm \
 && ln -s /usr/local/bin/npx /usr/bin/npx
# Copy Laravel + composer vendor
WORKDIR /var/www/html
COPY . .
COPY --from=composer-dependencies /app/vendor /var/www/html/vendor

# Copy MCP server
COPY mcp-server /var/www/html/mcp-server

# Install MCP server dependencies
WORKDIR /var/www/html/mcp-server
RUN npm install --production

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
RUN php artisan passport:keys

# Generate swagger
RUN php artisan l5-swagger:generate

# Expose MCP port
EXPOSE ${MCP_PORT}

## Start supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
