<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SafeMediaDeletionService extends MediaLifecycleService
{
    private const ALLOWED_PREFIXES = ['logo', 'favicon', 'seo_image'];

    public function isManagedBrandAsset($path): bool
    {
        return $this->managedBrandAssetPath($path) !== null;
    }

    public function managedBrandAssetPath($path)
    {
        $path = $this->normalizePath($path);
        if (!$path || strpos($path, 'asset/front-end/img/branding/') !== 0) {
            return null;
        }

        if ($this->isProtectedAsset($path)) {
            return null;
        }

        $filename = basename($path);
        if (! preg_match('/^('.implode('|', array_map('preg_quote', self::ALLOWED_PREFIXES)).')-[A-Za-z0-9][A-Za-z0-9._-]*\.(ico|png|jpe?g|webp)$/i', $filename)) {
            return null;
        }

        $fullPath = public_path($path);
        if (! is_file($fullPath) && ! is_link($fullPath)) {
            return null;
        }

        return $fullPath;
    }

    public function deleteManagedBrandAsset($path): bool
    {
        $path = $this->normalizePath($path);
        if (!$path) {
            return false;
        }

        return $this->deletePath($path, 'Managed branding asset removed.');
    }

    public function deleteManagedBrandAssetIfUnused($path, callable $isReferenced): bool
    {
        $path = $this->normalizePath($path);
        if (!$path || ! $this->isManagedBrandAsset($path)) {
            return false;
        }

        try {
            if ($isReferenced($path)) {
                return false;
            }
        } catch (\Throwable $exception) {
            Log::warning('Managed branding asset reference check failed.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }

        return $this->deleteManagedBrandAsset($path);
    }

    public function deleteAbsolutePath($fullPath, $displayPath = null): bool
    {
        $fullPath = (string) $fullPath;
        if ($fullPath === '') {
            return false;
        }

        $normalized = $this->normalizePath($fullPath);
        if ($normalized === null || strpos($normalized, 'asset/front-end/img/branding/') !== 0) {
            return false;
        }

        if (!is_file($fullPath) && !is_link($fullPath)) {
            return false;
        }

        if (@unlink($fullPath)) {
            return true;
        }

        Log::warning('Managed branding asset could not be deleted.', [
            'path' => $displayPath ?: $fullPath,
        ]);

        return false;
    }

    public function managedBrandAssetPathIfUnused($path, callable $isReferenced)
    {
        return $this->deleteManagedBrandAssetIfUnused($path, $isReferenced);
    }
}
