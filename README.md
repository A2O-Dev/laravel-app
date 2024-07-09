# Laravel App Demo

Application to serve product catalog

## Installing

### Docker

-   Build docker image

    ```shell
    docker build -t laravel-app-demo --build-arg APP_URL="http://my-domain.com" --no-cache .
    ```

### docker compose

1. Up containers

    ```shell
    docker compose up -d
    ```

2. Migrate and seed the database

    ```shell
    docker compose exec app php artisan migrate --seed
    ```

3. Generate client credentials for the authentication

    ```shell
    docker compose exec app php artisan passport:install
    ```

4. Go To Api Documentation

    By Default the api documentation is served in: http://localhost/api/documentation
