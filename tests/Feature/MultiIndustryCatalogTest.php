<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiIndustryCatalogTest extends TestCase
{
    private function adminSession(): array
    {
        $admin=DB::table('tbl_admin as a')->join('admin_roles as r','r.id','=','a.role_id')->where('a.is_active',1)->where('r.permissions','like','%"catalog"%')->select('a.*')->first();
        $this->assertNotNull($admin);
        return ['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
    }

    public function testProductFormExplainsOptionalMultiIndustryLayers(): void
    {
        $this->withSession($this->adminSession())->get('/add-product')->assertOk()
            ->assertSee('General merchandise')->assertSee('Clothing / fashion')
            ->assertSee('Medicine / healthcare')->assertSee('No brand / not applicable')
            ->assertSee('Collection / Product Line')->assertSee('Batch / lot traceability');
    }

    public function testUnbrandedMedicineCanStoreVariantAndLotTraceability(): void
    {
        DB::beginTransaction();
        try {
            $suffix=str_random(8);
            $category=DB::table('category')->insertGetId(['category_name'=>'Test health '.$suffix,'category_description'=>'Test','publication_status'=>0,'created_at'=>now(),'updated_at'=>now()]);
            $this->withSession($this->adminSession())->post('/save-product',[
                'product_id'=>'MED-'.$suffix,'sku'=>'MED-'.$suffix,'category_id'=>$category,
                'manufacturer_id'=>'','product_series_id'=>'','industry_profile'=>'medicine',
                'product_model'=>'','product_name'=>'Test medicine '.$suffix,'product_description'=>'Test',
                'regular_price'=>100,'purchase_price'=>70,'product_condition'=>'In Stock','stock_quantity'=>20,
                'publication_status'=>0,'generic_name'=>'Paracetamol','strength'=>'500 mg','dosage_form'=>'Tablet',
                'prescription_required'=>1,'storage_instructions'=>'Store below 25 C',
                'variants'=>[['name'=>'10 tablet strip','sku'=>'VAR-'.$suffix,'price_adjustment'=>0,'stock_quantity'=>20,'is_active'=>1]],
                'lots'=>[['lot_number'=>'LOT-'.$suffix,'manufactured_at'=>'2026-01-01','expires_at'=>'2027-01-01','quantity'=>20,'supplier_reference'=>'PO-'.$suffix]],
            ])->assertRedirect('/add-product')->assertSessionHas('message');

            $product=DB::table('product')->where('product_id','MED-'.$suffix)->first();
            $this->assertNotNull($product);$this->assertNull($product->manufacturer_id);$this->assertSame('medicine',$product->industry_profile);
            $this->assertDatabaseHas('product_variants',['product_id'=>$product->id,'sku'=>'VAR-'.$suffix,'stock_quantity'=>20]);
            $this->assertDatabaseHas('product_lots',['product_id'=>$product->id,'lot_number'=>'LOT-'.$suffix,'quantity'=>20]);
        } finally { DB::rollBack(); }
    }

    public function testAllIndustryProfilesSaveAndRenderTheirRelevantFrontendInformation(): void
    {
        DB::beginTransaction();
        try {
            $suffix=str_random(8);
            $category=DB::table('category')->insertGetId(['category_name'=>'Mixed retail '.$suffix,'category_description'=>'Test','publication_status'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $profiles=[
                'general'=>[],
                'technology'=>[],
                'clothing'=>['variants'=>[['name'=>'Navy / XL','sku'=>'CLO-'.$suffix,'stock_quantity'=>4,'is_active'=>1]]],
                'food'=>['allergen_information'=>'Contains peanuts','storage_instructions'=>'Keep refrigerated','lots'=>[['lot_number'=>'FOOD-'.$suffix,'expires_at'=>'2027-06-01','quantity'=>8]]],
                'medicine'=>['generic_name'=>'Cetirizine','strength'=>'10 mg','dosage_form'=>'Tablet','prescription_required'=>1,'storage_instructions'=>'Store below 25 C','lots'=>[['lot_number'=>'MEDLOT-'.$suffix,'expires_at'=>'2028-01-01','quantity'=>12]]],
            ];
            foreach($profiles as $profile=>$extra) {
                $code=strtoupper(substr($profile,0,3)).'-'.$suffix;
                $payload=array_merge(['product_id'=>$code,'sku'=>$code,'category_id'=>$category,'manufacturer_id'=>'','product_series_id'=>'','industry_profile'=>$profile,'product_model'=>'','product_name'=>ucfirst($profile).' test '.$suffix,'product_description'=>'Profile test','regular_price'=>100,'purchase_price'=>60,'product_condition'=>'In Stock','stock_quantity'=>20,'publication_status'=>1],$extra);
                $this->withSession($this->adminSession())->post('/save-product',$payload)->assertRedirect('/add-product')->assertSessionHas('message');
            }

            $this->assertDatabaseHas('product',['industry_profile'=>'general','manufacturer_id'=>null]);
            $this->assertDatabaseHas('product',['industry_profile'=>'technology']);
            $clothing=DB::table('product')->where('product_name','Clothing test '.$suffix)->value('id');
            $food=DB::table('product')->where('product_name','Food test '.$suffix)->value('id');
            $medicine=DB::table('product')->where('product_name','Medicine test '.$suffix)->value('id');
            $this->get('/product-details/'.$clothing)->assertOk()->assertSee('Available Options')->assertSee('Navy / XL');
            $this->get('/product-details/'.$food)->assertOk()->assertSee('Food &amp; Storage Information',false)->assertSee('Contains peanuts');
            $this->get('/product-details/'.$medicine)->assertOk()->assertSee('Medicine Information')->assertSee('Prescription')->assertSee('Required');
        } finally { DB::rollBack(); }
    }
}
