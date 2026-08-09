<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManufacturerProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manufacturer_products_page_supplies_card_price_fields(): void
    {
        $manufacturerId = DB::table('manufacturer')->insertGetId([
            'manufacturer_name' => 'Test Brand',
            'publication_status' => 1,
        ]);

        DB::table('product')->insert([
            'product_id' => 'TEST-BRAND-1',
            'product_name' => 'Test product',
            'product_model' => 'TB-1',
            'manufacturer_id' => $manufacturerId,
            'regular_price' => 1000,
            'offer_price' => 900,
            'publication_status' => 1,
            'product_condition' => 'In Stock',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/all-manufacturer-by-id/'.$manufacturerId)
            ->assertOk()
            ->assertSee('Test product')
            ->assertSee('900');
    }
}
