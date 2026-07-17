# Ecommerce

Ecommerce is a Laravel-powered, white-label e-commerce platform for computers, laptops, networking equipment, accessories, and related technology products. It includes a responsive customer storefront, product discovery and comparison tools, a complete checkout flow, customer self-service features, and an administration dashboard. “Ecommerce” is only the default installation name; administrators can replace the public branding without editing source code.

## Features

### Storefront

- Responsive homepage with banners, featured categories, featured products, new arrivals, and brands
- Category, subcategory, manufacturer, and keyword-based product discovery
- Product specifications, catalog attributes, reviews, questions, and stock alerts
- Shopping cart, product comparison, wishlist, and saved builds
- PC builder with component selection and add-to-cart support
- Search-engine sitemap, configurable site content, and white-label branding

### Orders and customer service

- Coupon validation, delivery zones, and server-calculated order totals
- Checkout, order confirmation, public order tracking, and protected invoices
- Customer order history and in-app notifications
- Return requests, return status, and credit notes
- Service/warranty claims and customer support requests
- Abandoned-cart recovery
- Configurable payment methods and EMI plans

### Administration

- Product, category, manufacturer, inventory, and catalog-attribute management
- Order, return, service-claim, and customer-message management
- Coupons, delivery zones, payment methods, banners, and site settings
- Sales reports, cost/profit fields, and visitor analytics
- Marketing campaigns and stock alerts
- Admin roles, activity auditing, system health, backups, integrations, and webhooks
- Supplier, purchasing, and multi-location inventory tools

## Technology

- PHP 7.4
- Laravel 5.7
- MySQL / MariaDB
- Blade templates
- Bootstrap 4, jQuery, Vue 2, and Laravel Mix 2
- PHPUnit 7

> [!IMPORTANT]
> This is a legacy Laravel 5.7 application. Use PHP 7.4 for local development. The application is not compatible with PHP 8.2 without framework-level upgrades.

## Requirements

- PHP 7.4 with the extensions required by Laravel and your database driver
- Composer 2
- MySQL or MariaDB
- Node.js and npm (only required when rebuilding frontend assets)

## Installation

1. Clone the repository and enter the project directory.

   ```bash
   git clone <repository-url>
   cd ecommerce
   ```

2. Install PHP dependencies.

   ```bash
   composer install
   ```

3. Create the environment file and application key.

   On Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   On macOS or Linux:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Update the database values in `.env`.

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

5. Create the configured database, then run the migrations.

   ```bash
   php artisan migrate
   ```

6. Clear cached configuration and start the application.

   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan serve
   ```

7. Open `http://127.0.0.1:8000` in a browser.

The repository contains public frontend assets. To rebuild the Laravel Mix bundle, install the JavaScript dependencies and run the appropriate build command:

```bash
npm install
npm run dev
```

For an optimized build, use `npm run production`.

## Configuration

The main environment settings are stored in `.env`:

- `APP_URL` controls generated application URLs.
- `DB_*` values configure the MySQL/MariaDB connection.
- `MAIL_*` values configure transactional email. Without valid SMTP credentials, use the in-app notification features during local development.
- `CACHE_DRIVER`, `SESSION_DRIVER`, and `QUEUE_CONNECTION` default to local-friendly drivers in `.env.example`.

Never commit `.env`, production credentials, database exports, or private API keys.

### Branding and site identity

After signing in as an administrator, open `/site-customization`. The branding controls let each installation change:

- Site/store name and tagline
- Header, storefront, admin, invoice, and credit-note logo
- Browser favicon
- Phone numbers, support email, WhatsApp, address, and business hours
- Footer description and copyright text
- SEO title, description, keywords, robots directives, and social-sharing image
- Google Analytics and Search Console configuration
- Social profile links, homepage content, notices, and promotional banners

The configured site name is also used in page titles, login and account screens, product metadata, notifications, and customer emails. Uploaded branding files are stored by the application; ensure the relevant public upload directories remain writable in production.

## Administration

The administration login is available at `/admin/login` (the legacy `/admin-login` URL redirects to the administrator login flow). An administrator record must exist in the legacy admin table before signing in. No default production credentials are documented or shipped in this README.

## Testing

Run the test suite with:

```bash
vendor/bin/phpunit
```

On Windows PowerShell, you can also use:

```powershell
vendor\bin\phpunit.bat
```

## Project structure

```text
app/Http/Controllers/    Application and admin controllers
app/Services/            Reusable domain services
database/migrations/     Legacy schema and feature migrations
public/                  Web entry point and compiled/static assets
resources/views/         Blade templates and reusable partials
routes/web.php           Storefront, account, checkout, and admin routes
tests/                   PHPUnit unit and feature tests
```

## Deployment notes

- Point the web server document root to the `public` directory.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Configure a production database, mail transport, and HTTPS URL.
- Ensure `storage` and `bootstrap/cache` are writable by the web server.
- Run `composer install --no-dev --optimize-autoloader` and `php artisan config:cache` during deployment.
- Back up the database and uploaded product assets before running migrations on an existing installation.

## License

This project declares the MIT license in `composer.json`. See the repository's license file, if provided, for the complete terms.
