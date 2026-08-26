<?php

namespace App\Console\Commands;

use App\Services\PublicAssetPublisher;
use Illuminate\Console\Command;

class PublishPublicAssets extends Command
{
    protected $signature = 'public-assets:publish {--cpanel-root : Copy public assets to the application root for a cPanel document root}';

    protected $description = 'Publish version-controlled Laravel public assets for the configured deployment layout.';

    public function handle(PublicAssetPublisher $publisher): int
    {
        if (! $this->option('cpanel-root')) {
            $this->line('No public asset copy was requested. Use --cpanel-root for a cPanel document-root deployment.');
            return self::SUCCESS;
        }

        $directories = $publisher->publishToCpanelRoot();
        $this->info('Published public asset directories: '.($directories ? implode(', ', $directories) : 'none found').'.');
        return self::SUCCESS;
    }
}
