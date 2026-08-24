@extends('admin.admin-master')
@section('admin_main_content')
<div id="content" class="span10">


    <ul class="breadcrumb">
        <li>
            <i class="icon-home"></i>
            <a href="#">Home</a> 
            <i class="icon-angle-right"></i>
        </li>
        <li><a href="#">Manage Manufacture</a></li>
    </ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    @php
        $catalogSourceAddress = catalog_import_source_address($siteSettings->get('catalog_import_source_address'));
        $catalogSourceLabel = catalog_import_source_label($catalogSourceAddress);
        $seriesImportOptions = isset($manufacturerOptions) ? $manufacturerOptions->pluck('manufacturer_name', 'manufacturer_id')->all() : [];
    @endphp
    <div style="margin-bottom:16px"><a class="btn btn-primary" href="{{ url('/add-manufacturer') }}"><i class="halflings-icon white plus"></i> Add Manufacturer</a></div>
    @include('admin.components.startech-import', [
        'title' => $catalogSourceLabel.' source import for series',
        'description' => 'Fetch the live source hierarchy first, then pick the brand that should receive imported product series.',
        'sourceAddress' => $catalogSourceAddress,
        'stepLabels' => [
            'series' => 'Series',
        ],
        'selectedSteps' => ['series'],
        'submitLabel' => 'Import source series',
        'helpText' => 'Use Fetch only to preview the selected source first, then import the series names that belong to one brand.',
        'noteTitle' => 'Series import scope',
        'noteBody' => 'Pick one brand to limit the import, or leave All brands selected to import series for every supported manufacturer.',
        'scopeSelectLabel' => 'Brand',
        'scopeSelectName' => 'manufacturer_id',
        'scopeSelectOptions' => $seriesImportOptions,
        'scopeSelectPlaceholder' => 'All brands',
        'scopeSelectHelp' => 'Choose a brand to import only its source series.',
    ])
    @include('admin.components.data-transfer',['resource'=>'manufacturers'])

    <div class="box" style="margin-bottom:20px">
        <div class="box-header" data-original-title>
            <h2><i class="halflings-icon star"></i><span class="break"></span>Featured Brands</h2>
        </div>
        <div class="box-content">
            <p class="muted">Choose the published brands that appear in the homepage “Popular Brands” section. The display order follows the brand name.</p>
            <form method="post" action="{{ url('/manage-manufacturer/featured') }}" id="featured-brand-form">
                {{ csrf_field() }}
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                    <input type="search" id="featured-brand-search" placeholder="Search brands..." style="margin:0;max-width:280px">
                    <button type="button" class="btn" id="featured-brand-select-all">Select all</button>
                    <button type="button" class="btn" id="featured-brand-select-visible">Select visible</button>
                    <button type="button" class="btn" id="featured-brand-clear">Clear all</button>
                    <span class="muted"><strong id="featured-brand-count">{{ count($featuredBrandIds) }}</strong> selected</span>
                </div>
                <div id="featured-brand-options" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px;max-height:300px;overflow:auto;padding:4px">
                    @foreach($manufacturerOptions->where('publication_status', 1) as $brand)
                        <label class="featured-brand-option" data-name="{{ strtolower($brand->manufacturer_name) }}" style="border:1px solid #ddd;border-radius:3px;padding:8px;background:#fafafa;cursor:pointer">
                            <input type="checkbox" name="featured_brand_ids[]" value="{{ $brand->manufacturer_id }}" {{ in_array((int) $brand->manufacturer_id, $featuredBrandIds, true) ? 'checked' : '' }}>
                            {{ $brand->manufacturer_name }}
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:14px"><i class="halflings-icon white ok"></i> Save featured brands</button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var search = document.getElementById('featured-brand-search');
        var options = Array.prototype.slice.call(document.querySelectorAll('.featured-brand-option'));
        var count = document.getElementById('featured-brand-count');
        function refresh() { count.textContent = document.querySelectorAll('#featured-brand-options input:checked').length; }
        function visible() { var term = (search.value || '').toLowerCase().trim(); return options.filter(function (option) { return !option.hidden && (!term || option.dataset.name.indexOf(term) !== -1); }); }
        search.addEventListener('input', function () { options.forEach(function (option) { option.hidden = !!search.value.trim() && option.dataset.name.indexOf(search.value.toLowerCase().trim()) === -1; }); });
        function setChecked(option, checked) {
            var input = option.querySelector('input');
            if (input.checked !== checked) input.click();
        }
        document.getElementById('featured-brand-select-all').addEventListener('click', function () { options.forEach(function (option) { setChecked(option, true); }); refresh(); });
        document.getElementById('featured-brand-select-visible').addEventListener('click', function () { visible().forEach(function (option) { setChecked(option, true); }); refresh(); });
        document.getElementById('featured-brand-clear').addEventListener('click', function () { options.forEach(function (option) { setChecked(option, false); }); refresh(); });
        options.forEach(function (option) { option.querySelector('input').addEventListener('change', refresh); });
    }());
    </script>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon user"></i><span class="break"></span>Manage Manufacturers</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content">
                <form id="bulk-manufacturer-form" method="post" action="{{ url('/manage-manufacturer/bulk-delete') }}">{{ csrf_field() }}
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><button id="bulk-manufacturer-button" type="submit" class="btn btn-danger" disabled><i class="halflings-icon white trash"></i> Delete selected</button><span id="bulk-manufacturer-count" class="muted">0 selected</span></div>
                <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all-manufacturers" aria-label="Select all manufacturers"></th>
                            <th>ID</th>
                            <th>Parent Company</th>
                            <th>Brand Name</th>
                            <th>Brand Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead> 

                    <tbody>
                        <?php
                        foreach ($all_manufacturer as $vmanufacturer) 
                            {
                        
                        ?>
                        <tr>
                            <td><input type="checkbox" class="bulk-row-checkbox" name="manufacturer_ids[]" value="{{ $vmanufacturer->manufacturer_id }}" aria-label="Select {{ $vmanufacturer->manufacturer_name }}"></td>
                            <td>{{$vmanufacturer->manufacturer_id}}</td>
                            <td class="center">{{ $vmanufacturer->company_name ?: '-' }}</td>
                            <td class="center">{{$vmanufacturer->manufacturer_name}}</td>
                            <td class="center">{{ $vmanufacturer->brand_code ?: '-' }}</td>
                            <td class="center">
                                <?php
                                if($vmanufacturer->publication_status==1)
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
                                if($vmanufacturer->publication_status==1)
                                {
                                ?>
                                <button type="submit" class="btn btn-danger" formaction="{{URL::to('/unpublished-manufacturer/'.$vmanufacturer->manufacturer_id)}}" formmethod="post" aria-label="Unpublish manufacturer">
                                    <i class="halflings-icon white thumbs-down"></i>  
                                </button>
                                <?php
                                }
                                else{
                                ?>
                                <button type="submit" class="btn btn-success" formaction="{{URL::to('/published-manufacturer/'.$vmanufacturer->manufacturer_id)}}" formmethod="post" aria-label="Publish manufacturer">
                                    <i class="halflings-icon white thumbs-up"></i>  
                                </button>
                                <?php
                                }
                                ?>
                                <a class="btn btn-info" href="{{URL::to('/edit-manufacturer/'.$vmanufacturer->manufacturer_id)}}">
                                    <i class="halflings-icon white edit"></i>  
                                </a>
                                <button type="submit" class="btn btn-danger" formaction="{{URL::to('/delete-manufacturer/'.$vmanufacturer->manufacturer_id)}}" formmethod="post" onclick="return checkDelete()" aria-label="Delete manufacturer">
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
@include('admin.components.bulk-delete-script',['formId'=>'bulk-manufacturer-form','selectAllId'=>'select-all-manufacturers','buttonId'=>'bulk-manufacturer-button','counterId'=>'bulk-manufacturer-count','itemLabel'=>'manufacturers'])
@endsection
