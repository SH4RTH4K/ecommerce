@extends('admin.admin-master')
@section('title', 'Catalog Hierarchy')
@section('admin_main_content')
@php
    $siteSettings = $siteSettings ?? collect();
    $catalogSourceAddress = catalog_import_source_address($siteSettings->get('catalog_import_source_address'));
    $catalogSourceLabel = catalog_import_source_label($catalogSourceAddress);
    $search = $filters['q'] ?? '';
    $selectedCompanyId = $filters['company_id'] ?? '';
    $selectedBrandId = $filters['brand_id'] ?? '';
    $selectedStatus = $filters['status'] ?? '';
@endphp
<style>
.ch{padding:26px}
.ch-hero{background:linear-gradient(135deg,#073b5c,#0f6b8f);color:#fff;border-radius:14px;padding:25px;margin-bottom:18px;box-shadow:0 10px 22px rgba(10,48,70,.12);display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
.ch-hero h1{margin:0 0 8px;font-size:27px}
.ch-hero p{margin:0;opacity:.9;max-width:900px}
.ch-flow{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.ch-flow span{background:rgba(255,255,255,.14);padding:8px 12px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap}
.ch-note{margin:0 0 18px;padding:14px 16px;border:1px solid #dbe7ee;border-radius:12px;background:#f7fbfd}
.ch-note strong{display:block;margin-bottom:6px;color:#123f61}
.ch-note .steps{display:flex;flex-wrap:wrap;gap:8px}
.ch-note .steps span{padding:7px 10px;border-radius:999px;background:#eaf4fa;color:#184e69;font-size:12px;font-weight:700}
.ch-filter{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto auto;gap:10px;align-items:end;background:#fff;border:1px solid #dce7ed;border-radius:12px;padding:16px;margin-bottom:18px;box-shadow:0 4px 16px rgba(18,63,97,.05)}
.ch-filter label{margin:0;font-weight:600}
.ch-filter input,.ch-filter select{width:100%;box-sizing:border-box;height:38px;margin:6px 0 0!important}
.ch-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.ch-card{background:#fff;border:1px solid #dce7ed;border-radius:12px;overflow:hidden;box-shadow:0 5px 18px rgba(14,56,76,.06)}
.ch-head{padding:18px;border-bottom:1px solid #e8eff3}
.ch-head h2{font-size:19px;margin:0 0 4px}
.ch-head p{margin:0;color:#667985}
.ch-body{padding:18px}
.ch label{display:block;font-weight:600;margin:0 0 6px}
.ch input[type=text],.ch input[type=number],.ch select{width:100%;box-sizing:border-box;height:38px;margin-bottom:10px}
.ch textarea{width:100%;box-sizing:border-box}
.ch-check{font-weight:400!important}
.ch-bulk{display:flex;align-items:center;gap:8px;margin:14px 0 6px;padding:9px;background:#f4f8fa;border-radius:8px;flex-wrap:wrap}
.ch-bulk label{margin:0;font-weight:400}
.ch-list{margin-top:10px;max-height:520px;overflow:auto}
.ch-item{border-top:1px solid #e8eff3;padding:13px 0}
.ch-item:first-child{border-top:0}
.ch-item-title{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.ch-item strong,.ch-item small{display:block}
.ch-item small{color:#6b7c87;margin:3px 0 9px}
.ch details summary{cursor:pointer;color:#08749b;font-weight:600}
.ch details form{margin-top:10px}
.ch-empty{color:#7b8b94;padding:15px 0}
.ch-error{background:#fff3f1;color:#a32d1e;padding:12px;border-radius:8px;margin-bottom:15px}
.ch-success{background:#edf9f1;color:#176536;padding:12px;border-radius:8px;margin-bottom:15px}
.ch-code{display:inline-flex;align-items:center;padding:2px 7px;border-radius:999px;background:#eef5f9;color:#214e67;font-size:12px;font-weight:700}
.ch-mini{color:#607684;font-size:12px;line-height:1.45}
@media(max-width:1100px){.ch-filter{grid-template-columns:1fr 1fr}.ch-grid{grid-template-columns:1fr}.ch-hero{flex-direction:column}.ch-flow{justify-content:flex-start}}
@media(max-width:640px){.ch-filter{grid-template-columns:1fr}}
</style>
<main id="content" class="span10 ch">
    <header class="ch-hero">
        <div>
            <h1>Catalog identity hierarchy</h1>
            <p>Organize the business identity layer before creating products. Companies, brands, and product series stay reusable across the catalog.</p>
        </div>
        <div class="ch-flow">
            <span>1. Company</span>
            <span>2. Brand</span>
            <span>3. Product series</span>
            <span>4. Product</span>
        </div>
    </header>

    @if(session('message'))<div class="ch-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="ch-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="ch-error">{{ $errors->first() }}</div>@endif

    <div class="ch-note">
        <strong>Setup Dependency Order</strong>
        <div class="steps">
            <span>Step 1 · Categories</span>
            <span>Step 2 · Subcategories</span>
            <span>Step 3 · Companies</span>
            <span>Step 4 · Product Attributes</span>
            <span>Step 5 · Products</span>
        </div>
    </div>

    @include('admin.components.startech-import', [
        'title' => $catalogSourceLabel.' source import',
        'description' => 'Pull the live source structure first, then keep company, brand, and product-line records organized here.',
        'stepLabels' => [
            'categories' => 'Categories',
            'subcategories' => 'Subcategories',
            'brands' => 'Brands',
            'series' => 'Series',
        ],
        'selectedSteps' => ['categories', 'subcategories', 'brands', 'series'],
        'submitLabel' => 'Import source hierarchy',
        'helpText' => 'Fetch only previews the current source data. Import after you confirm the company and brand mapping.',
        'noteTitle' => 'Hierarchy import order',
        'noteBody' => 'Categories come first, then subcategories, then brands, then series so downstream product setup stays consistent.',
    ])

    <form class="ch-filter" method="get" action="{{ route('catalog-hierarchy.index') }}">
        <label>Search
            <input type="text" name="q" value="{{ $search }}" placeholder="Company, brand, series, or keyword">
        </label>
        <label>Company
            <select name="company_id">
                <option value="">All companies</option>
                @foreach($filterCompanies as $company)
                    <option value="{{ $company->id }}" {{ (string) $selectedCompanyId === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Brand
            <select name="brand_id">
                <option value="">All brands</option>
                @foreach($filterBrands as $brand)
                    <option value="{{ $brand->manufacturer_id }}" {{ (string) $selectedBrandId === (string) $brand->manufacturer_id ? 'selected' : '' }}>{{ $brand->manufacturer_name }}</option>
                @endforeach
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="">Any status</option>
                <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active / published</option>
                <option value="inactive" {{ $selectedStatus === 'inactive' ? 'selected' : '' }}>Inactive / hidden</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit"><i class="icon-filter"></i> Filter</button>
        <a class="btn" href="{{ route('catalog-hierarchy.index') }}">Reset</a>
    </form>

    <div class="ch-grid">
        <section class="ch-card">
            <div class="ch-head">
                <h2>1. Companies</h2>
                <p>Legal owner or manufacturer group.</p>
            </div>
            <div class="ch-body">
                @include('admin.components.data-transfer', ['resource' => 'companies'])
                <form method="post" action="{{ route('catalog-companies.store') }}">
                    {{ csrf_field() }}
                    <label for="company-name">Company name</label>
                    <input id="company-name" name="name" type="text" maxlength="160" required placeholder="Example: Lenovo Group">
                    <label for="company-code">Company code</label>
                    <input id="company-code" name="company_code" type="text" maxlength="30" placeholder="Example: LEN">
                    <label class="ch-check"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="btn btn-primary" type="submit">Add company</button>
                </form>

                <form id="bulk-companies" method="post" action="{{ route('catalog-companies.bulk-delete') }}" onsubmit="return confirm('Delete selected unused companies?')">
                    {{ csrf_field() }}
                </form>
                <div class="ch-bulk">
                    <label><input type="checkbox" data-select-all="company"> Select visible</label>
                    <button class="btn btn-danger btn-mini" form="bulk-companies" type="submit">Delete selected</button>
                </div>

                <div class="ch-list">
                    @forelse($companies as $company)
                        <article class="ch-item">
                            <div class="ch-item-title">
                                <input type="checkbox" name="company_ids[]" value="{{ $company->id }}" form="bulk-companies" data-check-group="company">
                                <strong>{{ $company->name }}</strong>
                                <span class="ch-code">{{ $company->company_code ?: 'No code' }}</span>
                            </div>
                            <small>{{ $company->brands->count() }} brand(s) &middot; {{ $company->is_active ? 'Active' : 'Inactive' }}</small>
                            <details>
                                <summary>Edit company</summary>
                                <form method="post" action="{{ route('catalog-companies.update', $company->id) }}">
                                    {{ csrf_field() }}
                                    {{ method_field('PATCH') }}
                                    <label>Company name</label>
                                    <input name="name" value="{{ $company->name }}" required>
                                    <label>Company code</label>
                                    <input name="company_code" value="{{ $company->company_code }}" maxlength="30" placeholder="Example: LEN">
                                    <label class="ch-check"><input type="checkbox" name="is_active" value="1" {{ $company->is_active ? 'checked' : '' }}> Active</label>
                                    <button class="btn btn-info" type="submit">Save</button>
                                </form>
                                <form method="post" action="{{ route('catalog-companies.destroy', $company->id) }}" onsubmit="return confirm('Delete this empty company?')">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </details>
                        </article>
                    @empty
                        <div class="ch-empty">No companies match the filters.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="ch-card">
            <div class="ch-head">
                <h2>2. Brands</h2>
                <p>Customer-facing names assigned to products.</p>
            </div>
            <div class="ch-body">
                @include('admin.components.data-transfer', ['resource' => 'manufacturers'])
                <form method="post" action="{{ route('catalog-brands.store') }}">
                    {{ csrf_field() }}
                    <label for="brand-company">Parent company</label>
                    <select id="brand-company" name="company_id" required>
                        <option value="">Choose company</option>
                        @foreach($filterCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <label for="brand-name">Brand name</label>
                    <input id="brand-name" name="manufacturer_name" type="text" maxlength="160" required placeholder="Example: Lenovo">
                    <label for="brand-code">Brand code</label>
                    <input id="brand-code" name="brand_code" type="text" maxlength="30" placeholder="Example: LEN">
                    <label class="ch-check"><input type="checkbox" name="publication_status" value="1" checked> Published</label>
                    <button class="btn btn-primary" type="submit">Add brand</button>
                </form>

                <form id="bulk-brands" method="post" action="{{ route('catalog-brands.bulk-delete') }}" onsubmit="return confirm('Delete selected unused brands?')">
                    {{ csrf_field() }}
                </form>
                <div class="ch-bulk">
                    <label><input type="checkbox" data-select-all="brand"> Select visible</label>
                    <button class="btn btn-danger btn-mini" form="bulk-brands" type="submit">Delete selected</button>
                </div>

                <div class="ch-list">
                    @forelse($brands as $brand)
                        <article class="ch-item">
                            <div class="ch-item-title">
                                <input type="checkbox" name="brand_ids[]" value="{{ $brand->manufacturer_id }}" form="bulk-brands" data-check-group="brand">
                                <strong>{{ $brand->manufacturer_name }}</strong>
                                <span class="ch-code">{{ $brand->brand_code ?: 'No code' }}</span>
                            </div>
                            <small>{{ optional($brand->company)->name ?: 'No company' }} &middot; {{ $brand->publication_status ? 'Published' : 'Hidden' }}</small>
                            <details>
                                <summary>Edit brand</summary>
                                <form method="post" action="{{ route('catalog-brands.update', $brand->manufacturer_id) }}">
                                    {{ csrf_field() }}
                                    {{ method_field('PATCH') }}
                                    <label>Parent company</label>
                                    <select name="company_id" required>
                                        @foreach($filterCompanies as $company)
                                            <option value="{{ $company->id }}" {{ (string) $brand->company_id === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    <label>Brand name</label>
                                    <input name="manufacturer_name" value="{{ $brand->manufacturer_name }}" required>
                                    <label>Brand code</label>
                                    <input name="brand_code" value="{{ $brand->brand_code }}" maxlength="30" placeholder="Example: LEN">
                                    <label class="ch-check"><input type="checkbox" name="publication_status" value="1" {{ $brand->publication_status ? 'checked' : '' }}> Published</label>
                                    <button class="btn btn-info" type="submit">Save</button>
                                </form>
                                <form method="post" action="{{ route('catalog-brands.destroy', $brand->manufacturer_id) }}" onsubmit="return confirm('Delete this unused brand?')">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </details>
                        </article>
                    @empty
                        <div class="ch-empty">No brands match the filters.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="ch-card">
            <div class="ch-head">
                <h2>3. Collection / Product Line</h2>
                <p>Product series or collection, such as ThinkPad, Summer Collection, or Premium Range.</p>
            </div>
            <div class="ch-body">
                @include('admin.components.data-transfer', ['resource' => 'series'])
                <form method="post" action="{{ route('catalog-series.store') }}">
                    {{ csrf_field() }}
                    <label for="series-brand">Brand</label>
                    <select id="series-brand" name="manufacturer_id" required>
                        <option value="">Choose brand</option>
                        @foreach($filterBrands as $brand)
                            <option value="{{ $brand->manufacturer_id }}">{{ $brand->manufacturer_name }} &mdash; {{ optional($brand->company)->name }}</option>
                        @endforeach
                    </select>
                    <label for="series-name">Series name</label>
                    <input id="series-name" name="name" type="text" maxlength="160" required placeholder="Example: ThinkPad">
                    <label for="series-code">Series code</label>
                    <input id="series-code" name="series_code" type="text" maxlength="30" placeholder="Example: TPD">
                    <label class="ch-check"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="btn btn-primary" type="submit">Add series</button>
                </form>

                <form id="bulk-series" method="post" action="{{ route('catalog-series.bulk-delete') }}" onsubmit="return confirm('Delete selected unused series?')">
                    {{ csrf_field() }}
                </form>
                <div class="ch-bulk">
                    <label><input type="checkbox" data-select-all="series"> Select visible</label>
                    <button class="btn btn-danger btn-mini" form="bulk-series" type="submit">Delete selected</button>
                </div>

                <div class="ch-list">
                    @forelse($series as $item)
                        <article class="ch-item">
                            <div class="ch-item-title">
                                <input type="checkbox" name="series_ids[]" value="{{ $item->id }}" form="bulk-series" data-check-group="series">
                                <strong>{{ $item->name }}</strong>
                                <span class="ch-code">{{ $item->series_code ?: 'No code' }}</span>
                            </div>
                            <small>{{ optional($item->brand)->manufacturer_name ?: 'No brand' }} &middot; {{ $item->is_active ? 'Active' : 'Inactive' }}</small>
                            <details>
                                <summary>Edit series</summary>
                                <form method="post" action="{{ route('catalog-series.update', $item->id) }}">
                                    {{ csrf_field() }}
                                    {{ method_field('PATCH') }}
                                    <label>Brand</label>
                                    <select name="manufacturer_id" required>
                                        @foreach($filterBrands as $brand)
                                            <option value="{{ $brand->manufacturer_id }}" {{ (string) $item->manufacturer_id === (string) $brand->manufacturer_id ? 'selected' : '' }}>{{ $brand->manufacturer_name }}</option>
                                        @endforeach
                                    </select>
                                    <label>Series name</label>
                                    <input name="name" value="{{ $item->name }}" required>
                                    <label>Series code</label>
                                    <input name="series_code" value="{{ $item->series_code }}" maxlength="30" placeholder="Example: TPD">
                                    <label class="ch-check"><input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}> Active</label>
                                    <button class="btn btn-info" type="submit">Save</button>
                                </form>
                                <form method="post" action="{{ route('catalog-series.destroy', $item->id) }}" onsubmit="return confirm('Delete this unused series?')">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </details>
                        </article>
                    @empty
                        <div class="ch-empty">No product series match the filters.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function refreshUniform(targets) {
        if (window.jQuery && jQuery.uniform && jQuery.uniform.update) {
            jQuery.uniform.update(targets);
        }
    }

    document.querySelectorAll('[data-select-all]').forEach(function (toggle) {
        var group = toggle.getAttribute('data-select-all');
        var boxes = document.querySelectorAll('[data-check-group="' + group + '"]');

        function syncToggle() {
            var checked = document.querySelectorAll('[data-check-group="' + group + '"]:checked').length;
            toggle.checked = boxes.length > 0 && checked === boxes.length;
            toggle.indeterminate = checked > 0 && checked < boxes.length;
            refreshUniform(toggle);
        }

        toggle.addEventListener('change', function () {
            boxes.forEach(function (box) {
                box.checked = toggle.checked;
                box.indeterminate = false;
            });
            toggle.indeterminate = false;
            refreshUniform(boxes);
            refreshUniform(toggle);
        });

        boxes.forEach(function (box) {
            box.addEventListener('change', function () {
                syncToggle();
                refreshUniform(box);
            });
        });
    });
});
</script>
@endsection
