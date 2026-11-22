# MadeExlusif - perfume e-commerce

Vue 3 (Vite, Pinia, Vue Router) storefront + Laravel (PHP) JSON API + MySQL.

```
madeexlusif/
├── backend/    Laravel overlay: migrations, models, controllers, routes, seeder
└── frontend/   Vue 3 SPA
```

`backend/` is an **overlay**, not a full Laravel install. You create a fresh Laravel app and copy these files over it (steps below).

## 1. Backend (Laravel 11+ / PHP 8.2+, MySQL 8)

```bash
composer create-project laravel/laravel api
cd api
php artisan install:api            # installs Sanctum + routes/api.php wiring
```

Copy the overlay into the new project (merge folders, overwrite `User.php`):

```bash
cp -r ../madeexlusif/backend/app/.       app/
cp -r ../madeexlusif/backend/database/.  database/
cp    ../madeexlusif/backend/routes/api.php routes/api.php
```

Register the `admin` middleware alias in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
})
```

Create a MySQL database and set `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=madeexlusif
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate --seed
php artisan serve                  # http://127.0.0.1:8000
```

Seeded accounts (**change the admin password before deploying**):

| Role | Email | Password |
|---|---|---|
| Admin | admin@madeexlusif.test | ChangeMe!2026 |
| Customer | customer@madeexlusif.test | password123 |

Coupon: `WELCOME10` (10% off, min £30).

## 2. Frontend

```bash
cd frontend
npm install
npm run dev                        # http://localhost:5173  (proxies /api -> Laravel)
```

For production: `npm run build`, serve `dist/`, and set `VITE_API_URL` (e.g. `https://api.example.com/api`). If the SPA and API are on different origins, allow the SPA origin in `config/cors.php` (publish with `php artisan config:publish cors`).

## API summary

| Method | Path | Auth |
|---|---|---|
| GET | `/api/products`, `/api/products/{slug}`, `/api/filters` | public |
| POST | `/api/register`, `/api/login`, `/api/coupons/check` | public (throttled) |
| POST | `/api/orders` | guest or user |
| GET | `/api/me`, `/api/orders`, `/api/orders/{number}`, `/api/wishlist` | user |
| POST | `/api/logout`, `/api/products/{slug}/reviews`, `/api/wishlist/{id}` | user |
| * | `/api/admin/dashboard`, `/api/admin/products`, `/api/admin/orders` | admin |

## Features

Catalogue with search, filters (category, brand, gender, price), sorting and pagination · product variants by size with stock · reviews and ratings · wishlist · persistent bag with free-delivery progress · coupons · guest and account checkout · server-side price/stock validation inside a DB transaction with row locks · order history · admin dashboard, product editor (sizes, stock, visibility) and order status/tracking management.

## Before going live

- **Payments are not connected.** Orders are saved as `unpaid`. Add Stripe (PaymentIntent in `OrderController@store`, mark `paid` from a verified webhook).
- Transactional email (order confirmation, shipping) is not implemented - add Laravel Mailables/queues.
- Product images use a generated SVG bottle; set `products.image` and swap `BottleArt` for `<img>` once you have photography (add an upload endpoint + storage disk).
- Put the API behind HTTPS, set `APP_DEBUG=false`, and change the seeded admin credentials.
- VAT/tax, multi-currency, returns/refunds flow and address book are not included.
