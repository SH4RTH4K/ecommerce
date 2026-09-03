@extends('admin.admin-master')
@section('title', 'Website Settings - '.$brandName)
@section('admin_main_content')
@php
    $defaults = $siteCustomizationDefaults ?? [
        'site_name' => config('app.default_name', 'Ecommerce'),
        'site_name_font_size' => 23,
        'site_tagline_font_size' => 12,
        'logo_resize_width' => 600,
        'logo_resize_height' => 200,
        'favicon_resize_width' => 512,
        'favicon_resize_height' => 512,
        'robots_directive' => 'index,follow',
        'homepage_featured_products_limit' => 20,
        'homepage_featured_products_per_row' => 5,
        'homepage_new_arrivals_limit' => 20,
        'homepage_new_arrivals_per_row' => 5,
        'footer_credit_text' => 'Lucent Tech BD',
        'footer_credit_url' => '',
        'development_mode_enabled' => 0,
        'development_mode_message_type' => 'maintenance',
        'development_mode_title' => 'Website Under Development',
        'development_mode_message' => 'We are currently improving our website. Please check back again soon.',
        'development_mode_show_admin_login' => 1,
        'development_mode_login_button_text' => 'Admin Login',
    ];
    $setting = function ($key, $default = '') use ($settings) { return old($key, isset($settings[$key]) ? $settings[$key] : $default); };
    $assetUrl = function ($path) {
        if (!$path) return null;
        if (preg_match('#^https?://#i', (string)$path)) return $path;
        $relativePath = ltrim((string)(parse_url($path, PHP_URL_PATH) ?: $path), '/');
        $relativePath = preg_replace('#^public/#i', '', $relativePath);
        return file_exists(public_path($relativePath)) ? asset($relativePath) : null;
    };
    $logoUrl = $assetUrl($settings['site_logo'] ?? null);
    $tabletLogoUrl = $assetUrl($settings['site_logo_tablet'] ?? null);
    $mobileLogoUrl = $assetUrl($settings['site_logo_mobile'] ?? null);
    $siteNameValue = trim((string)$setting('site_name', $defaults['site_name']));
    $siteTaglineValue = trim((string)$setting('site_tagline'));
    $siteNameFontSize = (int)$setting('site_name_font_size', $defaults['site_name_font_size']);
    $siteTaglineFontSize = (int)$setting('site_tagline_font_size', $defaults['site_tagline_font_size']);
    $checks = $storeSetupChecks ?? [
        ['Business identity', ($siteNameValue !== '' && strcasecmp($siteNameValue, (string)$defaults['site_name']) !== 0) || $siteTaglineValue !== '' || $siteNameFontSize !== (int)$defaults['site_name_font_size'] || $siteTaglineFontSize !== (int)$defaults['site_tagline_font_size'], 'identity'],
        ['Customer contact', (bool)(trim((string)$setting('phone')) || trim((string)$setting('support_phone')) || trim((string)$setting('whatsapp_number')) || trim((string)$setting('support_email'))), 'contact'],
        ['Store address', (bool)(trim((string)$setting('shop_address')) || trim((string)$setting('business_hours'))), 'contact'],
        ['Search description', (bool)(trim((string)$setting('default_meta_title')) || trim((string)$setting('default_meta_description')) || trim((string)$setting('meta_keywords')) || trim((string)$setting('default_og_image'))), 'seo'],
        ['Header branding', (bool)(trim((string)$setting('site_logo')) || trim((string)$setting('favicon'))), 'identity'],
    ];
    $complete = collect($checks)->where(1, true)->count();
    $percent = $storeSetupPercent ?? (int)round(count($checks) ? ($complete / count($checks)) * 100 : 0);
    $developmentModeEnabled = (string)$setting('development_mode_enabled', '0') === '1';
    $logoResizeEnabled = (string)$setting('logo_resize_enabled', '1') === '1';
    $faviconResizeEnabled = (string)$setting('favicon_resize_enabled', '1') === '1';
    $startechSourceImportEnabled = (string)$setting('startech_source_import_enabled', '1') === '1';
    $catalogSourceLabel = catalog_import_source_label($setting('catalog_import_source_address'));
    $removeLogoRequested = (string)old('remove_logo', '0') === '1';
    $removeFaviconRequested = (string)old('remove_favicon', '0') === '1';
    $removeSeoImageRequested = (string)old('remove_seo_image', '0') === '1';
    $resetRequested = (string)old('reset_to_default', '0') === '1';
@endphp
<style>
.ws-page{padding-bottom:88px;color:#263746}.ws-hero{background:linear-gradient(125deg,#123e59,#176f91);border-radius:14px;color:#fff;padding:24px 28px;margin:0 0 18px;box-shadow:0 10px 28px rgba(16,61,85,.18)}.ws-hero h1{font-size:25px;line-height:1.25;margin:0 0 7px;color:#fff}.ws-hero p{margin:0;opacity:.88;font-size:14px}.ws-hero-actions{float:right;margin-top:-43px}.ws-hero-actions .btn{border:0;border-radius:7px;padding:9px 14px}.ws-layout{display:grid;grid-template-columns:245px minmax(0,1fr);gap:18px}.ws-sidebar,.ws-card{background:#fff;border:1px solid #dfe8ed;border-radius:12px;box-shadow:0 4px 16px rgba(30,58,76,.06)}.ws-sidebar{align-self:start;position:sticky;top:56px;overflow:hidden}.ws-progress{padding:18px;border-bottom:1px solid #e7eef2}.ws-progress-head{display:flex;justify-content:space-between;font-weight:700;margin-bottom:8px}.ws-progress-bar{height:8px;background:#e7eef2;border-radius:20px;overflow:hidden}.ws-progress-bar i{display:block;height:100%;background:#20a576;border-radius:20px}.ws-checks{margin:12px 0 0;list-style:none}.ws-checks li{font-size:12px;padding:4px 0;color:#657783}.ws-checks i{width:16px;color:#b5c0c7}.ws-checks .done i{color:#20a576}.ws-nav{padding:8px}.ws-nav button{background:none;border:0;border-radius:8px;color:#526672;display:block;text-align:left;width:100%;padding:11px 12px;margin:2px 0;font-weight:600}.ws-nav button i{width:22px}.ws-nav button:hover,.ws-nav button.active{background:#eaf6fa;color:#116381}.ws-panel{display:none}.ws-panel.active{display:block}.ws-card{margin-bottom:16px;overflow:hidden}.ws-card-head{padding:17px 20px;border-bottom:1px solid #e7eef2}.ws-card-head h2{font-size:18px;margin:0 0 3px;color:#173f56}.ws-card-head p{margin:0;color:#71828c;font-size:12px}.ws-card-body{padding:20px}.ws-grid{display:grid;grid-template-columns:1fr 1fr;gap:17px 20px}.ws-grid .full{grid-column:1/-1}.ws-field label{display:block;font-size:12px;font-weight:700;color:#3c515e;margin-bottom:6px}.ws-required{color:#db4b4b}.ws-field input,.ws-field textarea,.ws-field select{box-sizing:border-box;width:100%;min-height:39px;border:1px solid #cbd8df;border-radius:7px;padding:8px 10px;margin:0;background:#fff;box-shadow:none}.ws-field textarea{resize:vertical}.ws-field input:focus,.ws-field textarea:focus,.ws-field select:focus{border-color:#1988ad;box-shadow:0 0 0 3px rgba(25,136,173,.1);outline:0}.ws-help{display:block;color:#7a8992;font-size:11px;line-height:1.45;margin-top:5px}.ws-counter{float:right}.ws-upload{border:1px dashed #afc4cf;border-radius:9px;padding:13px;background:#f8fbfc}.ws-upload img{display:block;max-width:210px;max-height:86px;object-fit:contain;margin-bottom:10px;border-radius:5px}.ws-upload .ws-og{width:210px;height:110px;object-fit:cover}.ws-rule{display:flex;gap:10px;background:#f1f8fb;border-left:3px solid #1988ad;border-radius:5px;padding:11px 13px;margin-bottom:17px;font-size:12px;line-height:1.5}.ws-rule i{color:#1988ad;margin-top:2px}.ws-savebar{position:fixed;z-index:20;bottom:0;right:0;left:14.5%;background:rgba(255,255,255,.96);border-top:1px solid #d8e3e8;box-shadow:0 -5px 18px rgba(24,57,75,.08);padding:12px 24px;text-align:right}.ws-savebar span{float:left;color:#687b86;line-height:36px}.ws-savebar .btn{border-radius:7px;padding:9px 18px}.ws-alert{border-radius:9px}.ws-preview-note{background:#fff8e7;border:1px solid #f2d997;border-radius:8px;padding:11px 13px;color:#765e22}.ws-file-name{font-size:11px;color:#1988ad;margin-left:7px}.ws-footer-link{display:flex;align-items:center;justify-content:space-between}.ws-footer-link strong{display:block;color:#173f56}.ws-footer-link small{color:#71828c}@media(max-width:979px){.ws-layout{grid-template-columns:1fr}.ws-sidebar{position:static}.ws-nav{display:flex;overflow-x:auto}.ws-nav button{min-width:150px}.ws-savebar{left:0}.ws-hero-actions{float:none;margin:15px 0 0}}@media(max-width:680px){.ws-grid{grid-template-columns:1fr}.ws-grid .full{grid-column:auto}.ws-hero{padding:20px}.ws-savebar span{display:none}}
.dm-status{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:9px;background:#eef8f3;border:1px solid #c8e7d7;margin-bottom:18px}.dm-status.is-active{background:#fff1e8;border-color:#f2c49e}.dm-status b{color:#267552}.dm-status.is-active b{color:#b64d0b}.dm-toggle{display:flex;align-items:center;gap:10px}.dm-toggle input{width:auto;min-height:auto}.dm-preview{background:linear-gradient(140deg,#f5f8fa,#e9f2f6);border:1px solid #d6e3e9;border-radius:12px;padding:25px;text-align:center}.dm-preview-badge{display:inline-block;background:#fff2e6;color:#b94f00;border-radius:20px;padding:5px 10px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em}.dm-preview h3{color:#0b3d62;font-size:24px;margin:13px 0 9px}.dm-preview p{max-width:560px;margin:7px auto;color:#627785;white-space:pre-line}.dm-preview .dm-preview-extra{color:#334f61}.dm-preview-availability{display:inline-block;margin-top:10px;padding:7px 10px;background:#fff;border-radius:7px;font-weight:700}.dm-preview-button{display:inline-block;margin-top:15px;padding:9px 15px;border-radius:7px;background:#0b3d62;color:#fff;font-weight:700}@media(max-width:680px){.dm-status{align-items:flex-start;gap:10px;flex-direction:column}}
.ws-contact-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(250px,.75fr);gap:20px;align-items:start}.ws-contact-preview{position:sticky;top:70px;background:linear-gradient(145deg,#123e59,#176f91);border-radius:12px;color:#fff;padding:20px;box-shadow:0 12px 25px rgba(18,62,89,.18)}.ws-contact-preview>small{display:block;color:#a9d3e2;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:7px}.ws-contact-preview h3{color:#fff;font-size:20px;margin:0 0 5px}.ws-contact-preview>p{color:rgba(255,255,255,.75);font-size:12px;margin:0 0 16px}.ws-contact-item{display:flex;gap:11px;align-items:flex-start;padding:11px 0;border-top:1px solid rgba(255,255,255,.14)}.ws-contact-item i{width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.13);display:flex;align-items:center;justify-content:center;flex:0 0 30px}.ws-contact-item small,.ws-contact-item strong{display:block;color:#fff;overflow-wrap:anywhere}.ws-contact-item small{opacity:.65;font-size:10px;text-transform:uppercase;letter-spacing:.05em}.ws-contact-empty{opacity:.68;font-style:italic}.ws-location-link{display:flex;gap:12px;align-items:center;margin-top:16px;padding:14px;border:1px solid #d9e7ed;background:#f7fbfc;border-radius:9px}.ws-location-link>i{font-size:22px;color:#1988ad}.ws-location-link div{flex:1}.ws-location-link strong,.ws-location-link small{display:block}.ws-location-link small{color:#71828c;margin-top:2px}.ws-location-link .btn{white-space:nowrap}@media(max-width:900px){.ws-contact-layout{grid-template-columns:1fr}.ws-contact-preview{position:static}}@media(max-width:680px){.ws-location-link{align-items:flex-start;flex-wrap:wrap}.ws-location-link .btn{margin-left:42px}}
.ws-page-editor{display:grid;grid-template-columns:minmax(0,1.28fr) minmax(300px,.78fr);gap:20px;align-items:start}.ws-page-fields{min-width:0}.ws-page-fields .cleditorMain{max-width:100%}.ws-page-preview{position:sticky;top:70px;background:#fbfdfe;border:1px solid #d7e5eb;border-radius:13px;box-shadow:0 10px 24px rgba(28,61,78,.08);overflow:hidden}.ws-preview-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:15px 16px;background:linear-gradient(135deg,#143e58,#1c7898);color:#fff}.ws-preview-head strong{display:block;color:#fff;font-size:14px}.ws-preview-head small{display:block;color:rgba(255,255,255,.75);font-size:11px;margin-top:2px}.ws-preview-path{border:1px solid rgba(255,255,255,.25);border-radius:999px;color:#fff;font-size:10px;font-weight:800;padding:4px 8px;white-space:nowrap}.ws-preview-body{padding:16px}.ws-preview-kicker{display:block;color:#1682a7;font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;margin-bottom:5px}.ws-preview-title{display:block;color:#153d55;font-size:20px;font-weight:900;line-height:1.2;margin:0 0 9px}.ws-preview-copy{color:#566d79;font-size:12px;line-height:1.55;margin-bottom:12px;overflow-wrap:anywhere}.ws-preview-copy p{margin:0 0 8px}.ws-preview-copy p:last-child{margin-bottom:0}.ws-preview-copy ul,.ws-preview-copy ol{padding-left:18px;margin:6px 0}.ws-preview-copy a{color:#147899;text-decoration:underline}.ws-preview-divider{height:1px;background:#e4edf1;margin:13px 0}.ws-preview-chips{display:grid;grid-template-columns:1fr;gap:7px}.ws-preview-chip,.ws-preview-mini{border:1px solid #e0ebf0;border-radius:9px;background:#fff;padding:9px 10px}.ws-preview-chip strong,.ws-preview-mini strong{display:block;color:#173f56;font-size:12px;margin-bottom:3px}.ws-preview-chip small,.ws-preview-mini small{display:block;color:#667b86;font-size:11px;line-height:1.4;overflow-wrap:anywhere}.ws-preview-minis{display:grid;gap:8px;margin-top:10px}.ws-preview-list{padding-left:18px;margin:8px 0 0;color:#4f6672;font-size:12px;line-height:1.45}.ws-preview-list li{margin-bottom:5px}.ws-preview-nav{display:flex;flex-wrap:wrap;gap:6px;margin:9px 0 12px}.ws-preview-nav span{background:#eaf6fa;border-radius:999px;color:#12607d;font-size:10px;font-weight:800;padding:5px 8px}.ws-preview-button{display:inline-block;margin-top:8px;background:#f47b20;color:#fff;border-radius:7px;padding:7px 10px;font-size:11px;font-weight:800}.ws-preview-muted{color:#7d8e97;font-size:11px;margin-bottom:9px}.ws-preview-empty{color:#8a9aa3;font-style:italic}@media(max-width:1100px){.ws-page-editor{grid-template-columns:1fr}.ws-page-preview{position:static}}@media(max-width:680px){.ws-preview-head{display:block}.ws-preview-path{display:inline-block;margin-top:8px}.ws-preview-title{font-size:18px}}
.ws-upload-missing{display:flex;align-items:center;gap:10px;min-height:58px;margin-bottom:10px;padding:10px;border-radius:7px;background:#fff4e8;color:#9a5318}.ws-upload-missing i{font-size:24px}.ws-upload-missing strong,.ws-upload-missing small{display:block}.ws-upload-missing small{color:#8a7463;margin-top:2px}
.ws-upload.has-error{border-color:#d9534f;background:#fff8f8}.ws-upload-error{display:block;margin-top:7px;color:#b52b27;font-size:12px;font-weight:600}.ws-upload-error:empty{display:none}.ws-upload input[type=file]{height:auto;min-height:0;padding:7px;background:#fff}.ws-file-name{display:inline-block;margin-top:7px}
.ws-upload-specs{margin-top:8px;padding:8px 10px;border-radius:6px;background:#eaf6fa;color:#315666;font-size:11px;line-height:1.5}.ws-upload-specs strong{color:#17475d}.ws-file-details{display:block;min-height:16px;margin-top:6px;color:#147899;font-size:11px;font-weight:700}.ws-resize-box{margin-top:10px;padding:10px;border:1px solid #d8e5ea;border-radius:7px;background:#fff}.ws-resize-toggle{display:flex!important;align-items:flex-start;gap:8px;margin:0!important;cursor:pointer}.ws-resize-toggle input{width:auto!important;min-height:auto!important;margin:2px 0 0!important;flex:0 0 auto}.ws-resize-toggle strong,.ws-resize-toggle small{display:block}.ws-resize-toggle small{margin-top:2px;color:#71828c;font-size:10px;font-weight:400}.ws-resize-options{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:10px}.ws-resize-options[hidden]{display:none}.ws-dimension label{margin-bottom:4px}.ws-dimension input{min-height:34px}.ws-resize-note{display:block;margin-top:7px;color:#71828c;font-size:10px;line-height:1.4}@media(max-width:680px){.ws-resize-options{grid-template-columns:1fr}}
.ws-tagline-customizer{display:grid;grid-template-columns:130px minmax(0,1fr);gap:12px;align-items:end}.ws-tagline-preview{min-height:46px;display:flex;align-items:center;padding:8px 12px;border:1px solid #d8e5ea;border-radius:7px;background:#f8fbfc;color:#38566c;font-weight:700;line-height:1.45;overflow-wrap:anywhere}.ws-tagline-preview.is-bengali{font-family:"Noto Sans Bengali","Nirmala UI","Vrinda","Segoe UI",sans-serif;letter-spacing:0}.ws-name-preview{color:#0b3d62;font-weight:900;line-height:1.2}.ws-name-preview.is-bengali{font-family:"Noto Sans Bengali","Nirmala UI","Vrinda","Segoe UI",sans-serif;letter-spacing:0;line-height:1.45}.ws-asset-preview[hidden],.ws-removal-state[hidden]{display:none}.ws-asset-actions{display:flex;align-items:center;gap:8px;margin:9px 0}.ws-remove-asset{margin:0!important}.ws-removal-state{margin:9px 0;padding:9px 10px;border-radius:6px;background:#fff1e8;color:#9b4815;font-size:11px;line-height:1.45}.ws-upload.is-removing{border-color:#e5a36f;background:#fffaf6}@media(max-width:680px){.ws-tagline-customizer{grid-template-columns:1fr}}
</style>
<div id="content" class="span10 ws-page">
    <style>
        /* Homepage Feature Cards have their own Marketing editor. */
        [data-settings-panel="content"] .ws-field:has(#hero_side_title),
        [data-settings-panel="content"] .ws-field:has(#hero_side_text),
        [data-settings-panel="content"] .ws-field:has(#hero_side_button_text),
        [data-settings-panel="content"] .ws-field:has(#hero_side_url),
        [data-settings-panel="content"] .ws-field:has(#hero_side_style),
        [data-settings-panel="content"] .ws-field:has(#hero_side_enabled),
        [data-settings-panel="content"] .ws-field:has(#hero_side_2_kicker),
        [data-settings-panel="content"] .ws-field:has(#hero_side_2_title),
        [data-settings-panel="content"] .ws-field:has(#hero_side_2_button_text),
        [data-settings-panel="content"] .ws-field:has(#hero_side_2_url),
        [data-settings-panel="content"] .ws-field:has(#hero_side_2_style),
        [data-settings-panel="content"] .ws-field:has(#hero_side_2_enabled){display:none!important}
    </style>
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>Website Settings</li></ul>
    @if(session('message'))<div class="alert alert-success ws-alert"><strong>Saved.</strong> {{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-error ws-alert"><strong>Some information needs attention.</strong><ul style="margin-bottom:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="ws-hero">
        <h1>Website Settings</h1><p>Manage the information customers see and the defaults search engines use.</p>
        <div class="ws-hero-actions"><a class="btn" href="{{ url('/') }}" target="_blank"><i class="icon-external-link"></i> View storefront</a></div>
    </div>

    <form id="website-settings-form" method="post" enctype="multipart/form-data" action="{{ url('/site-settings') }}">{{ csrf_field() }}<input type="hidden" id="reset_to_default" name="reset_to_default" value="{{ $resetRequested ? '1' : '0' }}">
        <div class="ws-layout">
            <aside class="ws-sidebar">
                <div class="ws-progress"><div class="ws-progress-head"><span>Store setup</span><span>{{ $percent }}%</span></div><div class="ws-progress-bar"><i style="width:{{ $percent }}%"></i></div><ul class="ws-checks">@foreach($checks as $check)<li class="{{ $check[1]?'done':'' }}"><i class="icon-{{ $check[1]?'ok':'circle-blank' }}"></i> {{ $check[0] }}</li>@endforeach</ul></div>
                <nav class="ws-nav" aria-label="Settings sections">
                    <button type="button" class="active" data-settings-tab="identity"><i class="icon-certificate"></i> Identity</button>
                    <button type="button" data-settings-tab="contact"><i class="icon-phone"></i> Contact &amp; location</button>
                    <button type="button" data-settings-tab="content"><i class="icon-align-left"></i> Storefront content</button>
                    <button type="button" data-settings-tab="theme"><i class="icon-tint"></i> Theme &amp; colors</button>
                    <button type="button" data-settings-tab="development-mode"><i class="icon-wrench"></i> Development Mode</button>
                    <button type="button" data-settings-tab="seo"><i class="icon-search"></i> Search &amp; sharing</button>
                    <button type="button" data-settings-tab="connections"><i class="icon-signal"></i> Connections</button>
                </nav>
            </aside>

            <main>
                <section class="ws-panel active" data-settings-panel="identity">
                    <div class="ws-card"><div class="ws-card-head"><h2>Business identity</h2><p>Your main public brand information.</p></div><div class="ws-card-body">
                        <div class="ws-rule"><i class="icon-info-sign"></i><div><strong>Business rule:</strong> A site name is required. Logo and icon changes apply across the storefront and admin area after saving.</div></div>
                        <div class="ws-grid">
                            <div class="ws-field"><label for="site_name">Website name <span class="ws-required">*</span></label><input id="site_name" name="site_name" maxlength="120" value="{{ $setting('site_name', $defaults['site_name']) }}" required data-count data-brand-preview data-name-text><small class="ws-help">Use the customer-facing business or store name. <span class="ws-counter"></span></small></div>
                            <div class="ws-field"><label for="site_tagline">Short tagline</label><input id="site_tagline" name="site_tagline" maxlength="180" value="{{ $setting('site_tagline') }}" placeholder="Technology that works for you" data-count data-tagline-text><small class="ws-help">A short promise explaining what your business offers. <span class="ws-counter"></span></small></div>
                            <div class="ws-field full {{ $errors->has('site_name_font_size') ? 'has-error' : '' }}">
                                <label for="site_name_font_size">Website name font size</label>
                                <div class="ws-tagline-customizer">
                                    <div><input id="site_name_font_size" type="number" name="site_name_font_size" min="14" max="32" step="1" value="{{ $setting('site_name_font_size', '23') }}" data-name-size><small class="ws-help">14–32 pixels</small></div>
                                    <div class="ws-tagline-preview ws-name-preview" data-name-preview aria-live="polite">{{ $setting('site_name', $defaults['site_name']) }}</div>
                                </div>
                                <small class="ws-help">Used when no custom logo is uploaded. Bengali Website names are usually clearest at 22–28 px.</small>
                                <span class="ws-upload-error" role="alert">{{ $errors->first('site_name_font_size') }}</span>
                            </div>
                            <div class="ws-field full {{ $errors->has('site_tagline_font_size') ? 'has-error' : '' }}">
                                <label for="site_tagline_font_size">Tagline font size</label>
                                <div class="ws-tagline-customizer">
                                    <div><input id="site_tagline_font_size" type="number" name="site_tagline_font_size" min="8" max="24" step="1" value="{{ $setting('site_tagline_font_size', '12') }}" data-tagline-size><small class="ws-help">8–24 pixels</small></div>
                                    <div class="ws-tagline-preview" data-tagline-preview aria-live="polite">{{ $setting('site_tagline') ?: 'Your short tagline preview' }}</div>
                                </div>
                                <small class="ws-help">Bengali text is usually clearest at 13–16 px. This size is used in the storefront header, footer and administrator login.</small>
                                <span class="ws-upload-error" role="alert">{{ $errors->first('site_tagline_font_size') }}</span>
                            </div>
                            <div class="ws-field">
                                <label for="brand-logo-upload">Primary logo</label>
                                <div class="ws-upload {{ $errors->hasAny(['logo','remove_logo','logo_resize_width','logo_resize_height']) ? 'has-error' : '' }} {{ $removeLogoRequested ? 'is-removing' : '' }}">
                                    <div data-asset-preview="logo" {{ $removeLogoRequested ? 'hidden' : '' }}>@if($logoUrl)<img src="{{ $logoUrl }}" alt="Current website logo">@else<div class="ws-upload-missing"><i class="icon-picture"></i><div><strong>No usable brand logo found</strong><small>Upload a new logo to replace the missing image.</small></div></div>@endif</div>
                                    <input id="remove-logo" type="hidden" name="remove_logo" value="{{ $removeLogoRequested ? '1' : '0' }}" data-remove-input="logo">
                                    @if($logoUrl)
                                        <div class="ws-asset-actions"><button type="button" class="btn btn-danger ws-remove-asset" data-remove-asset="logo" aria-pressed="{{ $removeLogoRequested ? 'true' : 'false' }}"><i class="icon-trash"></i> <span>{{ $removeLogoRequested ? 'Undo logo removal' : 'Remove current logo' }}</span></button></div>
                                        <div class="ws-removal-state" data-removal-state="logo" role="status" {{ $removeLogoRequested ? '' : 'hidden' }}><strong>Logo marked for removal.</strong> Saving will delete the managed upload file and show the Website name instead.</div>
                                    @endif
                                    <input id="brand-logo-upload" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" data-file-label data-file-asset="logo" data-image-inspect data-image-max-width="6000" data-image-max-height="6000" data-file-types="png,jpg,jpeg,webp" data-file-max="5242880" data-no-uniform="true" aria-describedby="brand-logo-help brand-logo-details brand-logo-error">
                                    <span class="ws-file-name"></span>
                                    <div id="brand-logo-help" class="ws-upload-specs"><strong>Recommended output: 600 × 200 px (3:1)</strong><br>PNG, JPG or WebP · Maximum upload: 5 MB · Maximum source: 6000 × 6000 px</div>
                                    <small id="brand-logo-details" class="ws-file-details" data-image-details aria-live="polite"></small>
                                    <span id="brand-logo-error" class="ws-upload-error" data-file-error role="alert">{{ $errors->first('logo') }}</span>
                                    <span class="ws-upload-error" role="alert">{{ $errors->first('remove_logo') }}</span>
                                    <div class="ws-resize-box">
                                        <input type="hidden" name="logo_resize_enabled" value="0">
                                        <label class="ws-resize-toggle" for="logo-resize-enabled">
                                            <input id="logo-resize-enabled" type="checkbox" name="logo_resize_enabled" value="1" data-resize-toggle="logo" aria-controls="logo-resize-options" aria-expanded="{{ $logoResizeEnabled ? 'true' : 'false' }}" {{ $logoResizeEnabled ? 'checked' : '' }}>
                                            <span><strong>Automatically resize a new logo upload</strong><small>Turn this off to keep the uploaded image at its original dimensions.</small></span>
                                        </label>
                                        <div id="logo-resize-options" class="ws-resize-options" data-resize-options="logo" {{ $logoResizeEnabled ? '' : 'hidden' }}>
                                            <div class="ws-dimension"><label for="logo-resize-width">Output width (px)</label><input id="logo-resize-width" type="number" name="logo_resize_width" min="120" max="2400" step="1" value="{{ $setting('logo_resize_width', '600') }}" data-resize-dimension></div>
                                            <div class="ws-dimension"><label for="logo-resize-height">Output height (px)</label><input id="logo-resize-height" type="number" name="logo_resize_height" min="40" max="1200" step="1" value="{{ $setting('logo_resize_height', '200') }}" data-resize-dimension></div>
                                        </div>
                                        <small class="ws-resize-note">These values control the visible logo size on the storefront and in this preview. The whole image is fitted inside the selected size without stretching or cropping, and new uploads are resized to match when the server supports image processing.</small>
                                        <span class="ws-upload-error" role="alert">{{ $errors->first('logo_resize_width') ?: $errors->first('logo_resize_height') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ws-field">
                                <label for="brand-logo-tablet-upload">Tablet logo</label>
                                <div class="ws-upload {{ $errors->has('logo_tablet') ? 'has-error' : '' }}">
                                    <div data-asset-preview="logo_tablet">@if($tabletLogoUrl)<img src="{{ $tabletLogoUrl }}" alt="Current tablet logo">@else<div class="ws-upload-missing"><i class="icon-picture"></i><div><strong>Primary logo will be used</strong><small>Shown from 721px to 1024px wide.</small></div></div>@endif</div>
                                    <input type="hidden" name="remove_logo_tablet" value="0" data-remove-input="logo_tablet">
                                    @if($tabletLogoUrl)<div class="ws-asset-actions"><button type="button" class="btn btn-danger ws-remove-asset" data-remove-asset="logo_tablet"><i class="icon-trash"></i> Remove tablet logo</button></div>@endif
                                    <input id="brand-logo-tablet-upload" type="file" name="logo_tablet" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" data-file-label data-file-asset="logo_tablet" data-file-types="png,jpg,jpeg,webp" data-file-max="5242880" data-no-uniform="true">
                                    <span class="ws-file-name"></span><div class="ws-upload-specs"><strong>Recommended: 500 × 160 px</strong><br>Optional. Primary logo is used as fallback.</div>
                                    <span class="ws-upload-error" role="alert">{{ $errors->first('logo_tablet') }}</span>
                                </div>
                            </div>
                            <div class="ws-field">
                                <label for="brand-logo-mobile-upload">Mobile logo</label>
                                <div class="ws-upload {{ $errors->has('logo_mobile') ? 'has-error' : '' }}">
                                    <div data-asset-preview="logo_mobile">@if($mobileLogoUrl)<img src="{{ $mobileLogoUrl }}" alt="Current mobile logo">@else<div class="ws-upload-missing"><i class="icon-picture"></i><div><strong>Primary logo will be used</strong><small>Shown up to 720px wide.</small></div></div>@endif</div>
                                    <input type="hidden" name="remove_logo_mobile" value="0" data-remove-input="logo_mobile">
                                    @if($mobileLogoUrl)<div class="ws-asset-actions"><button type="button" class="btn btn-danger ws-remove-asset" data-remove-asset="logo_mobile"><i class="icon-trash"></i> Remove mobile logo</button></div>@endif
                                    <input id="brand-logo-mobile-upload" type="file" name="logo_mobile" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" data-file-label data-file-asset="logo_mobile" data-file-types="png,jpg,jpeg,webp" data-file-max="5242880" data-no-uniform="true">
                                    <span class="ws-file-name"></span><div class="ws-upload-specs"><strong>Recommended: 360 × 120 px</strong><br>Optional. Primary logo is used as fallback.</div>
                                    <span class="ws-upload-error" role="alert">{{ $errors->first('logo_mobile') }}</span>
                                </div>
                            </div>
                            <div class="ws-field">
                                <label for="brand-favicon-upload">Browser icon</label>
                                <div class="ws-upload {{ $errors->hasAny(['favicon','remove_favicon','favicon_resize_width','favicon_resize_height']) ? 'has-error' : '' }} {{ $removeFaviconRequested ? 'is-removing' : '' }}">
                                    <div data-asset-preview="favicon" {{ $removeFaviconRequested ? 'hidden' : '' }}>@if(!empty($settings['favicon']))<img src="{{ asset($settings['favicon']) }}" alt="Current browser icon" style="width:48px;height:48px">@else<div class="ws-upload-missing"><i class="icon-star"></i><div><strong>No browser icon uploaded</strong><small>No icon link is added to the site until you upload one.</small></div></div>@endif</div>
                                    <input id="remove-favicon" type="hidden" name="remove_favicon" value="{{ $removeFaviconRequested ? '1' : '0' }}" data-remove-input="favicon">
                                    @if(!empty($settings['favicon']))
                                        <div class="ws-asset-actions"><button type="button" class="btn btn-danger ws-remove-asset" data-remove-asset="favicon" aria-pressed="{{ $removeFaviconRequested ? 'true' : 'false' }}"><i class="icon-trash"></i> <span>{{ $removeFaviconRequested ? 'Undo icon removal' : 'Remove current icon' }}</span></button></div>
                                        <div class="ws-removal-state" data-removal-state="favicon" role="status" {{ $removeFaviconRequested ? '' : 'hidden' }}><strong>Browser icon marked for removal.</strong> Saving will delete the managed upload file and remove the icon from site pages.</div>
                                    @endif
                                    <input id="brand-favicon-upload" type="file" name="favicon" accept=".ico,.png,.jpg,.jpeg,.webp,image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/webp" data-file-label data-file-asset="favicon" data-image-inspect data-image-max-width="4096" data-image-max-height="4096" data-file-types="ico,png,jpg,jpeg,webp" data-file-max="2097152" data-no-uniform="true" aria-describedby="brand-favicon-help brand-favicon-details brand-favicon-error">
                                    <span class="ws-file-name"></span>
                                    <div id="brand-favicon-help" class="ws-upload-specs"><strong>Recommended output: 512 × 512 px (square)</strong><br>ICO, PNG, JPG or WebP · Maximum upload: 2 MB · Maximum raster source: 4096 × 4096 px</div>
                                    <small id="brand-favicon-details" class="ws-file-details" data-image-details aria-live="polite"></small>
                                    <span id="brand-favicon-error" class="ws-upload-error" data-file-error role="alert">{{ $errors->first('favicon') }}</span>
                                    <span class="ws-upload-error" role="alert">{{ $errors->first('remove_favicon') }}</span>
                                    <div class="ws-resize-box">
                                        <input type="hidden" name="favicon_resize_enabled" value="0">
                                        <label class="ws-resize-toggle" for="favicon-resize-enabled">
                                            <input id="favicon-resize-enabled" type="checkbox" name="favicon_resize_enabled" value="1" data-resize-toggle="favicon" aria-controls="favicon-resize-options" aria-expanded="{{ $faviconResizeEnabled ? 'true' : 'false' }}" {{ $faviconResizeEnabled ? 'checked' : '' }}>
                                            <span><strong>Automatically resize a new browser icon</strong><small>ICO files are kept unchanged; PNG, JPG and WebP images can be resized.</small></span>
                                        </label>
                                        <div id="favicon-resize-options" class="ws-resize-options" data-resize-options="favicon" {{ $faviconResizeEnabled ? '' : 'hidden' }}>
                                            <div class="ws-dimension"><label for="favicon-resize-width">Output width (px)</label><input id="favicon-resize-width" type="number" name="favicon_resize_width" min="16" max="1024" step="1" value="{{ $setting('favicon_resize_width', '512') }}" data-resize-dimension></div>
                                            <div class="ws-dimension"><label for="favicon-resize-height">Output height (px)</label><input id="favicon-resize-height" type="number" name="favicon_resize_height" min="16" max="1024" step="1" value="{{ $setting('favicon_resize_height', '512') }}" data-resize-dimension></div>
                                        </div>
                                        <small class="ws-resize-note">Allowed output: 16–1024 px per side. A square size is best; the image is fitted without stretching or cropping.</small>
                                        <span class="ws-upload-error" role="alert">{{ $errors->first('favicon_resize_width') ?: $errors->first('favicon_resize_height') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ws-panel" data-settings-panel="contact">
                    <div class="ws-card"><div class="ws-card-head"><h2>Customer contact and location</h2><p>Make it easy for customers to reach and visit your business.</p></div><div class="ws-card-body">
                        <div class="ws-rule"><i class="icon-info-sign"></i><div><strong>Recommended:</strong> Add at least one phone number or email and a complete address. The customer preview updates while you type.</div></div>
                        <div class="ws-contact-layout">
                            <div><div class="ws-grid">
                                <div class="ws-field"><label for="phone"><i class="icon-phone"></i> Primary sales phone</label><input id="phone" name="phone" maxlength="40" inputmode="tel" autocomplete="tel" value="{{ $setting('phone') }}" placeholder="+880 1XXX-XXXXXX" data-contact-preview="sales"><small class="ws-help">Shown as the main storefront contact.</small></div>
                                <div class="ws-field"><label for="support_phone"><i class="icon-headphones"></i> Customer support phone</label><input id="support_phone" name="support_phone" maxlength="40" inputmode="tel" autocomplete="tel" value="{{ $setting('support_phone') }}" placeholder="+880 1XXX-XXXXXX" data-contact-preview="support"><small class="ws-help">Use a dedicated support line when available.</small></div>
                                <div class="ws-field"><label for="whatsapp_number"><i class="icon-comments"></i> WhatsApp number</label><input id="whatsapp_number" name="whatsapp_number" maxlength="40" inputmode="tel" autocomplete="tel" value="{{ $setting('whatsapp_number') }}" placeholder="+8801XXXXXXXXX" data-contact-preview="whatsapp"><small class="ws-help">Include the country code for reliable WhatsApp links.</small></div>
                                <div class="ws-field"><label for="support_email"><i class="icon-envelope"></i> Support email</label><input type="email" id="support_email" name="support_email" maxlength="150" autocomplete="email" value="{{ $setting('support_email') }}" placeholder="support@example.com" data-contact-preview="email"><small class="ws-help">Use an inbox your team checks regularly.</small></div>
                                <div class="ws-field full"><label for="shop_address"><i class="icon-map-marker"></i> Main business or shop address</label><textarea id="shop_address" name="shop_address" maxlength="500" rows="3" data-count data-contact-preview="address" placeholder="Building, road, area, city and postal code">{{ $setting('shop_address') }}</textarea><small class="ws-help">Use a complete address customers can recognize. <span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="business_hours"><i class="icon-time"></i> Business hours</label><input id="business_hours" name="business_hours" maxlength="180" value="{{ $setting('business_hours') }}" placeholder="Saturday–Thursday, 10:00 AM–8:00 PM" data-contact-preview="hours"><small class="ws-help">Mention closed days and use your local time.</small></div>
                            </div><div class="ws-location-link"><i class="icon-map-marker"></i><div><strong>Have multiple branches or warehouses?</strong><small>Manage branch addresses, pickup availability and operating hours separately.</small></div><a class="btn" href="{{ url('/stock-locations') }}"><i class="icon-external-link"></i> Manage locations</a></div></div>
                            <aside class="ws-contact-preview" aria-label="Customer contact preview" aria-live="polite"><small>Customer preview</small><h3 data-brand-output>{{ $setting('site_name', $defaults['site_name']) }}</h3><p>This is how your essential contact information will read to customers.</p>
                                @foreach(['sales'=>['icon-phone','Sales phone'],'support'=>['icon-headphones','Customer support'],'whatsapp'=>['icon-comments','WhatsApp'],'email'=>['icon-envelope','Email'],'address'=>['icon-map-marker','Main address'],'hours'=>['icon-time','Business hours']] as $previewKey=>$previewMeta)<div class="ws-contact-item" data-contact-output="{{ $previewKey }}"><i class="{{ $previewMeta[0] }}"></i><div><small>{{ $previewMeta[1] }}</small><strong data-contact-value></strong></div></div>@endforeach
                                <div class="ws-contact-empty" data-contact-empty>No contact details added yet.</div>
                            </aside>
                        </div>
                    </div>
                </section>

                <section class="ws-panel" data-settings-panel="content">
                    <div class="ws-rule"><i class="icon-info-sign"></i><div><strong>How this tab works:</strong> Each card controls one public page or message area. The long editors support rich text, so you can add bold text, links, lists, and line breaks without touching HTML.</div></div>
                    <div class="ws-card"><div class="ws-card-head"><h2>Storefront messages</h2><p>Short text used on the homepage and global notice area.</p></div><div class="ws-card-body"><div class="ws-grid">
                        <div class="ws-field full"><label for="notice_text">Top announcement</label><textarea id="notice_text" name="notice_text" maxlength="300" rows="3" data-count placeholder="Free delivery on selected products this week">{{ $setting('notice_text') }}</textarea><small class="ws-help">Keep announcements current. Leave blank to hide the notice. <span class="ws-counter"></span></small></div>
                        <div class="ws-field"><label for="hero_side_title">Feature card 1 kicker</label><input id="hero_side_title" name="hero_side_title" maxlength="120" value="{{ $setting('hero_side_title', $siteCustomizationDefaults['hero_side_title'] ?? 'Build your dream PC') }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                        <div class="ws-field"><label for="hero_side_text">Feature card 1 title</label><input id="hero_side_text" name="hero_side_text" maxlength="240" value="{{ $setting('hero_side_text', $siteCustomizationDefaults['hero_side_text'] ?? 'Expert guidance. Genuine parts.') }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                        <div class="ws-field"><label for="hero_side_button_text">Feature card 1 button</label><input id="hero_side_button_text" name="hero_side_button_text" maxlength="80" value="{{ $setting('hero_side_button_text', $siteCustomizationDefaults['hero_side_button_text']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                        <div class="ws-field"><label for="hero_side_url">Feature card 1 link</label><input id="hero_side_url" name="hero_side_url" maxlength="255" value="{{ $setting('hero_side_url', $siteCustomizationDefaults['hero_side_url']) }}"><small class="ws-help">Use a site path such as <code>/contact-us</code>, an anchor, or an HTTPS URL.</small></div>
                        <div class="ws-field"><label for="hero_side_style">Feature card 1 style</label><select id="hero_side_style" name="hero_side_style"><option value="BLUE" {{ $setting('hero_side_style', $siteCustomizationDefaults['hero_side_style']) === 'BLUE' ? 'selected' : '' }}>Blue</option><option value="ORANGE" {{ $setting('hero_side_style', $siteCustomizationDefaults['hero_side_style']) === 'ORANGE' ? 'selected' : '' }}>Orange</option><option value="LIGHT" {{ $setting('hero_side_style', $siteCustomizationDefaults['hero_side_style']) === 'LIGHT' ? 'selected' : '' }}>Light</option><option value="DARK" {{ $setting('hero_side_style', $siteCustomizationDefaults['hero_side_style']) === 'DARK' ? 'selected' : '' }}>Dark</option></select></div>
                        <div class="ws-field"><label class="ws-check"><input type="hidden" name="hero_side_enabled" value="0"><input type="checkbox" name="hero_side_enabled" value="1" {{ $setting('hero_side_enabled', $siteCustomizationDefaults['hero_side_enabled']) ? 'checked' : '' }}> Show feature card 1</label></div>
                        <div class="ws-field"><label for="hero_side_2_kicker">Feature card 2 kicker</label><input id="hero_side_2_kicker" name="hero_side_2_kicker" maxlength="120" value="{{ $setting('hero_side_2_kicker', $siteCustomizationDefaults['hero_side_2_kicker']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                        <div class="ws-field"><label for="hero_side_2_title">Feature card 2 title</label><input id="hero_side_2_title" name="hero_side_2_title" maxlength="240" value="{{ $setting('hero_side_2_title', $siteCustomizationDefaults['hero_side_2_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                        <div class="ws-field"><label for="hero_side_2_button_text">Feature card 2 button</label><input id="hero_side_2_button_text" name="hero_side_2_button_text" maxlength="80" value="{{ $setting('hero_side_2_button_text', $siteCustomizationDefaults['hero_side_2_button_text']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                        <div class="ws-field"><label for="hero_side_2_url">Feature card 2 link</label><input id="hero_side_2_url" name="hero_side_2_url" maxlength="255" value="{{ $setting('hero_side_2_url', $siteCustomizationDefaults['hero_side_2_url']) }}"><small class="ws-help">Use a site path, anchor, or HTTPS URL.</small></div>
                        <div class="ws-field"><label for="hero_side_2_style">Feature card 2 style</label><select id="hero_side_2_style" name="hero_side_2_style"><option value="BLUE" {{ $setting('hero_side_2_style', $siteCustomizationDefaults['hero_side_2_style']) === 'BLUE' ? 'selected' : '' }}>Blue</option><option value="ORANGE" {{ $setting('hero_side_2_style', $siteCustomizationDefaults['hero_side_2_style']) === 'ORANGE' ? 'selected' : '' }}>Orange</option><option value="LIGHT" {{ $setting('hero_side_2_style', $siteCustomizationDefaults['hero_side_2_style']) === 'LIGHT' ? 'selected' : '' }}>Light</option><option value="DARK" {{ $setting('hero_side_2_style', $siteCustomizationDefaults['hero_side_2_style']) === 'DARK' ? 'selected' : '' }}>Dark</option></select></div>
                        <div class="ws-field"><label class="ws-check"><input type="hidden" name="hero_side_2_enabled" value="0"><input type="checkbox" name="hero_side_2_enabled" value="1" {{ $setting('hero_side_2_enabled', $siteCustomizationDefaults['hero_side_2_enabled']) ? 'checked' : '' }}> Show feature card 2</label></div>
                    </div></div></div>
                    <div class="ws-card"><div class="ws-card-head"><h2>Homepage product sections</h2><p>Control which flagged products appear in Featured Products and New Arrivals. Product limits are capped at 50.</p></div><div class="ws-card-body"><div class="ws-grid">
                        <div class="ws-field"><label for="homepage_featured_products_limit">Featured Products count</label><input id="homepage_featured_products_limit" type="number" name="homepage_featured_products_limit" min="1" max="50" step="1" value="{{ $setting('homepage_featured_products_limit', $defaults['homepage_featured_products_limit']) }}"><small class="ws-help">Maximum 50 published products marked as featured.</small><span class="ws-upload-error" role="alert">{{ $errors->first('homepage_featured_products_limit') }}</span></div>
                        <div class="ws-field"><label for="homepage_featured_products_per_row">Featured Products per row</label><input id="homepage_featured_products_per_row" type="number" name="homepage_featured_products_per_row" min="2" max="6" step="1" value="{{ $setting('homepage_featured_products_per_row', $defaults['homepage_featured_products_per_row']) }}"><small class="ws-help">Desktop cards per row: 2–6. Smaller screens stay responsive.</small><span class="ws-upload-error" role="alert">{{ $errors->first('homepage_featured_products_per_row') }}</span></div>
                        <div class="ws-field"><label for="homepage_new_arrivals_limit">New Arrivals count</label><input id="homepage_new_arrivals_limit" type="number" name="homepage_new_arrivals_limit" min="1" max="50" step="1" value="{{ $setting('homepage_new_arrivals_limit', $defaults['homepage_new_arrivals_limit']) }}"><small class="ws-help">Maximum 50 published products marked as new arrivals.</small><span class="ws-upload-error" role="alert">{{ $errors->first('homepage_new_arrivals_limit') }}</span></div>
                        <div class="ws-field"><label for="homepage_new_arrivals_per_row">New Arrivals per row</label><input id="homepage_new_arrivals_per_row" type="number" name="homepage_new_arrivals_per_row" min="2" max="6" step="1" value="{{ $setting('homepage_new_arrivals_per_row', $defaults['homepage_new_arrivals_per_row']) }}"><small class="ws-help">Desktop cards per row: 2–6. Smaller screens stay responsive.</small><span class="ws-upload-error" role="alert">{{ $errors->first('homepage_new_arrivals_per_row') }}</span></div>
                    </div></div></div>
                    <div class="ws-card"><div class="ws-card-head"><h2>Footer content</h2><p>Information repeated at the bottom of storefront pages.</p></div><div class="ws-card-body"><div class="ws-grid">
                        <div class="ws-field full"><label for="footer_description">Business description</label><textarea id="footer_description" name="footer_description" maxlength="500" rows="4" data-count>{{ $setting('footer_description') }}</textarea><small class="ws-help">Use one or two clear sentences. <span class="ws-counter"></span></small></div>
                        <div class="ws-field full"><label for="copyright_text">Copyright line</label><input id="copyright_text" name="copyright_text" maxlength="255" value="{{ $setting('copyright_text', '© {year} '.$defaults['site_name'].'. All rights reserved.') }}"><small class="ws-help">Use <code>{year}</code> to keep the year current automatically.</small></div>
                        <div class="ws-field"><label for="footer_credit_text">Designed and developed by</label><input id="footer_credit_text" name="footer_credit_text" maxlength="120" value="{{ $setting('footer_credit_text', $defaults['footer_credit_text']) }}"><small class="ws-help">This text appears after “Designed and developed by”. Leave blank to hide the credit.</small></div>
                        <div class="ws-field"><label for="footer_credit_url">Developer link</label><input id="footer_credit_url" name="footer_credit_url" maxlength="255" value="{{ $setting('footer_credit_url', $defaults['footer_credit_url']) }}" placeholder="https://example.com"><small class="ws-help">Use an HTTPS URL, site path, or anchor. The developer text becomes clickable.</small><span class="ws-upload-error" role="alert">{{ $errors->first('footer_credit_url') }}</span></div>
                    </div></div></div>
                    <div class="ws-card">
                        <div class="ws-card-head">
                            <h2>About Us page</h2>
                            <p>Edit the customer-facing copy shown on <code>/about-us</code>. The longer fields support bold text, links, lists, and line breaks.</p>
                        </div>
                        <div class="ws-card-body">
                            <div class="ws-rule"><i class="icon-info-sign"></i><div><strong>Quick guide:</strong> Use the short fields for headings and labels. Use the rich-text editors for the paragraph copy customers will read.</div></div>
                            <div class="ws-page-editor">
                                <div class="ws-page-fields">
                                    <div class="ws-grid">
                                <div class="ws-field"><label for="about_us_hero_kicker">Hero kicker</label><input id="about_us_hero_kicker" name="about_us_hero_kicker" maxlength="60" value="{{ $setting('about_us_hero_kicker', $defaults['about_us_hero_kicker']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_hero_title">Hero title</label><input id="about_us_hero_title" name="about_us_hero_title" maxlength="180" value="{{ $setting('about_us_hero_title', $defaults['about_us_hero_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="about_us_hero_text">Hero intro</label><textarea id="about_us_hero_text" name="about_us_hero_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_hero_text', $defaults['about_us_hero_text']) }}</textarea><small class="ws-help">This text appears under the main About Us heading. Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="about_us_story_kicker">Story kicker</label><input id="about_us_story_kicker" name="about_us_story_kicker" maxlength="60" value="{{ $setting('about_us_story_kicker', $defaults['about_us_story_kicker']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_story_title">Story title</label><input id="about_us_story_title" name="about_us_story_title" maxlength="180" value="{{ $setting('about_us_story_title', $defaults['about_us_story_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="about_us_story_text_1">Story paragraph 1</label><textarea id="about_us_story_text_1" name="about_us_story_text_1" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_story_text_1', $defaults['about_us_story_text_1']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field full"><label for="about_us_story_text_2">Story paragraph 2</label><textarea id="about_us_story_text_2" name="about_us_story_text_2" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_story_text_2', $defaults['about_us_story_text_2']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="about_us_highlight_1_title">Highlight 1 title</label><input id="about_us_highlight_1_title" name="about_us_highlight_1_title" maxlength="80" value="{{ $setting('about_us_highlight_1_title', $defaults['about_us_highlight_1_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_highlight_1_text">Highlight 1 text</label><input id="about_us_highlight_1_text" name="about_us_highlight_1_text" maxlength="120" value="{{ $setting('about_us_highlight_1_text', $defaults['about_us_highlight_1_text']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_highlight_2_title">Highlight 2 title</label><input id="about_us_highlight_2_title" name="about_us_highlight_2_title" maxlength="80" value="{{ $setting('about_us_highlight_2_title', $defaults['about_us_highlight_2_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_highlight_2_text">Highlight 2 text</label><input id="about_us_highlight_2_text" name="about_us_highlight_2_text" maxlength="120" value="{{ $setting('about_us_highlight_2_text', $defaults['about_us_highlight_2_text']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_highlight_3_title">Highlight 3 title</label><input id="about_us_highlight_3_title" name="about_us_highlight_3_title" maxlength="80" value="{{ $setting('about_us_highlight_3_title', $defaults['about_us_highlight_3_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_highlight_3_text">Highlight 3 text</label><input id="about_us_highlight_3_text" name="about_us_highlight_3_text" maxlength="120" value="{{ $setting('about_us_highlight_3_text', $defaults['about_us_highlight_3_text']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_mission_title">Mission title</label><input id="about_us_mission_title" name="about_us_mission_title" maxlength="80" value="{{ $setting('about_us_mission_title', $defaults['about_us_mission_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="about_us_mission_text">Mission paragraph</label><textarea id="about_us_mission_text" name="about_us_mission_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_mission_text', $defaults['about_us_mission_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="about_us_vision_title">Vision title</label><input id="about_us_vision_title" name="about_us_vision_title" maxlength="80" value="{{ $setting('about_us_vision_title', $defaults['about_us_vision_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="about_us_vision_text">Vision paragraph</label><textarea id="about_us_vision_text" name="about_us_vision_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_vision_text', $defaults['about_us_vision_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="about_us_promise_title">Promise title</label><input id="about_us_promise_title" name="about_us_promise_title" maxlength="80" value="{{ $setting('about_us_promise_title', $defaults['about_us_promise_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="about_us_promise_text">Promise paragraph</label><textarea id="about_us_promise_text" name="about_us_promise_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_promise_text', $defaults['about_us_promise_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="about_us_capabilities_kicker">Capabilities kicker</label><input id="about_us_capabilities_kicker" name="about_us_capabilities_kicker" maxlength="60" value="{{ $setting('about_us_capabilities_kicker', $defaults['about_us_capabilities_kicker']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_capabilities_title">Capabilities title</label><input id="about_us_capabilities_title" name="about_us_capabilities_title" maxlength="180" value="{{ $setting('about_us_capabilities_title', $defaults['about_us_capabilities_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="about_us_capabilities_text">Capabilities intro</label><textarea id="about_us_capabilities_text" name="about_us_capabilities_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_capabilities_text', $defaults['about_us_capabilities_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field full"><label for="about_us_capabilities_items">Capabilities bullets</label><textarea id="about_us_capabilities_items" name="about_us_capabilities_items" maxlength="1000" rows="4" data-count placeholder="One item per line">{{ $setting('about_us_capabilities_items', $defaults['about_us_capabilities_items']) }}</textarea><small class="ws-help">Enter one bullet item per line. <span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_cta_title">CTA title</label><input id="about_us_cta_title" name="about_us_cta_title" maxlength="180" value="{{ $setting('about_us_cta_title', $defaults['about_us_cta_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="about_us_cta_button_text">CTA button text</label><input id="about_us_cta_button_text" name="about_us_cta_button_text" maxlength="80" value="{{ $setting('about_us_cta_button_text', $defaults['about_us_cta_button_text']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="about_us_cta_text">Call-to-action copy</label><textarea id="about_us_cta_text" name="about_us_cta_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('about_us_cta_text', $defaults['about_us_cta_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                    </div>
                                </div>
                                <aside class="ws-page-preview" data-page-preview="about" aria-live="polite">
                                    <div class="ws-preview-head">
                                        <div><strong>Live About page preview</strong><small>Updates as you type before saving</small></div>
                                        <span class="ws-preview-path">/about-us</span>
                                    </div>
                                    <div class="ws-preview-body">
                                        <span class="ws-preview-kicker" data-preview-text="about_us_hero_kicker"></span>
                                        <span class="ws-preview-title" data-preview-text="about_us_hero_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="about_us_hero_text"></div>
                                        <div class="ws-preview-divider"></div>
                                        <span class="ws-preview-kicker" data-preview-text="about_us_story_kicker"></span>
                                        <span class="ws-preview-title" data-preview-text="about_us_story_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="about_us_story_text_1"></div>
                                        <div class="ws-preview-copy" data-preview-rich="about_us_story_text_2"></div>
                                        <div class="ws-preview-chips">
                                            <div class="ws-preview-chip"><strong data-preview-text="about_us_highlight_1_title"></strong><small data-preview-text="about_us_highlight_1_text"></small></div>
                                            <div class="ws-preview-chip"><strong data-preview-text="about_us_highlight_2_title"></strong><small data-preview-text="about_us_highlight_2_text"></small></div>
                                            <div class="ws-preview-chip"><strong data-preview-text="about_us_highlight_3_title"></strong><small data-preview-text="about_us_highlight_3_text"></small></div>
                                        </div>
                                        <div class="ws-preview-minis">
                                            <div class="ws-preview-mini"><strong data-preview-text="about_us_mission_title"></strong><div class="ws-preview-copy" data-preview-rich="about_us_mission_text"></div></div>
                                            <div class="ws-preview-mini"><strong data-preview-text="about_us_vision_title"></strong><div class="ws-preview-copy" data-preview-rich="about_us_vision_text"></div></div>
                                            <div class="ws-preview-mini"><strong data-preview-text="about_us_promise_title"></strong><div class="ws-preview-copy" data-preview-rich="about_us_promise_text"></div></div>
                                        </div>
                                        <div class="ws-preview-divider"></div>
                                        <span class="ws-preview-kicker" data-preview-text="about_us_capabilities_kicker"></span>
                                        <span class="ws-preview-title" data-preview-text="about_us_capabilities_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="about_us_capabilities_text"></div>
                                        <ul class="ws-preview-list" data-preview-lines="about_us_capabilities_items"></ul>
                                        <div class="ws-preview-divider"></div>
                                        <span class="ws-preview-title" data-preview-text="about_us_cta_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="about_us_cta_text"></div>
                                        <span class="ws-preview-button" data-preview-text="about_us_cta_button_text"></span>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                    <div class="ws-card">
                        <div class="ws-card-head">
                            <h2>Terms &amp; conditions page</h2>
                            <p>Edit the customer-facing copy shown on <code>/terms&amp;conditions</code>. The rich-text fields below support bold text, links, lists, and line breaks.</p>
                        </div>
                        <div class="ws-card-body">
                            <div class="ws-rule"><i class="icon-info-sign"></i><div><strong>Quick guide:</strong> Use the intro and paragraph fields for rich text. Keep the list fields one item per line so they render as bullets and steps.</div></div>
                            <div class="ws-page-editor">
                                <div class="ws-page-fields">
                                    <div class="ws-grid">
                                <div class="ws-field"><label for="terms_hero_kicker">Hero kicker</label><input id="terms_hero_kicker" name="terms_hero_kicker" maxlength="60" value="{{ $setting('terms_hero_kicker', $defaults['terms_hero_kicker']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_hero_title">Hero title</label><input id="terms_hero_title" name="terms_hero_title" maxlength="180" value="{{ $setting('terms_hero_title', $defaults['terms_hero_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="terms_hero_text">Hero intro</label><textarea id="terms_hero_text" name="terms_hero_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('terms_hero_text', $defaults['terms_hero_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="terms_nav_coverage">Coverage nav label</label><input id="terms_nav_coverage" name="terms_nav_coverage" maxlength="80" value="{{ $setting('terms_nav_coverage', $defaults['terms_nav_coverage']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_nav_exclusions">Exclusions nav label</label><input id="terms_nav_exclusions" name="terms_nav_exclusions" maxlength="80" value="{{ $setting('terms_nav_exclusions', $defaults['terms_nav_exclusions']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_nav_service">Service nav label</label><input id="terms_nav_service" name="terms_nav_service" maxlength="80" value="{{ $setting('terms_nav_service', $defaults['terms_nav_service']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_nav_delivery">Delivery nav label</label><input id="terms_nav_delivery" name="terms_nav_delivery" maxlength="80" value="{{ $setting('terms_nav_delivery', $defaults['terms_nav_delivery']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_coverage_title">Coverage title</label><input id="terms_coverage_title" name="terms_coverage_title" maxlength="120" value="{{ $setting('terms_coverage_title', $defaults['terms_coverage_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="terms_coverage_text">Warranty coverage paragraph</label><textarea id="terms_coverage_text" name="terms_coverage_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('terms_coverage_text', $defaults['terms_coverage_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="terms_exclusions_title">Exclusions title</label><input id="terms_exclusions_title" name="terms_exclusions_title" maxlength="120" value="{{ $setting('terms_exclusions_title', $defaults['terms_exclusions_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="terms_exclusions_items">Exclusions bullets</label><textarea id="terms_exclusions_items" name="terms_exclusions_items" maxlength="1000" rows="4" data-count placeholder="One item per line">{{ $setting('terms_exclusions_items', $defaults['terms_exclusions_items']) }}</textarea><small class="ws-help">Enter one bullet item per line. <span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_service_title">Service title</label><input id="terms_service_title" name="terms_service_title" maxlength="120" value="{{ $setting('terms_service_title', $defaults['terms_service_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="terms_service_items">Service steps</label><textarea id="terms_service_items" name="terms_service_items" maxlength="1000" rows="4" data-count placeholder="One step per line">{{ $setting('terms_service_items', $defaults['terms_service_items']) }}</textarea><small class="ws-help">Enter one step per line. <span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_delivery_title">Delivery title</label><input id="terms_delivery_title" name="terms_delivery_title" maxlength="120" value="{{ $setting('terms_delivery_title', $defaults['terms_delivery_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="terms_delivery_text">Delivery &amp; inspection paragraph</label><textarea id="terms_delivery_text" name="terms_delivery_text" class="ws-richtext" maxlength="2000" rows="5">{{ $setting('terms_delivery_text', $defaults['terms_delivery_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                <div class="ws-field"><label for="terms_help_title">Help title</label><input id="terms_help_title" name="terms_help_title" maxlength="120" value="{{ $setting('terms_help_title', $defaults['terms_help_title']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field"><label for="terms_help_button_text">Help button text</label><input id="terms_help_button_text" name="terms_help_button_text" maxlength="80" value="{{ $setting('terms_help_button_text', $defaults['terms_help_button_text']) }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                                <div class="ws-field full"><label for="terms_help_text">Support note</label><textarea id="terms_help_text" name="terms_help_text" class="ws-richtext" maxlength="2000" rows="4">{{ $setting('terms_help_text', $defaults['terms_help_text']) }}</textarea><small class="ws-help">Up to 2000 characters.</small></div>
                                    </div>
                                </div>
                                <aside class="ws-page-preview" data-page-preview="terms" aria-live="polite">
                                    <div class="ws-preview-head">
                                        <div><strong>Live Terms page preview</strong><small>Updates as you type before saving</small></div>
                                        <span class="ws-preview-path">/terms&amp;conditions</span>
                                    </div>
                                    <div class="ws-preview-body">
                                        <span class="ws-preview-kicker" data-preview-text="terms_hero_kicker"></span>
                                        <span class="ws-preview-title" data-preview-text="terms_hero_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="terms_hero_text"></div>
                                        <div class="ws-preview-nav">
                                            <span data-preview-text="terms_nav_coverage"></span>
                                            <span data-preview-text="terms_nav_exclusions"></span>
                                            <span data-preview-text="terms_nav_service"></span>
                                            <span data-preview-text="terms_nav_delivery"></span>
                                        </div>
                                        <div class="ws-preview-divider"></div>
                                        <span class="ws-preview-title" data-preview-text="terms_coverage_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="terms_coverage_text"></div>
                                        <div class="ws-preview-mini">
                                            <strong data-preview-text="terms_exclusions_title"></strong>
                                            <ul class="ws-preview-list" data-preview-lines="terms_exclusions_items"></ul>
                                        </div>
                                        <div class="ws-preview-mini">
                                            <strong data-preview-text="terms_service_title"></strong>
                                            <ol class="ws-preview-list" data-preview-lines="terms_service_items"></ol>
                                        </div>
                                        <div class="ws-preview-divider"></div>
                                        <span class="ws-preview-title" data-preview-text="terms_delivery_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="terms_delivery_text"></div>
                                        <div class="ws-preview-divider"></div>
                                        <span class="ws-preview-title" data-preview-text="terms_help_title"></span>
                                        <div class="ws-preview-copy" data-preview-rich="terms_help_text"></div>
                                        <span class="ws-preview-button" data-preview-text="terms_help_button_text"></span>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                    <div class="ws-card"><div class="ws-card-body ws-footer-link"><div><strong>Homepage banners</strong><small>Campaign banners are managed in their own workspace.</small></div><a class="btn btn-primary" href="{{ url('/banner-management') }}"><i class="icon-picture"></i> Open Banner Studio</a></div></div>
                    <div class="ws-card" id="catalog-import-workspace" style="scroll-margin-top:90px"><div class="ws-card-head"><h2>Catalog source import</h2><p>Control whether the {{ $catalogSourceLabel }} source import workspace appears on catalog admin pages.</p></div><div class="ws-card-body"><div class="ws-grid">
                        <div class="ws-field full"><label class="dm-toggle"><input type="hidden" name="startech_source_import_enabled" value="0"><input type="checkbox" id="startech_source_import_enabled" name="startech_source_import_enabled" value="1" {{ $startechSourceImportEnabled ? 'checked' : '' }}> Show {{ $catalogSourceLabel }} source import workspace</label><small class="ws-help">When enabled, the source import panel appears on Manage Category, Manage Subcategory, Manage Manufacturer, Manage Product, Catalog Attributes, Catalog Hierarchy, and the Catalog Import Center. Use Fetch only to preview selected source data without saving changes.</small></div>
                        <div class="ws-rule"><i class="icon-download-alt"></i><div><strong>Step-by-step imports stay available.</strong> The category, subcategory, brand, series, and product selectors still control what gets fetched or imported on each page.</div></div>
                        <div class="ws-field"><a class="btn btn-primary" href="{{ url('/catalog-imports') }}"><i class="icon-external-link"></i> Open catalog import center</a></div>
                    </div></div></div>
                </section>

                <section class="ws-panel" data-settings-panel="theme">
                    @include('admin.components.storefront-theme-manager')
                </section>

                <section class="ws-panel" data-settings-panel="development-mode">
                    <div class="ws-card"><div class="ws-card-head"><h2>Development Mode</h2><p>Temporarily hide the public storefront behind a controlled service message.</p></div><div class="ws-card-body">
                        <div class="dm-status {{ $developmentModeEnabled ? 'is-active' : '' }}" data-dm-status><div><b data-dm-status-label>{{ $developmentModeEnabled ? 'Development Mode Active' : 'Development Mode Off' }}</b><div data-dm-status-copy>{{ $developmentModeEnabled ? 'Public website is hidden. Only the Development Mode message and admin access are available.' : 'Public website is available.' }}</div></div><label class="dm-toggle"><input type="hidden" name="development_mode_enabled" value="0"><input type="checkbox" id="development_mode_enabled" name="development_mode_enabled" value="1" {{ $developmentModeEnabled ? 'checked' : '' }}> Turn on</label></div>
                        <div class="ws-rule"><i class="icon-warning-sign"></i><div>Admin login, authenticated admin pages, required assets, and authenticated integration APIs remain available. Public storefront pages return HTTP 503 while this mode is active.</div></div>
                        <div class="ws-grid">
                            <div class="ws-field"><label for="development_mode_message_type">Message Type <span class="ws-required">*</span></label><select id="development_mode_message_type" name="development_mode_message_type" required>@foreach(['development'=>'Development in Progress','maintenance'=>'Scheduled Maintenance','coming_soon'=>'Coming Soon','system_upgrade'=>'System Upgrade','emergency'=>'Temporary Service Interruption','custom'=>'Custom Message'] as $value=>$label)<option value="{{ $value }}" {{ $setting('development_mode_message_type','maintenance')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                            <div class="ws-field"><label for="development_mode_title">Page Title <span class="ws-required">*</span></label><input id="development_mode_title" name="development_mode_title" maxlength="150" value="{{ $setting('development_mode_title','Website Under Development') }}" required data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                            <div class="ws-field full"><label for="development_mode_message">Custom Message <span class="ws-required">*</span></label><textarea id="development_mode_message" name="development_mode_message" maxlength="2000" rows="4" required data-count>{{ $setting('development_mode_message','We are currently improving our website. Please check back again soon.') }}</textarea><small class="ws-help">Plain text only. <span class="ws-counter"></span></small></div>
                            <div class="ws-field full"><label for="development_mode_additional_message">Optional Additional Message</label><textarea id="development_mode_additional_message" name="development_mode_additional_message" maxlength="2000" rows="3" data-count>{{ $setting('development_mode_additional_message') }}</textarea><small class="ws-help"><span class="ws-counter"></span></small></div>
                            <div class="ws-field"><label for="development_mode_availability_text">Optional estimated availability text</label><input id="development_mode_availability_text" name="development_mode_availability_text" maxlength="255" value="{{ $setting('development_mode_availability_text') }}" placeholder="We will be back shortly." data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                            <div class="ws-field"><label>Admin Login button</label><label class="dm-toggle"><input type="hidden" name="development_mode_show_admin_login" value="0"><input type="checkbox" id="development_mode_show_admin_login" name="development_mode_show_admin_login" value="1" {{ (string)$setting('development_mode_show_admin_login','1')==='1'?'checked':'' }}> Show button</label></div>
                            <div class="ws-field full"><label for="development_mode_login_button_text">Admin Login button text</label><input id="development_mode_login_button_text" name="development_mode_login_button_text" maxlength="100" value="{{ $setting('development_mode_login_button_text','Admin Login') }}" data-count><small class="ws-help"><span class="ws-counter"></span></small></div>
                            <div class="full"><label style="font-weight:700">Live preview</label><div class="dm-preview" aria-live="polite"><span class="dm-preview-badge" data-dm-preview="badge"></span><h3 data-dm-preview="title"></h3><p data-dm-preview="message"></p><p class="dm-preview-extra" data-dm-preview="additional"></p><div class="dm-preview-availability" data-dm-preview="availability"></div><br><span class="dm-preview-button" data-dm-preview="button"></span></div></div>
                        </div>
                    </div></div>
                </section>

                <section class="ws-panel" data-settings-panel="seo">
                    <div class="ws-card"><div class="ws-card-head"><h2>Search engine defaults</h2><p>Fallback information for pages without their own SEO content.</p></div><div class="ws-card-body">
                        <div class="ws-rule"><i class="icon-info-sign"></i><div><strong>Business rule:</strong> “No index” options can remove pages from search results. Use <strong>Index and follow</strong> for a live public store.</div></div>
                        <div class="ws-grid">
                            <div class="ws-field full"><label for="default_meta_title">Default search title</label><input id="default_meta_title" name="default_meta_title" maxlength="70" value="{{ $setting('default_meta_title') }}" placeholder="Online computers and accessories | Store name" data-count><small class="ws-help">Aim for 50–60 characters. <span class="ws-counter"></span></small></div>
                            <div class="ws-field full"><label for="default_meta_description">Default search description</label><textarea id="default_meta_description" name="default_meta_description" maxlength="320" rows="3" data-count placeholder="Describe products, service area and the main customer benefit.">{{ $setting('default_meta_description') }}</textarea><small class="ws-help">Aim for 140–160 useful characters. <span class="ws-counter"></span></small></div>
                            <div class="ws-field"><label for="meta_keywords">Keywords</label><textarea id="meta_keywords" name="meta_keywords" maxlength="500" rows="3" data-count placeholder="computers, laptops, networking">{{ $setting('meta_keywords') }}</textarea><small class="ws-help">Optional; separate phrases with commas. <span class="ws-counter"></span></small></div>
                            <div class="ws-field"><label for="robots_directive">Search visibility</label><select id="robots_directive" name="robots_directive">@foreach(['index,follow'=>'Index and follow (recommended)','index,nofollow'=>'Index but do not follow links','noindex,follow'=>'Hide from search, follow links','noindex,nofollow'=>'Hide from search completely'] as $value=>$label)<option value="{{ $value }}" {{ $setting('robots_directive','index,follow')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select><small class="ws-help">Changing this affects all pages using the default.</small></div>
                            <div class="ws-field full"><label>Social sharing image</label><div class="ws-upload {{ $errors->hasAny(['seo_image','remove_seo_image']) ? 'has-error' : '' }} {{ $removeSeoImageRequested ? 'is-removing' : '' }}"><div data-asset-preview="seo_image" {{ $removeSeoImageRequested ? 'hidden' : '' }}>@if(!empty($settings['default_og_image']))<img class="ws-og" src="{{ asset($settings['default_og_image']) }}" alt="Current social sharing image">@else<div class="ws-upload-missing"><i class="icon-picture"></i><div><strong>No social sharing image uploaded</strong><small>The storefront uses the fallback branding image until you upload one.</small></div></div>@endif</div><input id="remove-seo-image" type="hidden" name="remove_seo_image" value="{{ $removeSeoImageRequested ? '1' : '0' }}" data-remove-input="seo_image">@if(!empty($settings['default_og_image']))<div class="ws-asset-actions"><button type="button" class="btn btn-danger ws-remove-asset" data-remove-asset="seo_image" aria-pressed="{{ $removeSeoImageRequested ? 'true' : 'false' }}"><i class="icon-trash"></i> <span>{{ $removeSeoImageRequested ? 'Undo sharing image removal' : 'Remove current social image' }}</span></button></div><div class="ws-removal-state" data-removal-state="seo_image" role="status" {{ $removeSeoImageRequested ? '' : 'hidden' }}><strong>Social sharing image marked for removal.</strong> Saving will delete the managed upload file and clear the default Open Graph image.</div>@endif<input type="file" name="seo_image" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" data-file-label data-file-asset="seo_image" data-image-inspect data-image-max-width="4096" data-image-max-height="4096" data-file-types="png,jpg,jpeg,webp" data-file-max="4194304" data-no-uniform="true" aria-describedby="seo-image-help seo-image-details seo-image-error"><span class="ws-file-name"></span><div id="seo-image-help" class="ws-upload-specs"><strong>Recommended output: 1200 × 630 px (1.91:1)</strong><br>PNG, JPG or WebP · Maximum upload: 4 MB · Maximum source: 4096 × 4096 px</div><small id="seo-image-details" class="ws-file-details" data-image-details aria-live="polite"></small><span id="seo-image-error" class="ws-upload-error" data-file-error role="alert">{{ $errors->first('seo_image') }}</span><span class="ws-upload-error" role="alert">{{ $errors->first('remove_seo_image') }}</span></div></div>
                        </div>
                    </div></div>
                </section>

                <section class="ws-panel" data-settings-panel="connections">
                    <div class="ws-card"><div class="ws-card-head"><h2>Social profiles</h2><p>Only completed links should be displayed to customers.</p></div><div class="ws-card-body"><div class="ws-grid">
                        @foreach(['facebook_url'=>'Facebook','instagram_url'=>'Instagram','youtube_url'=>'YouTube','linkedin_url'=>'LinkedIn','twitter_url'=>'X / Twitter'] as $key=>$label)<div class="ws-field"><label for="{{ $key }}">{{ $label }} URL</label><input type="url" id="{{ $key }}" name="{{ $key }}" maxlength="255" placeholder="https://" value="{{ $setting($key) }}"><small class="ws-help">Enter the complete public profile URL.</small></div>@endforeach
                    </div></div></div>
                    <div class="ws-card"><div class="ws-card-head"><h2>Google services</h2><p>Optional analytics and ownership verification.</p></div><div class="ws-card-body">
                        <div class="ws-rule"><i class="icon-lock"></i><div>Enter identifiers only. Never paste an entire script or HTML meta tag.</div></div><div class="ws-grid">
                            <div class="ws-field"><label for="google_analytics_id">GA4 Measurement ID</label><input id="google_analytics_id" name="google_analytics_id" maxlength="30" placeholder="G-XXXXXXXXXX" value="{{ $setting('google_analytics_id') }}"><small class="ws-help">Must start with <strong>G-</strong>. Leave blank to disable tracking.</small></div>
                            <div class="ws-field"><label for="google_site_verification">Search Console verification value</label><input id="google_site_verification" name="google_site_verification" maxlength="255" value="{{ $setting('google_site_verification') }}"><small class="ws-help">Paste only the value inside the meta tag's content attribute.</small></div>
                        </div>
                    </div></div>
                </section>
            </main>
        </div>
        <div class="ws-savebar"><span><i class="icon-info-sign"></i> Changes become public immediately after saving.</span><button type="button" class="btn btn-warning" data-reset-settings><i class="icon-refresh"></i> Reset to Default</button> <button type="submit" class="btn btn-primary"><i class="halflings-icon white ok"></i> Save website settings</button></div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    function showMissingAsset(image){
        if(!image)return;
        image.hidden=true;
        var preview=image.closest('[data-asset-preview]');
        if(!preview||preview.querySelector('.ws-upload-missing'))return;
        var missing=document.createElement('div');
        missing.className='ws-upload-missing';
        missing.innerHTML='<i class="icon-picture"></i><div><strong>Branding file could not be loaded</strong><small>Upload the file again or check the public asset path.</small></div>';
        preview.appendChild(missing);
    }
    document.querySelectorAll('[data-asset-preview] img').forEach(function(image){
        image.addEventListener('error',function(){showMissingAsset(image)});
        if(image.complete&&image.naturalWidth===0)showMissingAsset(image);
    });
    var buttons=document.querySelectorAll('[data-settings-tab]'),panels=document.querySelectorAll('[data-settings-panel]');
    function openTab(name){buttons.forEach(function(b){b.classList.toggle('active',b.getAttribute('data-settings-tab')===name)});panels.forEach(function(p){p.classList.toggle('active',p.getAttribute('data-settings-panel')===name)});if(history.replaceState)history.replaceState(null,'','#'+name)}
    buttons.forEach(function(button){button.addEventListener('click',function(){openTab(this.getAttribute('data-settings-tab'))})});
    var requested=location.hash.replace('#','')||@json($initialPanel ?? '');if(document.querySelector('[data-settings-panel="'+requested+'"]'))openTab(requested);
    var defaults=@json($defaults),form=document.getElementById('website-settings-form'),resetInput=document.getElementById('reset_to_default');
    document.querySelectorAll('[data-count]').forEach(function(field){var counter=field.parentNode.querySelector('.ws-counter');function update(){if(counter)counter.textContent=field.value.length+' / '+field.getAttribute('maxlength')}field.addEventListener('input',update);update()});
    var nameText=document.querySelector('[data-name-text]'),nameSize=document.querySelector('[data-name-size]'),namePreview=document.querySelector('[data-name-preview]');
    function updateNamePreview(){if(!namePreview)return;var text=nameText&&nameText.value.trim()?nameText.value.trim():(defaults.site_name||'Your Website name'),size=parseInt(nameSize&&nameSize.value?nameSize.value:'23',10);size=Math.max(14,Math.min(32,isNaN(size)?23:size));var bengali=/[\u0980-\u09FF]/.test(text);namePreview.textContent=text;namePreview.style.fontSize=size+'px';namePreview.classList.toggle('is-bengali',bengali);if(bengali)namePreview.setAttribute('lang','bn');else namePreview.removeAttribute('lang')}
    [nameText,nameSize].forEach(function(field){if(field)field.addEventListener('input',updateNamePreview)});updateNamePreview();
    var taglineText=document.querySelector('[data-tagline-text]'),taglineSize=document.querySelector('[data-tagline-size]'),taglinePreview=document.querySelector('[data-tagline-preview]');
    function updateTaglinePreview(){if(!taglinePreview)return;var text=taglineText&&taglineText.value.trim()?taglineText.value.trim():'Your short tagline preview',size=parseInt(taglineSize&&taglineSize.value?taglineSize.value:'12',10);size=Math.max(8,Math.min(24,isNaN(size)?12:size));var bengali=/[\u0980-\u09FF]/.test(text);taglinePreview.textContent=text;taglinePreview.style.fontSize=size+'px';taglinePreview.classList.toggle('is-bengali',bengali);if(bengali)taglinePreview.setAttribute('lang','bn');else taglinePreview.removeAttribute('lang')}
    [taglineText,taglineSize].forEach(function(field){if(field)field.addEventListener('input',updateTaglinePreview)});updateTaglinePreview();
    var assetLabels={logo:{remove:'Remove current logo',undo:'Undo logo removal'},favicon:{remove:'Remove current icon',undo:'Undo icon removal'},seo_image:{remove:'Remove current social image',undo:'Undo sharing image removal'}};
    function setAssetRemoval(key,removing){
        var input=document.querySelector('[data-remove-input="'+key+'"]'),button=document.querySelector('[data-remove-asset="'+key+'"]'),preview=document.querySelector('[data-asset-preview="'+key+'"]'),status=document.querySelector('[data-removal-state="'+key+'"]'),file=document.querySelector('[data-file-asset="'+key+'"]'),holder=file?file.closest('.ws-upload'):null,labels=assetLabels[key]||{remove:'Remove current asset',undo:'Undo removal'};
        if(input)input.value=removing?'1':'0';
        if(preview)preview.hidden=removing;
        if(status)status.hidden=!removing;
        if(holder)holder.classList.toggle('is-removing',removing);
        if(button){button.setAttribute('aria-pressed',removing?'true':'false');var label=button.querySelector('span');if(label)label.textContent=removing?labels.undo:labels.remove}
        if(removing&&file){file.value='';file.setCustomValidity('');var name=holder.querySelector('.ws-file-name'),details=holder.querySelector('[data-image-details]'),error=holder.querySelector('[data-file-error]');if(name)name.textContent='';if(details)details.textContent='';if(error)error.textContent=''}
    }
    document.querySelectorAll('[data-remove-asset]').forEach(function(button){var key=button.getAttribute('data-remove-asset'),input=document.querySelector('[data-remove-input="'+key+'"]');button.addEventListener('click',function(){var removing=!(input&&input.value==='1');if(removing&&!window.confirm('This managed upload file will be permanently deleted after you save Website Settings. Continue?'))return;setAssetRemoval(key,removing)});setAssetRemoval(key,!!(input&&input.value==='1'))});
    function setTextValue(id,value){var field=document.getElementById(id);if(!field)return;field.value=value;if(window.jQuery){var editor=window.jQuery(field).data('cleditor');if(editor&&typeof editor.updateFrame==='function')editor.updateFrame()}field.dispatchEvent(new Event('input',{bubbles:true}));field.dispatchEvent(new Event('change',{bubbles:true}))}
    function setCheckboxValue(id,checked){var field=document.getElementById(id);if(!field)return;field.checked=checked;field.dispatchEvent(new Event('change',{bubbles:true}))}
    function resetWebsiteSettings(){
        if(resetInput)resetInput.value='1';
        setTextValue('site_name',defaults.site_name||'Ecommerce');
        setTextValue('site_name_font_size',String(defaults.site_name_font_size||23));
        setTextValue('site_tagline','');
        setTextValue('site_tagline_font_size',String(defaults.site_tagline_font_size||12));
        setTextValue('notice_text','');
        setTextValue('phone','');
        setTextValue('support_phone','');
        setTextValue('whatsapp_number','');
        setTextValue('support_email','');
        setTextValue('shop_address','');
        setTextValue('business_hours','');
        setTextValue('footer_description','');
        setTextValue('copyright_text','© {year} '+(defaults.site_name||'Ecommerce')+'. All rights reserved.');
        setTextValue('footer_credit_text',defaults.footer_credit_text||'Lucent Tech BD');
        setTextValue('footer_credit_url',defaults.footer_credit_url||'');
        setTextValue('hero_side_title','');
        setTextValue('hero_side_text','');
        [
            'about_us_hero_kicker',
            'about_us_hero_title',
            'about_us_hero_text',
            'about_us_story_kicker',
            'about_us_story_title',
            'about_us_story_text_1',
            'about_us_story_text_2',
            'about_us_highlight_1_title',
            'about_us_highlight_1_text',
            'about_us_highlight_2_title',
            'about_us_highlight_2_text',
            'about_us_highlight_3_title',
            'about_us_highlight_3_text',
            'about_us_mission_title',
            'about_us_mission_text',
            'about_us_vision_title',
            'about_us_vision_text',
            'about_us_promise_title',
            'about_us_promise_text',
            'about_us_capabilities_kicker',
            'about_us_capabilities_title',
            'about_us_capabilities_text',
            'about_us_capabilities_items',
            'about_us_cta_title',
            'about_us_cta_text',
            'about_us_cta_button_text',
            'terms_hero_kicker',
            'terms_hero_title',
            'terms_hero_text',
            'terms_nav_coverage',
            'terms_nav_exclusions',
            'terms_nav_service',
            'terms_nav_delivery',
            'terms_coverage_title',
            'terms_coverage_text',
            'terms_exclusions_title',
            'terms_exclusions_items',
            'terms_service_title',
            'terms_service_items',
            'terms_delivery_title',
            'terms_delivery_text',
            'terms_help_title',
            'terms_help_text',
            'terms_help_button_text'
        ].forEach(function(key){setTextValue(key,defaults[key]||'')});
        setTextValue('google_analytics_id','');
        setTextValue('google_site_verification','');
        setTextValue('default_meta_title','');
        setTextValue('default_meta_description','');
        setTextValue('meta_keywords','');
        setTextValue('robots_directive',defaults.robots_directive||'index,follow');
        setCheckboxValue('startech_source_import_enabled',true);
        setCheckboxValue('logo_resize_enabled',true);
        setTextValue('logo_resize_width',String(defaults.logo_resize_width||600));
        setTextValue('logo_resize_height',String(defaults.logo_resize_height||200));
        setCheckboxValue('favicon_resize_enabled',true);
        setTextValue('favicon_resize_width',String(defaults.favicon_resize_width||512));
        setTextValue('favicon_resize_height',String(defaults.favicon_resize_height||512));
        setCheckboxValue('development_mode_enabled',false);
        setTextValue('development_mode_message_type',defaults.development_mode_message_type||'maintenance');
        setTextValue('development_mode_title',defaults.development_mode_title||'Website Under Development');
        setTextValue('development_mode_message',defaults.development_mode_message||'We are currently improving our website. Please check back again soon.');
        setTextValue('development_mode_additional_message','');
        setTextValue('development_mode_availability_text','');
        setCheckboxValue('development_mode_show_admin_login',true);
        setTextValue('development_mode_login_button_text',defaults.development_mode_login_button_text||'Admin Login');
        setTextValue('homepage_featured_products_limit',String(defaults.homepage_featured_products_limit||20));
        setTextValue('homepage_featured_products_per_row',String(defaults.homepage_featured_products_per_row||5));
        setTextValue('homepage_new_arrivals_limit',String(defaults.homepage_new_arrivals_limit||20));
        setTextValue('homepage_new_arrivals_per_row',String(defaults.homepage_new_arrivals_per_row||5));
        if(window.storefrontThemeManager&&typeof window.storefrontThemeManager.resetAll==='function')window.storefrontThemeManager.resetAll();
        ['logo','favicon','seo_image'].forEach(function(key){setAssetRemoval(key,true)});
        document.querySelectorAll('.ws-upload.has-error,.ws-field.has-error').forEach(function(node){node.classList.remove('has-error')});
        document.querySelectorAll('.ws-upload-error').forEach(function(node){node.textContent=''});
        openTab('identity');
        if(history.replaceState)history.replaceState(null,'','#identity');
    }
    var resetButton=document.querySelector('[data-reset-settings]');
    if(resetButton){resetButton.addEventListener('click',function(){if(!window.confirm('Reset website settings to default?\n\nThis will remove your customized website settings and custom uploaded branding files.\n\nThis action will take effect after saving.\n\nContinue?'))return;resetWebsiteSettings()})}
    function readableFileSize(bytes){if(bytes>=1048576)return(bytes/1048576).toFixed(bytes>=10485760?0:1)+' MB';return Math.max(1,Math.round(bytes/1024))+' KB'}
    function setFileState(field,holder,error,message){field.setCustomValidity(message);if(error)error.textContent=message;if(holder)holder.classList.toggle('has-error',!!message)}
    document.querySelectorAll('[data-file-label]').forEach(function(field){
        field.addEventListener('change',function(){
            var holder=this.closest('.ws-upload'),label=holder?holder.querySelector('.ws-file-name'):null,error=holder?holder.querySelector('[data-file-error]'):null,details=holder?holder.querySelector('[data-image-details]'):null,file=this.files.length?this.files[0]:null,allowed=(this.getAttribute('data-file-types')||'').split(',').filter(Boolean),maximum=parseInt(this.getAttribute('data-file-max')||'0',10),message='',extension='';
            if(file&&this.hasAttribute('data-file-asset'))setAssetRemoval(this.getAttribute('data-file-asset'),false);
            if(file){var parts=file.name.toLowerCase().split('.');extension=parts.length>1?parts.pop():'';if(allowed.length&&allowed.indexOf(extension)===-1)message='Choose one of these file types: '+allowed.join(', ').toUpperCase()+'.';else if(maximum&&file.size>maximum)message='This file is too large. The maximum size is '+(maximum/1048576)+' MB.'}
            if(label)label.textContent=file?file.name:'';
            if(details)details.textContent=file?'Selected file: '+readableFileSize(file.size):'';
            setFileState(this,holder,error,message);
            if(!file||message||!this.hasAttribute('data-image-inspect'))return;
            var selectedFile=file,image=new Image(),url=URL.createObjectURL(file),maxWidth=parseInt(this.getAttribute('data-image-max-width')||'0',10),maxHeight=parseInt(this.getAttribute('data-image-max-height')||'0',10);
            image.onload=function(){
                URL.revokeObjectURL(url);
                if(!field.files.length||field.files[0]!==selectedFile)return;
                var pixelMessage='';
                if((maxWidth&&image.naturalWidth>maxWidth)||(maxHeight&&image.naturalHeight>maxHeight))pixelMessage='This image is '+image.naturalWidth+' × '+image.naturalHeight+' px. The maximum source size is '+maxWidth+' × '+maxHeight+' px.';
                if(details)details.textContent='Selected: '+image.naturalWidth+' × '+image.naturalHeight+' px · '+readableFileSize(selectedFile.size);
                setFileState(field,holder,error,pixelMessage);
            };
            image.onerror=function(){URL.revokeObjectURL(url);if(!field.files.length||field.files[0]!==selectedFile)return;if(details)details.textContent='Selected: '+readableFileSize(selectedFile.size)+' · Pixel dimensions could not be detected in this browser.'};
            image.src=url;
        });
    });
    document.querySelectorAll('[data-resize-toggle]').forEach(function(toggle){
        function updateResizeOptions(){var key=toggle.getAttribute('data-resize-toggle'),options=document.querySelector('[data-resize-options="'+key+'"]'),enabled=toggle.checked;if(options){options.hidden=!enabled;options.querySelectorAll('[data-resize-dimension]').forEach(function(input){input.required=enabled})}toggle.setAttribute('aria-expanded',enabled?'true':'false')}
        toggle.addEventListener('change',updateResizeOptions);updateResizeOptions();
    });
    function updateLogoPreviewSize(){var preview=document.querySelector('[data-asset-preview="logo"] img'),widthInput=document.getElementById('logo-resize-width'),heightInput=document.getElementById('logo-resize-height');if(!preview||!widthInput||!heightInput)return;var width=parseInt(widthInput.value||'600',10),height=parseInt(heightInput.value||'200',10);width=Math.max(120,Math.min(2400,isNaN(width)?600:width));height=Math.max(40,Math.min(1200,isNaN(height)?200:height));var displayWidth=Math.max(120,Math.min(240,Math.round(width*220/600))),displayHeight=Math.max(40,Math.min(82,Math.round(height*73/200)));preview.style.width=displayWidth+'px';preview.style.height=displayHeight+'px';preview.style.maxWidth='none';preview.style.maxHeight='none';preview.style.objectFit='contain'}
    document.querySelectorAll('#logo-resize-width,#logo-resize-height').forEach(function(field){field.addEventListener('input',updateLogoPreviewSize);field.addEventListener('change',updateLogoPreviewSize)});updateLogoPreviewSize();
    function updateContactPreview(){var hasValue=false;document.querySelectorAll('[data-contact-preview]').forEach(function(field){var key=field.getAttribute('data-contact-preview'),row=document.querySelector('[data-contact-output="'+key+'"]'),value=field.value.trim();if(!row)return;row.style.display=value?'flex':'none';var output=row.querySelector('[data-contact-value]');if(output)output.textContent=value;if(value)hasValue=true});var empty=document.querySelector('[data-contact-empty]');if(empty)empty.style.display=hasValue?'none':'block'}
    document.querySelectorAll('[data-contact-preview]').forEach(function(field){field.addEventListener('input',updateContactPreview)});updateContactPreview();
    function pagePreviewFieldValue(key){var field=document.getElementById(key),value=field?field.value:'';return value.trim()?value:(defaults[key]||'')}
    function syncPreviewRichText(key){var field=document.getElementById(key);if(field&&window.jQuery){var editor=window.jQuery(field).data('cleditor');if(editor&&typeof editor.updateTextArea==='function')editor.updateTextArea()}return pagePreviewFieldValue(key)}
    function escapePreviewHtml(value){return String(value||'').replace(/[&<>"']/g,function(character){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]})}
    function sanitizePreviewHtml(value){
        var template=document.createElement('template'),allowed={p:1,br:1,strong:1,b:1,em:1,i:1,u:1,s:1,strike:1,ul:1,ol:1,li:1,blockquote:1,a:1,div:1,span:1,hr:1,sub:1,sup:1,code:1,pre:1};
        template.innerHTML=String(value||'');
        function clean(parent){
            Array.prototype.slice.call(parent.childNodes).forEach(function(node){
                if(node.nodeType===1){
                    var tag=node.nodeName.toLowerCase();
                    if(!allowed[tag]){node.parentNode.replaceChild(document.createTextNode(node.textContent||''),node);return}
                    Array.prototype.slice.call(node.attributes).forEach(function(attribute){
                        var name=attribute.name.toLowerCase(),safeLink=tag==='a'&&(name==='href'||name==='title'||name==='target'||name==='rel');
                        if(!safeLink){node.removeAttribute(attribute.name);return}
                        if(name==='href'&&!/^(https?:|mailto:|tel:|#|\/)/i.test(attribute.value.trim()))node.removeAttribute(attribute.name);
                    });
                    if(tag==='a'&&node.getAttribute('target')==='_blank')node.setAttribute('rel','noopener noreferrer');
                    clean(node);
                }else if(node.nodeType!==3)node.parentNode.removeChild(node);
            });
        }
        clean(template.content);
        return template.innerHTML;
    }
    function previewRichHtml(value){value=String(value||'').trim();if(!value)return '<span class="ws-preview-empty">Empty field</span>';if(/<\/?[a-z][\s\S]*>/i.test(value))return sanitizePreviewHtml(value);return escapePreviewHtml(value).replace(/\r?\n/g,'<br>')}
    function updatePagePreviews(){
        document.querySelectorAll('[data-preview-text]').forEach(function(output){var value=pagePreviewFieldValue(output.getAttribute('data-preview-text')).trim();output.textContent=value||'Empty field';output.classList.toggle('ws-preview-empty',!value)});
        document.querySelectorAll('[data-preview-rich]').forEach(function(output){output.innerHTML=previewRichHtml(syncPreviewRichText(output.getAttribute('data-preview-rich')))});
        document.querySelectorAll('[data-preview-lines]').forEach(function(list){var value=pagePreviewFieldValue(list.getAttribute('data-preview-lines')),lines=value.split(/\r?\n/).map(function(line){return line.trim()}).filter(Boolean);list.innerHTML='';if(!lines.length){var empty=document.createElement('li');empty.className='ws-preview-empty';empty.textContent='Empty field';list.appendChild(empty);return}lines.forEach(function(line){var item=document.createElement('li');item.textContent=line;list.appendChild(item)})});
    }
    function collectPagePreviewKeys(){var keys=[];['text','rich','lines'].forEach(function(type){document.querySelectorAll('[data-preview-'+type+']').forEach(function(node){var key=node.getAttribute('data-preview-'+type);if(key&&keys.indexOf(key)===-1)keys.push(key)})});return keys}
    collectPagePreviewKeys().forEach(function(key){var field=document.getElementById(key);if(field)['input','change','keyup','blur'].forEach(function(eventName){field.addEventListener(eventName,updatePagePreviews)})});
    function bindRichTextPreviewFrames(){if(!window.jQuery)return;document.querySelectorAll('.ws-richtext').forEach(function(field){var editor=window.jQuery(field).data('cleditor');if(!editor||!editor.doc||field.getAttribute('data-preview-frame-bound'))return;field.setAttribute('data-preview-frame-bound','1');['keyup','input','paste','mouseup'].forEach(function(eventName){editor.doc.addEventListener(eventName,function(){setTimeout(function(){if(typeof editor.updateTextArea==='function')editor.updateTextArea();field.dispatchEvent(new Event('input',{bubbles:true}))},0)})})})}
    updatePagePreviews();setTimeout(function(){bindRichTextPreviewFrames();updatePagePreviews()},500);setTimeout(function(){bindRichTextPreviewFrames();updatePagePreviews()},1200);
    var brandInput=document.querySelector('[data-brand-preview]'),brandOutput=document.querySelector('[data-brand-output]');if(brandInput&&brandOutput)brandInput.addEventListener('input',function(){brandOutput.textContent=this.value.trim()||(defaults.site_name||'Your business')});
    var dmEnabled=document.getElementById('development_mode_enabled'),initialDm={{ $developmentModeEnabled ? 'true' : 'false' }},dmTypes={development:'Development in Progress',maintenance:'Scheduled Maintenance',coming_soon:'Coming Soon',system_upgrade:'System Upgrade',emergency:'Temporary Service Interruption',custom:'Custom Message'};
    function dmValue(id){var el=document.getElementById(id);return el?el.value:''}
    function dmPreview(name,value){var el=document.querySelector('[data-dm-preview="'+name+'"]');if(el){el.textContent=value;el.style.display=value?'inline-block':'none'}}
    function updateDevelopmentPreview(){var type=dmValue('development_mode_message_type');dmPreview('badge',dmTypes[type]||dmTypes.maintenance);dmPreview('title',dmValue('development_mode_title')||defaults.development_mode_title||'Website Under Development');dmPreview('message',dmValue('development_mode_message')||defaults.development_mode_message||'We are currently improving our website. Please check back again soon.');dmPreview('additional',dmValue('development_mode_additional_message'));dmPreview('availability',dmValue('development_mode_availability_text'));dmPreview('button',document.getElementById('development_mode_show_admin_login').checked?(dmValue('development_mode_login_button_text')||defaults.development_mode_login_button_text||'Admin Login'):'');var status=document.querySelector('[data-dm-status]'),on=dmEnabled.checked;if(status){status.classList.toggle('is-active',on);status.querySelector('[data-dm-status-label]').textContent=on?'Development Mode Active':'Development Mode Off';status.querySelector('[data-dm-status-copy]').textContent=on?'Public website is hidden. Only the Development Mode message and admin access are available.':'Public website is available.'}}
    ['development_mode_message_type','development_mode_title','development_mode_message','development_mode_additional_message','development_mode_availability_text','development_mode_show_admin_login','development_mode_login_button_text','development_mode_enabled'].forEach(function(id){var el=document.getElementById(id);if(el){el.addEventListener('input',updateDevelopmentPreview);el.addEventListener('change',updateDevelopmentPreview)}});updateDevelopmentPreview();
    document.getElementById('website-settings-form').addEventListener('submit',function(event){if(resetInput&&resetInput.value==='1')return;if(dmEnabled.checked===initialDm)return;var message=dmEnabled.checked?'Enabling Development Mode will temporarily hide the public website and display your custom message. The admin login and admin panel will remain accessible. Do you want to continue?':'Disabling Development Mode will restore the public website immediately. Do you want to continue?';if(!window.confirm(message))event.preventDefault()});
    @if($errors->any())
    var invalid=document.querySelector('.ws-upload.has-error input,.ws-field.has-error input,.ws-field.has-error textarea,.ws-field.has-error select');if(invalid){var invalidPanel=invalid.closest('[data-settings-panel]');if(invalidPanel)openTab(invalidPanel.getAttribute('data-settings-panel'));invalid.focus()}
    @endif
});
</script>
@endsection
