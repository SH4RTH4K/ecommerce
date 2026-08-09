@extends('admin.admin-master')
@section('admin_main_content')
@php
    $adminPermissions = (array) session('admin_permissions', []);
    $activeProductCodeConfig = $productCodeSnapshot ?? [];
    $productCodeAutoGenerate = (bool) ($activeProductCodeConfig['auto_generate'] ?? true);
    $productCodeManualAllowed = in_array('override_product_code', $adminPermissions, true)
        && ((bool) ($activeProductCodeConfig['allow_manual_override'] ?? false) || ! $productCodeAutoGenerate);
    $selectedProductCode = old('product_code', old('sku', ''));
@endphp

<div id="content" class="span10">
    <ul class="breadcrumb">
        <li>
            <i class="icon-home"></i>
            <a href="#">Home</a>
            <i class="icon-angle-right"></i> 
        </li>
        <li>
            <i class="icon-edit"></i>
            <a href="#">Add Product</a>
        </li>
    </ul>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon edit"></i><span class="break"></span>Add Product</h2>
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
                <form action="{{ url('/save-product') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                <fieldset class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label" for="typeahead">Product ID </label>
                        <div class="controls">
                            <input type="text" name="product_id" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="company_id">Company</label>
                        <div class="controls">
                            <select id="company_id" name="company_id" class="span6" data-rel="chosen" data-placeholder="Search companies...">
                                <option value="">Auto-detect from brand</option>
                                @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}{{ $company->company_code ? ' · '.$company->company_code : '' }}</option>
                                @endforeach
                            </select>
                            <p class="help-block">Optional. If left blank, the brand's company will be used when available.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="branch_id">Branch</label>
                        <div class="controls">
                            <select id="branch_id" name="branch_id" class="span6" data-rel="chosen" data-placeholder="Search branches...">
                                <option value="">Global / not branch-specific</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}{{ $branch->code ? ' · '.$branch->code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="product_code">Product Code</label>
                        <div class="controls">
                            <input type="text" name="product_code" id="product_code" value="{{ $selectedProductCode }}" class="span6" placeholder="Auto-generated when blank" {{ $productCodeManualAllowed ? '' : 'readonly' }}>
                            <p class="help-block">{{ $productCodeManualAllowed ? 'Manual override is allowed for your role.' : 'This code is generated automatically by the active configuration.' }}</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label">Product Code Preview</label>
                        <div class="controls">
                            <div class="alert alert-info" style="margin-bottom:0">
                                <strong id="product-code-preview">Select company, category, brand, and series to see the generated code.</strong>
                                <div id="product-code-preview-meta" style="margin-top:4px">Active configuration: {{ $activeProductCodeConfig['name'] ?? config('product_code.default_name', 'Default Product Code') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="control-group {{ $errors->has('barcode') ? 'error' : '' }}">
                        <label class="control-label" for="barcode">Barcode</label>
                        <div class="controls">
                            <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}" class="span6" maxlength="64" inputmode="numeric" autocomplete="off" placeholder="Scan or enter UPC, EAN, ISBN, etc.">
                            @if($errors->has('barcode'))<span class="help-inline">{{ $errors->first('barcode') }}</span>@else<p class="help-block">Optional. Each barcode must be unique.</p>@endif
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="catId">Product Category</label>
                        <div class="controls">
                            <select id="catId" name="category_id" data-rel="chosen" data-placeholder="Search categories...">
                                @foreach($category as $vcategory)
                                <option value="{{$vcategory->category_id}}">{{$vcategory->category_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="selectSubCategory">Sub Category</label>
                        <div class="controls"><select id="selectSubCategory" name="sub_category_id" data-rel="chosen" data-placeholder="Search sub-categories..."><option value="">None</option>@foreach($sub_category as $vcategory)<option value="{{$vcategory->sub_category_id}}">{{$vcategory->sub_category_name}}</option>@endforeach</select></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="brand_id">Brand</label>
                        <div class="controls">
                            <select id="brand_id" name="manufacturer_id" data-rel="chosen" data-placeholder="Search brands...">
                                <option value="">No brand / not applicable</option>
                                @foreach($manufacturer as $vmanufacturer)
                                <option value="{{$vmanufacturer->manufacturer_id}}">{{ $vmanufacturer->company_name ? $vmanufacturer->company_name.' → ' : '' }}{{$vmanufacturer->manufacturer_name}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="product_series_id">Collection / Product Line</label>
                        <div class="controls"><select id="product_series_id" name="product_series_id" data-rel="chosen" data-placeholder="Search collections..."><option value="">None / not applicable</option>@foreach($productSeries as $series)<option value="{{ $series->id }}" data-brand="{{ $series->manufacturer_id }}">{{ $series->name }}</option>@endforeach</select><p class="help-block">Optional. Examples: ThinkPad, Summer Collection, or Premium Range.</p></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="typeahead">Product Model </label>
                        <div class="controls">
                            <input type="text" name="product_model" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="typeahead">Product Name </label>
                        <div class="controls">
                            <input type="text" name="product_name" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="short_description">Short Description</label>
                        <div class="controls"><textarea name="short_description" id="short_description" class="span6" rows="3" placeholder="Short summary shown on product cards"></textarea></div>
                    </div>
                    <div id="catalog-attribute-fields">
                        @foreach($catalogAttributes as $categoryId => $attributes)
                        <div class="catalog-attribute-group" data-category="{{ $categoryId }}" style="display:none">
                            <div class="control-group"><label class="control-label"><strong>Category Attributes</strong></label><div class="controls"><p class="help-block">Used by storefront filters and product comparison.</p></div></div>
                            @foreach($attributes as $attribute) @php $options=(array)json_decode($attribute->options,true); @endphp
                            <div class="control-group"><label class="control-label" for="attribute-{{ $attribute->id }}">{{ $attribute->name }}</label><div class="controls">@if($attribute->input_type==='select')<select id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}]"><option value="">Not specified</option>@foreach($options as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select>@elseif($attribute->input_type==='multiselect')<select id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}][]" multiple size="{{ min(6,max(3,count($options))) }}" class="span6">@foreach($options as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select><p class="help-block">Hold Ctrl to choose multiple values.</p>@else<input id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}]" class="span6">@endif</div></div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="key_features">Key Features</label>
                        <div class="controls"><textarea name="key_features" id="key_features" class="span6" rows="6" placeholder="One feature per line"></textarea></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="specifications">Specifications</label>
                        <div class="controls"><textarea name="specifications" id="specifications" class="span6" rows="12" placeholder="[Basic Information]&#10;Processor: Intel Core i5&#10;Memory: 16GB"></textarea><p><button type="button" class="btn btn-small" id="load-spec-template"><i class="icon-list-alt"></i> Load category template</button> <span id="spec-template-status" class="help-inline"></span></p><p class="help-block">Use one Label: Value pair per line, or paste a label followed by its value on the next line. Add a section heading as <strong>[Basic Information]</strong>.</p></div>
                    </div>
                    <div class="control-group hidden-phone">
                        <label class="control-label" for="textarea2" >Product Description</label>
                        <div class="controls">
                            <textarea name="product_description" class="cleditor" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="regular_price">Regular Price</label>
                        <div class="controls"><input type="number" min="0" step="0.01" name="regular_price" id="regular_price" class="span6" required><p class="help-block">Required. This is the product's normal price.</p></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="offer_price">Offer Price (optional)</label>
                        <div class="controls"><input type="number" min="0" step="0.01" name="offer_price" class="span6" id="offer_price"><p class="help-block">Used only when lower than Regular Price. Equal or higher values are ignored.</p></div>
                    </div>
                    <div class="control-group"><label class="control-label" for="purchase_price">Purchase Price</label><div class="controls"><input type="number" min="0" step="0.01" name="purchase_price" id="purchase_price" value="0" class="span6" required><p class="help-block">Private purchase price used for inventory valuation and profit reports.</p></div></div>
                    <div class="control-group">
                        <label class="control-label">Homepage Sections</label>
                        <div class="controls"><label class="checkbox inline"><input type="checkbox" name="top_product" value="1"> Featured product</label><label class="checkbox inline"><input type="checkbox" name="is_new_arrival" value="1"> New arrival</label></div>
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
                        <div class="controls"><input type="number" min="0" name="stock_quantity" id="stock_quantity" value="0" class="span6"></div>
                    </div>
                    @include('admin.components.multi-industry-product-fields')
                    <div class="control-group">
                        <label class="control-label" for="warranty">Warranty</label>
                        <div class="controls"><input type="text" name="warranty" id="warranty" class="span6" placeholder="Leave blank for No Warranty"></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="fileInput">Product Image</label>
                        <div class="controls">
                            <input class="input-file uniform_on" name="product_image" id="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <p class="help-block">Primary image shown on product cards. JPG, PNG or WebP, maximum 5MB.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="gallery_images">Gallery Images</label>
                        <div class="controls"><input class="input-file" name="gallery_images[]" id="gallery_images" type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><p class="help-block">Select up to 10 images at once. Each image can be up to 5MB.</p><div id="new-gallery-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px"></div>@if($errors->has('gallery_images.*'))<span class="help-inline">{{ $errors->first('gallery_images.*') }}</span>@endif</div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="seo_title">SEO Title</label>
                        <div class="controls"><input type="text" name="seo_title" id="seo_title" class="span6" maxlength="255"></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="seo_description">SEO Description</label>
                        <div class="controls"><textarea name="seo_description" id="seo_description" class="span6" rows="3"></textarea></div>
                    </div>
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
                </form>

            </div>
        </div><!--/span-->

    </div><!--/row-->
</div><!--/.fluid-container-->
<script>document.addEventListener('DOMContentLoaded',function(){var category=document.getElementById('catId'),brand=document.getElementById('brand_id'),series=document.getElementById('product_series_id');function showAttributes(){document.querySelectorAll('.catalog-attribute-group').forEach(function(group){group.style.display=group.getAttribute('data-category')===category.value?'block':'none';});}function showSeries(){Array.prototype.forEach.call(series.options,function(option,index){if(!index)return;option.hidden=option.getAttribute('data-brand')!==brand.value;});if(series.selectedOptions.length&&series.selectedOptions[0].hidden)series.value='';if(window.jQuery)jQuery(series).trigger('liszt:updated');}category.addEventListener('change',showAttributes);brand.addEventListener('change',showSeries);showAttributes();showSeries();var input=document.getElementById('gallery_images'),preview=document.getElementById('new-gallery-preview');if(input&&preview)input.addEventListener('change',function(){preview.innerHTML='';Array.prototype.slice.call(input.files,0,10).forEach(function(file){var image=document.createElement('img');image.src=URL.createObjectURL(file);image.alt=file.name;image.style.cssText='width:90px;height:90px;object-fit:cover;border:1px solid #ddd;border-radius:4px';image.onload=function(){URL.revokeObjectURL(image.src);};preview.appendChild(image);});});});</script>
<script>document.addEventListener('DOMContentLoaded',function(){var category=document.getElementById('catId'),templates=@json($specificationTemplates),field=document.getElementById('specifications'),button=document.getElementById('load-spec-template'),status=document.getElementById('spec-template-status');function refreshTemplate(){var template=templates[category.value];button.disabled=!template;status.textContent=template?template.name+' available':'No template configured';}category.addEventListener('change',refreshTemplate);button.addEventListener('click',function(){var template=templates[category.value];if(!template)return;if(field.value.trim()&&!confirm('Replace the current specifications with the category template?'))return;field.value=template.content;});refreshTemplate();});</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form[action="{{ url('/save-product') }}"]');
    if (!form) {
        return;
    }

    var preview = document.getElementById('product-code-preview');
    var previewMeta = document.getElementById('product-code-preview-meta');
    var company = document.getElementById('company_id');
    var branch = document.getElementById('branch_id');
    var category = document.getElementById('catId');
    var subCategory = document.getElementById('selectSubCategory');
    var brand = document.getElementById('brand_id');
    var series = document.getElementById('product_series_id');
    var previewUrl = @json(url('/product-code-configuration/preview'));
    var csrfToken = @json(csrf_token());
    var brandCompanyMap = @json($manufacturer->pluck('company_id', 'manufacturer_id')->all());
    var timer = null;

    function setPreview(message) {
        if (preview) {
            preview.textContent = message;
        }
    }

    function syncCompanyFromBrand() {
        if (!company || !brand) {
            return;
        }

        var brandCompanyId = brandCompanyMap[brand.value] || null;
        if (!company.value && brandCompanyId) {
            company.value = brandCompanyId;
        }
    }

    function requestPreview() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var payload = new FormData();
            ['company_id', 'branch_id', 'category_id', 'subcategory_id', 'manufacturer_id', 'series_id', 'variant_code', 'custom_prefix', 'custom_suffix', 'product_type_code'].forEach(function (field) {
                var element = form.querySelector('[name="' + field + '"]');
                if (element) {
                    payload.append(field, element.value || '');
                }
            });

            fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: payload
            })
            .then(function (response) {
                return response.json().then(function (json) {
                    return { ok: response.ok, json: json };
                });
            })
            .then(function (result) {
                if (result.ok && result.json && result.json.preview) {
                    setPreview(result.json.preview);
                    if (previewMeta && result.json.configuration && result.json.configuration.name) {
                        previewMeta.textContent = 'Active configuration: ' + result.json.configuration.name;
                    }
                    return;
                }

                var message = result.json && (result.json.message || result.json.error);
                if (!message && result.json && result.json.errors && result.json.errors.product_code && result.json.errors.product_code.length) {
                    message = result.json.errors.product_code[0];
                }
                setPreview(message || 'Select the required fields to generate a preview.');
            })
            .catch(function () {
                setPreview('Unable to load product code preview right now.');
            });
        }, 180);
    }

    if (brand) {
        brand.addEventListener('change', function () {
            syncCompanyFromBrand();
            requestPreview();
        });
    }

    [company, branch, category, subCategory, series].forEach(function (element) {
        if (!element) {
            return;
        }
        element.addEventListener('change', requestPreview);
    });

    requestPreview();
});
</script>
<!-- end: Content -->
@endsection
