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
        <li><a href="#">Manage Sub Category</a></li>
    </ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    @php
        $catalogSourceAddress = catalog_import_source_address($siteSettings->get('catalog_import_source_address'));
        $catalogSourceLabel = catalog_import_source_label($catalogSourceAddress);
        $subcategoryImportOptions = isset($categories) ? $categories->pluck('category_name', 'category_id')->all() : [];
    @endphp
    <div style="margin-bottom:16px"><a class="btn btn-primary" href="{{ url('/add-subCategory') }}"><i class="halflings-icon white plus"></i> Add Subcategory</a></div>
    @include('admin.components.startech-import', [
        'title' => $catalogSourceLabel.' source import for subcategories',
        'description' => 'Fetch the live source hierarchy first, then choose which category should receive the imported subcategories.',
        'sourceAddress' => $catalogSourceAddress,
        'stepLabels' => [
            'subcategories' => 'Subcategories',
        ],
        'selectedSteps' => ['subcategories'],
        'submitLabel' => 'Import source subcategories',
        'helpText' => 'Use Fetch only to preview the selected source first, then import the subcategory set you want to keep in sync.',
        'noteTitle' => 'Subcategory import scope',
        'noteBody' => 'Pick one category to limit the import, or leave All categories selected to import subcategories for every mapped category in the source.',
        'scopeSelectLabel' => 'Category',
        'scopeSelectName' => 'category_id',
        'scopeSelectOptions' => $subcategoryImportOptions,
        'scopeSelectPlaceholder' => 'All categories',
        'scopeSelectHelp' => 'Choose a category to import only its source subcategories.',
    ])
    @include('admin.components.data-transfer',['resource'=>'subcategories'])

    <div class="row-fluid sortable">		
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon user"></i><span class="break"></span>Manage Subcategories</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content admin-filtered-list">
                <form class="admin-list-filters" method="get" action="{{ url('/manage-subCategory') }}">
                    <label class="admin-filter-search">Search<input type="search" name="search" value="{{ request('search') }}" placeholder="Subcategory, code, parent, or ID"></label>
                    <label>Parent category<select name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" {{ (string)request('category_id')===(string)$category->category_id?'selected':'' }}>{{ $category->category_name }}</option>@endforeach</select></label>
                    <label>Status<select name="status"><option value="">All statuses</option><option value="1" {{ request('status')==='1'?'selected':'' }}>Published</option><option value="0" {{ request('status')==='0'?'selected':'' }}>Unpublished</option></select></label>
                    <label>Navbar<select name="navbar"><option value="">All navbar states</option><option value="1" {{ request('navbar')==='1'?'selected':'' }}>Shown in navbar</option><option value="0" {{ request('navbar')==='0'?'selected':'' }}>Hidden from navbar</option></select></label>
                    <div class="admin-filter-footer">
                        <output class="admin-filter-result" aria-live="polite"><strong>{{ number_format($category_details->count()) }}</strong> {{ \Illuminate\Support\Str::plural('subcategory', $category_details->count()) }} found</output>
                        <div class="admin-filter-actions"><button type="submit" class="btn btn-primary"><i class="icon-filter icon-white"></i> Apply filters</button><a class="btn" href="{{ url('/manage-subCategory') }}">Clear filters</a></div>
                    </div>
                </form>
                <form id="bulk-subcategory-form" method="post" action="{{ url('/manage-subCategory/bulk-delete') }}">
                {{ csrf_field() }}
                <div class="admin-bulk-actions" style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                    <button id="bulk-delete-button" type="submit" class="btn btn-danger" disabled><i class="halflings-icon white trash"></i> Delete selected</button>
                    <span id="selected-subcategory-count" class="muted">0 selected</span>
                </div>
                <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all-subcategories" data-no-uniform="true" aria-label="Select all subcategories"></th>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Sub Category Name</th>
                            <th>Subcategory Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead> 

                    <tbody>
                        <?php
                        foreach ($category_details as $vsubcategory) 
                            {
                        
                        ?>
                        <tr>
                            <td><input type="checkbox" class="subcategory-checkbox" name="sub_category_ids[]" value="{{ $vsubcategory->sub_category_id }}" data-no-uniform="true" aria-label="Select {{ $vsubcategory->sub_category_name }}"></td>
                            <td>{{$vsubcategory->sub_category_id}}</td>
                            <td>{{$vsubcategory->category_name}}</td>
                            <td class="center">{{$vsubcategory->sub_category_name}}</td>
                            <td class="center">{{ $vsubcategory->subcategory_code ?: '-' }}</td>
                            <td class="center">
                                <?php
                                if($vsubcategory->publication_status==1)
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
                                if($vsubcategory->publication_status==1)
                                {
                                ?>
                                <button type="submit" class="btn btn-danger" formaction="{{URL::to('/unpublished-subCategory/'.$vsubcategory->sub_category_id)}}" formmethod="post" aria-label="Unpublish subcategory">
                                    <i class="halflings-icon white thumbs-down"></i>  
                                </button>
                                <?php
                                }
                                else{
                                ?>
                                <button type="submit" class="btn btn-success" formaction="{{URL::to('/published-subCategory/'.$vsubcategory->sub_category_id)}}" formmethod="post" aria-label="Publish subcategory">
                                    <i class="halflings-icon white thumbs-up"></i>  
                                </button>
                                <?php
                                }
                                ?>
                                <a class="btn btn-info" href="{{URL::to('/edit-subCategory/'.$vsubcategory->sub_category_id)}}">
                                    <i class="halflings-icon white edit"></i>  
                                </a>
                                <button type="submit" class="btn btn-danger" formaction="{{URL::to('/delete-subCategory/'.$vsubcategory->sub_category_id)}}" formmethod="post" onclick="return checkDelete()" aria-label="Delete subcategory">
                                    <i class="halflings-icon white trash"></i> 
                                </button>
                            </td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
                </form>
            </div>
        </div><!--/span-->
    </div><!--/row-->
</div><!--/.fluid-container-->
<script>
document.addEventListener('DOMContentLoaded',function(){
    var form=document.getElementById('bulk-subcategory-form'),selectAll=document.getElementById('select-all-subcategories'),button=document.getElementById('bulk-delete-button'),counter=document.getElementById('selected-subcategory-count');
    if(!form)return;
    function boxes(){return Array.prototype.slice.call(form.querySelectorAll('.subcategory-checkbox'));}
    function update(){var all=boxes(),checked=all.filter(function(box){return box.checked;});counter.textContent=checked.length+' selected';button.disabled=checked.length===0;selectAll.checked=all.length>0&&checked.length===all.length;selectAll.indeterminate=checked.length>0&&checked.length<all.length;}
    selectAll.addEventListener('change',function(){boxes().forEach(function(box){box.checked=selectAll.checked;});update();});
    form.addEventListener('change',function(event){if(event.target.classList.contains('subcategory-checkbox'))update();});
    form.addEventListener('submit',function(event){
        // The row actions share this form for their formaction/formmethod. Only
        // apply bulk validation when the bulk-delete button submitted the form.
        if(event.submitter!==button)return;
        var count=boxes().filter(function(box){return box.checked;}).length;
        if(!count||!confirm('Delete '+count+' selected subcategor'+(count===1?'y':'ies')+'? Subcategories assigned to products will be skipped.'))event.preventDefault();
    });
    update();
});
</script>
@endsection
