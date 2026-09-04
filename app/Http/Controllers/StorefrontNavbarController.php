<?php

namespace App\Http\Controllers;

use App\Category;
use App\Services\StorefrontNavbarService;
use App\StorefrontNavbarItem;
use App\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorefrontNavbarController extends Controller
{
    public function index(StorefrontNavbarService $service)
    {
        $content = view('admin.admin-pages.storefront-navbar', [
            'items' => $service->adminItems(),
            'layout' => $service->layout(),
        ]);

        return view('admin.admin-master')->with('admin_main_content', $content);
    }

    public function save(Request $request, StorefrontNavbarService $service)
    {
        // Keep older integrations that only submit item settings compatible without
        // unexpectedly replacing an administrator's saved design choices.
        if (! $request->has('layout')) {
            $request->merge(['layout' => $service->layout()]);
        }

        $this->validate($request, [
            'items' => 'required|array',
            'items.*.display_name' => 'nullable|string|max:255',
            'items.*.show_in_navbar' => 'nullable|boolean',
            'items.*.show_subcategories' => 'nullable|boolean',
            'items.*.priority' => 'required|integer|min:0|max:999999',
            'subcategories' => 'nullable|array',
            'subcategories.*' => 'nullable|array',
            'subcategories.*.*.navbar_name' => 'nullable|string|max:255',
            'subcategories.*.*.show_in_navbar' => 'nullable|boolean',
            'subcategories.*.*.navbar_order' => 'required|integer|min:0|max:999999',
            'layout' => 'required|array',
            'layout.enabled' => 'nullable|boolean',
            'layout.alignment' => 'required|in:LEFT,CENTER,RIGHT,SPACE_BETWEEN,SPACE_AROUND,SPACE_EVENLY',
            'layout.row_alignment' => 'required|in:LEFT,CENTER,RIGHT,SPACE_BETWEEN,SPACE_AROUND,SPACE_EVENLY',
            'layout.max_rows' => 'required|in:1,2,3,AUTO',
            'layout.row_mode' => 'required|in:SINGLE_ROW,WRAP,AUTO',
            'layout.overflow_behavior' => 'required|in:HORIZONTAL_SCROLL,REDUCE_SPACING,REDUCE_FONT_WITHIN_LIMIT,ALLOW_EXTRA_ROW,COMPACT_ITEMS',
            'layout.container_mode' => 'required|in:FULL_WIDTH,CONTENT_WIDTH,CUSTOM_MAX_WIDTH',
            'layout.custom_max_width' => 'required|integer|min:800|max:1800',
            'layout.minimum_height' => 'required|integer|min:32|max:80',
            'layout.item_gap' => 'required|integer|min:0|max:40',
            'layout.row_gap' => 'nullable|integer|min:0|max:40',
            'layout.padding_x' => 'required|integer|min:0|max:30',
            'layout.padding_y' => 'required|integer|min:0|max:30',
            'layout.item_padding_x' => 'nullable|integer|min:0|max:40',
            'layout.item_padding_y' => 'nullable|integer|min:0|max:30',
            'layout.font_size_desktop' => 'required|integer|min:11|max:24',
            'layout.font_size_tablet' => 'required|integer|min:10|max:22',
            'layout.font_size_mobile' => 'required|integer|min:12|max:24',
            'layout.font_weight' => 'required|in:400,500,600,700',
            'layout.item_text_alignment' => 'required|in:LEFT,CENTER,RIGHT',
            'layout.item_width_mode' => 'required|in:AUTO,EQUAL_WIDTH',
            'layout.minimum_item_width' => 'required|integer|min:50|max:240',
            'layout.label_wrap' => 'required|in:NO_WRAP,ALLOW_WRAP',
            'layout.sticky' => 'nullable|boolean',
            'layout.desktop_enabled' => 'nullable|boolean',
            'layout.tablet_mode' => 'required|in:SAME_AS_DESKTOP,CUSTOM',
            'layout.tablet_alignment' => 'required|in:LEFT,CENTER,RIGHT,SPACE_BETWEEN,SPACE_AROUND,SPACE_EVENLY',
            'layout.tablet_max_rows' => 'required|in:1,2,3,AUTO',
            'layout.tablet_item_gap' => 'required|integer|min:0|max:40',
            'layout.tablet_overflow_behavior' => 'required|in:HORIZONTAL_SCROLL,REDUCE_SPACING,REDUCE_FONT_WITHIN_LIMIT,ALLOW_EXTRA_ROW,COMPACT_ITEMS',
            'layout.mobile_mode' => 'required|in:OFF_CANVAS,DROPDOWN,FULL_SCREEN',
            'layout.mobile_alignment' => 'required|in:LEFT,CENTER,RIGHT',
            'layout.mobile_subcategory_display' => 'required|in:ACCORDION,EXPANDED',
            'layout.dropdown_alignment' => 'required|in:AUTO,LEFT,CENTER,RIGHT',
            'layout.dropdown_width_mode' => 'required|in:AUTO,FIXED,MEGA_MENU_WIDTH',
            'layout.dropdown_width' => 'required|integer|min:200|max:500',
            'layout.bottom_border' => 'nullable|boolean',
            'layout.border_width' => 'required|integer|min:0|max:4',
            'layout.shadow_style' => 'required|in:NONE,SUBTLE,MEDIUM',
            'layout.active_indicator_style' => 'required|in:NONE,UNDERLINE,BOTTOM_BORDER,BACKGROUND,TEXT_ONLY',
            'layout.hover_style' => 'required|in:TEXT,UNDERLINE,BACKGROUND,TEXT_UNDERLINE',
            'layout.border_radius' => 'required|integer|min:0|max:12',
            'layout.item_radius' => 'required|integer|min:0|max:12',
        ]);

        $categoryIds = Category::whereIn('category_id', array_map('intval', array_keys($request->input('items', []))))
            ->pluck('category_id')->map(fn ($id) => (int) $id)->all();

        $layout = $service->normalizeLayout((array) $request->input('layout', []));

        DB::transaction(function () use ($request, $categoryIds, $layout) {
            foreach ($categoryIds as $categoryId) {
                $data = (array) $request->input('items.'.$categoryId, []);
                StorefrontNavbarItem::updateOrCreate(
                    ['category_id' => $categoryId],
                    [
                        'display_name' => trim((string) ($data['display_name'] ?? '')) ?: null,
                        'show_in_navbar' => ! empty($data['show_in_navbar']),
                        'show_subcategories' => ! empty($data['show_subcategories']),
                        'priority' => (int) ($data['priority'] ?? 0),
                        'created_by' => session('admin_id'),
                        'updated_by' => session('admin_id'),
                    ]
                );
            }

            foreach ((array) $request->input('subcategories', []) as $categoryId => $subcategories) {
                foreach ((array) $subcategories as $subCategoryId => $data) {
                    $subCategory = SubCategory::where('sub_category_id', (int) $subCategoryId)
                        ->where('category_id', (int) $categoryId)
                        ->first();
                    if (! $subCategory) continue;

                    $subCategory->update([
                        'navbar_name' => trim((string) ($data['navbar_name'] ?? '')) ?: null,
                        'show_in_navbar' => ! empty($data['show_in_navbar']),
                        'navbar_order' => (int) ($data['navbar_order'] ?? 0),
                    ]);
                }
            }

            DB::table('site_settings')->updateOrInsert(
                ['setting_key' => StorefrontNavbarService::LAYOUT_SETTING_KEY],
                ['setting_value' => json_encode($layout), 'created_at' => now(), 'updated_at' => now()]
            );
        });

        $service->clear();
        return redirect()->route('admin.storefront-navbar.index')->with('message', 'Navbar changes saved.');
    }

    public function reset(StorefrontNavbarService $service)
    {
        $service->resetDefaults(session('admin_id'));
        $service->resetLayout();
        return redirect()->route('admin.storefront-navbar.index')->with('message', 'Navbar reset to the catalog default order.');
    }

    public function resetItems(StorefrontNavbarService $service)
    {
        $service->resetDefaults(session('admin_id'));
        return redirect()->route('admin.storefront-navbar.index')->with('message', 'Navbar items reset. Design settings were preserved.');
    }

    public function resetDesign(StorefrontNavbarService $service)
    {
        $service->resetLayout();
        return redirect()->route('admin.storefront-navbar.index')->with('message', 'Navbar design reset. Item settings were preserved.');
    }
}
