<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PublicAssetPublisher
{
    private const DIRECTORIES = ['asset', 'css', 'js', 'svg'];

    /**
     * Copies version-controlled web assets from Laravel's public directory to
     * the cPanel document root. It deliberately does not delete destination
     * files, so uploads and other runtime media remain untouched.
     */
    public function publishToCpanelRoot(): array
    {
        $sourceRoot = base_path('public');
        $targetRoot = base_path();

        if (! is_dir($sourceRoot)) {
            throw new \RuntimeException('The Laravel public directory is missing, so public assets cannot be published.');
        }

        $published = [];
        foreach (self::DIRECTORIES as $directory) {
            $source = $sourceRoot.DIRECTORY_SEPARATOR.$directory;
            if (! is_dir($source)) {
                continue;
            }

            $target = $targetRoot.DIRECTORY_SEPARATOR.$directory;
            File::ensureDirectoryExists($target, 0755, true);
            if (! File::copyDirectory($source, $target)) {
                throw new \RuntimeException('Unable to publish public/'.$directory.' to the cPanel document root.');
            }
            $published[] = $directory;
        }

        return $published;
    }

    public function publishForMode(?string $mode): array
    {
        $mode = $mode ?: 'auto';
        if ($mode === 'auto') {
            $mode = app()->environment('production') ? 'cpanel_root' : 'laravel_public';
        }

        if ($mode === 'laravel_public') {
            return [];
        }
        if ($mode !== 'cpanel_root') {
            throw new \RuntimeException('The selected public asset deployment mode is invalid.');
        }

        return $this->publishToCpanelRoot();
    }
}
