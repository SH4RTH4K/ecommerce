@extends('admin.admin-master')
@section('title','Catalog Import Center - '.$brandName)
@section('admin_main_content')
@php
    $catalogSourceAddress = catalog_import_source_address($siteSettings->get('catalog_import_source_address'));
    $catalogSourceLabel = catalog_import_source_label($catalogSourceAddress);
    $importSections = [
        ['resource' => 'categories', 'title' => 'Categories', 'description' => 'Import the main category tree and homepage ordering.'],
        ['resource' => 'subcategories', 'title' => 'Subcategories', 'description' => 'Import child categories tied to a parent category.'],
        ['resource' => 'companies', 'title' => 'Companies', 'description' => 'Import manufacturer groups or parent companies.'],
        ['resource' => 'manufacturers', 'title' => 'Brands', 'description' => 'Import customer-facing brand names.'],
        ['resource' => 'series', 'title' => 'Product Series', 'description' => 'Import collection and product-line names.'],
        ['resource' => 'attributes', 'title' => 'Catalog Attributes', 'description' => 'Import filters and comparison attributes.'],
        ['resource' => 'products', 'title' => 'Products', 'description' => 'Import the product catalog itself.'],
        ['resource' => 'suppliers', 'title' => 'Suppliers', 'description' => 'Import vendor and supplier records.'],
        ['resource' => 'locations', 'title' => 'Stock Locations', 'description' => 'Import warehouses, stores, and fulfillment hubs.'],
    ];
@endphp
<style>
.ci{padding:26px}.ci-hero{background:linear-gradient(135deg,#073b5c,#0f6b8f);color:#fff;border-radius:14px;padding:25px;margin-bottom:20px;box-shadow:0 10px 22px rgba(10,48,70,.12)}.ci-hero h1{margin:0 0 7px;font-size:27px}.ci-hero p{margin:0;opacity:.88;max-width:900px}.ci-note{margin-top:15px;display:flex;flex-wrap:wrap;gap:8px}.ci-chip{background:rgba(255,255,255,.12);padding:7px 11px;border-radius:999px;font-size:12px;font-weight:700}.ci-section{margin-top:24px}.ci-section-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-end;margin-bottom:12px}.ci-section-head h2{margin:0;font-size:21px;color:#123f61}.ci-section-head p{margin:0;color:#627685}.ci-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.ci-grid .dt-card{margin:0}.ci-source-panel{margin-top:18px}@media(max-width:1000px){.ci-grid{grid-template-columns:1fr}.ci-section-head{align-items:flex-start;flex-direction:column}}
</style>
<main id="content" class="span10 ci">
    <header class="ci-hero">
        <h1>Catalog Import Center</h1>
        <p>Use this page to import catalog data from the current source address and to manage all catalog CSV imports in one place.</p>
        <div class="ci-note">
            <span class="ci-chip">Current source address: {{ $catalogSourceAddress }}</span>
            <span class="ci-chip">CSV templates, export, and import</span>
            <span class="ci-chip">Categories, brands, products, suppliers, and more</span>
        </div>
    </header>

    <div class="ci-source-panel">
        @include('admin.components.startech-import', [
            'title' => $catalogSourceLabel.' source import',
            'description' => 'Fetch the live source data first, then choose which catalog steps to import and continue with the CSV tools on this page.',
            'sourceAddress' => $catalogSourceAddress,
            'stepLabels' => [
                'categories' => 'Categories',
                'subcategories' => 'Subcategories',
                'brands' => 'Brands',
                'series' => 'Series',
                'products' => 'Products',
            ],
            'submitLabel' => 'Import source categories, brands, series, and products',
            'helpText' => 'Use Fetch only to preview the selected source first. Save the source address if it changes, then uncheck any steps you do not want before importing.',
            'noteTitle' => 'Preview first, then import',
            'noteBody' => 'The preview shows what the current source returned, so you can decide which parts of the hierarchy or product feed to import next.',
            'importState' => $startechProductImportState ?? null,
        ])
    </div>

    <section class="ci-section">
        <div class="ci-section-head">
            <div>
                <h2>Catalog CSV Imports</h2>
                <p>Each card includes the template, export, and import form for that dataset.</p>
            </div>
        </div>
        <div class="ci-grid">
            @foreach($importSections as $section)
                @include('admin.components.data-transfer', ['resource' => $section['resource']])
            @endforeach
        </div>
    </section>
</main>
@endsection
