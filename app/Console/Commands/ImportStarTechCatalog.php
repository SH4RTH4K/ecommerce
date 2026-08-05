<?php

namespace App\Console\Commands;

use App\Services\StarTechCatalogImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportStarTechCatalog extends Command
{
    protected $signature = 'startech:import-catalog {steps?* : Ordered steps to run (categories, subcategories, brands, series, products, attributes)} {--dry-run : Preview changes without saving them} {--source-address= : Override the saved catalog source address}';

    protected $description = 'Import the Star Tech catalog hierarchy step by step';

    public function handle(StarTechCatalogImporter $importer): int
    {
        try {
            $steps = $this->normalizeSteps($this->argument('steps'));
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        $dryRun = (bool) $this->option('dry-run');
        $sourceAddress = $this->sourceAddress();
        $importer->setSourceAddress($sourceAddress);
        $this->info('Star Tech catalog import started'.($dryRun ? ' in dry-run mode.' : '.'));
        $this->line('Source address: '.$sourceAddress);

        foreach ($steps as $index => $step) {
            $this->line(sprintf('%d. %s', $index + 1, $this->stepLabel($step)));

            $result = match ($step) {
                'categories' => $importer->importCategories($dryRun),
                'subcategories' => $importer->importSubcategories($dryRun),
                'brands' => $importer->importBrands($dryRun),
                'series' => $importer->importSeries($dryRun),
                'products' => $importer->importProducts($dryRun),
                'attributes' => $importer->importAttributes($dryRun),
            };

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Created', (string) ($result['created'] ?? 0)],
                    ['Updated', (string) ($result['updated'] ?? 0)],
                ]
            );
        }

        if (! $dryRun) {
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }

        $this->info('Star Tech catalog import finished.');
        return 0;
    }

    private function sourceAddress(): string
    {
        $override = trim((string) $this->option('source-address'));
        if ($override !== '') {
            return $override;
        }

        $saved = trim((string) DB::table('site_settings')
            ->where('setting_key', 'catalog_import_source_address')
            ->value('setting_value'));

        return $saved !== '' ? $saved : 'https://www.startech.com.bd/';
    }

    private function normalizeSteps(array $steps): array
    {
        if ($steps === []) {
            return ['categories', 'subcategories', 'brands', 'series'];
        }

        $map = [
            'category' => 'categories',
            'categories' => 'categories',
            'sub-category' => 'subcategories',
            'subcategory' => 'subcategories',
            'subcategories' => 'subcategories',
            'brand' => 'brands',
            'brands' => 'brands',
            'series' => 'series',
            'product' => 'products',
            'products' => 'products',
            'attributes' => 'attributes',
        ];

        $normalized = [];
        foreach ($steps as $step) {
            $key = strtolower(trim((string) $step));
            if (! isset($map[$key])) {
                throw new \InvalidArgumentException('Unsupported import step: '.$step);
            }

            $normalizedStep = $map[$key];
            if (! in_array($normalizedStep, $normalized, true)) {
                $normalized[] = $normalizedStep;
            }
        }

        return $normalized;
    }

    private function stepLabel(string $step): string
    {
        return match ($step) {
            'categories' => 'Categories',
            'subcategories' => 'Subcategories',
            'brands' => 'Brands',
            'series' => 'Series',
            'products' => 'Products',
            'attributes' => 'Attributes',
            default => ucfirst($step),
        };
    }
}
