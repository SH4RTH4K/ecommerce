# Ecommerce

Ecommerce is a Laravel-powered, white-label online store with a responsive storefront, product discovery and comparison, checkout, customer self-service, inventory tools, and an administration dashboard. “Ecommerce” is the default installation name; administrators can replace the public branding without editing source code.

## Main features

- Categories, manufacturers, product attributes, specifications, reviews, and stock alerts
- Cart, wishlist, comparison, saved PC builds, checkout, coupons, and delivery zones
- Orders, protected invoices, returns, credit notes, warranty claims, and support requests
- Product, inventory, supplier, purchasing, marketing, reporting, and staff administration
- Multi-location stock, backups, notifications, integrations, APIs, and webhooks
- Configurable SEO, analytics, homepage content, contact information, and branding

## Technology requirements

- PHP 7.4
- Laravel 5.7
- MySQL or MariaDB
- Composer 2
- Node.js and npm when rebuilding frontend assets

> This legacy Laravel 5.7 application must run with PHP 7.4. It is not directly compatible with PHP 8.2.

## Installation

```bash
git clone https://github.com/SH4RTH4K/ecommerce.git
cd ecommerce
composer install
```

Create `.env`, generate the application key, and configure the database:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

```dotenv
APP_NAME="Ecommerce"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=
```

Then initialize and run the application:

```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000` in a browser.

## White-label branding

Sign in at `/admin/login`, then open `/site-customization`. An administrator can change:

- Site/store name and tagline
- Storefront, admin, invoice, and credit-note logo
- Browser favicon
- Phone, support email, WhatsApp, address, and business hours
- Footer description and copyright text
- SEO title, description, keywords, robots rules, and sharing image
- Google Analytics and Search Console settings
- Social links, notices, homepage content, and promotional banners

The configured identity is used throughout page titles, navigation, login and account screens, contact information, product metadata, invoices, notifications, and customer emails.

## Development

```bash
npm install
npm run dev
vendor/bin/phpunit
```

Use `npm run production` for optimized frontend assets. Never commit `.env`, credentials, private API keys, database exports, or production customer data.

## Deployment

- Point the web server document root to `public/`.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Configure production database, mail, HTTPS, cache, and queue settings.
- Ensure `storage/` and `bootstrap/cache/` are writable.
- Back up the database and uploaded assets before migrations.

## License

This project declares the MIT license in `composer.json`.
