@extends('admin.admin-master')
@section('admin_main_content')
<link rel="stylesheet" href="{{ asset('css/admin-list-filters.css') }}?v={{ filemtime(public_path('css/admin-list-filters.css')) }}">
<div id="content" class="span10">


    <ul class="breadcrumb">
        <li>
            <i class="icon-home"></i>
            <a href="#">Home</a> 
            <i class="icon-angle-right"></i>
        </li>
        <li><a href="#">Manage Category</a></li>
    </ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    @php
        $catalogSourceAddress = catalog_import_source_address($siteSettings->get('catalog_import_source_address'));
        $catalogSourceLabel = catalog_import_source_label($catalogSourceAddress);
    @endphp
    <div style="margin-bottom:16px"><a class="btn btn-primary" href="{{ url('/add-category') }}"><i class="halflings-icon white plus"></i> Add Category</a></div>
    @include('admin.components.startech-import', [
        'title' => $catalogSourceLabel.' category import',
        'description' => 'Pull the live category tree from the current source address and store it here before creating subcategories or products.',
        'stepLabels' => ['categories' => 'Categories'],
        'selectedSteps' => ['categories'],
        'submitLabel' => 'Import '.$catalogSourceLabel.' categories',
        'helpText' => 'This runs only the category step from the saved source address.',
        'noteTitle' => 'Use this for category setup',
        'noteBody' => 'Use this when you only want the category level imported from the current source. You can run the full hierarchy import from Companies, Brands & Product Lines.',
    ])
    @include('admin.components.data-transfer',['resource'=>'categories'])

    @php
        $featuredCategoryIds = $featured_category_info->where('is_featured', 1)->pluck('category_id')->map(fn ($id) => (int) $id)->all();
    @endphp
    <div class="box" style="margin-bottom:20px">
        <div class="box-header" data-original-title>
            <h2><i class="halflings-icon star"></i><span class="break"></span>Featured Categories</h2>
        </div>
        <div class="box-content">
            <p class="muted">Choose multiple published categories for the homepage, then drag the selected items below into the order you want to show.</p>
            <form method="post" action="{{ url('/manage-category/featured') }}" id="featured-category-form">
                {{ csrf_field() }}
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                    <input type="search" id="featured-category-search" placeholder="Search categories..." style="margin:0;max-width:280px">
                    <button type="button" class="btn" id="featured-select-visible">Select visible</button>
                    <button type="button" class="btn" id="featured-clear">Clear all</button>
                    <span class="muted"><strong id="featured-count">{{ count($featuredCategoryIds) }}</strong> selected</span>
                </div>
                <div id="featured-category-order" style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;min-height:42px;margin:0 0 12px;padding:8px;border:1px dashed #cbd7df;border-radius:4px;background:#f8fafb">
                    <span class="muted" data-order-empty {{ count($featuredCategoryIds) ? 'style=display:none' : '' }}>No categories selected yet.</span>
                    @foreach($featured_category_info->where('publication_status', 1)->where('is_featured', 1)->sortBy('display_order') as $category)
                        <button type="button" class="featured-order-item" draggable="true" data-id="{{ $category->category_id }}" style="border:1px solid #c8d4dc;border-radius:3px;padding:6px 9px;background:#fff;cursor:grab">
                            <i class="fa fa-arrows" aria-hidden="true"></i> {{ $category->category_name }}
                        </button>
                    @endforeach
                </div>
                <div id="featured-category-options" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px;max-height:300px;overflow:auto;padding:4px">
                    @foreach($featured_category_info->where('publication_status', 1) as $category)
                        <label class="featured-category-option" data-name="{{ strtolower($category->category_name) }}" style="border:1px solid #ddd;border-radius:3px;padding:8px;background:#fafafa;cursor:pointer">
                            <input type="checkbox" name="featured_category_ids[]" value="{{ $category->category_id }}" {{ in_array((int) $category->category_id, $featuredCategoryIds, true) ? 'checked' : '' }}>
                            @if($category->icon_image)<img src="{{ asset($category->icon_image) }}" alt="" style="width:16px;height:16px;object-fit:contain;vertical-align:middle">@else<i class="fa {{ $category->icon_class ?: 'fa-folder-open' }}"></i>@endif
                            {{ $category->category_name }}
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:14px"><i class="halflings-icon white ok"></i> Save featured categories</button>
            </form>
        </div>
    </div>

    <div class="row-fluid sortable">		
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon user"></i><span class="break"></span>Manage Category</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content admin-filtered-list">
                <form class="admin-list-filters" method="get" action="{{ url('/manage-category') }}">
                    <label class="admin-filter-search">Search<input type="search" name="search" value="{{ request('search') }}" placeholder="Name, code, or category ID"></label>
                    <label>Status<select name="status"><option value="">All statuses</option><option value="1" {{ request('status')==='1'?'selected':'' }}>Published</option><option value="0" {{ request('status')==='0'?'selected':'' }}>Unpublished</option></select></label>
                    <label>Featured<select name="featured"><option value="">All categories</option><option value="1" {{ request('featured')==='1'?'selected':'' }}>Featured</option><option value="0" {{ request('featured')==='0'?'selected':'' }}>Not featured</option></select></label>
                    <label>Navigation<select name="navbar"><option value="">All navigation states</option><option value="1" {{ request('navbar')==='1'?'selected':'' }}>Shown in navigation</option><option value="0" {{ request('navbar')==='0'?'selected':'' }}>Hidden from navigation</option></select></label>
                    <div class="admin-filter-actions"><button type="submit" class="btn btn-primary"><i class="icon-filter icon-white"></i> Apply filters</button><a class="btn" href="{{ url('/manage-category') }}">Clear</a></div>
                    <span class="admin-filter-result">{{ $all_category_info->count() }} matching categories</span>
                </form>
                <form id="bulk-category-form" method="post" action="{{ url('/manage-category/bulk-delete') }}">{{ csrf_field() }}
                <div class="admin-bulk-actions" style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><button id="bulk-category-button" type="submit" class="btn btn-danger" disabled><i class="halflings-icon white trash"></i> Delete selected</button><span id="bulk-category-count" class="muted">0 selected</span></div>
                <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all-categories" aria-label="Select all categories"></th>
                            <th>Category ID</th>
                            <th>Category Name</th>
                            <th>Category Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>   
                    <tbody>
                        <?php
                            foreach ($all_category_info as $vcategory)
                            {
                        ?>
                        <tr>
                            <td><input type="checkbox" class="bulk-row-checkbox" name="category_ids[]" value="{{ $vcategory->category_id }}" aria-label="Select {{ $vcategory->category_name }}"></td>
                            <td>{{$vcategory->category_id}}</td>
                            <td class="center">{{$vcategory->category_name}}</td>
                            <td class="center">{{ $vcategory->category_code ?: '-' }}</td>
                            <td class="center">
                                <?php
                                if($vcategory->publication_status==1)
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
                                if($vcategory->publication_status==1)
                                {
                                ?>
                                <button type="submit" class="btn btn-danger" formaction="{{URL::to('/unpublished-category/'.$vcategory->category_id)}}" formmethod="post" aria-label="Unpublish category">
                                    <i class="halflings-icon white thumbs-down"></i>  
                                </button>
                                <?php
                                }
                                else{
                                ?>
                                <button type="submit" class="btn btn-success" formaction="{{URL::to('/published-category/'.$vcategory->category_id)}}" formmethod="post" aria-label="Publish category">
                                    <i class="halflings-icon white thumbs-up"></i>  
                                </button>
                                <?php
                                }
                                ?>
                                <a class="btn btn-info" href="{{URL::to('/edit-category/'.$vcategory->category_id)}}">
                                    <i class="halflings-icon white edit"></i>  
                                </a>
                                <button type="submit" class="btn btn-danger" formaction="{{URL::to('/delete-category/'.$vcategory->category_id)}}" formmethod="post" onclick="return checkDelete()" aria-label="Delete category">
                                    <i class="halflings-icon white trash"></i> 
                                </button>
                            </td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table></form>
            </div>
        </div><!--/span-->
    </div><!--/row-->
</div><!--/.fluid-container-->
@include('admin.components.bulk-delete-script',['formId'=>'bulk-category-form','selectAllId'=>'select-all-categories','buttonId'=>'bulk-category-button','counterId'=>'bulk-category-count','itemLabel'=>'categories'])
<script>
document.addEventListener('DOMContentLoaded', function () {
    var search = document.getElementById('featured-category-search');
    var options = Array.prototype.slice.call(document.querySelectorAll('.featured-category-option'));
    var count = document.getElementById('featured-count');
    var order = document.getElementById('featured-category-order');
    var empty = order.querySelector('[data-order-empty]');
    function selectedInput(id) { return document.querySelector('#featured-category-options input[value="' + id + '"]'); }
    function refreshOrder() {
        var checked = {};
        document.querySelectorAll('#featured-category-options input:checked').forEach(function (input) { checked[input.value] = true; });
        Array.prototype.slice.call(order.querySelectorAll('.featured-order-item')).forEach(function (item) {
            if (!checked[item.dataset.id]) item.remove();
        });
        options.forEach(function (option) {
            var input = option.querySelector('input');
            if (input.checked && !order.querySelector('.featured-order-item[data-id="' + input.value + '"]')) {
                var item = document.createElement('button');
                item.type = 'button'; item.draggable = true; item.className = 'featured-order-item'; item.dataset.id = input.value;
                item.style.cssText = 'border:1px solid #c8d4dc;border-radius:3px;padding:6px 9px;background:#fff;cursor:grab';
                item.innerHTML = '<i class="fa fa-arrows" aria-hidden="true"></i> ' + option.textContent.trim();
                order.appendChild(item);
            }
        });
        empty.style.display = order.querySelector('.featured-order-item') ? 'none' : '';
        count.textContent = Object.keys(checked).length;
    }
    function addOrderFields(form) {
        form.querySelectorAll('input[name="featured_category_order[]"]').forEach(function (input) { input.remove(); });
        order.querySelectorAll('.featured-order-item').forEach(function (item) {
            var input = document.createElement('input'); input.type = 'hidden'; input.name = 'featured_category_order[]'; input.value = item.dataset.id; form.appendChild(input);
        });
    }
    function filter() {
        var term = search.value.toLowerCase().trim();
        options.forEach(function (option) { option.style.display = !term || option.dataset.name.indexOf(term) !== -1 ? '' : 'none'; });
    }
    search.addEventListener('input', filter);
    document.getElementById('featured-select-visible').addEventListener('click', function () {
        options.forEach(function (option) { if (option.style.display !== 'none') option.querySelector('input').checked = true; });
        refreshOrder();
    });
    document.getElementById('featured-clear').addEventListener('click', function () {
        document.querySelectorAll('#featured-category-options input').forEach(function (input) { input.checked = false; });
        refreshOrder();
    });
    document.querySelectorAll('#featured-category-options input').forEach(function (input) { input.addEventListener('change', refreshOrder); });
    order.addEventListener('dragstart', function (event) { if (event.target.classList.contains('featured-order-item')) event.dataTransfer.setData('text/plain', event.target.dataset.id); });
    order.addEventListener('dragover', function (event) { event.preventDefault(); });
    order.addEventListener('drop', function (event) {
        event.preventDefault();
        var id = event.dataTransfer.getData('text/plain');
        var item = order.querySelector('.featured-order-item[data-id="' + id + '"]');
        var target = event.target.closest('.featured-order-item');
        if (!item || !target || item === target) return;
        order.insertBefore(item, event.clientX < target.getBoundingClientRect().left + target.offsetWidth / 2 ? target : target.nextSibling);
    });
    document.getElementById('featured-category-form').addEventListener('submit', function () { addOrderFields(this); });
    refreshOrder();
});
</script>
@endsection
