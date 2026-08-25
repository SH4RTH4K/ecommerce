<?php

namespace Tests\Feature;

use App\ProductCodeConfiguration;
use App\Services\CodeRegenerationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CodeRegenerationServiceTest extends TestCase
{
    public function testPreviewDoesNotChangeExistingCodes(): void
    {
        $configuration = ProductCodeConfiguration::with('components')->where('code_type', 'brand')->where('is_active', 1)->first();
        $brand = DB::table('manufacturer')->whereNotNull('brand_code')->whereNull('deleted_at')->first();
        if (! $configuration || ! $brand) $this->markTestSkipped('Brand code fixtures are not available.');

        $before = $brand->brand_code;
        $preview = app(CodeRegenerationService::class)->preview($configuration, 'UPDATE_ALL', [], true);

        $this->assertArrayHasKey('items', $preview);
        $this->assertSame($before, DB::table('manufacturer')->where('manufacturer_id', $brand->manufacturer_id)->value('brand_code'));
    }

    public function testPreviewDoesNotReturnDuplicateReadyCodes(): void
    {
        $configuration = ProductCodeConfiguration::with('components')->where('code_type', 'category')->where('is_active', 1)->first();
        if (! $configuration) $this->markTestSkipped('Category code fixtures are not available.');

        $preview = app(CodeRegenerationService::class)->preview($configuration, 'UPDATE_ALL', [], true);
        $readyCodes = collect($preview['items'])
            ->where('status', 'READY')
            ->pluck('new_code')
            ->filter()
            ->values();

        $this->assertSame($readyCodes->count(), $readyCodes->unique()->count());
    }

}
