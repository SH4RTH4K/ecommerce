<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogHierarchyTest extends TestCase
{
    private function catalogSession(): array
    {
        $admin = DB::table('tbl_admin as a')->join('admin_roles as r','r.id','=','a.role_id')->where('a.is_active',1)->where('r.permissions','like','%"catalog"%')->select('a.*')->first();
        $this->assertNotNull($admin, 'A catalog administrator is required.');
        return ['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name];
    }

    public function testCompanyBrandSeriesHierarchyWorkspaceRenders(): void
    {
        $this->withSession($this->catalogSession())->get('/catalog-hierarchy')
            ->assertOk()->assertSee('Catalog identity hierarchy')->assertSee('Companies')->assertSee('Product series')
            ->assertSee('jQuery.uniform.update',false)->assertSee('data-select-all="company"',false);
    }

    public function testAdministratorCanCreateACompleteHierarchy(): void
    {
        DB::beginTransaction();
        try {
            $suffix = str_random(8);
            $session = $this->catalogSession();
            $this->withSession($session)->post('/catalog-hierarchy/companies',['name'=>'Test Company '.$suffix,'is_active'=>1])->assertSessionHas('message');
            $companyId=DB::table('companies')->where('name','Test Company '.$suffix)->value('id');
            $this->withSession($session)->post('/catalog-hierarchy/brands',['company_id'=>$companyId,'manufacturer_name'=>'Test Brand '.$suffix,'publication_status'=>1])->assertSessionHas('message');
            $brandId=DB::table('manufacturer')->where('manufacturer_name','Test Brand '.$suffix)->value('manufacturer_id');
            $this->withSession($session)->post('/catalog-hierarchy/series',['manufacturer_id'=>$brandId,'name'=>'Test Series '.$suffix,'is_active'=>1])->assertSessionHas('message');
            $this->assertTrue(DB::table('product_series')->where('manufacturer_id',$brandId)->where('name','Test Series '.$suffix)->exists());
        } finally {
            DB::rollBack();
        }
    }

    public function testFiltersNarrowTheVisibleHierarchy(): void
    {
        DB::beginTransaction();
        try {
            $suffix=str_random(8);$session=$this->catalogSession();
            $company=DB::table('companies')->insertGetId(['name'=>'Filter '.$suffix,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $brand=DB::table('manufacturer')->insertGetId(['company_id'=>$company,'manufacturer_name'=>'Filter '.$suffix,'publication_status'=>1,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('product_series')->insert(['manufacturer_id'=>$brand,'name'=>'Filter '.$suffix,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $this->withSession($session)->get('/catalog-hierarchy?q='.urlencode('Filter '.$suffix).'&status=active')->assertOk()->assertSee('Filter '.$suffix);
            $this->withSession($session)->get('/catalog-hierarchy?company_id='.$company)->assertOk()->assertSee('Filter '.$suffix);
        } finally { DB::rollBack(); }
    }

    public function testBatchDeleteRemovesUnusedAndSkipsDependentRecords(): void
    {
        DB::beginTransaction();
        try {
            $session=$this->catalogSession();
            $empty=DB::table('companies')->insertGetId(['name'=>'Empty batch '.str_random(8),'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $used=DB::table('companies')->insertGetId(['name'=>'Used batch '.str_random(8),'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('manufacturer')->insert(['company_id'=>$used,'manufacturer_name'=>'Dependent brand '.str_random(8),'publication_status'=>1,'created_at'=>now(),'updated_at'=>now()]);

            $this->withSession($session)->post('/catalog-hierarchy/companies/bulk-delete',['company_ids'=>[$empty,$used]])
                ->assertSessionHas('message',fn ($message)=>str_contains($message,'1 companies deleted')&&str_contains($message,'1 skipped'));
            $this->assertFalse(DB::table('companies')->where('id',$empty)->exists());
            $this->assertTrue(DB::table('companies')->where('id',$used)->exists());
        } finally {
            DB::rollBack();
        }
    }

    public function testCatalogNavigationFollowsSetupDependencyOrder(): void
    {
        $content=$this->withSession($this->catalogSession())->get('/catalog-hierarchy')->assertOk()->getContent();
        $steps=['Step 1 · Categories','Step 2 · Subcategories','Step 3 · Companies','Step 4 · Product Attributes','Step 5 · Products'];
        $positions=array_map(fn ($step)=>strpos($content,$step),$steps);
        foreach($positions as $position)$this->assertNotFalse($position);
        $sorted=$positions;sort($sorted);
        $this->assertSame($sorted,$positions);
    }
}
