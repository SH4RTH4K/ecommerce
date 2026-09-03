<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use Tests\TestCase;

class DatabaseBackupServiceTest extends TestCase
{
    public function test_featured_brand_images_are_added_to_media_archives(): void
    {
        $directory = public_path('asset/front-end/img/featured-brands');
        $path = $directory.'/backup-service-test-logo.png';

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, 'featured-brand-logo');

        $archive = new class
        {
            public array $entries = [];

            public function addFile(string $source, string $entry): void
            {
                $this->entries[$entry] = $source;
            }
        };

        try {
            $method = (new \ReflectionClass(DatabaseBackupService::class))
                ->getMethod('addMediaFiles');
            $method->setAccessible(true);
            $method->invoke(new DatabaseBackupService(), $archive);

            $entry = 'media/asset/front-end/img/featured-brands/backup-service-test-logo.png';

            $this->assertArrayHasKey($entry, $archive->entries);
            $this->assertSame(realpath($path), realpath($archive->entries[$entry]));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
