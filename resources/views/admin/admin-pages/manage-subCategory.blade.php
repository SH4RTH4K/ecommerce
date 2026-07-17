@extends('admin.admin-master')
@section('admin_main_content')
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
            <div class="box-content">
                <form id="bulk-subcategory-form" method="post" action="{{ url('/manage-subCategory/bulk-delete') }}">
                {{ csrf_field() }}
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                    <button id="bulk-delete-button" type="submit" class="btn btn-danger" disabled><i class="halflings-icon white trash"></i> Delete selected</button>
                    <span id="selected-subcategory-count" class="muted">0 selected</span>
                </div>
                <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all-subcategories" aria-label="Select all subcategories"></th>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Sub Category Name</th>
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
                            <td><input type="checkbox" class="subcategory-checkbox" name="sub_category_ids[]" value="{{ $vsubcategory->sub_category_id }}" aria-label="Select {{ $vsubcategory->sub_category_name }}"></td>
                            <td>{{$vsubcategory->sub_category_id}}</td>
                            <td>{{$vsubcategory->category_name}}</td>
                            <td class="center">{{$vsubcategory->sub_category_name}}</td>
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
                                <a class="btn btn-danger" href="{{URL::to('/unpublished-subCategory/'.$vsubcategory->sub_category_id)}}">
                                    <i class="halflings-icon white thumbs-down"></i>  
                                </a>
                                <?php
                                }
                                else{
                                ?>
                                <a class="btn btn-success" href="{{URL::to('/published-subCategory/'.$vsubcategory->sub_category_id)}}">
                                    <i class="halflings-icon white thumbs-up"></i>  
                                </a>
                                <?php
                                }
                                ?>
                                <a class="btn btn-info" href="{{URL::to('/edit-subCategory/'.$vsubcategory->sub_category_id)}}">
                                    <i class="halflings-icon white edit"></i>  
                                </a>
                                <a class="btn btn-danger" href="{{URL::to('/delete-subCategory/'.$vsubcategory->sub_category_id)}}" onclick="return checkDelete()">
                                    <i class="halflings-icon white trash"></i> 
                                </a>
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
    form.addEventListener('submit',function(event){var count=boxes().filter(function(box){return box.checked;}).length;if(!count||!confirm('Delete '+count+' selected subcategor'+(count===1?'y':'ies')+'? Subcategories assigned to products will be skipped.'))event.preventDefault();});
    update();
});
</script>
@endsection
