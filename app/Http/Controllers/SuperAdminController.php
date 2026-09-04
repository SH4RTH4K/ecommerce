<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Session;
use DB;
use App\Banner;
use App\Category;
use App\Company;
use App\InventoryLocation;
use App\Manufacturer;
use App\Product;
use App\ProductCodeConfiguration;
use App\ProductSeries;
use App\SubCategory;
use App\Services\MediaLifecycleService;
use App\Services\RecycleBinService;
use App\Services\StarTechCatalogImporter;
use App\Services\ProductCodeGenerator;
use App\Services\SafeMediaDeletionService;
use App\Services\StorefrontThemeService;
use App\Services\HomepageFeatureCardService;
use App\Services\ApplicationUpdateService;
use App\Models\ApplicationUpdateSetting;
use App\Models\ApplicationDeployment;
use App\Support\PublicUpload;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\Process\Process;

class SuperAdminController extends Controller {

    public function applicationUpdate(ApplicationUpdateService $updates)
    {
        $this->authCheck();
        $settings = $updates->settings();
        $status = null; $error = null;
        if ($settings->enabled) { try { $status = $updates->status($settings); } catch (\Throwable $e) { $error = $e->getMessage(); } }
        $history = ApplicationDeployment::latest()->limit(20)->get();
        try {
            $subjects = $updates->deploymentCommitSubjects($history->map(fn ($item) => $item->deployed_commit ?: $item->target_commit)->all());
            $history->each(function ($item) use ($subjects) {
                $commit = $item->deployed_commit ?: $item->target_commit;
                $item->setAttribute('commit_subject', $subjects[$commit] ?? null);
            });
        } catch (\Throwable $exception) {
            Log::warning('Deployment history commit comments could not be loaded.', ['exception' => $exception]);
        }
        return view('admin.admin-pages.application-update', compact('settings','status','error','history'));
    }

    public function saveApplicationUpdateSettings(Request $request, ApplicationUpdateService $updates)
    {
        $this->authCheck();
        $data = $request->validate(['enabled'=>'nullable|boolean','repository_type'=>'required|in:public,private','repository_url'=>['required','string','max:255','regex:#^(https://github\\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+(?:\\.git)?|git@github\\.com:[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+\\.git)$#'],'branch'=>'required|regex:/^[A-Za-z0-9._-]{1,100}$/','remote_name'=>'required|regex:/^[A-Za-z0-9._-]{1,50}$/','authentication'=>'required|in:none,ssh,pat','secret'=>'nullable|string|max:10000','dependency_mode'=>'required|in:changed,always,never','public_asset_mode'=>'required|in:auto,laravel_public,cpanel_root','run_migrations'=>'nullable|boolean','clear_cache'=>'nullable|boolean','health_check'=>'nullable|boolean']);
        $s = $updates->settings();
        if ($data['repository_type'] === 'private' && ! in_array($data['authentication'], ['ssh','pat'], true)) return Redirect::back()->withInput()->withErrors(['authentication'=>'Private repositories require SSH Deploy Key or Personal Access Token authentication.']);
        if ($data['repository_type'] === 'private' && trim((string)($data['secret'] ?? '')) === '' && ! $s->encrypted_secret) return Redirect::back()->withInput()->withErrors(['secret'=>'A credential is required for a private repository.']);
        if ($data['repository_type'] === 'public' && $data['authentication'] !== 'none') return Redirect::back()->withInput()->withErrors(['authentication'=>'Public repositories do not require private authentication.']);
        $settingsData = collect($data)->except(['secret'])->all();
        if (! Schema::hasColumn('application_update_settings', 'public_asset_mode')) unset($settingsData['public_asset_mode']);
        $s->fill($settingsData); $s->enabled = !empty($data['enabled']); $s->run_migrations = !empty($data['run_migrations']); $s->clear_cache = !empty($data['clear_cache']); $s->health_check = !empty($data['health_check']);
        if ($s->repository_type === 'public') { $s->encrypted_secret = null; $s->secret_fingerprint = null; }
        if ($s->repository_type === 'public') $s->authentication = 'none';
        if ($s->authentication !== 'none' && trim((string)($data['secret'] ?? '')) !== '') $updates->saveSecret($s, $data['secret']);
        $s->save();
        return Redirect::back()->with('message','Application update settings saved. Test the repository connection before checking for updates.');
    }

    public function testApplicationRepository(ApplicationUpdateService $updates)
    {
        $this->authCheck();
        try { $s=$updates->settings(); $result=$updates->fetch($s); return Redirect::back()->with('message','Connection successful. Remote branch is available at '.substr((string)$result['remote'],0,12).'.'); } catch (\Throwable $e) { return Redirect::back()->with('exception',$e->getMessage()); }
    }

    public function checkApplicationUpdates(ApplicationUpdateService $updates)
    {
        $this->authCheck();
        try { $s=$updates->settings(); $result=$updates->fetch($s); $s->last_checked_commit=$result['remote']; $s->last_checked_at=now(); $s->last_status=$result['status']; $s->last_message=$result['message']; $s->save(); return Redirect::back()->with('message',$result['message']); } catch (\Throwable $e) { return Redirect::back()->with('exception',$e->getMessage()); }
    }

    public function pullApplicationUpdate(ApplicationUpdateService $updates)
    {
        $this->authCheck();
        try {
            $settings = $updates->settings();
            $result = $updates->pullLatest($settings);
            $settings->last_checked_commit = $result['remote'];
            $settings->last_checked_at = now();
            $settings->last_status = $result['status'];
            $settings->last_message = $result['message'];
            $settings->save();
            return Redirect::back()->with('message', 'Latest GitHub code was pulled into the cPanel checkout successfully.');
        } catch (\Throwable $e) {
            return Redirect::back()->with('exception', 'Pull failed: '.$e->getMessage());
        }
    }

    public function publishApplicationPublicAssets(ApplicationUpdateService $updates)
    {
        $this->authCheck();
        try {
            $directories = $updates->publishConfiguredPublicAssets($updates->settings());
            return Redirect::back()->with('message', $directories
                ? 'Published public assets to the cPanel main folder: '.implode(', ', $directories).'.'
                : 'Public asset publishing was skipped because this deployment uses Laravel\'s public/ folder.');
        } catch (\Throwable $e) {
            return Redirect::back()->with('exception', 'Public asset publishing failed: '.$e->getMessage());
        }
    }

    public function deployApplicationUpdate(ApplicationUpdateService $updates)
    {
        $this->authCheck();
        try { $deployment=$updates->deploy($updates->settings(), (int)session('admin_id')); return Redirect::back()->with('message','Deployment completed successfully at commit '.substr((string)$deployment->deployed_commit,0,12).'.'); } catch (\Throwable $e) { return Redirect::back()->with('exception','Deployment failed: '.$e->getMessage()); }
    }

    public function discardAndDeployApplicationUpdate(Request $request, ApplicationUpdateService $updates)
    {
        $this->authCheck();
        $request->validate(['confirmation' => 'required|in:DISCARD LOCAL CHANGES']);
        try {
            $deployment = $updates->deploy($updates->settings(), (int) session('admin_id'), true);
            return Redirect::back()->with('message', 'Local tracked changes were discarded and deployment completed successfully at commit '.substr((string) $deployment->deployed_commit, 0, 12).'.');
        } catch (\Throwable $e) {
            return Redirect::back()->with('exception', 'Deployment failed: '.$e->getMessage());
        }
    }

    public function rollbackApplicationDeployment($id, ApplicationUpdateService $updates)
    {
        $this->authCheck();
        try { $deployment=$updates->rollback(ApplicationDeployment::findOrFail((int)$id), (int)session('admin_id')); return Redirect::back()->with('message','Source rollback completed to '.substr((string)$deployment->deployed_commit,0,12).'. Database migrations were not reversed.'); } catch (\Throwable $e) { return Redirect::back()->with('exception',$e->getMessage()); }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $this->authCheck();
        $today=date('Y-m-d');$yesterday=date('Y-m-d',strtotime('-1 day'));
        $stats = [
            'orders' => DB::table('orders')->count(),
            'pending_orders' => DB::table('orders')->whereIn('status',['pending','confirmed','processing'])->count(),
            'today_orders' => DB::table('orders')->whereDate('created_at',date('Y-m-d'))->count(),
            'revenue' => DB::table('orders')->where('status','<>','cancelled')->sum('total'),
            'products' => DB::table('product')->whereNull('deleted_at')->where('publication_status',1)->count(),
            'low_stock' => DB::table('product')->whereNull('deleted_at')->where('stock_tracking',1)->where('stock_quantity','<=',5)->count(),
            'support' => DB::table('support_requests')->whereIn('status',['new','in_progress'])->count(),
            'feedback' => DB::table('product_reviews')->where('is_approved',0)->count() + DB::table('product_questions')->where('is_approved',0)->count(),
            'customers' => DB::table('users')->count(),
            'today_customers' => DB::table('users')->whereDate('created_at',$today)->count(),
            'total_visits' => DB::table('page_visits')->count(),
            'unique_visitors' => DB::table('visitor_sessions')->count(),
            'active_visitors' => DB::table('visitor_sessions')->where('last_seen_at','>=',now()->subMinutes(5))->count(),
            'today_visits' => DB::table('page_visits')->whereDate('visited_at',$today)->count(),
            'today_unique' => DB::table('visitor_sessions')->whereDate('last_seen_at',$today)->count(),
            'today_revenue' => DB::table('orders')->whereDate('created_at',$today)->where('status','<>','cancelled')->sum('total'),
            'open_claims' => DB::table('service_claims')->whereNotIn('status',['completed','rejected'])->count(),
        ];
        $yesterdayVisits=DB::table('page_visits')->whereDate('visited_at',$yesterday)->count();$stats['visit_change']=$yesterdayVisits?round((($stats['today_visits']-$yesterdayVisits)/$yesterdayVisits)*100,1):($stats['today_visits']?100:0);$stats['conversion_rate']=$stats['unique_visitors']?round(($stats['orders']/$stats['unique_visitors'])*100,2):0;
        $recentOrders = DB::table('orders')->latest()->limit(8)->get();
        $lowStockProducts = DB::table('product')->whereNull('deleted_at')->where('stock_tracking',1)->where('stock_quantity','<=',5)->orderBy('stock_quantity')->limit(8)->get();
        $topProducts = DB::table('order_items')->select('product_name',DB::raw('SUM(quantity) as units'),DB::raw('SUM(subtotal) as sales'))->groupBy('product_name')->orderByDesc('units')->limit(5)->get();
        $trafficTrend=DB::table('page_visits')->where('visited_at','>=',now()->subDays(6)->startOfDay())->selectRaw('DATE(visited_at) visit_date, COUNT(*) visits, COUNT(DISTINCT visitor_session_id) visitors')->groupBy(DB::raw('DATE(visited_at)'))->orderBy('visit_date')->get();
        $popularPages=DB::table('page_visits')->select('path',DB::raw('COUNT(*) visits'),DB::raw('COUNT(DISTINCT visitor_session_id) visitors'))->groupBy('path')->orderByDesc('visits')->limit(8)->get();
        $currentVisitors=DB::table('visitor_sessions')->where('last_seen_at','>=',now()->subMinutes(5))->latest('last_seen_at')->limit(10)->get();
        $recentCustomers=DB::table('users')->latest()->limit(6)->get();
        return view('admin.admin-pages.admin-home',compact('stats','recentOrders','lowStockProducts','topProducts','trafficTrend','popularPages','currentVisitors','recentCustomers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout() {
        Session::put('admin_name', '');
        Session::put('admin_id', '');
        return Redirect::route('admin.login');
    }

    private function categoryIconImage(Request $request, $categoryName)
    {
        if (! $request->hasFile('icon_image')) {
            return null;
        }

        $this->validate($request, [
            'icon_image' => 'image|mimes:jpg,jpeg,png,webp|max:1024',
        ]);

        return PublicUpload::store(
            $request->file('icon_image'),
            'asset/front-end/img/category-icons/',
            'category-'.(Str::slug($categoryName) ?: 'icon').'-',
            ['jpg', 'jpeg', 'png', 'webp']
        );
    }

    private function removeCategoryIconImage($path)
    {
        if ($path) {
            PublicUpload::remove($path);
        }
    }

    public function addCategory() {
        $this->authCheck();
        $add_category = view('admin.admin-pages.add-category');
        return view('admin.admin-master')
                        ->with('admin_main_content', $add_category);
    }

    public function saveCategory(Request $request) {
        $categoryName = trim((string) $request->category_name);
        if ($this->categoryNameExistsCaseInsensitive($categoryName)) {
            return Redirect::back()->withInput()->withErrors(['category_name' => 'That category name already exists.']);
        }

        $requestedCode = trim((string) $request->input('category_code', ''));
        $categoryCode = $requestedCode !== ''
            ? normalize_business_code($requestedCode, 30)
            : $this->nextUniqueBusinessCode('category', 'category_code', $categoryName, 30, 'CAT', null, 'category_id');

        if ($categoryCode === null) {
            return Redirect::back()->withInput()->withErrors(['category_code' => 'Please enter a valid category code.']);
        }

        if ($requestedCode !== '' && DB::table('category')->where('category_code', $categoryCode)->exists()) {
            return Redirect::back()->withInput()->withErrors(['category_code' => 'That category code already exists.']);
        }

        $data = array();
        $data['category_name'] = $categoryName;
        $data['category_code'] = $categoryCode;
        $data['slug'] = $this->nextUniqueSlug('category', 'slug', $categoryName, null, 'category_id');
        $data['category_description'] = $request->category_description;
        $allowedIcons = ['fa-folder-open','fa-music','fa-signal','fa-link','fa-archive','fa-refresh','fa-picture-o','fa-desktop','fa-dot-circle-o','fa-gamepad','fa-hdd-o','fa-headphones','fa-video-camera','fa-keyboard-o','fa-laptop','fa-mouse-pointer','fa-print','fa-clock-o','fa-volume-up','fa-bolt','fa-camera','fa-mobile','fa-cogs','fa-shield','fa-globe','fa-sitemap','fa-shopping-cart'];
        $data['icon_class'] = in_array($request->icon_class, $allowedIcons, true) ? $request->icon_class : 'fa-folder-open';
        $data['icon_image'] = $this->categoryIconImage($request, $categoryName);
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['display_order'] = max(0, (int) $request->display_order);
        $data['publication_status'] = $request->publication_status;
        DB::table('category')->insert($data);
        Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree');
        Session::put('message', 'Save Category Successfully');
        return Redirect::to('/add-category');
    }

    public function manageCategory(Request $request) {
        $this->authCheck();
        $categoryQuery = Category::query();
        $search = trim((string) $request->query('search'));
        if ($search !== '') {
            $categoryQuery->where(function ($query) use ($search) {
                $query->where('category_name', 'like', '%'.$search.'%')
                    ->orWhere('category_code', 'like', '%'.$search.'%');
                if (ctype_digit($search)) $query->orWhere('category_id', (int) $search);
            });
        }
        if (in_array((string) $request->query('status'), ['0', '1'], true)) {
            $categoryQuery->where('publication_status', (int) $request->query('status'));
        }
        if (in_array((string) $request->query('featured'), ['0', '1'], true)) {
            $categoryQuery->where('is_featured', (int) $request->query('featured'));
        }
        if (Schema::hasColumn('category', 'show_in_navigation') && in_array((string) $request->query('navbar'), ['0', '1'], true)) {
            $categoryQuery->where('show_in_navigation', (int) $request->query('navbar'));
        }
        $all_category = $categoryQuery->orderBy('display_order')->orderBy('category_name')->get();
        $featured_category_info = Category::orderBy('display_order')->orderBy('category_name')->get();
        $manage_category = view('admin.admin-pages.manage-category')
                ->with('all_category_info', $all_category)
                ->with('featured_category_info', $featured_category_info);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_category);
    }

    public function updateFeaturedCategories(Request $request)
    {
        $this->authCheck();

        $selectedIds = collect((array) $request->input('featured_category_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $requestedOrder = collect((array) $request->input('featured_category_order', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        if ($requestedOrder->isNotEmpty()) {
            $selectedIds = $requestedOrder
                ->filter(fn ($id) => $selectedIds->contains($id))
                ->values();
        }

        $publishedCategoryIds = Category::where('publication_status', 1)
            ->whereIn('category_id', $selectedIds)
            ->pluck('category_id');
        $publishedIds = $selectedIds
            ->filter(fn ($id) => $publishedCategoryIds->contains($id))
            ->values();

        DB::table('category')->update(['is_featured' => 0]);

        foreach ($publishedIds as $index => $categoryId) {
            DB::table('category')
                ->where('category_id', $categoryId)
                ->update([
                    'is_featured' => 1,
                    'display_order' => $index + 1,
                ]);
        }

        Cache::forget('mega-menu-tree');
        Cache::forget('storefront-navbar-tree');

        return Redirect::to('/manage-category')
            ->with('message', $publishedIds->count().' featured categor'.($publishedIds->count() === 1 ? 'y' : 'ies').' saved.');
    }

    public function unpublishedCategory($category_id) {
        DB::table('category')
                ->where('category_id', $category_id)
                ->update(['publication_status' => 0]);
        Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree');
        return Redirect::to('/manage-category');
    }

    public function publishedCategory($category_id) {
        DB::table('category')
                ->where('category_id', $category_id)
                ->update(['publication_status' => 1]);
        Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree');
        return Redirect::to('/manage-category');
    }

    public function editCategory($category_id) {
        $this->authCheck();
        $category_info = Category::find($category_id);
        $edit_category = view('admin.admin-pages.edit-category')
                ->with('category_info', $category_info);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_category);
    }

    public function updateCategory(Request $request) {
        $this->authCheck();
        $categoryId = (int) $request->category_id;
        $existing = Category::find($categoryId);
        if (! $existing) {
            return Redirect::back()->with('exception', 'Category not found.');
        }

        $categoryName = trim((string) $request->category_name);
        if ($this->categoryNameExistsCaseInsensitive($categoryName, $categoryId)) {
            return Redirect::back()->withInput()->withErrors(['category_name' => 'That category name already exists.']);
        }

        $requestedCode = trim((string) $request->input('category_code', (string) ($existing->category_code ?? '')));
        if ($requestedCode !== '') {
            $categoryCode = normalize_business_code($requestedCode, 30);
            if ($categoryCode === null) {
                return Redirect::back()->withInput()->withErrors(['category_code' => 'Please enter a valid category code.']);
            }

            if (DB::table('category')->where('category_code', $categoryCode)->where('category_id', '<>', $categoryId)->exists()) {
                return Redirect::back()->withInput()->withErrors(['category_code' => 'That category code already exists.']);
            }
        } elseif (trim((string) ($existing->category_code ?? '')) !== '') {
            $categoryCode = (string) $existing->category_code;
        } else {
            $categoryCode = $this->nextUniqueBusinessCode('category', 'category_code', $categoryName, 30, 'CAT', $categoryId, 'category_id');
        }

        $data = array();
        $data['category_name'] = $categoryName;
        $data['category_code'] = $categoryCode;
        $data['slug'] = $this->nextUniqueSlug('category', 'slug', $categoryName, $categoryId, 'category_id');
        $data['category_description'] = $request->category_description;
        $allowedIcons = ['fa-folder-open','fa-music','fa-signal','fa-link','fa-archive','fa-refresh','fa-picture-o','fa-desktop','fa-dot-circle-o','fa-gamepad','fa-hdd-o','fa-headphones','fa-video-camera','fa-keyboard-o','fa-laptop','fa-mouse-pointer','fa-print','fa-clock-o','fa-volume-up','fa-bolt','fa-camera','fa-mobile','fa-cogs','fa-shield','fa-globe','fa-sitemap','fa-shopping-cart'];
        $data['icon_class'] = in_array($request->icon_class, $allowedIcons, true) ? $request->icon_class : 'fa-folder-open';
        if ($request->hasFile('icon_image')) {
            $newIconImage = $this->categoryIconImage($request, $categoryName);
            $this->removeCategoryIconImage($existing->icon_image);
            $data['icon_image'] = $newIconImage;
        } elseif ($request->boolean('remove_icon_image')) {
            $this->removeCategoryIconImage($existing->icon_image);
            $data['icon_image'] = null;
        }
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['display_order'] = max(0, (int) $request->display_order);
        $existing->update($data);
        Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree');
        return Redirect::to('/manage-category');
    }

    public function removeCategoryIcon($categoryId)
    {
        $this->authCheck();
        $category = Category::find((int) $categoryId);
        if (! $category) {
            return Redirect::back()->with('exception', 'Category not found.');
        }

        $this->removeCategoryIconImage($category->icon_image);
        $category->update(['icon_image' => null]);

        return Redirect::to('/edit-category/'.$category->category_id)
            ->with('message', 'Custom category image removed. The fallback icon is now active.');
    }

    public function deleteCategory($category_id) {
        if (! Category::find($category_id)) {
            return Redirect::to('/manage-category')->with('exception', 'Category not found.');
        }

        if (Product::where('category_id', $category_id)->exists()) {
            return Redirect::to('/manage-category')->with('exception', 'This category is used by active products and cannot be deleted yet.');
        }

        if (SubCategory::where('category_id', $category_id)->exists()) {
            return Redirect::to('/manage-category')->with('exception', 'Move or delete this category\'s subcategories before deleting the category.');
        }

        app(RecycleBinService::class)->softDelete('category', (int) $category_id, session('admin_id'), 'Category moved to Recycle Bin.');
        Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree');
        return Redirect::to('/manage-category');
    }

    public function bulkDeleteCategories(Request $request)
    {
        $this->validate($request,['category_ids'=>'required|array|min:1','category_ids.*'=>'required|integer|distinct|exists:category,category_id']);
        $ids=array_values(array_unique(array_map('intval',$request->category_ids)));
        $used=array_unique(array_merge(
            Product::whereIn('category_id',$ids)->pluck('category_id')->map(function($id){return (int)$id;})->all(),
            SubCategory::whereIn('category_id',$ids)->pluck('category_id')->map(function($id){return (int)$id;})->all()
        ));
        $deletable=array_values(array_diff($ids,$used));
        $deleted=0;
        if ($deletable) {
            DB::transaction(function () use ($deletable, &$deleted) {
                foreach ($deletable as $id) {
                    app(RecycleBinService::class)->softDelete('category', (int) $id, session('admin_id'), 'Category moved to Recycle Bin.');
                    $deleted++;
                }
            });
        }
        Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree'); Cache::forget('xml-sitemap');
        return $this->bulkDeleteResult('/manage-category',$deleted,count($used),'categor','products or subcategories');
    }
    
    
    public function addSubCategory() {
        $this->authCheck();
        $all_category=DB::table('category')
                ->whereNull('deleted_at')
                ->where('publication_status', 1)
                ->orderBy('category_name')
                ->get();
        $addSubCategory = view('admin.admin-pages.add-subCategory')
                ->with('all_category', $all_category);
        return view('admin.admin-master')
                        ->with('admin_main_content', $addSubCategory);
    }

    public function saveSubCategory(Request $request) {
        $subCategoryName = trim((string) $request->subCategory_name);
        $categoryId = (int) $request->category_id;
        if ($this->subCategoryNameExistsCaseInsensitive($subCategoryName, $categoryId)) {
            return Redirect::back()->withInput()->withErrors(['subCategory_name' => 'That subcategory name already exists in this category.']);
        }

        $requestedCode = trim((string) $request->input('sub_category_code', ''));
        $subCategoryCode = $requestedCode !== ''
            ? normalize_business_code($requestedCode, 30)
            : $this->nextUniqueBusinessCode('sub_category', 'subcategory_code', $subCategoryName, 30, 'SUB', null, 'sub_category_id', ['category_id' => $categoryId]);

        if ($subCategoryCode === null) {
            return Redirect::back()->withInput()->withErrors(['sub_category_code' => 'Please enter a valid subcategory code.']);
        }

        if ($requestedCode !== '' && DB::table('sub_category')->where('subcategory_code', $subCategoryCode)->exists()) {
            return Redirect::back()->withInput()->withErrors(['sub_category_code' => 'That subcategory code already exists.']);
        }

        $data = array();
        $data['sub_category_name'] = $subCategoryName;
        $data['subcategory_code'] = $subCategoryCode;
        $data['slug'] = $this->nextUniqueSlug('sub_category', 'slug', $subCategoryName, null, 'sub_category_id', ['category_id' => $categoryId]);
        $data['category_id'] = $categoryId;
        $data['publication_status'] = $request->publication_status;
        DB::table('sub_category')
                ->insert($data);
        Session::put('message', 'Sub Category Save Successfully');
        return Redirect::to('/add-subCategory');
    }

    public function manageSubCategory(Request $request) {
        $this->authCheck();
        $subcategoryQuery = DB::table('sub_category as s')
                ->join('category as c', 's.category_id', '=', 'c.category_id')
                ->whereNull('s.deleted_at')
                ->whereNull('c.deleted_at')
                ->select('s.*', 'c.category_name');
        $search = trim((string) $request->query('search'));
        if ($search !== '') {
            $subcategoryQuery->where(function ($query) use ($search) {
                $query->where('s.sub_category_name', 'like', '%'.$search.'%')
                    ->orWhere('s.subcategory_code', 'like', '%'.$search.'%')
                    ->orWhere('c.category_name', 'like', '%'.$search.'%');
                if (ctype_digit($search)) $query->orWhere('s.sub_category_id', (int) $search);
            });
        }
        if ($request->filled('category_id')) $subcategoryQuery->where('s.category_id', (int) $request->query('category_id'));
        if (in_array((string) $request->query('status'), ['0', '1'], true)) $subcategoryQuery->where('s.publication_status', (int) $request->query('status'));
        if (Schema::hasColumn('sub_category', 'show_in_navbar') && in_array((string) $request->query('navbar'), ['0', '1'], true)) {
            $subcategoryQuery->where('s.show_in_navbar', (int) $request->query('navbar'));
        }
        $category_details = $subcategoryQuery->orderBy('c.category_name')->orderBy('s.display_order')->orderBy('s.sub_category_name')->get();
        $categories = Category::orderBy('category_name')
                ->get();
//        $all_subCategory = DB::table('sub_category')
//                ->get();
        $manage_subCategory = view('admin.admin-pages.manage-subCategory')
//                ->with('all_subCategory', $all_subCategory)
                ->with('category_details',$category_details)
                ->with('categories', $categories);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_subCategory);
    }

    public function unpublishedSubCategory($sub_category_id) {
        DB::table('sub_category')
                ->where('sub_category_id', $sub_category_id)
                ->update(['publication_status' => 0]);
        return Redirect::to('/manage-subCategory');
    }

    public function publishedSubCategory($sub_category_id) {
        DB::table('sub_category')
                ->where('sub_category_id', $sub_category_id)
                ->update(['publication_status' => 1]);
        return Redirect::to('/manage-subCategory');
    }

    public function deleteSubCategory($sub_category_id) {
        if (! SubCategory::find($sub_category_id)) {
            return Redirect::to('/manage-subCategory')->with('exception', 'Subcategory not found.');
        }

        if (Product::where('sub_category', $sub_category_id)->exists()) {
            return Redirect::to('/manage-subCategory')->with('exception', 'This subcategory is used by active products and cannot be deleted yet.');
        }

        app(RecycleBinService::class)->softDelete('sub_category', (int) $sub_category_id, session('admin_id'), 'Subcategory moved to Recycle Bin.');
        return Redirect::to('/manage-subCategory');
    }

    public function bulkDeleteSubCategories(Request $request)
    {
        $this->validate($request, [
            'sub_category_ids' => 'required|array|min:1',
            'sub_category_ids.*' => 'required|integer|distinct|exists:sub_category,sub_category_id',
        ]);

        $ids = array_values(array_unique(array_map('intval', $request->sub_category_ids)));
        $usedIds = Product::whereIn('sub_category', $ids)
            ->pluck('sub_category')->map(function ($id) { return (int) $id; })->unique()->all();
        $deletableIds = array_values(array_diff($ids, $usedIds));
        $deleted = 0;

        if ($deletableIds) {
            DB::transaction(function () use ($deletableIds, &$deleted) {
                foreach ($deletableIds as $id) {
                    app(RecycleBinService::class)->softDelete('sub_category', (int) $id, session('admin_id'), 'Subcategory moved to Recycle Bin.');
                    $deleted++;
                }
            });
        }

        $message = $deleted.' subcategor'.($deleted === 1 ? 'y' : 'ies').' deleted.';
        if ($usedIds) $message .= ' '.count($usedIds).' skipped because '.(count($usedIds) === 1 ? 'it is' : 'they are').' assigned to products.';
        return Redirect::to('/manage-subCategory')->with($deleted ? 'message' : 'exception', $message);
    }

    public function editSubCategory($sub_category_id) {
        $this->authCheck();
        $all_category=DB::table('category')
                ->whereNull('deleted_at')
                ->where('publication_status', 1)
                ->orderBy('category_name')
                ->get();
        $subCategory_info = SubCategory::find($sub_category_id);
        $edit_subCategory = view('admin.admin-pages.edit-subCategory')
                ->with('subCategory_info', $subCategory_info)
                ->with('all_category',$all_category);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_subCategory);
    }

    public function updateSubCategory(Request $request) {
        $this->authCheck();
        $subCategoryId = (int) $request->subCategory_id;
        $existing = SubCategory::find($subCategoryId);
        if (! $existing) {
            return Redirect::back()->with('exception', 'Subcategory not found.');
        }

        $subCategoryName = trim((string) $request->subCategory_name);
        $categoryId = (int) $request->category_id;
        if ($this->subCategoryNameExistsCaseInsensitive($subCategoryName, $categoryId, $subCategoryId)) {
            return Redirect::back()->withInput()->withErrors(['subCategory_name' => 'That subcategory name already exists in this category.']);
        }

        $requestedCode = trim((string) $request->input('sub_category_code', (string) ($existing->subcategory_code ?? '')));
        if ($requestedCode !== '') {
            $subCategoryCode = normalize_business_code($requestedCode, 30);
            if ($subCategoryCode === null) {
                return Redirect::back()->withInput()->withErrors(['sub_category_code' => 'Please enter a valid subcategory code.']);
            }

            if (DB::table('sub_category')->where('subcategory_code', $subCategoryCode)->where('sub_category_id', '<>', $subCategoryId)->exists()) {
                return Redirect::back()->withInput()->withErrors(['sub_category_code' => 'That subcategory code already exists.']);
            }
        } elseif (trim((string) ($existing->subcategory_code ?? '')) !== '') {
            $subCategoryCode = (string) $existing->subcategory_code;
        } else {
            $subCategoryCode = $this->nextUniqueBusinessCode('sub_category', 'subcategory_code', $subCategoryName, 30, 'SUB', $subCategoryId, 'sub_category_id', ['category_id' => $categoryId]);
        }

        $data = array();
        $data['sub_category_name'] = $subCategoryName;
        $data['subcategory_code'] = $subCategoryCode;
        $data['slug'] = $this->nextUniqueSlug('sub_category', 'slug', $subCategoryName, $subCategoryId, 'sub_category_id', ['category_id' => $categoryId]);
        $data['category_id'] = $categoryId;
        $existing->update($data);
        return Redirect::to('/manage-subCategory');
    }
    
    
//  For Manufacturer
    

    public function addManufacturer() {
        $this->authCheck();
        $companies = DB::table('companies')->whereNull('deleted_at')->orderBy('name')->get();
        $add_manufacturer = view('admin.admin-pages.add-manufacturer');
        return view('admin.admin-master')
                        ->with('admin_main_content', $add_manufacturer)
                        ->with('companies', $companies);
    }

    public function saveManufacturer(Request $request) {
        $manufacturerName = trim((string) $request->manufacturer_name);
        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;

        if ($this->manufacturerNameExistsCaseInsensitive($manufacturerName)) {
            return Redirect::back()->withInput()->withErrors(['manufacturer_name' => 'That brand name already exists.']);
        }

        $requestedCode = trim((string) $request->input('brand_code', ''));
        $brandCode = $requestedCode !== ''
            ? normalize_business_code($requestedCode, 30)
            : $this->nextUniqueBusinessCode('manufacturer', 'brand_code', $manufacturerName, 30, 'BR', null, 'manufacturer_id', ['company_id' => $companyId]);

        if ($brandCode === null) {
            return Redirect::back()->withInput()->withErrors(['brand_code' => 'Please enter a valid brand code.']);
        }

        if ($requestedCode !== '' && DB::table('manufacturer')->where('brand_code', $brandCode)->exists()) {
            return Redirect::back()->withInput()->withErrors(['brand_code' => 'That brand code already exists.']);
        }

        $data = array();
        $data['company_id'] = $companyId;
        $data['manufacturer_name'] = $manufacturerName;
        $data['brand_code'] = $brandCode;
        $data['slug'] = $this->nextUniqueSlug('manufacturer', 'slug', $manufacturerName, null, 'manufacturer_id', ['company_id' => $companyId]);
        $data['publication_status'] = $request->publication_status;
        DB::table('manufacturer')
                ->insert($data);
        Session::put('message', 'Company Name Save Successfully');
        return Redirect::to('/add-manufacturer');
    }

    public function manageManufacturer() {
        $this->authCheck();
        $all_manufacturer = DB::table('manufacturer as m')
                ->leftJoin('companies as c', 'c.id', '=', 'm.company_id')
                ->whereNull('m.deleted_at')
                ->whereNull('c.deleted_at')
                ->select('m.*', 'c.name as company_name')
                ->get();
        $manufacturerOptions = Manufacturer::orderBy('manufacturer_name')
                ->get();
        $featuredBrandSetting = DB::table('site_settings')
                ->where('setting_key', 'homepage_featured_brands')
                ->value('setting_value');
        $featuredBrandIds = $featuredBrandSetting !== null
                ? collect(json_decode($featuredBrandSetting, true) ?: [])->map(fn ($id) => (int) $id)->filter()->values()->all()
                : [];
        $featuredBrandIconSetting = DB::table('site_settings')
                ->where('setting_key', 'homepage_featured_brand_icons')
                ->value('setting_value');
        $featuredBrandIcons = json_decode($featuredBrandIconSetting ?: '{}', true) ?: [];
        $featuredBrandImageSetting = DB::table('site_settings')
                ->where('setting_key', 'homepage_featured_brand_images')
                ->value('setting_value');
        $featuredBrandImages = json_decode($featuredBrandImageSetting ?: '{}', true) ?: [];
        $manage_manufacturer = view('admin.admin-pages.manage-manufacturer')
                ->with('all_manufacturer', $all_manufacturer)
                ->with('manufacturerOptions', $manufacturerOptions)
                ->with('featuredBrandIds', $featuredBrandIds)
                ->with('featuredBrandIcons', $featuredBrandIcons)
                ->with('featuredBrandImages', $featuredBrandImages);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_manufacturer);
    }

    public function updateFeaturedBrands(Request $request)
    {
        $this->authCheck();
        $this->validate($request, ['featured_brand_images.*' => 'nullable|image|max:2048']);

        $selectedIds = collect((array) $request->input('featured_brand_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $requestedOrder = collect((array) $request->input('featured_brand_order', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        if ($requestedOrder->isNotEmpty()) {
            $selectedIds = $requestedOrder
                ->filter(fn ($id) => $selectedIds->contains($id))
                ->values();
        }

        $publishedBrandIds = Manufacturer::where('publication_status', 1)
            ->whereIn('manufacturer_id', $selectedIds)
            ->pluck('manufacturer_id');
        $publishedIds = $selectedIds
            ->filter(fn ($id) => $publishedBrandIds->contains($id))
            ->values();
        $allowedIcons = ['fa-building', 'fa-laptop', 'fa-desktop', 'fa-mobile-phone', 'fa-camera', 'fa-shield', 'fa-bolt', 'fa-cog', 'fa-star', 'fa-tag', 'fa-certificate', 'fa-cube', 'fa-hdd-o', 'fa-print'];
        $icons = collect((array) $request->input('featured_brand_icons', []))
            ->mapWithKeys(function ($icon, $id) use ($allowedIcons) {
                $icon = trim((string) $icon);
                return in_array($icon, $allowedIcons, true) ? [(int) $id => $icon] : [];
            })
            ->only($publishedIds->map(fn ($id) => (int) $id)->all())
            ->all();
        $imageSetting = DB::table('site_settings')->where('setting_key', 'homepage_featured_brand_images')->value('setting_value');
        $images = json_decode($imageSetting ?: '{}', true) ?: [];
        $brandNames = Manufacturer::whereIn('manufacturer_id', $selectedIds)
            ->pluck('manufacturer_name', 'manufacturer_id');
        $deleteFeaturedBrandImage = function ($path) {
            $path = ltrim((string) $path, '/');
            $prefix = 'asset/front-end/img/featured-brands/';
            if ($path !== '' && strpos($path, $prefix) === 0) {
                $absolutePath = public_path($path);
                if (is_file($absolutePath)) @unlink($absolutePath);
            }
        };
        $renameFeaturedBrandImage = function ($path, $brandId, $brandName) {
            $path = ltrim((string) $path, '/');
            $prefix = 'asset/front-end/img/featured-brands/';
            if ($path === '' || strpos($path, $prefix) !== 0) return $path;
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $slug = \Illuminate\Support\Str::slug((string) $brandName) ?: 'brand';
            $newPath = $prefix.'brand-'.$slug.'-'.(int) $brandId.'.'.$extension;
            if ($path === $newPath) return $path;
            $oldAbsolute = public_path($path);
            $newAbsolute = public_path($newPath);
            if (is_file($oldAbsolute)) {
                if (is_file($newAbsolute)) @unlink($newAbsolute);
                if (@rename($oldAbsolute, $newAbsolute)) return $newPath;
            }
            return $path;
        };
        foreach ($selectedIds as $brandId) {
            $brandId = (int) $brandId;
            if (in_array((string) $brandId, (array) $request->input('remove_featured_brand_images', []), true)) {
                $deleteFeaturedBrandImage($images[(string) $brandId] ?? null);
                unset($images[(string) $brandId]);
            }
            $image = $request->file('featured_brand_images', [])[$brandId] ?? null;
            if ($image) {
                $oldImage = $images[(string) $brandId] ?? null;
                $images[(string) $brandId] = \App\Support\PublicUpload::store($image, 'asset/front-end/img/featured-brands/', 'brand-'.$brandId.'-', ['jpg', 'jpeg', 'png', 'webp']);
                if ($oldImage && $oldImage !== $images[(string) $brandId]) $deleteFeaturedBrandImage($oldImage);
            }
            if (!empty($images[(string) $brandId])) {
                $images[(string) $brandId] = $renameFeaturedBrandImage($images[(string) $brandId], $brandId, $brandNames->get($brandId, 'brand'));
            }
        }
        $images = collect($images)->only($publishedIds->map(fn ($id) => (string) $id)->all())->all();

        DB::table('site_settings')->updateOrInsert(
            ['setting_key' => 'homepage_featured_brands'],
            ['setting_value' => json_encode($publishedIds->values()->all()), 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('site_settings')->updateOrInsert(
            ['setting_key' => 'homepage_featured_brand_icons'],
            ['setting_value' => json_encode($icons), 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('site_settings')->updateOrInsert(
            ['setting_key' => 'homepage_featured_brand_images'],
            ['setting_value' => json_encode($images), 'created_at' => now(), 'updated_at' => now()]
        );

        return Redirect::to('/manage-manufacturer')
            ->with('message', 'Featured brands saved: '.$publishedIds->count().' selected, '.count($icons).' Font Awesome icon'.(count($icons) === 1 ? '' : 's').', and '.count($images).' image icon'.(count($images) === 1 ? '' : 's').'.');
    }

    public function unpublishedManufacturer($manufacturer_id) {
        DB::table('manufacturer')
                ->where('manufacturer_id', $manufacturer_id)
                ->update(['publication_status' => 0]);
        return Redirect::to('/manage-manufacturer');
    }

    public function publishedManufacturer($manufacturer_id) {
        DB::table('manufacturer')
                ->where('manufacturer_id', $manufacturer_id)
                ->update(['publication_status' => 1]);
        return Redirect::to('/manage-manufacturer');
    }

    public function deleteManufacturer($manufacturer_id) {
        if (! Manufacturer::find($manufacturer_id)) {
            return Redirect::to('/manage-manufacturer')->with('exception', 'Brand not found.');
        }

        if (Product::where('manufacturer_id', $manufacturer_id)->exists()) {
            return Redirect::to('/manage-manufacturer')->with('exception', 'This brand is used by active products and cannot be deleted yet.');
        }

        if (ProductSeries::where('manufacturer_id', $manufacturer_id)->exists()) {
            return Redirect::to('/manage-manufacturer')->with('exception', 'Move or delete this brand\'s product series before deleting the brand.');
        }

        app(RecycleBinService::class)->softDelete('manufacturer', (int) $manufacturer_id, session('admin_id'), 'Brand moved to Recycle Bin.');
        return Redirect::to('/manage-manufacturer');
    }

    public function bulkDeleteManufacturers(Request $request)
    {
        $this->validate($request,['manufacturer_ids'=>'required|array|min:1','manufacturer_ids.*'=>'required|integer|distinct|exists:manufacturer,manufacturer_id']);
        $ids=array_values(array_unique(array_map('intval',$request->manufacturer_ids)));
        $used=Product::whereIn('manufacturer_id',$ids)->pluck('manufacturer_id')->map(function($id){return (int)$id;})->unique()->all();
        $used=array_values(array_unique(array_merge($used, ProductSeries::whereIn('manufacturer_id',$ids)->pluck('manufacturer_id')->map(function($id){return (int)$id;})->unique()->all())));
        $deletable=array_values(array_diff($ids,$used));
        $deleted=0;
        if ($deletable) {
            DB::transaction(function () use ($deletable, &$deleted) {
                foreach ($deletable as $id) {
                    app(RecycleBinService::class)->softDelete('manufacturer', (int) $id, session('admin_id'), 'Brand moved to Recycle Bin.');
                    $deleted++;
                }
            });
        }
        return $this->bulkDeleteResult('/manage-manufacturer',$deleted,count($used),'manufacturer','products');
    }

    public function editManufacturer($manufacturer_id) {
        $this->authCheck();
        $manufacturer_info = Manufacturer::find($manufacturer_id);
        $companies = DB::table('companies')->orderBy('name')->get();
        $edit_manufacturer = view('admin.admin-pages.edit-manufacturer')
                ->with('manufacturer_info', $manufacturer_info)
                ->with('companies', $companies);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_manufacturer);
    }

    public function updateManufacturer(Request $request) {
        $this->authCheck();
        $manufacturerId = (int) $request->manufacturer_id;
        $existing = Manufacturer::find($manufacturerId);
        if (! $existing) {
            return Redirect::back()->with('exception', 'Brand not found.');
        }

        $manufacturerName = trim((string) $request->manufacturer_name);
        $companyId = $request->filled('company_id') ? (int) $request->company_id : ($existing->company_id ?? null);
        if ($this->manufacturerNameExistsCaseInsensitive($manufacturerName, $manufacturerId)) {
            return Redirect::back()->withInput()->withErrors(['manufacturer_name' => 'That brand name already exists.']);
        }

        $requestedCode = trim((string) $request->input('brand_code', (string) ($existing->brand_code ?? '')));
        if ($requestedCode !== '') {
            $brandCode = normalize_business_code($requestedCode, 30);
            if ($brandCode === null) {
                return Redirect::back()->withInput()->withErrors(['brand_code' => 'Please enter a valid brand code.']);
            }

            if (DB::table('manufacturer')->where('brand_code', $brandCode)->where('manufacturer_id', '<>', $manufacturerId)->exists()) {
                return Redirect::back()->withInput()->withErrors(['brand_code' => 'That brand code already exists.']);
            }
        } elseif (trim((string) ($existing->brand_code ?? '')) !== '') {
            $brandCode = (string) $existing->brand_code;
        } else {
            $brandCode = $this->nextUniqueBusinessCode('manufacturer', 'brand_code', $manufacturerName, 30, 'BR', $manufacturerId, 'manufacturer_id', ['company_id' => $companyId]);
        }

        $data = array();
        $data['company_id'] = $companyId;
        $data['manufacturer_name'] = $manufacturerName;
        $data['brand_code'] = $brandCode;
        $data['slug'] = $this->nextUniqueSlug('manufacturer', 'slug', $manufacturerName, $manufacturerId, 'manufacturer_id', ['company_id' => $companyId]);
        $existing->update($data);
        return Redirect::to('/manage-manufacturer');
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

    private function categoryNameExistsCaseInsensitive(string $name, ?int $ignoreId = null): bool
    {
        $query = DB::table('category')
            ->whereRaw('LOWER(TRIM(category_name)) = ?', [mb_strtolower($name, 'UTF-8')]);

        if ($ignoreId !== null) {
            $query->where('category_id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    private function subCategoryNameExistsCaseInsensitive(string $name, int $categoryId, ?int $ignoreId = null): bool
    {
        $query = DB::table('sub_category')
            ->where('category_id', $categoryId)
            ->whereRaw('LOWER(TRIM(sub_category_name)) = ?', [mb_strtolower($name, 'UTF-8')]);

        if ($ignoreId !== null) {
            $query->where('sub_category_id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    
    //  For Product
    
    public function addProduct() {
        $this->authCheck();
        $category = DB::table('category')
                ->whereNull('deleted_at')
                ->orderBy("category_name","asc")
                ->get();
        $sub_category = DB::table('sub_category')
                ->whereNull('deleted_at')
                ->get();
        $companies = DB::table('companies')->orderBy('name')->get();
        $branches = DB::table('inventory_locations')->where('type', 'branch')->where('is_active', 1)->orderBy('name')->get();
        $manufacturer = DB::table('manufacturer as m')->leftJoin('companies as c','c.id','=','m.company_id')->whereNull('m.deleted_at')->whereNull('c.deleted_at')->select('m.*','c.name as company_name')->orderBy('c.name')->orderBy('m.manufacturer_name')->get();
        $productSeries = DB::table('product_series as s')->leftJoin('manufacturer as m','m.manufacturer_id','=','s.manufacturer_id')->whereNull('s.deleted_at')->whereNull('m.deleted_at')->select('s.*','m.company_id','m.manufacturer_name as brand_name')->where('s.is_active',1)->orderBy('s.name')->get();
        $catalogAttributes = DB::table('catalog_attributes')->orderBy('category_id')->orderBy('display_order')->get()->groupBy('category_id');
        $specificationTemplates = config('catalog_specification_templates', []);
        $productCodeConfiguration = app(ProductCodeGenerator::class)->resolveConfiguration([]);
        $productCodeSnapshot = $productCodeConfiguration ? app(ProductCodeGenerator::class)->snapshot($productCodeConfiguration) : null;
        $home = view('admin.admin-pages.add-product')
                ->with('category', $category)
                ->with('companies', $companies)
                ->with('branches', $branches)
                ->with('manufacturer', $manufacturer)
                ->with('productSeries', $productSeries)
                ->with('sub_category', $sub_category)
                ->with('catalogAttributes', $catalogAttributes)
                ->with('specificationTemplates', $specificationTemplates)
                ->with('productCodeConfiguration', $productCodeConfiguration)
                ->with('productCodeSnapshot', $productCodeSnapshot);
        return view('admin.admin-master')
                        ->with('admin_main_content', $home);
    }

    public function saveProduct(Request $request, ProductCodeGenerator $generator) {
        $this->authCheck();
        $request->merge([
            'barcode' => $request->filled('barcode') ? trim($request->barcode) : null,
            'product_code' => $request->filled('product_code') ? trim($request->product_code) : trim((string) $request->input('sku', '')),
        ]);
        $this->validate($request, [
            'barcode' => 'nullable|string|max:64|unique:product,barcode',
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:inventory_locations,id',
            'product_code' => 'nullable|string|max:100',
            'product_id' => 'nullable|string|max:255',
            'publication_status' => 'required|boolean',
            'category_id' => 'required|integer|exists:category,category_id',
            'sub_category_id' => 'nullable|integer|exists:sub_category,sub_category_id',
            'regular_price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'gallery_images' => 'nullable|array|max:10',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'manufacturer_id' => 'nullable|integer|exists:manufacturer,manufacturer_id',
            'product_series_id' => ['nullable','integer',Rule::exists('product_series','id')->where(function($query)use($request){$query->where('manufacturer_id',$request->manufacturer_id);})],
            'industry_profile' => 'required|in:general,technology,clothing,food,medicine',
            'generic_name' => 'nullable|string|max:255', 'strength' => 'nullable|string|max:255',
            'dosage_form' => 'nullable|string|max:255', 'storage_instructions' => 'nullable|string|max:255',
            'allergen_information' => 'nullable|string|max:2000',
            'variants' => 'nullable|array|max:100', 'variants.*.name' => 'nullable|string|max:255',
            'variants.*.sku' => 'nullable|string|max:255|distinct', 'variants.*.barcode' => 'nullable|string|max:64|distinct',
            'variants.*.price_adjustment' => 'nullable|numeric', 'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'lots' => 'nullable|array|max:100', 'lots.*.lot_number' => 'nullable|string|max:255|distinct',
            'lots.*.manufactured_at' => 'nullable|date', 'lots.*.expires_at' => 'nullable|date|after_or_equal:lots.*.manufactured_at',
            'lots.*.quantity' => 'nullable|integer|min:0', 'lots.*.supplier_reference' => 'nullable|string|max:255',
        ]);

        $this->validateVariantUniqueness($request);

        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;
        $brandCompanyId = null;
        if ($request->filled('manufacturer_id')) {
            $brandCompanyId = Manufacturer::where('manufacturer_id', $request->manufacturer_id)->value('company_id');
        }
        if ($companyId !== null && $brandCompanyId !== null && (int) $companyId !== (int) $brandCompanyId) {
            return Redirect::back()->withInput()->withErrors([
                'company_id' => 'The selected brand belongs to a different company.',
            ]);
        }
        if ($companyId === null) {
            $companyId = $brandCompanyId ? (int) $brandCompanyId : null;
        }

        $galleryFiles = (array) $request->file('gallery_images', []);
        try {
            $galleryImages = $this->storeProductImages($galleryFiles);
        } catch (\Throwable $exception) {
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'gallery_images' => 'The product gallery image(s) could not be saved. Please try again or check upload storage permissions.',
            ]);
        }

        $image = $request->file('product_image');
        try {
            $productImage = $image ? $this->storeProductImage($image) : 'asset/front-end/img/home/pic 1.jpg';
        } catch (\Throwable $exception) {
            foreach ($galleryImages as $storedGalleryImage) {
                $this->deleteOwnedProductImage($storedGalleryImage);
            }
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'product_image' => 'The product image could not be saved. Please try again or check upload storage permissions.',
            ]);
        }

        $context = [
            'company_id' => $companyId,
            'branch_id' => $request->filled('branch_id') ? (int) $request->branch_id : null,
            'category_id' => (int) $request->category_id,
            'subcategory_id' => $request->filled('sub_category_id') ? (int) $request->sub_category_id : null,
            'manufacturer_id' => $request->filled('manufacturer_id') ? (int) $request->manufacturer_id : null,
            'series_id' => $request->filled('product_series_id') ? (int) $request->product_series_id : null,
            'industry_profile' => $request->industry_profile,
        ];
        $configuration = $generator->resolveConfiguration($context) ?: ProductCodeConfiguration::with('components')->where('is_active', 1)->orderByDesc('id')->first();
        $manualProductCode = trim((string) $request->input('product_code', $request->input('sku', '')));
        $productCode = null;
        if ($manualProductCode !== '' && $configuration && ((bool) $configuration->allow_manual_override || ! (bool) $configuration->auto_generate) && $this->adminHasPermission('override_product_code')) {
            $productCode = normalize_product_code($manualProductCode, 100);
            if ($productCode === null) {
                return Redirect::back()->withInput()->withErrors(['product_code' => 'Please enter a valid product code.']);
            }

            if ($this->productCodeExists($productCode)) {
                return Redirect::back()->withInput()->withErrors(['product_code' => 'That product code already exists.']);
            }
        }

        $data = array();
        $data['product_id'] = trim((string) $request->product_id);
        $data['barcode'] = $request->barcode;
        $data['company_id'] = $companyId;
        $data['branch_id'] = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $data['category_id'] = $request->category_id;
        $data['sub_category'] = $request->sub_category_id;
        $data['manufacturer_id'] = $request->manufacturer_id ?: null;
        $data['product_series_id'] = $request->product_series_id ?: null;
        $this->setIndustryData($data, $request);
        $data['product_model'] = $request->product_model;
        $data['product_name'] = $request->product_name;
        $data['publication_status'] = (int) $request->publication_status;
        $data['product_description'] = sanitize_product_description_html($request->input('product_description'));
        $data['short_description'] = $request->short_description;
        $data['key_features'] = json_encode($this->parseList($request->key_features));
        $data['specifications'] = json_encode($this->parseSpecifications($request->specifications));
        $data['gallery_images'] = json_encode(array_values(array_unique($galleryImages)));
        $regularPrice = max(0,(float)$request->regular_price);
        $offerPrice = $request->filled('offer_price') ? max(0,(float)$request->offer_price) : null;
        $data['regular_price'] = $regularPrice;
        $data['offer_price'] = $offerPrice !== null && $offerPrice < $regularPrice ? $offerPrice : null;
        $data['purchase_price'] = max(0,(float)$request->purchase_price);
        $data['product_condition'] = $request->product_condition;
        $data['stock_quantity'] = max(0, (int) $request->stock_quantity);
        $data['warranty'] = $request->warranty;
        $data['publication_status'] = $request->publication_status;
        $data['top_product'] = $request->has('top_product') ? 1 : 0;
        $data['is_new_arrival'] = $request->has('is_new_arrival') ? 1 : 0;
        $data['seo_title'] = $request->seo_title;
        $data['seo_description'] = $request->seo_description;
        $data['product_image'] = $productImage;

        try {
            $productId = DB::transaction(function () use ($data, $request, &$productCode, $generator, $context, $configuration) {
                if ($productCode === null) {
                    $allocation = $generator->allocate($context, $configuration);
                    $productCode = $allocation['product_code'];
                }

                $data['product_id'] = trim((string) ($data['product_id'] ?? '')) !== '' ? trim((string) $data['product_id']) : $productCode;
                $data['product_code'] = $productCode;
                $data['sku'] = $productCode;
                $data['created_at'] = now();
                $data['updated_at'] = now();

                $productId = DB::table('product')->insertGetId($data);
                $this->syncProductAttributes($productId, $request);
                $this->syncProductVariantsAndLots($productId, $request);

                return $productId;
            });
        } catch (\Throwable $exception) {
            foreach ($galleryImages as $storedGalleryImage) {
                $this->deleteOwnedProductImage($storedGalleryImage);
            }
            if ($productImage !== 'asset/front-end/img/home/pic 1.jpg') {
                $this->deleteOwnedProductImage($productImage);
            }
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'product_code' => 'The product could not be saved. Please check the submitted values and try again.',
            ]);
        }

        Session::put('message', 'Save Product Successfully');
        return Redirect::to('/add-product');
    }
    
    public function editProduct($id) {
        $this->authCheck();
        $product_info = Product::find($id);
        if (! $product_info) {
            return Redirect::to('/manage-product')->with('exception', 'Product not found.');
        }
        $category = DB::table('category')
                ->whereNull('deleted_at')
                ->orderBy("category_name","asc")
                ->get();
        $sub_category = DB::table('sub_category')
                ->whereNull('deleted_at')
                ->get();
        $companies = DB::table('companies')->orderBy('name')->get();
        $branches = DB::table('inventory_locations')->where('type', 'branch')->where('is_active', 1)->orderBy('name')->get();
        $manufacturer = DB::table('manufacturer as m')->leftJoin('companies as c','c.id','=','m.company_id')->whereNull('m.deleted_at')->whereNull('c.deleted_at')->select('m.*','c.name as company_name')->orderBy('c.name')->orderBy('m.manufacturer_name')->get();
        $productSeries = DB::table('product_series as s')->leftJoin('manufacturer as m','m.manufacturer_id','=','s.manufacturer_id')->whereNull('s.deleted_at')->whereNull('m.deleted_at')->select('s.*','m.company_id','m.manufacturer_name as brand_name')->orderBy('s.name')->get();
        $catalogAttributes = DB::table('catalog_attributes')->orderBy('category_id')->orderBy('display_order')->get()->groupBy('category_id');
        $specificationTemplates = config('catalog_specification_templates', []);
        $productAttributeValues = DB::table('product_attribute_values')->where('product_id',$id)->pluck('value','attribute_id');
        $productVariants = DB::table('product_variants')->where('product_id',$id)->orderBy('id')->get();
        $productLots = DB::table('product_lots')->where('product_id',$id)->orderBy('expires_at')->orderBy('id')->get();
        $productCodeConfiguration = app(ProductCodeGenerator::class)->resolveConfiguration([
            'company_id' => $product_info->company_id ?? null,
            'branch_id' => $product_info->branch_id ?? null,
            'category_id' => $product_info->category_id ?? null,
            'subcategory_id' => $product_info->sub_category ?? null,
            'manufacturer_id' => $product_info->manufacturer_id ?? null,
            'series_id' => $product_info->product_series_id ?? null,
        ]);
        $productCodeSnapshot = $productCodeConfiguration ? app(ProductCodeGenerator::class)->snapshot($productCodeConfiguration) : null;
        $edit_product = view('admin.admin-pages.edit-product')
                ->with('product_info', $product_info)
                ->with('category', $category)
                ->with('companies', $companies)
                ->with('branches', $branches)
                ->with('manufacturer', $manufacturer)
                ->with('productSeries', $productSeries)
                ->with('sub_category', $sub_category)
                ->with('catalogAttributes', $catalogAttributes)
                ->with('specificationTemplates', $specificationTemplates)
                ->with('productAttributeValues', $productAttributeValues)
                ->with('productCodeConfiguration', $productCodeConfiguration)
                ->with('productCodeSnapshot', $productCodeSnapshot);
        $edit_product->with('productVariants', $productVariants)->with('productLots', $productLots);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_product);
    }
            

    public function updateProduct(Request $request, ProductCodeGenerator $generator) {
        $this->authCheck();
        $id = $request->id;
        $request->merge([
            'barcode' => $request->filled('barcode') ? trim($request->barcode) : null,
            'product_code' => $request->filled('product_code') ? trim($request->product_code) : trim((string) $request->input('sku', '')),
        ]);
        $this->validate($request, [
            'barcode' => 'nullable|string|max:64|unique:product,barcode,'.$id,
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:inventory_locations,id',
            'product_code' => 'nullable|string|max:100',
            'product_id' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:category,category_id',
            'sub_category_id' => 'nullable|integer|exists:sub_category,sub_category_id',
            'regular_price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'gallery_images' => 'nullable|array|max:10',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_gallery_images' => 'nullable|array',
            'manufacturer_id' => 'nullable|integer|exists:manufacturer,manufacturer_id',
            'product_series_id' => ['nullable','integer',Rule::exists('product_series','id')->where(function($query)use($request){$query->where('manufacturer_id',$request->manufacturer_id);})],
            'industry_profile' => 'required|in:general,technology,clothing,food,medicine',
            'generic_name' => 'nullable|string|max:255', 'strength' => 'nullable|string|max:255',
            'dosage_form' => 'nullable|string|max:255', 'storage_instructions' => 'nullable|string|max:255',
            'allergen_information' => 'nullable|string|max:2000',
            'variants' => 'nullable|array|max:100', 'variants.*.name' => 'nullable|string|max:255',
            'variants.*.sku' => 'nullable|string|max:255|distinct', 'variants.*.barcode' => 'nullable|string|max:64|distinct',
            'variants.*.price_adjustment' => 'nullable|numeric', 'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'lots' => 'nullable|array|max:100', 'lots.*.lot_number' => 'nullable|string|max:255|distinct',
            'lots.*.manufactured_at' => 'nullable|date', 'lots.*.expires_at' => 'nullable|date|after_or_equal:lots.*.manufactured_at',
            'lots.*.quantity' => 'nullable|integer|min:0', 'lots.*.supplier_reference' => 'nullable|string|max:255',
        ]);

        // A subcategory owns its parent category. This prevents stale legacy
        // category values from being submitted when the UI is locked.
        if ($request->filled('sub_category_id')) {
            $parentCategoryId = DB::table('sub_category')
                ->where('sub_category_id', (int) $request->sub_category_id)
                ->value('category_id');

            if ($parentCategoryId) {
                $request->merge(['category_id' => (int) $parentCategoryId]);
            }
        }

        $this->validateVariantUniqueness($request, $id);

        $beforeProduct = Product::find($id);
        if (! $beforeProduct) {
            return Redirect::to('/manage-product')->with('exception', 'Product not found.');
        }

        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;
        $brandCompanyId = null;
        if ($request->filled('manufacturer_id')) {
            $brandCompanyId = Manufacturer::where('manufacturer_id', $request->manufacturer_id)->value('company_id');
        }
        if ($companyId !== null && $brandCompanyId !== null && (int) $companyId !== (int) $brandCompanyId) {
            return Redirect::back()->withInput()->withErrors([
                'company_id' => 'The selected brand belongs to a different company.',
            ]);
        }
        if ($companyId === null) {
            $companyId = $brandCompanyId ? (int) $brandCompanyId : null;
        }

        $currentGallery = array_values(array_filter((array) $beforeProduct->gallery_images));
        $removeGallery = array_values(array_intersect($currentGallery, (array) $request->input('remove_gallery_images', [])));
        $keptGallery = array_values(array_diff($currentGallery, $removeGallery));
        $galleryUploads = (array) $request->file('gallery_images', []);
        if (count($keptGallery) + count($galleryUploads) > 10) {
            return Redirect::back()->withInput()->withErrors(['gallery_images' => 'A product can have a maximum of 10 gallery images. Remove existing images or select fewer new files.']);
        }
        try {
            $newGallery = $this->storeProductImages($galleryUploads);
        } catch (\Throwable $exception) {
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'gallery_images' => 'The product gallery image(s) could not be saved. Please try again or check upload storage permissions.',
            ]);
        }

        $image = $request->file('product_image');
        $newProductImage = null;
        if ($image) {
            try {
                $newProductImage = $this->storeProductImage($image);
            } catch (\Throwable $exception) {
                foreach ($newGallery as $storedGalleryImage) {
                    $this->deleteOwnedProductImage($storedGalleryImage);
                }
                report($exception);
                return Redirect::back()->withInput()->withErrors([
                    'product_image' => 'The product image could not be saved. Please try again or check upload storage permissions.',
                ]);
            }
        }

        $context = [
            'company_id' => $companyId,
            'branch_id' => $request->filled('branch_id') ? (int) $request->branch_id : null,
            'category_id' => (int) $request->category_id,
            'subcategory_id' => $request->filled('sub_category_id') ? (int) $request->sub_category_id : null,
            'manufacturer_id' => $request->filled('manufacturer_id') ? (int) $request->manufacturer_id : null,
            'series_id' => $request->filled('product_series_id') ? (int) $request->product_series_id : null,
            'industry_profile' => $request->industry_profile,
        ];
        $configuration = $generator->resolveConfiguration($context) ?: ProductCodeConfiguration::with('components')->where('is_active', 1)->orderByDesc('id')->first();
        $requestedCode = trim((string) $request->input('product_code', $request->input('sku', '')));
        $existingCode = trim((string) ($beforeProduct->product_code ?: $beforeProduct->sku ?: $beforeProduct->product_id ?: ''));
        $productCode = $existingCode;
        $historyReason = trim((string) $request->input('product_code_reason', 'Product code updated'));
        $canRegenerate = $configuration && (bool) $configuration->allow_regeneration && $this->adminHasPermission('regenerate_product_code');
        $canManualOverride = $configuration && ((bool) $configuration->allow_manual_override || ! (bool) $configuration->auto_generate) && $this->adminHasPermission('override_product_code');

        if ($requestedCode !== '' && $requestedCode !== $existingCode) {
            if ($existingCode !== '' && ! $canRegenerate) {
                return Redirect::back()->withInput()->withErrors([
                    'product_code' => 'You do not have permission to regenerate this product code.',
                ]);
            }

                if ($existingCode === '' && ! $canManualOverride) {
                    $requestedCode = '';
                } else {
                    $normalizedRequestedCode = normalize_product_code($requestedCode, 100);
                    if ($normalizedRequestedCode === null) {
                        return Redirect::back()->withInput()->withErrors([
                            'product_code' => 'Please enter a valid product code.',
                    ]);
                }

                if ($this->productCodeExists($normalizedRequestedCode, $id)) {
                    return Redirect::back()->withInput()->withErrors([
                        'product_code' => 'That product code already exists.',
                    ]);
                }

                $productCode = $normalizedRequestedCode;
            }
        }

        $data = array();
        $data['product_id'] = trim((string) $request->product_id) !== '' ? trim((string) $request->product_id) : ($beforeProduct->product_id ?: null);
        $data['barcode'] = $request->barcode;
        $data['company_id'] = $companyId;
        $data['branch_id'] = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $data['category_id'] = $request->category_id;
        $data['sub_category'] = $request->sub_category_id;
        $data['manufacturer_id'] = $request->manufacturer_id ?: null;
        $data['product_series_id'] = $request->product_series_id ?: null;
        $this->setIndustryData($data, $request);
        $data['product_model'] = $request->product_model;
        $data['product_name'] = $request->product_name;
        $data['product_description'] = sanitize_product_description_html($request->input('product_description'));
        $data['short_description'] = $request->short_description;
        $data['key_features'] = json_encode($this->parseList($request->key_features));
        $data['specifications'] = json_encode($this->parseSpecifications($request->specifications));
        $data['gallery_images'] = json_encode(array_values(array_unique(array_merge($keptGallery, $newGallery))));
        $regularPrice = max(0,(float)$request->regular_price);
        $offerPrice = $request->filled('offer_price') ? max(0,(float)$request->offer_price) : null;
        $data['regular_price'] = $regularPrice;
        $data['offer_price'] = $offerPrice !== null && $offerPrice < $regularPrice ? $offerPrice : null;
        $data['purchase_price'] = max(0,(float)$request->purchase_price);
        $data['top_product'] = $request->has('top_product') ? 1 : 0;
        $data['is_new_arrival'] = $request->has('is_new_arrival') ? 1 : 0;
        $data['product_condition'] = $request->product_condition;
        $data['stock_quantity'] = max(0, (int) $request->stock_quantity);
        $data['warranty'] = $request->warranty;
        $data['seo_title'] = $request->seo_title;
        $data['seo_description'] = $request->seo_description;
        if ($newProductImage) {
            $data['product_image'] = $newProductImage;
        }

        try {
            DB::transaction(function () use ($id, &$productCode, $data, $request, $generator, $context, $configuration, $existingCode, $historyReason, $beforeProduct, $newProductImage) {
                if ($productCode === '' || $productCode === null) {
                    $allocation = $generator->allocate($context, $configuration);
                    $productCode = $allocation['product_code'];
                }

                $data['product_code'] = $productCode;
                $data['sku'] = $productCode;
                if (! trim((string) ($data['product_id'] ?? ''))) {
                    $data['product_id'] = $productCode;
                }
                $data['updated_at'] = now();

                DB::table('product')->where('id', $id)->update($data);
                $updatedProduct = Product::find($id);
                if ($existingCode !== '' && $existingCode !== $productCode) {
                    $generator->recordHistory($configuration, $updatedProduct, $existingCode, $productCode, $historyReason, session('admin_id'));
                }

                $this->syncProductAttributes($id, $request);
                $this->syncProductVariantsAndLots($id, $request);
            });
        } catch (\Throwable $exception) {
            foreach ($newGallery as $storedGalleryImage) {
                $this->deleteOwnedProductImage($storedGalleryImage);
            }
            if ($newProductImage) {
                $this->deleteOwnedProductImage($newProductImage);
            }
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'product_code' => 'The product could not be updated. Please check the submitted values and try again.',
            ]);
        }

        if ($newProductImage) {
            $this->deleteOwnedProductImage($beforeProduct->product_image);
        }
        foreach ($removeGallery as $removedImage) {
            $this->deleteOwnedProductImage($removedImage);
        }
        if ($data['product_condition'] === 'In Stock' && (!$beforeProduct || $beforeProduct->product_condition !== 'In Stock')) app(\App\Services\StockAlertService::class)->process($id);
        Session::put('message', 'Update Product Successfully');
        return Redirect::to('/manage-product');
    }
    
    
    
    public function manageProduct(Request $request, StarTechCatalogImporter $importer)
    {
        $this->authCheck();
        $productQuery = Product::query()->select('product.*')->selectRaw(\App\Product::sellingPriceSql().' selling_price');
        $search = trim((string) $request->query('search'));
        if ($search !== '') {
            $productQuery->where(function ($query) use ($search) {
                $query->where('product_name', 'like', '%'.$search.'%')
                    ->orWhere('product_model', 'like', '%'.$search.'%')
                    ->orWhere('product_code', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');
                if (ctype_digit($search)) $query->orWhere('id', (int) $search);
            });
        }
        if ($request->filled('category_id')) $productQuery->where('category_id', (int) $request->query('category_id'));
        if ($request->filled('sub_category_id')) $productQuery->where('sub_category', (string) $request->query('sub_category_id'));
        if ($request->filled('manufacturer_id')) $productQuery->where('manufacturer_id', (string) $request->query('manufacturer_id'));
        if (in_array((string) $request->query('status'), ['0', '1'], true)) $productQuery->where('publication_status', (int) $request->query('status'));
        if ($request->filled('stock')) $productQuery->where('product_condition', (string) $request->query('stock'));
        if (in_array((string) $request->query('featured'), ['0', '1'], true)) $productQuery->where('top_product', (int) $request->query('featured'));
        if (in_array((string) $request->query('new_arrival'), ['0', '1'], true)) $productQuery->where('is_new_arrival', (int) $request->query('new_arrival'));
        $all_product = $productQuery->orderBy('id','DESC')->get();
        $filterCategories = Category::orderBy('category_name')->get(['category_id','category_name']);
        $filterSubcategories = DB::table('sub_category')->whereNull('deleted_at')->orderBy('sub_category_name')->get(['sub_category_id','category_id','sub_category_name']);
        $filterManufacturers = DB::table('manufacturer')->whereNull('deleted_at')->orderBy('manufacturer_name')->get(['manufacturer_id','manufacturer_name']);
        $productConditions = Product::query()->whereNotNull('product_condition')->where('product_condition','<>','')->distinct()->orderBy('product_condition')->pluck('product_condition');
        $productImportCategories = collect($importer->categoryMap())
            ->mapWithKeys(fn (array $meta, string $slug) => [$slug => $meta['name']])
            ->all();
        $manage_product = view('admin.admin-pages.manage-product')
                ->with('all_product', $all_product)
                ->with('filterCategories', $filterCategories)
                ->with('filterSubcategories', $filterSubcategories)
                ->with('filterManufacturers', $filterManufacturers)
                ->with('productConditions', $productConditions)
                ->with('productImportCategories', $productImportCategories)
                ->with('startechProductImportState', session('startech_product_import_state'));
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_product);
    }

    public function pcBuilderSettings()
    {
        $this->authCheck();
        $configuration = app(\App\Services\PcBuilderConfigurationService::class);
        $slots = $configuration->slots();
        $categories = Category::where('publication_status', 1)->orderBy('category_name')->get(['category_id','category_name']);
        $subcategories = DB::table('sub_category')->where('publication_status',1)->whereNull('deleted_at')->orderBy('sub_category_name')->get(['sub_category_id','category_id','sub_category_name']);
        foreach($slots as &$slot){$categoryId=(int)($slot['category_id']??0);$subId=(int)($slot['sub_category_id']??0);$slot['available_count']=Product::whereNull('deleted_at')->where('publication_status',1)->where('product_condition','In Stock')->when($categoryId,fn($q)=>$q->where('category_id',$categoryId))->when($subId,fn($q)=>$q->where('sub_category',(string)$subId))->count();} unset($slot);
        return view('admin.admin-pages.pc-builder-settings', ['slots'=>$slots, 'rules'=>$configuration->rules(), 'categories'=>$categories, 'subcategories'=>$subcategories]);
    }

    public function updatePcBuilderSettings(Request $request)
    {
        $this->authCheck();
        $this->validate($request, ['slots'=>'required|array', 'rules'=>'nullable|array', 'slot_icons.*'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:1024']);
        $allowedIcons = ['cog','sitemap','list','picture-o','hdd-o','bolt','archive','refresh','desktop','cogs','folder-open','wrench'];
        $defaults = app(\App\Services\PcBuilderConfigurationService::class)->defaultSlots();
        $slots = [];
        foreach ($defaults as $default) {
            $input = (array) $request->input('slots.'.$default['key'], []);
            $subCategory = DB::table('sub_category')->where('sub_category_id',(int)($input['sub_category_id']??0))->where('publication_status',1)->whereNull('deleted_at')->first();
            $category = $subCategory ? Category::where('category_id',$subCategory->category_id)->where('publication_status',1)->first() : Category::where('category_id', (int) ($input['category_id'] ?? 0))->where('publication_status', 1)->first();
            $slotData = array_merge($default, [
                'label' => trim((string) ($input['label'] ?? $default['label'])) ?: $default['label'],
                'category' => $category ? strtolower($category->category_name) : $default['category'],
                'category_id' => $category ? (int) $category->category_id : null,
                'sub_category_id' => $subCategory ? (int) $subCategory->sub_category_id : null,
                'required' => !empty($input['required']),
                'icon' => in_array($input['icon'] ?? '', $allowedIcons, true) ? $input['icon'] : $default['icon'],
            ]);
            $uploadedIcon = $request->file('slot_icons.'.$default['key']);
            if ($uploadedIcon) {
                $this->removeCategoryIconImage($slotData['icon_image'] ?? null);
                $slotData['icon_image'] = PublicUpload::store($uploadedIcon, 'asset/front-end/img/pc-builder-icons/', 'pc-builder-'.(Str::slug($slotData['label']) ?: $default['key']).'-', ['jpg', 'jpeg', 'png', 'webp']);
            }
            $slots[] = $slotData;
        }
        $slotKeys = collect($slots)->pluck('key')->all();
        $rules = [];
        foreach ((array) $request->input('rules', []) as $input) {
            $input = (array) $input;
            if (trim((string) ($input['left_attribute'] ?? '')) === '' || trim((string) ($input['right_attribute'] ?? '')) === '') continue;
            if (!in_array($input['left_slot'] ?? '', $slotKeys, true) || !in_array($input['right_slot'] ?? '', $slotKeys, true)) continue;
            $rules[] = ['name'=>trim((string) ($input['name'] ?? 'Compatibility rule')), 'left_slot'=>$input['left_slot'], 'left_attribute'=>trim((string)$input['left_attribute']), 'right_slot'=>$input['right_slot'], 'right_attribute'=>trim((string)$input['right_attribute']), 'message'=>trim((string)($input['message'] ?? 'Selected components are not compatible.')), 'enabled'=>!empty($input['enabled'])];
        }
        app(\App\Services\PcBuilderConfigurationService::class)->save($slots, $rules);
        return Redirect::to('/pc-builder-settings')->with('message', 'PC Builder settings saved.');
    }

    public function removePcBuilderIcon(Request $request, $slotKey)
    {
        $this->authCheck();
        $configuration = app(\App\Services\PcBuilderConfigurationService::class);
        $slots = $configuration->slots();
        $found = false;
        foreach ($slots as &$slot) {
            if ((string) $slot['key'] !== (string) $slotKey) continue;
            $this->removeCategoryIconImage($slot['icon_image'] ?? null);
            $slot['icon_image'] = null;
            $found = true;
            break;
        }
        unset($slot);
        if (! $found) return Redirect::to('/pc-builder-settings')->with('exception', 'Builder slot not found.');
        $configuration->save($slots, $configuration->rules());
        return Redirect::to('/pc-builder-settings')->with('message', 'Custom slot image removed. The built-in icon is now active.');
    }

    public function unpublishedProduct($id) {
        $this->authCheck();
        $updated = DB::table('product')->where('id', $id)->whereNull('deleted_at')->update(['publication_status' => 0, 'updated_at' => now()]);
        Cache::forget('xml-sitemap');
        return Redirect::to('/manage-product')->with($updated ? 'message' : 'exception', $updated ? 'Product unpublished.' : 'Product not found.');
    }

    public function publishedProduct($id) {
        $this->authCheck();
        $updated = DB::table('product')->where('id', $id)->whereNull('deleted_at')->update(['publication_status' => 1, 'updated_at' => now()]);
        Cache::forget('xml-sitemap');
        return Redirect::to('/manage-product')->with($updated ? 'message' : 'exception', $updated ? 'Product published.' : 'Product not found.');
    }

    public function bulkUpdateProductPublication(Request $request)
    {
        $this->authCheck();
        $this->validate($request, [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|distinct|exists:product,id',
            'publication_status' => 'required|boolean',
        ]);

        $ids = array_values(array_unique(array_map('intval', $request->product_ids)));
        $updated = DB::table('product')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update(['publication_status' => (int) $request->publication_status, 'updated_at' => now()]);

        Cache::forget('xml-sitemap');

        return Redirect::to('/manage-product')->with(
            'message',
            $updated.' product'.($updated === 1 ? '' : 's').' '.((int) $request->publication_status === 1 ? 'published' : 'unpublished').'.'
        );
    }

    public function deleteproduct($id) {
        $product = Product::find($id);
        if (! $product) {
            return Redirect::to('/manage-product')->with('exception', 'Product not found.');
        }

        app(RecycleBinService::class)->softDelete('product', (int) $id, session('admin_id'), 'Product moved to Recycle Bin.');
        return Redirect::to('/manage-product');
    }

    public function bulkDeleteProducts(Request $request)
    {
        $this->validate($request,['product_ids'=>'required|array|min:1','product_ids.*'=>'required|integer|distinct|exists:product,id']);
        $ids=array_values(array_unique(array_map('intval',$request->product_ids)));
        $deleted=0;
        DB::transaction(function () use ($ids, &$deleted) {
            foreach ($ids as $id) {
                app(RecycleBinService::class)->softDelete('product', (int) $id, session('admin_id'), 'Product moved to Recycle Bin.');
                $deleted++;
            }
        });
        Cache::forget('xml-sitemap');
        return $this->bulkDeleteResult('/manage-product',$deleted,0,'product','orders, purchasing, returns, service claims, or transfer history');
    }

    public function siteCustomization()
    {
        $this->authCheck();
        $settings = DB::table('site_settings')->pluck('setting_value', 'setting_key');
        $brandName = trim((string) ($settings->get('site_name') ?: config('app.default_name', 'Ecommerce')));
        $topAnnouncements = \Schema::hasTable('top_announcements') ? \App\TopAnnouncement::orderByDesc('priority')->orderBy('display_order')->get() : collect();
        $siteContactItems = \Schema::hasTable('site_contact_items') ? \App\SiteContactItem::orderByDesc('is_primary')->orderBy('display_order')->get() : collect();
        $themeService = app(StorefrontThemeService::class);
        $storefrontTheme = $themeService->fromSettings($settings);
        $setup = $this->siteCustomizationSetup($settings);
        return view('admin.admin-pages.site-customization', [
            'settings' => $settings,
            'topAnnouncements' => $topAnnouncements,
            'siteContactItems' => $siteContactItems,
            'storeSetupChecks' => $setup['checks'],
            'storeSetupPercent' => $setup['percent'],
            'siteCustomizationDefaults' => $this->siteCustomizationDefaults($brandName),
            'storefrontTheme' => $storefrontTheme,
            'storefrontThemeDefaults' => $themeService->defaults(),
            'storefrontThemeGroups' => $themeService->groupedFields(),
            'storefrontThemePresets' => $themeService->presetOptions(),
            'storefrontThemePresetPalettes' => $themeService->presetPalettes(),
            'storefrontThemeContrast' => $themeService->contrastReport($storefrontTheme),
            'initialPanel' => request()->is('homepage-feature-cards') ? 'content' : '',
        ]);
    }

    public function homepageFeatureCards(HomepageFeatureCardService $featureCards)
    {
        $this->authCheck();
        $data = $featureCards->adminData();
        $content = view('admin.admin-pages.homepage-feature-cards', $data);

        return view('admin.admin-master')->with('admin_main_content', $content);
    }

    public function updateHomepageFeatureCards(Request $request, HomepageFeatureCardService $featureCards)
    {
        $this->authCheck();
        $this->validate($request, [
            'cards' => 'nullable|array',
            'cards.*.name' => 'required|string|max:150', 'cards.*.card_type' => 'required|in:TEXT_CTA,IMAGE,IMAGE_TEXT,CATEGORY,SUBCATEGORY,PRODUCT,BRAND,SPECIAL_OFFER,CUSTOM',
            'cards.*.kicker_text' => 'nullable|string|max:150', 'cards.*.title' => 'nullable|string|max:255', 'cards.*.description' => 'nullable|string|max:1000',
            'cards.*.image_alt' => 'nullable|string|max:255',
            'cards.*.button_text' => 'nullable|string|max:100', 'cards.*.link_type' => 'required|in:NONE,CUSTOM_URL,INTERNAL_PAGE,CATEGORY,SUBCATEGORY,PRODUCT,BRAND,CONTACT_PAGE,SHOP_PRODUCTS,ANCHOR',
            'cards.*.custom_url' => ['nullable','regex:/^(https?:\/\/|\/|#)/i','max:255'], 'cards.*.category_id' => 'nullable|integer|exists:category,category_id',
            'cards.*.sub_category_id' => 'nullable|integer|exists:sub_category,sub_category_id', 'cards.*.product_id' => 'nullable|integer|exists:product,id', 'cards.*.manufacturer_id' => 'nullable|integer|exists:manufacturer,manufacturer_id',
            'cards.*.clickable_area' => 'required|in:BUTTON_ONLY,ENTIRE_CARD,IMAGE_ONLY', 'cards.*.open_in_new_tab' => 'nullable|boolean',
            'cards.*.color_style' => 'required|in:THEME_PRIMARY,THEME_SECONDARY,THEME_ACCENT,BLUE,ORANGE,DARK,LIGHT,CUSTOM', 'cards.*.custom_background_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/', 'cards.*.custom_text_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/', 'cards.*.custom_button_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/', 'cards.*.custom_button_text_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'cards.*.image_fit' => 'required|in:COVER,CONTAIN', 'cards.*.image_position' => 'required|in:CENTER,TOP,BOTTOM,LEFT,RIGHT', 'cards.*.text_position' => 'required|in:TOP_LEFT,TOP_CENTER,TOP_RIGHT,CENTER_LEFT,CENTER,CENTER_RIGHT,BOTTOM_LEFT,BOTTOM_CENTER,BOTTOM_RIGHT', 'cards.*.overlay_style' => 'required|in:NONE,LIGHT,MEDIUM,DARK',
            'cards.*.sort_order' => 'required|integer|min:0|max:999999', 'cards.*.is_active' => 'nullable|boolean', 'cards.*.use_product_image' => 'nullable|boolean', 'cards.*.use_product_name' => 'nullable|boolean', 'cards.*.use_product_price' => 'nullable|boolean', 'cards.*.publish_from' => 'nullable|date', 'cards.*.publish_until' => 'nullable|date|after_or_equal:cards.*.publish_from',
            'cards.*.detach_image' => 'nullable|boolean',
            'cards.*.delete_image' => 'nullable|boolean',
            'new_card' => 'nullable|array', 'new_card.name' => 'nullable|string|max:150',
            'card_image' => 'nullable|array', 'card_image.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096', 'new_card_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'config.layout' => 'required|in:STACKED,GRID,SLIDER', 'config.max_visible_cards' => 'required|in:1,2,3,4,5,AUTO', 'config.card_gap' => 'required|integer|min:0|max:40', 'config.slider_interval' => 'required|integer|min:3|max:30', 'delete_card' => 'nullable|integer|exists:homepage_feature_cards,id',
        ]);
        $sharedImageKept = false;
        DB::transaction(function () use ($request, $featureCards, &$sharedImageKept) {
            if ($request->filled('delete_card')) {
                $card = \App\HomepageFeatureCard::findOrFail((int) $request->input('delete_card'));
                $old = $card->image_path; $oldId = $card->id; $card->delete(); if ($old) app(\App\Services\MediaLifecycleService::class)->deleteIfUnreferenced($old, ['homepage_feature_card'=>[$oldId]], 'Feature card deleted.');
            }
            foreach ((array) $request->input('cards', []) as $id => $input) {
                if ((int) $id === (int) $request->input('delete_card')) continue;
                $card = \App\HomepageFeatureCard::findOrFail((int) $id);
                $data = collect((array) $input)->only(['name','card_type','kicker_text','title','description','image_alt','button_text','link_type','custom_url','category_id','sub_category_id','product_id','manufacturer_id','clickable_area','open_in_new_tab','color_style','custom_background_color','custom_text_color','custom_button_color','custom_button_text_color','image_fit','image_position','text_position','overlay_style','sort_order','is_active','use_product_image','use_product_name','use_product_price','publish_from','publish_until'])->all();
                $oldImage = $card->image_path;
                $detachImage = ! empty($input['detach_image']);
                $deleteImage = ! empty($input['delete_image']);
                foreach (['open_in_new_tab','is_active','use_product_image','use_product_name','use_product_price'] as $key) $data[$key] = ! empty($input[$key]);
                foreach (['category_id','sub_category_id','product_id','manufacturer_id'] as $key) $data[$key] = ! empty($data[$key]) ? (int) $data[$key] : null;
                $card->update($data);
                if ($request->hasFile('card_image.'.$id)) { $card->image_path = \App\Support\PublicUpload::store($request->file('card_image.'.$id), 'asset/front-end/img/feature-cards/', 'feature-card-'.(int) $id.'-', ['jpg','jpeg','png','webp']); $card->save(); if ($oldImage) app(\App\Services\MediaLifecycleService::class)->deleteIfUnreferenced($oldImage, ['homepage_feature_card'=>[$card->id]], 'Feature card image replaced.'); }
                elseif (($detachImage || $deleteImage) && $oldImage) { $media = app(\App\Services\MediaLifecycleService::class); $shared = $media->isReferenced($oldImage, ['homepage_feature_card'=>[$card->id]]); $card->image_path = null; $card->save(); if ($deleteImage && $shared) $sharedImageKept = true; if ($deleteImage && ! $shared) $media->deleteIfUnreferenced($oldImage, ['homepage_feature_card'=>[$card->id]], 'Feature card image permanently deleted.'); }
            }
            $new = (array) $request->input('new_card', []);
            if (trim((string) ($new['name'] ?? '')) !== '') { $new['sort_order'] = ((int) \App\HomepageFeatureCard::max('sort_order')) + 10; $new['card_type'] = $new['card_type'] ?? 'TEXT_CTA'; $new['link_type'] = $new['link_type'] ?? 'NONE'; $new['color_style'] = $new['color_style'] ?? 'BLUE'; $new['clickable_area'] = $new['clickable_area'] ?? 'BUTTON_ONLY'; $new['image_fit'] = 'CONTAIN'; $new['image_position'] = 'CENTER'; $new['text_position'] = 'CENTER_LEFT'; $new['overlay_style'] = 'NONE'; $new['is_active'] = true; $new['created_by'] = session('admin_id'); $created = \App\HomepageFeatureCard::create($new); if ($request->hasFile('new_card_image')) { $created->image_path = \App\Support\PublicUpload::store($request->file('new_card_image'), 'asset/front-end/img/feature-cards/', 'feature-card-new-', ['jpg','jpeg','png','webp']); $created->save(); } }
            $config = $featureCards->normalizeConfig((array) $request->input('config', [])); DB::table('site_settings')->updateOrInsert(['setting_key'=>HomepageFeatureCardService::CONFIG_KEY], ['setting_value'=>json_encode($config), 'created_at'=>now(), 'updated_at'=>now()]);
        });
        $featureCards->clear();
        $message = $sharedImageKept
            ? 'Homepage feature cards saved. The shared image was removed from this card but kept because another section still uses it.'
            : 'Homepage feature cards saved.';
        return redirect()->route('admin.homepage-feature-cards')->with('message', $message);
    }

    public function bannerManagement()
    {
        $this->authCheck();
        $banners = Banner::with(['product', 'category'])->orderBy('display_order')->orderByDesc('id')->get();
        $bannerProducts = Product::where('publication_status', 1)->orderBy('product_name')->get(['id','product_id','sku','product_name','product_image','regular_price','offer_price','publication_status']);
        $bannerCategories = Category::where('publication_status', 1)->orderBy('category_name')->get(['category_id','category_name']);
        return view('admin.admin-pages.banner-management', compact('banners', 'bannerProducts', 'bannerCategories'));
    }

    public function updateSiteSettings(Request $request)
    {
        $this->authCheck();
        $resetRequested = $request->boolean('reset_to_default');
        $settingKeys = $this->siteCustomizationSettingKeys();
        $assetKeys = $this->siteCustomizationAssetKeys();
        $themeService = app(StorefrontThemeService::class);
        $currentSettings = DB::table('site_settings')->pluck('setting_value', 'setting_key');
        $previousDevelopmentMode = $currentSettings->get('development_mode_enabled');
        $themeRules = $themeService->validationRules();
        $themeMessages = $themeService->validationMessages();
        foreach (['logo', 'logo_tablet', 'logo_mobile', 'favicon', 'seo_image'] as $uploadKey) {
            $upload = $_FILES[$uploadKey] ?? null;
            $uploadError = is_array($upload) ? (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
            if ($uploadError === UPLOAD_ERR_NO_FILE) continue;
            if ($uploadError !== UPLOAD_ERR_OK) {
                $limit = ini_get('upload_max_filesize') ?: 'unknown';
                $postLimit = ini_get('post_max_size') ?: 'unknown';
                $message = match ($uploadError) {
                    UPLOAD_ERR_INI_SIZE => "PHP rejected this file because upload_max_filesize is too small (currently {$limit}). Increase it to at least 10M.",
                    UPLOAD_ERR_FORM_SIZE => 'The submitted form is larger than the server allows. Increase post_max_size.',
                    UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again or check the server connection timeout.',
                    UPLOAD_ERR_NO_TMP_DIR => 'PHP has no temporary upload directory. Check upload_tmp_dir and its permissions.',
                    UPLOAD_ERR_CANT_WRITE => 'PHP could not write the uploaded file. Check the temporary directory permissions.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload. Check the server PHP upload/security configuration.',
                    default => "The server rejected the upload. Check upload_max_filesize ({$limit}) and post_max_size ({$postLimit}).",
                };
                return Redirect::to('/site-customization#identity')->withInput()->withErrors([$uploadKey => $message]);
            }
        }
        $this->validate($request, array_merge([
            'site_name' => 'required|string|max:120',
            'site_name_font_size' => 'nullable|integer|min:14|max:32',
            'site_tagline' => 'nullable|string|max:180',
            'site_tagline_font_size' => 'nullable|integer|min:8|max:24',
            'notice_text' => 'nullable|string|max:300',
            'phone' => ['nullable','string','max:40','regex:/^[0-9+()\-\s.]+$/'],
            'support_phone' => ['nullable','string','max:40','regex:/^[0-9+()\-\s.]+$/'],
            'whatsapp_number' => ['nullable','string','max:40','regex:/^[0-9+()\-\s.]+$/'],
            'support_email' => 'nullable|email|max:150',
            'shop_address' => 'nullable|string|max:500',
            'business_hours' => 'nullable|string|max:180',
            'logo' => [
                'nullable','file','image','mimes:png,jpg,jpeg,webp','max:5120',
                function ($attribute, $file, $fail) {
                    if (!$file || !$file->isValid()) return;
                    $dimensions = @getimagesize($file->getPathname());
                    if ($dimensions && ($dimensions[0] > 6000 || $dimensions[1] > 6000)) {
                        $fail('The logo source image may not be larger than 6000 × 6000 pixels.');
                    }
                },
            ],
            'favicon' => [
                'nullable','file','mimes:ico,png,jpg,jpeg,webp','max:2048',
                function ($attribute, $file, $fail) {
                    if (!$file || !$file->isValid() || strtolower($file->getClientOriginalExtension()) === 'ico') return;
                    $dimensions = @getimagesize($file->getPathname());
                    if ($dimensions && ($dimensions[0] > 4096 || $dimensions[1] > 4096)) {
                        $fail('The browser icon source image may not be larger than 4096 × 4096 pixels.');
                    }
                },
            ],
            'logo_resize_enabled' => 'nullable|boolean',
            'logo_resize_width' => 'nullable|required_if:logo_resize_enabled,1|integer|min:120|max:2400',
            'logo_resize_height' => 'nullable|required_if:logo_resize_enabled,1|integer|min:40|max:1200',
            'favicon_resize_enabled' => 'nullable|boolean',
            'favicon_resize_width' => 'nullable|required_if:favicon_resize_enabled,1|integer|min:16|max:1024',
            'favicon_resize_height' => 'nullable|required_if:favicon_resize_enabled,1|integer|min:16|max:1024',
            'remove_logo' => [
                'nullable','boolean',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->boolean('remove_logo') && $request->hasFile('logo')) {
                        $fail('Choose either a replacement logo or remove the current logo, not both.');
                    }
                },
            ],
            'remove_favicon' => [
                'nullable','boolean',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->boolean('remove_favicon') && $request->hasFile('favicon')) {
                        $fail('Choose either a replacement browser icon or remove the current icon, not both.');
                    }
                },
            ],
            'logo_tablet' => 'nullable|file|image|mimes:png,jpg,jpeg,webp|max:5120',
            'logo_mobile' => 'nullable|file|image|mimes:png,jpg,jpeg,webp|max:5120',
            'remove_seo_image' => [
                'nullable','boolean',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->boolean('remove_seo_image') && $request->hasFile('seo_image')) {
                        $fail('Choose either a replacement social sharing image or remove the current image, not both.');
                    }
                },
            ],
            'reset_to_default' => 'nullable|boolean',
            'simulate_db_failure' => 'nullable|boolean',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'google_analytics_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/i', 'max:30'],
            'google_site_verification' => 'nullable|string|max:255',
            'default_meta_title' => 'nullable|string|max:70',
            'default_meta_description' => 'nullable|string|max:320',
            'meta_keywords' => 'nullable|string|max:500',
            'robots_directive' => ['nullable', \Illuminate\Validation\Rule::in(['index,follow','index,nofollow','noindex,follow','noindex,nofollow'])],
            'seo_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'footer_description' => 'nullable|string|max:500',
            'copyright_text' => 'nullable|string|max:255',
            'footer_credit_text' => 'nullable|string|max:120',
            'footer_credit_url' => ['nullable', 'regex:/^(https?:\/\/|\/|#)/i', 'max:255'],
            'hero_side_title' => 'nullable|string|max:120',
            'hero_side_text' => 'nullable|string|max:240',
            'hero_side_button_text' => 'nullable|string|max:80',
            'hero_side_url' => ['nullable','regex:/^(https?:\/\/|\/|#)/i','max:255'],
            'hero_side_enabled' => 'nullable|boolean',
            'hero_side_style' => ['nullable', \Illuminate\Validation\Rule::in(['BLUE','ORANGE','LIGHT','DARK'])],
            'hero_side_2_kicker' => 'nullable|string|max:120',
            'hero_side_2_title' => 'nullable|string|max:240',
            'hero_side_2_button_text' => 'nullable|string|max:80',
            'hero_side_2_url' => ['nullable','regex:/^(https?:\/\/|\/|#)/i','max:255'],
            'hero_side_2_enabled' => 'nullable|boolean',
            'hero_side_2_style' => ['nullable', \Illuminate\Validation\Rule::in(['BLUE','ORANGE','LIGHT','DARK'])],
            'development_mode_enabled' => 'required|boolean',
            'development_mode_message_type' => ['required', \Illuminate\Validation\Rule::in(['development','maintenance','coming_soon','system_upgrade','emergency','custom'])],
            'development_mode_title' => 'required|string|max:150',
            'development_mode_message' => 'required|string|max:2000',
            'development_mode_additional_message' => 'nullable|string|max:2000',
            'development_mode_availability_text' => 'nullable|string|max:255',
            'development_mode_show_admin_login' => 'required|boolean',
            'development_mode_login_button_text' => 'nullable|string|max:100',
            'homepage_featured_products_limit' => 'required|integer|min:1|max:50',
            'homepage_featured_products_per_row' => 'required|integer|min:2|max:6',
            'homepage_new_arrivals_limit' => 'required|integer|min:1|max:50',
            'homepage_new_arrivals_per_row' => 'required|integer|min:2|max:6',
        ], $themeRules, $this->siteCustomizationPageValidationRules()), array_merge([
            'logo.uploaded' => 'The logo could not be uploaded. Choose a PNG, JPG, or WebP image no larger than 5 MB.',
            'logo.image' => 'The logo must be a valid PNG, JPG, or WebP image.',
            'logo.mimes' => 'The logo must be a PNG, JPG, or WebP image.',
            'logo.max' => 'The logo may not be larger than 5 MB.',
            'logo_tablet.uploaded' => 'The tablet logo could not be uploaded. Choose a PNG, JPG, or WebP image no larger than 5 MB.',
            'logo_tablet.image' => 'The tablet logo must be a valid PNG, JPG, or WebP image.',
            'logo_tablet.mimes' => 'The tablet logo must be a PNG, JPG, or WebP image.',
            'logo_tablet.max' => 'The tablet logo may not be larger than 5 MB.',
            'logo_mobile.uploaded' => 'The mobile logo could not be uploaded. Choose a PNG, JPG, or WebP image no larger than 5 MB.',
            'logo_mobile.image' => 'The mobile logo must be a valid PNG, JPG, or WebP image.',
            'logo_mobile.mimes' => 'The mobile logo must be a PNG, JPG, or WebP image.',
            'logo_mobile.max' => 'The mobile logo may not be larger than 5 MB.',
            'favicon.uploaded' => 'The browser icon could not be uploaded. Choose an ICO, PNG, JPG, or WebP file no larger than 2 MB.',
            'favicon.mimes' => 'The browser icon must be an ICO, PNG, JPG, or WebP file.',
            'favicon.max' => 'The browser icon may not be larger than 2 MB.',
            'logo_resize_width.required_if' => 'Enter the logo output width when automatic resizing is enabled.',
            'logo_resize_width.min' => 'The logo output width must be at least 120 pixels.',
            'logo_resize_width.max' => 'The logo output width may not exceed 2400 pixels.',
            'logo_resize_height.required_if' => 'Enter the logo output height when automatic resizing is enabled.',
            'logo_resize_height.min' => 'The logo output height must be at least 40 pixels.',
            'logo_resize_height.max' => 'The logo output height may not exceed 1200 pixels.',
            'favicon_resize_width.required_if' => 'Enter the browser icon output width when automatic resizing is enabled.',
            'favicon_resize_width.min' => 'The browser icon output width must be at least 16 pixels.',
            'favicon_resize_width.max' => 'The browser icon output width may not exceed 1024 pixels.',
            'favicon_resize_height.required_if' => 'Enter the browser icon output height when automatic resizing is enabled.',
            'favicon_resize_height.min' => 'The browser icon output height must be at least 16 pixels.',
            'favicon_resize_height.max' => 'The browser icon output height may not exceed 1024 pixels.',
        ], $themeMessages));
        $fileCleanupPaths = [];
        if ($resetRequested) {
            $fileCleanupPaths = $currentSettings->only(array_values($assetKeys))->filter()->values()->all();
        }
        $booleanKeys = ['logo_resize_enabled', 'favicon_resize_enabled', 'development_mode_enabled', 'development_mode_show_admin_login', 'startech_source_import_enabled', 'hero_side_enabled', 'hero_side_2_enabled'];
        $themePayload = $themeService->normalize((array) $request->input('storefront_theme', []));
        $storedAssets = [];
        $assetRemovals = [];

        if (!$resetRequested) {
            foreach ($assetKeys as $input => $settingKey) {
                if ($request->boolean('remove_'.$input)) {
                    $assetRemovals[$input] = $settingKey;
                    $fileCleanupPaths[] = $currentSettings->get($settingKey);
                }
            }

            foreach ($assetKeys as $input => $settingKey) {
                if (!$request->hasFile($input)) {
                    continue;
                }

                try {
                    $resizeOptions = $this->brandResizeOptions($request, $input);
                    $storedAssets[$input] = $this->storeBrandAsset($request->file($input), $input, $resizeOptions);
                    $fileCleanupPaths[] = $currentSettings->get($settingKey);
                } catch (\Throwable $exception) {
                    foreach ($storedAssets as $storedPath) {
                        $this->removeBrandAsset($storedPath);
                    }
                    report($exception);
                    return Redirect::to('/site-customization#identity')->withInput()->withErrors([
                        $input => 'The file passed validation, but the server could not save it. Please try again.',
                    ]);
                }
            }
        }

        try {
            DB::transaction(function () use ($request, $resetRequested, $settingKeys, $assetKeys, $storedAssets, $assetRemovals, $booleanKeys, $themePayload) {
                if (app()->environment('testing') && $request->boolean('simulate_db_failure')) {
                    throw new \RuntimeException('Simulated site settings database failure.');
                }

                if ($resetRequested) {
                    DB::table('site_settings')->whereIn('setting_key', array_merge($settingKeys, array_values($assetKeys)))->delete();
                    return;
                }

                foreach ($settingKeys as $key) {
                    $value = null;
                    if ($key === 'storefront_theme') {
                        $value = json_encode($themePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } elseif (in_array($key, $booleanKeys, true)) {
                        $value = $request->has($key) ? ($request->boolean($key) ? '1' : '0') : null;
                    } elseif ($request->filled($key)) {
                        $value = (string) $request->input($key);
                        if (in_array($key, $this->siteCustomizationRichTextKeys(), true)) {
                            $value = $this->sanitizeSiteCustomizationRichText($value);
                        } else {
                            $value = preg_replace('/<(script|iframe|object|embed|style)\b[^>]*>.*?<\/\1>/is', '', $value);
                            $value = trim(strip_tags($value));
                        }
                    }

                    if ($value === null || $value === '') {
                        DB::table('site_settings')->where('setting_key', $key)->delete();
                        continue;
                    }

                    DB::table('site_settings')->updateOrInsert(['setting_key' => $key], [
                        'setting_value' => $value,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }

                foreach ($storedAssets as $input => $storedPath) {
                    DB::table('site_settings')->updateOrInsert(['setting_key' => $assetKeys[$input]], [
                        'setting_value' => $storedPath,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }

                foreach ($assetRemovals as $settingKey) {
                    DB::table('site_settings')->where('setting_key', $settingKey)->delete();
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedAssets as $storedPath) $this->removeBrandAsset($storedPath);
            report($exception);
            return Redirect::to('/site-customization#identity')->withInput()->withErrors([
                'settings' => 'Website settings could not be saved. No changes were applied. Please try again.',
            ]);
        }
        foreach (array_unique(array_filter($fileCleanupPaths)) as $oldPath) {
            $this->removeBrandAssetIfUnused($oldPath);
        }
        Cache::forget('site-settings');
        Cache::forget('site-top-bar');
        Cache::forget('xml-sitemap');
        $freshSettings=DB::table('site_settings')->pluck('setting_value','setting_key');
        $freshBrandName=$freshSettings->get('site_name') ?: config('app.default_name', 'Ecommerce');
        $freshNameFontSize=(int)($freshSettings->get('site_name_font_size') ?: 23);
        $freshNameFontSize=max(14,min(32,$freshNameFontSize));
        $freshTaglineFontSize=(int)($freshSettings->get('site_tagline_font_size') ?: 12);
        $freshTaglineFontSize=max(8,min(24,$freshTaglineFontSize));
        $freshLogoResizeWidth = (int)($freshSettings->get('logo_resize_width') ?: 600);
        $freshLogoResizeWidth = max(120, min(2400, $freshLogoResizeWidth));
        $freshLogoResizeHeight = (int)($freshSettings->get('logo_resize_height') ?: 200);
        $freshLogoResizeHeight = max(40, min(1200, $freshLogoResizeHeight));
        $freshLogoDisplayWidth = max(120, min(240, (int) round($freshLogoResizeWidth * 220 / 600)));
        $freshLogoDisplayHeight = max(40, min(82, (int) round($freshLogoResizeHeight * 73 / 200)));
        $freshLogoMobileWidth = max(90, min(150, (int) round($freshLogoResizeWidth * 150 / 600)));
        $freshLogoMobileHeight = max(30, min(54, (int) round($freshLogoResizeHeight * 50 / 200)));
        $freshStorefrontTheme = $themeService->fromSettings($freshSettings);
        $freshStorefrontThemeCss = $themeService->cssVariables($freshStorefrontTheme);
        $freshBrandLogoHeader = $themeService->resolvedLogoPath($freshStorefrontTheme, $freshSettings->get('site_logo') ?: null);
        \View::share('siteSettings',$freshSettings);
        \View::share('brandName',$freshBrandName);
        \View::share('brandLogo',$freshSettings->get('site_logo') ?: null);
        \View::share('brandLogoTablet',$freshSettings->get('site_logo_tablet') ?: null);
        \View::share('brandLogoMobile',$freshSettings->get('site_logo_mobile') ?: null);
        \View::share('brandLogoHeader',$freshBrandLogoHeader ?: ($freshSettings->get('site_logo') ?: null));
        \View::share('brandFavicon',$freshSettings->get('favicon') ?: null);
        \View::share('hasCustomBrandLogo',(bool)$freshSettings->get('site_logo'));
        \View::share('hasCustomBrandFavicon',(bool)$freshSettings->get('favicon'));
        \View::share('brandNameFontSize',$freshNameFontSize);
        \View::share('brandTaglineFontSize',$freshTaglineFontSize);
        \View::share('brandLogoDisplayWidth',$freshLogoDisplayWidth);
        \View::share('brandLogoDisplayHeight',$freshLogoDisplayHeight);
        \View::share('brandLogoMobileWidth',$freshLogoMobileWidth);
        \View::share('brandLogoMobileHeight',$freshLogoMobileHeight);
        \View::share('storefrontTheme',$freshStorefrontTheme);
        \View::share('storefrontThemeCss',$freshStorefrontThemeCss);
        config(['app.name'=>$freshBrandName]);
        $newDevelopmentMode = (string)$request->input('development_mode_enabled') === '1';
        $previouslyEnabled = in_array($previousDevelopmentMode, [true, 1, '1', 'true', 'on'], true);
        if ($resetRequested && \Schema::hasTable('admin_activity_logs')) {
            DB::table('admin_activity_logs')->insert([
                'admin_id' => session('admin_id'),
                'admin_name' => session('admin_name'),
                'action' => 'WEBSITE_SETTINGS_RESET',
                'method' => 'POST',
                'path' => '/site-settings',
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), config('app.key')),
                'details' => json_encode([
                    'reset_to_default' => true,
                    'removed_files' => array_values(array_map(function ($path) {
                        return basename($path);
                    }, array_unique(array_filter($fileCleanupPaths)))),
                ]),
                'created_at' => now(),
            ]);
        }
        if ($newDevelopmentMode !== $previouslyEnabled && \Schema::hasTable('admin_activity_logs')) {
            DB::table('admin_activity_logs')->insert([
                'admin_id' => session('admin_id'),
                'admin_name' => session('admin_name'),
                'action' => 'Development Mode Updated',
                'method' => 'POST',
                'path' => '/site-settings',
                'ip_hash' => hash_hmac('sha256', (string)$request->ip(), config('app.key')),
                'details' => json_encode(['previous_status' => $previouslyEnabled, 'new_status' => $newDevelopmentMode, 'message_type' => $request->development_mode_message_type]),
                'created_at' => now(),
            ]);
        }
        if ($resetRequested) {
            $message = 'Website settings were reset successfully.';
        } elseif ($newDevelopmentMode && !$previouslyEnabled) {
            $message = 'Development Mode has been enabled. Public visitors will now see the configured Development Mode message.';
        } elseif (!$newDevelopmentMode && $previouslyEnabled) {
            $message = 'Development Mode has been disabled. The public website is now available.';
        } elseif ($assetRemovals) {
            $removed = [];
            if (isset($assetRemovals['logo'])) $removed[] = 'logo';
            if (isset($assetRemovals['favicon'])) $removed[] = 'browser icon';
            if (isset($assetRemovals['seo_image'])) $removed[] = 'social sharing image';
            $message = ucfirst(implode(' and ', $removed)).' removed from the site and upload storage.';
        } else {
            $message = 'Site settings updated.';
        }
        $destination = $newDevelopmentMode !== $previouslyEnabled
            ? '/site-customization#development-mode'
            : '/site-customization';
        return Redirect::to($destination)->with('message', $message);
    }

    public function saveBanner(Request $request)
    {
        $this->authCheck();
        $data = $this->validatedBannerData($request);
        try {
            $desktopImage = $request->hasFile('desktop_image') ? $this->storeBannerImage($request->file('desktop_image'), 'desktop') : null;
            $mobileImage = $request->hasFile('mobile_image') ? $this->storeBannerImage($request->file('mobile_image'), 'mobile') : null;
        } catch (\Throwable $exception) {
            if (isset($desktopImage)) $this->removeBannerFile($desktopImage);
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'desktop_image' => 'The banner image could not be saved. Please try again or check upload storage permissions.',
            ]);
        }
        $data['image_path'] = $desktopImage;
        $data['mobile_image'] = $mobileImage;
        try { Banner::create($data); }
        catch (\Throwable $exception) { $this->removeBannerFile($desktopImage); $this->removeBannerFile($mobileImage); throw $exception; }
        return Redirect::to('/banner-management')->with('message', 'Banner added.');
    }

    public function updateBanner(Request $request, $id)
    {
        $this->authCheck();
        $banner = Banner::findOrFail($id);
        $data = $this->validatedBannerData($request, $banner);
        $oldDesktop = $banner->image_path;
        $oldMobile = $banner->mobile_image;
        try {
            $newDesktop = $request->hasFile('desktop_image') ? $this->storeBannerImage($request->file('desktop_image'), 'desktop') : null;
            $newMobile = $request->hasFile('mobile_image') ? $this->storeBannerImage($request->file('mobile_image'), 'mobile') : null;
        } catch (\Throwable $exception) {
            if (isset($newDesktop)) $this->removeBannerFile($newDesktop);
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'desktop_image' => 'The banner image could not be saved. Please try again or check upload storage permissions.',
            ]);
        }
        if ($newDesktop) $data['image_path'] = $newDesktop;
        if ($newMobile) $data['mobile_image'] = $newMobile;
        if ($request->has('remove_mobile_image')) $data['mobile_image'] = null;
        try { $banner->update($data); }
        catch (\Throwable $exception) { $this->removeBannerFile($newDesktop); $this->removeBannerFile($newMobile); throw $exception; }
        if ($newDesktop) $this->removeBannerFileIfUnused($oldDesktop, $banner->id);
        if ($newMobile || $request->has('remove_mobile_image')) $this->removeBannerFileIfUnused($oldMobile, $banner->id);
        return Redirect::to('/banner-management')->with('message', 'Banner updated.');
    }

    public function bannerProductPreview($id)
    {
        $this->authCheck();
        $product = Product::where('publication_status', 1)->findOrFail($id);
        return response()->json($this->productBannerData($product));
    }

    public function toggleBanner($id)
    {
        $this->authCheck();
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => !$banner->is_active]);
        return Redirect::to('/banner-management')->with('message', $banner->is_active ? 'Banner is now visible.' : 'Banner is now hidden.');
    }

    public function deleteBanner($id)
    {
        $this->authCheck();
        if (! Banner::find($id)) {
            return Redirect::to('/banner-management')->with('exception', 'Banner not found.');
        }

        app(RecycleBinService::class)->softDelete('banner', (int) $id, session('admin_id'), 'Banner moved to Recycle Bin.');
        return Redirect::to('/banner-management')->with('message', 'Banner deleted.');
    }

    private function validatedBannerData(Request $request, Banner $existing = null)
    {
        $types = ['custom','product','category','campaign','information'];
        $positions = ['center','top','bottom','left','right'];
        $validator = Validator::make($request->all(), [
            'banner_type' => ['required', Rule::in($types)],
            'product_id' => ['nullable','integer',Rule::exists('product','id')->where(function ($query) { $query->where('publication_status', 1); })],
            'category_id' => ['nullable','integer',Rule::exists('category','category_id')->where(function ($query) { $query->where('publication_status', 1); })],
            'link_url' => ['nullable','string','max:255'],
            'title' => ['nullable','string','max:255'],
            'subtitle' => ['nullable','string','max:255'],
            'button_text' => ['nullable','string','max:100'],
            'desktop_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'mobile_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'image_position' => ['required', Rule::in($positions)],
            'display_order' => ['required','integer','min:0'],
            'starts_at' => ['nullable','date'],
            'expires_at' => ['nullable','date','after_or_equal:starts_at'],
        ]);
        $validator->after(function ($validator) use ($request, $existing) {
            $type = $request->input('banner_type');
            if ($type === 'product' && !$request->filled('product_id')) $validator->errors()->add('product_id', 'Choose a published product.');
            if ($type === 'category' && !$request->filled('category_id')) $validator->errors()->add('category_id', 'Choose a published category.');
            if ($type === 'custom' && !$request->filled('link_url')) $validator->errors()->add('link_url', 'Enter an internal path or HTTPS destination.');
            if (in_array($type, ['custom','campaign'], true) && $request->filled('link_url') && !$this->isSafeBannerUrl($request->input('link_url'))) $validator->errors()->add('link_url', 'Use an internal path beginning with / or a valid HTTPS URL.');
            $useProduct = $request->boolean('use_product_image');
            if ($useProduct && !$request->filled('product_id')) $validator->errors()->add('use_product_image', 'Choose a product before using its image.');
            $hasDesktop = $request->hasFile('desktop_image') || ($existing && $existing->image_path);
            if (!$hasDesktop) $validator->errors()->add('desktop_image', 'Upload a dedicated desktop banner image. It remains the safe fallback in product-image mode.');
            if ($useProduct && $request->filled('product_id')) {
                $product = Product::where('publication_status', 1)->find($request->product_id);
                if ((!$product || !$product->product_image) && !$hasDesktop) $validator->errors()->add('desktop_image', 'This product has no image. Upload a desktop banner fallback.');
            }
        });
        $validator->validate();

        $type = $request->banner_type;
        $product = $type === 'product' || $request->boolean('use_product_image') ? Product::where('publication_status', 1)->find($request->product_id) : null;
        $title = $request->filled('title') ? trim($request->title) : ($product ? $product->product_name : null);
        $subtitle = $request->filled('subtitle') ? trim($request->subtitle) : ($product ? $this->productBannerData($product)['subtitle'] : null);
        return [
            'banner_type' => $type,
            'product_id' => $product ? $product->id : null,
            'category_id' => $type === 'category' ? (int)$request->category_id : null,
            'title' => $title,
            'subtitle' => $subtitle,
            'button_text' => $request->filled('button_text') ? trim($request->button_text) : ($type === 'product' ? 'Shop Now' : null),
            'link_url' => in_array($type, ['custom','campaign'], true) && $request->filled('link_url') ? trim($request->link_url) : null,
            'use_product_image' => $request->boolean('use_product_image'),
            'image_position' => $request->image_position,
            'show_overlay' => $request->boolean('show_overlay'),
            'display_order' => (int)$request->display_order,
            'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
            'expires_at' => $request->filled('expires_at') ? $request->expires_at : null,
            'open_in_new_tab' => $request->boolean('open_in_new_tab'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function productBannerData(Product $product)
    {
        $discount = $product->discount_percent;
        $price = number_format($product->selling_price, 0);
        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'sku' => $product->sku ?: $product->product_id,
            'regular_price' => (float)$product->regular_price,
            'selling_price' => (float)$product->selling_price,
            'discount_percent' => $discount,
            'subtitle' => $discount ? 'Save '.$discount.'% — Now ৳'.$price : 'Now ৳'.$price,
            'button_text' => 'Shop Now',
            'image' => $product->image_url,
            'url' => route('store.product.show', $product->id),
            'status' => $product->publication_status ? 'Published' : 'Hidden',
        ];
    }

    private function siteCustomizationDefaults(?string $brandName = null)
    {
        $brandName = trim((string) ($brandName ?: config('app.default_name', 'Ecommerce')));

        return array_merge([
            'site_name' => config('app.default_name', 'Ecommerce'),
            'site_name_font_size' => 23,
            'site_tagline' => '',
            'site_tagline_font_size' => 12,
            'logo_resize_enabled' => 1,
            'logo_resize_width' => 600,
            'logo_resize_height' => 200,
            'favicon_resize_enabled' => 1,
            'favicon_resize_width' => 512,
            'favicon_resize_height' => 512,
            'robots_directive' => 'index,follow',
            'development_mode_enabled' => 0,
            'development_mode_message_type' => 'maintenance',
            'development_mode_title' => 'Website Under Development',
            'development_mode_message' => 'We are currently improving our website. Please check back again soon.',
            'development_mode_additional_message' => '',
            'development_mode_availability_text' => '',
            'development_mode_show_admin_login' => 1,
            'development_mode_login_button_text' => 'Admin Login',
            'startech_source_import_enabled' => 1,
            'copyright_text' => '',
            'footer_credit_text' => 'Lucent Tech BD',
            'footer_credit_url' => '',
            'hero_side_enabled' => 1,
            'hero_side_button_text' => 'Get a quotation',
            'hero_side_url' => '/contact-us',
            'hero_side_style' => 'BLUE',
            'hero_side_2_kicker' => 'Fast nationwide delivery',
            'hero_side_2_title' => 'Technology at your doorstep.',
            'hero_side_2_button_text' => 'Shop products',
            'hero_side_2_url' => '#products',
            'hero_side_2_enabled' => 1,
            'hero_side_2_style' => 'ORANGE',
            'homepage_featured_products_limit' => 20,
            'homepage_featured_products_per_row' => 5,
            'homepage_new_arrivals_limit' => 20,
            'homepage_new_arrivals_per_row' => 5,
        ], $this->siteCustomizationPageDefaults($brandName));
    }

    private function siteCustomizationPageDefaults(string $brandName): array
    {
        return [
            'about_us_hero_kicker' => 'About our store',
            'about_us_hero_title' => 'A flexible ecommerce experience built around customers.',
            'about_us_hero_text' => $brandName.' presents products clearly, accepts orders securely, and provides dependable customer service.',
            'about_us_story_kicker' => 'Our approach',
            'about_us_story_title' => 'Clear information and practical service',
            'about_us_story_text_1' => 'This storefront can be customized for different industries, product catalogs, delivery areas, and business models.',
            'about_us_story_text_2' => 'Store owners can configure branding, contact details, policies, products, inventory, payments, and customer support from the administration dashboard.',
            'about_us_highlight_1_title' => 'Flexible',
            'about_us_highlight_1_text' => 'Configurable catalog and branding',
            'about_us_highlight_2_title' => 'Responsive',
            'about_us_highlight_2_text' => 'Shopping across devices',
            'about_us_highlight_3_title' => 'Customer first',
            'about_us_highlight_3_text' => 'Support before and after purchase',
            'about_us_mission_title' => 'Our Mission',
            'about_us_mission_text' => 'Make dependable technology accessible through honest advice, suitable products, and responsive service.',
            'about_us_vision_title' => 'Our Vision',
            'about_us_vision_text' => 'Become a trusted technology partner for households, professionals, and growing organizations.',
            'about_us_promise_title' => 'Our Promise',
            'about_us_promise_text' => 'Put customer needs first and recommend products with value, quality, and long-term usability in mind.',
            'about_us_capabilities_kicker' => 'What we provide',
            'about_us_capabilities_title' => 'Products and expertise for every setup',
            'about_us_capabilities_text' => 'Our catalog covers desktops, laptops, monitors, networking products, printers, office equipment, cameras, security systems, accessories, and PC components from established brands.',
            'about_us_capabilities_items' => "Personal and custom PC solutions\nNetworking and office hardware\nCorporate procurement support\nNationwide product delivery",
            'about_us_cta_title' => 'Need help choosing the right product?',
            'about_us_cta_text' => 'Tell our team what you need and we will help you compare suitable options.',
            'about_us_cta_button_text' => 'Talk to our team',
            'terms_hero_kicker' => 'Customer information',
            'terms_hero_title' => 'Warranty, Service & Terms',
            'terms_hero_text' => 'Please review these conditions before purchasing, receiving, or submitting a product for service.',
            'terms_nav_coverage' => 'Warranty coverage',
            'terms_nav_exclusions' => 'Exclusions',
            'terms_nav_service' => 'Service process',
            'terms_nav_delivery' => 'Delivery & inspection',
            'terms_coverage_title' => 'Warranty Coverage',
            'terms_coverage_text' => 'Product warranty follows the applicable manufacturer or distributor policy and begins from the purchase date shown on the invoice. Keep the invoice and warranty documents for any service request.',
            'terms_exclusions_title' => 'Warranty Exclusions',
            'terms_exclusions_items' => "Unauthorized opening, repair, modification, or installation.\nAccident, fire, lightning, voltage fluctuation, short circuit, water, or physical damage.\nBroken panels, connectors, casing, or other visible physical damage.\nDamage caused by misuse, improper installation, or unsuitable operating conditions.",
            'terms_service_title' => 'Warranty Service Process',
            'terms_service_items' => "Contact our team with the invoice and product details.\nBring the product to our showroom or send it through a reliable courier.\nOur team will inspect it and coordinate eligible service with the supplier.\nCustomers are responsible for courier and transport costs in both directions.",
            'terms_delivery_title' => 'Delivery & Product Inspection',
            'terms_delivery_text' => 'Inspect the model, configuration, physical condition, and included accessories when receiving the product. Report any delivery-related issue immediately. After acceptance, configuration concerns remain subject to the invoice and warranty policy.',
            'terms_help_title' => 'Need clarification?',
            'terms_help_text' => 'Call +88 01612-717349 before submitting a product for service.',
            'terms_help_button_text' => 'Contact support',
        ];
    }

    private function siteCustomizationRichTextKeys(): array
    {
        return [
            'about_us_hero_text',
            'about_us_story_text_1',
            'about_us_story_text_2',
            'about_us_mission_text',
            'about_us_vision_text',
            'about_us_promise_text',
            'about_us_capabilities_text',
            'about_us_cta_text',
            'terms_hero_text',
            'terms_coverage_text',
            'terms_delivery_text',
            'terms_help_text',
        ];
    }

    private function sanitizeSiteCustomizationRichText(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'a', 'div', 'span', 'hr', 'sub', 'sup', 'code', 'pre'];
        $allowedTagMarkup = '<'.implode('><', $allowedTags).'>';

        if (! class_exists(\DOMDocument::class)) {
            return trim(strip_tags($value, $allowedTagMarkup));
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8"><div>'.$value.'</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return trim(strip_tags($value, $allowedTagMarkup));
        }

        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root) {
            return trim(strip_tags($value, $allowedTagMarkup));
        }

        $this->sanitizeRichTextNode($root, $allowedTags);

        $clean = '';
        for ($i = 0; $i < $root->childNodes->length; $i++) {
            $child = $root->childNodes->item($i);
            if ($child) {
                $clean .= $document->saveHTML($child);
            }
        }

        return trim($clean);
    }

    private function sanitizeRichTextNode(\DOMNode $node, array $allowedTags): void
    {
        for ($index = $node->childNodes->length - 1; $index >= 0; $index--) {
            $child = $node->childNodes->item($index);
            if (! $child) {
                continue;
            }

            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            if (! in_array($tag, $allowedTags, true)) {
                $grandChildren = [];
                while ($child->firstChild) {
                    $grandChildren[] = $child->firstChild;
                    $child->removeChild($child->firstChild);
                }

                foreach ($grandChildren as $grandChild) {
                    $node->insertBefore($grandChild, $child);
                    if ($grandChild->nodeType === XML_ELEMENT_NODE) {
                        $this->sanitizeRichTextNode($grandChild, $allowedTags);
                    }
                }

                $node->removeChild($child);
                continue;
            }

            $this->sanitizeRichTextAttributes($child, $tag);
            $this->sanitizeRichTextNode($child, $allowedTags);
        }
    }

    private function sanitizeRichTextAttributes(\DOMElement $element, string $tag): void
    {
        $allowedAttributes = [];

        if ($tag === 'a') {
            $allowedAttributes = ['href', 'title', 'target', 'rel'];
        } elseif (in_array($tag, ['div', 'span', 'p', 'blockquote', 'li'], true)) {
            $allowedAttributes = ['align', 'style'];
        } elseif (in_array($tag, ['hr', 'pre', 'code', 'sub', 'sup'], true)) {
            $allowedAttributes = [];
        }

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);
            if (! $attribute) {
                continue;
            }

            $name = strtolower($attribute->nodeName);
            if (! in_array($name, $allowedAttributes, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            $value = $this->sanitizeRichTextAttributeValue($tag, $name, $attribute->nodeValue);
            if ($value === null || $value === '') {
                $element->removeAttributeNode($attribute);
                continue;
            }

            $element->setAttribute($attribute->nodeName, $value);
        }

        if ($tag === 'a' && strtolower($element->getAttribute('target')) === '_blank') {
            $rel = trim((string) $element->getAttribute('rel'));
            $relParts = preg_split('/\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $relParts = array_values(array_unique(array_merge($relParts, ['noopener', 'noreferrer'])));
            $element->setAttribute('rel', implode(' ', $relParts));
        }
    }

    private function sanitizeRichTextAttributeValue(string $tag, string $name, string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if ($name === 'href') {
            if (preg_match('/^(?:https?:|mailto:|tel:|\/(?!\/)|#)/i', $value)) {
                return $value;
            }

            return null;
        }

        if ($name === 'target') {
            $target = strtolower($value);
            return in_array($target, ['_blank', '_self', '_parent', '_top'], true) ? $target : null;
        }

        if ($name === 'rel') {
            $rel = preg_replace('/[^a-zA-Z0-9 _-]/', '', $value);
            $rel = trim(preg_replace('/\s+/', ' ', (string) $rel));

            return $rel !== '' ? $rel : null;
        }

        if ($name === 'align') {
            $align = strtolower($value);
            return in_array($align, ['left', 'center', 'right', 'justify'], true) ? $align : null;
        }

        if ($name === 'style') {
            $styles = [];
            foreach (explode(';', $value) as $declaration) {
                $declaration = trim($declaration);
                if ($declaration === '' || ! str_contains($declaration, ':')) {
                    continue;
                }

                [$property, $propertyValue] = array_map('trim', explode(':', $declaration, 2));
                if (strtolower($property) !== 'text-align') {
                    continue;
                }

                $propertyValue = strtolower($propertyValue);
                if (! in_array($propertyValue, ['left', 'center', 'right', 'justify'], true)) {
                    continue;
                }

                $styles[] = 'text-align: '.$propertyValue;
            }

            $styles = array_values(array_unique($styles));

            return $styles ? implode('; ', $styles) : null;
        }

        return $value;
    }

    private function siteCustomizationPageSettingKeys(): array
    {
        return array_keys($this->siteCustomizationPageDefaults(config('app.default_name', 'Ecommerce')));
    }

    private function siteCustomizationPageValidationRules(): array
    {
        return [
            'about_us_hero_kicker' => 'nullable|string|max:60',
            'about_us_hero_title' => 'nullable|string|max:180',
            'about_us_hero_text' => 'nullable|string|max:2000',
            'about_us_story_kicker' => 'nullable|string|max:60',
            'about_us_story_title' => 'nullable|string|max:180',
            'about_us_story_text_1' => 'nullable|string|max:2000',
            'about_us_story_text_2' => 'nullable|string|max:2000',
            'about_us_highlight_1_title' => 'nullable|string|max:80',
            'about_us_highlight_1_text' => 'nullable|string|max:120',
            'about_us_highlight_2_title' => 'nullable|string|max:80',
            'about_us_highlight_2_text' => 'nullable|string|max:120',
            'about_us_highlight_3_title' => 'nullable|string|max:80',
            'about_us_highlight_3_text' => 'nullable|string|max:120',
            'about_us_mission_title' => 'nullable|string|max:80',
            'about_us_mission_text' => 'nullable|string|max:2000',
            'about_us_vision_title' => 'nullable|string|max:80',
            'about_us_vision_text' => 'nullable|string|max:2000',
            'about_us_promise_title' => 'nullable|string|max:80',
            'about_us_promise_text' => 'nullable|string|max:2000',
            'about_us_capabilities_kicker' => 'nullable|string|max:60',
            'about_us_capabilities_title' => 'nullable|string|max:180',
            'about_us_capabilities_text' => 'nullable|string|max:2000',
            'about_us_capabilities_items' => 'nullable|string|max:1000',
            'about_us_cta_title' => 'nullable|string|max:180',
            'about_us_cta_text' => 'nullable|string|max:2000',
            'about_us_cta_button_text' => 'nullable|string|max:80',
            'terms_hero_kicker' => 'nullable|string|max:60',
            'terms_hero_title' => 'nullable|string|max:180',
            'terms_hero_text' => 'nullable|string|max:2000',
            'terms_nav_coverage' => 'nullable|string|max:80',
            'terms_nav_exclusions' => 'nullable|string|max:80',
            'terms_nav_service' => 'nullable|string|max:80',
            'terms_nav_delivery' => 'nullable|string|max:80',
            'terms_coverage_title' => 'nullable|string|max:120',
            'terms_coverage_text' => 'nullable|string|max:2000',
            'terms_exclusions_title' => 'nullable|string|max:120',
            'terms_exclusions_items' => 'nullable|string|max:1000',
            'terms_service_title' => 'nullable|string|max:120',
            'terms_service_items' => 'nullable|string|max:1000',
            'terms_delivery_title' => 'nullable|string|max:120',
            'terms_delivery_text' => 'nullable|string|max:2000',
            'terms_help_title' => 'nullable|string|max:120',
            'terms_help_text' => 'nullable|string|max:2000',
            'terms_help_button_text' => 'nullable|string|max:80',
        ];
    }

    private function siteCustomizationSettingKeys()
    {
        return array_merge([
            'site_name', 'site_name_font_size', 'site_tagline', 'site_tagline_font_size',
            'notice_text', 'phone', 'support_phone', 'whatsapp_number', 'support_email',
            'shop_address', 'business_hours', 'facebook_url', 'instagram_url', 'youtube_url',
            'linkedin_url', 'twitter_url', 'google_analytics_id', 'google_site_verification',
            'default_meta_title', 'default_meta_description', 'meta_keywords', 'robots_directive',
            'footer_description', 'copyright_text', 'footer_credit_text', 'footer_credit_url',
            'development_mode_enabled', 'development_mode_message_type', 'development_mode_title',
            'development_mode_message', 'development_mode_additional_message',
            'development_mode_availability_text', 'development_mode_show_admin_login',
            'development_mode_login_button_text', 'logo_resize_enabled', 'logo_resize_width',
            'logo_resize_height', 'favicon_resize_enabled', 'favicon_resize_width',
            'favicon_resize_height', 'startech_source_import_enabled', 'storefront_theme',
            'homepage_featured_products_limit', 'homepage_featured_products_per_row',
            'homepage_new_arrivals_limit', 'homepage_new_arrivals_per_row',
        ], $this->siteCustomizationPageSettingKeys());
    }

    private function siteCustomizationAssetKeys()
    {
        return [
            'logo' => 'site_logo',
            'logo_tablet' => 'site_logo_tablet',
            'logo_mobile' => 'site_logo_mobile',
            'favicon' => 'favicon',
            'seo_image' => 'default_og_image',
        ];
    }

    private function siteCustomizationSetup($settings)
    {
        $settings = collect($settings);
        $brandName = trim((string) ($settings->get('site_name', '') ?: config('app.default_name', 'Ecommerce')));
        $defaults = $this->siteCustomizationDefaults($brandName);
        $siteName = trim((string) $settings->get('site_name', ''));
        $siteTagline = trim((string) $settings->get('site_tagline', ''));
        $siteNameFontSize = (int) ($settings->get('site_name_font_size') ?: $defaults['site_name_font_size']);
        $siteTaglineFontSize = (int) ($settings->get('site_tagline_font_size') ?: $defaults['site_tagline_font_size']);
        $publicPageCustomized = false;
        foreach ($this->siteCustomizationPageSettingKeys() as $key) {
            $currentValue = trim((string) $settings->get($key, ''));
            $defaultValue = trim((string) ($defaults[$key] ?? ''));
            if ($currentValue !== '' && $currentValue !== $defaultValue) {
                $publicPageCustomized = true;
                break;
            }
        }
        $checks = [
            ['Business identity', ($siteName !== '' && strcasecmp($siteName, (string) $defaults['site_name']) !== 0) || $siteTagline !== '' || $siteNameFontSize !== (int) $defaults['site_name_font_size'] || $siteTaglineFontSize !== (int) $defaults['site_tagline_font_size'], 'identity'],
            ['Customer contact', trim((string) $settings->get('phone', '')) !== '' || trim((string) $settings->get('support_phone', '')) !== '' || trim((string) $settings->get('whatsapp_number', '')) !== '' || trim((string) $settings->get('support_email', '')) !== '', 'contact'],
            ['Store address', trim((string) $settings->get('shop_address', '')) !== '' || trim((string) $settings->get('business_hours', '')) !== '', 'contact'],
            ['Search description', trim((string) $settings->get('default_meta_title', '')) !== '' || trim((string) $settings->get('default_meta_description', '')) !== '' || trim((string) $settings->get('meta_keywords', '')) !== '' || trim((string) $settings->get('default_og_image', '')) !== '', 'seo'],
            ['Header branding', trim((string) $settings->get('site_logo', '')) !== '' || trim((string) $settings->get('favicon', '')) !== '', 'identity'],
            ['Public page copy', $publicPageCustomized, 'content'],
        ];
        $complete = collect($checks)->where(1, true)->count();

        return [
            'checks' => $checks,
            'percent' => count($checks) ? (int) round(($complete / count($checks)) * 100) : 0,
        ];
    }

    private function brandResizeOptions(Request $request, $prefix)
    {
        if (!in_array($prefix, ['logo', 'favicon'], true) || !$request->boolean($prefix.'_resize_enabled')) {
            return null;
        }

        return [
            'width' => (int)$request->input($prefix.'_resize_width'),
            'height' => (int)$request->input($prefix.'_resize_height'),
        ];
    }

    private function storeBrandAsset($file, $prefix, ?array $resizeOptions = null)
    {
        if (!$file || !$file->isValid()) {
            throw new \RuntimeException('The uploaded branding file is not valid.');
        }
        $relativeDirectory = 'asset/front-end/img/branding/';
        $directory = public_path($relativeDirectory);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('The branding upload directory could not be created.');
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException('The branding upload directory is not writable.');
        }
        $extension = strtolower((string)($file->getClientOriginalExtension() ?: $file->extension()));
        if (!in_array($extension, ['ico','png','jpg','jpeg','webp'], true)) {
            throw new \RuntimeException('The uploaded branding file has an unsupported extension.');
        }
        if ($extension === 'jpeg') $extension = 'jpg';
        do {
            $name = $prefix.'-'.str_random(20).'.'.$extension;
        } while (is_file($directory.DIRECTORY_SEPARATOR.$name));
        $storedPath = $relativeDirectory.$name;
        $destination = public_path($storedPath);
        try {
            if ($resizeOptions && $extension !== 'ico' && $this->canResizeBrandImage($extension)) {
                $this->writeResizedBrandImage(
                    $file->getPathname(),
                    $destination,
                    $extension,
                    $resizeOptions['width'],
                    $resizeOptions['height']
                );
            } else {
                $file->move($directory, $name);
            }
        } catch (\Throwable $exception) {
            if (is_file($destination)) unlink($destination);
            throw $exception;
        }
        if (!is_file(public_path($storedPath)) || filesize(public_path($storedPath)) === 0) {
            if (is_file($destination)) unlink($destination);
            throw new \RuntimeException('The uploaded branding file was not written completely.');
        }
        return $storedPath;
    }

    private function canResizeBrandImage($extension)
    {
        if (!extension_loaded('gd')) return false;
        if (in_array($extension, ['jpg', 'jpeg'], true)) return function_exists('imagecreatefromjpeg') && function_exists('imagejpeg');
        if ($extension === 'png') return function_exists('imagecreatefrompng') && function_exists('imagepng');
        if ($extension === 'webp') return function_exists('imagecreatefromwebp') && function_exists('imagewebp');
        return false;
    }

    private function writeResizedBrandImage($sourcePath, $destination, $extension, $targetWidth, $targetHeight)
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('Automatic image resizing is not available on this server.');
        }
        $details = @getimagesize($sourcePath);
        if (!$details || empty($details[0]) || empty($details[1])) {
            throw new \RuntimeException('The uploaded image dimensions could not be read.');
        }

        $source = null;
        $canvas = null;
        try {
            if ($extension === 'png') {
                $source = @imagecreatefrompng($sourcePath);
            } elseif ($extension === 'webp' && function_exists('imagecreatefromwebp')) {
                $source = @imagecreatefromwebp($sourcePath);
            } elseif (in_array($extension, ['jpg', 'jpeg'], true)) {
                $source = @imagecreatefromjpeg($sourcePath);
            }
            if (!$source) {
                throw new \RuntimeException('The uploaded image could not be opened for resizing.');
            }

            $targetWidth = (int)$targetWidth;
            $targetHeight = (int)$targetHeight;
            $sourceWidth = (int)$details[0];
            $sourceHeight = (int)$details[1];
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if (!$canvas) {
                throw new \RuntimeException('The resized image canvas could not be created.');
            }

            if (in_array($extension, ['png', 'webp'], true)) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
                imagefill($canvas, 0, 0, $transparent);
            } else {
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);
            }

            $scale = min($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
            $drawWidth = max(1, (int)round($sourceWidth * $scale));
            $drawHeight = max(1, (int)round($sourceHeight * $scale));
            $drawX = (int)floor(($targetWidth - $drawWidth) / 2);
            $drawY = (int)floor(($targetHeight - $drawHeight) / 2);
            if (!imagecopyresampled(
                $canvas,
                $source,
                $drawX,
                $drawY,
                0,
                0,
                $drawWidth,
                $drawHeight,
                $sourceWidth,
                $sourceHeight
            )) {
                throw new \RuntimeException('The uploaded image could not be resized.');
            }

            if ($extension === 'png') {
                $written = imagepng($canvas, $destination, 6);
            } elseif ($extension === 'webp' && function_exists('imagewebp')) {
                $written = imagewebp($canvas, $destination, 88);
            } else {
                $written = imagejpeg($canvas, $destination, 88);
            }
            if (!$written) {
                throw new \RuntimeException('The resized image could not be written.');
            }
        } finally {
            if ($source) imagedestroy($source);
            if ($canvas) imagedestroy($canvas);
        }
    }

    private function removeBrandAsset($path)
    {
        return app(SafeMediaDeletionService::class)->deleteManagedBrandAsset($path);
    }

    private function removeBrandAssetIfUnused($path)
    {
        return app(SafeMediaDeletionService::class)->deleteManagedBrandAssetIfUnused($path, function ($candidatePath) {
            return DB::table('site_settings')->where('setting_value', $candidatePath)->exists();
        });
    }

    private function managedBrandAssetPath($path)
    {
        return app(SafeMediaDeletionService::class)->managedBrandAssetPath($path);
    }

    private function storeBannerImage($file, $prefix)
    {
        return PublicUpload::store($file, 'asset/front-end/img/banners/', $prefix.'-', ['jpg','jpeg','png','webp']);
    }

    private function isSafeBannerUrl($url)
    {
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) return true;
        return filter_var($url, FILTER_VALIDATE_URL) && strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    private function removeBannerFileIfUnused($path, $excludingId)
    {
        app(MediaLifecycleService::class)->deleteIfUnreferenced($path, ['banner' => [(int) $excludingId]], 'Banner image replaced or removed.');
    }

    private function removeBannerFile($path)
    {
        app(MediaLifecycleService::class)->deletePath($path, 'Banner image removed.');
    }

    public function customerInbox()
    {
        $this->authCheck();
        $reviews = DB::table('product_reviews')->join('product','product_reviews.product_id','=','product.id')->select('product_reviews.*','product.product_name')->latest('product_reviews.created_at')->get();
        $questions = DB::table('product_questions')->join('product','product_questions.product_id','=','product.id')->select('product_questions.*','product.product_name')->latest('product_questions.created_at')->get();
        $supportRequests = DB::table('support_requests')->latest()->get();
        return view('admin.admin-pages.customer-inbox', compact('reviews','questions','supportRequests'));
    }

    public function moderateReview(Request $request, $id)
    {
        $this->authCheck();
        if ($request->action === 'delete') DB::table('product_reviews')->where('id',$id)->delete();
        else DB::table('product_reviews')->where('id',$id)->update(['is_approved'=>$request->action === 'approve' ? 1 : 0,'updated_at'=>now()]);
        return Redirect::to('/customer-inbox')->with('message','Review updated.');
    }

    public function answerQuestion(Request $request, $id)
    {
        $this->authCheck();
        if ($request->action === 'delete') DB::table('product_questions')->where('id',$id)->delete();
        else {
            $this->validate($request,['answer'=>'required|min:2|max:3000']);
            DB::table('product_questions')->where('id',$id)->update(['answer'=>$request->answer,'is_approved'=>1,'answered_at'=>now(),'updated_at'=>now()]);
        }
        return Redirect::to('/customer-inbox')->with('message','Question updated.');
    }

    public function updateSupportRequest(Request $request, $id)
    {
        $this->authCheck();
        $this->validate($request,['status'=>'required|in:new,in_progress,resolved','admin_note'=>'nullable|max:3000']);
        DB::table('support_requests')->where('id',$id)->update(['status'=>$request->status,'admin_note'=>$request->admin_note,'updated_at'=>now()]);
        return Redirect::to('/customer-inbox')->with('message','Support request updated.');
    }

    public function manageOrders(Request $request)
    {
        $this->authCheck();
        $query = DB::table('orders')->latest();
        if ($request->filled('status')) $query->where('status',$request->status);
        if ($request->filled('search')) {
            $term=$request->search;
            $query->where(function($q) use($term){$q->where('order_number','like','%'.$term.'%')->orWhere('customer_name','like','%'.$term.'%')->orWhere('phone','like','%'.$term.'%');});
        }
        $orders=$query->paginate(20)->appends($request->query());
        return view('admin.admin-pages.manage-orders',compact('orders'));
    }

    public function viewOrder($id)
    {
        $this->authCheck();
        $order=DB::table('orders')->where('id',$id)->first();
        abort_unless($order,404);
        $items=DB::table('order_items')->where('order_id',$id)->get();
        return view('admin.admin-pages.view-order',compact('order','items'));
    }

    public function updateOrderStatus(Request $request,$id)
    {
        $this->authCheck();
        $this->validate($request,['status'=>'required|in:pending,confirmed,processing,shipped,delivered,cancelled','delivery_charge'=>'nullable|numeric|min:0']);
        $order=DB::table('orders')->where('id',$id)->first();
        abort_unless($order,404);
        $delivery=$request->filled('delivery_charge')?(float)$request->delivery_charge:(float)$order->delivery_charge;
        DB::table('orders')->where('id',$id)->update(['status'=>$request->status,'delivery_charge'=>$delivery,'total'=>(float)$order->subtotal-(float)$order->discount+$delivery,'updated_at'=>now()]);
        if($order->status!==$request->status) {
            $updated=DB::table('orders')->where('id',$id)->first();
            app(\App\Services\OrderNotifier::class)->customer($updated,'Order '.$updated->order_number.' is '.ucfirst($updated->status),'Your order status has been updated to '.ucfirst($updated->status).'.');
            app(\App\Services\WebhookService::class)->dispatch('order.updated',['order_id'=>$updated->id,'order_number'=>$updated->order_number,'status'=>$updated->status,'total'=>(float)$updated->total]);
        }
        return Redirect::to('/manage-orders/'.$id)->with('message','Order updated.');
    }

    public function inventory(Request $request)
    {
        $this->authCheck();
        $query=DB::table('product')->whereNull('deleted_at')->orderBy('product_name');
        if ($request->filter==='low') $query->where('stock_tracking',1)->whereBetween('stock_quantity',[1,5]);
        if ($request->filter==='out') $query->where('stock_tracking',1)->where('stock_quantity',0);
        if ($request->filter==='untracked') $query->where('stock_tracking',0);
        if ($request->filled('search')) $query->where(function($q)use($request){$q->where('product_name','like','%'.$request->search.'%')->orWhere('sku','like','%'.$request->search.'%');});
        $products=$query->paginate(25)->appends($request->query());
        $counts=['tracked'=>DB::table('product')->whereNull('deleted_at')->where('stock_tracking',1)->count(),'low'=>DB::table('product')->whereNull('deleted_at')->where('stock_tracking',1)->whereBetween('stock_quantity',[1,5])->count(),'out'=>DB::table('product')->whereNull('deleted_at')->where('stock_tracking',1)->where('stock_quantity',0)->count()];
        return view('admin.admin-pages.inventory',compact('products','counts'));
    }

    public function updateInventory(Request $request,$id)
    {
        $this->authCheck();
        $this->validate($request,['stock_quantity'=>'required|integer|min:0|max:100000']);
        $before=Product::find($id);
        if (! $before) {
            return Redirect::back()->with('exception','Product not found.');
        }
        $quantity=(int)$request->stock_quantity;
        DB::table('product')->where('id',$id)->update(['stock_quantity'=>$quantity,'stock_tracking'=>$request->has('stock_tracking')?1:0,'product_condition'=>$request->has('stock_tracking')?($quantity>0?'In Stock':'Out Of Stock'):$request->product_condition,'updated_at'=>now()]);
        if(\Schema::hasTable('inventory_locations')){$location=DB::table('inventory_locations')->where('is_default',1)->first();if($location){DB::table('product_location_stock')->updateOrInsert(['location_id'=>$location->id,'product_id'=>$id],['quantity'=>$quantity,'updated_at'=>now(),'created_at'=>now()]);$total=DB::table('product_location_stock')->where('product_id',$id)->sum('quantity');DB::table('product')->where('id',$id)->update(['stock_quantity'=>$total,'product_condition'=>$total>0?'In Stock':'Out Of Stock']);}}
        $after=DB::table('product')->where('id',$id)->first();$notified=0;
        if($after && $after->product_condition==='In Stock' && (!$before || $before->product_condition!=='In Stock')) $notified=app(\App\Services\StockAlertService::class)->process($id);
        if($after) app(\App\Services\WebhookService::class)->dispatch('inventory.updated',['product_id'=>$after->id,'sku'=>$after->sku,'stock_quantity'=>$after->stock_quantity,'condition'=>$after->product_condition]);
        return Redirect::back()->with('message','Inventory updated.'.($notified?' '.$notified.' stock alert(s) processed.':''));
    }

    public function catalogAttributes(Request $request)
    {
        $this->authCheck();
        $categories=DB::table('category')->whereNull('deleted_at')->where('publication_status',1)->orderBy('category_name')->get();
        $query=DB::table('catalog_attributes')->join('category',function($join){$join->on('catalog_attributes.category_id','=','category.category_id')->whereNull('category.deleted_at');})
            ->select('catalog_attributes.*','category.category_name',DB::raw('(SELECT COUNT(*) FROM product_attribute_values pav WHERE pav.attribute_id = catalog_attributes.id) as usage_count'))
            ->orderBy('category.category_name')->orderBy('catalog_attributes.display_order')->orderBy('catalog_attributes.name');
        if($request->filled('category_id')) $query->where('catalog_attributes.category_id',$request->category_id);
        if($request->filled('search')) $query->where(function($q)use($request){$term='%'.trim($request->search).'%';$q->where('catalog_attributes.name','like',$term)->orWhere('catalog_attributes.slug','like',$term);});
        if(in_array((string)$request->query('input_type'),['select','multiselect','text'],true)) $query->where('catalog_attributes.input_type',$request->query('input_type'));
        if(in_array((string)$request->query('filterable'),['0','1'],true)) $query->where('catalog_attributes.is_filterable',(int)$request->query('filterable'));
        if(in_array((string)$request->query('comparable'),['0','1'],true)) $query->where('catalog_attributes.is_comparable',(int)$request->query('comparable'));
        if($request->query('usage')==='used') $query->whereExists(function($q){$q->select(DB::raw(1))->from('product_attribute_values as pav_filter')->whereRaw('pav_filter.attribute_id = catalog_attributes.id');});
        if($request->query('usage')==='unused') $query->whereNotExists(function($q){$q->select(DB::raw(1))->from('product_attribute_values as pav_filter')->whereRaw('pav_filter.attribute_id = catalog_attributes.id');});
        $attributes=$query->get();
        $categoryStats=DB::table('catalog_attributes')->select('category_id',DB::raw('COUNT(*) as attribute_count'),DB::raw('SUM(is_filterable) as filter_count'),DB::raw('SUM(is_comparable) as compare_count'))->groupBy('category_id')->get()->keyBy('category_id');
        return view('admin.admin-pages.catalog-attributes',compact('categories','attributes','categoryStats'));
    }

    public function saveCatalogAttribute(Request $request)
    {
        $this->authCheck();
        if($request->has('attributes')){
            $this->validate($request,['category_id'=>['required','integer',Rule::exists('category','category_id')->whereNull('deleted_at')],'attributes'=>'required|array|min:1|max:30','attributes.*.name'=>'required|string|max:100','attributes.*.input_type'=>'required|in:select,multiselect,text','attributes.*.display_order'=>'nullable|integer|min:0']);
            $rows=[];$slugs=[];
            foreach($request->attributes as $index=>$attribute){
                $slug=str_slug($attribute['name']);
                if(in_array($slug,$slugs,true)||DB::table('catalog_attributes')->where('category_id',$request->category_id)->where('slug',$slug)->exists())return Redirect::back()->withInput()->with('exception','Duplicate attribute: '.$attribute['name'].'. Remove it or rename it before saving.');
                $slugs[]=$slug;$options=in_array($attribute['input_type'],['select','multiselect'],true)?$this->parseList(isset($attribute['options'])?$attribute['options']:''):[];
                if(in_array($attribute['input_type'],['select','multiselect'],true)&&!$options)return Redirect::back()->withInput()->with('exception','Add at least one option for '.$attribute['name'].'.');
                $rows[]=['category_id'=>(int)$request->category_id,'name'=>trim($attribute['name']),'slug'=>$slug,'input_type'=>$attribute['input_type'],'options'=>json_encode($options),'is_filterable'=>!empty($attribute['is_filterable'])?1:0,'is_comparable'=>!empty($attribute['is_comparable'])?1:0,'display_order'=>max(0,(int)(isset($attribute['display_order'])?$attribute['display_order']:(($index+1)*10))),'created_at'=>now(),'updated_at'=>now()];
            }
            DB::transaction(function()use($rows){DB::table('catalog_attributes')->insert($rows);});
            return Redirect::to('/catalog-attributes?category_id='.$request->category_id)->with('message',count($rows).' attributes created.');
        }
        $this->validate($request,['category_id'=>['required','integer',Rule::exists('category','category_id')->whereNull('deleted_at')],'name'=>'required|max:100','input_type'=>'required|in:select,multiselect,text','display_order'=>'nullable|integer|min:0']);
        $slug=str_slug($request->name);
        $exists=DB::table('catalog_attributes')->where('category_id',$request->category_id)->where('slug',$slug)->exists();
        if($exists) return Redirect::back()->withInput()->with('exception','That attribute already exists for this category.');
        $options=in_array($request->input_type,['select','multiselect'],true)?$this->parseList($request->options):[];
        if(in_array($request->input_type,['select','multiselect'],true)&&!$options)return Redirect::back()->withInput()->with('exception','Add at least one option for a selection attribute.');
        DB::table('catalog_attributes')->insert(['category_id'=>$request->category_id,'name'=>$request->name,'slug'=>$slug,'input_type'=>$request->input_type,'options'=>json_encode($options),'is_filterable'=>$request->has('is_filterable')?1:0,'is_comparable'=>$request->has('is_comparable')?1:0,'display_order'=>max(0,(int)$request->display_order),'created_at'=>now(),'updated_at'=>now()]);
        return Redirect::to('/catalog-attributes?category_id='.$request->category_id)->with('message','Attribute created.');
    }

    public function updateCatalogAttribute(Request $request, $id)
    {
        $this->authCheck();
        $attribute=DB::table('catalog_attributes')->where('id',$id)->first();
        if(!$attribute)return Redirect::to('/catalog-attributes')->with('exception','Attribute not found.');
        $this->validate($request,['category_id'=>['required','integer',Rule::exists('category','category_id')->whereNull('deleted_at')],'name'=>'required|string|max:120','input_type'=>'required|in:select,multiselect,text','display_order'=>'nullable|integer|min:0']);
        $slug=str_slug($request->name);
        if(DB::table('catalog_attributes')->where('category_id',$request->category_id)->where('slug',$slug)->where('id','<>',$id)->exists())return Redirect::back()->with('exception','That attribute already exists in this category.');
        $options=in_array($request->input_type,['select','multiselect'],true)?$this->parseList($request->options):[];
        if(in_array($request->input_type,['select','multiselect'],true)&&!$options)return Redirect::back()->with('exception','Add at least one option for a selection attribute.');
        DB::table('catalog_attributes')->where('id',$id)->update(['category_id'=>$request->category_id,'name'=>trim($request->name),'slug'=>$slug,'input_type'=>$request->input_type,'options'=>json_encode($options),'is_filterable'=>$request->has('is_filterable')?1:0,'is_comparable'=>$request->has('is_comparable')?1:0,'display_order'=>max(0,(int)$request->display_order),'updated_at'=>now()]);
        return Redirect::to('/catalog-attributes?category_id='.$request->category_id)->with('message','Attribute updated.');
    }

    public function toggleCatalogAttribute(Request $request, $id)
    {
        $this->authCheck();
        $field=$request->input('field');
        if(!in_array($field,['is_filterable','is_comparable'],true))return Redirect::back()->with('exception','Invalid attribute setting.');
        $attribute=DB::table('catalog_attributes')->where('id',$id)->first();
        if(!$attribute)return Redirect::back()->with('exception','Attribute not found.');
        DB::table('catalog_attributes')->where('id',$id)->update([$field=>$attribute->{$field}?0:1,'updated_at'=>now()]);
        return Redirect::back()->with('message','Attribute setting updated.');
    }

    public function duplicateCatalogAttribute($id)
    {
        $this->authCheck();
        $attribute=DB::table('catalog_attributes')->where('id',$id)->first();
        if(!$attribute)return Redirect::back()->with('exception','Attribute not found.');
        $name=$attribute->name.' Copy';$slug=str_slug($name);$suffix=2;
        while(DB::table('catalog_attributes')->where('category_id',$attribute->category_id)->where('slug',$slug)->exists()){$name=$attribute->name.' Copy '.$suffix++;$slug=str_slug($name);}
        DB::table('catalog_attributes')->insert(['category_id'=>$attribute->category_id,'name'=>$name,'slug'=>$slug,'input_type'=>$attribute->input_type,'options'=>$attribute->options,'is_filterable'=>$attribute->is_filterable,'is_comparable'=>$attribute->is_comparable,'display_order'=>$attribute->display_order+1,'created_at'=>now(),'updated_at'=>now()]);
        return Redirect::back()->with('message','Attribute duplicated.');
    }

    public function bulkDeleteCatalogAttributes(Request $request)
    {
        $this->authCheck();
        $this->validate($request,['attribute_ids'=>'required|array|min:1','attribute_ids.*'=>'integer|distinct|exists:catalog_attributes,id']);
        $count=DB::table('catalog_attributes')->whereIn('id',$request->attribute_ids)->delete();
        return Redirect::back()->with('message',$count.' attribute'.($count===1?'':'s').' deleted.');
    }

    public function reorderCatalogAttributes(Request $request)
    {
        $this->authCheck();
        $this->validate($request,['category_id'=>'required|integer','attribute_ids'=>'required|array','attribute_ids.*'=>'integer|distinct']);
        $valid=DB::table('catalog_attributes')->where('category_id',$request->category_id)->whereIn('id',$request->attribute_ids)->pluck('id')->map(function($id){return (int)$id;})->all();
        if(count($valid)!==count($request->attribute_ids))return response()->json(['message'=>'Invalid attribute order.'],422);
        DB::transaction(function()use($request){foreach($request->attribute_ids as $index=>$id)DB::table('catalog_attributes')->where('id',$id)->update(['display_order'=>($index+1)*10,'updated_at'=>now()]);});
        return response()->json(['message'=>'Order saved.']);
    }

    public function deleteCatalogAttribute($id)
    {
        $this->authCheck();
        DB::table('catalog_attributes')->where('id',$id)->delete();
        return Redirect::back()->with('message','Attribute deleted.');
    }

    public function deliveryZones()
    {
        $this->authCheck();
        $zones=DB::table('delivery_zones')->orderBy('display_order')->get();
        return view('admin.admin-pages.delivery-zones',compact('zones'));
    }

    public function saveDeliveryZone(Request $request)
    {
        $this->authCheck();
        $this->validate($request,['name'=>'required|max:120','charge'=>'required|numeric|min:0','free_delivery_minimum'=>'nullable|numeric|min:0','estimated_time'=>'nullable|max:120','display_order'=>'nullable|integer|min:0']);
        $data=['name'=>$request->name,'areas'=>$request->areas,'charge'=>$request->charge,'free_delivery_minimum'=>$request->free_delivery_minimum?:null,'estimated_time'=>$request->estimated_time,'is_active'=>(int)$request->input('is_active',0)===1?1:0,'display_order'=>max(0,(int)$request->display_order),'updated_at'=>now()];
        if($request->filled('id')) DB::table('delivery_zones')->where('id',$request->id)->update($data);
        else { $data['created_at']=now(); DB::table('delivery_zones')->insert($data); }
        return Redirect::to('/delivery-zones')->with('message','Delivery zone saved.');
    }

    public function toggleDeliveryZone($id)
    {
        $this->authCheck();
        $zone=DB::table('delivery_zones')->where('id',$id)->first();
        if($zone) DB::table('delivery_zones')->where('id',$id)->update(['is_active'=>$zone->is_active?0:1,'updated_at'=>now()]);
        return Redirect::back()->with('message','Delivery zone status updated.');
    }

    public function deleteDeliveryZone($id)
    {
        $this->authCheck();
        $used=DB::table('orders')->where('delivery_zone_id',$id)->exists();
        if($used) return Redirect::back()->with('exception','This zone is used by existing orders and cannot be deleted. Disable it instead.');
        DB::table('delivery_zones')->where('id',$id)->delete();
        return Redirect::back()->with('message','Delivery zone deleted.');
    }

    public function coupons()
    {
        $coupons=DB::table('coupons')->orderByDesc('id')->get();
        return view('admin.admin-pages.coupons',compact('coupons'));
    }

    public function saveCoupon(Request $request)
    {
        $this->validate($request,['code'=>'required|max:60','discount_type'=>'required|in:fixed,percent','discount_value'=>'required|numeric|min:0.01','minimum_order'=>'nullable|numeric|min:0','maximum_discount'=>'nullable|numeric|min:0','usage_limit'=>'nullable|integer|min:1','starts_at'=>'nullable|date','expires_at'=>'nullable|date|after:starts_at']);
        if($request->discount_type==='percent' && (float)$request->discount_value>100) return Redirect::back()->withInput()->with('exception','Percentage discount cannot exceed 100%.');
        $code=strtoupper(trim($request->code));
        $duplicate=DB::table('coupons')->where('code',$code)->when($request->id,function($query) use($request){$query->where('id','<>',$request->id);})->exists();
        if($duplicate) return Redirect::back()->withInput()->with('exception','That coupon code already exists.');
        $data=['code'=>$code,'description'=>$request->description,'discount_type'=>$request->discount_type,'discount_value'=>$request->discount_value,'minimum_order'=>$request->minimum_order?:0,'maximum_discount'=>$request->maximum_discount?:null,'usage_limit'=>$request->usage_limit?:null,'starts_at'=>$request->starts_at?:null,'expires_at'=>$request->expires_at?:null,'is_active'=>(int)$request->input('is_active',0)===1?1:0,'updated_at'=>now()];
        if($request->filled('id')) DB::table('coupons')->where('id',$request->id)->update($data); else {$data['created_at']=now();DB::table('coupons')->insert($data);}
        return Redirect::to('/coupons')->with('message','Coupon saved.');
    }

    public function toggleCoupon($id)
    {
        $coupon=DB::table('coupons')->where('id',$id)->first();
        if($coupon) DB::table('coupons')->where('id',$id)->update(['is_active'=>$coupon->is_active?0:1,'updated_at'=>now()]);
        return Redirect::back()->with('message','Coupon status updated.');
    }

    public function deleteCoupon($id)
    {
        if(DB::table('orders')->where('coupon_id',$id)->exists()) return Redirect::back()->with('exception','This coupon was used in an order. Disable it instead.');
        DB::table('coupons')->where('id',$id)->delete();
        return Redirect::back()->with('message','Coupon deleted.');
    }

    public function adminNotifications()
    {
        $notifications=DB::table('store_notifications')->where('recipient_type','admin')->latest()->paginate(30);
        return view('admin.admin-pages.notifications',compact('notifications'));
    }

    public function readAdminNotification($id)
    {
        $notification=DB::table('store_notifications')->where('id',$id)->where('recipient_type','admin')->first();
        abort_unless($notification,404);
        DB::table('store_notifications')->where('id',$id)->update(['read_at'=>now(),'updated_at'=>now()]);
        return $notification->action_url?redirect($notification->action_url):Redirect::to('/admin-notifications');
    }

    public function readAllAdminNotifications()
    {
        DB::table('store_notifications')->where('recipient_type','admin')->whereNull('read_at')->update(['read_at'=>now(),'updated_at'=>now()]);
        return Redirect::back()->with('message','All notifications marked as read.');
    }

    public function stockAlerts(Request $request)
    {
        $query=DB::table('stock_alerts')->join('product','product.id','=','stock_alerts.product_id')->select('stock_alerts.*','product.product_name','product.product_condition')->latest('stock_alerts.created_at');
        if($request->filled('status'))$query->where('stock_alerts.status',$request->status);
        $alerts=$query->paginate(30)->appends($request->query());
        $counts=['waiting'=>DB::table('stock_alerts')->where('status','waiting')->count(),'notified'=>DB::table('stock_alerts')->where('status','notified')->count()];
        return view('admin.admin-pages.stock-alerts',compact('alerts','counts'));
    }

    public function deleteStockAlert($id)
    {
        DB::table('stock_alerts')->where('id',$id)->delete();return Redirect::back()->with('message','Stock alert deleted.');
    }

    public function serviceClaims(Request $request)
    {
        $query=DB::table('service_claims')->latest();if($request->filled('status'))$query->where('status',$request->status);if($request->filled('search')){$term=$request->search;$query->where(function($q)use($term){$q->where('claim_number','like','%'.$term.'%')->orWhere('customer_name','like','%'.$term.'%')->orWhere('product_name','like','%'.$term.'%')->orWhere('phone','like','%'.$term.'%');});}$claims=$query->paginate(30)->appends($request->query());return view('admin.admin-pages.service-claims',compact('claims'));
    }

    public function viewServiceClaim($id)
    {
        $claim=DB::table('service_claims')->where('id',$id)->first();abort_unless($claim,404);return view('admin.admin-pages.service-claim',compact('claim'));
    }

    public function updateServiceClaim(Request $request,$id)
    {
        $this->validate($request,['status'=>'required|in:submitted,reviewing,approved,item_received,in_service,ready,completed,rejected','admin_note'=>'nullable|max:4000']);$claim=DB::table('service_claims')->where('id',$id)->first();abort_unless($claim,404);DB::table('service_claims')->where('id',$id)->update(['status'=>$request->status,'admin_note'=>$request->admin_note,'updated_at'=>now()]);if($claim->status!==$request->status&&$claim->user_id)DB::table('store_notifications')->insert(['recipient_type'=>'customer','user_id'=>$claim->user_id,'order_id'=>$claim->order_id,'email'=>$claim->email,'title'=>'Service request '.$claim->claim_number.' updated','message'=>'Your service request is now '.ucwords(str_replace('_',' ',$request->status)).'.'.($request->admin_note?' '.$request->admin_note:''),'action_url'=>url('/service-request/'.$claim->id),'email_status'=>'not_requested','created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Service request updated.');
    }

    public function paymentMethods(){ $methods=\App\PaymentMethod::with('emiPlans')->orderBy('display_order')->get();return view('admin.admin-pages.payment-methods',compact('methods')); }
    public function savePaymentMethod(Request $request){$this->validate($request,['name'=>'required|max:120','code'=>'required|max:50','type'=>'required|in:cash,bank,card,mobile,offline','display_order'=>'nullable|integer|min:0']);$code=str_slug($request->code,'_');$duplicate=DB::table('payment_methods')->where('code',$code)->when($request->id,function($q)use($request){$q->where('id','<>',$request->id);})->exists();if($duplicate)return Redirect::back()->with('exception','Payment method code already exists.');$data=['name'=>$request->name,'code'=>$code,'type'=>$request->type,'instructions'=>$request->instructions,'supports_emi'=>$request->has('supports_emi')?1:0,'is_active'=>$request->has('is_active')?1:0,'display_order'=>max(0,(int)$request->display_order),'updated_at'=>now()];if($request->id)DB::table('payment_methods')->where('id',$request->id)->update($data);else{$data['created_at']=now();DB::table('payment_methods')->insert($data);}return Redirect::back()->with('message','Payment method saved.');}
    public function togglePaymentMethod($id){$method=DB::table('payment_methods')->where('id',$id)->first();if($method)DB::table('payment_methods')->where('id',$id)->update(['is_active'=>$method->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Payment method status updated.');}
    public function saveEmiPlan(Request $request){$this->validate($request,['payment_method_id'=>'required|integer|exists:payment_methods,id','months'=>'required|integer|min:2|max:60','interest_rate'=>'required|numeric|min:0|max:100','processing_fee'=>'nullable|numeric|min:0','minimum_order'=>'nullable|numeric|min:0']);DB::table('emi_plans')->updateOrInsert(['payment_method_id'=>$request->payment_method_id,'months'=>$request->months],['interest_rate'=>$request->interest_rate,'processing_fee'=>$request->processing_fee?:0,'minimum_order'=>$request->minimum_order?:0,'is_active'=>1,'updated_at'=>now(),'created_at'=>now()]);DB::table('payment_methods')->where('id',$request->payment_method_id)->update(['supports_emi'=>1,'updated_at'=>now()]);return Redirect::back()->with('message','EMI plan saved.');}
    public function deleteEmiPlan($id){DB::table('emi_plans')->where('id',$id)->delete();return Redirect::back()->with('message','EMI plan deleted.');}

    public function abandonedCarts(Request $request){$query=DB::table('abandoned_carts')->latest('last_activity_at');if($request->filled('status'))$query->where('status',$request->status);$carts=$query->paginate(30)->appends($request->query());$counts=['active'=>DB::table('abandoned_carts')->where('status','active')->count(),'reminded'=>DB::table('abandoned_carts')->where('status','reminded')->count(),'recovered'=>DB::table('abandoned_carts')->whereIn('status',['recovered','converted'])->count()];return view('admin.admin-pages.abandoned-carts',compact('carts','counts'));}
    public function remindAbandonedCart($id){$sent=app(\App\Services\CartRecoveryService::class)->remind($id);return Redirect::back()->with($sent?'message':'exception',$sent?'Reminder processed.':'Cart has no recovery email or is no longer active.');}
    public function deleteAbandonedCart($id){DB::table('abandoned_carts')->where('id',$id)->delete();return Redirect::back()->with('message','Saved cart deleted.');}

    public function salesReports(Request $request)
    {
        $from=$request->input('from',date('Y-m-01'));$to=$request->input('to',date('Y-m-d'));$this->validate($request,['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
        $orders=DB::table('orders')->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('status','<>','cancelled');$summary=(clone $orders)->selectRaw('COUNT(*) orders_count, COALESCE(SUM(subtotal-discount),0) net_sales, COALESCE(SUM(delivery_charge),0) delivery_income, COALESCE(AVG(total),0) average_order')->first();
        $costs=DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->selectRaw('COALESCE(SUM(i.unit_purchase_price*i.quantity),0) purchase_cost')->first();
        $discounts=(float)(clone $orders)->sum('discount');
        $profitBeforeRefunds=(float)DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->sum('i.profit')-$discounts;
        $daily=DB::table('orders')->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('status','<>','cancelled')->selectRaw('DATE(created_at) sale_date, COUNT(*) orders_count, SUM(subtotal-discount) sales')->groupBy(DB::raw('DATE(created_at)'))->orderBy('sale_date')->get();
        $topProducts=DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->select('i.product_name',DB::raw('SUM(i.quantity) units'),DB::raw('SUM(i.subtotal) sales'),DB::raw('SUM(i.profit) profit'))->groupBy('i.product_name')->orderByDesc('sales')->limit(15)->get();
        $refundTotal=(float)DB::table('refunds')->where('status','completed')->whereBetween('refunded_at',[$from.' 00:00:00',$to.' 23:59:59'])->sum('amount');
        $recoveredPurchaseCost=(float)DB::table('refunds as r')->join('order_returns as returns','returns.id','=','r.order_return_id')->join('order_return_items as ri','ri.order_return_id','=','returns.id')->join('order_items as i','i.id','=','ri.order_item_id')->where('r.status','completed')->whereBetween('r.refunded_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('ri.restock',1)->whereNotNull('ri.inventory_restored_at')->sum(DB::raw('i.unit_purchase_price*ri.quantity'));
        $netAfterRefunds=(float)$summary->net_sales-$refundTotal;
        $purchaseCostAfterReturns=(float)$costs->purchase_cost-$recoveredPurchaseCost;
        $profitAfterRefunds=$profitBeforeRefunds-$refundTotal+$recoveredPurchaseCost;
        $statuses=DB::table('orders')->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->select('status',DB::raw('COUNT(*) total'))->groupBy('status')->get();
        return view('admin.admin-pages.sales-reports',compact('from','to','summary','costs','discounts','profitBeforeRefunds','daily','topProducts','statuses','refundTotal','recoveredPurchaseCost','netAfterRefunds','purchaseCostAfterReturns','profitAfterRefunds'));
    }

    public function exportSalesReport(Request $request)
    {
        $from=$request->input('from',date('Y-m-01'));$to=$request->input('to',date('Y-m-d'));$this->validate($request,['from'=>'required|date','to'=>'required|date|after_or_equal:from']);$rows=DB::table('orders as o')->leftJoin('order_items as i','i.order_id','=','o.id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->select('o.order_number','o.created_at','o.customer_name','o.phone','o.status','o.payment_method','o.subtotal','o.discount','o.delivery_charge','o.total',DB::raw('COALESCE(SUM(i.unit_purchase_price*i.quantity),0) purchase_cost'),DB::raw('COALESCE(SUM(i.profit),0)-o.discount profit_before_refunds'))->groupBy('o.id','o.order_number','o.created_at','o.customer_name','o.phone','o.status','o.payment_method','o.subtotal','o.discount','o.delivery_charge','o.total')->orderBy('o.created_at')->get();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Order','Date','Customer','Phone','Status','Payment','Subtotal','Discount','Delivery','Total','Purchase Cost','Profit Before Refunds']);foreach($rows as $r)fputcsv($out,[$r->order_number,$r->created_at,$r->customer_name,$r->phone,$r->status,$r->payment_method,$r->subtotal,$r->discount,$r->delivery_charge,$r->total,$r->purchase_cost,$r->profit_before_refunds]);fclose($out);},'sales-report-'.$from.'-to-'.$to.'.csv',['Content-Type'=>'text/csv']);
    }

    public function marketingCampaigns(){ $segments=DB::table('customer_segments')->where('is_active',1)->orderBy('name')->get();$campaigns=DB::table('marketing_campaigns as c')->join('customer_segments as s','s.id','=','c.customer_segment_id')->leftJoin('coupons as p','p.id','=','c.coupon_id')->select('c.*','s.name as segment_name','p.code as coupon_code')->latest('c.id')->get();$coupons=DB::table('coupons')->where('is_active',1)->orderBy('code')->get();foreach($segments as $segment)$segment->audience_count=app(\App\Services\CampaignService::class)->audience($segment)->count();return view('admin.admin-pages.marketing-campaigns',compact('segments','campaigns','coupons'));}
    public function saveCustomerSegment(Request $request){$this->validate($request,['name'=>'required|max:120','minimum_orders'=>'nullable|integer|min:0','minimum_spend'=>'nullable|numeric|min:0','registered_within_days'=>'nullable|integer|min:1','inactive_for_days'=>'nullable|integer|min:1']);DB::table('customer_segments')->insert(['name'=>$request->name,'description'=>$request->description,'minimum_orders'=>max(0,(int)$request->minimum_orders),'minimum_spend'=>max(0,(float)$request->minimum_spend),'registered_within_days'=>$request->registered_within_days?:null,'inactive_for_days'=>$request->inactive_for_days?:null,'registered_only'=>1,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Customer segment created.');}
    public function deleteCustomerSegment($id){if(DB::table('marketing_campaigns')->where('customer_segment_id',$id)->exists())return Redirect::back()->with('exception','This segment is used by a campaign and cannot be deleted.');DB::table('customer_segments')->where('id',$id)->delete();return Redirect::back()->with('message','Customer segment deleted.');}
    public function saveMarketingCampaign(Request $request){$this->validate($request,['name'=>'required|max:120','subject'=>'required|max:180','message'=>'required|min:10|max:5000','customer_segment_id'=>'required|integer|exists:customer_segments,id','coupon_id'=>'nullable|integer|exists:coupons,id']);DB::table('marketing_campaigns')->insert(['name'=>$request->name,'subject'=>$request->subject,'message'=>$request->message,'customer_segment_id'=>$request->customer_segment_id,'coupon_id'=>$request->coupon_id?:null,'status'=>'draft','created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Campaign draft created. Prepare its audience before sending.');}
    public function prepareMarketingCampaign($id){$campaign=DB::table('marketing_campaigns')->where('id',$id)->first();abort_unless($campaign,404);if($campaign->status==='sent')return Redirect::back()->with('exception','A sent campaign cannot be prepared again.');$count=app(\App\Services\CampaignService::class)->prepare($id);return Redirect::back()->with('message',$count.' recipient(s) prepared. Review the count before sending.');}
    public function sendMarketingCampaign($id){$campaign=DB::table('marketing_campaigns')->where('id',$id)->first();abort_unless($campaign,404);if($campaign->status!=='ready')return Redirect::back()->with('exception','Prepare the audience before sending.');$count=app(\App\Services\CampaignService::class)->send($id);return Redirect::back()->with('message',$count.' campaign notification(s) processed.');}
    public function deleteMarketingCampaign($id){$campaign=DB::table('marketing_campaigns')->where('id',$id)->first();if($campaign&&$campaign->status==='sent')return Redirect::back()->with('exception','Sent campaigns are retained for delivery history.');DB::table('campaign_recipients')->where('campaign_id',$id)->delete();DB::table('marketing_campaigns')->where('id',$id)->delete();return Redirect::back()->with('message','Campaign deleted.');}

    public function adminUsers(){ $roles=DB::table('admin_roles')->orderBy('name')->get();$admins=DB::table('tbl_admin as a')->leftJoin('admin_roles as r','r.id','=','a.role_id')->select('a.*','r.name as role_name')->orderBy('a.admin_name')->get();return view('admin.admin-pages.admin-users',compact('roles','admins'));}
    public function saveAdminRole(Request $request){$permissions=['dashboard','catalog','inventory','orders','customers','marketing','reports','settings','staff','view_product_code_configuration','change_product_code_configuration','view_product_code_sequence','change_product_code_sequence','reset_product_code_sequence','override_product_code','regenerate_product_code','view_product_code_history','view_recycle_bin','restore_deleted_items','permanently_delete_items','empty_recycle_bin','view_orphan_media','cleanup_orphan_media','view_storefront_navbar','change_storefront_navbar'];$this->validate($request,['name'=>'required|max:100']);$selected=array_values(array_intersect($permissions,(array)$request->permissions));if(!in_array('dashboard',$selected,true))$selected[]='dashboard';if(DB::table('admin_roles')->where('name',$request->name)->exists())return Redirect::back()->with('exception','A role with that name already exists.');DB::table('admin_roles')->insert(['name'=>$request->name,'permissions'=>json_encode($selected),'is_system'=>0,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Administrator role created.');}
    public function updateAdminRole(Request $request,$id){
        $allowed=['dashboard','catalog','inventory','orders','customers','marketing','reports','settings','staff','view_product_code_configuration','change_product_code_configuration','view_product_code_sequence','change_product_code_sequence','reset_product_code_sequence','override_product_code','regenerate_product_code','view_product_code_history','view_recycle_bin','restore_deleted_items','permanently_delete_items','empty_recycle_bin','view_orphan_media','cleanup_orphan_media','view_storefront_navbar','change_storefront_navbar'];
        $this->validate($request,['name'=>'required|max:100']);
        $role=DB::table('admin_roles')->where('id',$id)->first();
        abort_unless($role,404);
        if(DB::table('admin_roles')->where('name',$request->name)->where('id','<>',$id)->exists())return Redirect::back()->with('exception','A role with that name already exists.');
        $selected=array_values(array_intersect($allowed,(array)$request->permissions));
        if(!in_array('dashboard',$selected,true))$selected[]='dashboard';
        if($role->is_system && $role->name !== 'Super Admin')$selected=$allowed;
        DB::table('admin_roles')->where('id',$id)->update(['name'=>$request->name,'permissions'=>json_encode($selected),'updated_at'=>now()]);
        return Redirect::back()->with('message','Administrator role updated.');
    }
    public function deleteAdminRole($id){$role=DB::table('admin_roles')->where('id',$id)->first();if(!$role)return Redirect::back();if($role->is_system||DB::table('tbl_admin')->where('role_id',$id)->exists())return Redirect::back()->with('exception','System roles and roles assigned to staff cannot be deleted.');DB::table('admin_roles')->where('id',$id)->delete();return Redirect::back()->with('message','Role deleted.');}
    public function saveAdminUser(Request $request){$this->validate($request,['admin_name'=>'required|max:30','full_name'=>'nullable|max:120','admin_email'=>'nullable|email|max:150','role_id'=>'required|integer|exists:admin_roles,id','password'=>'required|min:8|max:255']);if(DB::table('tbl_admin')->where('admin_name',$request->admin_name)->exists())return Redirect::back()->withInput()->with('exception','That administrator username already exists.');DB::table('tbl_admin')->insert(['admin_name'=>$request->admin_name,'full_name'=>$request->full_name,'admin_email'=>$request->admin_email,'role_id'=>$request->role_id,'is_active'=>1,'admin_password'=>\Hash::make($request->password),'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Administrator account created.');}
    public function updateAdminUser(Request $request,$id){
        $this->validate($request,['admin_name'=>'required|max:30','full_name'=>'nullable|max:120','admin_email'=>'nullable|email|max:150','role_id'=>'required|integer|exists:admin_roles,id']);
        $admin=DB::table('tbl_admin')->where('admin_id',$id)->first();
        abort_unless($admin,404);
        if(DB::table('tbl_admin')->where('admin_name',$request->admin_name)->where('admin_id','<>',$id)->exists())return Redirect::back()->with('exception','That administrator username already exists.');
        if($request->admin_email&&DB::table('tbl_admin')->where('admin_email',$request->admin_email)->where('admin_id','<>',$id)->exists())return Redirect::back()->with('exception','That administrator email is already in use.');
        $roleId=(int)$request->role_id;
        if((int)$id===(int)session('admin_id'))$roleId=(int)$admin->role_id;
        DB::table('tbl_admin')->where('admin_id',$id)->update(['admin_name'=>$request->admin_name,'full_name'=>$request->full_name?:null,'admin_email'=>$request->admin_email?:null,'role_id'=>$roleId,'updated_at'=>now()]);
        if((int)$id===(int)session('admin_id'))$request->session()->put(['admin_name'=>$request->admin_name,'admin_display_name'=>$request->full_name ?: $request->admin_name]);
        return Redirect::back()->with('message','Administrator account updated.');
    }
    public function toggleAdminUser($id){if((int)$id===(int)session('admin_id'))return Redirect::back()->with('exception','You cannot disable your own account.');$admin=DB::table('tbl_admin')->where('admin_id',$id)->first();if($admin)DB::table('tbl_admin')->where('admin_id',$id)->update(['is_active'=>$admin->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Administrator status updated.');}
    public function resetAdminPassword(Request $request,$id){$this->validate($request,['password'=>'required|min:8|max:255|confirmed']);abort_unless(DB::table('tbl_admin')->where('admin_id',$id)->exists(),404);DB::table('tbl_admin')->where('admin_id',$id)->update(['admin_password'=>\Hash::make($request->password),'updated_at'=>now()]);return Redirect::back()->with('message','Administrator password updated.');}
    public function adminActivity(Request $request){$query=DB::table('admin_activity_logs')->latest('created_at');if($request->filled('admin_id'))$query->where('admin_id',$request->admin_id);if($request->filled('search'))$query->where(function($q)use($request){$q->where('action','like','%'.$request->search.'%')->orWhere('path','like','%'.$request->search.'%');});$logs=$query->paginate(50)->appends($request->query());$admins=DB::table('tbl_admin')->select('admin_id','admin_name')->orderBy('admin_name')->get();return view('admin.admin-pages.admin-activity',compact('logs','admins'));}

    public function systemHealth()
    {
        try {
            DB::select('SELECT 1');
            $database = ['ok' => true, 'message' => 'Connected'];
        } catch (\Throwable $e) {
            $database = ['ok' => false, 'message' => $e->getMessage()];
        }

        $storageWritable = is_writable(storage_path('app'))
            && is_writable(storage_path('framework'))
            && is_writable(storage_path('logs'));
        $disk = $this->diskHealth();
        $health = [
            'database' => $database,
            'storage' => [
                'ok' => $storageWritable,
                'message' => $storageWritable ? 'Writable' : 'One or more storage directories are not writable',
            ],
            'php' => ['ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'message' => PHP_VERSION],
            'laravel' => ['ok' => true, 'message' => app()->version()],
            'disk' => $disk,
            'environment' => [
                'ok' => config('app.env') === 'production' && ! config('app.debug'),
                'message' => config('app.env').' · debug '.(config('app.debug') ? 'ON' : 'off'),
            ],
        ];
        $migrations = $this->migrationStatus();
        $backups = DB::table('system_backups')->latest()->limit(30)->get();
        $lastBackup = $backups->first();

        return view('admin.admin-pages.system-health', compact('health', 'migrations', 'backups', 'lastBackup'));
    }

    private function diskHealth(): array
    {
        if ($cpanel = $this->cpanelDiskUsage()) {
            $used = (float) $cpanel['used_bytes'];
            $limit = $cpanel['limit_bytes'];
            $percent = $limit !== null && $limit > 0
                ? round(($used / $limit) * 100, 2)
                : null;
            $message = $cpanel['used_label'].' used';
            if ($cpanel['limit_label'] !== null) {
                $message .= ' of '.$cpanel['limit_label'];
                if ($percent !== null) {
                    $message .= ' ('.$percent.'%)';
                }
            } else {
                $message .= ' · cPanel quota unlimited';
            }

            return [
                'ok' => $limit === null || $percent < 90,
                'message' => $message,
            ];
        }

        $path = base_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if ($free === false || $total === false || $total <= 0) {
            return ['ok' => false, 'message' => 'Unavailable'];
        }

        return [
            'ok' => $free > 536870912,
            'message' => $this->formatBytes($free).' free of '.$this->formatBytes($total).' filesystem capacity (cPanel quota unavailable)',
        ];
    }

    private function cpanelDiskUsage(): ?array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        $username = $_SERVER['CPANEL_USER'] ?? getenv('CPANEL_USER') ?: null;
        if (! $username && preg_match('#[/\\\\]home[/\\\\]([A-Za-z0-9._-]+)#', base_path(), $matches)) {
            $username = $matches[1];
        }
        if (! $username && function_exists('get_current_user')) {
            $username = get_current_user();
        }
        if (! is_string($username) || ! preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
            return null;
        }

        foreach (['/usr/local/cpanel/bin/uapi', '/usr/bin/uapi'] as $binary) {
            if (! is_file($binary) || ! is_executable($binary)) {
                continue;
            }

            try {
                $process = new Process([
                    $binary,
                    '--output=json',
                    '--user='.$username,
                    'StatsBar',
                    'get_stats',
                    'display=diskusage',
                    'warnings=0',
                ], base_path(), null, null, 5);
                $process->run();
                if (! $process->isSuccessful()) {
                    continue;
                }

                $payload = json_decode($process->getOutput(), true);
                $data = data_get($payload, 'result.data');
                $records = is_array($data) && array_is_list($data) ? $data : [$data];
                foreach ($records as $record) {
                    if (! is_array($record)) {
                        continue;
                    }
                    $key = strtolower((string) ($record['id'] ?? $record['name'] ?? ''));
                    if ($key !== 'diskusage') {
                        continue;
                    }

                    $used = $this->cpanelMegabytesToBytes($record['count'] ?? $record['_count'] ?? null);
                    $limitValue = $record['max'] ?? $record['_max'] ?? null;
                    $limit = $this->cpanelMegabytesToBytes($limitValue);
                    if ($used === null) {
                        continue;
                    }

                    return [
                        'used_bytes' => $used,
                        'limit_bytes' => $limit,
                        'used_label' => $this->cpanelAmountLabel($record['count'] ?? $record['_count'] ?? null),
                        'limit_label' => $limit === null ? null : $this->cpanelAmountLabel($limitValue),
                    ];
                }
            } catch (\Throwable $e) {
                // Shared hosting may disable the UAPI binary. Use the safe
                // filesystem fallback instead of breaking System Health.
            }
        }

        return null;
    }

    private function cpanelMegabytesToBytes($value): ?float
    {
        $normalized = str_replace(',', '', trim((string) $value));
        if ($value === null || strtolower($normalized) === 'unlimited') {
            return null;
        }
        if (is_string($value) && ! is_numeric($normalized)) {
            if (! preg_match('/^\\s*([0-9]+(?:\\.[0-9]+)?)\\s*(KB|MB|GB|TB|B)?/i', $normalized, $matches)) {
                return null;
            }
            $number = (float) $matches[1];
            $unit = strtoupper($matches[2] ?? 'MB');
            $multipliers = ['B' => 1, 'KB' => 1024, 'MB' => 1048576, 'GB' => 1073741824, 'TB' => 1099511627776];
            return $number * ($multipliers[$unit] ?? 1048576);
        }

        return (float) $value * 1048576;
    }

    private function cpanelAmountLabel($value): ?string
    {
        if ($value === null || strtolower(trim((string) $value)) === 'unlimited') {
            return null;
        }
        if (is_string($value) && preg_match('/^\\s*[0-9,]+(?:\\.[0-9]+)?\\s*(KB|MB|GB|TB|B)\\b/i', $value)) {
            return trim($value);
        }
        $number = (float) $value;
        $formatted = fmod($number, 1.0) === 0.0 ? number_format($number, 0) : rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
        return $formatted.' MB';
    }

    public function runPendingMigrations(){
        $this->requireAdminPermission('settings');
        try {
            \Artisan::call('migrate', ['--force' => true, '--no-ansi' => true]);
            $output=trim(\Artisan::output());
            $this->auditAdminAction('RUN_PENDING_MIGRATIONS', ['output' => $output]);
            return Redirect::back()->with('message', $output !== '' ? $output : 'Database migrations completed successfully.');
        } catch (\Throwable $e) {
            $this->auditAdminAction('RUN_PENDING_MIGRATIONS_FAILED', ['error' => $e->getMessage()]);
            return Redirect::back()->with('exception', 'Migration failed: '.$e->getMessage());
        }
    }

    private function migrationStatus(): array
    {
        try {
            $migrator=app('migrator');
            $ran=array_flip($migrator->getRepository()->getRan());
            $files=$migrator->getMigrationFiles(database_path('migrations'));
            $pending=[];
            foreach ($files as $name => $path) {
                if (!isset($ran[$name])) $pending[]=$name;
            }
            return ['ok'=>count($pending)===0,'pending'=>$pending,'ran'=>count($files)-count($pending),'total'=>count($files),'message'=>count($pending)===0?'All database migrations are up to date.':count($pending).' pending migration(s) require attention.'];
        } catch (\Throwable $e) {
            return ['ok'=>false,'pending'=>[],'ran'=>0,'total'=>0,'message'=>'Unable to check migrations: '.$e->getMessage()];
        }
    }
    public function createSystemBackup(){try{$id=app(\App\Services\DatabaseBackupService::class)->create(session('admin_name'));return Redirect::back()->with('message','Database backup completed successfully. Reference '.$id.'.');}catch(\Throwable $e){return Redirect::back()->with('exception','Backup failed: '.$e->getMessage());}}
    public function createFullSystemBackup(){set_time_limit(0);try{$id=app(\App\Services\DatabaseBackupService::class)->createFull(session('admin_name'));return Redirect::back()->with('message','Full database and media backup completed successfully. Reference '.$id.'.');}catch(\Throwable $e){return Redirect::back()->with('exception','Full backup failed: '.$e->getMessage());}}
    public function createMediaSystemBackup(){set_time_limit(0);try{$id=app(\App\Services\DatabaseBackupService::class)->createMedia(session('admin_name'));return Redirect::back()->with('message','Media backup completed successfully. Reference '.$id.'.');}catch(\Throwable $e){return Redirect::back()->with('exception','Media backup failed: '.$e->getMessage());}}
    public function restoreSystemBackup(Request $request){set_time_limit(0);$this->validate($request,['backup'=>'required|file|max:512000']);$file=$request->file('backup');if(!preg_match('/^ecommerce-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz$/i',$file->getClientOriginalName()))return Redirect::back()->with('exception','Please upload a generated Ecommerce .sql.gz backup file.');$service=app(\App\Services\DatabaseBackupService::class);$path=$file->getRealPath();$safetyId=null;try{$safetyId=$service->create('pre-restore by '.session('admin_name'));$service->restore($path);Cache::flush();return Redirect::back()->with('message','Database restored successfully. A pre-restore safety backup was saved as reference '.$safetyId.'.');}catch(\Throwable $e){return Redirect::back()->with('exception','Restore failed. The pre-restore safety backup reference is '.($safetyId??'unavailable').'. '.$e->getMessage());}}
    public function restoreFullSystemBackup(Request $request){set_time_limit(0);$this->validate($request,['full_backup'=>'required|file|max:512000']);$file=$request->file('full_backup');if(!preg_match('/^ecommerce-\d{4}-\d{2}-\d{2}-\d{6}\.full\.tar\.gz$/i',$file->getClientOriginalName()))return Redirect::back()->with('exception','Please upload a generated Ecommerce full backup (.full.tar.gz) file.');$service=app(\App\Services\DatabaseBackupService::class);$path=$file->getRealPath();$safetyId=null;try{$safetyId=$service->createFull('pre-full-restore by '.session('admin_name'));$service->restoreFull($path);Cache::flush();return Redirect::back()->with('message','Full database and media restore completed successfully. A pre-restore safety backup was saved as reference '.$safetyId.'.');}catch(\Throwable $e){return Redirect::back()->with('exception','Full restore failed. The pre-restore safety backup reference is '.($safetyId??'unavailable').'. '.$e->getMessage());}}
    public function restoreMediaSystemBackup(Request $request){set_time_limit(0);$this->validate($request,['media_backup'=>'required|file|max:512000']);$file=$request->file('media_backup');if(!preg_match('/^ecommerce-\d{4}-\d{2}-\d{2}-\d{6}\.media\.tar\.gz$/i',$file->getClientOriginalName()))return Redirect::back()->with('exception','Please upload a generated Ecommerce media backup (.media.tar.gz) file.');try{app(\App\Services\DatabaseBackupService::class)->restoreMedia($file->getRealPath());Cache::flush();return Redirect::back()->with('message','Media restore completed successfully.');}catch(\Throwable $e){return Redirect::back()->with('exception','Media restore failed: '.$e->getMessage());}}
    public function downloadSystemBackup($id){$backup=DB::table('system_backups')->where('id',$id)->where('status','completed')->first();abort_unless($backup,404);$path=storage_path('app/backups/'.$backup->filename);abort_unless(is_file($path),404);return response()->download($path,$backup->filename,['Content-Type'=>'application/gzip']);}
    public function deleteSystemBackup($id){$backup=DB::table('system_backups')->where('id',$id)->first();if($backup){\Storage::disk($backup->disk)->delete('backups/'.$backup->filename);DB::table('system_backups')->where('id',$id)->delete();}return Redirect::back()->with('message','Backup deleted.');}
    public function clearSystemCache(){try{\Artisan::call('optimize:clear',['--no-ansi'=>true]);\Artisan::call('view:cache',['--no-ansi'=>true]);return Redirect::back()->with('message','Application caches cleared and website views rebuilt successfully.');}catch(\Throwable $e){report($e);return Redirect::back()->with('exception','Cache refresh failed: '.$e->getMessage());}}
    public function systemMonitor(Request $request){$query=DB::table('system_events')->latest('last_occurred_at');if($request->filled('type'))$query->where('event_type',$request->type);if($request->input('status')==='open')$query->whereNull('resolved_at');if($request->input('status')==='resolved')$query->whereNotNull('resolved_at');$events=$query->paginate(40)->appends($request->query());$runs=DB::table('scheduled_task_runs')->latest('started_at')->limit(20)->get();$stats=['open_errors'=>DB::table('system_events')->where('event_type','application_error')->whereNull('resolved_at')->count(),'security_24h'=>DB::table('system_events')->where('event_type','admin_security')->where('last_occurred_at','>=',now()->subDay())->count(),'failed_logins'=>DB::table('system_events')->where('title','like','Failed administrator login%')->where('last_occurred_at','>=',now()->subDay())->count(),'failed_tasks'=>DB::table('scheduled_task_runs')->where('status','failed')->where('started_at','>=',now()->subDays(7))->count()];return view('admin.admin-pages.system-monitor',compact('events','runs','stats'));}
    public function resolveSystemEvent($id){$event=DB::table('system_events')->where('id',$id)->first();abort_unless($event,404);DB::table('system_events')->where('id',$id)->update(['resolved_at'=>now(),'resolved_by'=>session('admin_name'),'updated_at'=>now()]);return Redirect::back()->with('message','System event marked as resolved.');}
    public function integrations(){ $clients=DB::table('api_clients')->latest()->get();$webhooks=DB::table('webhook_endpoints')->latest()->get();$deliveries=DB::table('webhook_deliveries as d')->join('webhook_endpoints as w','w.id','=','d.webhook_endpoint_id')->select('d.*','w.name as webhook_name')->latest('d.created_at')->limit(50)->get();return view('admin.admin-pages.integrations',compact('clients','webhooks','deliveries'));}
    public function saveApiClient(Request $request){$allowed=['catalog.read','orders.read','inventory.write'];$this->validate($request,['name'=>'required|max:120']);$scopes=array_values(array_intersect($allowed,(array)$request->scopes));if(!$scopes)return Redirect::back()->with('exception','Select at least one API scope.');$token='ltbd_'.bin2hex(random_bytes(24));DB::table('api_clients')->insert(['name'=>$request->name,'token_prefix'=>substr($token,0,12),'token_hash'=>hash('sha256',$token),'scopes'=>json_encode($scopes),'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','API key created. Copy it now; it will not be shown again.')->with('new_api_token',$token);}
    public function toggleApiClient($id){$client=DB::table('api_clients')->where('id',$id)->first();if($client)DB::table('api_clients')->where('id',$id)->update(['is_active'=>$client->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','API client status updated.');}
    public function deleteApiClient($id){DB::table('api_clients')->where('id',$id)->delete();return Redirect::back()->with('message','API client deleted.');}
    public function saveWebhookEndpoint(Request $request, \App\Services\SafeExternalUrl $safeUrl){$this->validate($request,['name'=>'required|max:120','url'=>'required|url|max:1000']);if(!$safeUrl->isAllowed($request->url))return Redirect::back()->withInput()->with('exception','Webhook URL must use HTTPS and resolve only to public internet addresses.');$allowed=['order.created','order.updated','inventory.updated'];$events=array_values(array_intersect($allowed,(array)$request->events));if(!$events)return Redirect::back()->with('exception','Select at least one webhook event.');$secret=$request->secret?:bin2hex(random_bytes(24));DB::table('webhook_endpoints')->insert(['name'=>$request->name,'url'=>$request->url,'secret'=>encrypt($secret),'events'=>json_encode($events),'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Webhook created. Copy the signing secret now.')->with('new_webhook_secret',$secret);}
    public function toggleWebhookEndpoint($id){$hook=DB::table('webhook_endpoints')->where('id',$id)->first();if($hook)DB::table('webhook_endpoints')->where('id',$id)->update(['is_active'=>$hook->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Webhook status updated.');}
    public function deleteWebhookEndpoint($id){DB::table('webhook_deliveries')->where('webhook_endpoint_id',$id)->delete();DB::table('webhook_endpoints')->where('id',$id)->delete();return Redirect::back()->with('message','Webhook deleted.');}

    public function purchasing(){ $suppliers=DB::table('suppliers')->orderBy('name')->get();$products=DB::table('product')->whereNull('deleted_at')->select('id','product_name','sku','purchase_price')->orderBy('product_name')->get();$purchaseOrders=DB::table('purchase_orders as p')->join('suppliers as s','s.id','=','p.supplier_id')->select('p.*','s.name as supplier_name')->latest('p.id')->paginate(25);return view('admin.admin-pages.purchasing',compact('suppliers','products','purchaseOrders'));}
    public function saveSupplier(Request $request){$this->validate($request,['name'=>'required|max:150','email'=>'nullable|email|max:150','phone'=>'nullable|max:40']);DB::table('suppliers')->insert(['name'=>$request->name,'contact_person'=>$request->contact_person,'phone'=>$request->phone,'email'=>$request->email,'address'=>$request->address,'tax_id'=>$request->tax_id,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Supplier created.');}
    public function toggleSupplier($id){$supplier=DB::table('suppliers')->where('id',$id)->first();if($supplier)DB::table('suppliers')->where('id',$id)->update(['is_active'=>$supplier->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Supplier status updated.');}
    public function savePurchaseOrder(Request $request){$this->validate($request,['supplier_id'=>'required|integer|exists:suppliers,id','expected_at'=>'nullable|date','other_cost'=>'nullable|numeric|min:0','product_id'=>'required|array|min:1','product_id.*'=>['required','integer','distinct',Rule::exists('product','id')->whereNull('deleted_at')],'quantity.*'=>'required|integer|min:1|max:100000','unit_cost.*'=>'required|numeric|min:0']);$products=DB::table('product')->whereNull('deleted_at')->whereIn('id',$request->product_id)->get()->keyBy('id');$subtotal=0;$lines=[];foreach($request->product_id as $index=>$productId){$product=$products[$productId];$quantity=(int)$request->quantity[$index];$cost=(float)$request->unit_cost[$index];$line=$quantity*$cost;$subtotal+=$line;$lines[]=['product_id'=>$product->id,'product_name'=>$product->product_name,'sku'=>$product->sku,'ordered_quantity'=>$quantity,'received_quantity'=>0,'unit_cost'=>$cost,'subtotal'=>$line,'created_at'=>now(),'updated_at'=>now()];}$id=DB::transaction(function()use($request,$subtotal,$lines){$id=DB::table('purchase_orders')->insertGetId(['po_number'=>'PO-'.date('ymd').'-'.strtoupper(str_random(5)),'supplier_id'=>$request->supplier_id,'status'=>'draft','expected_at'=>$request->expected_at,'subtotal'=>$subtotal,'other_cost'=>$request->other_cost?:0,'total'=>$subtotal+(float)$request->other_cost,'notes'=>$request->notes,'created_by'=>session('admin_name'),'created_at'=>now(),'updated_at'=>now()]);foreach($lines as $line){$line['purchase_order_id']=$id;DB::table('purchase_order_items')->insert($line);}return $id;});return Redirect::to('/purchase-orders/'.$id)->with('message','Purchase order created as a draft.');}
    public function viewPurchaseOrder($id){$order=DB::table('purchase_orders as p')->join('suppliers as s','s.id','=','p.supplier_id')->where('p.id',$id)->select('p.*','s.name as supplier_name','s.contact_person','s.phone as supplier_phone','s.email as supplier_email')->first();abort_unless($order,404);$items=DB::table('purchase_order_items')->where('purchase_order_id',$id)->get();$receipts=DB::table('stock_receipts')->where('purchase_order_id',$id)->latest('received_at')->get();return view('admin.admin-pages.purchase-order',compact('order','items','receipts'));}
    public function updatePurchaseOrderStatus(Request $request,$id){$this->validate($request,['status'=>'required|in:ordered,cancelled']);$order=DB::table('purchase_orders')->where('id',$id)->first();abort_unless($order,404);if($order->status!=='draft')return Redirect::back()->with('exception','Only draft purchase orders can be ordered or cancelled.');DB::table('purchase_orders')->where('id',$id)->update(['status'=>$request->status,'updated_at'=>now()]);return Redirect::back()->with('message','Purchase order status updated.');}
    public function receivePurchaseOrder(Request $request,$id)
    {
        $order=DB::table('purchase_orders')->where('id',$id)->first();
        abort_unless($order,404);
        if(!in_array($order->status,['ordered','partial'],true)) return Redirect::back()->with('exception','This purchase order is not open for receiving.');
        $location=DB::table('inventory_locations')->where('is_default',1)->where('is_active',1)->first();
        if(!$location) return Redirect::back()->with('exception','Configure an active default warehouse before receiving stock.');

        $received=0;$updatedProducts=[];
        try {
            DB::transaction(function()use($request,$id,$location,&$received,&$updatedProducts){
                $items=DB::table('purchase_order_items')->where('purchase_order_id',$id)->lockForUpdate()->get();
                foreach($items as $item){
                    $quantity=max(0,(int)$request->input('received.'.$item->id,0));
                    $remaining=$item->ordered_quantity-$item->received_quantity;
                    if($quantity>$remaining) throw new \RuntimeException('Received quantity exceeds the remaining quantity for '.$item->product_name.'.');
                    if(!$quantity) continue;
                    $product=DB::table('product')->whereNull('deleted_at')->where('id',$item->product_id)->lockForUpdate()->first();
                    $oldQuantity=max(0,(int)$product->stock_quantity);$newQuantity=$oldQuantity+$quantity;
                    $newCost=(float)$product->purchase_price>0&&$oldQuantity>0?(($oldQuantity*(float)$product->purchase_price)+($quantity*(float)$item->unit_cost))/$newQuantity:(float)$item->unit_cost;
                    $balance=DB::table('product_location_stock')->where('location_id',$location->id)->where('product_id',$product->id)->lockForUpdate()->first();
                    if($balance) DB::table('product_location_stock')->where('id',$balance->id)->increment('quantity',$quantity);
                    else DB::table('product_location_stock')->insert(['location_id'=>$location->id,'product_id'=>$product->id,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);
                    DB::table('product')->where('id',$product->id)->update(['stock_quantity'=>$newQuantity,'stock_tracking'=>1,'product_condition'=>'In Stock','purchase_price'=>round($newCost,2),'updated_at'=>now()]);
                    DB::table('purchase_order_items')->where('id',$item->id)->increment('received_quantity',$quantity);
                    DB::table('stock_receipts')->insert(['purchase_order_id'=>$id,'purchase_order_item_id'=>$item->id,'product_id'=>$product->id,'location_id'=>$location->id,'quantity'=>$quantity,'unit_cost'=>$item->unit_cost,'received_by'=>session('admin_name'),'received_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
                    $received+=$quantity;$updatedProducts[$product->id]=['product_id'=>$product->id,'sku'=>$product->sku,'stock_quantity'=>$newQuantity,'condition'=>'In Stock'];
                }
                $remaining=DB::table('purchase_order_items')->where('purchase_order_id',$id)->whereRaw('received_quantity < ordered_quantity')->exists();
                DB::table('purchase_orders')->where('id',$id)->update(['status'=>$remaining?'partial':'received','updated_at'=>now()]);
            });
        } catch(\RuntimeException $e) { return Redirect::back()->withInput()->with('exception',$e->getMessage()); }
        foreach($updatedProducts as $payload){app(\App\Services\StockAlertService::class)->process($payload['product_id']);app(\App\Services\WebhookService::class)->dispatch('inventory.updated',$payload);}
        return Redirect::back()->with('message',$received.' inventory unit(s) received into '.$location->name.'.');
    }

    public function stockLocations(Request $request){$locations=DB::table('inventory_locations')->orderByDesc('is_default')->orderBy('name')->get();$editLocation=$request->filled('edit')?DB::table('inventory_locations')->where('id',$request->edit)->first():null;$products=DB::table('product')->whereNull('deleted_at')->select('id','product_name','sku','stock_quantity')->orderBy('product_name')->get();$balances=DB::table('product_location_stock as s')->join('inventory_locations as l','l.id','=','s.location_id')->select('s.*','l.name as location_name')->get()->groupBy('product_id');$transfers=DB::table('stock_transfers as t')->join('inventory_locations as f','f.id','=','t.from_location_id')->join('inventory_locations as d','d.id','=','t.to_location_id')->select('t.*','f.name as from_name','d.name as to_name')->latest('t.id')->limit(30)->get();return view('admin.admin-pages.stock-locations',compact('locations','editLocation','products','balances','transfers'));}
    public function saveStockLocation(Request $request)
    {
        $this->validateStockLocation($request);
        $data=$this->stockLocationData($request);
        $data['is_default']=0;$data['is_active']=1;$data['created_at']=now();$data['updated_at']=now();
        DB::table('inventory_locations')->insert($data);
        return Redirect::to('/stock-locations')->with('message','Inventory location created.');
    }
    public function updateStockLocation(Request $request,$id)
    {
        $location=DB::table('inventory_locations')->where('id',$id)->first();
        if(!$location)return Redirect::to('/stock-locations')->with('exception','Inventory location not found.');
        $this->validateStockLocation($request,$id);
        $data=$this->stockLocationData($request);$data['updated_at']=now();
        DB::table('inventory_locations')->where('id',$id)->update($data);
        return Redirect::to('/stock-locations')->with('message','Inventory location updated.');
    }
    public function toggleStockLocation($id){$location=DB::table('inventory_locations')->where('id',$id)->first();if(!$location)return Redirect::back();if($location->is_default)return Redirect::back()->with('exception','The default warehouse cannot be disabled.');DB::table('inventory_locations')->where('id',$id)->update(['is_active'=>$location->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Location status updated.');}
    public function saveStockTransfer(Request $request){$this->validate($request,['from_location_id'=>'required|integer|exists:inventory_locations,id','to_location_id'=>'required|integer|different:from_location_id|exists:inventory_locations,id','product_id'=>'required|array|min:1','product_id.*'=>['required','integer','distinct',Rule::exists('product','id')->whereNull('deleted_at')],'quantity.*'=>'required|integer|min:1|max:100000']);$products=DB::table('product')->whereNull('deleted_at')->whereIn('id',$request->product_id)->get()->keyBy('id');try{$id=DB::transaction(function()use($request,$products){$id=DB::table('stock_transfers')->insertGetId(['transfer_number'=>'TR-'.date('ymd').'-'.strtoupper(str_random(5)),'from_location_id'=>$request->from_location_id,'to_location_id'=>$request->to_location_id,'status'=>'completed','notes'=>$request->notes,'created_by'=>session('admin_name'),'completed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);foreach($request->product_id as $index=>$productId){$quantity=(int)$request->quantity[$index];$source=DB::table('product_location_stock')->where('location_id',$request->from_location_id)->where('product_id',$productId)->lockForUpdate()->first();if(!$source||$source->quantity<$quantity)throw new \RuntimeException($products[$productId]->product_name.' has insufficient stock at the source location.');DB::table('product_location_stock')->where('id',$source->id)->decrement('quantity',$quantity);$destination=DB::table('product_location_stock')->where('location_id',$request->to_location_id)->where('product_id',$productId)->lockForUpdate()->first();if($destination)DB::table('product_location_stock')->where('id',$destination->id)->increment('quantity',$quantity);else DB::table('product_location_stock')->insert(['location_id'=>$request->to_location_id,'product_id'=>$productId,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);DB::table('stock_transfer_items')->insert(['stock_transfer_id'=>$id,'product_id'=>$productId,'product_name'=>$products[$productId]->product_name,'sku'=>$products[$productId]->sku,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);}return $id;});}catch(\RuntimeException $e){return Redirect::back()->withInput()->with('exception',$e->getMessage());}return Redirect::back()->with('message','Stock transfer completed. Reference '.$id.'.');}






























    public function authCheck() {
        return true;
    }

    private function parseList($value)
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    private function storeProductImage($image)
    {
        return PublicUpload::store($image, 'asset/front-end/img/Product_image/', 'product-', ['jpg','jpeg','png','webp']);
    }

    private function storeProductImages(array $images)
    {
        $paths=[];
        try {
            foreach($images as $image)if($image)$paths[]=$this->storeProductImage($image);
        } catch (\Throwable $exception) {
            foreach ($paths as $path) $this->deleteOwnedProductImage($path);
            throw $exception;
        }
        return $paths;
    }

    private function deleteOwnedProductImage($path)
    {
        app(MediaLifecycleService::class)->deleteIfUnreferenced($path, [], 'Product image replaced or removed.');
    }

    private function productCodeExists(string $code, ?int $ignoreId = null): bool
    {
        $code = normalize_product_code($code, 100);
        if ($code === null || $code === '') {
            return false;
        }

        $query = DB::table('product')->where(function ($builder) use ($code) {
            $builder->where('product_code', $code)->orWhere('sku', $code);
        });

        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        if ($query->exists()) {
            return true;
        }

        if (! DB::getSchemaBuilder()->hasTable('product_code_histories')) {
            return false;
        }

        return DB::table('product_code_histories')->where(function ($builder) use ($code) {
            $builder->where('old_code', $code)->orWhere('new_code', $code);
        })->exists();
    }

    private function validateStockLocation(Request $request, $ignoreId = null)
    {
        $rules = [
            'name'=>'required|string|max:150','code'=>'required|string|max:30|unique:inventory_locations,code'.($ignoreId?','.$ignoreId:''),
            'type'=>'required|in:warehouse,branch,store,distribution_center,office','address'=>'nullable|string|max:1000',
            'phone'=>'nullable|string|max:40','contact_person'=>'nullable|string|max:120','email'=>'nullable|email|max:150',
            'country'=>'nullable|string|max:100','division'=>'nullable|string|max:100','city'=>'nullable|string|max:100',
            'postal_code'=>'nullable|string|max:20','latitude'=>'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude'=>'nullable|numeric|between:-180,180|required_with:latitude','google_maps_url'=>'nullable|url|max:255',
            'operating_hours'=>'nullable|string|max:150','notes'=>'nullable|string|max:2000'
        ];
        $request->merge(['code'=>strtoupper(trim((string)$request->code))]);
        $this->validate($request,$rules);
    }

    private function bulkDeleteResult($path,$deleted,$skipped,$word,$reason)
    {
        $message=$deleted.' '.$word.($deleted===1?'y':'ies').' deleted.';
        if($word==='manufacturer')$message=$deleted.' manufacturer'.($deleted===1?'':'s').' deleted.';
        if($word==='product')$message=$deleted.' product'.($deleted===1?'':'s').' deleted.';
        if($skipped)$message.=' '.$skipped.' skipped because '.($skipped===1?'it is':'they are').' referenced by '.$reason.'.';
        return Redirect::to($path)->with($deleted?'message':'exception',$message);
    }

    private function stockLocationData(Request $request)
    {
        $fields=['name','code','type','address','country','division','city','postal_code','phone','contact_person','email','operating_hours','google_maps_url','notes'];
        $data=[];foreach($fields as $field)$data[$field]=$request->filled($field)?trim($request->input($field)):null;
        $data['latitude']=$request->filled('latitude')?(float)$request->latitude:null;
        $data['longitude']=$request->filled('longitude')?(float)$request->longitude:null;
        $data['pickup_available']=$request->has('pickup_available')?1:0;
        $data['delivery_hub']=$request->has('delivery_hub')?1:0;
        return $data;
    }

    private function formatBytes($bytes)
    {
        if(!$bytes)return '0 B';$units=['B','KB','MB','GB','TB'];$power=min((int)floor(log($bytes,1024)),count($units)-1);return round($bytes/pow(1024,$power),1).' '.$units[$power];
    }

    private function syncProductAttributes($productId, Request $request)
    {
        $allowed=DB::table('catalog_attributes')->where('category_id',$request->category_id)->pluck('id')->map(function($id){return (int)$id;})->all();
        DB::table('product_attribute_values')->where('product_id',$productId)->delete();
        foreach((array)$request->input('attributes',[]) as $attributeId=>$value) {
            $stored=is_array($value)?json_encode(array_values(array_filter(array_map('trim',$value)))):trim((string)$value);
            if(in_array((int)$attributeId,$allowed,true) && $stored!=='' && $stored!=='[]') DB::table('product_attribute_values')->insert(['product_id'=>$productId,'attribute_id'=>$attributeId,'value'=>$stored,'created_at'=>now(),'updated_at'=>now()]);
        }
    }

    private function setIndustryData(array &$data, Request $request)
    {
        $data['industry_profile']=$request->industry_profile;
        foreach(['generic_name','strength','dosage_form','storage_instructions','allergen_information'] as $field) {
            $data[$field]=$request->filled($field)?trim((string)$request->input($field)):null;
        }
        $data['prescription_required']=$request->has('prescription_required')?1:0;
    }

    private function validateVariantUniqueness(Request $request, $productId = null)
    {
        foreach(['sku','barcode'] as $field) {
            $values=collect((array)$request->input('variants',[]))->pluck($field)->map(function($value) use ($field){return $field === 'sku' ? normalize_product_code($value, 100) : trim((string)$value);})->filter()->values();
            if(!$values->count())continue;
            $query=DB::table('product_variants')->whereIn($field,$values);
            if($productId)$query->where('product_id','<>',$productId);
            if($query->exists())throw \Illuminate\Validation\ValidationException::withMessages(['variants'=>'A variant '.$field.' is already used by another product.']);
            if(DB::table('product')->where(function($query) use ($field, $values) {$query->whereIn($field,$values); if($field === 'sku') {$query->orWhereIn('product_code',$values);}})->when($productId,function($query)use($productId){$query->where('id','<>',$productId);})->exists() || ($field === 'sku' && $values->contains(function ($value) use ($productId) { return $this->productCodeExists((string) $value, $productId); })))throw \Illuminate\Validation\ValidationException::withMessages(['variants'=>'A variant '.$field.' conflicts with a product '.$field.'.']);
        }
    }

    private function syncProductVariantsAndLots($productId, Request $request)
    {
        DB::transaction(function()use($productId,$request){
            $productCode = trim((string) (DB::table('product')->where('id', $productId)->value('product_code') ?: DB::table('product')->where('id', $productId)->value('sku') ?: DB::table('product')->where('id', $productId)->value('product_id') ?: ''));
            $fallbackProductCode = $productCode !== '' ? $productCode : ('VARIANT-'.$productId);
            $variantGenerator = app(ProductCodeGenerator::class);
            $usedSkus = [];
            DB::table('product_variants')->where('product_id',$productId)->delete();
            foreach((array)$request->input('variants',[]) as $variant) {
                $name=trim((string)($variant['name']??''));
                if($name==='')continue;
                $variantSku = normalize_product_code($variant['sku'] ?? null, 100);
                if ($variantSku === null || $variantSku === '') {
                    $variantSku = $variantGenerator->generateVariantSku($fallbackProductCode, $variant, $usedSkus);
                } elseif ($variantSku !== null && in_array($variantSku, $usedSkus, true)) {
                    $variantSku = $variantGenerator->generateVariantSku($fallbackProductCode, $variant, $usedSkus);
                } else {
                    $usedSkus[] = $variantSku;
                }

                DB::table('product_variants')->insert(['product_id'=>$productId,'name'=>$name,'sku'=>$variantSku?:null,'barcode'=>trim((string)($variant['barcode']??''))?:null,'price_adjustment'=>(float)($variant['price_adjustment']??0),'stock_quantity'=>max(0,(int)($variant['stock_quantity']??0)),'is_active'=>isset($variant['is_active'])?1:0,'created_at'=>now(),'updated_at'=>now()]);
            }
            DB::table('product_lots')->where('product_id',$productId)->delete();
            foreach((array)$request->input('lots',[]) as $lot) {
                $number=trim((string)($lot['lot_number']??''));
                if($number==='')continue;
                DB::table('product_lots')->insert(['product_id'=>$productId,'lot_number'=>$number,'manufactured_at'=>($lot['manufactured_at']??null)?:null,'expires_at'=>($lot['expires_at']??null)?:null,'quantity'=>max(0,(int)($lot['quantity']??0)),'supplier_reference'=>trim((string)($lot['supplier_reference']??''))?:null,'created_at'=>now(),'updated_at'=>now()]);
            }
        });
    }

    private function parseSpecifications($value)
    {
        $specifications = [];
        $section = null;
        $pendingLabel = null;
        $pendingValue = [];
        $pendingExplicit = false;

        $savePending = function () use (&$specifications, &$section, &$pendingLabel, &$pendingValue) {
            if ($pendingLabel === null || $pendingValue === []) {
                return;
            }

            $parsedValue = trim(implode("\n", $pendingValue));
            if ($parsedValue === '') {
                return;
            }

            if ($section !== null) {
                $specifications[$section][$pendingLabel] = $parsedValue;
            } else {
                $specifications[$pendingLabel] = $parsedValue;
            }
        };

        $resetPending = function () use (&$pendingLabel, &$pendingValue, &$pendingExplicit) {
            $pendingLabel = null;
            $pendingValue = [];
            $pendingExplicit = false;
        };

        $startPending = function (string $label, array $values = [], bool $explicit = false) use (&$pendingLabel, &$pendingValue, &$pendingExplicit) {
            $pendingLabel = trim($label);
            $pendingValue = array_values(array_filter(array_map('trim', $values), fn ($item) => $item !== ''));
            $pendingExplicit = $explicit;
        };

        foreach (preg_split('/\r\n|\r|\n/', (string) $value) ?: [] as $rawLine) {
            $isIndented = preg_match('/^[\t ]+/', $rawLine) === 1;
            $line = trim($rawLine);

            if ($line === '') {
                $savePending();
                $resetPending();
                continue;
            }

            if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
                $savePending();
                $resetPending();
                $section = trim($matches[1]);
                if ($section !== '' && !isset($specifications[$section])) {
                    $specifications[$section] = [];
                }
                continue;
            }

            if ($isIndented && $pendingLabel !== null) {
                $pendingValue[] = $line;
                continue;
            }

            $tabParts = array_values(array_filter(array_map('trim', preg_split('/\t+/', $line) ?: []), fn ($item) => $item !== ''));
            if (count($tabParts) >= 2) {
                $savePending();
                $resetPending();
                $startPending(array_shift($tabParts), [implode(' ', $tabParts)], true);
                continue;
            }

            $parts = array_map('trim', explode(':', $line, 2));
            if ($pendingLabel !== null) {
                // Explicit "Label: Value" rows and title-case label/value pairs
                // both remain supported. Indented lines above are always kept
                // as continuations, even when their text resembles a label.
                if ($pendingExplicit && $pendingValue !== [] && count($parts) === 2 && $parts[0] !== '') {
                    $savePending();
                    $resetPending();
                    $startPending($parts[0], [$parts[1]], true);
                } elseif ($pendingValue !== [] && count($parts) === 1 && $this->looksLikeSpecificationLabel($line)) {
                    $savePending();
                    $resetPending();
                    $startPending($line);
                } else {
                    // Pasted catalog specifications commonly use two lines per
                    // item: "Data Rate" followed by one or more value lines.
                    $pendingValue[] = $line;
                }
                continue;
            }

            if (count($parts) === 2 && $parts[0] !== '') {
                $startPending($parts[0], [$parts[1]], true);
                continue;
            }

            if ($this->looksLikeSpecificationLabel($line)) {
                $startPending($line);
            }
        }

        $savePending();

        return $specifications;
    }

    /**
     * Detect the label line in the label/value format copied from product pages.
     * A value such as "Microsoft Windows 10" or "2 × Internal Antennas" is
     * intentionally not treated as a new label because it contains digits or
     * punctuation.
     */
    private function looksLikeSpecificationLabel(string $line): bool
    {
        return preg_match('/^[A-Z][A-Za-z]*(?:\s+[A-Z][A-Za-z]*){0,5}$/', $line) === 1;
    }

}
