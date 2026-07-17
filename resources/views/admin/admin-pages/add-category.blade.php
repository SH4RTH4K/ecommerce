@extends('admin.admin-master')
@section('admin_main_content')

<div id="content" class="span10">
    <ul class="breadcrumb">
        <li>
            <i class="icon-home"></i>
            <a href="#">Home</a>
            <i class="icon-angle-right"></i> 
        </li>
        <li>
            <i class="icon-edit"></i>
            <a href="#">Add Category</a>
        </li>
    </ul>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon edit"></i><span class="break"></span>Add Category</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content">
                <h3 style="color: green">
                    <?php
                    $message=Session::get('message');
                    if($message){
                        echo $message;
                        Session::put('message','');
                    }
                    ?>
                </h3>
                {!! Form::open(['url' => '/save-category', 'method' => 'post']) !!}
                <fieldset class="form-horizontal">
                        <div class="control-group">
                            <label class="control-label" for="typeahead">Category Name </label>
                            <div class="controls">
                                <input type="text" name="category_name" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                            </div>
                        </div>    
                        <div class="control-group hidden-phone">
                            <label class="control-label" for="textarea2" >Category Description</label>
                            <div class="controls">
                                <textarea name="category_description" class="cleditor" rows="3"></textarea>
                            </div>
                        </div>
                        @php $categoryIcons = ['fa-folder-open'=>'Folder','fa-desktop'=>'Desktop','fa-laptop'=>'Laptop','fa-keyboard-o'=>'Keyboard','fa-mouse-pointer'=>'Mouse','fa-print'=>'Printer','fa-hdd-o'=>'Storage / HDD','fa-picture-o'=>'Graphics','fa-refresh'=>'Cooling','fa-archive'=>'Casing / Box','fa-link'=>'Cable / Connector','fa-signal'=>'Network / Wireless','fa-video-camera'=>'Camera','fa-camera'=>'Webcam / Camera','fa-headphones'=>'Headphones','fa-music'=>'Audio','fa-volume-up'=>'Speaker','fa-gamepad'=>'Gaming','fa-dot-circle-o'=>'Optical Disc','fa-bolt'=>'Power / UPS','fa-clock-o'=>'Watch','fa-mobile'=>'Mobile','fa-cogs'=>'Components','fa-shield'=>'Security','fa-globe'=>'Internet','fa-sitemap'=>'Network Structure','fa-shopping-cart'=>'Shopping']; @endphp
                        <div class="control-group">
                            <label class="control-label" for="icon_class">Category Icon</label>
                            <div class="controls"><select name="icon_class" id="icon_class" class="span6">@foreach($categoryIcons as $class => $label)<option value="{{$class}}">{{$label}} ({{$class}})</option>@endforeach</select> <span id="category-icon-preview" style="font-size:24px;margin-left:12px"><i class="fa fa-folder-open"></i></span></div>
                        </div>
                        <div class="control-group"><label class="control-label" for="display_order">Homepage Order</label><div class="controls"><input type="number" min="0" name="display_order" id="display_order" value="0" class="span2"><span class="help-inline">Lower numbers appear first.</span></div></div>
                        <div class="control-group"><label class="control-label">Featured Categories</label><div class="controls"><label class="checkbox"><input type="checkbox" name="is_featured" value="1" checked> Show on homepage</label></div></div>
                        <div class="control-group">
                            <label class="control-label" for="selectError3">Publication Status</label>
                            <div class="controls">
                                <select id="selectError3" name="publication_status">
                                    <option value="0">Unpublished</option>
                                    <option value="1">Published</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <button type="reset" class="btn">Cancel</button>
                        </div>
                    </fieldset>
                {!! Form::close() !!}   
                <script>document.getElementById('icon_class').addEventListener('change',function(){document.getElementById('category-icon-preview').innerHTML='<i class="fa '+this.value+'"></i>';});</script>

            </div>
        </div><!--/span-->

    </div><!--/row-->
</div><!--/.fluid-container-->

<!-- end: Content -->
@endsection
