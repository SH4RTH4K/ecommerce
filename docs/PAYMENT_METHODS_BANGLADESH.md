# Bangladesh Payment Methods Deployment

## Architecture and safety

The existing `payment_methods` IDs, codes, `type`, `instructions`, EMI relationships, and historical order labels are preserved. The modern fields extend that table. `PaymentMethodAvailabilityService` is the single server-side authority for checkout visibility, amount/zone eligibility, sandbox rules, and customer-paid charges.

Credentials entered by a settings administrator are encrypted with Laravel `Crypt` using `APP_KEY`, hidden by the model, never returned by preview endpoints, and never rendered after saving. Do not store customer PINs, OTPs, CVVs, card data, or personal passwords. Database credential management is optional; production environment variables/provider adapters remain preferable.

No real gateway adapter is bundled. bKash, Nagad, Rocket, Upay, SSLCOMMERZ, TakaPay, card and Bangla QR names are configuration choices, not claims of API integration. API/redirect/hosted methods remain `Configuration Required` until a verified adapter, approved merchant account, and provider credentials exist.

## Files and database

New files:

- `app/Http/Controllers/PaymentMethodAdminController.php`
- `app/PaymentTransaction.php`
- `app/Services/PaymentMethodAvailabilityService.php`
- `resources/views/admin/components/payment-method-form.blade.php`
- `database/migrations/2026_07_18_030000_modernize_payment_methods_for_bangladesh.php`
- `tests/Feature/PaymentMethodsModernizationTest.php`

Updated files include `app/PaymentMethod.php`, `app/Http/Controllers/CheckoutController.php`, `routes/web.php`, the admin payment page, checkout Blade/CSS, `config/app.php`, and environment examples.

The migration adds Bangladesh-focused configuration fields to `payment_methods`, creates `payment_transactions`, and adds `payment_charge` and `payment_status` to `orders`. Existing values are mapped safely: cash to COD, bank to manual bank transfer, card to card payment, and mobile to manual MFS.

## cPanel deployment

Back up the database and application files first. Upload project files to the Laravel application root using their repository-relative paths. Upload `public/css/ecommerce-home.css` and any future payment logos to the public web root matching the existing public-path mapping.

With Terminal:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set `APP_TIMEZONE=Asia/Dhaka`, keep `APP_DEBUG=false`, use HTTPS, and preserve the current production `APP_KEY`; changing it makes encrypted credentials unreadable. No queue or scheduler is required for manual/COD methods. A future provider adapter may introduce its own requirements.

Without Terminal, use phpMyAdmin after taking a backup: import a SQL export generated from the migration in a staging copy, then upload code. Do not create a public Artisan runner and do not leave migration utilities in `public_html`. Record the migration row in the `migrations` table only after every schema statement succeeds.

## Sandbox and live activation

Create or duplicate a method in Sandbox, leave it disabled, review all instructions/rules, enable `allow_sandbox_at_checkout` only for a controlled administrator test, and place a test order. Confirm payment charge, transaction record, duplicate provider-reference rejection, pending verification, approval/rejection, and mobile checkout layout.

Before Live activation verify merchant approval, real provider adapter, encrypted/environment-managed credentials, HTTPS callbacks/webhook, signature verification, provider-side amount/currency/reference validation, idempotent callbacks, refund process, `APP_DEBUG=false`, BDT handling, audit logs, and a successful provider-side test. Saving a provider name is not sufficient.

There are currently no gateway callback routes because no verified provider adapter exists. Add provider-specific success, failure, cancel, and webhook routes only with signature verification and idempotency implemented in that adapter.

## Rollback

Disable affected methods first and back up payment/order data. With Terminal, run the specific migration rollback in a controlled maintenance window. Rolling back deletes `payment_transactions` and the new payment fields, so export those records first. Restore the database backup if any schema step fails.
