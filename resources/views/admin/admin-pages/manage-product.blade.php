@extends('admin.admin-master')
@section('admin_main_content')
<div id="content" class="span10">


    <ul class="breadcrumb">
        <li>
            <i class="icon-home"></i>
            <a href="#">Home</a> 
            <i class="icon-angle-right"></i>
        </li>
        <li><a href="#">Manage Product</a></li>
    </ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    @php
        $catalogSourceAddress = catalog_import_source_address($siteSettings->get('catalog_import_source_address'));
        $catalogSourceLabel = catalog_import_source_label($catalogSourceAddress);
    @endphp
    <div style="margin-bottom:16px"><a class="btn btn-primary" href="{{ url('/add-product') }}"><i class="halflings-icon white plus"></i> Add Product</a></div>
    @include('admin.components.data-transfer',['resource'=>'products'])
    @include('admin.components.startech-import', [
        'title' => $catalogSourceLabel.' source import for products',
        'description' => 'Refresh the current catalog source structure and live product data before you create, edit, or assign products. You can narrow the product crawl to one source category or paste one product link to import only that item.',
        'stepLabels' => [
            'categories' => 'Categories',
            'subcategories' => 'Subcategories',
            'brands' => 'Brands',
            'series' => 'Series',
            'products' => 'Products',
        ],
        'submitLabel' => 'Refresh catalog source structure and products',
        'helpText' => 'Use this when the source changes and you want the hierarchy and product feed ready before product entry. A pasted product link imports only that product.',
        'noteTitle' => 'Recommended before product work',
        'noteBody' => 'Import the latest source hierarchy and product feed here, or paste a product page link to import only one item before you continue managing products against those categories, brands, and series.',
        'productCategoryOptions' => $productImportCategories ?? [],
        'allowSingleProductImport' => true,
        'importState' => $startechProductImportState ?? null,
    ])

    <div class="row-fluid sortable">		
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon user"></i><span class="break"></span>Manage Product</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content">
                <form id="bulk-product-form" method="post" action="{{ url('/manage-product/bulk-delete') }}">{{ csrf_field() }}
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                    <button id="bulk-product-publish" type="submit" class="btn btn-success" formaction="{{ url('/manage-product/bulk-publication') }}" name="publication_status" value="1" disabled><i class="halflings-icon white thumbs-up"></i> Publish selected</button>
                    <button id="bulk-product-unpublish" type="submit" class="btn btn-warning" formaction="{{ url('/manage-product/bulk-publication') }}" name="publication_status" value="0" disabled><i class="halflings-icon white thumbs-down"></i> Unpublish selected</button>
                    <button id="bulk-product-button" type="submit" class="btn btn-danger" disabled><i class="halflings-icon white trash"></i> Delete selected</button>
                    <span id="bulk-product-count" class="muted">0 selected</span>
                </div>
                </form>
                <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all-products" aria-label="Select all products"></th>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Product Code</th>
                            <th>Barcode</th>
                            <th>Regular Price</th>
                            <th>Offer Price</th>
                            <th>Product Image</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead> 

                    <tbody>
                        <?php
                        foreach ($all_product as $vproduct) 
                            {
                        
                        ?>
                        <tr>
                            <td><input type="checkbox" class="bulk-row-checkbox" name="product_ids[]" value="{{ $vproduct->id }}" form="bulk-product-form" aria-label="Select {{ $vproduct->product_name }}"></td>
                            <td>{{$vproduct->id}}</td>
                            <td class="center">{{$vproduct->product_name}}</td>
                            <td class="center">{{ $vproduct->product_code ?: $vproduct->sku ?: '—' }}</td>
                            <td class="center">{{$vproduct->barcode ?: '—'}}</td>
                            <td class="center">{{$vproduct->regular_price}}</td>
                            <td class="center">{{$vproduct->offer_price !== null && $vproduct->offer_price < $vproduct->regular_price ? $vproduct->offer_price : '—'}}</td>
                            <td class="center"><img src="{{asset($vproduct->product_image)}}" width="50" height="50"></td>
                            <td class="center">
                                <?php
                                if($vproduct->publication_status==1)
                                {
                                ?>
                                <span class="label label-success">Published</span>
                                <?php
                                }
                                else{
                                ?>
                                <span class="label label-important">Unpublished</span>
                                <?php
                                }
                                ?>
                            </td>
                            <td class="center">
                                <?php
                                if($vproduct->publication_status==1)
                                {
                                ?>
                                <form method="post" action="{{ URL::to('/unpublished-product/'.$vproduct->id) }}" style="display:inline">{{ csrf_field() }}<button type="submit" class="btn btn-danger" aria-label="Unpublish product"><i class="halflings-icon white thumbs-down"></i></button></form>
                                <?php
                                }
                                else{
                                ?>
                                <form method="post" action="{{ URL::to('/published-product/'.$vproduct->id) }}" style="display:inline">{{ csrf_field() }}<button type="submit" class="btn btn-success" aria-label="Publish product"><i class="halflings-icon white thumbs-up"></i></button></form>
                                <?php
                                }
                                ?>
                                <a class="btn btn-info" href="{{URL::to('/edit-product/'.$vproduct->id)}}">
                                    <i class="halflings-icon white edit"></i>  
                                </a>
                                <form method="post" action="{{ URL::to('/delete-product/'.$vproduct->id) }}" style="display:inline">{{ csrf_field() }}<button type="submit" class="btn btn-danger" onclick="return checkDelete()" aria-label="Delete product"><i class="halflings-icon white trash"></i></button></form>
                            </td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div><!--/span-->
    </div><!--/row-->
</div><!--/.fluid-container-->
@include('admin.components.bulk-delete-script',['formId'=>'bulk-product-form','selectAllId'=>'select-all-products','buttonId'=>'bulk-product-button','counterId'=>'bulk-product-count','itemLabel'=>'products'])
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('bulk-product-form');
    var publish = document.getElementById('bulk-product-publish');
    var unpublish = document.getElementById('bulk-product-unpublish');
    function syncPublicationButtons() {
        var hasSelection = document.querySelectorAll('#bulk-product-form .bulk-row-checkbox:checked, .bulk-row-checkbox[form="bulk-product-form"]:checked').length > 0;
        publish.disabled = !hasSelection;
        unpublish.disabled = !hasSelection;
    }
    document.querySelectorAll('#bulk-product-form .bulk-row-checkbox, .bulk-row-checkbox[form="bulk-product-form"]').forEach(function (checkbox) { checkbox.addEventListener('change', syncPublicationButtons); });
    var selectAll = document.getElementById('select-all-products');
    if (selectAll) selectAll.addEventListener('change', function () { setTimeout(syncPublicationButtons, 0); });
    syncPublicationButtons();
});
</script>
@endsection
