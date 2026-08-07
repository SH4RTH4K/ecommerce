<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubCategoryPageTest extends TestCase
{
    public function testPublishedSubcategoryPageRendersItsProductCards()
    {
        $subCategory = DB::table('sub_category')
            ->where('publication_status', 1)
            ->whereNull('deleted_at')
            ->orderBy('sub_category_id')
            ->first();

        if (! $subCategory) return $this->assertTrue(true);

        $this->get('/product-by-sub-category/'.$subCategory->sub_category_id)
            ->assertStatus(200)
            ->assertSee('lt-product-grid', false);
    }

    public function testSubcategoryWithProductsExposesItsBrandFlyoutInNavbar()
    {
        $subCategory = DB::table('product as p')
            ->join('sub_category as s', 's.sub_category_id', '=', 'p.sub_category')
            ->join('manufacturer as m', 'm.manufacturer_id', '=', 'p.manufacturer_id')
            ->where('p.publication_status', 1)
            ->whereNull('p.deleted_at')
            ->where('s.publication_status', 1)
            ->whereNull('s.deleted_at')
            ->where('m.publication_status', 1)
            ->orderBy('s.sub_category_id')
            ->first(['s.sub_category_id']);

        if (! $subCategory) return $this->assertTrue(true);

        $this->get('/product-by-sub-category/'.$subCategory->sub_category_id)
            ->assertStatus(200)
            ->assertSee('lt-subcategory-dropdown', false)
            ->assertSee('brands', false);
    }
}
