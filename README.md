# Product Inventory API

A Laravel 11 REST API for inventory, categories, and suppliers. Product endpoints require a Laravel Sanctum bearer token. The API contract is in [`docs/openapi.yaml`](docs/openapi.yaml).

## Requirements

- PHP 8.4.1 or newer with PDO SQLite or PDO MySQL (required by the locked dependencies)
- Composer 2
- Docker Compose v2 for the containerized setup

## Local setup

```powershell
composer install --no-blocking
Copy-Item .env.example .env
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The API is available at `http://127.0.0.1:8000/api`. The development seeder creates `inventory@example.com` with password `password`; change or remove this account outside local development.

Composer's current security policy blocks Laravel 11 dependencies due to published advisories. The lockfile pins Laravel Framework 11.56.1 and Sanctum 4.3.3; `--no-blocking` is needed to install this explicitly requested Laravel 11 stack. `composer audit` reports CVE-2026-48019 and two additional framework advisories; Laravel 11 has no patched release for these findings. Review the audit and use a currently supported Laravel release for production deployments.

## Docker setup

```powershell
Copy-Item .env.example .env
docker compose build
docker compose run --rm --no-deps app php artisan key:generate --force
docker compose up --build -d
```

Compose starts the API on port 8000 and MySQL 8.0, waits for MySQL health, then runs migrations and seeders. Set `APP_PORT` to change the host port. The Compose database credentials are development defaults and must not be reused in production.

## Authentication

Register or log in to receive a bearer token. Send it on protected requests as `Authorization: Bearer <token>`.

```http
POST /api/auth/register
Content-Type: application/json

{"name":"Inventory User","email":"user@example.com","password":"a-long-password","password_confirmation":"a-long-password"}
```

`POST /api/auth/login` accepts `email` and `password`. `GET /api/auth/me` returns the authenticated user and `POST /api/auth/logout` revokes the current token.

## Products

All `/api/products` routes require authentication. Create requests require `category_id`, `sku`, `name`, `price`, and `stock_quantity`; `supplier_ids` is optional. Update requests accept any subset of these fields. Deleting a product soft-deletes it; deleted products are omitted from normal queries and can no longer be fetched through the API.

`GET /api/products` supports these query parameters:

| Parameter | Meaning |
| --- | --- |
| `category_id` | Exact category ID |
| `min_price`, `max_price` | Inclusive price bounds |
| `stock_status` | `in_stock`, `out_of_stock`, or `low_stock` |
| `per_page` | Results per page, from 1 to 100 (default 15) |
| `page` | Page number |

Responses use Laravel API Resources and paginator metadata. Product list results are cached for 60 seconds; product writes bump the cache version. API requests are limited to 60 per minute per authenticated user or IP, and auth requests have an additional limit of 10 per minute per email/IP pair.

## Tests

```powershell
php artisan test
```

Feature tests use an in-memory SQLite database. Seeded development data can be recreated with `php artisan migrate:fresh --seed`.