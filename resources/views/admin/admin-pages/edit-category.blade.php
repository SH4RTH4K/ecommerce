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
            <a href="#">Edit Category</a>
        </li>
    </ul>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon edit"></i><span class="break"></span>Edit Category</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content">
<!--                <h3 style="color: green">
                    <?php
                    $message=Session::get('message');
                    if($message){
                        echo $message;
                        Session::put('message','');
                    }
                    ?>
                </h3>-->
                <form action="{{ url('/update-category') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                <fieldset class="form-horizontal">
                        <div class="control-group">
                            <label class="control-label" for="typeahead">Category Name </label>
                            <div class="controls">
                                <input type="text" name="category_name" value="{{$category_info->category_name}}" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                                <input type="hidden" name="category_id" value="{{$category_info->category_id}}" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                            </div>
                        </div>    
                        <div class="control-group">
                            <label class="control-label" for="category_code">Category Code</label>
                            <div class="controls"><input type="text" name="category_code" id="category_code" value="{{ old('category_code', $category_info->category_code) }}" class="span3" maxlength="30" placeholder="Example: LAP"><p class="help-block">Optional. Keep the current code or change it for new naming rules.</p></div>
                        </div>
                        <div class="control-group hidden-phone">
                            <label class="control-label" for="textarea2" >Category Description</label>
                            <div class="controls">
                                <textarea name="category_description" class="cleditor" rows="3">{{$category_info->category_description}}</textarea>
                            </div>
                        </div>
                        @php $categoryIcons = ['fa-folder-open'=>'Folder','fa-desktop'=>'Desktop','fa-laptop'=>'Laptop','fa-keyboard-o'=>'Keyboard','fa-mouse-pointer'=>'Mouse','fa-print'=>'Printer','fa-hdd-o'=>'Storage / HDD','fa-picture-o'=>'Graphics','fa-refresh'=>'Cooling','fa-archive'=>'Casing / Box','fa-link'=>'Cable / Connector','fa-signal'=>'Network / Wireless','fa-video-camera'=>'Camera','fa-camera'=>'Webcam / Camera','fa-headphones'=>'Headphones','fa-music'=>'Audio','fa-volume-up'=>'Speaker','fa-gamepad'=>'Gaming','fa-dot-circle-o'=>'Optical Disc','fa-bolt'=>'Power / UPS','fa-clock-o'=>'Watch','fa-mobile'=>'Mobile','fa-cogs'=>'Components','fa-shield'=>'Security','fa-globe'=>'Internet','fa-sitemap'=>'Network Structure','fa-shopping-cart'=>'Shopping']; @endphp
                        <div class="control-group"><label class="control-label">Category Icon</label><div class="controls">
                            <div style="display:flex;align-items:center;gap:16px;max-width:720px;padding:14px;border:1px solid #d9e3e9;border-radius:10px;background:#f8fafb">
                                <div id="category-icon-preview" style="width:72px;height:72px;display:grid;place-items:center;flex:0 0 72px;border:1px solid #d9e3e9;border-radius:12px;background:#fff;color:#0b3d62;font-size:32px;overflow:hidden">
                                    @if($category_info->icon_image)<img src="{{ asset($category_info->icon_image) }}" alt="{{ $category_info->category_name }} icon" style="width:100%;height:100%;object-fit:contain">@else<i class="fa {{ $category_info->icon_class ?: 'fa-folder-open' }}" aria-hidden="true"></i>@endif
                                </div>
                                <div style="min-width:0;flex:1">
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                        <select name="icon_class" id="icon_class" style="width:220px;max-width:100%;margin:0">@foreach($categoryIcons as $class => $label)<option value="{{$class}}" {{$category_info->icon_class === $class ? 'selected' : ''}}>{{$label}}</option>@endforeach</select>
                                        <span style="color:#71828d;font-size:12px">Built-in fallback</span>
                                    </div>
                                    <div style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                        <input type="file" name="icon_image" id="icon_image" accept="image/png,image/jpeg,image/webp" style="max-width:260px;margin:0;padding:4px">
                                        <span style="color:#71828d;font-size:12px">PNG, JPG or WebP · max 1 MB</span>
                                    </div>
                                    @if($category_info->icon_image)<span style="display:block;margin-top:8px;color:#71828d;font-size:12px">A custom image is currently active.</span>@endif
                                    <p class="help-block" style="margin:7px 0 0">Upload a custom icon for “{{ $category_info->category_name }}”. The file is stored locally as a category-named asset.</p>
                                </div>
                            </div>
                        </div></div>
                        <div class="control-group"><label class="control-label" for="display_order">Homepage Order</label><div class="controls"><input type="number" min="0" name="display_order" id="display_order" value="{{$category_info->display_order}}" class="span2"><span class="help-inline">Lower numbers appear first.</span></div></div>
                        <div class="control-group"><label class="control-label">Featured Categories</label><div class="controls"><label class="checkbox"><input type="checkbox" name="is_featured" value="1" {{$category_info->is_featured ? 'checked' : ''}}> Show on homepage</label></div></div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ url('/manage-category') }}" class="btn">Cancel</a>
                        </div>
                    </fieldset>
                </form>
                @if($category_info->icon_image)
                    <form method="post" action="{{ url('/remove-category-icon/'.$category_info->category_id) }}" style="margin:-10px 0 14px 180px" onsubmit="return confirm('Remove this custom category image and use the fallback icon?')">
                        @csrf
                        <button type="submit" class="btn btn-mini btn-danger"><i class="halflings-icon white trash"></i> Remove saved image</button>
                    </form>
                @endif
                <script>
                (function () {
                    var select = document.getElementById('icon_class'), file = document.getElementById('icon_image'), preview = document.getElementById('category-icon-preview');
                    select.addEventListener('change', function () { if (!file.files.length) preview.innerHTML = '<i class="fa ' + this.value + '" aria-hidden="true"></i>'; });
                    file.addEventListener('change', function () { var selected = file.files[0]; if (!selected) return; var image = document.createElement('img'); image.alt = 'New category icon preview'; image.style.cssText = 'width:100%;height:100%;object-fit:contain'; image.src = URL.createObjectURL(selected); image.onload = function () { URL.revokeObjectURL(image.src); }; preview.innerHTML = ''; preview.appendChild(image); });
                }());
                </script>

            </div>
        </div><!--/span-->

    </div><!--/row-->
</div><!--/.fluid-container-->

<!-- end: Content -->
@endsection
