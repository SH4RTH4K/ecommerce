<?php

namespace App\Services;

use App\Banner;
use App\MediaCleanupQueue;
use App\PaymentMethod;
use App\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MediaLifecycleService
{
    private const PRODUCT_PREFIX = 'asset/front-end/img/Product_image/';
    private const LEGACY_PRODUCT_PREFIX = 'Product_image/';
    private const BANNER_PREFIX = 'asset/front-end/img/banners/';
    private const FEATURE_CARD_PREFIX = 'asset/front-end/img/feature-cards/';
    private const BRANDING_PREFIX = 'asset/front-end/img/branding/';
    private const PAYMENT_PREFIX = 'asset/front-end/img/payments/';

    private const BRANDING_PROTECTED_BASENAMES = [
        'favicon-736a0b0a2889.png',
        'logo-56dfc106e78b.png',
        'logo-e-commerce-0a143d32d43b.png',
        'logo-ecommerce-retail-032b29fc3b87.png',
    ];

    public function normalizePath(?string $path): ?string
    {
        $path = trim(str_replace('\\', '/', (string) $path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    public function isManagedPath(?string $path): bool
    {
        return $this->managedPrefixFor($path) !== null;
    }

    public function isProtectedAsset(?string $path): bool
    {
        $path = $this->normalizePath($path);
        if ($path === null) {
            return true;
        }

        $prefix = $this->managedPrefixFor($path);
        if ($prefix === null) {
            return true;
        }

        if ($prefix === self::BRANDING_PREFIX && in_array(basename($path), self::BRANDING_PROTECTED_BASENAMES, true)) {
            return true;
        }

        return false;
    }

    public function deleteIfUnreferenced(?string $path, array $ignore = [], ?string $reason = null): bool
    {
        $path = $this->normalizePath($path);
        if ($path === null || $this->isProtectedAsset($path)) {
            return false;
        }

        if ($this->isReferenced($path, $ignore)) {
            return false;
        }

        return $this->deletePath($path, $reason);
    }

    public function deletePath(?string $path, ?string $reason = null): bool
    {
        $path = $this->normalizePath($path);
        if ($path === null || $this->isProtectedAsset($path)) {
            return false;
        }

        $fullPath = public_path($path);
        if (! is_file($fullPath) && ! is_link($fullPath)) {
            return true;
        }

        if (@unlink($fullPath)) {
            return true;
        }

        Log::warning('Managed media could not be deleted.', [
            'path' => $path,
            'reason' => $reason,
        ]);

        $this->queueCleanupFailure($path, $reason ?: 'Delete failed.');

        return false;
    }

    public function queueCleanupFailure(string $path, string $reason, array $context = []): void
    {
        if (! Schema::hasTable('media_cleanup_queue')) {
            return;
        }

        $payload = [
            'path' => $this->normalizePath($path) ?: $path,
            'reason' => $reason,
            'entity_type' => $context['entity_type'] ?? null,
            'entity_id' => $context['entity_id'] ?? null,
            'status' => 'pending',
            'attempt_count' => (int) ($context['attempt_count'] ?? 0),
            'last_error' => $context['last_error'] ?? $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('media_cleanup_queue')->insert($payload);
    }

    public function isReferenced(string $path, array $ignore = []): bool
    {
        $path = $this->normalizePath($path);
        if ($path === null) {
            return false;
        }

        return $this->productReferences($path, $ignore)
            || $this->bannerReferences($path, $ignore)
            || $this->featureCardReferences($path, $ignore)
            || $this->brandingReferences($path, $ignore)
            || $this->paymentMethodReferences($path, $ignore);
    }

    public function scanManagedMedia(array $options = []): array
    {
        $entity = strtolower(trim((string) ($options['entity'] ?? 'all')));
        $olderThanDays = max(0, (int) ($options['older_than_days'] ?? 1));
        $delete = (bool) ($options['delete'] ?? false);

        $directories = $this->directoriesForScan($entity);
        $threshold = now()->subDays($olderThanDays)->getTimestamp();

        $summary = [
            'scanned' => 0,
            'referenced' => 0,
            'orphan' => 0,
            'protected' => 0,
            'unknown' => 0,
            'deleted' => 0,
        ];

        $rows = [];
        foreach ($directories as $directory => $entityType) {
            $fullDirectory = public_path($directory);
            if (! is_dir($fullDirectory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullDirectory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (! $fileInfo->isFile()) {
                    continue;
                }

                $relativePath = $this->normalizePath(Str::after((string) $fileInfo->getPathname(), public_path().DIRECTORY_SEPARATOR));
                if ($relativePath === null) {
                    continue;
                }

                $summary['scanned']++;

                $modified = (int) $fileInfo->getMTime();
                $status = 'UNKNOWN';
                $reason = 'Recently created or not yet eligible for cleanup.';
                if ($this->isProtectedAsset($relativePath)) {
                    $status = 'PROTECTED';
                    $reason = 'Bundled/static asset or protected branding file.';
                    $summary['protected']++;
                } elseif ($modified >= $threshold) {
                    $summary['unknown']++;
                } elseif ($this->isReferenced($relativePath)) {
                    $status = 'REFERENCED';
                    $reason = 'A database record still references this file.';
                    $summary['referenced']++;
                } else {
                    $status = 'ORPHAN';
                    $reason = 'No database reference was found.';
                    $summary['orphan']++;
                }

                $row = [
                    'entity_type' => $entityType,
                    'path' => $relativePath,
                    'size_bytes' => $fileInfo->getSize(),
                    'size_human' => $this->humanBytes((float) $fileInfo->getSize()),
                    'last_modified' => date('Y-m-d H:i:s', $modified),
                    'status' => $status,
                    'reason' => $reason,
                ];

                if ($delete && $status === 'ORPHAN') {
                    if ($this->deletePath($relativePath, 'Orphan media cleanup')) {
                        $summary['deleted']++;
                        $row['status'] = 'DELETED';
                        $row['reason'] = 'Deleted during cleanup.';
                    }
                }

                $rows[] = $row;
            }
        }

        return [
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    public function mediaFromProduct(Product $product): array
    {
        $paths = array_filter(array_merge(
            [(string) ($product->product_image ?? null)],
            (array) ($product->gallery_images ?? [])
        ));

        return array_values(array_unique(array_map([$this, 'normalizePath'], $paths)));
    }

    public function mediaFromBanner(Banner $banner): array
    {
        return array_values(array_unique(array_filter(array_map([$this, 'normalizePath'], [
            $banner->image_path ?? null,
            $banner->mobile_image ?? null,
        ]))));
    }

    public function mediaFromPaymentMethod(PaymentMethod $method): array
    {
        return array_values(array_unique(array_filter(array_map([$this, 'normalizePath'], [
            $method->logo_path ?? null,
            $method->qr_image_path ?? null,
        ]))));
    }

    private function directoriesForScan(string $entity): array
    {
        $map = [
            'all' => [
                self::PRODUCT_PREFIX => 'products',
                self::LEGACY_PRODUCT_PREFIX => 'products',
                self::BANNER_PREFIX => 'banners',
                self::FEATURE_CARD_PREFIX => 'feature-cards',
                self::BRANDING_PREFIX => 'branding',
                self::PAYMENT_PREFIX => 'payments',
            ],
            'products' => [
                self::PRODUCT_PREFIX => 'products',
                self::LEGACY_PRODUCT_PREFIX => 'products',
            ],
            'banners' => [self::BANNER_PREFIX => 'banners'],
            'branding' => [self::BRANDING_PREFIX => 'branding'],
            'payments' => [self::PAYMENT_PREFIX => 'payments'],
        ];

        return $map[$entity] ?? $map['all'];
    }

    private function managedPrefixFor(?string $path): ?string
    {
        $path = $this->normalizePath($path);
        if ($path === null) {
            return null;
        }

        foreach ([self::PRODUCT_PREFIX, self::LEGACY_PRODUCT_PREFIX, self::BANNER_PREFIX, self::FEATURE_CARD_PREFIX, self::BRANDING_PREFIX, self::PAYMENT_PREFIX] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }

    private function productReferences(string $path, array $ignore = []): bool
    {
        if (! Schema::hasTable('product')) {
            return false;
        }

        $ignoreIds = array_values(array_filter(array_map('intval', (array) ($ignore['product'] ?? []))));
        $query = DB::table('product')
            ->where(function ($builder) use ($path) {
                $builder->where('product_image', $path)
                    ->orWhere('gallery_images', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $path).'%');
            });

        if ($ignoreIds !== []) {
            $query->whereNotIn('id', $ignoreIds);
        }

        return $query->exists();
    }

    private function bannerReferences(string $path, array $ignore = []): bool
    {
        if (! Schema::hasTable('banners')) {
            return false;
        }

        $ignoreIds = array_values(array_filter(array_map('intval', (array) ($ignore['banner'] ?? []))));
        $query = DB::table('banners')
            ->where(function ($builder) use ($path) {
                $builder->where('image_path', $path)
                    ->orWhere('mobile_image', $path);
            });

        if ($ignoreIds !== []) {
            $query->whereNotIn('id', $ignoreIds);
        }

        return $query->exists();
    }

    private function featureCardReferences(string $path, array $ignore = []): bool
    {
        if (! Schema::hasTable('homepage_feature_cards')) return false;
        $ignoreIds = array_values(array_filter(array_map('intval', (array) ($ignore['homepage_feature_card'] ?? []))));
        $query = DB::table('homepage_feature_cards')->where('image_path', $path);
        if ($ignoreIds !== []) $query->whereNotIn('id', $ignoreIds);
        return $query->exists();
    }

    private function brandingReferences(string $path, array $ignore = []): bool
    {
        if (! Schema::hasTable('site_settings')) {
            return false;
        }

        $ignoreKeys = array_values(array_filter((array) ($ignore['site_setting'] ?? [])));
        $query = DB::table('site_settings')
            ->where('setting_value', $path)
            ->whereIn('setting_key', ['site_logo', 'site_logo_tablet', 'site_logo_mobile', 'favicon', 'default_og_image']);

        if ($ignoreKeys !== []) {
            $query->whereNotIn('setting_key', $ignoreKeys);
        }

        return $query->exists();
    }

    private function paymentMethodReferences(string $path, array $ignore = []): bool
    {
        if (! Schema::hasTable('payment_methods')) {
            return false;
        }

        $ignoreIds = array_values(array_filter(array_map('intval', (array) ($ignore['payment_method'] ?? []))));
        $query = DB::table('payment_methods')
            ->where(function ($builder) use ($path) {
                $builder->where('logo_path', $path)
                    ->orWhere('qr_image_path', $path);
            });

        if ($ignoreIds !== []) {
            $query->whereNotIn('id', $ignoreIds);
        }

        return $query->exists();
    }

    private function humanBytes(float $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
