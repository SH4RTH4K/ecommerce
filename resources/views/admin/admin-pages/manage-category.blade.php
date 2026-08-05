@extends('admin.admin-master')
@section('admin_main_content')
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
            <div class="box-content">
                <form id="bulk-category-form" method="post" action="{{ url('/manage-category/bulk-delete') }}">{{ csrf_field() }}
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><button id="bulk-category-button" type="submit" class="btn btn-danger" disabled><i class="halflings-icon white trash"></i> Delete selected</button><span id="bulk-category-count" class="muted">0 selected</span></div>
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
@endsection
