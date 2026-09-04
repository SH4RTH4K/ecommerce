<?php

namespace App\Services;

use App\Category;
use App\StorefrontNavbarItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StorefrontNavbarService
{
    public const TREE_CACHE_KEY = 'storefront-navbar-tree';
    public const LAYOUT_CACHE_KEY = 'storefront-navbar-layout';
    public const LAYOUT_SETTING_KEY = 'storefront_navbar_layout';

    public function defaultLayout(): array
    {
        return [
            'enabled' => true,
            'alignment' => 'LEFT',
            'row_alignment' => 'LEFT',
            'max_rows' => 1,
            'row_mode' => 'SINGLE_ROW',
            'overflow_behavior' => 'HORIZONTAL_SCROLL',
            'container_mode' => 'CONTENT_WIDTH',
            'custom_max_width' => 1320,
            'minimum_height' => 42,
            'item_gap' => 0,
            'row_gap' => 0,
            'padding_x' => 8,
            'padding_y' => 8,
            'item_padding_x' => 8,
            'item_padding_y' => 8,
            'font_size_desktop' => 14,
            'font_size_tablet' => 13,
            'font_size_mobile' => 14,
            'font_weight' => 600,
            'item_text_alignment' => 'CENTER',
            'item_width_mode' => 'AUTO',
            'minimum_item_width' => 80,
            'label_wrap' => 'NO_WRAP',
            'sticky' => false,
            'desktop_enabled' => true,
            'tablet_mode' => 'SAME_AS_DESKTOP',
            'tablet_alignment' => 'LEFT',
            'tablet_max_rows' => 1,
            'tablet_item_gap' => 0,
            'tablet_overflow_behavior' => 'HORIZONTAL_SCROLL',
            'mobile_mode' => 'OFF_CANVAS',
            'mobile_alignment' => 'LEFT',
            'mobile_subcategory_display' => 'ACCORDION',
            'dropdown_alignment' => 'AUTO',
            'dropdown_width_mode' => 'AUTO',
            'dropdown_width' => 260,
            'bottom_border' => true,
            'border_width' => 1,
            'shadow_style' => 'SUBTLE',
            'active_indicator_style' => 'UNDERLINE',
            'hover_style' => 'TEXT_UNDERLINE',
            'border_radius' => 0,
            'item_radius' => 0,
        ];
    }

    public function layout(): array
    {
        return Cache::remember(self::LAYOUT_CACHE_KEY, now()->addHours(6), function () {
            if (! Schema::hasTable('site_settings')) return $this->defaultLayout();

            $raw = DB::table('site_settings')
                ->where('setting_key', self::LAYOUT_SETTING_KEY)
                ->value('setting_value');
            $decoded = json_decode((string) $raw, true);

            return $this->normalizeLayout(is_array($decoded) ? $decoded : []);
        });
    }

    public function normalizeLayout(array $input): array
    {
        $defaults = $this->defaultLayout();

        // Older saved layouts used the outer navbar padding for item padding.
        // Carry those values forward until the administrator changes them.
        if (! array_key_exists('item_padding_x', $input)) {
            $input['item_padding_x'] = $input['padding_x'] ?? $defaults['item_padding_x'];
        }
        if (! array_key_exists('item_padding_y', $input)) {
            $input['item_padding_y'] = $input['padding_y'] ?? $defaults['item_padding_y'];
        }
        if (! array_key_exists('row_gap', $input)) {
            $input['row_gap'] = $input['item_gap'] ?? $defaults['row_gap'];
        }

        $layout = array_replace($defaults, $input);
        $enum = function ($key, array $allowed) use (&$layout, $defaults) {
            $value = strtoupper(trim((string) ($layout[$key] ?? '')));
            $layout[$key] = in_array($value, $allowed, true) ? $value : $defaults[$key];
        };
        $integer = function ($key, int $min, int $max) use (&$layout, $defaults) {
            $value = filter_var($layout[$key] ?? null, FILTER_VALIDATE_INT);
            $layout[$key] = $value === false ? $defaults[$key] : max($min, min($max, (int) $value));
        };
        $boolean = function ($key) use (&$layout) {
            $layout[$key] = filter_var($layout[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        };

        foreach (['enabled', 'sticky', 'desktop_enabled', 'bottom_border'] as $key) $boolean($key);
        $enum('alignment', ['LEFT', 'CENTER', 'RIGHT', 'SPACE_BETWEEN', 'SPACE_AROUND', 'SPACE_EVENLY']);
        $enum('row_alignment', ['LEFT', 'CENTER', 'RIGHT', 'SPACE_BETWEEN', 'SPACE_AROUND', 'SPACE_EVENLY']);
        $enum('max_rows', ['1', '2', '3', 'AUTO']);
        $layout['max_rows'] = $layout['max_rows'] === 'AUTO' ? 'AUTO' : (int) $layout['max_rows'];
        $enum('row_mode', ['SINGLE_ROW', 'WRAP', 'AUTO']);
        $enum('overflow_behavior', ['HORIZONTAL_SCROLL', 'REDUCE_SPACING', 'REDUCE_FONT_WITHIN_LIMIT', 'ALLOW_EXTRA_ROW', 'COMPACT_ITEMS']);
        $enum('container_mode', ['FULL_WIDTH', 'CONTENT_WIDTH', 'CUSTOM_MAX_WIDTH']);
        $enum('font_weight', ['400', '500', '600', '700']);
        $layout['font_weight'] = (int) $layout['font_weight'];
        $enum('item_text_alignment', ['LEFT', 'CENTER', 'RIGHT']);
        $enum('item_width_mode', ['AUTO', 'EQUAL_WIDTH']);
        $enum('label_wrap', ['NO_WRAP', 'ALLOW_WRAP']);
        $enum('tablet_mode', ['SAME_AS_DESKTOP', 'CUSTOM']);
        $enum('tablet_alignment', ['LEFT', 'CENTER', 'RIGHT', 'SPACE_BETWEEN', 'SPACE_AROUND', 'SPACE_EVENLY']);
        $layout['tablet_max_rows'] = strtoupper((string) $layout['tablet_max_rows']) === 'AUTO' ? 'AUTO' : max(1, min(3, (int) $layout['tablet_max_rows']));
        $enum('tablet_overflow_behavior', ['HORIZONTAL_SCROLL', 'REDUCE_SPACING', 'REDUCE_FONT_WITHIN_LIMIT', 'ALLOW_EXTRA_ROW', 'COMPACT_ITEMS']);
        $enum('mobile_mode', ['OFF_CANVAS', 'DROPDOWN', 'FULL_SCREEN']);
        $enum('mobile_alignment', ['LEFT', 'CENTER', 'RIGHT']);
        $enum('mobile_subcategory_display', ['ACCORDION', 'EXPANDED']);
        $enum('dropdown_alignment', ['AUTO', 'LEFT', 'CENTER', 'RIGHT']);
        $enum('dropdown_width_mode', ['AUTO', 'FIXED', 'MEGA_MENU_WIDTH']);
        $enum('shadow_style', ['NONE', 'SUBTLE', 'MEDIUM']);
        $enum('active_indicator_style', ['NONE', 'UNDERLINE', 'BOTTOM_BORDER', 'BACKGROUND', 'TEXT_ONLY']);
        $enum('hover_style', ['TEXT', 'UNDERLINE', 'BACKGROUND', 'TEXT_UNDERLINE']);
        foreach ([
            ['custom_max_width', 800, 1800], ['minimum_height', 32, 80], ['item_gap', 0, 40],
            ['row_gap', 0, 40],
            ['padding_x', 0, 30], ['padding_y', 0, 30], ['item_padding_x', 0, 40],
            ['item_padding_y', 0, 30], ['font_size_desktop', 11, 24],
            ['font_size_tablet', 10, 22], ['font_size_mobile', 12, 24], ['minimum_item_width', 50, 240],
            ['tablet_item_gap', 0, 40], ['dropdown_width', 200, 500], ['border_width', 0, 4],
            ['border_radius', 0, 12], ['item_radius', 0, 12],
        ] as [$key, $min, $max]) $integer($key, $min, $max);

        return $layout;
    }

    public function saveLayout(array $layout, $adminId = null): void
    {
        $normalized = $this->normalizeLayout($layout);
        DB::table('site_settings')->updateOrInsert(
            ['setting_key' => self::LAYOUT_SETTING_KEY],
            ['setting_value' => json_encode($normalized), 'updated_at' => now(), 'created_at' => now()]
        );
        $this->clear();
    }

    public function resetLayout(): void
    {
        $this->saveLayout($this->defaultLayout());
    }

    public function categoryTree()
    {
        return Cache::remember('mega-menu-tree', now()->addHours(6), function () {
            return Category::with('subCategories')
                ->withCount(['products as published_products_count' => function ($query) {
                    $query->where('publication_status', 1);
                }])
                ->where('publication_status', 1)
                ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 999999 ELSE display_order END')
                ->orderBy('category_name')
                ->get();
        });
    }

    public function storefront()
    {
        $items = Cache::remember(self::TREE_CACHE_KEY, now()->addHours(6), function () {
            $items = StorefrontNavbarItem::with('category.navbarSubCategories')
                ->where('show_in_navbar', 1)
                ->whereHas('category', function ($query) {
                    $query->where('publication_status', 1);
                })
                ->orderBy('priority')
                ->orderBy('id')
                ->get();

            $this->attachManufacturers($items);
            return $items;
        });

        return [
            'items' => $items,
            'context' => $this->activeContext(),
            'layout' => $this->layout(),
        ];
    }

    public function adminItems()
    {
        $categories = Category::withTrashed()
            ->whereNull('deleted_at')
            ->with('navbarManageSubCategories')
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 999999 ELSE display_order END')
            ->orderBy('category_name')
            ->get();
        $configured = StorefrontNavbarItem::with('category')->get()->keyBy('category_id');
        $nextPriority = ((int) $configured->max('priority')) + 10;

        return $categories->map(function ($category) use ($configured, &$nextPriority) {
            $item = $configured->get($category->category_id);
            if (! $item) {
                $item = new StorefrontNavbarItem([
                    'category_id' => $category->category_id,
                    'display_name' => null,
                    'show_in_navbar' => false,
                    'show_subcategories' => true,
                    'priority' => $nextPriority,
                ]);
                $item->setAttribute('is_configured', false);
                $nextPriority += 10;
            } else {
                $item->setAttribute('is_configured', true);
            }
            $item->setRelation('category', $category);
            return $item;
        })->sortBy(function ($item) {
            return [(int) $item->priority, (int) optional($item->category)->category_id];
        })->values();
    }

    public function resetDefaults($adminId = null)
    {
        $categories = Category::withTrashed()
            ->whereNull('deleted_at')
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 999999 ELSE display_order END')
            ->orderBy('category_name')
            ->get(['category_id']);
        $visibleByDefault = [];
        if (Schema::hasColumn('category', 'show_in_navigation')) {
            $visibleByDefault = DB::table('category')->where('show_in_navigation', 1)->pluck('category_id')->all();
        }

        foreach ($categories as $index => $category) {
            $show = ! Schema::hasColumn('category', 'show_in_navigation') || in_array($category->category_id, $visibleByDefault, true);
            StorefrontNavbarItem::updateOrCreate(
                ['category_id' => $category->category_id],
                [
                    'display_name' => null,
                    'show_in_navbar' => $show,
                    'priority' => ($index + 1) * 10,
                    'show_subcategories' => true,
                    'updated_by' => $adminId,
                ]
            );
        }

        $this->clear();
    }

    public function clear()
    {
        Cache::forget(self::TREE_CACHE_KEY);
        Cache::forget(self::LAYOUT_CACHE_KEY);
        Cache::forget('mega-menu-tree');
        Cache::forget('site-settings');
    }

    private function attachManufacturers(Collection $items)
    {
        $subCategories = $items->flatMap(function ($item) {
            return $item->category ? $item->category->navbarSubCategories : collect();
        })->filter()->unique('sub_category_id')->values();
        $subCategoryIds = $subCategories->pluck('sub_category_id')->filter()->unique()->values();

        if ($subCategoryIds->isEmpty()) return;

        // Older restored product data may still point to a standalone category
        // such as ROUTER instead of the newer Router subcategory.
        $legacyCategoryMap = DB::table('category')
            ->whereIn('category_name', $subCategories->pluck('sub_category_name')->all())
            ->get(['category_id', 'category_name'])
            ->mapWithKeys(function ($category) use ($subCategories) {
                $target = $subCategories->first(function ($subCategory) use ($category) {
                    return strcasecmp((string) $subCategory->sub_category_name, (string) $category->category_name) === 0;
                });

                return $target ? [(int) $category->category_id => (int) $target->sub_category_id] : [];
            });

        $manufacturersBySubCategory = DB::table('product as p')
            ->join('manufacturer as m', 'm.manufacturer_id', '=', 'p.manufacturer_id')
            ->whereNull('p.deleted_at')
            ->where('p.publication_status', 1)
            ->where('m.publication_status', 1)
            ->where(function ($query) use ($subCategoryIds, $legacyCategoryMap) {
                $query->whereIn('p.sub_category', $subCategoryIds->all());
                if ($legacyCategoryMap->isNotEmpty()) {
                    $query->orWhereIn('p.category_id', $legacyCategoryMap->keys()->all());
                }
            })
            ->select('p.sub_category', 'p.category_id', 'm.manufacturer_id', 'm.manufacturer_name')
            ->distinct()
            ->orderBy('m.manufacturer_name')
            ->get()
            ->map(function ($manufacturer) use ($subCategoryIds, $legacyCategoryMap) {
                $manufacturer->menu_sub_category_id = $subCategoryIds->contains($manufacturer->sub_category)
                    ? (int) $manufacturer->sub_category
                    : ($legacyCategoryMap->get((int) $manufacturer->category_id));
                return $manufacturer;
            })
            ->filter(fn ($manufacturer) => $manufacturer->menu_sub_category_id)
            ->groupBy('menu_sub_category_id');

        $items->each(function ($item) use ($manufacturersBySubCategory) {
            if (! $item->category) return;
            $item->category->navbarSubCategories->each(function ($subCategory) use ($manufacturersBySubCategory) {
                $subCategory->menuManufacturers = $manufacturersBySubCategory
                    ->get($subCategory->sub_category_id, collect())
                    ->values();
            });
        });
    }

    private function activeContext()
    {
        $context = ['category_id' => null, 'sub_category_id' => null];
        $route = request()->route();
        $routeName = $route ? $route->getName() : null;
        $id = $route ? (int) $route->parameter('id') : 0;

        if ($routeName === 'store.category.show' || request()->is('product-by-category/*')) {
            $context['category_id'] = $id ?: null;
        } elseif (request()->is('product-by-sub-category/*')) {
            $subCategory = DB::table('sub_category')
                ->where('sub_category_id', $id)
                ->whereNull('deleted_at')
                ->first(['sub_category_id', 'category_id']);
            if ($subCategory) {
                $context['category_id'] = (int) $subCategory->category_id;
                $context['sub_category_id'] = (int) $subCategory->sub_category_id;
            }
        } elseif ($routeName === 'store.product.show' || request()->is('product-details/*')) {
            $product = DB::table('product')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first(['category_id', 'sub_category']);
            if ($product) {
                $context['category_id'] = (int) $product->category_id;
                $context['sub_category_id'] = is_numeric($product->sub_category) ? (int) $product->sub_category : null;
            }
        }

        return $context;
    }
}
