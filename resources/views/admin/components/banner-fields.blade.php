@php
    $editingBanner = isset($banner) && $banner;
    $bannerType = old('banner_type', $editingBanner ? ($banner->banner_type ?: 'custom') : 'custom');
    $desktopPreview = $editingBanner ? asset($banner->resolved_desktop_image) : '';
    $mobilePreview = $editingBanner ? asset($banner->resolved_mobile_image) : '';
@endphp
<div class="bn-fields">
    <div class="bn-field"><label>Banner type</label><select name="banner_type" data-banner-type required>@foreach(['custom'=>'Custom Link','product'=>'Product','category'=>'Category','campaign'=>'Promotional Campaign','information'=>'Informational Banner'] as $value=>$label)<option value="{{ $value }}" {{ $bannerType===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
    <div class="bn-field bn-product-field"><label>Find product</label><input type="search" data-product-search placeholder="Search name or SKU"><select name="product_id" data-product-select><option value="">Choose a published product</option>@foreach($bannerProducts as $product)<option value="{{ $product->id }}" {{ (string)old('product_id',$editingBanner?$banner->product_id:'')===(string)$product->id?'selected':'' }}>{{ $product->product_name }} · {{ $product->sku ?: $product->product_id }}</option>@endforeach</select><small data-product-meta></small></div>
    <div class="bn-field bn-category-field"><label>Category</label><select name="category_id"><option value="">Choose a published category</option>@foreach($bannerCategories as $category)<option value="{{ $category->category_id }}" {{ (string)old('category_id',$editingBanner?$banner->category_id:'')===(string)$category->category_id?'selected':'' }}>{{ $category->category_name }}</option>@endforeach</select></div>
    <div class="bn-field bn-url-field"><label>Custom destination</label><input type="text" name="link_url" maxlength="255" value="{{ old('link_url',$editingBanner?$banner->link_url:'') }}" placeholder="/internal-path or https://example.com"><small>Only internal paths and HTTPS URLs are accepted.</small></div>
    <div class="bn-field"><label>Title</label><input type="text" name="title" maxlength="255" value="{{ old('title',$editingBanner?$banner->title:'') }}" data-preview-title></div>
    <div class="bn-field"><label>Subtitle</label><input type="text" name="subtitle" maxlength="255" value="{{ old('subtitle',$editingBanner?$banner->subtitle:'') }}" data-preview-subtitle></div>
    <div class="bn-field"><label>Button text</label><input type="text" name="button_text" maxlength="100" value="{{ old('button_text',$editingBanner?$banner->button_text:'') }}" placeholder="Shop Now" data-preview-button></div>
    <div class="bn-field"><label>Desktop banner</label><input type="file" name="desktop_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-desktop-image><small>JPG, PNG or WebP · 1200 × 500 recommended · maximum 5MB. {{ $editingBanner?'Leave empty to keep the current image.':'' }}</small></div>
    <div class="bn-field"><label>Mobile banner</label><input type="file" name="mobile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-mobile-image><small>800 × 800 recommended · maximum 5MB. Desktop image is the fallback.</small>@if($editingBanner && $banner->mobile_image)<label class="bn-check"><input type="checkbox" name="remove_mobile_image" value="1"> Remove current mobile image</label>@endif</div>
    <div class="bn-field"><label>Image position</label><select name="image_position" data-image-position>@foreach(['center'=>'Center','top'=>'Top','bottom'=>'Bottom','left'=>'Left','right'=>'Right'] as $value=>$label)<option value="{{ $value }}" {{ old('image_position',$editingBanner?$banner->image_position:'center')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
    <div class="bn-field"><label>Display order</label><input type="number" name="display_order" min="0" value="{{ old('display_order',$editingBanner?$banner->display_order:0) }}" required></div>
    <div class="bn-field"><label>Starts at</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at',$editingBanner&&$banner->starts_at?$banner->starts_at->format('Y-m-d\TH:i'):'') }}"></div>
    <div class="bn-field"><label>Expires at</label><input type="datetime-local" name="expires_at" value="{{ old('expires_at',$editingBanner&&$banner->expires_at?$banner->expires_at->format('Y-m-d\TH:i'):'') }}"></div>
    <div class="bn-options">
        <label class="bn-check bn-product-image-field"><input type="checkbox" name="use_product_image" value="1" data-use-product-image {{ old('use_product_image',$editingBanner?$banner->use_product_image:false)?'checked':'' }}> Use product image <small>May be cropped to fill the banner.</small></label>
        <label class="bn-check"><input type="checkbox" name="show_overlay" value="1" data-show-overlay {{ old('show_overlay',$editingBanner?$banner->show_overlay:true)?'checked':'' }}> Show text overlay</label>
        <label class="bn-check"><input type="checkbox" name="open_in_new_tab" value="1" {{ old('open_in_new_tab',$editingBanner?$banner->open_in_new_tab:false)?'checked':'' }}> Open link in new tab</label>
        <label class="bn-check"><input type="checkbox" name="is_active" value="1" data-is-active {{ old('is_active',$editingBanner?$banner->is_active:true)?'checked':'' }}> Visible</label>
    </div>
</div>
<div class="bn-previews" data-desktop-current="{{ $desktopPreview }}" data-mobile-current="{{ $mobilePreview }}">
    <div><b>Desktop preview</b><div class="bn-preview bn-preview-desktop"><img alt=""><span><strong></strong><small></small><em></em></span></div></div>
    <div><b>Mobile preview</b><div class="bn-preview bn-preview-mobile"><img alt=""><span><strong></strong><small></small><em></em></span></div></div>
</div>
