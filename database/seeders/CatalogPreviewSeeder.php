<?php

namespace Database\Seeders;

use App\Company;
use App\Category;
use App\Manufacturer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! Category::query()->whereNull('deleted_at')->exists()) {
            Category::query()->create([
                'category_name' => 'Preview Category',
                'category_code' => $this->uniqueCode('category', 'category_code', 'PREVCAT'),
                'slug' => $this->uniqueSlug('category', 'slug', 'Preview Category'),
                'category_description' => 'Sample category created so the product code preview can render.',
                'icon_class' => 'fa-folder-open',
                'is_featured' => 0,
                'display_order' => 999,
                'publication_status' => 1,
            ]);
        }

        $company = Company::query()->firstOrCreate(
            ['name' => 'Preview Catalog Company'],
            [
                'company_code' => $this->uniqueCode('companies', 'company_code', 'PREVCO'),
                'is_active' => 1,
            ]
        );

        Manufacturer::query()->firstOrCreate(
            ['manufacturer_name' => 'Preview Brand', 'company_id' => $company->id],
            [
                'brand_code' => $this->uniqueCode('manufacturer', 'brand_code', 'PREVBR'),
                'slug' => $this->uniqueSlug('manufacturer', 'slug', 'Preview Brand', ['company_id' => $company->id]),
                'publication_status' => 1,
            ]
        );
    }

    private function uniqueCode(string $table, string $column, string $base): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]+/', '', $base) ?: $base);
        $base = substr($base, 0, 30);
        $candidate = $base;
        $suffix = 1;

        while (DB::table($table)->where($column, $candidate)->exists()) {
            $tail = (string) $suffix++;
            $candidate = substr($base, 0, max(1, 30 - strlen($tail))).$tail;
        }

        return $candidate;
    }

    private function uniqueSlug(string $table, string $column, string $name, array $where = []): string
    {
        $base = Str::slug($name) ?: 'preview-brand';
        $candidate = $base;
        $suffix = 1;

        do {
            $query = DB::table($table)->where($column, $candidate);
            foreach ($where as $key => $value) {
                $query->where($key, $value);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base.'-'.$suffix++;
        } while (true);
    }
}
