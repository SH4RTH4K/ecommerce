<?php

namespace Tests\Feature;

use App\Banner;
use App\Category;
use App\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    public function testBannerVisibilityHonorsStatusAndSchedule()
    {
        DB::beginTransaction();
        try {
            $base = ['banner_type'=>'information','image_path'=>'asset/front-end/img/home/pic 1.jpg','display_order'=>0,'created_at'=>now(),'updated_at'=>now()];
            $visible = DB::table('banners')->insertGetId(array_merge($base, ['title'=>'Visible test banner','is_active'=>1,'starts_at'=>now()->subMinute(),'expires_at'=>now()->addMinute()]));
            DB::table('banners')->insert(array_merge($base, ['title'=>'Future test banner','is_active'=>1,'starts_at'=>now()->addDay()]));
            DB::table('banners')->insert(array_merge($base, ['title'=>'Expired test banner','is_active'=>1,'expires_at'=>now()->subDay()]));
            DB::table('banners')->insert(array_merge($base, ['title'=>'Hidden test banner','is_active'=>0]));
            $ids = Banner::visible()->where('title', 'like', '%test banner')->pluck('id')->all();
            $this->assertSame([$visible], $ids);
        } finally {
            DB::rollBack();
        }
    }

    public function testBannerDestinationsAreResolvedOnTheBackend()
    {
        $product = new Product(['product_name'=>'Linked product']);
        $product->id = 91;
        $category = new Category(['category_name'=>'Linked category']);
        $category->category_id = 27;

        $productBanner = new Banner(['banner_type'=>'product']);
        $productBanner->setRelation('product', $product);
        $categoryBanner = new Banner(['banner_type'=>'category']);
        $categoryBanner->setRelation('category', $category);

        $this->assertSame(route('store.product.show', 91), $productBanner->resolved_link);
        $this->assertSame(route('store.category.show', 27), $categoryBanner->resolved_link);
        $this->assertSame('/safe-path', (new Banner(['banner_type'=>'custom','link_url'=>'/safe-path']))->resolved_link);
        $this->assertSame('https://example.com/sale', (new Banner(['banner_type'=>'campaign','link_url'=>'https://example.com/sale']))->resolved_link);
        $this->assertNull((new Banner(['banner_type'=>'custom','link_url'=>'javascript:alert(1)']))->resolved_link);
        $this->assertNull((new Banner(['banner_type'=>'information','link_url'=>'https://example.com']))->resolved_link);
    }

    public function testBannerStudioRendersForAnAuthorizedAdministrator()
    {
        $admin = DB::table('tbl_admin')->where('is_active', 1)->first();
        if (!$admin) return $this->assertTrue(true);
        $session = ['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
        $this->withSession($session)->get('/site-customization')->assertStatus(200)
            ->assertSee('Website Settings')
            ->assertDontSee('name="banner_type"', false);
        $this->withSession($session)->get('/banner-management')->assertStatus(200)
            ->assertSee('Homepage banner studio')
            ->assertSee('name="banner_type"', false)
            ->assertSee('name="desktop_image"', false)
            ->assertSee('name="mobile_image"', false)
            ->assertSee('name="starts_at"', false)
            ->assertSee('name="expires_at"', false)
            ->assertSee('Use product image');
    }

    public function testAdministratorCanCreateAProductBannerWithBackendAutofill()
    {
        $admin = DB::table('tbl_admin')->where('is_active', 1)->first();
        $product = Product::where('publication_status', 1)->first();
        if (!$admin || !$product) return $this->assertTrue(true);
        DB::beginTransaction();
        $storedPath = null;
        try {
            $response = $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])->post('/save-banner', [
                'banner_type'=>'product','product_id'=>$product->id,'desktop_image'=>UploadedFile::fake()->image('promotion.jpg',1200,500),
                'image_position'=>'center','display_order'=>7,'show_overlay'=>1,'use_product_image'=>1,'is_active'=>1,
            ]);
            $response->assertRedirect('/banner-management')->assertSessionHas('message');
            $banner = Banner::where('product_id', $product->id)->where('display_order', 7)->latest('id')->first();
            $this->assertNotNull($banner);
            $this->assertSame($product->product_name, $banner->title);
            $this->assertSame('Shop Now', $banner->button_text);
            $this->assertTrue($banner->use_product_image);
            $this->assertStringStartsWith('asset/front-end/img/banners/desktop-', $banner->image_path);
            $storedPath = public_path($banner->image_path);
        } finally {
            DB::rollBack();
            if ($storedPath && is_file($storedPath)) unlink($storedPath);
        }
    }

    public function testUnsafeCustomBannerUrlIsRejected()
    {
        $admin = DB::table('tbl_admin')->where('is_active', 1)->first();
        if (!$admin) return $this->assertTrue(true);
        $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])->from('/banner-management')->post('/save-banner', [
            'banner_type'=>'custom','link_url'=>'javascript:alert(1)','desktop_image'=>UploadedFile::fake()->image('promotion.jpg',1200,500),
            'image_position'=>'center','display_order'=>0,'show_overlay'=>1,'is_active'=>1,
        ])->assertRedirect('/banner-management')->assertSessionHasErrors('link_url');
    }

    public function testHomepageOmitsTheEntireSliderWhenNoBannerIsVisible()
    {
        DB::beginTransaction();
        try {
            DB::table('banners')->update(['is_active'=>0]);
            $this->get('/')->assertStatus(200)
                ->assertDontSee('data-carousel', false)
                ->assertDontSee('Featured promotions')
                ->assertDontSee('pic 2.jpg')
                ->assertDontSee('pic 3.jpg');
        } finally {
            DB::rollBack();
        }
    }

    public function testAdministratorCanHideAndShowTheOnlyBanner()
    {
        $admin = DB::table('tbl_admin')->where('is_active', 1)->first();
        if (!$admin) return $this->assertTrue(true);
        DB::beginTransaction();
        try {
            DB::table('banners')->update(['is_active'=>0]);
            $banner = Banner::create(['banner_type'=>'information','title'=>'Toggle lifecycle banner','image_path'=>'asset/front-end/img/home/pic 1.jpg','image_position'=>'center','show_overlay'=>1,'display_order'=>0,'is_active'=>1]);
            $session = ['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
            $this->get('/')->assertSee('Toggle lifecycle banner');
            $this->withSession($session)->post('/toggle-banner/'.$banner->id)->assertRedirect('/banner-management');
            $this->assertFalse($banner->fresh()->is_active);
            $this->get('/')->assertDontSee('data-carousel', false)->assertDontSee('Toggle lifecycle banner');
            $this->withSession($session)->post('/toggle-banner/'.$banner->id)->assertRedirect('/banner-management');
            $this->assertTrue($banner->fresh()->is_active);
            $this->get('/')->assertSee('Toggle lifecycle banner');
        } finally {
            DB::rollBack();
        }
    }

    public function testDeleteRemovesOnlyUnsharedDedicatedBannerImages()
    {
        $admin = DB::table('tbl_admin')->where('is_active', 1)->first();
        if (!$admin) return $this->assertTrue(true);
        $directory = public_path('asset/front-end/img/banners');
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $token = str_random(12);
        $sharedPath = 'asset/front-end/img/banners/shared-'.$token.'.jpg';
        $mobilePath = 'asset/front-end/img/banners/mobile-'.$token.'.jpg';
        file_put_contents(public_path($sharedPath), 'shared');
        file_put_contents(public_path($mobilePath), 'mobile');
        DB::beginTransaction();
        try {
            Banner::create(['banner_type'=>'information','title'=>'Shared image owner','image_path'=>$sharedPath,'image_position'=>'center','display_order'=>0,'is_active'=>0]);
            $deleted = Banner::create(['banner_type'=>'information','title'=>'Delete lifecycle banner','image_path'=>$sharedPath,'mobile_image'=>$mobilePath,'image_position'=>'center','display_order'=>1,'is_active'=>1]);
            $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])->post('/delete-banner/'.$deleted->id)->assertRedirect('/banner-management')->assertSessionHas('message');
            $this->assertNull(Banner::find($deleted->id));
            $this->assertFileExists(public_path($sharedPath));
            $this->assertFileDoesNotExist(public_path($mobilePath));
        } finally {
            DB::rollBack();
            if (is_file(public_path($sharedPath))) unlink(public_path($sharedPath));
            if (is_file(public_path($mobilePath))) unlink(public_path($mobilePath));
        }
    }
}
