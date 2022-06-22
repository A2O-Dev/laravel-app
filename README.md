# Laravel App Demo

Application to serve product catalog

## Installing

1. Copy environment file

```shell
cp .env.example .env
```

2. Add permissions to storage folder

```shell
sudo chmod -R 777 storage
```

3. Install dependencies

```shell
docker run --volume $PWD:/app composer:2.3.7 composer install
```

4. Up containers

```shell
docker compose up -d
```

5. Generate key

```shell
docker compose exec app php artisan key:generate
```

6. Migrate and seed the database

```shell
docker compose exec app php artisan migrate --seed
```

7. Generate client credentials for the authentication

```shell
docker compose exec app php artisan passport:install
```

8. Go To Api Documentation

   By Default the api documentation is served in: http://localhost/api/documentation
