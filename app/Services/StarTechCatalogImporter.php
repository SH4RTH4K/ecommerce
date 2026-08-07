<?php

namespace App\Services;

use App\Support\PublicUpload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StarTechCatalogImporter
{
    private const DEFAULT_SOURCE_ADDRESS = 'https://www.startech.com.bd/';
    private const DEFAULT_COMPANY = 'Star Tech Imported Brands';

    private ?array $sourcePaths = null;
    private ?array $productCandidateState = null;
    private string $sourceAddress = self::DEFAULT_SOURCE_ADDRESS;
    private ?string $productCategorySlug = null;

    public function import(array $steps = ['categories', 'subcategories', 'brands', 'series'], bool $dryRun = false, ?string $sourceAddress = null, array $options = []): array
    {
        $this->setSourceAddress($sourceAddress);
        $steps = $this->normalizeSteps($steps);
        $results = [];

        foreach ($steps as $step) {
            if ($step === 'categories') {
                $results[$step] = $this->importCategories($dryRun);
            } elseif ($step === 'subcategories') {
                $results[$step] = $this->importSubcategories($dryRun, $options['subcategories'] ?? []);
            } elseif ($step === 'brands') {
                $results[$step] = $this->importBrands($dryRun);
            } elseif ($step === 'series') {
                $results[$step] = $this->importSeries($dryRun, $options['series'] ?? []);
            } elseif ($step === 'products') {
                $results[$step] = $this->importProducts($dryRun, $options['products'] ?? []);
            } elseif ($step === 'attributes') {
                $results[$step] = $this->importAttributes($dryRun);
            }
        }

        if (! $dryRun) {
            Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree');
            Cache::forget('xml-sitemap');
        }

        return $results;
    }

    public function setSourceAddress(?string $sourceAddress): self
    {
        $this->sourceAddress = $this->normalizeSourceAddress($sourceAddress);
        $this->sourcePaths = null;
        $this->productCandidateState = null;
        $this->productCategorySlug = null;

        return $this;
    }

    public function categoryMap(): array
    {
        return [
            'desktops' => ['name' => 'Desktops', 'icon' => 'fa-desktop', 'order' => 1, 'featured' => 1, 'description' => 'Desktop PCs, AI PCs, brand PCs and all-in-one systems.'],
            'laptop-notebook' => ['name' => 'Laptop & Notebook', 'icon' => 'fa-laptop', 'order' => 2, 'featured' => 1, 'description' => 'Laptop PCs, notebooks, ultrabooks and notebook accessories.'],
            'component' => ['name' => 'Components', 'icon' => 'fa-cogs', 'order' => 3, 'featured' => 1, 'description' => 'PC components for custom desktops, upgrades and repairs.'],
            'accessories' => ['name' => 'Accessories', 'icon' => 'fa-keyboard-o', 'order' => 4, 'featured' => 1, 'description' => 'Keyboards, mice, audio gear and everyday computer accessories.'],
            'networking' => ['name' => 'Networking', 'icon' => 'fa-sitemap', 'order' => 5, 'featured' => 1, 'description' => 'Routers, switches, access points and networking gear.'],
            'server-networking' => ['name' => 'Server & Networking', 'icon' => 'fa-server', 'order' => 6, 'featured' => 1, 'description' => 'Server hardware, storage and business networking solutions.'],
            'office-equipment' => ['name' => 'Office Equipment', 'icon' => 'fa-briefcase', 'order' => 7, 'featured' => 1, 'description' => 'Printers, scanners, shredders, attendance devices and office tools.'],
            'camera' => ['name' => 'Camera', 'icon' => 'fa-camera', 'order' => 8, 'featured' => 1, 'description' => 'Digital cameras, action cameras, lenses and camera accessories.'],
            'security-camera' => ['name' => 'Security Camera', 'icon' => 'fa-video-camera', 'order' => 9, 'featured' => 1, 'description' => 'CCTV, IP camera, PTZ camera and surveillance solutions.'],
            'gadget' => ['name' => 'Gadget', 'icon' => 'fa-bolt', 'order' => 10, 'featured' => 1, 'description' => 'Smart watches, earbuds, power banks, projectors and gadgets.'],
            'gaming' => ['name' => 'Gaming', 'icon' => 'fa-gamepad', 'order' => 11, 'featured' => 1, 'description' => 'Gaming PCs, accessories and gaming-focused hardware.'],
            'monitor' => ['name' => 'Monitor', 'icon' => 'fa-desktop', 'order' => 12, 'featured' => 1, 'description' => 'Monitors for office, gaming and content creation.'],
            'tv' => ['name' => 'TV', 'icon' => 'fa-television', 'order' => 13, 'featured' => 0, 'description' => 'TVs, Android TV boxes and display devices.'],
            'tablet-pc' => ['name' => 'Tablet PC', 'icon' => 'fa-tablet', 'order' => 14, 'featured' => 0, 'description' => 'Tablets, iPads and tablet accessories.'],
            'mobile' => ['name' => 'Mobile', 'icon' => 'fa-mobile', 'order' => 15, 'featured' => 0, 'description' => 'Smartphones, feature phones and mobile accessories.'],
            'appliance' => ['name' => 'Appliance', 'icon' => 'fa-plug', 'order' => 16, 'featured' => 0, 'description' => 'Home appliances for cooling, cooking and cleaning.'],
            'air-conditioner' => ['name' => 'Air Conditioner', 'icon' => 'fa-snowflake-o', 'order' => 17, 'featured' => 0, 'description' => 'Room and commercial air conditioning solutions.'],
            'air-cooler' => ['name' => 'Air Cooler', 'icon' => 'fa-snowflake-o', 'order' => 18, 'featured' => 0, 'description' => 'Evaporative coolers and room cooling products.'],
            'air-purifier' => ['name' => 'Air Purifier', 'icon' => 'fa-leaf', 'order' => 19, 'featured' => 0, 'description' => 'Air purifiers, filters and cleaner accessories.'],
            'access-control' => ['name' => 'Access Control', 'icon' => 'fa-lock', 'order' => 20, 'featured' => 0, 'description' => 'Access control, attendance and security entry devices.'],
            'software' => ['name' => 'Software', 'icon' => 'fa-certificate', 'order' => 21, 'featured' => 0, 'description' => 'Utility software, antivirus and productivity licenses.'],
        ];
    }

    public function seriesMap(): array
    {
        return [
            'Apple' => ['MacBook', 'iMac', 'Mac Mini', 'Mac Studio', 'Mac Pro', 'iPad', 'iPhone'],
            'Acer' => ['Predator', 'Nitro', 'Aspire', 'Swift', 'TravelMate'],
            'ASUS' => ['ROG', 'TUF Gaming', 'ProArt', 'Zenbook', 'Vivobook', 'ExpertBook'],
            'Dell' => ['Inspiron', 'Vostro', 'Latitude', 'XPS', 'Alienware', 'OptiPlex', 'Precision'],
            'Gigabyte' => ['AORUS', 'AERO', 'G5', 'G6', 'Gaming'],
            'HP' => ['Pavilion', 'Victus', 'OMEN', 'EliteBook', 'ProBook', 'Spectre', 'ENVY'],
            'Lenovo' => ['ThinkPad', 'IdeaPad', 'Legion', 'LOQ', 'Yoga', 'ThinkBook'],
            'MSI' => ['Creator', 'Stealth', 'Raider', 'Katana', 'Prestige', 'Modern', 'Thin'],
            'Microsoft' => ['Surface'],
            'Razer' => ['Blade'],
            'Walton' => ['Prelude', 'Tamarind', 'Karonda'],
            'Intel' => ['NUC'],
            'Zotac' => ['ZBOX'],
            'Beelink' => ['Mini PC'],
            'Minisforum' => ['Mini PC'],
        ];
    }

    public function brandAliases(): array
    {
        return [
            '1stplayer' => '1st Player',
            'acer' => 'Acer',
            'adata' => 'ADATA',
            'afox' => 'AFOX',
            'aipu' => 'Aipu',
            'apple' => 'Apple',
            'apc' => 'APC',
            'arctic-hunter' => 'Arctic Hunter',
            'arctic' => 'Arctic',
            'arktek' => 'Arktek',
            'asus' => 'ASUS',
            'aoc' => 'AOC',
            'anker' => 'Anker',
            'antec' => 'Antec',
            'apacer' => 'Apacer',
            'asrock' => 'ASRock',
            'beelink' => 'Beelink',
            'baseus' => 'Baseus',
            'bwoo' => 'BWOO',
            'boat' => 'boAt',
            'colorful' => 'Colorful',
            'cooler-master' => 'Cooler Master',
            'corsair' => 'Corsair',
            'cougar' => 'Cougar',
            'chuwi' => 'Chuwi',
            'deli' => 'Deli',
            'dahua' => 'Dahua',
            'deepcool' => 'DeepCool',
            'd-link' => 'D-Link',
            'dell' => 'Dell',
            'edifier' => 'Edifier',
            'fantech' => 'Fantech',
            'g-skill' => 'G.Skill',
            'gamdias' => 'Gamdias',
            'gigabyte' => 'Gigabyte',
            'gunnir' => 'GUNNIR',
            'havit' => 'Havit',
            'haylou' => 'Haylou',
            'honor' => 'Honor',
            'hoco' => 'HOCO',
            'hp' => 'HP',
            'huawei' => 'Huawei',
            'imiki' => 'IMIKI',
            'inno3d' => 'Inno3D',
            'intel' => 'Intel',
            'kingbank' => 'KingBank',
            'kieslect' => 'Kieslect',
            'kospet' => 'Kospet',
            'kingston' => 'Kingston',
            'lexar' => 'Lexar',
            'luminous' => 'Luminous',
            'lenovo' => 'Lenovo',
            'logitech' => 'Logitech',
            'manli' => 'Manli',
            'maxgreen' => 'MaxGreen',
            'maxsun' => 'Maxsun',
            'microsoft' => 'Microsoft',
            'minisforum' => 'Minisforum',
            'mibro' => 'Mibro',
            'msi' => 'MSI',
            'orient' => 'Orient',
            'netac' => 'Netac',
            'nvidia' => 'NVIDIA',
            'nzxt' => 'NZXT',
            'ocpc' => 'OCPC',
            'ocypus' => 'Ocypus',
            'oneplus' => 'OnePlus',
            'oppo' => 'OPPO',
            'pny' => 'PNY',
            'powercolor' => 'PowerColor',
            'razer' => 'Razer',
            'realme' => 'Realme',
            'rapoo' => 'Rapoo',
            'redragon' => 'Redragon',
            'samsung' => 'Samsung',
            'sapphire' => 'Sapphire',
            'seagate' => 'Seagate',
            'sony' => 'Sony',
            'tecno' => 'Tecno',
            'teclast' => 'Teclast',
            'team' => 'Team',
            'transcend' => 'Transcend',
            'tp-link' => 'TP-Link',
            'tplink' => 'TP-Link',
            'thunderobot' => 'Thunderobot',
            'targus' => 'Targus',
            'tucano' => 'Tucano',
            'ugreen' => 'UGREEN',
            'unika' => 'Unika',
            'uphere' => 'UpHere',
            'v-color' => 'V-Color',
            'value-top' => 'Value-Top',
            'viewsonic' => 'ViewSonic',
            'walton' => 'Walton',
            'western-digital' => 'Western Digital',
            'wd' => 'Western Digital',
            'wiwu' => 'WIWU',
            'xiaomi' => 'Xiaomi',
            'xprinter' => 'Xprinter',
            'zeblaze' => 'Zeblaze',
            'xigmatek' => 'Xigmatek',
            'yeston' => 'Yeston',
            'zotac' => 'Zotac',
            'awei' => 'Awei',
            'ajazz' => 'AJAZZ',
            'hikvision' => 'Hikvision',
            'mercusys' => 'Mercusys',
            'philips' => 'Philips',
            'qulik' => 'Qulik',
            'ubiquiti' => 'Ubiquiti',
            'vivo' => 'vivo',
            'zkteco' => 'ZKTeco',
        ];
    }

    public function importCategories(bool $dryRun = false): array
    {
        $created = 0;
        $updated = 0;
        $defaultDescription = 'Imported from the Star Tech public catalog structure.';

        foreach ($this->categoryMap() as $slug => $meta) {
            $existing = DB::table('category')->where('category_name', $meta['name'])->first();
            $payload = [
                'category_name' => $meta['name'],
                'category_description' => $meta['description'] ?? $defaultDescription,
                'icon_class' => $meta['icon'] ?? 'fa-folder-open',
                'is_featured' => (int) ($meta['featured'] ?? 0),
                'display_order' => (int) ($meta['order'] ?? 0),
                'publication_status' => 1,
                'updated_at' => now(),
            ] + $this->restoreSoftDeletedPayload('category');
            $payload = $this->withImportBusinessCode($payload, 'category', 'category', 'category_code', $meta['name'], 'CAT', $existing, 'category_id', [
                'category_name' => $meta['name'],
            ], $dryRun);
            if ($existing) {
                $updated++;
                if (! $dryRun) {
                    DB::table('category')->where('category_id', $existing->category_id)->update($payload);
                }
            } else {
                $created++;
                if (! $dryRun) {
                    $payload['created_at'] = now();
                    DB::table('category')->insert($payload);
                }
            }
        }

        return compact('created', 'updated');
    }

    public function importSubcategories(bool $dryRun = false, array $options = []): array
    {
        $created = 0;
        $updated = 0;
        $paths = $this->sourcePaths();
        $categoryLookup = $this->categoryLookup();
        $selectedRootSlug = null;
        $selectedCategoryId = isset($options['category_id']) ? (int) $options['category_id'] : null;
        if ($selectedCategoryId) {
            $selectedCategoryName = DB::table('category')
                ->where('category_id', $selectedCategoryId)
                ->value('category_name');
            $selectedRootSlug = $this->sourceCategorySlugFromName($selectedCategoryName);
            if ($selectedRootSlug === null) {
                return compact('created', 'updated');
            }
        }

        foreach ($this->categoryMap() as $rootSlug => $meta) {
            if ($selectedRootSlug !== null && $rootSlug !== $selectedRootSlug) {
                continue;
            }

            $categoryId = $categoryLookup[$meta['name']] ?? null;
            if (! $categoryId) {
                continue;
            }

            $candidates = $this->discoverSubcategoryCandidates($rootSlug, $paths);
            foreach ($candidates as $candidate) {
                $existing = DB::table('sub_category')
                    ->where('category_id', $categoryId)
                    ->where('sub_category_name', $candidate)
                    ->first();

                $payload = [
                    'category_id' => $categoryId,
                    'sub_category_name' => $candidate,
                    'publication_status' => 1,
                    'updated_at' => now(),
                ] + $this->restoreSoftDeletedPayload('sub_category');
                $payload = $this->withImportBusinessCode($payload, 'subcategory', 'sub_category', 'subcategory_code', $candidate, 'SUB', $existing, 'sub_category_id', [
                    'category_id' => $categoryId,
                    'category_name' => $meta['name'],
                    'subcategory_name' => $candidate,
                ], $dryRun);

                if ($existing) {
                    $updated++;
                    if (! $dryRun) {
                        DB::table('sub_category')->where('sub_category_id', $existing->sub_category_id)->update($payload);
                    }
                } else {
                    $created++;
                    if (! $dryRun) {
                        $payload['created_at'] = now();
                        DB::table('sub_category')->insert($payload);
                    }
                }
            }
        }

        return compact('created', 'updated');
    }

    public function importBrands(bool $dryRun = false): array
    {
        $created = 0;
        $updated = 0;
        $companyId = $this->defaultCompanyId($dryRun);
        $brands = array_values(array_unique(array_merge(
            $this->discoverBrands(),
            array_keys($this->seriesMap())
        )));
        sort($brands);

        foreach ($brands as $brandName) {
            $existing = DB::table('manufacturer')->where('manufacturer_name', $brandName)->first();
            $payload = [
                'company_id' => $companyId,
                'manufacturer_name' => $brandName,
                'publication_status' => 1,
                'updated_at' => now(),
            ] + $this->restoreSoftDeletedPayload('manufacturer');
            $payload = $this->withImportBusinessCode($payload, 'brand', 'manufacturer', 'brand_code', $brandName, 'BR', $existing, 'manufacturer_id', [
                'company_id' => $companyId,
                'brand_name' => $brandName,
                'manufacturer_name' => $brandName,
            ], $dryRun);

            if ($existing) {
                $updated++;
                if (! $dryRun) {
                    DB::table('manufacturer')->where('manufacturer_id', $existing->manufacturer_id)->update($payload);
                }
            } else {
                $created++;
                if (! $dryRun) {
                    $payload['created_at'] = now();
                    DB::table('manufacturer')->insert($payload);
                }
            }
        }

        return compact('created', 'updated');
    }

    public function importSeries(bool $dryRun = false, array $options = []): array
    {
        $created = 0;
        $updated = 0;
        $brandLookup = DB::table('manufacturer')->pluck('manufacturer_id', 'manufacturer_name')->all();
        $selectedBrandName = null;
        $selectedManufacturerId = isset($options['manufacturer_id']) ? (int) $options['manufacturer_id'] : null;
        if ($selectedManufacturerId) {
            $selectedManufacturerName = DB::table('manufacturer')
                ->where('manufacturer_id', $selectedManufacturerId)
                ->value('manufacturer_name');
            $selectedBrandName = $this->sourceBrandNameFromName($selectedManufacturerName);
            if ($selectedBrandName === null) {
                return compact('created', 'updated');
            }
        }

        foreach ($this->seriesMap() as $brandName => $seriesNames) {
            if ($selectedBrandName !== null && $brandName !== $selectedBrandName) {
                continue;
            }

            $manufacturerId = $brandLookup[$brandName] ?? null;
            if (! $manufacturerId) {
                continue;
            }

            foreach ($seriesNames as $seriesName) {
                $existing = DB::table('product_series')
                    ->where('manufacturer_id', $manufacturerId)
                    ->where('name', $seriesName)
                    ->first();

                $payload = [
                    'manufacturer_id' => $manufacturerId,
                    'name' => $seriesName,
                    'is_active' => 1,
                    'updated_at' => now(),
                ] + $this->restoreSoftDeletedPayload('product_series');
                $payload = $this->withImportBusinessCode($payload, 'series', 'product_series', 'series_code', $seriesName, 'SER', $existing, 'id', [
                    'manufacturer_id' => $manufacturerId,
                    'brand_name' => $brandName,
                    'series_name' => $seriesName,
                ], $dryRun);

                if ($existing) {
                    $updated++;
                    if (! $dryRun) {
                        DB::table('product_series')->where('id', $existing->id)->update($payload);
                    }
                } else {
                    $created++;
                    if (! $dryRun) {
                        $payload['created_at'] = now();
                        DB::table('product_series')->insert($payload);
                    }
                }
            }
        }

        return compact('created', 'updated');
    }

    public function importProducts(bool $dryRun = false, array $options = []): array
    {
        $previousCategorySlug = $this->productCategorySlug;
        $this->productCategorySlug = $this->normalizeCategorySlug($options['category_slug'] ?? null);

        $created = 0;
        $updated = 0;
        $fieldCapture = [
            'captured' => 0,
            'possible' => 0,
            'products' => [],
        ];
        try {
            $paths = $this->sourcePathsForProducts();
            $categoryLookup = $this->categoryLookupBySourceSlug();
            $subcategoryLookup = $this->subcategoryLookupBySourceSlug();
            $batchSize = isset($options['batch_size']) ? max(1, (int) $options['batch_size']) : null;
            $cursor = trim((string) ($options['cursor'] ?? ''));
            if ($batchSize === null) {
                $paths = $this->buildProductCandidatePaths($paths);
                $startIndex = $this->productPathStartIndex($paths, $cursor);
                $batchPaths = array_slice($paths, $startIndex);
            } else {
                $paths = $this->productCandidatePaths($paths, $batchSize);
                $startIndex = $this->productPathStartIndex($paths, $cursor);
                $desiredCount = $startIndex + $batchSize;
                if (count($paths) < $desiredCount) {
                    $paths = $this->productCandidatePaths($this->sourcePathsForProducts(), $desiredCount);
                    $startIndex = $this->productPathStartIndex($paths, $cursor);
                }
                $batchPaths = array_slice($paths, $startIndex, $batchSize);
            }
            $processed = 0;

            foreach ($batchPaths as $path) {
                $processed++;
                $product = $this->scrapeProductPage($path);
                if (! $product) {
                    continue;
                }

                $categoryId = $categoryLookup[$product['source_category_slug']] ?? null;
                if (! $categoryId) {
                    continue;
                }

                $subCategoryId = null;
                if (! empty($product['subcategory_slug'])) {
                    $subCategoryId = $subcategoryLookup[$product['source_category_slug']][$product['subcategory_slug']] ?? null;
                }

                $captureStats = $this->captureProductFieldStats($product);
                $fieldCapture['captured'] += $captureStats['captured'];
                $fieldCapture['possible'] += $captureStats['possible'];
                $fieldCapture['products'][] = [
                    'name' => $product['product_name'],
                    'captured' => $captureStats['captured'],
                    'possible' => $captureStats['possible'],
                ];

                $manufacturerId = $this->resolveManufacturerId($product['brand_name'] ?? null, $dryRun);
                $productSeriesId = $this->resolveSeriesId($manufacturerId, $product['series_name'] ?? null, $dryRun);
                $sourceCode = normalize_product_code($product['sku'] ?: ($product['product_code'] ?? null), 100);
                $existing = $sourceCode !== null && $sourceCode !== ''
                    ? DB::table('product')->where(function ($query) use ($sourceCode) {
                        $query->where('product_code', $sourceCode)->orWhere('sku', $sourceCode);
                    })->first()
                    : DB::table('product')->where('product_id', $product['product_id'])->first();
                $sku = $sourceCode !== null && $sourceCode !== ''
                    ? $sourceCode
                    : trim((string) ($existing->sku ?? ''));
                $productCode = $this->businessCodeForImport('product', 'product', 'product_code', $product['product_name'], 'PRD', $existing, 'id', [
                    'company_id' => $manufacturerId ? (int) DB::table('manufacturer')->where('manufacturer_id', $manufacturerId)->value('company_id') : null,
                    'category_id' => $categoryId,
                    'subcategory_id' => $subCategoryId,
                    'manufacturer_id' => $manufacturerId,
                    'series_id' => $productSeriesId,
                    'source_code' => $sourceCode,
                    'sku' => $sku,
                    'product_name' => $product['product_name'],
                    'product_model' => $product['product_model'] ?: $product['product_name'],
                ], $dryRun);

                $payload = [
                    'product_id' => $product['product_id'],
                    'product_code' => $productCode ?: null,
                    'sku' => $sku ?: null,
                    'barcode' => $product['barcode'] ?: null,
                    'company_id' => $manufacturerId ? (int) DB::table('manufacturer')->where('manufacturer_id', $manufacturerId)->value('company_id') : null,
                    'category_id' => $categoryId,
                    'sub_category' => $subCategoryId,
                    'manufacturer_id' => $manufacturerId,
                    'product_series_id' => $productSeriesId,
                    'product_model' => $product['product_model'] ?: $product['product_name'],
                    'product_name' => $product['product_name'],
                    'Product_description' => $product['product_description'] ?: $product['short_description'],
                    'short_description' => $product['short_description'] ?: null,
                    'key_features' => json_encode($product['key_features'] ?? []),
                    'specifications' => json_encode($product['specifications'] ?? []),
                    'gallery_images' => json_encode($dryRun ? [] : $this->downloadProductImages($product['gallery_images'] ?? [])),
                    'regular_price' => $product['regular_price'],
                    'offer_price' => $product['offer_price'],
                    'purchase_price' => 0,
                    'product_condition' => $product['product_condition'],
                    'stock_quantity' => 0,
                    'stock_tracking' => 0,
                    'warranty' => $product['warranty'] ?: null,
                    'publication_status' => 1,
                    'top_product' => 0,
                    'is_new_arrival' => 0,
                    'seo_title' => $product['seo_title'] ?: $product['product_name'],
                    'seo_description' => $product['seo_description'] ?: $product['short_description'],
                    'product_image' => $dryRun ? 'asset/front-end/img/home/pic 1.jpg' : ($this->downloadProductImage($product['image_url'] ?? null) ?: 'asset/front-end/img/home/pic 1.jpg'),
                    'updated_at' => now(),
                ];

                if ($existing) {
                    $updated++;
                    if (! $dryRun) {
                        DB::table('product')->where('id', $existing->id)->update($payload);
                    }
                } else {
                    $created++;
                    if (! $dryRun) {
                        $payload['created_at'] = now();
                        DB::table('product')->insert($payload);
                    }
                }
            }

            $nextIndex = $startIndex + count($batchPaths);
            $remaining = max(0, count($paths) - $nextIndex);
            if ($batchSize !== null && ! ($this->productCandidateState['exhausted'] ?? false) && $remaining === 0) {
                $remaining = 1;
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'processed' => $processed,
                'total' => count($paths),
                'remaining' => $remaining,
                'has_more' => $batchSize !== null ? ($remaining > 0) : false,
                'next_cursor' => $batchPaths !== [] ? $batchPaths[array_key_last($batchPaths)] : null,
                'field_capture' => $fieldCapture,
            ];
        } finally {
            $this->productCategorySlug = $previousCategorySlug;
        }
    }

    private function buildProductCandidatePaths(array $paths): array
    {
        $paths = array_values(array_unique(array_filter(array_map([$this, 'normalizePath'], $paths))));
        if ($paths === []) {
            return [];
        }

        return $this->productCandidatePaths($paths, max(1, count($paths)));
    }

    public function importProductByPath(string $path, bool $dryRun = false): array
    {
        $path = trim($path, '/');
        if ($path === '') {
            return ['created' => 0, 'updated' => 0];
        }

        $categoryLookup = $this->categoryLookupBySourceSlug();
        $subcategoryLookup = $this->subcategoryLookupBySourceSlug();
        $product = $this->scrapeProductPage($path);
        if (! $product) {
            return ['created' => 0, 'updated' => 0];
        }

        $categoryId = $categoryLookup[$product['source_category_slug']] ?? null;
        if (! $categoryId) {
            return ['created' => 0, 'updated' => 0];
        }

        $subCategoryId = null;
        if (! empty($product['subcategory_slug'])) {
            $subCategoryId = $subcategoryLookup[$product['source_category_slug']][$product['subcategory_slug']] ?? null;
        }

        $captureStats = $this->captureProductFieldStats($product);
        $manufacturerId = $this->resolveManufacturerId($product['brand_name'] ?? null, $dryRun);
        $productSeriesId = $this->resolveSeriesId($manufacturerId, $product['series_name'] ?? null, $dryRun);
        $existing = ! empty($product['sku'])
            ? DB::table('product')->where('sku', $product['sku'])->first()
            : DB::table('product')->where('product_id', $product['product_id'])->first();

        $payload = [
            'product_id' => $product['product_id'],
            'sku' => $product['sku'] ?: null,
            'barcode' => $product['barcode'] ?: null,
            'category_id' => $categoryId,
            'sub_category' => $subCategoryId,
            'manufacturer_id' => $manufacturerId,
            'product_series_id' => $productSeriesId,
            'product_model' => $product['product_model'] ?: $product['product_name'],
            'product_name' => $product['product_name'],
            'Product_description' => $product['product_description'] ?: $product['short_description'],
            'short_description' => $product['short_description'] ?: null,
            'key_features' => json_encode($product['key_features'] ?? []),
            'specifications' => json_encode($product['specifications'] ?? []),
            'gallery_images' => json_encode($dryRun ? [] : $this->downloadProductImages($product['gallery_images'] ?? [])),
            'regular_price' => $product['regular_price'],
            'offer_price' => $product['offer_price'],
            'purchase_price' => 0,
            'product_condition' => $product['product_condition'],
            'stock_quantity' => 0,
            'stock_tracking' => 0,
            'warranty' => $product['warranty'] ?: null,
            'publication_status' => 1,
            'top_product' => 0,
            'is_new_arrival' => 0,
            'seo_title' => $product['seo_title'] ?: $product['product_name'],
            'seo_description' => $product['seo_description'] ?: $product['short_description'],
            'product_image' => $dryRun ? 'asset/front-end/img/home/pic 1.jpg' : ($this->downloadProductImage($product['image_url'] ?? null) ?: 'asset/front-end/img/home/pic 1.jpg'),
            'updated_at' => now(),
        ];

        if ($existing) {
            if (! $dryRun) {
                DB::table('product')->where('id', $existing->id)->update($payload);
            }

            return [
                'created' => 0,
                'updated' => 1,
                'field_capture' => [
                    'captured' => $captureStats['captured'],
                    'possible' => $captureStats['possible'],
                    'products' => [[
                        'name' => $product['product_name'],
                        'captured' => $captureStats['captured'],
                        'possible' => $captureStats['possible'],
                    ]],
                ],
            ];
        }

        if (! $dryRun) {
            $payload['created_at'] = now();
            DB::table('product')->insert($payload);
        }

        return [
            'created' => 1,
            'updated' => 0,
            'field_capture' => [
                'captured' => $captureStats['captured'],
                'possible' => $captureStats['possible'],
                'products' => [[
                    'name' => $product['product_name'],
                    'captured' => $captureStats['captured'],
                    'possible' => $captureStats['possible'],
                ]],
            ],
        ];
    }

    public function importAttributes(bool $dryRun = false): array
    {
        $created = 0;
        $updated = 0;
        $categoryLookup = $this->categoryLookupBySlug();

        foreach ($this->attributeBlueprints() as $categorySlug => $attributes) {
            $categoryId = $categoryLookup[$categorySlug] ?? null;
            if (! $categoryId) {
                continue;
            }

            foreach ($attributes as $index => $attributeName) {
                $label = trim((string) $attributeName);
                if ($label === '') {
                    continue;
                }

                $slug = Str::slug($label);
                $existing = DB::table('catalog_attributes')
                    ->where('category_id', $categoryId)
                    ->where('slug', $slug)
                    ->first();

                $payload = [
                    'category_id' => $categoryId,
                    'name' => $label,
                    'slug' => $slug,
                    'input_type' => 'text',
                    'options' => null,
                    'is_filterable' => 1,
                    'is_comparable' => 1,
                    'display_order' => ($index + 1) * 10,
                    'updated_at' => now(),
                ];

                if ($existing) {
                    $updated++;
                    if (! $dryRun) {
                        DB::table('catalog_attributes')->where('id', $existing->id)->update($payload);
                    }
                } else {
                    $created++;
                    if (! $dryRun) {
                        $payload['created_at'] = now();
                        DB::table('catalog_attributes')->insert($payload);
                    }
                }
            }
        }

        return compact('created', 'updated');
    }

    public function categoryLookup(): array
    {
        return DB::table('category')->pluck('category_id', 'category_name')->all();
    }

    private function categoryLookupBySlug(): array
    {
        return DB::table('category')
            ->get(['category_id', 'category_name'])
            ->mapWithKeys(function ($category) {
                return [Str::slug($category->category_name) => (int) $category->category_id];
            })
            ->all();
    }

    private function categoryLookupBySourceSlug(): array
    {
        $categoryLookup = $this->categoryLookup();

        return collect($this->categoryMap())
            ->mapWithKeys(function (array $meta, string $sourceSlug) use ($categoryLookup) {
                return [$sourceSlug => $categoryLookup[$meta['name']] ?? null];
            })
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function subcategoryLookupBySourceSlug(): array
    {
        $categorySourceSlugByName = collect($this->categoryMap())
            ->mapWithKeys(function (array $meta, string $sourceSlug) {
                return [$meta['name'] => $sourceSlug];
            })
            ->all();

        $lookup = [];
        foreach (DB::table('sub_category as s')
            ->join('category as c', 'c.category_id', '=', 's.category_id')
            ->get(['s.sub_category_id', 's.sub_category_name', 'c.category_name']) as $subCategory) {
            $sourceCategorySlug = $categorySourceSlugByName[$subCategory->category_name] ?? Str::slug($subCategory->category_name);
            $lookup[$sourceCategorySlug][Str::slug($subCategory->sub_category_name)] = (int) $subCategory->sub_category_id;
        }

        return $lookup;
    }

    private function productCandidatePaths(?array $paths = null, ?int $minimumCount = null): array
    {
        $paths = $paths ?? $this->sourcePaths();
        $state = $this->loadProductCandidateState($paths);
        $knownPaths = array_fill_keys(array_map(static fn ($path) => trim((string) $path, '/'), $paths), true);
        $allowedRoots = $this->productCandidateRootSlugs($paths);
        $deadline = microtime(true) + 15;
        $targetCount = max(1, (int) ($minimumCount ?? 0));

        $this->extendProductCandidateStateFromSourcePaths($state, $paths, $targetCount, $deadline);
        if (count($state['candidates']) < $targetCount) {
            $this->extendProductCandidateState($state, $knownPaths, $targetCount, $deadline, $allowedRoots);
        }
        $this->saveProductCandidateState($state);

        return array_keys($state['candidates']);
    }

    private function productPathStartIndex(array $paths, string $cursor): int
    {
        if ($cursor === '' || $paths === []) {
            return 0;
        }

        $exactIndex = array_search($cursor, $paths, true);
        if ($exactIndex !== false) {
            return ((int) $exactIndex) + 1;
        }

        foreach ($paths as $index => $path) {
            if (strcmp($path, $cursor) > 0) {
                return (int) $index;
            }
        }

        return count($paths);
    }

    private function productCandidateStateKey(): string
    {
        return 'startech-product-crawl:v5:'.sha1($this->sourceAddress.'|'.($this->productCategorySlug ?? 'all'));
    }

    private function normalizeCategorySlug(mixed $categorySlug): ?string
    {
        $categorySlug = trim((string) $categorySlug);
        if ($categorySlug === '') {
            return null;
        }

        return array_key_exists($categorySlug, $this->categoryMap()) ? $categorySlug : null;
    }

    private function sourcePathsForProducts(): array
    {
        $paths = $this->sourcePaths();
        if ($this->productCategorySlug === null) {
            return $paths;
        }

        $rootSlug = $this->productCategorySlug;
        $prefix = $rootSlug.'/';

        return array_values(array_filter($paths, static function (string $path) use ($rootSlug, $prefix): bool {
            return $path === $rootSlug || Str::startsWith($path, $prefix);
        }));
    }

    private function loadProductCandidateState(array $paths): array
    {
        $pathsSignature = sha1(implode("\n", array_map(static fn ($path) => trim((string) $path, '/'), $paths)));
        if (is_array($this->productCandidateState) && ($this->productCandidateState['paths_signature'] ?? null) === $pathsSignature) {
            return $this->productCandidateState;
        }

        $state = Cache::get($this->productCandidateStateKey());
        if (! is_array($state) || ($state['paths_signature'] ?? null) !== $pathsSignature) {
            $state = [
                'paths_signature' => $pathsSignature,
                'candidates' => [],
                'source_cursor' => '',
                'visited_listings' => [],
                'queue' => array_map(static fn (string $rootSlug) => ['root' => $rootSlug, 'path' => $rootSlug], $this->productCandidateRootSlugs($paths)),
                'exhausted' => false,
            ];
        } else {
            $state['candidates'] = $this->normalizeProductCandidateList($state['candidates'] ?? []);
            $state['source_cursor'] = trim((string) ($state['source_cursor'] ?? ''));
            $state['visited_listings'] = is_array($state['visited_listings'] ?? null) ? $state['visited_listings'] : [];
            $state['queue'] = $this->normalizeProductCandidateQueue($state['queue'] ?? []);
            $state['exhausted'] = (bool) ($state['exhausted'] ?? false);
        }

        $this->productCandidateState = $state;

        return $state;
    }

    private function productCandidateRootSlugs(array $paths): array
    {
        $roots = [];
        foreach ($paths as $path) {
            $path = trim((string) $path, '/');
            if ($path === '') {
                continue;
            }

            $root = explode('/', $path, 2)[0];
            if ($root === '' || isset($roots[$root])) {
                continue;
            }

            $roots[$root] = true;
        }

        return array_keys($roots);
    }

    private function saveProductCandidateState(array $state): void
    {
        $state['queue'] = $this->normalizeProductCandidateQueue($state['queue'] ?? []);
        $state['candidates'] = $this->normalizeProductCandidateList($state['candidates'] ?? []);
        $state['source_cursor'] = trim((string) ($state['source_cursor'] ?? ''));
        $state['visited_listings'] = is_array($state['visited_listings'] ?? null) ? $state['visited_listings'] : [];
        $state['exhausted'] = (bool) ($state['exhausted'] ?? false);

        $this->productCandidateState = $state;
        Cache::put($this->productCandidateStateKey(), $state, now()->addHours(6));
    }

    private function extendProductCandidateStateFromSourcePaths(array &$state, array $paths, int $targetCount, float $deadline): void
    {
        $paths = array_values(array_unique(array_filter(array_map([$this, 'normalizePath'], $paths))));
        if ($paths === []) {
            return;
        }

        $candidates = $this->normalizeProductCandidateList($state['candidates'] ?? []);
        $sourceCursor = trim((string) ($state['source_cursor'] ?? ''));
        $startIndex = $this->productPathStartIndex($paths, $sourceCursor);

        for ($index = $startIndex; $index < count($paths) && microtime(true) < $deadline; $index++) {
            $path = trim((string) $paths[$index], '/');
            if ($path === '') {
                continue;
            }

            $sourceCursor = $path;
            if (isset($candidates[$path])) {
                if (count($candidates) >= $targetCount) {
                    break;
                }

                continue;
            }

            $product = $this->scrapeProductPage($path);
            if (! $product) {
                continue;
            }

            $candidates[$path] = true;
            if (count($candidates) >= $targetCount) {
                break;
            }
        }

        $state['source_cursor'] = $sourceCursor;
        $state['candidates'] = $candidates;
    }

    private function normalizeProductCandidateQueue(array $queue): array
    {
        $normalized = [];

        foreach ($queue as $entry) {
            if (is_string($entry)) {
                $path = trim($entry, '/');
                if ($path === '') {
                    continue;
                }

                $rootSlug = explode('/', $path, 2)[0] ?? '';
                if ($rootSlug === '') {
                    continue;
                }

                $normalized[] = ['root' => $rootSlug, 'path' => $path];
                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $rootSlug = trim((string) ($entry['root'] ?? ''), '/');
            $path = trim((string) ($entry['path'] ?? ''), '/');
            if ($rootSlug === '' || $path === '') {
                continue;
            }

            $normalized[] = ['root' => $rootSlug, 'path' => $path];
        }

        return array_values($normalized);
    }

    private function normalizeProductCandidateList(array $candidates): array
    {
        $normalized = [];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate, '/');
            if ($candidate !== '') {
                $normalized[$candidate] = true;
            }
        }

        return $normalized;
    }

    private function extendProductCandidateState(array &$state, array $knownPaths, int $targetCount, float $deadline, array $allowedRoots): void
    {
        $queue = $this->normalizeProductCandidateQueue($state['queue'] ?? []);
        $visitedListings = is_array($state['visited_listings'] ?? null) ? $state['visited_listings'] : [];
        $candidates = $this->normalizeProductCandidateList($state['candidates'] ?? []);

        while ($queue !== [] && microtime(true) < $deadline && count($candidates) < $targetCount) {
            $entry = array_shift($queue);
            $rootSlug = trim((string) ($entry['root'] ?? ''), '/');
            $listingPath = trim((string) ($entry['path'] ?? ''), '/');
            if ($rootSlug === '' || $listingPath === '') {
                continue;
            }

            $listingKey = $rootSlug.'|'.$listingPath;
            if (isset($visitedListings[$listingKey])) {
                continue;
            }

            $visitedListings[$listingKey] = true;
            $page = $this->loadSourcePage($listingPath);
            if (! $page) {
                continue;
            }

            if ($page['schema'] || Str::contains($page['page_type'], 'product')) {
                $candidates[$listingPath] = true;
                continue;
            }

            $children = [];
            foreach ($this->extractInternalLinkPathsFromHtml($page['html']) as $linkedPath) {
                $linkedKey = trim((string) $linkedPath, '/');
                if ($linkedKey === '') {
                    continue;
                }

                $linkedBase = explode('?', $linkedKey, 2)[0];
                $linkedSegments = array_values(array_filter(explode('/', trim($linkedBase, '/')), static fn ($segment) => $segment !== ''));
                if ($linkedSegments === []) {
                    continue;
                }

                if (($linkedSegments[0] ?? null) === $rootSlug) {
                    $childListingKey = $rootSlug.'|'.$linkedKey;
                    if (! isset($visitedListings[$childListingKey])) {
                        $children[] = ['root' => $rootSlug, 'path' => $linkedKey];
                    }
                    continue;
                }

                $linkedRootSlug = $linkedSegments[0] ?? $rootSlug;
                if ($allowedRoots !== [] && ! in_array($linkedRootSlug, $allowedRoots, true) && count($linkedSegments) > 1) {
                    continue;
                }
                $childListingKey = $linkedRootSlug.'|'.$linkedKey;
                if (! isset($knownPaths[$linkedKey]) && ! isset($visitedListings[$childListingKey]) && ! isset($candidates[$linkedKey])) {
                    $children[] = ['root' => $linkedRootSlug, 'path' => $linkedKey];
                }
            }

            if ($children !== []) {
                $queue = array_merge($children, $queue);
            }
        }

        $state['queue'] = $queue;
        $state['visited_listings'] = $visitedListings;
        $state['candidates'] = $candidates;
        $state['exhausted'] = $queue === [];
    }

    private function loadSourcePage(string $path): ?array
    {
        $url = $this->sourceUrlForPath($path);

        try {
            $response = Http::timeout(30)->retry(2, 500)->get($url)->throw();
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }

        $html = (string) $response->body();
        $xpath = $this->loadHtmlXPath($html);
        if (! $xpath) {
            return null;
        }

        return [
            'url' => $url,
            'html' => $html,
            'xpath' => $xpath,
            'schema' => $this->extractProductSchema($xpath),
            'page_type' => strtolower((string) $this->xpathMetaContent($xpath, 'og:type')),
        ];
    }

    private function crawlProductListingsForRootSlug(string $rootSlug, array $knownPaths, array &$candidates, array &$visitedListings): void
    {
        $queue = [$rootSlug];

        while ($queue !== []) {
            $listingPath = array_shift($queue);
            $listingKey = trim((string) $listingPath, '/');
            if ($listingKey === '' || isset($visitedListings[$listingKey])) {
                continue;
            }

            $visitedListings[$listingKey] = true;
            $page = $this->loadSourcePage($listingPath);
            if (! $page) {
                continue;
            }

            if ($page['schema'] || Str::contains($page['page_type'], 'product')) {
                $candidates[$listingKey] = true;
                continue;
            }

            foreach ($this->extractInternalLinkPathsFromHtml($page['html']) as $linkedPath) {
                $linkedKey = trim((string) $linkedPath, '/');
                if ($linkedKey === '' || isset($visitedListings[$linkedKey])) {
                    continue;
                }

                $linkedBase = explode('?', $linkedKey, 2)[0];
                $linkedSegments = array_values(array_filter(explode('/', trim($linkedBase, '/')), static fn ($segment) => $segment !== ''));
                if ($linkedSegments === []) {
                    continue;
                }

                if (($linkedSegments[0] ?? null) === $rootSlug) {
                    $queue[] = $linkedPath;
                    continue;
                }

                $linkedRootSlug = $linkedSegments[0] ?? $rootSlug;
                $childListingKey = $linkedRootSlug.'|'.$linkedKey;
                if (! isset($knownPaths[$linkedKey]) && ! isset($visitedListings[$childListingKey])) {
                    $queue[] = $linkedPath;
                }
            }
        }
    }

    private function scrapeProductPage(string $path): ?array
    {
        $page = $this->loadSourcePage($path);
        if (! $page) {
            return null;
        }

        $html = $page['html'];
        $xpath = $page['xpath'];
        $schema = $page['schema'];
        $pageType = $page['page_type'];
        if (! $schema && ! Str::contains($pageType, 'product')) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($segment) => $segment !== ''));
        [$sourceCategorySlug, $subcategorySlug] = $this->resolveProductSourceContext($xpath, $segments);

        $name = $this->cleanText(
            data_get($schema, 'name')
            ?? $this->xpathMetaContent($xpath, 'og:title')
            ?? $this->xpathText($xpath, '//h1[1]')
            ?? $this->xpathText($xpath, '//title')
        );
        if ($name === '') {
            return null;
        }

        $summary = $this->cleanText(
            data_get($schema, 'description')
            ?? $this->xpathMetaContent($xpath, 'description')
            ?? $this->xpathText($xpath, '//p[1]')
            ?? ''
        );

        $sku = $this->cleanText((string) (
            data_get($schema, 'sku')
            ?? data_get($schema, 'mpn')
            ?? data_get($schema, 'productID')
            ?? $this->xpathMetaContent($xpath, 'product:retailer_item_id')
            ?? $this->extractProductLabeledValue($html, 'Product Code')
            ?? ''
        ));
        $barcode = $this->cleanText((string) (data_get($schema, 'gtin13') ?? data_get($schema, 'gtin14') ?? data_get($schema, 'gtin12') ?? data_get($schema, 'gtin8') ?? ''));
        $brandName = $this->cleanText((string) (
            data_get($schema, 'brand.name')
            ?? data_get($schema, 'brand')
            ?? $this->xpathMetaContent($xpath, 'product:brand')
            ?? $this->extractProductLabeledValue($html, 'Brand')
            ?? ''
        ));
        $breadcrumbLabels = $this->breadcrumbLabels($xpath);
        if ($brandName === '' && count($breadcrumbLabels) >= 3) {
            $brandName = $this->cleanText((string) ($breadcrumbLabels[array_key_last($breadcrumbLabels) - 1] ?? ''));
        }
        $seriesName = $this->cleanText((string) (
            data_get($schema, 'model')
            ?? $this->extractProductLabeledValue($html, 'Model')
            ?? ''
        ));
        $productModel = $this->extractProductModel($schema, $xpath, $html, $name, $segments);
        $pricing = $this->extractProductPricing($schema, $xpath, $html);
        $regularPrice = $pricing['regular_price'] ?? null;
        $offerPrice = $pricing['offer_price'] ?? null;
        $productDescription = $this->extractProductDescription($xpath, $summary, $html);
        $shortDescription = $summary !== '' ? Str::limit($summary, 280, '') : ($productDescription !== '' ? Str::limit($productDescription, 280, '') : '');
        $condition = $this->extractProductCondition($schema, $html);
        $imageUrl = $this->extractProductImageUrl($schema, $xpath);
        $galleryImages = $this->extractProductGalleryImageUrls($html, $imageUrl);
        [$keyFeatures, $specifications] = $this->extractProductSpecifications($xpath);
        $warranty = $this->extractProductWarranty($schema, $xpath, $html);
        if (($warranty === null || $warranty === '') && $specifications !== []) {
            $warranty = $this->extractWarrantyFromSpecifications($specifications);
        }

        return [
            'source_category_slug' => $sourceCategorySlug,
            'subcategory_slug' => $subcategorySlug,
            'brand_name' => $brandName !== '' ? $brandName : null,
            'series_name' => $seriesName !== '' ? $seriesName : null,
            'product_id' => 'ST-'.Str::upper(Str::slug($path, '-')),
            'product_name' => $name,
            'product_model' => $productModel !== '' ? $productModel : $this->humanizeSlug($segments[count($segments) - 1] ?? $name),
            'sku' => $sku !== '' ? $sku : null,
            'barcode' => $barcode !== '' ? $barcode : null,
            'product_description' => $productDescription !== '' ? $productDescription : $summary,
            'short_description' => $shortDescription,
            'seo_title' => $name,
            'seo_description' => $summary !== '' ? Str::limit($summary, 320, '') : ($productDescription !== '' ? Str::limit($productDescription, 320, '') : ''),
            'regular_price' => $regularPrice !== null ? $regularPrice : 0,
            'offer_price' => $offerPrice,
            'product_condition' => $condition,
            'image_url' => $imageUrl,
            'warranty' => $warranty !== '' ? $warranty : null,
            'key_features' => $keyFeatures,
            'specifications' => $specifications,
            'gallery_images' => $galleryImages,
        ];
    }

    private function resolveProductSourceContext(\DOMXPath $xpath, array $segments): array
    {
        $categoryMap = $this->categoryMap();
        $sourceCategorySlug = null;
        $subcategorySlug = null;

        foreach ($this->breadcrumbItems($xpath) as $breadcrumbItem) {
            $breadcrumbPath = trim((string) ($breadcrumbItem['path'] ?? ''), '/');
            $breadcrumbLabel = $this->cleanText((string) ($breadcrumbItem['label'] ?? ''));
            $breadcrumbSegments = array_values(array_filter(explode('/', $breadcrumbPath), static fn ($segment) => $segment !== ''));

            $candidateSlugs = [];
            if ($breadcrumbSegments !== []) {
                $candidateSlugs[] = (string) end($breadcrumbSegments);
            }

            if ($breadcrumbLabel !== '') {
                $labelSlug = Str::slug($breadcrumbLabel);
                if ($labelSlug !== '' && ! in_array($labelSlug, $candidateSlugs, true)) {
                    $candidateSlugs[] = $labelSlug;
                }
            }

            foreach ($candidateSlugs as $candidateSlug) {
                if ($candidateSlug === '') {
                    continue;
                }

                if ($sourceCategorySlug === null && array_key_exists($candidateSlug, $categoryMap)) {
                    $sourceCategorySlug = $candidateSlug;
                    continue 2;
                }

                if ($sourceCategorySlug !== null && $subcategorySlug === null && $candidateSlug !== $sourceCategorySlug) {
                    $subcategorySlug = $candidateSlug;
                }
            }
        }

        if ($sourceCategorySlug === null) {
            foreach ($segments as $segment) {
                $segment = trim((string) $segment, '/');
                if ($segment !== '' && array_key_exists($segment, $categoryMap)) {
                    $sourceCategorySlug = $segment;
                    break;
                }
            }
        }

        if ($sourceCategorySlug === null) {
            $sourceCategorySlug = $segments[0] ?? null;
        }

        if ($subcategorySlug === null && $sourceCategorySlug !== null) {
            foreach ($segments as $segment) {
                $segment = trim((string) $segment, '/');
                if ($segment === '' || $segment === $sourceCategorySlug) {
                    continue;
                }

                $subcategorySlug = $segment;
                break;
            }
        }

        return [$sourceCategorySlug, $subcategorySlug];
    }

    private function breadcrumbPaths(\DOMXPath $xpath): array
    {
        return array_values(array_map(
            static fn (array $item) => $item['path'],
            $this->breadcrumbItems($xpath)
        ));
    }

    private function breadcrumbLabels(\DOMXPath $xpath): array
    {
        $labels = [];
        foreach ($this->breadcrumbItems($xpath) as $item) {
            $label = $this->cleanText((string) ($item['label'] ?? ''));
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    private function breadcrumbItems(\DOMXPath $xpath): array
    {
        $items = [];
        $links = $xpath->query('//ul[contains(concat(" ", normalize-space(@class), " "), " breadcrumb ")]//a[@href]');
        if (! $links || $links->length === 0) {
            return $items;
        }

        $labels = $xpath->query('//ul[contains(concat(" ", normalize-space(@class), " "), " breadcrumb ")]//span[@itemprop="name"]');
        if (! $labels || $labels->length === 0) {
            return $items;
        }

        $count = min($links->length, $labels->length);
        for ($index = 0; $index < $count; $index++) {
            $path = $this->normalizeInternalLinkPath((string) $links->item($index)->getAttribute('href'));
            if ($path === null) {
                continue;
            }

            $items[] = [
                'path' => $path,
                'label' => $this->cleanText((string) $labels->item($index)->textContent),
            ];
        }

        return $items;
    }

    private function extractProductSchema(\DOMXPath $xpath): ?array
    {
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        if (! $scripts || $scripts->length === 0) {
            return null;
        }

        foreach ($scripts as $script) {
            $json = trim(html_entity_decode((string) $script->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($json === '') {
                continue;
            }

            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            foreach ($this->flattenJsonLd($decoded) as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }

                $types = array_map('strtolower', array_filter((array) ($candidate['@type'] ?? [])));
                if (in_array('product', $types, true)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function flattenJsonLd(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        if (array_is_list($decoded)) {
            $items = [];
            foreach ($decoded as $item) {
                $items = array_merge($items, $this->flattenJsonLd($item));
            }

            return $items;
        }

        $items = [$decoded];
        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            $items = array_merge($items, $this->flattenJsonLd($decoded['@graph']));
        }

        return $items;
    }

    private function loadHtmlXPath(string $html): ?\DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        return new \DOMXPath($document);
    }

    private function xpathText(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        return $this->cleanText((string) $nodes->item(0)->textContent);
    }

    private function xpathMetaContent(\DOMXPath $xpath, string $name): ?string
    {
        $needle = strtolower($name);
        $query = sprintf(
            '//meta[(translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "%1$s" or translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "%1$s") and @content]/@content',
            $needle
        );
        $nodes = $xpath->query($query);
        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        return $this->cleanText((string) $nodes->item(0)->nodeValue);
    }

    private function extractProductImageUrl(?array $schema, \DOMXPath $xpath): ?string
    {
        $image = data_get($schema, 'image');
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        $image = $this->cleanText((string) $image);
        if ($image !== '') {
            return $this->resolveSourceUrl($image);
        }

        foreach (['og:image', 'twitter:image'] as $metaName) {
            $metaImage = $this->xpathMetaContent($xpath, $metaName);
            if ($metaImage !== null && $metaImage !== '') {
                return $this->resolveSourceUrl($metaImage);
            }
        }

        return null;
    }

    private function extractProductLabeledValue(string $html, string $label): ?string
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($html === '') {
            return null;
        }

        $pattern = '/\b'.preg_quote($label, '/').'\s*:\s*([^\r\n<]+)/i';
        if (! preg_match($pattern, $html, $matches)) {
            return null;
        }

        $value = $this->cleanText($matches[1]);
        return $value !== '' ? $value : null;
    }

    private function extractProductDescription(\DOMXPath $xpath, string $fallback, string $html): string
    {
        $nodes = $xpath->query('//section[@id="description"]//*[contains(concat(" ", normalize-space(@class), " "), " full-description ")]');
        if ($nodes && $nodes->length > 0) {
            $parts = [];
            foreach ($nodes->item(0)->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $text = $this->cleanText((string) $child->textContent);
                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }
            }

            if ($parts !== []) {
                return trim(implode("\n\n", $parts));
            }
        }

        $description = $this->cleanText($fallback);
        if ($description !== '') {
            return $description;
        }

        return $this->cleanText($html);
    }

    private function extractProductModel(?array $schema, \DOMXPath $xpath, string $html, string $name, array $segments): string
    {
        $model = $this->cleanText((string) (
            data_get($schema, 'model')
            ?? $this->xpathMetaContent($xpath, 'product:model')
            ?? $this->extractProductLabeledValue($html, 'Model')
            ?? ''
        ));

        if ($model !== '') {
            return $model;
        }

        return $this->humanizeSlug($segments[count($segments) - 1] ?? $name);
    }

    private function extractProductPricing(?array $schema, \DOMXPath $xpath, string $html): array
    {
        $offers = data_get($schema, 'offers');
        if (is_array($offers) && array_is_list($offers)) {
            $offers = $offers[0] ?? null;
        }

        $schemaPrice = $this->parsePriceValue(data_get($offers, 'price'));
        $metaPrice = $this->parsePriceValue(
            $this->xpathMetaContent($xpath, 'product:price:amount')
            ?? $this->xpathMetaContent($xpath, 'og:price:amount')
        );
        if ($metaPrice === null) {
            if (preg_match('/(?:৳|Tk\.?|BDT)\s*([0-9][0-9,]*(?:\.[0-9]+)?)/iu', $html, $matches)) {
                $metaPrice = $this->parsePriceValue($matches[1]);
            } elseif (preg_match('/([0-9][0-9,]*(?:\.[0-9]+)?)\s*(?:৳|Tk\.?|BDT)/iu', $html, $matches)) {
                $metaPrice = $this->parsePriceValue($matches[1]);
            }
        }
        $cashPrice = $this->parsePriceValue($this->xpathText($xpath, '//label[contains(concat(" ", normalize-space(@class), " "), " cash ")]//span[@class="price"]'));
        $cashOldPrice = $this->parsePriceValue($this->xpathText($xpath, '//label[contains(concat(" ", normalize-space(@class), " "), " cash ")]//span[@class="price-old"]'));
        $regularPriceLabel = $this->parsePriceValue($this->xpathText($xpath, '//label[contains(concat(" ", normalize-space(@class), " "), " emi ")]//*[contains(concat(" ", normalize-space(@class), " "), " regular ")]'));

        $fallbackPrice = $schemaPrice ?? $metaPrice;
        $regularPrice = $regularPriceLabel ?? $cashOldPrice ?? $fallbackPrice ?? $cashPrice;
        $offerPrice = $cashPrice;

        if ($offerPrice === null && $fallbackPrice !== null && $regularPrice !== null && $fallbackPrice < $regularPrice) {
            $offerPrice = $fallbackPrice;
        }

        if ($offerPrice !== null && $regularPrice !== null && $offerPrice >= $regularPrice) {
            $offerPrice = null;
        }

        if ($regularPrice === null && $offerPrice !== null) {
            $regularPrice = $offerPrice;
        }

        return [
            'regular_price' => $regularPrice,
            'offer_price' => $offerPrice,
        ];
    }

    private function extractProductSpecifications(\DOMXPath $xpath): array
    {
        $keyFeatures = [];
        $specifications = [];
        $tables = $xpath->query('//section[@id="specification"]//table[contains(concat(" ", normalize-space(@class), " "), " data-table ")]');
        if (! $tables || $tables->length === 0) {
            return [$keyFeatures, $specifications];
        }

        $table = $tables->item(0);
        $currentSection = null;

        foreach ($table->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            if ($tag === 'thead') {
                $currentSection = $this->cleanText((string) $child->textContent);
                continue;
            }

            if ($tag !== 'tbody' || $currentSection === null || $currentSection === '') {
                continue;
            }

            foreach ($child->childNodes as $row) {
                if ($row->nodeType !== XML_ELEMENT_NODE || strtolower($row->nodeName) !== 'tr') {
                    continue;
                }

                $cells = [];
                foreach ($row->childNodes as $cell) {
                    if ($cell->nodeType === XML_ELEMENT_NODE && strtolower($cell->nodeName) === 'td') {
                        $cells[] = $this->cleanText((string) $cell->textContent);
                    }
                }

                if (count($cells) < 2) {
                    continue;
                }

                [$label, $value] = $cells;
                if ($label === '' || $value === '') {
                    continue;
                }

                if ($currentSection === 'Key Features') {
                    $keyFeatures[] = $label.': '.$value;
                    continue;
                }

                if (! isset($specifications[$currentSection])) {
                    $specifications[$currentSection] = [];
                }

                $specifications[$currentSection][$label] = $value;
            }
        }

        return [$keyFeatures, $specifications];
    }

    private function extractProductGalleryImageUrls(string $html, ?string $mainImageUrl = null): array
    {
        $mainPath = $mainImageUrl ? (parse_url($mainImageUrl, PHP_URL_PATH) ?: '') : '';
        $mainPath = $mainPath !== '' ? str_replace('\\', '/', $mainPath) : '';
        $directory = $mainPath !== '' ? trim(str_replace('\\', '/', dirname($mainPath)), '/') : '';
        if ($directory === '' || $directory === '.') {
            return [];
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pattern = '#https?://[^"\'<>\\r\\n]*'.preg_quote($directory, '#').'[^"\'<>\\r\\n]*#i';
        preg_match_all($pattern, $html, $matches);

        $galleryImages = [];
        foreach ($matches[0] as $match) {
            $url = $this->resolveSourceUrl($match);
            if (! $url) {
                continue;
            }

            $path = parse_url($url, PHP_URL_PATH) ?: '';
            if ($path === '') {
                continue;
            }

            $path = str_replace('\\', '/', $path);
            if ($mainPath !== '' && $path === $mainPath) {
                continue;
            }

            if (! $this->isLikelyGalleryImagePath($path)) {
                continue;
            }

            $galleryImages[$path] = $url;
        }

        return array_values($galleryImages);
    }

    private function extractWarrantyFromSpecifications(array $specifications): ?string
    {
        foreach (['Warranty Information', 'Warranty'] as $sectionName) {
            if (! isset($specifications[$sectionName]) || ! is_array($specifications[$sectionName])) {
                continue;
            }

            foreach ($specifications[$sectionName] as $value) {
                $value = $this->cleanText((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function parsePriceValue(mixed $value): ?float
    {
        $value = $this->cleanText((string) $value);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/([0-9][0-9,]*(?:\.[0-9]+)?)/', $value, $matches)) {
            return null;
        }

        $number = str_replace(',', '', $matches[1]);
        return $number !== '' ? (float) $number : null;
    }

    private function isLikelyGalleryImagePath(string $path): bool
    {
        if (! preg_match('/-(\d+)x(\d+)\.(?:jpe?g|png|webp)$/i', $path, $matches)) {
            return true;
        }

        return max((int) $matches[1], (int) $matches[2]) >= 200;
    }

    private function captureProductFieldStats(array $product): array
    {
        $fields = [
            'product_name' => $product['product_name'] ?? null,
            'product_model' => $product['product_model'] ?? null,
            'sku' => $product['sku'] ?? null,
            'barcode' => $product['barcode'] ?? null,
            'brand_name' => $product['brand_name'] ?? null,
            'series_name' => $product['series_name'] ?? null,
            'regular_price' => $product['regular_price'] ?? null,
            'offer_price' => $product['offer_price'] ?? null,
            'product_description' => $product['product_description'] ?? null,
            'short_description' => $product['short_description'] ?? null,
            'warranty' => $product['warranty'] ?? null,
            'key_features' => $product['key_features'] ?? [],
            'specifications' => $product['specifications'] ?? [],
            'gallery_images' => $product['gallery_images'] ?? [],
            'image_url' => $product['image_url'] ?? null,
            'product_condition' => $product['product_condition'] ?? null,
        ];

        $captured = 0;
        foreach ($fields as $value) {
            if ($this->hasCapturedProductValue($value)) {
                $captured++;
            }
        }

        return [
            'captured' => $captured,
            'possible' => count($fields),
        ];
    }

    private function hasCapturedProductValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value > 0;
        }

        return trim((string) $value) !== '';
    }

    private function extractProductPrice(?array $schema, \DOMXPath $xpath, string $html): ?float
    {
        $offers = data_get($schema, 'offers');
        if (is_array($offers) && array_is_list($offers)) {
            $offers = $offers[0] ?? null;
        }

        $price = data_get($offers, 'price');
        if ($price === null) {
            $price = $this->xpathMetaContent($xpath, 'product:price:amount') ?? $this->xpathMetaContent($xpath, 'og:price:amount');
        }

        if ($price === null) {
            if (preg_match('/(?:৳|Tk\.?|BDT)\s*([0-9][0-9,]*(?:\.[0-9]+)?)/iu', $html, $matches)) {
                $price = $matches[1];
            } elseif (preg_match('/([0-9][0-9,]*(?:\.[0-9]+)?)\s*(?:৳|Tk\.?|BDT)/iu', $html, $matches)) {
                $price = $matches[1];
            }
        }

        if ($price === null) {
            return null;
        }

        $price = preg_replace('/[^0-9.]/', '', (string) $price);
        return $price !== '' ? (float) $price : null;
    }

    private function extractProductCondition(?array $schema, string $html): string
    {
        $availability = strtolower((string) data_get($schema, 'offers.availability'));
        $sourceText = strtolower(strip_tags($html));

        if (Str::contains($availability, 'outofstock') || Str::contains($sourceText, 'out of stock') || Str::contains($sourceText, 'stock out')) {
            return 'Out Of Stock';
        }

        return 'In Stock';
    }

    private function extractProductWarranty(?array $schema, ?\DOMXPath $xpath, string $html): ?string
    {
        $warranty = data_get($schema, 'warranty');
        if (is_array($warranty)) {
            $warranty = data_get($warranty, 'name');
        }

        $warranty = $this->cleanText((string) $warranty);
        if ($warranty !== '') {
            return $warranty;
        }

        $snippet = strip_tags($html);
        if (preg_match('/Warranty\s*[:\-]\s*([^\r\n]+)/i', $snippet, $matches)) {
            return $this->cleanText($matches[1]);
        }

        return null;
    }

    private function downloadProductImage(?string $imageUrl): ?string
    {
        $imageUrl = $this->resolveSourceUrl($imageUrl);
        if (! $imageUrl) {
            return null;
        }

        try {
            $response = Http::timeout(30)->retry(2, 500)->get($imageUrl);
            if (! $response->successful()) {
                return null;
            }

            $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            if (! in_array($extension, ['jpg', 'png', 'webp'], true)) {
                $extension = 'jpg';
            }

            $tempBase = tempnam(sys_get_temp_dir(), 'startech-product-');
            if ($tempBase === false) {
                return null;
            }

            $tempPath = $tempBase.'.'.$extension;
            @rename($tempBase, $tempPath);
            file_put_contents($tempPath, $response->body());

            $originalName = basename($path ?: ('product.'.$extension));
            if ($originalName === '' || $originalName === '.' || $originalName === '..') {
                $originalName = 'product.'.$extension;
            }

            $uploaded = new UploadedFile($tempPath, $originalName, null, null, true);
            try {
                return PublicUpload::store($uploaded, 'asset/front-end/img/Product_image/', 'product-', ['jpg', 'jpeg', 'png', 'webp']);
            } finally {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function downloadProductImages(array $imageUrls): array
    {
        $storedImages = [];
        foreach (array_values(array_unique(array_filter(array_map(function ($imageUrl) {
            return $this->resolveSourceUrl((string) $imageUrl);
        }, $imageUrls)))) as $imageUrl) {
            $storedImage = $this->downloadProductImage($imageUrl);
            if ($storedImage !== null && $storedImage !== '') {
                $storedImages[] = $storedImage;
            }
        }

        return array_values(array_unique($storedImages));
    }

    private function resolveManufacturerId(?string $brandName, bool $dryRun = false): ?int
    {
        $brandName = $this->cleanText((string) $brandName);
        if ($brandName === '') {
            return null;
        }

        $existing = DB::table('manufacturer')->where('manufacturer_name', $brandName)->first();
        if (! $existing) {
            $brandSlug = Str::slug($brandName);
            $existing = DB::table('manufacturer')
                ->get(['manufacturer_id', 'manufacturer_name'])
                ->first(function ($brand) use ($brandSlug) {
                    return Str::slug($brand->manufacturer_name) === $brandSlug;
                });
        }

        if ($existing) {
            $code = $this->businessCodeForImport('brand', 'manufacturer', 'brand_code', $brandName, 'BR', $existing, 'manufacturer_id', [
                'company_id' => $existing->company_id ?? null,
                'brand_name' => $brandName,
                'manufacturer_name' => $brandName,
            ], $dryRun);
            if (! $dryRun && $code !== null) {
                DB::table('manufacturer')->where('manufacturer_id', $existing->manufacturer_id)->update([
                    'brand_code' => $code,
                    'updated_at' => now(),
                ]);
            }

            return (int) $existing->manufacturer_id;
        }

        if ($dryRun) {
            return null;
        }

        $companyId = $this->defaultCompanyId(false);
        $payload = [
            'company_id' => $companyId ?: null,
            'manufacturer_name' => $brandName,
            'publication_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $payload = $this->withImportBusinessCode($payload, 'brand', 'manufacturer', 'brand_code', $brandName, 'BR', null, 'manufacturer_id', [
            'company_id' => $companyId ?: null,
            'brand_name' => $brandName,
            'manufacturer_name' => $brandName,
        ], false);

        return (int) DB::table('manufacturer')->insertGetId($payload);
    }

    private function resolveSeriesId(?int $manufacturerId, ?string $seriesName, bool $dryRun = false): ?int
    {
        $seriesName = $this->cleanText((string) $seriesName);
        if (! $manufacturerId || $seriesName === '') {
            return null;
        }

        $existing = DB::table('product_series')
            ->where('manufacturer_id', $manufacturerId)
            ->where('name', $seriesName)
            ->first();
        if (! $existing) {
            $seriesSlug = Str::slug($seriesName);
            $existing = DB::table('product_series')
                ->where('manufacturer_id', $manufacturerId)
                ->get(['id', 'name'])
                ->first(function ($series) use ($seriesSlug) {
                    return Str::slug($series->name) === $seriesSlug;
                });
        }

        if ($existing) {
            $brandName = DB::table('manufacturer')->where('manufacturer_id', $manufacturerId)->value('manufacturer_name');
            $code = $this->businessCodeForImport('series', 'product_series', 'series_code', $seriesName, 'SER', $existing, 'id', [
                'manufacturer_id' => $manufacturerId,
                'brand_name' => $brandName,
                'series_name' => $seriesName,
            ], $dryRun);
            if (! $dryRun && $code !== null) {
                DB::table('product_series')->where('id', $existing->id)->update([
                    'series_code' => $code,
                    'updated_at' => now(),
                ]);
            }

            return (int) $existing->id;
        }

        if ($dryRun) {
            return null;
        }

        $brandName = DB::table('manufacturer')->where('manufacturer_id', $manufacturerId)->value('manufacturer_name');
        $payload = [
            'manufacturer_id' => $manufacturerId,
            'name' => $seriesName,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $payload = $this->withImportBusinessCode($payload, 'series', 'product_series', 'series_code', $seriesName, 'SER', null, 'id', [
            'manufacturer_id' => $manufacturerId,
            'brand_name' => $brandName,
            'series_name' => $seriesName,
        ], false);

        return (int) DB::table('product_series')->insertGetId($payload);
    }

    private function sourceUrlForPath(string $path): string
    {
        return rtrim($this->sourceAddress, '/').'/'.ltrim($path, '/');
    }

    private function resolveSourceUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            $scheme = parse_url($this->sourceAddress, PHP_URL_SCHEME) ?: 'https';
            return $scheme.':'.$url;
        }

        return rtrim($this->sourceAddress, '/').'/'.ltrim($url, '/');
    }

    private function cleanText(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return trim($value);
    }

    private function attributeBlueprints(): array
    {
        $desktops = [
            'Processor Model',
            'Processor Cores',
            'Processor Threads',
            'Memory Type',
            'Memory Capacity',
            'Storage Capacity',
            'Graphics',
            'Power Supply',
            'Operating System',
            'Warranty',
        ];
        $components = [
            'Processor Model',
            'Socket',
            'Chipset',
            'Memory Type',
            'Memory Capacity',
            'Memory Speed',
            'Graphics Memory',
            'Storage Interface',
            'Power Output',
            'Form Factor',
            'Fan Size',
            'Warranty',
        ];
        $laptop = [
            'Processor Model',
            'Display Size',
            'Resolution',
            'RAM',
            'Storage',
            'Graphics',
            'Battery',
            'Weight',
            'Operating System',
            'Warranty',
        ];
        $accessory = [
            'Connection',
            'Type',
            'Buttons',
            'DPI',
            'Driver Size',
            'Battery',
            'Compatibility',
            'Warranty',
        ];
        $network = [
            'Wi-Fi Standard',
            'Band',
            'Speed',
            'Ports',
            'Antenna',
            'Coverage',
            'Security',
            'Warranty',
        ];
        $server = [
            'Processor',
            'RAM',
            'Storage',
            'RAID',
            'Ports',
            'Form Factor',
            'Warranty',
        ];
        $office = [
            'Type',
            'Functions',
            'Resolution',
            'Speed',
            'Connectivity',
            'Paper Size',
            'Warranty',
        ];
        $camera = [
            'Sensor',
            'Lens',
            'Resolution',
            'Zoom',
            'Video Resolution',
            'Stabilization',
            'Warranty',
        ];
        $security = [
            'Channel',
            'Resolution',
            'Night Vision',
            'Storage',
            'Connectivity',
            'Power',
            'Warranty',
        ];
        $gadget = [
            'Display',
            'Battery',
            'Sensors',
            'Water Resistance',
            'Connectivity',
            'Warranty',
        ];
        $gaming = [
            'Device Type',
            'Connection',
            'Buttons',
            'Compatibility',
            'Lighting',
            'Warranty',
        ];
        $monitor = [
            'Screen Size',
            'Resolution',
            'Panel Type',
            'Refresh Rate',
            'Response Time',
            'Brightness',
            'Ports',
            'Warranty',
        ];
        $tv = [
            'Screen Size',
            'Resolution',
            'Panel Type',
            'Smart TV',
            'Refresh Rate',
            'Ports',
            'Warranty',
        ];
        $tablet = [
            'Display Size',
            'RAM',
            'Storage',
            'Battery',
            'Camera',
            'Operating System',
            'Warranty',
        ];
        $mobile = [
            'Display Size',
            'RAM',
            'Storage',
            'Battery',
            'Camera',
            'Network',
            'SIM',
            'Warranty',
        ];
        $appliance = [
            'Capacity',
            'Power',
            'Feature',
            'Warranty',
        ];
        $airConditioner = [
            'Capacity',
            'Inverter',
            'Refrigerant',
            'Energy Rating',
            'Warranty',
        ];
        $airCooler = [
            'Tank Capacity',
            'Fan Speed',
            'Power',
            'Air Throw',
            'Warranty',
        ];
        $airPurifier = [
            'Coverage',
            'Filter Type',
            'CADR',
            'Noise Level',
            'Warranty',
        ];
        $accessControl = [
            'Authentication',
            'Capacity',
            'Connectivity',
            'Power',
            'Warranty',
        ];
        $software = [
            'Platform',
            'License',
            'Users',
            'Version',
            'Warranty',
        ];

        return [
            'desktops' => $desktops,
            'components' => $components,
            'laptop-notebook' => $laptop,
            'accessories' => $accessory,
            'networking' => $network,
            'server-networking' => $server,
            'office-equipment' => $office,
            'camera' => $camera,
            'security-camera' => $security,
            'gadget' => $gadget,
            'gaming' => $gaming,
            'monitor' => $monitor,
            'tv' => $tv,
            'tablet-pc' => $tablet,
            'mobile' => $mobile,
            'appliance' => $appliance,
            'air-conditioner' => $airConditioner,
            'air-cooler' => $airCooler,
            'air-purifier' => $airPurifier,
            'access-control' => $accessControl,
            'software' => $software,
        ];
    }

    public function sourcePaths(): array
    {
        if ($this->sourcePaths !== null) {
            return $this->sourcePaths;
        }

        $paths = [];
        try {
            $homepage = Http::timeout(30)->retry(2, 500)->get($this->sourceHomeUrl())->throw()->body();
            $header = $this->extractHeaderMarkup($homepage);
            $paths = array_merge($paths, $this->extractInternalPaths($header));
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            $sitemap = Http::timeout(30)->retry(2, 500)->get($this->sourceSitemapUrl())->throw()->body();
            $paths = array_merge($paths, $this->extractSitemapPaths($sitemap));
        } catch (\Throwable $exception) {
            report($exception);
        }

        $paths = array_values(array_unique(array_filter(array_map([$this, 'normalizePath'], $paths))));
        sort($paths);
        return $this->sourcePaths = $paths;
    }

    public function discoverSubcategoryCandidates(string $rootSlug, ?array $paths = null): array
    {
        $paths = $paths ?? $this->sourcePaths();
        $ignore = array_flip([
            'page', 'login', 'register', 'cart', 'checkout', 'compare', 'wishlist', 'search', 'product', 'about', 'contact',
            'offer', 'happy-hour', 'tool', 'information', 'account', 'career', 'blog', 'image', 'catalog', 'admin',
        ]);
        $candidates = [];

        foreach ($paths as $path) {
            if ($path === $rootSlug || ! Str::startsWith($path, $rootSlug.'/')) {
                continue;
            }

            $tail = trim(substr($path, strlen($rootSlug) + 1), '/');
            if ($tail === '') {
                continue;
            }

            $first = explode('/', $tail)[0];
            if ($first === '' || isset($ignore[$first])) {
                continue;
            }
            if ($this->brandSlugFromPath($first) !== null && ! $this->looksLikeCategory($first)) {
                continue;
            }

            $candidates[] = $this->humanizeSlug($first);
        }

        $candidates = array_values(array_unique($candidates));
        sort($candidates);
        return $candidates;
    }

    public function discoverBrands(): array
    {
        $paths = $this->sourcePaths();
        $brandNames = [];
        foreach ($paths as $path) {
            $slug = $this->brandSlugFromPath($path);
            if (! $slug) {
                continue;
            }
            $brandNames[$slug] = $this->brandNameFromSlug($slug);
        }

        $brandNames = array_values(array_unique(array_filter($brandNames)));
        sort($brandNames);
        return $brandNames;
    }

    private function sourceCategorySlugFromName(?string $categoryName): ?string
    {
        $categoryName = trim((string) $categoryName);
        if ($categoryName === '') {
            return null;
        }

        $normalizedCategoryName = Str::slug($categoryName);
        foreach ($this->categoryMap() as $sourceSlug => $meta) {
            if (Str::slug((string) ($meta['name'] ?? '')) === $normalizedCategoryName) {
                return $sourceSlug;
            }
        }

        return null;
    }

    private function sourceBrandNameFromName(?string $brandName): ?string
    {
        $brandName = trim((string) $brandName);
        if ($brandName === '') {
            return null;
        }

        $normalizedBrandName = Str::slug($brandName);
        foreach (array_keys($this->seriesMap()) as $sourceBrandName) {
            if (Str::slug($sourceBrandName) === $normalizedBrandName) {
                return $sourceBrandName;
            }
        }

        return null;
    }

    private function normalizeSteps(array $steps): array
    {
        $steps = array_map(static function ($step) {
            return strtolower(trim((string) $step));
        }, $steps);
        $steps = array_values(array_unique($steps));

        $ordered = [];
        foreach (['categories', 'subcategories', 'brands', 'series', 'products', 'attributes'] as $step) {
            if (in_array($step, $steps, true)) {
                $ordered[] = $step;
            }
        }

        return $ordered ?: ['categories', 'subcategories', 'brands', 'series'];
    }

    private function extractHeaderMarkup(string $html): string
    {
        if (preg_match('#<header\b.*?</header>#is', $html, $matches)) {
            return $matches[0];
        }

        return $html;
    }

    private function extractInternalPaths(string $html): array
    {
        preg_match_all('#href="' . $this->sourceAddressPattern() . '([^"]+)"#i', $html, $matches);
        return $matches[1] ?? [];
    }

    private function extractSitemapPaths(string $xml): array
    {
        preg_match_all('#<loc>\s*' . $this->sourceAddressPattern() . '([^<]+)</loc>#i', $xml, $matches);
        return $matches[1] ?? [];
    }

    private function extractInternalLinkPathsFromHtml(string $html): array
    {
        $xpath = $this->loadHtmlXPath($html);
        if (! $xpath) {
            return [];
        }

        $nodes = $xpath->query('//a[@href]');
        if (! $nodes || $nodes->length === 0) {
            return [];
        }

        $paths = [];
        foreach ($nodes as $node) {
            $path = $this->normalizeInternalLinkPath((string) $node->getAttribute('href'));
            if ($path !== null) {
                $paths[] = $path;
            }
        }

        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }

    private function normalizeInternalLinkPath(?string $href): ?string
    {
        $href = html_entity_decode(trim((string) $href), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($href === '' || str_starts_with($href, '#') || preg_match('#^(javascript:|mailto:|tel:)#i', $href)) {
            return null;
        }

        $scheme = parse_url($this->sourceAddress, PHP_URL_SCHEME) ?: 'https';
        if (str_starts_with($href, '//')) {
            $href = $scheme.':'.$href;
        } elseif (! preg_match('#^[a-z][a-z0-9+\-.]*://#i', $href)) {
            $href = rtrim($this->sourceAddress, '/').'/'.ltrim($href, '/');
        }

        $parts = parse_url($href);
        $sourceParts = parse_url($this->sourceAddress);
        if (! is_array($parts) || ! is_array($sourceParts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $sourceHost = strtolower((string) ($sourceParts['host'] ?? ''));
        if ($host === '' || $host !== $sourceHost) {
            return null;
        }

        if (($parts['port'] ?? null) !== ($sourceParts['port'] ?? null)) {
            return null;
        }

        $path = strtolower(rawurldecode(trim((string) ($parts['path'] ?? ''), '/')));
        if ($path === '' || $this->isBlockedPath($path)) {
            return null;
        }

        $query = (string) ($parts['query'] ?? '');
        return $query !== '' ? $path.'?'.$query : $path;
    }

    private function normalizePath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        $path = strtolower(rawurldecode($path));
        $path = preg_replace('#\?.*$#', '', $path);
        $path = preg_replace('#^' . preg_quote($this->sourceAddress, '#') . '#i', '', $path);
        $path = preg_replace('#^' . preg_quote(rtrim($this->sourceAddress, '/'), '#') . '/?#i', '', $path);
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        if ($this->isBlockedPath($path)) {
            return null;
        }

        return $path;
    }

    private function isBlockedPath(string $path): bool
    {
        $blocked = [
            'account', 'about_us', 'blog', 'career', 'checkout', 'compare', 'contact', 'information', 'login', 'register',
            'cart', 'gift-cards', 'track-order', 'wishlist', 'admin', 'privacy-policy', 'terms-and-conditions', 'tool',
            'image', 'catalog', 'download', 'search', 'payment', 'returns', 'refund', 'support',
        ];

        foreach ($blocked as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSourceAddress(?string $sourceAddress): string
    {
        $sourceAddress = trim((string) $sourceAddress);
        if ($sourceAddress === '') {
            return self::DEFAULT_SOURCE_ADDRESS;
        }

        if (! preg_match('#^https?://#i', $sourceAddress)) {
            $sourceAddress = 'https://' . $sourceAddress;
        }

        $sourceAddress = preg_replace('~[?#].*$~', '', $sourceAddress) ?: $sourceAddress;
        $sourceAddress = rtrim($sourceAddress, '/') . '/';
        $parts = parse_url($sourceAddress);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return self::DEFAULT_SOURCE_ADDRESS;
        }

        $normalized = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $normalized .= ':' . $parts['port'];
        }

        $path = trim($parts['path'] ?? '', '/');
        return $normalized . '/' . ($path !== '' ? $path . '/' : '');
    }

    private function sourceHomeUrl(): string
    {
        return $this->sourceAddress;
    }

    private function sourceSitemapUrl(): string
    {
        return rtrim($this->sourceAddress, '/') . '/sitemap.xml';
    }

    private function sourceAddressPattern(): string
    {
        return preg_quote($this->sourceAddress, '#');
    }

    private function looksLikeCategory(string $slug): bool
    {
        return array_key_exists($slug, $this->categoryMap());
    }

    private function brandSlugFromPath(string $path): ?string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        $aliases = array_keys($this->brandAliases());
        usort($aliases, static fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach (explode('/', $path) as $segment) {
            foreach ($aliases as $alias) {
                if ($segment === $alias || Str::startsWith($segment, $alias.'-') || Str::startsWith($segment, $alias.'/')) {
                    return $alias;
                }
            }
        }

        return null;
    }

    private function brandNameFromSlug(string $slug): string
    {
        $aliases = $this->brandAliases();
        if (isset($aliases[$slug])) {
            return $aliases[$slug];
        }

        return $this->humanizeSlug($slug);
    }

    private function humanizeSlug(string $slug): string
    {
        $slug = str_replace(['_', '/'], '-', $slug);
        $slug = preg_replace('#-+#', '-', $slug);
        $words = array_map(static function ($word) {
            $special = [
                'pc' => 'PC',
                'tv' => 'TV',
                'ram' => 'RAM',
                'ssd' => 'SSD',
                'hdd' => 'HDD',
                'cpu' => 'CPU',
                'gpu' => 'GPU',
                'wifi' => 'Wi-Fi',
                'wi-fi' => 'Wi-Fi',
                'ip' => 'IP',
                'dvr' => 'DVR',
                'nvr' => 'NVR',
                'nas' => 'NAS',
                'ai' => 'AI',
                'qled' => 'QLED',
                'oled' => 'OLED',
            ];

            $normalized = strtolower($word);
            if (isset($special[$normalized])) {
                return $special[$normalized];
            }

            if (preg_match('/^\d+[a-z0-9]*$/i', $word)) {
                return strtoupper($word);
            }

            return ucfirst($normalized);
        }, explode('-', $slug));

        return trim(implode(' ', $words));
    }

    private function defaultCompanyId(bool $dryRun = false): ?int
    {
        $company = DB::table('companies')->where('name', self::DEFAULT_COMPANY)->first();
        if ($company) {
            $code = $this->businessCodeForImport('company', 'companies', 'company_code', self::DEFAULT_COMPANY, 'CO', $company, 'id', [
                'company_id' => $company->id,
                'company_name' => self::DEFAULT_COMPANY,
            ], $dryRun);
            if (! $dryRun && $code !== null) {
                DB::table('companies')->where('id', $company->id)->update([
                    'company_code' => $code,
                    'updated_at' => now(),
                ]);
            }

            return (int) $company->id;
        }

        if ($dryRun) {
            return 0;
        }

        $payload = [
            'name' => self::DEFAULT_COMPANY,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $payload = $this->withImportBusinessCode($payload, 'company', 'companies', 'company_code', self::DEFAULT_COMPANY, 'CO', null, 'id', [
            'company_name' => self::DEFAULT_COMPANY,
        ], false);

        return (int) DB::table('companies')->insertGetId($payload);
    }

    private function withImportBusinessCode(
        array $payload,
        string $codeType,
        string $table,
        string $column,
        string $seed,
        string $fallbackPrefix,
        ?object $existing = null,
        string $keyColumn = 'id',
        array $context = [],
        bool $dryRun = false
    ): array {
        $code = $this->businessCodeForImport($codeType, $table, $column, $seed, $fallbackPrefix, $existing, $keyColumn, $context, $dryRun);
        if ($code !== null) {
            $payload[$column] = $code;
        }

        return $payload;
    }

    private function businessCodeForImport(
        string $codeType,
        string $table,
        string $column,
        string $seed,
        string $fallbackPrefix,
        ?object $existing = null,
        string $keyColumn = 'id',
        array $context = [],
        bool $dryRun = false
    ): ?string {
        if (! Schema::hasColumn($table, $column)) {
            return null;
        }

        $current = trim((string) ($existing->{$column} ?? ''));
        if ($current !== '' && ! $this->shouldRegenerateImportBusinessCode($codeType, $table, $current, $seed, $context)) {
            return $current;
        }

        if ($dryRun) {
            return null;
        }

        $ignoreId = isset($existing->{$keyColumn}) ? (int) $existing->{$keyColumn} : null;
        $context = array_merge($context, [
            'code_type' => $codeType,
            'name' => $seed,
            'entity_name' => $seed,
            'ignore_id' => $ignoreId,
            'table' => $table,
            'column' => $column,
            'key_column' => $keyColumn,
        ]);

        try {
            $allocation = app(ProductCodeGenerator::class)->allocate($context);
            $code = trim((string) ($allocation['code'] ?? $allocation['product_code'] ?? ''));
            if ($code !== '') {
                return $code;
            }
        } catch (\Throwable $exception) {
            // If the configured rule is temporarily incomplete, keep imports usable
            // and fall back to the legacy unique code pattern.
        }

        return $this->legacyImportBusinessCode($table, $column, $seed, $fallbackPrefix, $ignoreId, $keyColumn);
    }

    private function shouldRegenerateImportBusinessCode(
        string $codeType,
        string $table,
        string $current,
        string $seed,
        array $context = []
    ): bool {
        $legacy = normalize_business_code($seed, $this->importBusinessCodeMaxLength($table));
        if ($legacy !== null && $legacy !== '' && $current === $legacy) {
            return true;
        }

        if ($codeType === 'product') {
            foreach (['source_code', 'sku'] as $key) {
                $sourceCode = normalize_product_code($context[$key] ?? null, 100);
                if ($sourceCode !== null && $sourceCode !== '' && $current === $sourceCode) {
                    return true;
                }
            }
        }

        return false;
    }

    private function legacyImportBusinessCode(
        string $table,
        string $column,
        string $seed,
        string $fallbackPrefix,
        ?int $ignoreId = null,
        string $keyColumn = 'id'
    ): string {
        $maxLength = $this->importBusinessCodeMaxLength($table);
        $base = normalize_business_code($seed, $maxLength) ?: normalize_business_code($fallbackPrefix, $maxLength) ?: $fallbackPrefix;
        $candidate = $base;
        $counter = 2;

        while ($this->importBusinessCodeExists($table, $column, $candidate, $ignoreId, $keyColumn)) {
            $suffix = (string) $counter;
            $prefixLength = max(1, $maxLength - strlen($suffix));
            $candidate = normalize_business_code(substr($base, 0, $prefixLength).$suffix, $maxLength) ?: $fallbackPrefix.$suffix;
            $counter++;
        }

        return $candidate;
    }

    private function importBusinessCodeMaxLength(string $table): int
    {
        return $table === 'product' ? 100 : 30;
    }

    private function importBusinessCodeExists(
        string $table,
        string $column,
        string $code,
        ?int $ignoreId = null,
        string $keyColumn = 'id'
    ): bool {
        $query = DB::table($table)->where($column, $code);

        if ($ignoreId !== null) {
            $query->where($keyColumn, '<>', $ignoreId);
        }

        return $query->exists();
    }

    private function restoreSoftDeletedPayload(string $table): array
    {
        $payload = [];
        foreach (['deleted_at', 'deleted_by', 'delete_reason'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $payload[$column] = null;
            }
        }

        return $payload;
    }
}
