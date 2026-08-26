<?php

namespace App\Http\Controllers;

use App\Company;
use App\Manufacturer;
use App\Product;
use App\ProductSeries;
use App\Services\RecycleBinService;
use App\Services\StarTechCatalogImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogHierarchyController extends Controller
{
    private const STARTECH_PRODUCT_CRAWL_VERSION = 5;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'company_id' => 'nullable|integer|exists:companies,id',
            'brand_id' => 'nullable|integer|exists:manufacturer,manufacturer_id', 'status' => 'nullable|in:active,inactive',
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $companyId = $filters['company_id'] ?? null;
        $brandId = $filters['brand_id'] ?? null;
        $active = isset($filters['status']) ? $filters['status'] === 'active' : null;

        $companies = Company::with(['brands' => fn ($query) => $query->withCount(['series'])->orderBy('manufacturer_name')])
            ->when($search, fn ($query) => $query->where('name','like','%'.$search.'%'))
            ->when($companyId, fn ($query) => $query->where('id',$companyId))
            ->when($brandId, fn ($query) => $query->whereHas('brands',fn ($brand) => $brand->where('manufacturer_id',$brandId)))
            ->when($active !== null, fn ($query) => $query->where('is_active',$active))->orderBy('name')->get();
        $brands = Manufacturer::with('company')
            ->when($search, fn ($query) => $query->where('manufacturer_name','like','%'.$search.'%'))
            ->when($companyId, fn ($query) => $query->where('company_id',$companyId))
            ->when($brandId, fn ($query) => $query->where('manufacturer_id',$brandId))
            ->when($active !== null, fn ($query) => $query->where('publication_status',$active))->orderBy('manufacturer_name')->get();
        $series = ProductSeries::with('brand.company')
            ->when($search, fn ($query) => $query->where('name','like','%'.$search.'%'))
            ->when($companyId, fn ($query) => $query->whereHas('brand',fn ($brand) => $brand->where('company_id',$companyId)))
            ->when($brandId, fn ($query) => $query->where('manufacturer_id',$brandId))
            ->when($active !== null, fn ($query) => $query->where('is_active',$active))->orderBy('name')->get();
        $filterCompanies = Company::orderBy('name')->get();
        $filterBrands = Manufacturer::with('company')->orderBy('manufacturer_name')->get();

        return view('admin.admin-master')->with('admin_main_content', view('admin.admin-pages.catalog-hierarchy', compact('companies', 'brands', 'series', 'filterCompanies', 'filterBrands', 'filters')));
    }

    public function imports()
    {
        return view('admin.admin-master')->with('admin_main_content', view('admin.admin-pages.catalog-imports')
            ->with('startechProductImportState', session('startech_product_import_state')));
    }

    public function importStarTechCatalog(Request $request, StarTechCatalogImporter $importer)
    {
        $categorySlugs = array_keys($importer->categoryMap());
        $categoryIds = DB::table('category')->pluck('category_id')->all();
        $manufacturerIds = DB::table('manufacturer')->pluck('manufacturer_id')->all();
        $validated = $request->validate([
            'steps' => 'nullable|array',
            'steps.*' => 'required|string|in:categories,subcategories,brands,series,product,products,attributes',
            'dry_run' => 'nullable|boolean',
            'save_source_address' => 'nullable|boolean',
            'source_address' => 'nullable|url|max:255',
            'category_slug' => ['nullable', 'string', Rule::in($categorySlugs)],
            'category_id' => ['nullable', 'integer', Rule::in($categoryIds)],
            'manufacturer_id' => ['nullable', 'integer', Rule::in($manufacturerIds)],
            'product_url' => 'nullable|string|max:1000',
            'product_batch_size' => 'nullable|integer|min:1|max:500',
            'product_cursor' => 'nullable|string|max:1000',
        ]);

        $previousSourceAddress = trim((string) DB::table('site_settings')
            ->where('setting_key', 'catalog_import_source_address')
            ->value('setting_value'));
        $sourceAddress = $this->catalogImportSourceAddress($validated['source_address'] ?? null);
        $this->saveCatalogImportSourceAddress($sourceAddress);
        if ($previousSourceAddress !== $sourceAddress) {
            session()->forget('startech_product_import_state');
            session()->forget('startech_catalog_import_preview');
        }

        if ($request->boolean('save_source_address')) {
            return back()->with('message', 'Source address saved successfully.');
        }

        $steps = array_values(array_unique($validated['steps'] ?? ['categories', 'subcategories', 'brands', 'series']));

        $dryRun = $request->boolean('dry_run');
        $categorySlug = trim((string) ($validated['category_slug'] ?? ''));
        if ($categorySlug === '') {
            $categorySlug = null;
        }

        $importState = session('startech_product_import_state');
        $sourceChanged = ! is_array($importState)
            || ($importState['source_address'] ?? null) !== $sourceAddress
            || (int) ($importState['crawl_version'] ?? 0) !== self::STARTECH_PRODUCT_CRAWL_VERSION
            || ($importState['category_slug'] ?? null) !== $categorySlug;
        if ($sourceChanged) {
            session()->forget('startech_product_import_state');
        }

        $productUrl = trim((string) ($validated['product_url'] ?? ''));
        if ($productUrl !== '') {
            $productPath = $this->normalizeProductImportPath($productUrl, $sourceAddress);
            if ($productPath === null) {
                return back()
                    ->withInput()
                    ->withErrors(['product_url' => 'Paste a valid product page link or path from the configured source site.']);
            }

            $importer->setSourceAddress($sourceAddress);
            $result = $importer->importProductByPath($productPath, $dryRun);
            $created = (int) ($result['created'] ?? 0);
            $updated = (int) ($result['updated'] ?? 0);

            if (($created + $updated) === 0) {
                return back()
                    ->withInput()
                    ->withErrors(['product_url' => 'No product could be imported from that link.']);
            }

            session()->forget('startech_product_import_state');
            if ($dryRun) {
                session()->put('startech_catalog_import_preview', [
                    'source_address' => $sourceAddress,
                    'summary' => 'Single product preview complete from '.$sourceAddress.'. '.$created.' created, '.$updated.' updated. No database changes were saved.',
                    'results' => [
                        'products' => [
                            'created' => $created,
                            'updated' => $updated,
                            'processed' => $created + $updated,
                            'total' => $created + $updated,
                            'remaining' => 0,
                            'has_more' => false,
                        ],
                    ],
                    'updated_at' => now()->toDateTimeString(),
                ]);
            } else {
                session()->forget('startech_catalog_import_preview');
            }

            $message = $dryRun
                ? 'Single product dry run complete from '.$sourceAddress.'. '.$created.' created, '.$updated.' updated. No database changes were saved.'
                : 'Single product import complete from '.$sourceAddress.'. '.$created.' created, '.$updated.' updated.';

            return back()->with('message', $message);
        }

        $productBatchSize = $request->filled('product_batch_size')
            ? (int) $validated['product_batch_size']
            : (in_array('products', $steps, true) ? 1 : null);
        $productCursor = trim((string) ($validated['product_cursor'] ?? ''));
        if ($productCursor === '' && ! $sourceChanged && is_array($importState) && ! empty($importState['cursor'])) {
            $productCursor = (string) $importState['cursor'];
        }

        $options = [];
        if ($productBatchSize !== null) {
            $options['products']['batch_size'] = (int) $productBatchSize;
        }
        if ($productCursor !== '') {
            $options['products']['cursor'] = $productCursor;
        }
        if ($categorySlug !== null) {
            $options['products']['category_slug'] = $categorySlug;
        }
        if (! empty($validated['category_id'])) {
            $options['subcategories']['category_id'] = (int) $validated['category_id'];
        }
        if (! empty($validated['manufacturer_id'])) {
            $options['series']['manufacturer_id'] = (int) $validated['manufacturer_id'];
        }

        $results = $importer->import($steps, $dryRun, $sourceAddress, $options);

        if (in_array('products', $steps, true) && ! $dryRun) {
            $productStats = $results['products'] ?? null;
            if (is_array($productStats) && ! empty($productStats['has_more'])) {
                session()->put('startech_product_import_state', [
                    'source_address' => $sourceAddress,
                    'category_slug' => $categorySlug,
                    'batch_size' => $options['products']['batch_size'] ?? null,
                    'cursor' => $productStats['next_cursor'] ?? null,
                    'processed' => (int) ($productStats['processed'] ?? 0),
                    'total' => (int) ($productStats['total'] ?? 0),
                    'remaining' => (int) ($productStats['remaining'] ?? 0),
                    'crawl_version' => self::STARTECH_PRODUCT_CRAWL_VERSION,
                    'updated_at' => now()->toDateTimeString(),
                ]);
            } else {
                session()->forget('startech_product_import_state');
            }
        }

        $summary = $this->summarizeCatalogImportResults($results);
        if ($dryRun) {
            session()->put('startech_catalog_import_preview', [
                'source_address' => $sourceAddress,
                'summary' => $summary === ''
                    ? 'Preview complete from '.$sourceAddress.'. No import steps were selected.'
                    : 'Preview complete from '.$sourceAddress.'. '.$summary.' No database changes were saved.',
                'results' => $results,
                'steps' => $steps,
                'updated_at' => now()->toDateTimeString(),
            ]);
        } else {
            session()->forget('startech_catalog_import_preview');
        }

        $message = $dryRun
            ? 'Catalog dry run complete from '.$sourceAddress.'. '.$summary.' No database changes were saved.'
            : 'Catalog import complete from '.$sourceAddress.'. '.$summary;

        if (! $dryRun && in_array('products', $steps, true) && ! empty($results['products']['has_more'])) {
            $remaining = (int) ($results['products']['remaining'] ?? 0);
            $message = 'Catalog import batch complete from '.$sourceAddress.'. '.$summary.' '.$remaining.' products remain. Re-run the same import to continue from the saved cursor.';
        }

        return back()->with('message', $message);
    }

    public function storeCompany(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'company_code' => 'nullable|string|max:30',
        ]);

        $companyName = trim($data['name']);
        if ($this->companyNameExistsCaseInsensitive($companyName)) {
            return back()->withInput()->withErrors(['name' => 'That company name already exists.']);
        }
        $requestedCode = trim((string) ($data['company_code'] ?? ''));
        $companyCode = $requestedCode !== ''
            ? normalize_business_code($requestedCode, 30)
            : $this->nextUniqueBusinessCode('companies', 'company_code', $companyName, 30, 'CO', null, 'id');

        if ($companyCode === null) {
            return back()->withInput()->withErrors(['company_code' => 'Please enter a valid company code.']);
        }

        if ($requestedCode !== '' && DB::table('companies')->where('company_code', $companyCode)->exists()) {
            return back()->withInput()->withErrors(['company_code' => 'That company code already exists.']);
        }

        Company::create([
            'name' => $companyName,
            'company_code' => $companyCode,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return back()->with('message', 'Company added successfully.');
    }

    public function updateCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'company_code' => 'nullable|string|max:30',
        ]);

        $companyName = trim($data['name']);
        if ($this->companyNameExistsCaseInsensitive($companyName, (int) $company->id)) {
            return back()->withInput()->withErrors(['name' => 'That company name already exists.']);
        }
        $requestedCode = trim((string) ($data['company_code'] ?? ''));
        if ($requestedCode !== '') {
            $companyCode = normalize_business_code($requestedCode, 30);
            if ($companyCode === null) {
                return back()->withInput()->withErrors(['company_code' => 'Please enter a valid company code.']);
            }

            if (DB::table('companies')->where('company_code', $companyCode)->where('id', '<>', $company->id)->exists()) {
                return back()->withInput()->withErrors(['company_code' => 'That company code already exists.']);
            }
        } elseif (trim((string) ($company->company_code ?? '')) !== '') {
            $companyCode = (string) $company->company_code;
        } else {
            $companyCode = $this->nextUniqueBusinessCode('companies', 'company_code', $companyName, 30, 'CO', $company->id, 'id');
        }

        $company->update([
            'name' => $companyName,
            'company_code' => $companyCode,
            'is_active' => $request->boolean('is_active'),
        ]);
        return back()->with('message', 'Company updated successfully.');
    }

    public function deleteCompany($id)
    {
        $company = Company::withCount('brands')->findOrFail($id);
        if ($company->brands_count) return back()->with('exception', 'Move or delete this company’s brands before deleting the company.');
        app(RecycleBinService::class)->softDelete('company', (int) $id, session('admin_id'), 'Company moved to Recycle Bin.');
        return back()->with('message', 'Company moved to Recycle Bin.');
    }

    public function storeBrand(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'manufacturer_name' => 'required|string|max:160',
            'brand_code' => 'nullable|string|max:30',
        ]);

        $brandName = trim($data['manufacturer_name']);
        if ($this->manufacturerNameExistsCaseInsensitive($brandName)) {
            return back()->withInput()->withErrors(['manufacturer_name' => 'That brand name already exists.']);
        }

        $requestedCode = trim((string) ($data['brand_code'] ?? ''));
        $brandCode = $requestedCode !== ''
            ? normalize_business_code($requestedCode, 30)
            : $this->nextUniqueBusinessCode('manufacturer', 'brand_code', $brandName, 30, 'BR', null, 'manufacturer_id', ['company_id' => (int) $data['company_id']]);

        if ($brandCode === null) {
            return back()->withInput()->withErrors(['brand_code' => 'Please enter a valid brand code.']);
        }

        if ($requestedCode !== '' && DB::table('manufacturer')->where('brand_code', $brandCode)->exists()) {
            return back()->withInput()->withErrors(['brand_code' => 'That brand code already exists.']);
        }

        Manufacturer::create([
            'company_id' => $data['company_id'],
            'manufacturer_name' => $brandName,
            'brand_code' => $brandCode,
            'slug' => $this->nextUniqueSlug('manufacturer', 'slug', $brandName, null, 'manufacturer_id', ['company_id' => (int) $data['company_id']]),
            'publication_status' => $request->boolean('publication_status', true),
        ]);
        return back()->with('message', 'Brand added successfully.');
    }

    public function updateBrand(Request $request, $id)
    {
        $brand = Manufacturer::findOrFail($id);
        $data = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'manufacturer_name' => ['required', 'string', 'max:160'],
            'brand_code' => 'nullable|string|max:30',
        ]);

        $brandName = trim($data['manufacturer_name']);
        if ($this->manufacturerNameExistsCaseInsensitive($brandName, (int) $brand->manufacturer_id)) {
            return back()->withInput()->withErrors(['manufacturer_name' => 'That brand name already exists.']);
        }

        $requestedCode = trim((string) ($data['brand_code'] ?? ''));
        if ($requestedCode !== '') {
            $brandCode = normalize_business_code($requestedCode, 30);
            if ($brandCode === null) {
                return back()->withInput()->withErrors(['brand_code' => 'Please enter a valid brand code.']);
            }

            if (DB::table('manufacturer')->where('brand_code', $brandCode)->where('manufacturer_id', '<>', $brand->manufacturer_id)->exists()) {
                return back()->withInput()->withErrors(['brand_code' => 'That brand code already exists.']);
            }
        } elseif (trim((string) ($brand->brand_code ?? '')) !== '') {
            $brandCode = (string) $brand->brand_code;
        } else {
            $brandCode = $this->nextUniqueBusinessCode('manufacturer', 'brand_code', $brandName, 30, 'BR', $brand->manufacturer_id, 'manufacturer_id', ['company_id' => (int) $data['company_id']]);
        }

        $brand->update([
            'company_id' => $data['company_id'],
            'manufacturer_name' => $brandName,
            'brand_code' => $brandCode,
            'slug' => $this->nextUniqueSlug('manufacturer', 'slug', $brandName, $brand->manufacturer_id, 'manufacturer_id', ['company_id' => (int) $data['company_id']]),
            'publication_status' => $request->boolean('publication_status'),
        ]);
        return back()->with('message', 'Brand updated successfully.');
    }

    private function manufacturerNameExistsCaseInsensitive(string $name, ?int $ignoreId = null): bool
    {
        $query = DB::table('manufacturer')
            ->whereRaw('LOWER(TRIM(manufacturer_name)) = ?', [mb_strtolower($name, 'UTF-8')]);

        if ($ignoreId !== null) {
            $query->where('manufacturer_id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    private function companyNameExistsCaseInsensitive(string $name, ?int $ignoreId = null): bool
    {
        $query = DB::table('companies')
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name, 'UTF-8')]);

        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    private function seriesNameExistsCaseInsensitive(string $name, int $manufacturerId, ?int $ignoreId = null): bool
    {
        $query = DB::table('product_series')
            ->where('manufacturer_id', $manufacturerId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name, 'UTF-8')]);

        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    public function deleteBrand($id)
    {
        $brand = Manufacturer::withCount('series')->findOrFail($id);
        $used = Product::where('manufacturer_id', $id)->exists();
        if ($used || $brand->series_count) return back()->with('exception', 'This brand is used by products or series and cannot be deleted.');
        app(RecycleBinService::class)->softDelete('manufacturer', (int) $id, session('admin_id'), 'Brand moved to Recycle Bin.');
        return back()->with('message', 'Brand moved to Recycle Bin.');
    }

    public function storeSeries(Request $request)
    {
        $data = $request->validate([
            'manufacturer_id' => 'required|integer|exists:manufacturer,manufacturer_id',
            'name' => ['required', 'string', 'max:160'],
            'series_code' => 'nullable|string|max:30',
        ]);

        $seriesName = trim($data['name']);
        if ($this->seriesNameExistsCaseInsensitive($seriesName, (int) $data['manufacturer_id'])) {
            return back()->withInput()->withErrors(['name' => 'That product series name already exists for this brand.']);
        }
        $requestedCode = trim((string) ($data['series_code'] ?? ''));
        $seriesCode = $requestedCode !== ''
            ? normalize_business_code($requestedCode, 30)
            : $this->nextUniqueBusinessCode('product_series', 'series_code', $seriesName, 30, 'SR', null, 'id', ['manufacturer_id' => (int) $data['manufacturer_id']]);

        if ($seriesCode === null) {
            return back()->withInput()->withErrors(['series_code' => 'Please enter a valid series code.']);
        }

        if ($requestedCode !== '' && DB::table('product_series')->where('series_code', $seriesCode)->exists()) {
            return back()->withInput()->withErrors(['series_code' => 'That series code already exists.']);
        }

        ProductSeries::create([
            'manufacturer_id' => $data['manufacturer_id'],
            'name' => $seriesName,
            'series_code' => $seriesCode,
            'slug' => $this->nextUniqueSlug('product_series', 'slug', $seriesName, null, 'id', ['manufacturer_id' => (int) $data['manufacturer_id']]),
            'is_active' => $request->boolean('is_active', true),
        ]);
        return back()->with('message', 'Product series added successfully.');
    }

    public function updateSeries(Request $request, $id)
    {
        $series = ProductSeries::findOrFail($id);
        $data = $request->validate([
            'manufacturer_id' => 'required|integer|exists:manufacturer,manufacturer_id',
            'name' => ['required', 'string', 'max:160'],
            'series_code' => 'nullable|string|max:30',
        ]);

        $seriesName = trim($data['name']);
        if ($this->seriesNameExistsCaseInsensitive($seriesName, (int) $data['manufacturer_id'], (int) $series->id)) {
            return back()->withInput()->withErrors(['name' => 'That product series name already exists for this brand.']);
        }
        $requestedCode = trim((string) ($data['series_code'] ?? ''));
        if ($requestedCode !== '') {
            $seriesCode = normalize_business_code($requestedCode, 30);
            if ($seriesCode === null) {
                return back()->withInput()->withErrors(['series_code' => 'Please enter a valid series code.']);
            }

            if (DB::table('product_series')->where('series_code', $seriesCode)->where('id', '<>', $series->id)->exists()) {
                return back()->withInput()->withErrors(['series_code' => 'That series code already exists.']);
            }
        } elseif (trim((string) ($series->series_code ?? '')) !== '') {
            $seriesCode = (string) $series->series_code;
        } else {
            $seriesCode = $this->nextUniqueBusinessCode('product_series', 'series_code', $seriesName, 30, 'SR', $series->id, 'id', ['manufacturer_id' => (int) $data['manufacturer_id']]);
        }

        $series->update([
            'manufacturer_id' => $data['manufacturer_id'],
            'name' => $seriesName,
            'series_code' => $seriesCode,
            'slug' => $this->nextUniqueSlug('product_series', 'slug', $seriesName, $series->id, 'id', ['manufacturer_id' => (int) $data['manufacturer_id']]),
            'is_active' => $request->boolean('is_active'),
        ]);
        return back()->with('message', 'Product series updated successfully.');
    }

    public function deleteSeries($id)
    {
        $series = ProductSeries::findOrFail($id);
        if (Product::where('product_series_id', $id)->exists()) return back()->with('exception', 'This series is assigned to products and cannot be deleted.');
        app(RecycleBinService::class)->softDelete('product_series', (int) $id, session('admin_id'), 'Series moved to Recycle Bin.');
        return back()->with('message', 'Product series moved to Recycle Bin.');
    }

    public function bulkDeleteCompanies(Request $request)
    {
        $ids = $this->validatedIds($request, 'company_ids', 'companies,id');
        $blocked = Manufacturer::whereIn('company_id',$ids)->pluck('company_id')->map(fn ($id)=>(int)$id)->unique()->all();
        return $this->bulkDelete('companies','id',$ids,$blocked,'companies','brands');
    }

    public function bulkDeleteBrands(Request $request)
    {
        $ids = $this->validatedIds($request, 'brand_ids', 'manufacturer,manufacturer_id');
        $blocked = Product::whereIn('manufacturer_id',$ids)->pluck('manufacturer_id')
            ->merge(ProductSeries::whereIn('manufacturer_id',$ids)->pluck('manufacturer_id'))->map(fn ($id)=>(int)$id)->unique()->all();
        return $this->bulkDelete('manufacturer','manufacturer_id',$ids,$blocked,'brands','products or series');
    }

    public function bulkDeleteSeries(Request $request)
    {
        $ids = $this->validatedIds($request, 'series_ids', 'product_series,id');
        $blocked = Product::whereIn('product_series_id',$ids)->pluck('product_series_id')->map(fn ($id)=>(int)$id)->unique()->all();
        return $this->bulkDelete('product_series','id',$ids,$blocked,'series','products');
    }

    private function validatedIds(Request $request, string $field, string $exists): array
    {
        $request->validate([$field=>'required|array|min:1',$field.'.*'=>'required|integer|distinct|exists:'.$exists]);
        return array_values(array_unique(array_map('intval',$request->input($field))));
    }

    private function bulkDelete(string $table, string $key, array $ids, array $blocked, string $label, string $dependency)
    {
        $deletable = array_values(array_diff($ids,$blocked));
        $deleted = 0;
        if ($deletable) {
            $typeMap = [
                'companies' => 'company',
                'manufacturer' => 'manufacturer',
                'product_series' => 'product_series',
            ];
            $type = $typeMap[$table] ?? null;
            if ($type) {
                DB::transaction(function () use ($type, $deletable, &$deleted) {
                    foreach ($deletable as $id) {
                        app(RecycleBinService::class)->softDelete($type, (int) $id, session('admin_id'), ucfirst($type).' moved to Recycle Bin.');
                        $deleted++;
                    }
                });
            }
        }
        $skipped = count($blocked);
        $message = $deleted.' '.$label.' deleted.';
        if ($skipped) $message .= ' '.$skipped.' skipped because they are used by '.$dependency.'.';
        return back()->with($deleted ? 'message' : 'exception',$message);
    }

    private function catalogImportSourceAddress(?string $sourceAddress = null): string
    {
        if ($sourceAddress === null || trim((string) $sourceAddress) === '') {
            $sourceAddress = (string) DB::table('site_settings')
                ->where('setting_key', 'catalog_import_source_address')
                ->value('setting_value');
        }

        return catalog_import_source_address($sourceAddress);
    }

    private function saveCatalogImportSourceAddress(string $sourceAddress): void
    {
        DB::table('site_settings')->updateOrInsert(['setting_key' => 'catalog_import_source_address'], [
            'setting_value' => $sourceAddress,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Cache::forget('site-settings');
    }

    private function summarizeCatalogImportResults(array $results): string
    {
        if ($results === []) {
            return 'No import steps were selected.';
        }

        return collect($results)->map(function (array $stats, string $step) {
            $parts = [ucfirst($step).': '.((int) ($stats['created'] ?? 0)).' created, '.((int) ($stats['updated'] ?? 0)).' updated'];
            if (array_key_exists('processed', $stats)) {
                $parts[] = ((int) ($stats['processed'] ?? 0)).' processed';
            }
            if (array_key_exists('total', $stats)) {
                $parts[] = ((int) ($stats['total'] ?? 0)).' total';
            }
            if ($step === 'products') {
                $fieldCaptureSummary = $this->summarizeProductFieldCapture($stats);
                if ($fieldCaptureSummary !== '') {
                    $parts[] = $fieldCaptureSummary;
                }
            }
            if (! empty($stats['has_more'])) {
                $parts[] = ((int) ($stats['remaining'] ?? 0)).' remaining';
            }

            return implode(', ', $parts);
        })->implode(' | ');
    }

    private function summarizeProductFieldCapture(array $stats): string
    {
        $capture = $stats['field_capture'] ?? null;
        if (! is_array($capture)) {
            return '';
        }

        $products = array_values(array_filter((array) ($capture['products'] ?? []), 'is_array'));
        if ($products === []) {
            return '';
        }

        $displayProducts = count($products) <= 5 ? $products : array_slice($products, 0, 3);
        $parts = collect($displayProducts)->map(function (array $product) {
            $name = Str::limit(trim((string) ($product['name'] ?? 'Product')), 42, '...');
            $captured = (int) ($product['captured'] ?? 0);
            $possible = (int) ($product['possible'] ?? 0);

            return $name.' '.$captured.'/'.$possible;
        })->all();

        if ($parts === []) {
            return '';
        }

        $summary = 'Fields captured: '.implode('; ', $parts);
        if (count($products) > count($displayProducts)) {
            $summary .= '; +'.(count($products) - count($displayProducts)).' more';
        }

        return $summary;
    }

    private function normalizeProductImportPath(string $input, string $sourceAddress): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        $sourceParts = parse_url($sourceAddress);
        if (! is_array($sourceParts) || empty($sourceParts['host'])) {
            return null;
        }

        $sourceHost = strtolower((string) $sourceParts['host']);
        $sourcePort = $sourceParts['port'] ?? null;
        $sourcePath = trim((string) ($sourceParts['path'] ?? ''), '/');

        if (preg_match('#^https?://#i', $input)) {
            $parts = parse_url($input);
            if (! is_array($parts) || strtolower((string) ($parts['host'] ?? '')) !== $sourceHost) {
                return null;
            }

            if (($parts['port'] ?? null) !== $sourcePort) {
                return null;
            }

            $path = trim((string) ($parts['path'] ?? ''), '/');
        } else {
            $path = trim((string) preg_replace('#[?#].*$#', '', $input), '/');
        }

        if ($path === '') {
            return null;
        }

        if ($sourcePath !== '' && $path === $sourcePath) {
            return null;
        }

        if ($sourcePath !== '' && Str::startsWith($path, $sourcePath.'/')) {
            $path = trim(substr($path, strlen($sourcePath) + 1), '/');
        }

        return $path !== '' ? $path : null;
    }
}
