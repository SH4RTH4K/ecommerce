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
    <div style="margin-bottom:16px"><a class="btn btn-primary" href="{{ url('/add-product') }}"><i class="halflings-icon white plus"></i> Add Product</a></div>
    @include('admin.components.data-transfer',['resource'=>'products'])

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
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><button id="bulk-product-button" type="submit" class="btn btn-danger" disabled><i class="halflings-icon white trash"></i> Delete selected</button><span id="bulk-product-count" class="muted">0 selected</span></div>
                <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all-products" aria-label="Select all products"></th>
                            <th>ID</th>
                            <th>Product Name</th>
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
                            <td><input type="checkbox" class="bulk-row-checkbox" name="product_ids[]" value="{{ $vproduct->id }}" aria-label="Select {{ $vproduct->product_name }}"></td>
                            <td>{{$vproduct->id}}</td>
                            <td class="center">{{$vproduct->product_name}}</td>
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
                                <a class="btn btn-danger" href="{{URL::to('/unpublished-product/'.$vproduct->id)}}">
                                    <i class="halflings-icon white thumbs-down"></i>  
                                </a>
                                <?php
                                }
                                else{
                                ?>
                                <a class="btn btn-success" href="{{URL::to('/published-product/'.$vproduct->id)}}">
                                    <i class="halflings-icon white thumbs-up"></i>  
                                </a>
                                <?php
                                }
                                ?>
                                <a class="btn btn-info" href="{{URL::to('/edit-product/'.$vproduct->id)}}">
                                    <i class="halflings-icon white edit"></i>  
                                </a>
                                <a class="btn btn-danger" href="{{URL::to('/delete-product/'.$vproduct->id)}}" onclick="return checkDelete()">
                                    <i class="halflings-icon white trash"></i> 
                                </a>
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
@include('admin.components.bulk-delete-script',['formId'=>'bulk-product-form','selectAllId'=>'select-all-products','buttonId'=>'bulk-product-button','counterId'=>'bulk-product-count','itemLabel'=>'products'])
@endsection
