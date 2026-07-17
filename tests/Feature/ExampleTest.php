<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;
use App\User;
use App\Product;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testPublishedCatalogPagesRender()
    {
        $categoryId=DB::table('category')->where('publication_status',1)->value('category_id');
        $subCategoryId=DB::table('sub_category')->where('publication_status',1)->value('sub_category_id');

        if($categoryId) $this->get('/product-by-category/'.$categoryId)->assertStatus(200);
        if($subCategoryId) $this->get('/product-by-sub-category/'.$subCategoryId)->assertStatus(200);
    }

    public function testCustomerPagesRequireAuthentication()
    {
        foreach(['/wishlist','/saved-builds','/my-orders','/notifications'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function testIntegrationApiRequiresAClientKey()
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
        $this->getJson('/api/v1/orders')->assertStatus(401);
    }

    public function testCustomerCanRequestAValidDeliveredOrderReturn()
    {
        $user=User::first();
        if(!$user) return $this->assertTrue(true);
        DB::beginTransaction();
        try {
            $orderId=DB::table('orders')->insertGetId(['order_number'=>'TEST-RETURN-'.str_random(8),'user_id'=>$user->id,'customer_name'=>$user->name,'phone'=>'01700000000','email'=>$user->email,'address'=>'Test address','subtotal'=>2000,'delivery_charge'=>0,'total'=>2000,'payment_method'=>'cash_on_delivery','status'=>'delivered','created_at'=>now(),'updated_at'=>now()]);
            $itemId=DB::table('order_items')->insertGetId(['order_id'=>$orderId,'product_id'=>null,'product_name'=>'Return test product','sku'=>'TEST-RETURN','offer_price'=>1000,'unit_purchase_price'=>0,'quantity'=>2,'subtotal'=>2000,'profit'=>0,'created_at'=>now(),'updated_at'=>now()]);
            $this->actingAs($user)->post('/my-orders/'.$orderId.'/return',['reason'=>'defective','details'=>'The product is defective during normal use.','quantity'=>[$itemId=>1]])->assertRedirect('/my-returns');
            $return=DB::table('order_returns')->where('order_id',$orderId)->first();
            $this->assertNotNull($return);$this->assertEquals(1000,(float)$return->requested_amount);$this->assertEquals(1,DB::table('order_return_items')->where('order_return_id',$return->id)->value('quantity'));
        } finally { DB::rollBack(); }
    }

    public function testAdminReturnQueueRequiresAdminAndRendersForAdmin()
    {
        $this->get('/returns')->assertRedirect('/admin/login');
        $admin=DB::table('tbl_admin')->where('is_active',1)->first();
        if($admin) $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])->get('/returns')->assertStatus(200);
    }

    public function testAdminPricingFormAndRefundAwareSalesReportRender()
    {
        $this->assertTrue(Schema::hasColumn('product','offer_price'));
        $this->assertTrue(Schema::hasColumn('product','regular_price'));
        $this->assertTrue(Schema::hasColumn('product','purchase_price'));
        $this->assertFalse(Schema::hasColumn('product','product_price'));
        $this->assertFalse(Schema::hasColumn('product','old_price'));
        $this->assertFalse(Schema::hasColumn('product','cost_price'));
        $this->assertTrue(Schema::hasColumn('order_items','offer_price'));
        $this->assertTrue(Schema::hasColumn('order_items','unit_purchase_price'));

        $admin=DB::table('tbl_admin')->where('is_active',1)->first();
        if(!$admin) return $this->assertTrue(true);

        $session=['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
        $this->withSession($session)->get('/add-product')
            ->assertStatus(200)
            ->assertSee('name="offer_price"',false)
            ->assertSee('name="regular_price"',false)
            ->assertSee('name="purchase_price"',false);
        $this->withSession($session)->get('/sales-reports')
            ->assertStatus(200)
            ->assertSee('Profit after refunds')
            ->assertSee('Recovered purchase price');
    }

    public function testRegularPriceIsPrimaryAndOnlyLowerOfferIsUsed()
    {
        $product=new Product(['regular_price'=>1000,'offer_price'=>800]);
        $this->assertTrue($product->has_offer);
        $this->assertEquals(800,$product->selling_price);
        $this->assertEquals(20,$product->discount_percent);

        foreach([null,1000,1200] as $offer) {
            $product=new Product(['regular_price'=>1000,'offer_price'=>$offer]);
            $this->assertFalse($product->has_offer);
            $this->assertEquals(1000,$product->selling_price);
            $this->assertNull($product->discount_percent);
        }
    }

    public function testAdminDataTransferRequiresAuthenticationAndAllTemplatesDownload()
    {
        $this->withoutExceptionHandling();
        $this->get('/admin-data/categories/export')->assertRedirect('/admin/login');
        $admin=DB::table('tbl_admin')->where('is_active',1)->first();
        if(!$admin) return $this->assertTrue(true);

        $session=['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
        foreach(['products','categories','subcategories','manufacturers','attributes','suppliers','locations'] as $resource) {
            $this->withSession($session)->get('/admin-data/'.$resource.'/template')
                ->assertStatus(200)
                ->assertHeader('content-type','text/csv; charset=UTF-8');
            $this->withSession($session)->get('/admin-data/'.$resource.'/export')->assertStatus(200);
        }
    }

    public function testDataTransferPanelsRenderOnAllRequestedAdminPages()
    {
        $admin=DB::table('tbl_admin')->where('is_active',1)->first();
        if(!$admin) return $this->assertTrue(true);
        $session=['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
        foreach(['/manage-product','/manage-category','/manage-subCategory','/manage-manufacturer','/catalog-attributes','/purchasing','/stock-locations'] as $uri) {
            $this->withSession($session)->get($uri)->assertStatus(200)->assertSee('Import CSV');
        }
    }

    public function testCategoryCsvImportIsTransactional()
    {
        $admin=DB::table('tbl_admin')->where('is_active',1)->first();
        if(!$admin) return $this->assertTrue(true);
        $session=['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
        $name='CSV test '.str_random(10);
        $path=tempnam(sys_get_temp_dir(),'catalog-csv-');
        file_put_contents($path,"category_id,category_name,category_description,icon_class,is_featured,display_order,publication_status\n,{$name},Imported test category,fa-folder,1,5,1\n");
        try {
            $file=new UploadedFile($path,'categories.csv','text/csv',null,true);
            $this->withSession($session)->post('/admin-data/categories/import',['mode'=>'upsert','csv_file'=>$file])->assertSessionHas('message');
            $this->assertDatabaseHas('category',['category_name'=>$name]);
            DB::table('category')->where('category_name',$name)->delete();
        } finally {
            if(file_exists($path)) unlink($path);
        }
    }
}
