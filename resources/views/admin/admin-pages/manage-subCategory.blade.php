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

    <div class="row-fluid sortable">		
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon user"></i><span class="break"></span>Manage Manufacture</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content">
                <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
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
            </div>
        </div><!--/span-->
    </div><!--/row-->
</div><!--/.fluid-container-->
@endsection