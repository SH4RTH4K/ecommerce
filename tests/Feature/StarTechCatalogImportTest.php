<?php

namespace Tests\Feature;

use App\Services\StarTechCatalogImporter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StarTechCatalogImportTest extends TestCase
{
    private function fakeHomeHtml(string $baseUrl = 'https://www.startech.com.bd/'): string
    {
        $baseUrl = rtrim($baseUrl, '/') . '/';

        $html = <<<'HTML'
<html><body><header>
<a href="%sdesktops">Desktops</a>
<a href="%slaptop-notebook">Laptop & Notebook</a>
<a href="%scomponent">Components</a>
<a href="%saccessories">Accessories</a>
<a href="%slaptop-notebook/quantum-station">Quantum Station</a>
<a href="%slaptop-notebook/hp-laptop">HP Laptops</a>
<a href="%slaptop-notebook/lenovo-laptop">Lenovo Laptops</a>
<a href="%slaptop-notebook/acer-laptop">Acer Laptops</a>
<a href="%saccessories/hoco-mouse">HOCO Mouse</a>
<a href="%sgadget/sony-speaker">Sony Speaker</a>
<a href="%sgaming/qulik-fan">Qulik Fan</a>
</header></body></html>
HTML;

        return sprintf($html, $baseUrl, $baseUrl, $baseUrl, $baseUrl, $baseUrl, $baseUrl, $baseUrl, $baseUrl, $baseUrl, $baseUrl, $baseUrl);
    }

    private function fakeSitemapXml(string $baseUrl = 'https://www.startech.com.bd/', array $extraPaths = []): string
    {
        $baseUrl = rtrim($baseUrl, '/') . '/';
        $paths = [
            'desktops/desktop-pc',
            'laptop-notebook/quantum-station',
            'laptop-notebook/hp-laptop',
            'laptop-notebook/lenovo-laptop',
            'laptop-notebook/acer-laptop',
            'accessories/hoco-mouse',
            'gadget/sony-speaker',
            'gaming/qulik-fan',
        ];
        foreach ($extraPaths as $extraPath) {
            $extraPath = trim((string) $extraPath);
            if ($extraPath !== '') {
                $paths[] = ltrim($extraPath, '/');
            }
        }

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset>
%s
</urlset>
XML;

        $entries = array_map(static function ($path) use ($baseUrl) {
            $path = trim((string) $path);
            $loc = preg_match('#^https?://#i', $path) ? $path : $baseUrl.ltrim($path, '/');

            return '  <url><loc>'.$loc.'</loc></url>';
        }, $paths);

        return sprintf($xml, implode("\n", $entries));
    }

    private function fakeProductHtml(string $name, string $description, string $sku, string $brandName, string $model, string $imageUrl, string $price, array $breadcrumbLinks = [], bool $includeSchema = true): string
    {
        $schema = $includeSchema ? json_encode([
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $name,
            'description' => $description,
            'sku' => $sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => $brandName,
            ],
            'model' => $model,
            'image' => $imageUrl,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'USD',
                'price' => $price,
                'availability' => 'https://schema.org/InStock',
            ],
        ], JSON_UNESCAPED_SLASHES) : '';

        $breadcrumbHtml = '';
        if ($breadcrumbLinks !== []) {
            $items = '<li><a href="https://www.startech.com.bd/"><i class="material-icons" title="Home">home</i></a></li>';
            $position = 1;
            foreach ($breadcrumbLinks as $breadcrumbLink) {
                if (! is_array($breadcrumbLink) || count($breadcrumbLink) < 2) {
                    continue;
                }

                [$href, $label] = array_values($breadcrumbLink);
                $items .= '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a itemtype="http://schema.org/Thing" itemprop="item" href="'.$href.'"><span itemprop="name">'.$label.'</span></a><meta itemprop="position" content="'.$position.'" /></li>';
                $position++;
            }

            $breadcrumbHtml = '<section class="after-header"><div class="container"><ul class="breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">'.$items.'</ul></div></section>';
        }

        return <<<HTML
<html><head>
<meta property="og:type" content="product">
<meta property="og:title" content="$name">
<meta name="description" content="$description">
<meta property="product:price:amount" content="$price">
<meta property="og:price:amount" content="$price">
<meta property="og:image" content="$imageUrl">
</head><body>
{$breadcrumbHtml}
<h1>$name</h1>
HTML
        . ($includeSchema ? '<script type="application/ld+json">'.$schema.'</script>' : '')
        . <<<HTML
<p>Warranty: 2 Years</p>
</body></html>
HTML;
    }

    private function fakeRichProductHtml(
        string $name,
        string $summary,
        string $sku,
        string $brandName,
        string $model,
        string $productCode,
        string $mainImageUrl,
        array $galleryImageUrls,
        string $cashPrice,
        string $cashOldPrice,
        string $regularPrice,
        array $keyFeatures,
        array $specificationSections,
        array $descriptionBlocks,
        array $breadcrumbLinks = [],
        bool $includeSchema = true
    ): string {
        $schema = $includeSchema ? json_encode([
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $name,
            'description' => $summary,
            'sku' => $sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => $brandName,
            ],
            'model' => $model,
            'image' => $mainImageUrl,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'BDT',
                'price' => $cashPrice,
                'availability' => 'https://schema.org/InStock',
            ],
        ], JSON_UNESCAPED_SLASHES) : '';

        $breadcrumbHtml = '';
        if ($breadcrumbLinks !== []) {
            $items = '<li><a href="https://www.startech.com.bd/"><i class="material-icons" title="Home">home</i></a></li>';
            $position = 1;
            foreach ($breadcrumbLinks as $breadcrumbLink) {
                if (! is_array($breadcrumbLink) || count($breadcrumbLink) < 2) {
                    continue;
                }

                [$href, $label] = array_values($breadcrumbLink);
                $items .= '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a itemtype="http://schema.org/Thing" itemprop="item" href="'.$href.'"><span itemprop="name">'.$label.'</span></a><meta itemprop="position" content="'.$position.'" /></li>';
                $position++;
            }

            $breadcrumbHtml = '<section class="after-header"><div class="container"><ul class="breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">'.$items.'</ul></div></section>';
        }

        $galleryHtml = '<div class="product-gallery">';
        $galleryHtml .= '<img class="main-product-image" src="'.$mainImageUrl.'" alt="'.$name.'">';
        foreach ($galleryImageUrls as $galleryImageUrl) {
            $galleryHtml .= '<img class="gallery-product-image" src="'.$galleryImageUrl.'" alt="'.$name.' gallery">';
        }
        $galleryHtml .= '</div>';

        $highlightsHtml = '';
        if ($keyFeatures !== []) {
            $highlightsHtml = '<div class="product-highlights"><ul>';
            foreach ($keyFeatures as $feature) {
                $highlightsHtml .= '<li>'.$feature.'</li>';
            }
            $highlightsHtml .= '</ul></div>';
        }

        $pricingHtml = <<<HTML
<div class="product-pricing">
    <label class="p-wrap cash">
        <input type="radio" name="enable_emi" value="0" checked />
        <span class="price">{$cashPrice}</span>
        <span class="price-old">{$cashOldPrice}</span>
        <div class="p-tag">Cash Discount Price</div>
        <div class="p-tag fade">Online / Cash Payment</div>
    </label>
    <label class="p-wrap emi">
        <input type="radio" name="enable_emi" value="1" />
        <span class="price">177৳/month</span>
        <div class="p-tag regular">Regular Price: {$regularPrice}</div>
        <div class="p-tag fade">0% EMI for up to 12 Months***</div>
    </label>
</div>
HTML;

        $specificationHtml = '<section class="specification-tab m-tb-10" id="specification"><div class="section-head"><h2>Specification</h2></div><table class="data-table flex-table" cellpadding="0" cellspacing="0"><colgroup><col class="name"/><col class="value"/></colgroup>';
        foreach ($specificationSections as $sectionName => $rows) {
            $specificationHtml .= '<thead><tr><td class="heading-row" colspan="3">'.$sectionName.'</td></tr></thead><tbody>';
            foreach ($rows as $label => $value) {
                $specificationHtml .= '<tr><td class="name">'.$label.'</td><td class="value">'.$value.'</td></tr>';
            }
            $specificationHtml .= '</tbody>';
        }
        $specificationHtml .= '</table></section>';

        $descriptionHtml = '<section class="description bg-white m-tb-15" id="description"><div class="section-head"><h2>Description</h2></div><div class="full-description" itemprop="description">';
        foreach ($descriptionBlocks as $block) {
            $descriptionHtml .= $block;
        }
        $descriptionHtml .= '</div></section>';

        return <<<HTML
<html><head>
<title>{$name} Price in Bangladesh</title>
<meta property="og:type" content="product">
<meta property="og:title" content="{$name}">
<meta name="description" content="{$summary}">
<meta property="product:brand" content="{$brandName}">
<meta property="product:availability" content="In Stock">
<meta property="product:condition" content="new">
<meta property="product:price:amount" content="{$cashPrice}">
<meta property="product:price:currency" content="BDT">
<meta property="product:retailer_item_id" content="{$productCode}">
<meta property="og:price:amount" content="{$cashPrice}">
<meta property="og:image" content="{$mainImageUrl}">
</head><body>
{$breadcrumbHtml}
<h1>{$name}</h1>
<div class="product-meta">
    <p>Product Code: {$productCode}</p>
    <p>Brand: {$brandName}</p>
    <p>Model: {$model}</p>
</div>
{$galleryHtml}
{$highlightsHtml}
{$pricingHtml}
{$specificationHtml}
{$descriptionHtml}
<section class="latest-price bg-white m-tb-15" id="latest-price">
    <div class="section-head">
        <h2>What is the price of {$name} in Bangladesh?</h2>
    </div>
    <p>The latest price of {$name} in Bangladesh is {$cashPrice}.</p>
</section>
</body></html>
HTML
        . ($includeSchema ? '<script type="application/ld+json">'.$schema.'</script>' : '');
    }

    private function fakeListingHtml(string $title, string|array $productUrl, array $extraLinks = []): string
    {
        $links = is_array($productUrl) ? array_values($productUrl) : [$productUrl];
        foreach ($extraLinks as $extraLink) {
            if (is_string($extraLink) && trim($extraLink) !== '') {
                $links[] = $extraLink;
            }
        }
        $anchors = '';
        foreach ($links as $index => $url) {
            $label = $title.($index > 0 ? ' '.($index + 1) : '');
            $anchors .= '<a href="'.$url.'">'.$label.'</a>'."\n";
        }

        return <<<HTML
<html><body>
<main>
<h1>$title</h1>
<div class="listing">
$anchors
</div>
</main>
</body></html>
HTML;
    }

    private function catalogSession(): array
    {
        $admin = DB::table('tbl_admin as a')
            ->join('admin_roles as r', 'r.id', '=', 'a.role_id')
            ->where('a.is_active', 1)
            ->where('r.permissions', 'like', '%"catalog"%')
            ->select('a.*')
            ->first();

        $this->assertNotNull($admin, 'A catalog administrator is required.');

        return ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
    }

    public function testStarTechCatalogCanBeImportedStepByStep(): void
    {
        Http::fake([
            'https://www.startech.com.bd/' => Http::response($this->fakeHomeHtml(), 200),
            'https://www.startech.com.bd/sitemap.xml' => Http::response($this->fakeSitemapXml(), 200),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $exitCode = Artisan::call('startech:import-catalog');
            $output = Artisan::output();

            $this->assertSame(0, $exitCode);
            $this->assertStringContainsString('Star Tech catalog import started', $output);
            $this->assertStringContainsString('1. Categories', $output);
            $this->assertStringContainsString('2. Subcategories', $output);
            $this->assertStringContainsString('3. Brands', $output);
            $this->assertStringContainsString('4. Series', $output);
            $this->assertStringContainsString('Star Tech catalog import finished.', $output);

            $this->assertDatabaseHas('category', [
                'category_name' => 'Laptop & Notebook',
                'display_order' => 2,
            ]);

            $categoryId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            $this->assertNotNull($categoryId);
            $this->assertTrue(
                DB::table('sub_category')
                    ->where('category_id', $categoryId)
                    ->where('sub_category_name', 'Quantum Station')
                    ->exists()
            );

            $companyId = DB::table('companies')->where('name', 'Star Tech Imported Brands')->value('id');
            $this->assertNotNull($companyId);
            $this->assertTrue(
                DB::table('manufacturer')
                    ->where('manufacturer_name', 'HOCO')
                    ->where('company_id', $companyId)
                    ->exists()
            );
            $this->assertTrue(
                DB::table('manufacturer')
                    ->where('manufacturer_name', 'Beelink')
                    ->where('company_id', $companyId)
                    ->exists()
            );

            $beelinkId = DB::table('manufacturer')->where('manufacturer_name', 'Beelink')->value('manufacturer_id');
            $this->assertNotNull($beelinkId);
            $this->assertTrue(
                DB::table('product_series')
                    ->where('manufacturer_id', $beelinkId)
                    ->where('name', 'Mini PC')
                    ->exists()
            );
        } finally {
            DB::rollBack();
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testStarTechCatalogImportBackfillsConfiguredCodesForEverySection(): void
    {
        Http::fake([
            'https://www.startech.com.bd/' => Http::response($this->fakeHomeHtml(), 200),
            'https://www.startech.com.bd/sitemap.xml' => Http::response($this->fakeSitemapXml(), 200),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $importer = app(StarTechCatalogImporter::class);
            $importer->import(['categories', 'subcategories', 'brands', 'series'], false, 'https://www.startech.com.bd/');

            $desktopId = DB::table('category')->where('category_name', 'Desktops')->value('category_id');
            $laptopId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            $subcategoryId = DB::table('sub_category')
                ->where('category_id', $laptopId)
                ->where('sub_category_name', 'Quantum Station')
                ->value('sub_category_id');
            $companyId = DB::table('companies')->where('name', 'Star Tech Imported Brands')->value('id');
            $brandId = DB::table('manufacturer')->where('manufacturer_name', 'Beelink')->value('manufacturer_id');
            $seriesId = DB::table('product_series')
                ->where('manufacturer_id', $brandId)
                ->where('name', 'Mini PC')
                ->value('id');

            $this->assertNotNull($desktopId);
            $this->assertNotNull($subcategoryId);
            $this->assertNotNull($companyId);
            $this->assertNotNull($brandId);
            $this->assertNotNull($seriesId);

            DB::table('category')->where('category_id', $desktopId)->update(['category_code' => null]);
            DB::table('sub_category')->where('sub_category_id', $subcategoryId)->update(['subcategory_code' => null]);
            DB::table('companies')->where('id', $companyId)->update(['company_code' => null]);
            DB::table('manufacturer')->where('manufacturer_id', $brandId)->update(['brand_code' => null]);
            DB::table('product_series')->where('id', $seriesId)->update(['series_code' => null]);

            $importer->import(['categories', 'subcategories', 'brands', 'series'], false, 'https://www.startech.com.bd/');

            $this->assertNotEmpty(trim((string) DB::table('category')->where('category_id', $desktopId)->value('category_code')));
            $this->assertNotEmpty(trim((string) DB::table('sub_category')->where('sub_category_id', $subcategoryId)->value('subcategory_code')));
            $this->assertNotEmpty(trim((string) DB::table('companies')->where('id', $companyId)->value('company_code')));
            $this->assertNotEmpty(trim((string) DB::table('manufacturer')->where('manufacturer_id', $brandId)->value('brand_code')));
            $this->assertNotEmpty(trim((string) DB::table('product_series')->where('id', $seriesId)->value('series_code')));
        } finally {
            DB::rollBack();
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testAdministratorCanImportStarTechCatalogFromTheCatalogHierarchyPage(): void
    {
        Http::fake([
            'https://www.startech.com.bd/' => Http::response($this->fakeHomeHtml(), 200),
            'https://www.startech.com.bd/sitemap.xml' => Http::response($this->fakeSitemapXml(), 200),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $response = $this->withSession($this->catalogSession())
                ->from('/catalog-hierarchy')
                ->post('/catalog-hierarchy/startech-import');

            $response->assertRedirect('/catalog-hierarchy')->assertSessionHas('message');

            $this->assertDatabaseHas('category', [
                'category_name' => 'Laptop & Notebook',
            ]);

            $categoryId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            $this->assertNotNull($categoryId);
            $this->assertTrue(
                DB::table('sub_category')
                    ->where('category_id', $categoryId)
                    ->where('sub_category_name', 'Quantum Station')
                    ->exists()
            );
        } finally {
            DB::rollBack();
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testStarTechCategoryImportRestoresPreviouslyDeletedCategories(): void
    {
        DB::beginTransaction();
        try {
            $categoryId = DB::table('category')->where('category_name', 'Desktops')->value('category_id');
            if (! $categoryId) {
                $categoryId = DB::table('category')->insertGetId([
                    'category_name' => 'Desktops',
                    'category_description' => 'Temporarily deleted category.',
                    'publication_status' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('category')->where('category_id', $categoryId)->update([
                'publication_status' => 0,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            $result = app(StarTechCatalogImporter::class)->importCategories();

            $this->assertGreaterThanOrEqual(1, (int) ($result['updated'] ?? 0));
            $this->assertDatabaseHas('category', [
                'category_id' => $categoryId,
                'category_name' => 'Desktops',
                'publication_status' => 1,
                'deleted_at' => null,
            ]);
        } finally {
            DB::rollBack();
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testManageCategoryPageShowsTheStarTechCategoryImportPanel(): void
    {
        $sourceLabel = catalog_import_source_label('https://www.startech.com.bd/');

        $this->withSession($this->catalogSession())
            ->get('/manage-category')
            ->assertOk()
            ->assertSee($sourceLabel.' category import')
            ->assertSee('Import '.$sourceLabel.' categories')
            ->assertSee('Pull the live category tree from the current source address', false);
    }

    public function testManageSubCategoryPageShowsTheSourceImportPanelAndCategoryScopeSelector(): void
    {
        $sourceLabel = catalog_import_source_label('https://www.startech.com.bd/');

        $this->withSession($this->catalogSession())
            ->get('/manage-subCategory')
            ->assertOk()
            ->assertSee($sourceLabel.' source import for subcategories')
            ->assertSee('Import source subcategories')
            ->assertSee('Choose a category to import only its source subcategories.', false)
            ->assertSee('All categories', false);
    }

    public function testManageManufacturerPageShowsTheSourceImportPanelAndBrandScopeSelector(): void
    {
        $sourceLabel = catalog_import_source_label('https://www.startech.com.bd/');

        $this->withSession($this->catalogSession())
            ->get('/manage-manufacturer')
            ->assertOk()
            ->assertSee($sourceLabel.' source import for series')
            ->assertSee('Import source series')
            ->assertSee('Choose a brand to import only its source series.', false)
            ->assertSee('All brands', false);
    }

    public function testCatalogImportCenterShowsSourceAddressAndAllImportOptions(): void
    {
        $sourceLabel = catalog_import_source_label('https://www.startech.com.bd/');

        $this->withSession($this->catalogSession())
            ->get('/catalog-imports')
            ->assertOk()
            ->assertSee('Catalog Import Center')
            ->assertSee('Current source address:', false)
            ->assertSee($sourceLabel.' source import')
            ->assertSee('Source address:', false)
            ->assertSee('https://www.startech.com.bd/')
            ->assertSee('Categories Import &amp; Export', false)
            ->assertSee('Subcategories Import &amp; Export', false)
            ->assertSee('Brands Import &amp; Export', false)
            ->assertSee('Products Import &amp; Export', false)
            ->assertSee('Fetch only', false)
            ->assertSee('Product batch size')
            ->assertSee('value="products"', false)
            ->assertSee('Suppliers Import &amp; Export', false)
            ->assertSee('Stock Locations Import &amp; Export', false);
    }

    public function testCatalogAttributesPageShowsTheSourceImportPanel(): void
    {
        $sourceLabel = catalog_import_source_label('https://www.startech.com.bd/');

        $this->withSession($this->catalogSession())
            ->get('/catalog-attributes')
            ->assertOk()
            ->assertSee($sourceLabel.' source import for attributes')
            ->assertSee('Import from Source', false)
            ->assertSee('Import attributes from source')
            ->assertSee('Attributes', false)
            ->assertSee('Source address:', false)
            ->assertSee('https://www.startech.com.bd/')
            ->assertSee('Catalog Attributes Import &amp; Export', false);
    }

    public function testAdministratorCanImportCatalogAttributesFromTheCatalogAttributesPage(): void
    {
        DB::beginTransaction();
        try {
            $componentsId = DB::table('category')->where('category_name', 'Components')->value('category_id');
            $laptopId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            $this->assertNotNull($componentsId, 'Components category is required.');
            $this->assertNotNull($laptopId, 'Laptop & Notebook category is required.');

            $response = $this->withSession($this->catalogSession())
                ->from('/catalog-attributes')
                ->post('/catalog-hierarchy/startech-import', [
                    'steps' => ['attributes'],
                ]);

            $response->assertRedirect('/catalog-attributes')->assertSessionHas('message');

            $this->assertDatabaseHas('catalog_attributes', [
                'category_id' => $componentsId,
                'slug' => 'processor-model',
            ]);
            $this->assertDatabaseHas('catalog_attributes', [
                'category_id' => $laptopId,
                'slug' => 'display-size',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function testManageProductPageShowsTheSourceImportPanel(): void
    {
        $sourceLabel = catalog_import_source_label('https://www.startech.com.bd/');

        $this->withSession($this->catalogSession())
            ->get('/manage-product')
            ->assertOk()
            ->assertSee($sourceLabel.' source import for products')
            ->assertSee('Refresh catalog source structure')
            ->assertSee('Source address:', false)
            ->assertSee('https://www.startech.com.bd/')
            ->assertSee('Products Import &amp; Export', false)
            ->assertSee('Fetch only', false)
            ->assertSee('Product batch size')
            ->assertSee('value="products"', false);
    }

    public function testAdministratorCanImportOnlySubcategoriesForSelectedCategory(): void
    {
        Http::fake([
            'https://www.startech.com.bd/' => Http::response($this->fakeHomeHtml(), 200),
            'https://www.startech.com.bd/sitemap.xml' => Http::response($this->fakeSitemapXml(), 200),
        ]);

        DB::beginTransaction();
        try {
            $laptopCategoryId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            $gadgetCategoryId = DB::table('category')->where('category_name', 'Gadget')->value('category_id');
            $this->assertNotNull($laptopCategoryId, 'Laptop & Notebook category is required.');
            $this->assertNotNull($gadgetCategoryId, 'Gadget category is required.');

            DB::table('sub_category')
                ->where('category_id', $laptopCategoryId)
                ->whereIn('sub_category_name', ['Quantum Station', 'HP Laptops', 'Lenovo Laptops', 'Acer Laptops'])
                ->delete();
            DB::table('sub_category')
                ->where('category_id', $gadgetCategoryId)
                ->where('sub_category_name', 'Sony Speaker')
                ->delete();

            $response = $this->withSession($this->catalogSession())
                ->from('/manage-subCategory')
                ->post('/catalog-hierarchy/startech-import', [
                    'steps' => ['subcategories'],
                    'category_id' => $laptopCategoryId,
                ]);

            $response->assertRedirect('/manage-subCategory')->assertSessionHas('message');

            $this->assertDatabaseHas('sub_category', [
                'category_id' => $laptopCategoryId,
                'sub_category_name' => 'Quantum Station',
            ]);
            $this->assertDatabaseMissing('sub_category', [
                'category_id' => $gadgetCategoryId,
                'sub_category_name' => 'Sony Speaker',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function testAdministratorCanImportOnlySeriesForSelectedBrand(): void
    {
        DB::beginTransaction();
        try {
            $importer = app(StarTechCatalogImporter::class);
            $expectedSeries = $importer->seriesMap()['Lenovo'] ?? [];
            $this->assertNotEmpty($expectedSeries, 'Lenovo series definitions are required.');

            $manufacturerId = DB::table('manufacturer')->where('manufacturer_name', 'Lenovo')->value('manufacturer_id');
            if (! $manufacturerId) {
                $manufacturerId = DB::table('manufacturer')->insertGetId([
                    'manufacturer_name' => 'Lenovo',
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('product_series')->where('manufacturer_id', $manufacturerId)->delete();

            $response = $this->withSession($this->catalogSession())
                ->from('/manage-manufacturer')
                ->post('/catalog-hierarchy/startech-import', [
                    'steps' => ['series'],
                    'manufacturer_id' => $manufacturerId,
                ]);

            $response->assertRedirect('/manage-manufacturer')->assertSessionHas('message');

            $this->assertSame(
                count($expectedSeries),
                DB::table('product_series')->where('manufacturer_id', $manufacturerId)->count()
            );
            $this->assertDatabaseHas('product_series', [
                'manufacturer_id' => $manufacturerId,
                'name' => 'ThinkPad',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function testAdministratorCanImportLiveSourceProductsFromTheManageProductPage(): void
    {
        $sourceAddress = 'https://product-source.example.test/';
        $productPath = 'laptop-notebook/live-test-laptop/live-import-gaming-laptop';
        $productUrl = rtrim($sourceAddress, '/') . '/' . $productPath;
        $productName = '1STPLAYER M3 ARGB 3 in 1 Combo Casing Cooling Fan with Controller and Hub';
        $summary = 'Buy 1STPLAYER M3 ARGB 3 in 1 Casing Fan at best price in Bangladesh. Latest 1STPLAYER M3 ARGB Case Fan available at Star Tech. Order online for delivery in BD.';
        $brandName = '1STPLAYER';
        $modelName = 'M3 ARGB';
        $sku = '39139';
        $productCode = '39139';
        $mainImageUrl = 'https://cdn.example.test/products/1stplayer-m3-argb-01-500x500.webp';
        $galleryImageUrl = 'https://cdn.example.test/products/1stplayer-m3-argb-02-500x500.webp';
        $galleryThumbUrl = 'https://cdn.example.test/products/1stplayer-m3-argb-02-74x74.webp';
        $keyFeatures = [
            'Fan Speed: 1200+10%RPM',
            'Air Flow: 36.46CFM MAX',
            'Noise (dBA): 27.82dB(A)',
            'Lifespan: 25000 HRS',
            'Bearing Type: Hydro bearing',
            'LED Type: Yes',
            'Operating Voltage/Power Range: 12V/LED DC 5V',
        ];
        $specificationSections = [
            'Key Features' => [
                'Fan Speed' => '1200+10%RPM',
                'Air Flow' => '36.46CFM MAX',
                'Noise (dBA)' => '27.82dB(A)',
                'Lifespan' => '25000 HRS',
                'Bearing Type' => 'Hydro bearing',
                'LED Type' => 'Yes',
                'Operating Voltage/Power Range' => '12V/LED DC 5V',
            ],
            'Physical Specification' => [
                'Color' => 'Black',
                'Size' => '120 x 120 x 25 mm',
                'Connector' => '5 pin',
            ],
            'Others' => [
                'Others' => 'Cable Length: 50CM',
            ],
            'Warranty Information' => [
                'Manufacturing Warranty' => '1 Year',
            ],
        ];
        $descriptionBlocks = [
            '<h2>1STPLAYER M3 ARGB 3 in 1 Combo Casing Cooling Fan</h2>',
            '<p>The 1STPLAYER M3 ARGB 3 in 1 Combo Casing Cooling Fan with Controller and Hub is an excellent solution for PC gamers seeking both effective cooling and beautiful design. This 1STPLAYER M3 ARGB Casing Fan has three 120mm fans and runs at a speed of 1200 RPM, giving a maximum airflow of 36.46 CFM with little noise at 27.82 dB(A). Its ARGB lighting effect improves the visual attractiveness of your PC setup. The cooling kit also includes an integrated controller and hub for simple installation.</p>',
            '<h2>Buy 1STPLAYER M3 ARGB 3 in 1 Combo Casing Cooling Fan From Star Tech</h2>',
            '<p>In Bangladesh, you can get original 1STPLAYER M3 ARGB 3 in 1 Combo Casing Cooling Fan From Star Tech. We have a large collection of the latest 1STPLAYER Casing Fan to purchase. The 1STPLAYER M3 ARGB 3 in 1 Combo Casing Cooling Fan comes with 1 Year warranty.</p>',
        ];
        $createdImagePaths = [];

        Http::fake([
            $sourceAddress => Http::response($this->fakeHomeHtml($sourceAddress), 200),
            rtrim($sourceAddress, '/') . '/sitemap.xml' => Http::response($this->fakeSitemapXml($sourceAddress), 200),
            rtrim($sourceAddress, '/') . '/desktops' => Http::response($this->fakeListingHtml('Desktops', []), 200),
            rtrim($sourceAddress, '/') . '/desktops/desktop-pc' => Http::response($this->fakeListingHtml('Desktop PC', []), 200),
            rtrim($sourceAddress, '/') . '/laptop-notebook' => Http::response($this->fakeListingHtml($productName, $productUrl), 200),
            rtrim($sourceAddress, '/') . '/laptop-notebook/live-test-laptop' => Http::response($this->fakeListingHtml('Live Test Laptop', [$productUrl]), 200),
            rtrim($sourceAddress, '/') . '/laptop-notebook/quantum-station' => Http::response($this->fakeListingHtml('Quantum Station', []), 200),
            rtrim($sourceAddress, '/') . '/laptop-notebook/hp-laptop' => Http::response($this->fakeListingHtml('HP Laptops', []), 200),
            rtrim($sourceAddress, '/') . '/laptop-notebook/lenovo-laptop' => Http::response($this->fakeListingHtml('Lenovo Laptops', []), 200),
            rtrim($sourceAddress, '/') . '/laptop-notebook/acer-laptop' => Http::response($this->fakeListingHtml('Acer Laptops', []), 200),
            rtrim($sourceAddress, '/') . '/component' => Http::response($this->fakeListingHtml('Components', []), 200),
            rtrim($sourceAddress, '/') . '/accessories' => Http::response($this->fakeListingHtml('Accessories', []), 200),
            rtrim($sourceAddress, '/') . '/accessories/hoco-mouse' => Http::response($this->fakeListingHtml('HOCO Mouse', []), 200),
            rtrim($sourceAddress, '/') . '/gadget/sony-speaker' => Http::response($this->fakeListingHtml('Sony Speaker', []), 200),
            rtrim($sourceAddress, '/') . '/gaming/qulik-fan' => Http::response($this->fakeListingHtml('Qulik Fan', []), 200),
            $productUrl => Http::response($this->fakeRichProductHtml(
                $productName,
                $summary,
                $sku,
                $brandName,
                $modelName,
                $productCode,
                $mainImageUrl,
                [$galleryImageUrl, $galleryThumbUrl],
                '1800.00',
                '2000.00',
                '2120.00',
                $keyFeatures,
                $specificationSections,
                $descriptionBlocks,
                [
                    [$sourceAddress . 'laptop-notebook', 'Laptop & Notebook'],
                    [$sourceAddress . 'laptop-notebook/live-test-laptop', 'Live Test Laptop'],
                    [$productUrl, $productName],
                ],
            ), 200),
            $mainImageUrl => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/webp']),
            $galleryImageUrl => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/webp']),
            $galleryThumbUrl => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/webp']),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $categoryId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            if (! $categoryId) {
                $categoryId = DB::table('category')->insertGetId([
                    'category_name' => 'Laptop & Notebook',
                    'category_description' => 'Laptop category for live product import regression test.',
                    'icon_class' => 'fa-laptop',
                    'is_featured' => 1,
                    'display_order' => 2,
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $subCategoryId = DB::table('sub_category')
                ->where('category_id', $categoryId)
                ->where('sub_category_name', 'Live Test Laptop')
                ->value('sub_category_id');
            if (! $subCategoryId) {
                $subCategoryId = DB::table('sub_category')->insertGetId([
                    'category_id' => $categoryId,
                    'sub_category_name' => 'Live Test Laptop',
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $response = $this->withSession($this->catalogSession())
                ->from('/manage-product')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'steps' => ['products'],
                ]);

            $response->assertRedirect('/manage-product')->assertSessionHas('message', function (string $message) use ($productName) {
                return str_contains($message, '1STPLAYER M3 ARGB')
                    && str_contains($message, 'Fields captured:')
                    && preg_match('/\d+\/\d+/', $message) === 1;
            });

            $product = DB::table('product')->where('sku', $sku)->first();
            $this->assertNotNull($product);
            $productRecord = (array) $product;
            $this->assertSame($productName, $product->product_name);
            $this->assertSame($modelName, $product->product_model);
            $this->assertSame((int) $categoryId, (int) $product->category_id);
            $this->assertSame((int) $subCategoryId, (int) $product->sub_category);
            $this->assertSame('In Stock', $product->product_condition);
            $this->assertSame(2120.00, (float) $product->regular_price);
            $this->assertSame(1800.00, (float) $product->offer_price);
            $this->assertSame($summary, $product->short_description);
            $this->assertSame($summary, $product->seo_description);
            $this->assertSame('1 Year', $product->warranty);
            $this->assertNotNull($product->manufacturer_id);
            $this->assertNotNull($product->product_series_id);

            $this->assertSame($brandName, DB::table('manufacturer')->where('manufacturer_id', $product->manufacturer_id)->value('manufacturer_name'));
            $this->assertSame($modelName, DB::table('product_series')->where('id', $product->product_series_id)->value('name'));

            $this->assertSame('1 Year', $productRecord['warranty'] ?? null);
            $this->assertStringContainsString('excellent solution for PC gamers', (string) ($productRecord['Product_description'] ?? $productRecord['product_description'] ?? ''));
            $this->assertStringContainsString('integrated controller and hub', (string) ($productRecord['Product_description'] ?? $productRecord['product_description'] ?? ''));

            $keyFeatures = json_decode((string) ($productRecord['key_features'] ?? '[]'), true) ?: [];
            $specifications = json_decode((string) ($productRecord['specifications'] ?? '[]'), true) ?: [];
            $galleryImages = json_decode((string) ($productRecord['gallery_images'] ?? '[]'), true) ?: [];

            $this->assertSame('Fan Speed: 1200+10%RPM', $keyFeatures[0] ?? null);
            $this->assertSame('Black', $specifications['Physical Specification']['Color'] ?? null);
            $this->assertSame('1 Year', $specifications['Warranty Information']['Manufacturing Warranty'] ?? null);
            $this->assertCount(1, $galleryImages);

            $createdImagePath = (string) $product->product_image;
            $this->assertStringStartsWith('asset/front-end/img/Product_image/product-', $createdImagePath);
            $this->assertFileExists(public_path($createdImagePath));
            foreach ($galleryImages as $galleryImagePath) {
                $this->assertStringStartsWith('asset/front-end/img/Product_image/product-', $galleryImagePath);
                $this->assertFileExists(public_path($galleryImagePath));
                $createdImagePaths[] = $galleryImagePath;
            }
            $createdImagePaths[] = $createdImagePath;
        } finally {
            foreach (array_unique($createdImagePaths) as $createdImagePath) {
                if ($createdImagePath && is_file(public_path($createdImagePath))) {
                    unlink(public_path($createdImagePath));
                }
            }
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testAdministratorCanImportLiveSourceProductsInBatches(): void
    {
        $sourceAddress = 'https://batch-source.example.test/';
        $rootPath = 'laptop-notebook';
        $listingPath = 'laptop-notebook/live-test-laptop';
        $firstProductPath = $listingPath.'/batch-product-one';
        $secondProductPath = $listingPath.'/batch-product-two';
        $rootUrl = rtrim($sourceAddress, '/').'/'.$rootPath;
        $listingUrl = rtrim($sourceAddress, '/').'/'.$listingPath;
        $firstProductUrl = rtrim($sourceAddress, '/').'/'.$firstProductPath;
        $secondProductUrl = rtrim($sourceAddress, '/').'/'.$secondProductPath;
        $firstImageUrl = 'https://cdn.example.test/products/batch-product-one.png';
        $secondImageUrl = 'https://cdn.example.test/products/batch-product-two.png';
        $firstProductName = 'Batch Product One';
        $secondProductName = 'Batch Product Two';
        $description = 'Batch imported product used for the step-by-step regression test.';
        $brandName = 'Batch Test Brand';
        $seriesName = 'Batch Test Series';
        $firstSku = 'BATCH-1001';
        $secondSku = 'BATCH-1002';
        $createdImagePaths = [];
        $savedImportState = null;

        Http::fake([
            $sourceAddress => Http::response($this->fakeHomeHtml($sourceAddress), 200),
            rtrim($sourceAddress, '/') . '/sitemap.xml' => Http::response($this->fakeSitemapXml($sourceAddress), 200),
            $rootUrl => Http::response($this->fakeListingHtml('Laptop & Notebook', [$listingUrl]), 200),
            $listingUrl => Http::response($this->fakeListingHtml('Live Test Laptop', [$firstProductUrl, $secondProductUrl], [
                rtrim($sourceAddress, '/') . '/software',
                rtrim($sourceAddress, '/') . '/compare',
            ]), 200),
            $firstProductUrl => Http::response($this->fakeProductHtml($firstProductName, $description, $firstSku, $brandName, $seriesName, $firstImageUrl, '1599.00'), 200),
            $secondProductUrl => Http::response($this->fakeProductHtml($secondProductName, $description, $secondSku, $brandName, $seriesName, $secondImageUrl, '1699.00'), 200),
            $firstImageUrl => Http::response('first-image-bytes', 200, ['Content-Type' => 'image/png']),
            $secondImageUrl => Http::response('second-image-bytes', 200, ['Content-Type' => 'image/png']),
            rtrim($sourceAddress, '/') . '/*' => Http::response('<html><body></body></html>', 200),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $categoryId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            if (! $categoryId) {
                $categoryId = DB::table('category')->insertGetId([
                    'category_name' => 'Laptop & Notebook',
                    'category_description' => 'Laptop category for batch product import regression test.',
                    'icon_class' => 'fa-laptop',
                    'is_featured' => 1,
                    'display_order' => 2,
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $subCategoryId = DB::table('sub_category')
                ->where('category_id', $categoryId)
                ->where('sub_category_name', 'Live Test Laptop')
                ->value('sub_category_id');
            if (! $subCategoryId) {
                $subCategoryId = DB::table('sub_category')->insertGetId([
                    'category_id' => $categoryId,
                    'sub_category_name' => 'Live Test Laptop',
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $firstResponse = $this->withSession($this->catalogSession())
                ->from('/manage-product')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'steps' => ['products'],
                    'product_batch_size' => 1,
                ]);

            $firstResponse->assertRedirect('/manage-product')
                ->assertSessionHas('message', function (string $message) {
                    return str_contains($message, 'products remain') && str_contains($message, 'Fields captured:');
                })
                ->assertSessionHas('startech_product_import_state', function (array $state) use ($sourceAddress, &$savedImportState) {
                    $savedImportState = $state;
                    $this->assertSame($sourceAddress, $state['source_address'] ?? null);
                    $this->assertSame(1, (int) ($state['batch_size'] ?? 0));
                    $this->assertSame(1, (int) ($state['remaining'] ?? 0));
                    $this->assertNotEmpty($state['cursor'] ?? null);

                    return true;
                });

            $this->assertDatabaseHas('product', [
                'sku' => $firstSku,
                'product_name' => $firstProductName,
            ]);
            $this->assertDatabaseMissing('product', [
                'sku' => $secondSku,
            ]);

            $secondResponse = $this->withSession(array_merge($this->catalogSession(), [
                    'startech_product_import_state' => $savedImportState,
                ]))
                ->from('/manage-product')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'steps' => ['products'],
                    'product_batch_size' => 1,
                ]);

            $secondResponse->assertRedirect('/manage-product')
                ->assertSessionHas('message');

            $this->assertDatabaseHas('product', [
                'sku' => $firstSku,
                'product_name' => $firstProductName,
            ]);
            $this->assertDatabaseHas('product', [
                'sku' => $secondSku,
                'product_name' => $secondProductName,
            ]);

            $createdImagePaths = DB::table('product')
                ->whereIn('sku', [$firstSku, $secondSku])
                ->pluck('product_image')
                ->filter()
                ->map(static fn ($path) => (string) $path)
                ->all();
        } finally {
            foreach ($createdImagePaths as $createdImagePath) {
                if ($createdImagePath && is_file(public_path($createdImagePath))) {
                    unlink(public_path($createdImagePath));
                }
            }
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testAdministratorCanImportFiveRootlessProductsFromTheManageProductPage(): void
    {
        $sourceAddress = 'https://rootless-source.example.test/';
        $gadgetRootUrl = rtrim($sourceAddress, '/') . '/gadget';
        $listingUrl = rtrim($sourceAddress, '/') . '/earbuds';
        $productDefinitions = [
            ['path' => 'emeet-rootless-airflow-1', 'name' => 'EMEET Rootless AirFlow 1', 'sku' => 'EME-RA-1001'],
            ['path' => 'emeet-rootless-airflow-2', 'name' => 'EMEET Rootless AirFlow 2', 'sku' => 'EME-RA-1002'],
            ['path' => 'emeet-rootless-airflow-3', 'name' => 'EMEET Rootless AirFlow 3', 'sku' => 'EME-RA-1003'],
            ['path' => 'emeet-rootless-airflow-4', 'name' => 'EMEET Rootless AirFlow 4', 'sku' => 'EME-RA-1004'],
            ['path' => 'emeet-rootless-airflow-5', 'name' => 'EMEET Rootless AirFlow 5', 'sku' => 'EME-RA-1005'],
        ];
        $description = 'Rootless product imported through the manage product page.';
        $brandName = 'EMEET';
        $seriesName = 'AirFlow';
        $createdImagePaths = [];

        $listingProductUrls = array_map(static fn (array $product) => rtrim($sourceAddress, '/') . '/' . $product['path'], $productDefinitions);
        $httpResponses = [
            $sourceAddress => Http::response($this->fakeHomeHtml($sourceAddress), 200),
            rtrim($sourceAddress, '/') . '/sitemap.xml' => Http::response($this->fakeSitemapXml($sourceAddress), 200),
            $gadgetRootUrl => Http::response($this->fakeListingHtml('Gadget', [$listingUrl]), 200),
            $listingUrl => Http::response($this->fakeListingHtml('Earbuds', array_merge($listingProductUrls, [
                rtrim($sourceAddress, '/') . '/emeet-earbuds',
                rtrim($sourceAddress, '/') . '/compare',
            ])), 200),
            rtrim($sourceAddress, '/') . '/emeet-earbuds' => Http::response($this->fakeListingHtml('EMEET', $listingProductUrls), 200),
            rtrim($sourceAddress, '/') . '/compare' => Http::response('<html><body>Compare</body></html>', 200),
        ];

        foreach ($productDefinitions as $index => $product) {
            $productUrl = rtrim($sourceAddress, '/') . '/' . $product['path'];
            $imageUrl = 'https://cdn.example.test/products/'.$product['path'].'.png';
            $httpResponses[$productUrl] = Http::response($this->fakeProductHtml(
                $product['name'],
                $description,
                $product['sku'],
                $brandName,
                $seriesName,
                $imageUrl,
                (string) (18900 + ($index * 100)),
                [
                    [$sourceAddress . 'gadget', 'Gadget'],
                    [$sourceAddress . 'earbuds', 'Earbuds'],
                    [$sourceAddress . 'emeet-earbuds', 'EMEET'],
                    [$productUrl, $product['name']],
                ],
            ), 200);
            $httpResponses[$imageUrl] = Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/png']);
        }

        Http::fake($httpResponses);

        DB::beginTransaction();
        try {
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $categoryId = DB::table('category')->where('category_name', 'Gadget')->value('category_id');
            if (! $categoryId) {
                $categoryId = DB::table('category')->insertGetId([
                    'category_name' => 'Gadget',
                    'category_description' => 'Gadget category for rootless product import regression test.',
                    'icon_class' => 'fa-bolt',
                    'is_featured' => 1,
                    'display_order' => 10,
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $subCategoryId = DB::table('sub_category')
                ->where('category_id', $categoryId)
                ->where('sub_category_name', 'Earbuds')
                ->value('sub_category_id');
            if (! $subCategoryId) {
                $subCategoryId = DB::table('sub_category')->insertGetId([
                    'category_id' => $categoryId,
                    'sub_category_name' => 'Earbuds',
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $response = $this->withSession($this->catalogSession())
                ->from('/manage-product')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'steps' => ['products'],
                    'product_batch_size' => 5,
                ]);

            $response->assertRedirect('/manage-product')->assertSessionHas('message');

            $this->assertSame(5, DB::table('product')->whereIn('sku', array_column($productDefinitions, 'sku'))->count());
            foreach ($productDefinitions as $index => $productDefinition) {
                $product = DB::table('product')->where('sku', $productDefinition['sku'])->first();
                $this->assertNotNull($product);
                $this->assertSame($productDefinition['name'], $product->product_name);
                $this->assertSame((int) $categoryId, (int) $product->category_id);
                $this->assertSame((int) $subCategoryId, (int) $product->sub_category);
                $this->assertSame($description, $product->short_description);
                $this->assertSame((float) (18900 + ($index * 100)), (float) $product->regular_price);
                $this->assertSame('In Stock', $product->product_condition);
                $this->assertSame($brandName, DB::table('manufacturer')->where('manufacturer_id', $product->manufacturer_id)->value('manufacturer_name'));

                $createdImagePaths[] = (string) $product->product_image;
            }

            foreach ($createdImagePaths as $createdImagePath) {
                $this->assertStringStartsWith('asset/front-end/img/Product_image/product-', $createdImagePath);
                $this->assertFileExists(public_path($createdImagePath));
            }
        } finally {
            foreach ($createdImagePaths as $createdImagePath) {
                if ($createdImagePath && is_file(public_path($createdImagePath))) {
                    unlink(public_path($createdImagePath));
                }
            }
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testAdministratorCanImportAProductFromAnIndividualProductLinkOnTheManageProductPage(): void
    {
        $sourceAddress = 'https://single-product-source.example.test/';
        $productPath = 'gadget/sony-speaker-blast';
        $productUrl = rtrim($sourceAddress, '/') . '/' . $productPath;
        $imageUrl = 'https://cdn.example.test/products/sony-speaker-blast.png';
        $productName = 'Sony Speaker Blast';
        $description = 'Single product import triggered from a pasted product link.';
        $brandName = 'Sony';
        $price = '14990.00';
        $createdImagePath = null;

        Http::fake([
            $productUrl => Http::response($this->fakeProductHtml($productName, $description, 'SONY-BLAST-001', $brandName, 'Blast', $imageUrl, $price, [
                [$sourceAddress . 'gadget', 'Gadget'],
                [$productUrl, $productName],
            ]), 200),
            $imageUrl => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $categoryId = DB::table('category')->where('category_name', 'Gadget')->value('category_id');
            if (! $categoryId) {
                $categoryId = DB::table('category')->insertGetId([
                    'category_name' => 'Gadget',
                    'category_description' => 'Gadget category for single link import regression test.',
                    'icon_class' => 'fa-bolt',
                    'is_featured' => 1,
                    'display_order' => 10,
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $response = $this->withSession($this->catalogSession())
                ->from('/manage-product')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'steps' => ['products'],
                    'product_url' => $productUrl,
                ]);

            $response->assertRedirect('/manage-product')
                ->assertSessionHas('message', function (string $message) {
                    return str_contains($message, 'Single product import complete') && str_contains($message, '1 created');
                })
                ->assertSessionMissing('startech_product_import_state');

            $product = DB::table('product')->where('product_name', $productName)->first();
            $this->assertNotNull($product);
            $this->assertSame((int) $categoryId, (int) $product->category_id);
            $this->assertSame($description, $product->short_description);
            $this->assertSame(14990.0, (float) $product->regular_price);
            $this->assertSame('In Stock', $product->product_condition);
            $this->assertSame('SONY-BLAST-001', $product->sku);
            $this->assertSame($brandName, DB::table('manufacturer')->where('manufacturer_id', $product->manufacturer_id)->value('manufacturer_name'));

            $createdImagePath = (string) $product->product_image;
            $this->assertStringStartsWith('asset/front-end/img/Product_image/product-', $createdImagePath);
            $this->assertFileExists(public_path($createdImagePath));
        } finally {
            if ($createdImagePath && is_file(public_path($createdImagePath))) {
                unlink(public_path($createdImagePath));
            }
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testImporterCanImportAProductByUsingBreadcrumbCategoryInference(): void
    {
        $sourceAddress = 'https://breadcrumb-source.example.test/';
        $productPath = 'emeet-airflow';
        $productUrl = rtrim($sourceAddress, '/') . '/' . $productPath;
        $imageUrl = 'https://cdn.example.test/products/emeet-airflow.png';
        $productName = 'EMEET AirFlow';
        $description = 'Compact wireless earbuds imported through breadcrumb category inference.';
        $brandName = 'EMEET';
        $price = '18900.00';
        $createdImagePath = null;

        Http::fake([
            $productUrl => Http::response($this->fakeProductHtml($productName, $description, '', $brandName, 'AirFlow', $imageUrl, $price, [
                [$sourceAddress . 'gadget', 'Gadget'],
                [$sourceAddress . 'gadget/earbuds', 'Earbuds'],
                [$sourceAddress . 'emeet-earbuds', 'EMEET'],
                [$productUrl, $productName],
            ], false), 200),
            $imageUrl => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $categoryId = DB::table('category')->where('category_name', 'Gadget')->value('category_id');
            if (! $categoryId) {
                $categoryId = DB::table('category')->insertGetId([
                    'category_name' => 'Gadget',
                    'category_description' => 'Gadget category for breadcrumb product import regression test.',
                    'icon_class' => 'fa-bolt',
                    'is_featured' => 1,
                    'display_order' => 10,
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $subCategoryId = DB::table('sub_category')
                ->where('category_id', $categoryId)
                ->where('sub_category_name', 'Earbuds')
                ->value('sub_category_id');
            if (! $subCategoryId) {
                $subCategoryId = DB::table('sub_category')->insertGetId([
                    'category_id' => $categoryId,
                    'sub_category_name' => 'Earbuds',
                    'publication_status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $importer = app(\App\Services\StarTechCatalogImporter::class);
            $importer->setSourceAddress($sourceAddress);
            $result = $importer->importProductByPath($productPath);

            $this->assertSame(1, (int) ($result['created'] ?? 0) + (int) ($result['updated'] ?? 0));

            $product = DB::table('product')->where('product_name', $productName)->first();
            $this->assertNotNull($product);
            $this->assertSame((int) $categoryId, (int) $product->category_id);
            $this->assertSame((int) $subCategoryId, (int) $product->sub_category);
            $this->assertSame($description, $product->short_description);
            $this->assertSame(18900.0, (float) $product->regular_price);
            $this->assertSame('In Stock', $product->product_condition);
            $this->assertNull($product->product_series_id);
            $this->assertNotNull($product->manufacturer_id);
            $this->assertSame($brandName, DB::table('manufacturer')->where('manufacturer_id', $product->manufacturer_id)->value('manufacturer_name'));

            $createdImagePath = (string) $product->product_image;
            $this->assertStringStartsWith('asset/front-end/img/Product_image/product-', $createdImagePath);
            $this->assertFileExists(public_path($createdImagePath));
        } finally {
            if ($createdImagePath && is_file(public_path($createdImagePath))) {
                unlink(public_path($createdImagePath));
            }
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testAdministratorCanImportProductsCsvFromTheManageProductPage(): void
    {
        DB::beginTransaction();
        try {
            $categoryId = DB::table('category')->insertGetId([
                'category_name' => 'CSV Import Category',
                'category_code' => 'CATCSV'.strtoupper(substr(uniqid('', true), -5)),
                'category_description' => 'Category used for the product CSV import regression test.',
                'icon_class' => 'fa-folder-open',
                'is_featured' => 0,
                'display_order' => 999,
                'publication_status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $subCategoryId = DB::table('sub_category')->insertGetId([
                'category_id' => $categoryId,
                'sub_category_name' => 'CSV Import Subcategory',
                'subcategory_code' => 'SUBCSV'.strtoupper(substr(uniqid('', true), -5)),
                'publication_status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'CSV Import Company',
                'company_code' => 'COCSV'.strtoupper(substr(uniqid('', true), -5)),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $manufacturerId = DB::table('manufacturer')->insertGetId([
                'company_id' => $companyId,
                'manufacturer_name' => 'CSV Import Brand',
                'brand_code' => 'BRCSV'.strtoupper(substr(uniqid('', true), -5)),
                'publication_status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $productId = 'PROD-'.strtoupper(substr(uniqid('', true), -8));
            $csv = implode("\n", [
                'product_id,sku,barcode,product_name,product_model,category_id,sub_category,manufacturer_id,product_series_id,regular_price,offer_price,purchase_price,product_condition,stock_quantity,stock_tracking,warranty,publication_status,top_product,is_new_arrival,short_description',
                $productId.',,,"CSV Import Product","CSV-100",'.$categoryId.','.$subCategoryId.','.$manufacturerId.',,999.50,,750,In Stock,12,1,,1,0,1,',
            ]);
            $path = tempnam(sys_get_temp_dir(), 'products-csv-');
            file_put_contents($path, $csv);

            try {
                $file = new UploadedFile($path, 'products.csv', 'text/csv', null, true);

                $response = $this->withSession($this->catalogSession())
                    ->from('/manage-product')
                    ->post('/admin-data/products/import', [
                        'mode' => 'upsert',
                        'csv_file' => $file,
                    ]);

                $response->assertRedirect('/manage-product')->assertSessionHas('message');

                $this->assertDatabaseHas('product', [
                    'product_id' => $productId,
                    'product_name' => 'CSV Import Product',
                    'product_model' => 'CSV-100',
                    'category_id' => $categoryId,
                    'sub_category' => $subCategoryId,
                    'manufacturer_id' => $manufacturerId,
                ]);

                $product = DB::table('product')->where('product_id', $productId)->first();
                $this->assertNotNull($product);
                $this->assertNotNull($product->product_code);
                $this->assertSame($product->product_code, $product->sku);
                $this->assertNull($product->barcode);
                $this->assertNull($product->product_series_id);
                $this->assertNull($product->offer_price);
                $this->assertNull($product->warranty);
                $this->assertNull($product->short_description);
            } finally {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        } finally {
            DB::rollBack();
        }
    }

    public function testAdministratorCanChangeTheCatalogSourceAddressAndUseItForImports(): void
    {
        $sourceAddress = 'https://source.example.test/';

        Http::fake([
            $sourceAddress => Http::response($this->fakeHomeHtml($sourceAddress), 200),
            rtrim($sourceAddress, '/') . '/sitemap.xml' => Http::response($this->fakeSitemapXml($sourceAddress), 200),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $response = $this->withSession($this->catalogSession())
                ->from('/catalog-imports')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'steps' => ['categories', 'subcategories'],
                ]);

            $response->assertRedirect('/catalog-imports')->assertSessionHas('message');

            $this->assertDatabaseHas('site_settings', [
                'setting_key' => 'catalog_import_source_address',
                'setting_value' => $sourceAddress,
            ]);

            $categoryId = DB::table('category')->where('category_name', 'Laptop & Notebook')->value('category_id');
            $this->assertNotNull($categoryId);
            $this->assertTrue(
                DB::table('sub_category')
                    ->where('category_id', $categoryId)
                    ->where('sub_category_name', 'Quantum Station')
                    ->exists()
            );
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testCatalogImportCenterCanSaveSourceAddressWithoutRunningAnImport(): void
    {
        $sourceAddress = 'https://saved-source.example.test/';

        Http::fake();
        Http::preventStrayRequests();

        DB::beginTransaction();
        try {
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $response = $this->withSession($this->catalogSession())
                ->from('/catalog-imports')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'save_source_address' => '1',
                ]);

            $response->assertRedirect('/catalog-imports')
                ->assertSessionHas('message', 'Source address saved successfully.')
                ->assertSessionMissing('startech_catalog_import_preview');

            $this->assertDatabaseHas('site_settings', [
                'setting_key' => 'catalog_import_source_address',
                'setting_value' => $sourceAddress,
            ]);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testCatalogImportCenterShowsPreviewAfterFetchOnly(): void
    {
        $sourceAddress = 'https://www.startech.com.bd/';

        Http::fake([
            $sourceAddress => Http::response($this->fakeHomeHtml($sourceAddress), 200),
            rtrim($sourceAddress, '/') . '/sitemap.xml' => Http::response($this->fakeSitemapXml($sourceAddress), 200),
        ]);

        DB::beginTransaction();
        try {
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');

            $response = $this->withSession($this->catalogSession())
                ->from('/catalog-imports')
                ->post('/catalog-hierarchy/startech-import', [
                    'source_address' => $sourceAddress,
                    'dry_run' => '1',
                    'steps' => ['categories', 'brands'],
                ]);

            $response->assertRedirect('/catalog-imports')
                ->assertSessionHas('message')
                ->assertSessionHas('startech_catalog_import_preview', function (array $preview) use ($sourceAddress) {
                    $this->assertSame($sourceAddress, $preview['source_address'] ?? null);
                    $this->assertArrayHasKey('categories', $preview['results'] ?? []);
                    $this->assertArrayHasKey('brands', $preview['results'] ?? []);

                    return true;
                });

            $html = view('admin.components.startech-import', [
                'siteSettings' => collect(['catalog_import_source_address' => $sourceAddress]),
                'title' => 'Star Tech source import',
                'description' => 'Preview the source data before deciding what to import.',
                'sourceAddress' => $sourceAddress,
                'stepLabels' => [
                    'categories' => 'Categories',
                    'brands' => 'Brands',
                ],
                'previewResults' => [
                    'source_address' => $sourceAddress,
                    'summary' => 'Preview complete from '.$sourceAddress.'. Categories: 1 created, 0 updated. No database changes were saved.',
                    'results' => [
                        'categories' => ['created' => 1, 'updated' => 0],
                        'brands' => ['created' => 1, 'updated' => 0],
                    ],
                    'updated_at' => now()->toDateTimeString(),
                ],
                'showSourceImportPanelOverride' => true,
            ])->render();

            $this->assertStringContainsString('Last fetch preview', $html);
            $this->assertStringContainsString('Save source address', $html);
            $this->assertStringContainsString('Preview complete from '.$sourceAddress, $html);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            Cache::forget('mega-menu-tree');
            Cache::forget('xml-sitemap');
        }
    }

    public function testCatalogImportPagesUseTheConfiguredSourceName(): void
    {
        $sourceAddress = 'https://www.ryans.com/';

        DB::beginTransaction();
        try {
            DB::table('site_settings')->updateOrInsert(
                ['setting_key' => 'catalog_import_source_address'],
                ['setting_value' => $sourceAddress, 'created_at' => now(), 'updated_at' => now()]
            );
            Cache::forget('site-settings');
            \View::share('siteSettings', DB::table('site_settings')->pluck('setting_value', 'setting_key'));

            $expectedLabels = [
                '/manage-category' => 'Ryans category import',
                '/manage-subCategory' => 'Ryans source import for subcategories',
                '/manage-manufacturer' => 'Ryans source import for series',
                '/manage-product' => 'Ryans source import for products',
                '/catalog-attributes' => 'Ryans source import for attributes',
                '/catalog-hierarchy' => 'Ryans source import',
                '/catalog-imports' => 'Ryans source import',
            ];

            foreach ($expectedLabels as $path => $label) {
                $this->withSession($this->catalogSession())
                    ->get($path)
                    ->assertOk()
                    ->assertSee($label, false);
            }

            $this->withSession($this->catalogSession())
                ->get('/site-customization')
                ->assertOk()
                ->assertSee('Control whether the Ryans source import workspace appears on catalog admin pages.', false)
                ->assertSee('Show Ryans source import workspace', false);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }
}
