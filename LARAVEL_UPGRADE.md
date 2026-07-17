# Laravel 13 Upgrade

This application targets Laravel 13 and requires PHP 8.3 or newer.

## Deployment

1. Select PHP 8.3+ for both the web server and CLI.
2. Enable the `curl`, `fileinfo`, `gd`, `mbstring`, `openssl`, and `pdo_mysql` extensions.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Run `php artisan optimize:clear`, `php artisan migrate --force`, and `php artisan optimize`.
5. Restart PHP-FPM or the hosting PHP process so opcode caches use the new code.

Do not deploy the repository-local `.runtime` directory. It is ignored by Git and exists only for local verification.

## Local verification

Use a PHP 8.3+ executable for all commands:

```text
php artisan --version
php artisan route:list --except-vendor
php artisan migrate:status
php vendor/bin/phpunit
```

The legacy authentication views continue to use `laravel/ui`'s maintained authentication backend. Laravel Collective HTML was removed; forms now use native Blade markup.
