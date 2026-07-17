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

- PHP 8.3 or newer
- Laravel 13
- MySQL / MariaDB
- Blade templates
- Bootstrap 4, jQuery, Vue 2, and Laravel Mix 2
- PHPUnit 12

> [!IMPORTANT]
> This application requires PHP 8.3 or newer. Confirm that both the command-line PHP binary and the web server use a supported version before installing dependencies or running Artisan commands.

## Requirements

- PHP 8.3 or newer with the extensions required by Laravel and your database driver
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

## Running the application locally

### Start the local server

```bash
php artisan config:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Keep that terminal open and visit `http://127.0.0.1:8000`. Stop the server with `Ctrl+C`.

`artisan serve` is for local development only. Do not use it as the public production web server.

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

The administration login is available at `/admin/login` (the legacy `/admin-login` URL redirects to the administrator login flow).

### Create the first administrator

Run all migrations first, then open Laravel Tinker from the project directory:

```bash
php artisan tinker
```

Paste the following into Tinker and press Enter after each statement:

```php
$roleId = DB::table('admin_roles')->where('name', 'Super Admin')->value('id');
DB::table('tbl_admin')->updateOrInsert(['admin_name' => 'admin'], ['full_name' => 'Default Administrator', 'admin_email' => 'admin@example.com', 'role_id' => $roleId, 'is_active' => 1, 'admin_password' => Hash::make('Admin@12345'), 'created_at' => now(), 'updated_at' => now()]);
exit
```

You can then sign in at `http://127.0.0.1:8000/admin/login` with:

- Username: `admin`
- Password: `Admin@12345`

The command is safe to run again: it updates the `admin` account instead of creating a duplicate.

> [!WARNING]
> These credentials are intended only for initial local setup. Change the password immediately from **Administration > Admin Users** before exposing the application to a network or deploying it. Use a unique password of at least 12 characters.

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

## Deploying to cPanel

1. In **MultiPHP Manager**, select PHP 8.3 or newer for the domain. Enable the PHP extensions required by Laravel and MySQL, including `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, and `fileinfo`.
2. Create a MySQL database and database user in cPanel, assign the user **All Privileges**, and note the cPanel-prefixed database name and username.
3. Upload or clone the project into a directory outside `public_html`, for example `/home/CPANEL_USER/ecommerce`.
4. Set the domain's document root to `/home/CPANEL_USER/ecommerce/public`. This is the recommended and secure layout because `.env`, application code, and `vendor` remain outside the public web directory.
5. Copy `.env.cpanel.example` to `.env`, replace every placeholder, and configure at least:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://example.com

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=cpaneluser_ecommerce
   DB_USERNAME=cpaneluser_dbuser
   DB_PASSWORD=use-a-strong-database-password
   ```

6. In **cPanel Terminal**, run these commands from the project directory. The exact PHP path can differ by host; use `php -v` first and ensure it reports PHP 8.3 or newer.

   ```bash
   cd /home/CPANEL_USER/ecommerce
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan config:cache
   php artisan view:cache
   ```

   Run `key:generate` only for a new installation whose `.env` has no `APP_KEY`. Never replace an existing production key because encrypted data and sessions may become unreadable.

   On many cPanel servers, PHP 8.3 can be invoked explicitly with `/opt/cpanel/ea-php83/root/usr/bin/php artisan ...` if the default `php` command uses another version.

7. Make Laravel's writable directories available to the web-server user:

   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

8. Enable SSL in cPanel, open the site over HTTPS, and create the first administrator using the instructions in the **Administration** section.

If cPanel does not allow changing the document root, keep the Laravel application outside `public_html`, copy only the contents of its `public` directory into `public_html`, and update the two paths in `public_html/index.php` to point to the real `vendor/autoload.php` and `bootstrap/app.php`. Never place `.env` or the complete Laravel project inside a publicly accessible directory.

### cPanel without Terminal or Composer

Run `composer install --no-dev --optimize-autoloader` locally with PHP 8.3 or newer, then upload the project including `vendor`. Export the prepared local database and import it with phpMyAdmin, or ask the hosting provider to run the migrations. Generate `APP_KEY` locally with `php artisan key:generate --show` and place the resulting value in the server `.env`.

## Deploying to a VPS or other server

Use PHP 8.3 or newer with Apache or Nginx, MySQL/MariaDB, and Composer. Upload or clone the project, configure `.env`, and run:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan view:cache
```

Run `key:generate` only once for a new installation. Keep the existing `APP_KEY` during later deployments.

Configure the virtual host's document root as `/path/to/ecommerce/public`. For Nginx, the application location must fall back to Laravel's front controller:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

For Apache, enable `mod_rewrite` and allow `.htaccess` overrides for the `public` directory. The repository already includes the Laravel rewrite file.

The web-server user must be able to write to `storage`, `bootstrap/cache`, and the product/branding upload directories below `public/asset/front-end/img`. Configure HTTPS and set `APP_URL` to the final HTTPS domain.

## Production checklist

- Keep `.env`, database exports, backups, and application source outside the public document root.
- Set `APP_ENV=production`, `APP_DEBUG=false`, and a correct HTTPS `APP_URL`.
- Use a unique `APP_KEY`, strong database credentials, and a new administrator password.
- Configure SMTP, scheduled jobs, queues, and backups if those features are used.
- Back up the database and `public/asset/front-end/img` uploads before migrations or updates.
- After changing `.env`, clear and rebuild configuration with `php artisan config:clear` followed by `php artisan config:cache`.
- Do not run `php artisan serve` as the production server.

## License

This project declares the MIT license in `composer.json`. See the repository's license file, if provided, for the complete terms.
