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
            <a href="#">Update Product</a>
        </li>
    </ul>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon edit"></i><span class="break"></span>Update Product</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content">
                <h3 style="color: green">
                    <?php
                    $message = Session::get('message');
                    if ($message) {
                        echo $message;
                        Session::put('message', '');
                    }
                    ?>
                </h3>
                @if($errors->any())<div class="alert alert-error"><strong>Please correct the product form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form action="{{ url('/update-product') }}" method="POST" enctype="multipart/form-data" name="update_data">
                    @csrf
                <fieldset class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label" for="typeahead">Product ID </label>
                        <div class="controls">
                            <input type="text" name="product_id" class="span6 typeahead" value="{{$product_info->product_id}}" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                            <input type="hidden" name="id" class="span6 typeahead" value="{{$product_info->id}}" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="sku">SKU / Product Code</label>
                        <div class="controls"><input type="text" name="sku" id="sku" value="{{ $product_info->sku }}" class="span6"></div>
                    </div>
                    <div class="control-group {{ $errors->has('barcode') ? 'error' : '' }}">
                        <label class="control-label" for="barcode">Barcode</label>
                        <div class="controls">
                            <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product_info->barcode) }}" class="span6" maxlength="64" inputmode="numeric" autocomplete="off" placeholder="Scan or enter UPC, EAN, ISBN, etc.">
                            @if($errors->has('barcode'))<span class="help-inline">{{ $errors->first('barcode') }}</span>@else<p class="help-block">Optional. Each barcode must be unique.</p>@endif
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="catId">Product Category</label>
                        <div class="controls">
                            <select id="catId" name="category_id">
                                @foreach($category as $vcategory)
                                <option value="{{$vcategory->category_id}}">{{$vcategory->category_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="selectSubCategory">Sub Category</label>
                        <div class="controls">
                            <select id="selectSubCategory" name="sub_category_id">
                                <option value="">None</option>
                                @foreach($sub_category as $vcategory)
                                <option value="{{$vcategory->sub_category_id}}">{{$vcategory->sub_category_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="brand_id">Brand</label>
                        <div class="controls">
                            <select id="brand_id" name="manufacturer_id" required>
                                @foreach($manufacturer as $vmanufacturer)
                                <option value="{{$vmanufacturer->manufacturer_id}}">{{ $vmanufacturer->company_name ? $vmanufacturer->company_name.' → ' : '' }}{{$vmanufacturer->manufacturer_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="product_series_id">Product Series</label>
                        <div class="controls"><select id="product_series_id" name="product_series_id"><option value="">No series / not applicable</option>@foreach($productSeries as $series)<option value="{{ $series->id }}" data-brand="{{ $series->manufacturer_id }}" {{ $product_info->product_series_id==$series->id?'selected':'' }}>{{ $series->name }}</option>@endforeach</select><p class="help-block">Series options update after choosing a brand.</p></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="typeahead">Product Model </label>
                        <div class="controls">
                            <input type="text" name="product_model" value="{{$product_info->product_model}}" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="typeahead">Product Name </label>
                        <div class="controls">
                            <input type="text" name="product_name" value="{{$product_info->product_name}}" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="short_description">Short Description</label>
                        <div class="controls"><textarea name="short_description" id="short_description" class="span6" rows="3">{{ $product_info->short_description }}</textarea></div>
                    </div>
                    <div id="catalog-attribute-fields">
                        @foreach($catalogAttributes as $categoryId => $attributes)
                        <div class="catalog-attribute-group" data-category="{{ $categoryId }}" style="display:none">
                            <div class="control-group"><label class="control-label"><strong>Category Attributes</strong></label><div class="controls"><p class="help-block">Used by storefront filters and product comparison.</p></div></div>
                            @foreach($attributes as $attribute) @php $options=(array)json_decode($attribute->options,true); $current=isset($productAttributeValues[$attribute->id])?$productAttributeValues[$attribute->id]:''; $currentMultiple=(array)json_decode($current,true); @endphp
                            <div class="control-group"><label class="control-label" for="attribute-{{ $attribute->id }}">{{ $attribute->name }}</label><div class="controls">@if($attribute->input_type==='select')<select id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}]"><option value="">Not specified</option>@foreach($options as $option)<option value="{{ $option }}" {{ $current===$option?'selected':'' }}>{{ $option }}</option>@endforeach</select>@elseif($attribute->input_type==='multiselect')<select id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}][]" multiple size="{{ min(6,max(3,count($options))) }}" class="span6">@foreach($options as $option)<option value="{{ $option }}" {{ in_array($option,$currentMultiple,true)?'selected':'' }}>{{ $option }}</option>@endforeach</select><p class="help-block">Hold Ctrl to choose multiple values.</p>@else<input id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}]" value="{{ $current }}" class="span6">@endif</div></div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="key_features">Key Features</label>
                        <div class="controls"><textarea name="key_features" id="key_features" class="span6" rows="6">{{ implode("\n", (array) json_decode($product_info->key_features, true)) }}</textarea><p class="help-block">One feature per line.</p></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="specifications">Specifications</label>
                        <div class="controls"><textarea name="specifications" id="specifications" class="span6" rows="12">@foreach((array) json_decode($product_info->specifications, true) as $label => $value)@if(is_array($value))[{{ $label }}]{{ "\n" }}@foreach($value as $itemLabel => $itemValue){{ $itemLabel }}: {{ $itemValue }}{{ "\n" }}@endforeach{{ "\n" }}@else{{ $label }}: {{ $value }}{{ "\n" }}@endif @endforeach</textarea><p><button type="button" class="btn btn-small" id="load-spec-template"><i class="icon-list-alt"></i> Load category template</button> <span id="spec-template-status" class="help-inline"></span></p><p class="help-block">Use one Label: Value pair per line. Add a section heading as <strong>[Basic Information]</strong>.</p></div>
                    </div>
                    <div class="control-group hidden-phone">
                        <label class="control-label" for="textarea2" >Product Description</label>
                        <div class="controls">
                            <textarea name="product_description" class="cleditor" rows="3">{{$product_info->product_description}}</textarea>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="regular_price">Regular Price</label>
                        <div class="controls"><input type="number" min="0" step="0.01" name="regular_price" id="regular_price" value="{{$product_info->regular_price}}" class="span6" required><p class="help-block">Required. This is the product's normal price.</p></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="offer_price">Offer Price (optional)</label>
                        <div class="controls"><input type="number" min="0" step="0.01" name="offer_price" value="{{$product_info->offer_price}}" class="span6" id="offer_price"><p class="help-block">Used only when lower than Regular Price. Equal or higher values are ignored.</p></div>
                    </div>
                    <div class="control-group"><label class="control-label" for="purchase_price">Purchase Price</label><div class="controls"><input type="number" min="0" step="0.01" name="purchase_price" id="purchase_price" value="{{$product_info->purchase_price}}" class="span6" required><p class="help-block">Private purchase price used for inventory valuation and profit reports.</p></div></div>
                    <div class="control-group">
                        <label class="control-label">Homepage Sections</label>
                        <div class="controls"><label class="checkbox inline"><input type="checkbox" name="top_product" value="1" {{$product_info->top_product ? 'checked' : ''}}> Featured product</label><label class="checkbox inline"><input type="checkbox" name="is_new_arrival" value="1" {{$product_info->is_new_arrival ? 'checked' : ''}}> New arrival</label></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="selectError3">Product Condition</label>
                        <div class="controls">
                            <select id="selectError3" name="product_condition">
                                <option value="In Stock">In Stock</option>
                                <option value="Out Of Stock">Stock Out</option>
                                <!--<option value="Up Coming">Up Coming</option>-->
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="stock_quantity">Stock Quantity</label>
                        <div class="controls"><input type="number" min="0" name="stock_quantity" id="stock_quantity" value="{{ $product_info->stock_quantity }}" class="span6"></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="warranty">Warranty</label>
                        <div class="controls"><input type="text" name="warranty" id="warranty" value="{{ $product_info->warranty }}" class="span6"></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="fileInput">Product Image</label>
                        <div class="controls">
                            <input class="input-file uniform_on" name="product_image" id="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <input class="input-file uniform_on" value="{{$product_info->product_image}}" name="product_old_image" id="fileInput" type="hidden">
                            <span><img src="{{asset($product_info->product_image)}}" width="50" height="50"></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="gallery_images">Gallery Images</label>
                        <div class="controls">
                            @php $galleryImages=array_values(array_filter((array)json_decode($product_info->gallery_images,true))); @endphp
                            @if($galleryImages)<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px">@foreach($galleryImages as $galleryImage)<label style="position:relative;border:1px solid #ddd;padding:5px;border-radius:4px;text-align:center"><img src="{{ asset($galleryImage) }}" alt="Product gallery image" style="display:block;width:100px;height:100px;object-fit:cover"><span style="display:block;margin-top:5px"><input type="checkbox" name="remove_gallery_images[]" value="{{ $galleryImage }}"> Remove</span></label>@endforeach</div>@else<p class="muted">No gallery images uploaded yet.</p>@endif
                            <input class="input-file" name="gallery_images[]" id="gallery_images" type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <p class="help-block">Add up to 10 new JPG, PNG or WebP images per update. Each image can be up to 5MB.</p>
                            <div id="new-gallery-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px"></div>
                            @if($errors->has('gallery_images.*'))<span class="help-inline">{{ $errors->first('gallery_images.*') }}</span>@endif
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="seo_title">SEO Title</label>
                        <div class="controls"><input type="text" name="seo_title" id="seo_title" value="{{ $product_info->seo_title }}" class="span6" maxlength="255"></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="seo_description">SEO Description</label>
                        <div class="controls"><textarea name="seo_description" id="seo_description" class="span6" rows="3">{{ $product_info->seo_description }}</textarea></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="reset" class="btn">Cancel</button>
                    </div>
                </fieldset>
                </form>

            </div>
        </div><!--/span-->

    </div><!--/row-->
</div><!--/.fluid-container-->
<script type="text/javascript">
    document.forms['update_data'].elements['category_id'].value='<?php echo $product_info->category_id?>';
    document.forms['update_data'].elements['sub_category_id'].value='<?php echo isset($product_info->sub_category) ? $product_info->sub_category : ''?>';
    document.forms['update_data'].elements['manufacturer_id'].value='<?php echo $product_info->manufacturer_id?>';
</script>
<script>document.addEventListener('DOMContentLoaded',function(){var category=document.getElementById('catId'),brand=document.getElementById('brand_id'),series=document.getElementById('product_series_id');function showAttributes(){document.querySelectorAll('.catalog-attribute-group').forEach(function(group){group.style.display=group.getAttribute('data-category')===category.value?'block':'none';});}function showSeries(){Array.prototype.forEach.call(series.options,function(option,index){if(!index)return;option.hidden=option.getAttribute('data-brand')!==brand.value;});if(series.selectedOptions.length&&series.selectedOptions[0].hidden)series.value='';}category.addEventListener('change',showAttributes);brand.addEventListener('change',showSeries);showAttributes();showSeries();});</script>
<script>document.addEventListener('DOMContentLoaded',function(){var category=document.getElementById('catId'),templates=@json($specificationTemplates),field=document.getElementById('specifications'),button=document.getElementById('load-spec-template'),status=document.getElementById('spec-template-status');function refreshTemplate(){var template=templates[category.value];button.disabled=!template;status.textContent=template?template.name+' available':'No template configured';}category.addEventListener('change',refreshTemplate);button.addEventListener('click',function(){var template=templates[category.value];if(!template)return;if(field.value.trim()&&!confirm('Replace the current specifications with the category template?'))return;field.value=template.content;});refreshTemplate();});</script>
<script>document.addEventListener('DOMContentLoaded',function(){var input=document.getElementById('gallery_images'),preview=document.getElementById('new-gallery-preview');if(input&&preview)input.addEventListener('change',function(){preview.innerHTML='';Array.prototype.slice.call(input.files,0,10).forEach(function(file){var image=document.createElement('img');image.src=URL.createObjectURL(file);image.alt=file.name;image.style.cssText='width:90px;height:90px;object-fit:cover;border:1px solid #ddd;border-radius:4px';image.onload=function(){URL.revokeObjectURL(image.src);};preview.appendChild(image);});});});</script>
<!-- end: Content -->
@endsection
