@extends('admin.admin-master')
@section('admin_main_content')
<div id="content" class="span10">
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>Site &amp; Banners</li></ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-error"><strong>Please correct these settings:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form class="form-horizontal" method="post" enctype="multipart/form-data" action="{{ url('/site-settings') }}">
        {{ csrf_field() }}
        <div class="row-fluid sortable">
            <div class="box span6">
                <div class="box-header"><h2><i class="halflings-icon picture"></i><span class="break"></span>Branding</h2></div>
                <div class="box-content">
                    <div class="control-group"><label class="control-label" for="site_name">Site name</label><div class="controls"><input class="span10" id="site_name" name="site_name" maxlength="120" value="{{ old('site_name', isset($settings['site_name']) ? $settings['site_name'] : config('app.name')) }}" required><p class="help-block">Changing this updates storefront, admin, emails, invoices, metadata, and copyright defaults.</p></div></div>
                    <div class="control-group"><label class="control-label" for="site_tagline">Tagline</label><div class="controls"><input class="span10" id="site_tagline" name="site_tagline" value="{{ old('site_tagline', isset($settings['site_tagline']) ? $settings['site_tagline'] : '') }}"></div></div>
                    <div class="control-group"><label class="control-label" for="logo">Site logo</label><div class="controls">@if(isset($settings['site_logo']) && $settings['site_logo'])<img src="{{ asset($settings['site_logo']) }}" alt="Current logo" style="display:block;max-width:190px;max-height:70px;margin-bottom:8px">@endif<input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"><p class="help-block">PNG, JPG or WebP; maximum 2MB. Leave empty to keep the current logo.</p></div></div>
                    <div class="control-group"><label class="control-label" for="favicon">Browser icon</label><div class="controls">@if(isset($settings['favicon']) && $settings['favicon'])<img src="{{ asset($settings['favicon']) }}" alt="Current icon" style="width:32px;height:32px;object-fit:contain;margin-right:8px">@endif<input type="file" id="favicon" name="favicon" accept=".ico,.png,image/x-icon,image/png"><p class="help-block">ICO or PNG; square 32×32 or 48×48 recommended, maximum 512KB.</p></div></div>
                </div>
            </div>
            <div class="box span6">
                <div class="box-header"><h2><i class="halflings-icon envelope"></i><span class="break"></span>Contact Details</h2></div>
                <div class="box-content">
                    @foreach(['phone'=>'Primary phone','support_phone'=>'Support phone','whatsapp_number'=>'WhatsApp number','support_email'=>'Support email','business_hours'=>'Business hours'] as $key=>$label)<div class="control-group"><label class="control-label" for="{{ $key }}">{{ $label }}</label><div class="controls"><input class="span10" id="{{ $key }}" name="{{ $key }}" value="{{ old($key, isset($settings[$key]) ? $settings[$key] : '') }}"></div></div>@endforeach
                    <div class="control-group"><label class="control-label" for="shop_address">Shop address</label><div class="controls"><textarea class="span10" rows="3" id="shop_address" name="shop_address">{{ old('shop_address', isset($settings['shop_address']) ? $settings['shop_address'] : '') }}</textarea></div></div>
                </div>
            </div>
        </div>

        <div class="row-fluid sortable">
            <div class="box span5">
                <div class="box-header"><h2><i class="halflings-icon signal"></i><span class="break"></span>Google Analytics</h2></div>
                <div class="box-content">
                    <div class="control-group"><label class="control-label" for="google_analytics_id">GA4 Measurement ID</label><div class="controls"><input class="span10" id="google_analytics_id" name="google_analytics_id" maxlength="30" placeholder="G-XXXXXXXXXX" value="{{ old('google_analytics_id', isset($settings['google_analytics_id']) ? $settings['google_analytics_id'] : '') }}"><p class="help-block">The tracking script is added only when a valid GA4 ID is saved.</p></div></div>
                    <div class="control-group"><label class="control-label" for="google_site_verification">Search Console verification</label><div class="controls"><input class="span10" id="google_site_verification" name="google_site_verification" maxlength="255" placeholder="Verification content value" value="{{ old('google_site_verification', isset($settings['google_site_verification']) ? $settings['google_site_verification'] : '') }}"><p class="help-block">Enter only the content value from Google's meta tag.</p></div></div>
                </div>
            </div>
            <div class="box span7">
                <div class="box-header"><h2><i class="halflings-icon search"></i><span class="break"></span>Search Engine Optimization</h2></div>
                <div class="box-content">
                    <div class="control-group"><label class="control-label" for="default_meta_title">Default page title</label><div class="controls"><input class="span10" id="default_meta_title" name="default_meta_title" maxlength="70" value="{{ old('default_meta_title', isset($settings['default_meta_title']) ? $settings['default_meta_title'] : '') }}"><p class="help-block">Used when a page does not provide its own SEO title.</p></div></div>
                    <div class="control-group"><label class="control-label" for="default_meta_description">Default description</label><div class="controls"><textarea class="span10" rows="3" maxlength="320" id="default_meta_description" name="default_meta_description">{{ old('default_meta_description', isset($settings['default_meta_description']) ? $settings['default_meta_description'] : '') }}</textarea></div></div>
                    <div class="control-group"><label class="control-label" for="meta_keywords">Default keywords</label><div class="controls"><textarea class="span10" rows="2" maxlength="500" id="meta_keywords" name="meta_keywords" placeholder="computers, laptops, networking">{{ old('meta_keywords', isset($settings['meta_keywords']) ? $settings['meta_keywords'] : '') }}</textarea></div></div>
                    <div class="control-group"><label class="control-label" for="robots_directive">Robots directive</label><div class="controls"><select id="robots_directive" name="robots_directive">@foreach(['index,follow'=>'Index and follow','index,nofollow'=>'Index, do not follow links','noindex,follow'=>'Do not index, follow links','noindex,nofollow'=>'Do not index or follow'] as $value=>$label)<option value="{{ $value }}" {{ old('robots_directive', isset($settings['robots_directive']) && $settings['robots_directive'] ? $settings['robots_directive'] : 'index,follow') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
                    <div class="control-group"><label class="control-label" for="seo_image">Social sharing image</label><div class="controls">@if(isset($settings['default_og_image']) && $settings['default_og_image'])<img src="{{ asset($settings['default_og_image']) }}" alt="Current social sharing image" style="display:block;width:180px;height:95px;object-fit:cover;margin-bottom:8px">@endif<input type="file" id="seo_image" name="seo_image" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"><p class="help-block">Recommended 1200×630px; maximum 4MB.</p></div></div>
                </div>
            </div>
        </div>

        <div class="row-fluid sortable">
            <div class="box span6">
                <div class="box-header"><h2><i class="halflings-icon share"></i><span class="break"></span>Social Media</h2></div>
                <div class="box-content">
                    @foreach(['facebook_url'=>'Facebook URL','instagram_url'=>'Instagram URL','youtube_url'=>'YouTube URL','linkedin_url'=>'LinkedIn URL','twitter_url'=>'X / Twitter URL'] as $key=>$label)<div class="control-group"><label class="control-label" for="{{ $key }}">{{ $label }}</label><div class="controls"><input type="url" class="span10" id="{{ $key }}" name="{{ $key }}" placeholder="https://" value="{{ old($key, isset($settings[$key]) ? $settings[$key] : '') }}"></div></div>@endforeach
                </div>
            </div>
            <div class="box span6">
                <div class="box-header"><h2><i class="halflings-icon globe"></i><span class="break"></span>Footer Content</h2></div>
                <div class="box-content">
                    <div class="control-group"><label class="control-label" for="footer_description">Footer description</label><div class="controls"><textarea class="span10" rows="3" id="footer_description" name="footer_description">{{ old('footer_description', isset($settings['footer_description']) ? $settings['footer_description'] : '') }}</textarea></div></div>
                    <div class="control-group"><label class="control-label" for="copyright_text">Copyright text</label><div class="controls"><input class="span10" id="copyright_text" name="copyright_text" value="{{ old('copyright_text', isset($settings['copyright_text']) ? $settings['copyright_text'] : '© {year} '.config('app.name').'. All rights reserved.') }}"><p class="help-block">Use <code>{year}</code> to insert the current year automatically.</p></div></div>
                </div>
            </div>
        </div>

        <div class="row-fluid sortable"><div class="box span12"><div class="box-header"><h2><i class="halflings-icon home"></i><span class="break"></span>Homepage Content</h2></div><div class="box-content">
            <div class="control-group"><label class="control-label" for="notice_text">Top notice</label><div class="controls"><textarea class="span8" rows="3" id="notice_text" name="notice_text">{{ old('notice_text', isset($settings['notice_text']) ? $settings['notice_text'] : '') }}</textarea></div></div>
            @foreach(['hero_side_title'=>'Hero side title','hero_side_text'=>'Hero side text'] as $key=>$label)<div class="control-group"><label class="control-label" for="{{ $key }}">{{ $label }}</label><div class="controls"><input class="span8" id="{{ $key }}" name="{{ $key }}" value="{{ old($key, isset($settings[$key]) ? $settings[$key] : '') }}"></div></div>@endforeach
            <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="halflings-icon white ok"></i> Save all settings</button></div>
        </div></div></div>
    </form>

    <div class="row-fluid sortable"><div class="box span5"><div class="box-header"><h2><i class="halflings-icon picture"></i><span class="break"></span>Add Homepage Banner</h2></div><div class="box-content"><form class="form-horizontal" method="post" enctype="multipart/form-data" action="{{ url('/save-banner') }}">{{ csrf_field() }}
    <div class="control-group"><label class="control-label">Image</label><div class="controls"><input type="file" name="image" accept="image/*" required><p class="help-block">Recommended 1200 × 500px, maximum 4MB.</p></div></div><div class="control-group"><label class="control-label">Title</label><div class="controls"><input name="title"></div></div><div class="control-group"><label class="control-label">Subtitle</label><div class="controls"><input name="subtitle"></div></div><div class="control-group"><label class="control-label">Link</label><div class="controls"><input name="link_url" placeholder="/product-by-category/45"></div></div><div class="control-group"><label class="control-label">Order</label><div class="controls"><input type="number" min="0" name="display_order" value="0"></div></div><div class="control-group"><label class="control-label">Active</label><div class="controls"><input type="checkbox" name="is_active" value="1" checked></div></div><div class="form-actions"><button class="btn btn-primary">Add banner</button></div></form></div></div>
    <div class="box span7"><div class="box-header"><h2>Current Banners</h2></div><div class="box-content"><table class="table table-striped"><thead><tr><th>Preview</th><th>Title</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead><tbody>@forelse($banners as $banner)<tr><td><img src="{{ asset($banner->image_path) }}" style="width:100px;height:45px;object-fit:cover"></td><td>{{ $banner->title ?: 'Untitled' }}</td><td>{{ $banner->display_order }}</td><td><span class="label {{ $banner->is_active ? 'label-success' : '' }}">{{ $banner->is_active ? 'Active' : 'Hidden' }}</span></td><td><a class="btn btn-mini" href="{{ url('/toggle-banner/'.$banner->id) }}">Toggle</a> <a class="btn btn-mini btn-danger" onclick="return confirm('Delete this banner?')" href="{{ url('/delete-banner/'.$banner->id) }}">Delete</a></td></tr>@empty<tr><td colspan="5">No custom banners yet. The default homepage banners remain visible.</td></tr>@endforelse</tbody></table></div></div></div></div>
</div>
@endsection
