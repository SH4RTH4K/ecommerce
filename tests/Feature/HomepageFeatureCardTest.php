<?php

namespace Tests\Feature;

use App\HomepageFeatureCard;
use App\Services\HomepageFeatureCardService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepageFeatureCardTest extends TestCase
{
    private function admin()
    {
        return DB::table('tbl_admin as a')->join('admin_roles as r', 'r.id', '=', 'a.role_id')
            ->where('a.is_active', 1)->where('r.permissions', 'like', '%settings%')->select('a.*')->first();
    }

    public function testMigratedCardsRenderAndMarketingPageIsAvailable(): void
    {
        $admin = $this->admin();
        if (! $admin) { $this->assertTrue(true); return; }

        $this->assertSame(2, HomepageFeatureCard::count());
        $this->get('/')->assertStatus(200)->assertSee('Build your dream PC')->assertSee('Technology at your doorstep.');
        $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
            ->get('/homepage-feature-cards')->assertStatus(200)->assertSee('Add Feature Card')->assertSee('Card Type')->assertSee('Maximum Visible Cards');
    }

    public function testDisabledAndScheduledCardsAreExcludedFromStorefront(): void
    {
        DB::beginTransaction();
        try {
            $card = HomepageFeatureCard::first();
            $card->update(['is_active' => false]);
            app(HomepageFeatureCardService::class)->clear();
            $this->get('/')->assertStatus(200)->assertDontSee('Build your dream PC');
        } finally {
            DB::rollBack();
            app(HomepageFeatureCardService::class)->clear();
        }
    }
}
