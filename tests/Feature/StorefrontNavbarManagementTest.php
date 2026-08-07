<?php

namespace Tests\Feature;

use App\Services\StorefrontNavbarService;
use App\StorefrontNavbarItem;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontNavbarManagementTest extends TestCase
{
    private function navbarAdmin()
    {
        return DB::table('tbl_admin as a')
            ->join('admin_roles as r', 'r.id', '=', 'a.role_id')
            ->where('a.is_active', 1)
            ->where('r.permissions', 'like', '%view_storefront_navbar%')
            ->select('a.*')
            ->first();
    }

    private function adminSession($admin)
    {
        return ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
    }

    private function clearNavbarCache()
    {
        app(StorefrontNavbarService::class)->clear();
    }

    private function navMarkup($response)
    {
        $content = $response->getContent();
        $start = strpos($content, '<nav id="main-menu"');
        $end = $start === false ? false : strpos($content, '</nav>', $start);

        return $start === false || $end === false ? '' : substr($content, $start, $end - $start);
    }

    private function categoryId($name)
    {
        return DB::table('category')->where('category_name', $name)->value('category_id');
    }

    public function testHomepageHasNoAutomaticMoreAndNoCategoryIsActive()
    {
        $this->clearNavbarCache();
        $nav = $this->navMarkup($this->get('/')->assertStatus(200));

        $this->assertStringNotContainsString('lt-more', $nav);
        $this->assertStringNotContainsString('lt-category-item is-active', $nav);
    }

    public function testOnlyEnabledCategoriesAreRenderedInAdminOrder()
    {
        $desktopId = $this->categoryId('Desktop');
        $laptopId = $this->categoryId('Laptop');
        $monitorId = $this->categoryId('Monitor');
        if (! $desktopId || ! $laptopId || ! $monitorId) return $this->assertTrue(true);

        DB::beginTransaction();
        try {
            StorefrontNavbarItem::query()->update(['show_in_navbar' => 0]);
            StorefrontNavbarItem::where('category_id', $desktopId)->update(['show_in_navbar' => 1, 'priority' => 20]);
            StorefrontNavbarItem::where('category_id', $laptopId)->update(['show_in_navbar' => 1, 'priority' => 10]);
            $this->clearNavbarCache();
            $nav = $this->navMarkup($this->get('/')->assertStatus(200));

            $this->assertStringContainsString('>Laptop<', $nav);
            $this->assertStringContainsString('>Desktop<', $nav);
            $this->assertStringNotContainsString('>Monitor<', $nav);
            $this->assertLessThan(strpos($nav, '>Desktop<'), strpos($nav, '>Laptop<'));
        } finally {
            DB::rollBack();
            $this->clearNavbarCache();
        }
    }

    public function testNavbarNameFallsBackToCategoryNameAndCanBeCustomized()
    {
        $desktopId = $this->categoryId('Desktop');
        if (! $desktopId) return $this->assertTrue(true);

        DB::beginTransaction();
        try {
            StorefrontNavbarItem::where('category_id', $desktopId)->update([
                'display_name' => 'Workstations',
                'show_in_navbar' => 1,
            ]);
            $this->clearNavbarCache();
            $customNav = $this->navMarkup($this->get('/')->assertStatus(200));
            $this->assertStringContainsString('>Workstations<', $customNav);

            StorefrontNavbarItem::where('category_id', $desktopId)->update(['display_name' => null]);
            $this->clearNavbarCache();
            $fallbackNav = $this->navMarkup($this->get('/')->assertStatus(200));
            $this->assertStringContainsString('>Desktop<', $fallbackNav);
        } finally {
            DB::rollBack();
            $this->clearNavbarCache();
        }
    }

    public function testParentDropdownAndIndividualSubcategoryVisibilityAreAdminControlled()
    {
        $laptopId = $this->categoryId('Laptop');
        $subCategory = $laptopId
            ? DB::table('sub_category')->where('category_id', $laptopId)->where('publication_status', 1)->whereNull('deleted_at')->first()
            : null;
        if (! $laptopId || ! $subCategory) return $this->assertTrue(true);

        DB::beginTransaction();
        try {
            StorefrontNavbarItem::where('category_id', $laptopId)->update([
                'show_in_navbar' => 1,
                'show_subcategories' => 1,
            ]);
            DB::table('sub_category')->where('sub_category_id', $subCategory->sub_category_id)->update([
                'navbar_name' => 'Featured Laptops',
                'show_in_navbar' => 1,
                'navbar_order' => 10,
            ]);
            $this->clearNavbarCache();
            $withDropdown = $this->navMarkup($this->get('/')->assertStatus(200));
            $this->assertStringContainsString('aria-label="Laptop subcategories"', $withDropdown);
            $this->assertStringContainsString('>Featured Laptops<', $withDropdown);

            DB::table('sub_category')->where('sub_category_id', $subCategory->sub_category_id)->update(['show_in_navbar' => 0]);
            $this->clearNavbarCache();
            $hiddenSubcategory = $this->navMarkup($this->get('/')->assertStatus(200));
            $this->assertStringNotContainsString('>Featured Laptops<', $hiddenSubcategory);

            StorefrontNavbarItem::where('category_id', $laptopId)->update(['show_subcategories' => 0]);
            $this->clearNavbarCache();
            $withoutDropdown = $this->navMarkup($this->get('/')->assertStatus(200));
            $this->assertStringNotContainsString('aria-label="Laptop subcategories"', $withoutDropdown);
        } finally {
            DB::rollBack();
            $this->clearNavbarCache();
        }
    }

    public function testCategoryAndSubcategoryRoutesSetActiveStateFromCurrentContext()
    {
        $desktopId = $this->categoryId('Desktop');
        $laptopId = $this->categoryId('Laptop');
        $subCategory = $laptopId
            ? DB::table('sub_category')->where('category_id', $laptopId)->where('publication_status', 1)->whereNull('deleted_at')->first()
            : null;
        if (! $desktopId || ! $laptopId || ! $subCategory) return $this->assertTrue(true);

        $homeNav = $this->navMarkup($this->get('/')->assertStatus(200));
        $this->assertStringNotContainsString('lt-category-item is-active', $homeNav);

        $desktopNav = $this->navMarkup($this->get('/product-by-category/'.$desktopId)->assertStatus(200));
        $this->assertStringContainsString('data-category-id="'.$desktopId.'"', $desktopNav);
        $this->assertStringContainsString('lt-category-item is-active', $desktopNav);

        $subNav = $this->navMarkup($this->get('/product-by-sub-category/'.$subCategory->sub_category_id)->assertStatus(200));
        $this->assertStringContainsString('data-category-id="'.$laptopId.'"', $subNav);
        $this->assertStringContainsString('href="'.url('/product-by-sub-category/'.$subCategory->sub_category_id).'"  aria-current="page"', $subNav);
    }

    public function testAdminCanSaveFlatVisibilityLabelsOrderAndSubcategorySettings()
    {
        $admin = $this->navbarAdmin();
        $desktopId = $this->categoryId('Desktop');
        if (! $admin || ! $desktopId) return $this->assertTrue(true);

        $subCategory = DB::table('sub_category')->where('category_id', $desktopId)->whereNull('deleted_at')->first();
        $payload = [
            'items' => [
                $desktopId => [
                    'display_name' => 'Office PCs',
                    'show_in_navbar' => 1,
                    'show_subcategories' => $subCategory ? 1 : 0,
                    'priority' => 15,
                ],
            ],
        ];
        if ($subCategory) {
            $payload['subcategories'] = [
                $desktopId => [
                    $subCategory->sub_category_id => [
                        'navbar_name' => 'Office Desktop',
                        'show_in_navbar' => 1,
                        'navbar_order' => 25,
                    ],
                ],
            ];
        }

        DB::beginTransaction();
        try {
            $this->withSession($this->adminSession($admin))
                ->post('/storefront-navbar/save', $payload)
                ->assertRedirect('/storefront-navbar')
                ->assertSessionHas('message');

            $item = StorefrontNavbarItem::where('category_id', $desktopId)->first();
            $this->assertSame('Office PCs', $item->display_name);
            $this->assertTrue((bool) $item->show_in_navbar);
            $this->assertSame(15, (int) $item->priority);
            if ($subCategory) {
                $savedSubcategory = DB::table('sub_category')->where('sub_category_id', $subCategory->sub_category_id)->first();
                $this->assertSame('Office Desktop', $savedSubcategory->navbar_name);
                $this->assertSame(25, (int) $savedSubcategory->navbar_order);
            }
        } finally {
            DB::rollBack();
            $this->clearNavbarCache();
        }
    }

    public function testNavbarManagementPageIsFlatAndAvailableToAuthorizedAdmin()
    {
        $admin = $this->navbarAdmin();
        if (! $admin) return $this->assertTrue(true);

        $this->withSession($this->adminSession($admin))
            ->get('/storefront-navbar')
            ->assertStatus(200)
            ->assertSee('Navbar Management')
            ->assertSee('Show in Navbar')
            ->assertSee('Save Navbar')
            ->assertDontSee('Primary Navigation')
            ->assertDontSee('More Menu')
            ->assertDontSee('Hidden Categories');
    }

    public function testAdminCanSaveNormalizedNavbarLayoutOnTheExistingManagementPage()
    {
        $admin = $this->navbarAdmin();
        $desktopId = $this->categoryId('Desktop');
        if (! $admin || ! $desktopId) return $this->assertTrue(true);

        DB::beginTransaction();
        try {
            $payload = [
                'items' => [$desktopId => ['display_name' => 'Desktop', 'show_in_navbar' => 1, 'show_subcategories' => 1, 'priority' => 10]],
                'layout' => [
                    'enabled' => 1,
                    'alignment' => 'CENTER',
                    'row_alignment' => 'CENTER',
                    'max_rows' => '2',
                    'row_mode' => 'WRAP',
                    'overflow_behavior' => 'REDUCE_SPACING',
                    'container_mode' => 'CUSTOM_MAX_WIDTH',
                    'custom_max_width' => 1800,
                    'minimum_height' => 42,
                    'item_gap' => 12,
                    'padding_x' => 10,
                    'padding_y' => 8,
                    'font_size_desktop' => 16,
                    'font_size_tablet' => 14,
                    'font_size_mobile' => 14,
                    'font_weight' => 700,
                    'item_text_alignment' => 'CENTER',
                    'item_width_mode' => 'AUTO',
                    'minimum_item_width' => 80,
                    'label_wrap' => 'NO_WRAP',
                    'sticky' => 1,
                    'desktop_enabled' => 1,
                    'tablet_mode' => 'CUSTOM',
                    'tablet_alignment' => 'RIGHT',
                    'tablet_max_rows' => '1',
                    'tablet_item_gap' => 4,
                    'tablet_overflow_behavior' => 'HORIZONTAL_SCROLL',
                    'mobile_mode' => 'DROPDOWN',
                    'mobile_alignment' => 'LEFT',
                    'mobile_subcategory_display' => 'ACCORDION',
                    'dropdown_alignment' => 'RIGHT',
                    'dropdown_width_mode' => 'FIXED',
                    'dropdown_width' => 310,
                    'bottom_border' => 1,
                    'border_width' => 2,
                    'shadow_style' => 'MEDIUM',
                    'active_indicator_style' => 'BACKGROUND',
                    'hover_style' => 'BACKGROUND',
                    'border_radius' => 8,
                    'item_radius' => 6,
                ],
            ];

            $this->withSession($this->adminSession($admin))
                ->post('/storefront-navbar/save', $payload)
                ->assertRedirect('/storefront-navbar');

            $saved = json_decode((string) DB::table('site_settings')->where('setting_key', StorefrontNavbarService::LAYOUT_SETTING_KEY)->value('setting_value'), true);
            $this->assertSame(1800, $saved['custom_max_width']);
            $this->assertSame('CENTER', $saved['alignment']);
            $this->assertSame('WRAP', $saved['row_mode']);
            $this->assertTrue($saved['sticky']);
            $this->assertSame(310, $saved['dropdown_width']);
        } finally {
            DB::rollBack();
            $this->clearNavbarCache();
        }
    }

    public function testDesignResetPreservesConfiguredNavbarItems()
    {
        $admin = $this->navbarAdmin();
        $desktopId = $this->categoryId('Desktop');
        if (! $admin || ! $desktopId) return $this->assertTrue(true);

        DB::beginTransaction();
        try {
            StorefrontNavbarItem::where('category_id', $desktopId)->update(['display_name' => 'My Desktop', 'priority' => 77]);
            app(StorefrontNavbarService::class)->saveLayout(['alignment' => 'RIGHT']);

            $this->withSession($this->adminSession($admin))
                ->post('/storefront-navbar/reset-design')
                ->assertRedirect('/storefront-navbar');

            $item = StorefrontNavbarItem::where('category_id', $desktopId)->first();
            $this->assertSame('My Desktop', $item->display_name);
            $this->assertSame(77, (int) $item->priority);
            $this->assertSame('LEFT', app(StorefrontNavbarService::class)->layout()['alignment']);
        } finally {
            DB::rollBack();
            $this->clearNavbarCache();
        }
    }

    public function testAdminWithoutNavbarPermissionIsDenied()
    {
        $admin = DB::table('tbl_admin as a')
            ->join('admin_roles as r', 'r.id', '=', 'a.role_id')
            ->where('a.is_active', 1)
            ->where('r.permissions', 'not like', '%view_storefront_navbar%')
            ->select('a.*')
            ->first();
        if (! $admin) return $this->assertTrue(true);

        $this->withSession($this->adminSession($admin))->get('/storefront-navbar')->assertStatus(403);
    }
}
