<?php

namespace App\Services;

use App\Banner;
use App\Category;
use App\Company;
use App\Manufacturer;
use App\PaymentMethod;
use App\Product;
use App\ProductSeries;
use App\SiteContactItem;
use App\TopAnnouncement;
use App\SubCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecycleBinService
{
    public function __construct(
        private readonly MediaLifecycleService $mediaLifecycle
    ) {
    }

    public function types(): array
    {
        return [
            'all' => 'All',
            'product' => 'Products',
            'category' => 'Categories',
            'sub_category' => 'Subcategories',
            'manufacturer' => 'Brands',
            'company' => 'Companies',
            'product_series' => 'Series',
            'banner' => 'Banners',
            'payment_method' => 'Payment methods',
            'top_announcement' => 'Top announcements',
            'site_contact_item' => 'Contact items',
        ];
    }

    public function items(?string $type = null): Collection
    {
        $type = $type ? strtolower(trim($type)) : 'all';
        $definitions = $type === 'all'
            ? $this->definitions()
            : array_intersect_key($this->definitions(), [$type => true]);

        $items = collect();
        foreach ($definitions as $key => $definition) {
            $modelClass = $definition['model'];
            if (! class_exists($modelClass)) {
                continue;
            }

            $query = $modelClass::withTrashed()->whereNotNull('deleted_at');
            $query = $query->orderByDesc('deleted_at')->orderByDesc($definition['key']);

            foreach ($query->get() as $model) {
                $items->push($this->decorate($key, $definition, $model));
            }
        }

        return $items->sortByDesc('deleted_at')->values();
    }

    public function getItem(string $type, int $id): ?array
    {
        $definition = $this->definitions()[$type] ?? null;
        if (! $definition) {
            return null;
        }

        $model = $definition['model']::withTrashed()->find($id);
        if (! $model) {
            return null;
        }

        return $this->decorate($type, $definition, $model);
    }

    public function softDelete(string $type, int $id, ?int $deletedBy = null, ?string $reason = null): Model
    {
        $definition = $this->definition($type);
        $model = $definition['model']::findOrFail($id);

        DB::transaction(function () use ($model, $deletedBy, $reason) {
            $model->forceFill([
                'deleted_by' => $deletedBy,
                'delete_reason' => $reason,
            ])->save();

            $model->delete();
        });

        return $model->refresh();
    }

    public function restore(string $type, int $id): Model
    {
        $definition = $this->definition($type);
        $model = $definition['model']::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($model) {
            $model->restore();
            if (Schema::hasColumn($model->getTable(), 'deleted_by')) {
                $model->forceFill([
                    'deleted_by' => null,
                    'delete_reason' => null,
                ])->save();
            }
        });

        return $model->refresh();
    }

    public function purge(string $type, int $id): array
    {
        $definition = $this->definition($type);
        $model = $definition['model']::withTrashed()->findOrFail($id);

        $paths = array_values(array_unique(array_filter((array) ($definition['media'] ?? fn () => [])($model))));

        DB::transaction(function () use ($definition, $model, $type) {
            if (isset($definition['purge']) && is_callable($definition['purge'])) {
                ($definition['purge'])($model);
                return;
            }

            $model->forceDelete();
        });

        return $paths;
    }

    public function bulkRestore(string $type, array $ids): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return 0;
        }

        $definition = $this->definition($type);
        $count = 0;
        DB::transaction(function () use ($definition, $ids, &$count) {
            $models = $definition['model']::withTrashed()->whereIn($definition['key'], $ids)->get();
            foreach ($models as $model) {
                if (! $model->trashed()) {
                    continue;
                }
                $model->restore();
                if (Schema::hasColumn($model->getTable(), 'deleted_by')) {
                    $model->forceFill(['deleted_by' => null, 'delete_reason' => null])->save();
                }
                $count++;
            }
        });

        return $count;
    }

    public function bulkPurge(string $type, array $ids): array
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return [];
        }

        $paths = [];
        foreach ($ids as $id) {
            $paths = array_merge($paths, $this->purge($type, $id));
        }

        return array_values(array_unique(array_filter($paths)));
    }

    public function empty(array $types = []): array
    {
        $targets = $types ? array_intersect_key($this->definitions(), array_fill_keys($types, true)) : $this->definitions();
        $paths = [];
        foreach ($targets as $type => $definition) {
            $ids = $definition['model']::onlyTrashed()->pluck($definition['key'])->all();
            foreach ($ids as $id) {
                $paths = array_merge($paths, $this->purge($type, (int) $id));
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    private function definitions(): array
    {
        return [
            'product' => [
                'label' => 'Product',
                'model' => Product::class,
                'key' => 'id',
                'name' => static function (Product $product): string {
                    return trim((string) ($product->product_name ?: $product->product_id ?: $product->sku ?: ('Product #'.$product->id)));
                },
                'reference' => static function (Product $product): string {
                    return trim((string) ($product->product_code ?: $product->sku ?: $product->product_id ?: $product->id));
                },
                'media' => fn (Product $product): array => $this->mediaLifecycle->mediaFromProduct($product),
                'purge' => function (Product $product) {
                    DB::transaction(function () use ($product) {
                        $productCode = trim((string) ($product->product_code ?: $product->sku ?: $product->product_id ?: ''));
                        if ($productCode !== '' && Schema::hasTable('product_code_histories')) {
                            DB::table('product_code_histories')->insert([
                                'configuration_id' => null,
                                'product_id' => $product->id,
                                'old_code' => $productCode,
                                'new_code' => $productCode,
                                'reason' => 'Product permanently deleted.',
                                'changed_by' => session('admin_id'),
                                'changed_at' => now(),
                            ]);
                        }

                        foreach ([
                            'product_attribute_values',
                            'product_reviews',
                            'product_questions',
                            'wishlists',
                            'stock_alerts',
                            'product_location_stock',
                            'product_variants',
                            'product_lots',
                        ] as $table) {
                            if (Schema::hasTable($table)) {
                                DB::table($table)->where('product_id', $product->id)->delete();
                            }
                        }

                        $product->forceDelete();
                    });
                },
            ],
            'category' => [
                'label' => 'Category',
                'model' => Category::class,
                'key' => 'category_id',
                'name' => static fn (Category $category): string => trim((string) ($category->category_name ?: ('Category #'.$category->category_id))),
                'reference' => static fn (Category $category): string => trim((string) ($category->category_code ?: $category->category_id)),
            ],
            'sub_category' => [
                'label' => 'Subcategory',
                'model' => SubCategory::class,
                'key' => 'sub_category_id',
                'name' => static fn (SubCategory $subCategory): string => trim((string) ($subCategory->sub_category_name ?: ('Subcategory #'.$subCategory->sub_category_id))),
                'reference' => static fn (SubCategory $subCategory): string => trim((string) ($subCategory->subcategory_code ?: $subCategory->sub_category_id)),
            ],
            'manufacturer' => [
                'label' => 'Brand',
                'model' => Manufacturer::class,
                'key' => 'manufacturer_id',
                'name' => static fn (Manufacturer $brand): string => trim((string) ($brand->manufacturer_name ?: ('Brand #'.$brand->manufacturer_id))),
                'reference' => static fn (Manufacturer $brand): string => trim((string) ($brand->brand_code ?: $brand->manufacturer_id)),
            ],
            'company' => [
                'label' => 'Company',
                'model' => Company::class,
                'key' => 'id',
                'name' => static fn (Company $company): string => trim((string) ($company->name ?: ('Company #'.$company->id))),
                'reference' => static fn (Company $company): string => trim((string) ($company->company_code ?: $company->id)),
            ],
            'product_series' => [
                'label' => 'Series',
                'model' => ProductSeries::class,
                'key' => 'id',
                'name' => static fn (ProductSeries $series): string => trim((string) ($series->name ?: ('Series #'.$series->id))),
                'reference' => static fn (ProductSeries $series): string => trim((string) ($series->series_code ?: $series->id)),
            ],
            'banner' => [
                'label' => 'Banner',
                'model' => Banner::class,
                'key' => 'id',
                'name' => static fn (Banner $banner): string => trim((string) ($banner->title ?: ('Banner #'.$banner->id))),
                'reference' => static fn (Banner $banner): string => trim((string) ($banner->banner_type ?: $banner->id)),
                'media' => fn (Banner $banner): array => $this->mediaLifecycle->mediaFromBanner($banner),
            ],
            'payment_method' => [
                'label' => 'Payment method',
                'model' => PaymentMethod::class,
                'key' => 'id',
                'name' => static fn (PaymentMethod $method): string => trim((string) ($method->name ?: ('Payment method #'.$method->id))),
                'reference' => static fn (PaymentMethod $method): string => trim((string) ($method->code ?: $method->id)),
                'media' => fn (PaymentMethod $method): array => $this->mediaLifecycle->mediaFromPaymentMethod($method),
            ],
            'top_announcement' => [
                'label' => 'Announcement',
                'model' => TopAnnouncement::class,
                'key' => 'id',
                'name' => static fn (TopAnnouncement $announcement): string => trim((string) ($announcement->title ?: Str::limit((string) $announcement->message, 48))),
                'reference' => static fn (TopAnnouncement $announcement): string => trim((string) ($announcement->announcement_type ?: $announcement->id)),
            ],
            'site_contact_item' => [
                'label' => 'Contact item',
                'model' => SiteContactItem::class,
                'key' => 'id',
                'name' => static fn (SiteContactItem $item): string => trim((string) ($item->label ?: ('Contact item #'.$item->id))),
                'reference' => static fn (SiteContactItem $item): string => trim((string) ($item->contact_type ?: $item->id)),
            ],
        ];
    }

    private function definition(string $type): array
    {
        $definition = $this->definitions()[strtolower(trim($type))] ?? null;
        abort_unless($definition, 404, 'Unknown recycle bin entity type.');

        return $definition;
    }

    private function decorate(string $type, array $definition, Model $model): array
    {
        $deletedBy = $model->deleted_by ?? null;
        $deletedByName = null;
        if ($deletedBy && Schema::hasTable('tbl_admin')) {
            $deletedByName = DB::table('tbl_admin')->where('admin_id', $deletedBy)->value('admin_name');
        }

        return [
            'entity_type' => $type,
            'entity_label' => $definition['label'],
            'id' => (int) $model->getKey(),
            'name' => (string) ($definition['name'])($model),
            'reference' => (string) ($definition['reference'])($model),
            'deleted_at' => optional($model->deleted_at)->toDateTimeString(),
            'deleted_by' => $deletedBy ? (int) $deletedBy : null,
            'deleted_by_name' => $deletedByName,
            'delete_reason' => $model->delete_reason ?? null,
            'media_paths' => array_values(array_unique(array_filter((array) (($definition['media'] ?? fn () => [])($model))))),
        ];
    }

    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
