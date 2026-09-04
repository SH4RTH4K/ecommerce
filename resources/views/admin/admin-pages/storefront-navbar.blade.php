@extends('admin.admin-master')

@section('admin_main_content')
@php
    $lv = function ($key, $fallback = null) use ($layout) { return old('layout.'.$key, $layout[$key] ?? $fallback); };
    $selected = function ($key, $value) use ($lv) { return (string) $lv($key) === (string) $value ? 'selected' : ''; };
@endphp
<link rel="stylesheet" href="{{ asset('css/admin-storefront-navbar.css') }}?v={{ filemtime(public_path('css/admin-storefront-navbar.css')) }}">

<div id="content" class="span10 snb-page">
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>Website <i class="icon-angle-right"></i></li><li>Navbar Management</li></ul>
    @if(session('message'))<div class="snb-alert snb-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="snb-alert snb-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="snb-alert snb-error">{{ $errors->first() }}</div>@endif

    <header class="snb-head">
        <div><h2>Navbar Management</h2><p>Choose what customers see, arrange it visually, and tune each screen size without changing catalog data.</p></div>
        <details class="snb-reset-menu">
            <summary class="snb-btn snb-btn-secondary">Reset options</summary>
            <div>
                <form method="post" action="{{ route('admin.storefront-navbar.reset-design') }}" onsubmit="return confirm('Reset only navbar design settings?');">@csrf<button type="submit">Reset design only</button></form>
                <form method="post" action="{{ route('admin.storefront-navbar.reset-items') }}" onsubmit="return confirm('Reset only navbar item settings?');">@csrf<button type="submit">Reset items only</button></form>
                <form method="post" action="{{ route('admin.storefront-navbar.reset') }}" onsubmit="return confirm('Reset every navbar setting?');">@csrf<button type="submit" class="is-danger">Reset everything</button></form>
            </div>
        </details>
    </header>

    <form method="post" action="{{ route('admin.storefront-navbar.save') }}" id="snb-form">
        @csrf
        <section class="snb-card snb-preview-card" aria-labelledby="snb-preview-title">
            <div class="snb-section-head">
                <div><h3 id="snb-preview-title">Live preview</h3><p>Updates as you edit. Enabled items are shown in their current order.</p></div>
                <div class="snb-device-tabs" role="group" aria-label="Preview screen size"><button type="button" class="is-active" data-preview-device="desktop">Desktop</button><button type="button" data-preview-device="tablet">Tablet</button><button type="button" data-preview-device="mobile">Mobile</button></div>
            </div>
            <div class="snb-preview-frame is-desktop" data-preview-frame><div id="snb-preview" class="snb-preview" aria-live="polite"></div></div>
            <div class="snb-preview-meta"><span><strong id="snb-count">0</strong> enabled</span><span id="snb-fit-status">Checking fit…</span></div>
            <div id="snb-warning" class="snb-warning" hidden>These items may overflow at this width. Try the Compact preset, shorter labels, or two rows.</div>
        </section>

        <nav class="snb-workspace-tabs" aria-label="Navbar management sections"><button type="button" class="is-active" data-workspace-tab="items">Menu items</button><button type="button" data-workspace-tab="design">Design &amp; responsive</button></nav>

        <section data-workspace-panel="items">
            <div class="snb-card">
                <div class="snb-section-head">
                    <div><h3>Menu items</h3><p>Enable, rename, and arrange category links. Drag the handle or use the arrows.</p></div>
                    <div class="snb-toolbar"><label><span class="sr-only">Search categories</span><input type="search" id="snb-search" placeholder="Search categories…"></label><select id="snb-filter" aria-label="Filter categories"><option value="all">All categories</option><option value="shown">Enabled</option><option value="hidden">Disabled</option></select></div>
                </div>
                <div class="snb-bulk-actions"><button type="button" class="snb-text-btn" data-bulk="enable">Enable filtered</button><button type="button" class="snb-text-btn" data-bulk="disable">Disable filtered</button><button type="button" class="snb-text-btn" data-expand-all>Expand all</button><span id="snb-filter-count" class="snb-note"></span></div>
                <div id="snb-list" class="snb-item-list">
                    @forelse($items as $item)
                        @php
                            $category = $item->category; $categoryId = (int) $category->category_id;
                            $subcategories = collect($category->navbarManageSubCategories ?? []);
                            $showItem = old('items.'.$categoryId.'.show_in_navbar', $item->show_in_navbar);
                            $showChildren = old('items.'.$categoryId.'.show_subcategories', $item->show_subcategories);
                        @endphp
                        <article class="snb-item {{ $showItem ? 'is-enabled' : '' }}" data-row data-category-id="{{ $categoryId }}" data-name="{{ strtolower($category->category_name.' '.$item->display_name) }}">
                            <div class="snb-item-main">
                                <button type="button" class="snb-drag" data-drag aria-label="Drag {{ $category->category_name }} to reorder" title="Drag to reorder">&#8942;&#8942;</button>
                                <div class="snb-order-buttons" aria-label="Move {{ $category->category_name }}"><button type="button" data-move="up" aria-label="Move up" title="Move up">&#8593;</button><button type="button" data-move="down" aria-label="Move down" title="Move down">&#8595;</button></div>
                                <div class="snb-item-identity"><strong>{{ $category->category_name }}</strong><span data-row-status>{{ $showItem ? 'Visible in navbar' : 'Hidden from navbar' }}</span></div>
                                <label class="snb-toggle"><input type="checkbox" name="items[{{ $categoryId }}][show_in_navbar]" value="1" {{ $showItem ? 'checked' : '' }} data-show data-no-uniform="true"><span aria-hidden="true"></span><b>Show in Navbar</b></label>
                                <button type="button" class="snb-settings-toggle" data-settings-toggle aria-expanded="false">Edit <span aria-hidden="true">&#9662;</span></button>
                            </div>
                            <div class="snb-item-settings" data-item-settings hidden>
                                <div class="snb-field-grid">
                                    <label><span>Navbar label</span><input type="text" name="items[{{ $categoryId }}][display_name]" value="{{ old('items.'.$categoryId.'.display_name', $item->display_name) }}" maxlength="255" placeholder="{{ $category->category_name }}" data-label><small>Leave blank to use the category name.</small></label>
                                    <label><span>Dropdown</span><select name="items[{{ $categoryId }}][show_subcategories]"><option value="1" {{ $showChildren ? 'selected' : '' }}>Show subcategories</option><option value="0" {{ !$showChildren ? 'selected' : '' }}>Category link only</option></select></label>
                                    <label><span>Order</span><input type="number" name="items[{{ $categoryId }}][priority]" value="{{ old('items.'.$categoryId.'.priority', $item->priority) }}" min="0" max="999999" required data-priority><small>Updated automatically when moved.</small></label>
                                </div>
                                @if($subcategories->isNotEmpty())
                                    <details class="snb-sub-details"><summary>Manage {{ $subcategories->count() }} subcategories</summary>
                                        <div class="snb-sub-head"><span>Drag</span><span>Label</span><span>Visible</span><span>Order</span></div>
                                        <ol class="snb-sub-list" data-sub-list>
                                            @foreach($subcategories as $subCategory)
                                                @php
                                                    $subId = (int) $subCategory->sub_category_id;
                                                    $subLabel = old('subcategories.'.$categoryId.'.'.$subId.'.navbar_name', $subCategory->navbar_name ?: $subCategory->sub_category_name);
                                                    $subShown = old('subcategories.'.$categoryId.'.'.$subId.'.show_in_navbar', $subCategory->show_in_navbar);
                                                @endphp
                                                <li class="snb-sub-row" data-sub-row><button type="button" class="snb-sub-drag" data-sub-drag aria-label="Drag {{ $subCategory->sub_category_name }}">&#8942;&#8942;</button><input type="text" name="subcategories[{{ $categoryId }}][{{ $subId }}][navbar_name]" value="{{ $subLabel }}" maxlength="255" aria-label="Navbar name for {{ $subCategory->sub_category_name }}"><label class="snb-mini-check"><input type="checkbox" name="subcategories[{{ $categoryId }}][{{ $subId }}][show_in_navbar]" value="1" {{ $subShown ? 'checked' : '' }} data-no-uniform="true"><span class="sr-only">Show {{ $subCategory->sub_category_name }}</span></label><input type="number" name="subcategories[{{ $categoryId }}][{{ $subId }}][navbar_order]" value="{{ old('subcategories.'.$categoryId.'.'.$subId.'.navbar_order', $subCategory->navbar_order ?: $subCategory->display_order) }}" min="0" max="999999" aria-label="Order for {{ $subCategory->sub_category_name }}" data-sub-priority></li>
                                            @endforeach
                                        </ol>
                                    </details>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="snb-empty">No categories are available.</div>
                    @endforelse
                </div>
                <div id="snb-no-results" class="snb-empty" hidden>No categories match this filter.</div>
            </div>
        </section>

        <section data-workspace-panel="design" hidden>
            <div class="snb-card"><div class="snb-section-head"><div><h3>Quick presets</h3><p>Start with a proven layout, then fine-tune any setting below.</p></div></div><div class="snb-presets"><button type="button" data-preset="standard"><strong>Standard</strong><span>One clean scrolling row</span></button><button type="button" data-preset="compact"><strong>Compact</strong><span>Fit more categories</span></button><button type="button" data-preset="centered"><strong>Centered</strong><span>Balanced presentation</span></button><button type="button" data-preset="two_rows"><strong>Two rows</strong><span>Show everything clearly</span></button></div></div>

            <div class="snb-card snb-settings-groups">
                <details open><summary><span><strong>Layout</strong><small>Visibility, width, rows, and alignment</small></span></summary><div class="snb-layout-grid">
                    <label><span>Navbar status</span><select name="layout[enabled]"><option value="1" {{ $lv('enabled') ? 'selected' : '' }}>Enabled</option><option value="0" {{ !$lv('enabled') ? 'selected' : '' }}>Disabled</option></select></label>
                    <label><span>Desktop display</span><select name="layout[desktop_enabled]"><option value="1" {{ $lv('desktop_enabled') ? 'selected' : '' }}>Show</option><option value="0" {{ !$lv('desktop_enabled') ? 'selected' : '' }}>Hide</option></select></label>
                    <label><span>Container width</span><select name="layout[container_mode]"><option value="FULL_WIDTH" {{ $selected('container_mode','FULL_WIDTH') }}>Full width</option><option value="CONTENT_WIDTH" {{ $selected('container_mode','CONTENT_WIDTH') }}>Site content width</option><option value="CUSTOM_MAX_WIDTH" {{ $selected('container_mode','CUSTOM_MAX_WIDTH') }}>Custom maximum</option></select></label>
                    <label data-show-when="container_mode:CUSTOM_MAX_WIDTH"><span>Maximum width (px)</span><input type="number" name="layout[custom_max_width]" value="{{ $lv('custom_max_width') }}" min="800" max="1800" required></label>
                    <label><span>Row mode</span><select name="layout[row_mode]"><option value="SINGLE_ROW" {{ $selected('row_mode','SINGLE_ROW') }}>Single row</option><option value="WRAP" {{ $selected('row_mode','WRAP') }}>Wrap to rows</option><option value="AUTO" {{ $selected('row_mode','AUTO') }}>Automatic</option></select></label>
                    <label><span>Maximum rows</span><select name="layout[max_rows]"><option value="1" {{ $selected('max_rows','1') }}>1 row</option><option value="2" {{ $selected('max_rows','2') }}>2 rows</option><option value="3" {{ $selected('max_rows','3') }}>3 rows</option><option value="AUTO" {{ $selected('max_rows','AUTO') }}>Automatic</option></select></label>
                    <label><span>Navbar alignment</span><select name="layout[alignment]"><option value="LEFT" {{ $selected('alignment','LEFT') }}>Left</option><option value="CENTER" {{ $selected('alignment','CENTER') }}>Center</option><option value="RIGHT" {{ $selected('alignment','RIGHT') }}>Right</option><option value="SPACE_BETWEEN" {{ $selected('alignment','SPACE_BETWEEN') }}>Space between</option><option value="SPACE_AROUND" {{ $selected('alignment','SPACE_AROUND') }}>Space around</option><option value="SPACE_EVENLY" {{ $selected('alignment','SPACE_EVENLY') }}>Space evenly</option></select></label>
                    <label><span>Wrapped row alignment</span><select name="layout[row_alignment]"><option value="LEFT" {{ $selected('row_alignment','LEFT') }}>Left</option><option value="CENTER" {{ $selected('row_alignment','CENTER') }}>Center</option><option value="RIGHT" {{ $selected('row_alignment','RIGHT') }}>Right</option><option value="SPACE_BETWEEN" {{ $selected('row_alignment','SPACE_BETWEEN') }}>Space between</option><option value="SPACE_AROUND" {{ $selected('row_alignment','SPACE_AROUND') }}>Space around</option><option value="SPACE_EVENLY" {{ $selected('row_alignment','SPACE_EVENLY') }}>Space evenly</option></select></label>
                    <label><span>When items do not fit</span><select name="layout[overflow_behavior]"><option value="HORIZONTAL_SCROLL" {{ $selected('overflow_behavior','HORIZONTAL_SCROLL') }}>Scroll horizontally</option><option value="REDUCE_SPACING" {{ $selected('overflow_behavior','REDUCE_SPACING') }}>Reduce spacing</option><option value="REDUCE_FONT_WITHIN_LIMIT" {{ $selected('overflow_behavior','REDUCE_FONT_WITHIN_LIMIT') }}>Reduce font size</option><option value="ALLOW_EXTRA_ROW" {{ $selected('overflow_behavior','ALLOW_EXTRA_ROW') }}>Add another row</option><option value="COMPACT_ITEMS" {{ $selected('overflow_behavior','COMPACT_ITEMS') }}>Compact items</option></select></label>
                    <label><span>Minimum height (px)</span><input type="number" name="layout[minimum_height]" value="{{ $lv('minimum_height') }}" min="32" max="80" required></label>
                    <label class="snb-check"><input type="hidden" name="layout[sticky]" value="0"><input type="checkbox" name="layout[sticky]" value="1" {{ $lv('sticky') ? 'checked' : '' }} data-no-uniform="true"><span>Keep navbar sticky while scrolling</span></label>
                </div></details>

                <details><summary><span><strong>Items &amp; typography</strong><small>Text, spacing, and item sizing</small></span></summary><div class="snb-layout-grid">
                    <label><span>Desktop font (px)</span><input type="number" name="layout[font_size_desktop]" value="{{ $lv('font_size_desktop') }}" min="11" max="24" required></label><label><span>Tablet font (px)</span><input type="number" name="layout[font_size_tablet]" value="{{ $lv('font_size_tablet') }}" min="10" max="22" required></label><label><span>Mobile font (px)</span><input type="number" name="layout[font_size_mobile]" value="{{ $lv('font_size_mobile') }}" min="12" max="24" required></label>
                    <label><span>Font weight</span><select name="layout[font_weight]"><option value="400" {{ $selected('font_weight','400') }}>Normal</option><option value="500" {{ $selected('font_weight','500') }}>Medium</option><option value="600" {{ $selected('font_weight','600') }}>Semi bold</option><option value="700" {{ $selected('font_weight','700') }}>Bold</option></select></label>
                    <label><span>Horizontal space between items (px)</span><input type="number" name="layout[item_gap]" value="{{ $lv('item_gap') }}" min="0" max="40" required><small>Extra horizontal space from one category to the next.</small></label>
                    <label><span>Space between rows (px)</span><input type="number" name="layout[row_gap]" value="{{ $lv('row_gap', $lv('item_gap')) }}" min="0" max="40" required><small>Vertical space used only when the navbar wraps.</small></label>
                    <label><span>Space inside item — horizontal (px)</span><input type="number" name="layout[item_padding_x]" value="{{ $lv('item_padding_x', $lv('padding_x')) }}" min="0" max="40" required><small>Space on both sides of each category label.</small></label>
                    <label><span>Space inside item — vertical (px)</span><input type="number" name="layout[item_padding_y]" value="{{ $lv('item_padding_y', $lv('padding_y')) }}" min="0" max="30" required></label>
                    <label><span>Navbar side padding (px)</span><input type="number" name="layout[padding_x]" value="{{ $lv('padding_x') }}" min="0" max="30" required></label><label><span>Navbar top/bottom padding (px)</span><input type="number" name="layout[padding_y]" value="{{ $lv('padding_y') }}" min="0" max="30" required></label>
                    <label><span>Item text alignment</span><select name="layout[item_text_alignment]"><option value="LEFT" {{ $selected('item_text_alignment','LEFT') }}>Left</option><option value="CENTER" {{ $selected('item_text_alignment','CENTER') }}>Center</option><option value="RIGHT" {{ $selected('item_text_alignment','RIGHT') }}>Right</option></select></label>
                    <label><span>Item width</span><select name="layout[item_width_mode]"><option value="AUTO" {{ $selected('item_width_mode','AUTO') }}>Fit label</option><option value="EQUAL_WIDTH" {{ $selected('item_width_mode','EQUAL_WIDTH') }}>Equal widths</option></select></label>
                    <label><span>Minimum item width (px)</span><input type="number" name="layout[minimum_item_width]" value="{{ $lv('minimum_item_width') }}" min="50" max="240" required></label>
                    <label><span>Long labels</span><select name="layout[label_wrap]"><option value="NO_WRAP" {{ $selected('label_wrap','NO_WRAP') }}>Keep on one line</option><option value="ALLOW_WRAP" {{ $selected('label_wrap','ALLOW_WRAP') }}>Allow wrapping</option></select></label>
                </div></details>

                <details><summary><span><strong>Tablet</strong><small>Optional tablet-specific behavior</small></span></summary><div class="snb-layout-grid">
                    <label><span>Tablet settings</span><select name="layout[tablet_mode]"><option value="SAME_AS_DESKTOP" {{ $selected('tablet_mode','SAME_AS_DESKTOP') }}>Use desktop settings</option><option value="CUSTOM" {{ $selected('tablet_mode','CUSTOM') }}>Customize tablet</option></select></label>
                    <label data-tablet-custom><span>Alignment</span><select name="layout[tablet_alignment]"><option value="LEFT" {{ $selected('tablet_alignment','LEFT') }}>Left</option><option value="CENTER" {{ $selected('tablet_alignment','CENTER') }}>Center</option><option value="RIGHT" {{ $selected('tablet_alignment','RIGHT') }}>Right</option><option value="SPACE_BETWEEN" {{ $selected('tablet_alignment','SPACE_BETWEEN') }}>Space between</option><option value="SPACE_AROUND" {{ $selected('tablet_alignment','SPACE_AROUND') }}>Space around</option><option value="SPACE_EVENLY" {{ $selected('tablet_alignment','SPACE_EVENLY') }}>Space evenly</option></select></label>
                    <label data-tablet-custom><span>Maximum rows</span><select name="layout[tablet_max_rows]"><option value="1" {{ $selected('tablet_max_rows','1') }}>1 row</option><option value="2" {{ $selected('tablet_max_rows','2') }}>2 rows</option><option value="3" {{ $selected('tablet_max_rows','3') }}>3 rows</option><option value="AUTO" {{ $selected('tablet_max_rows','AUTO') }}>Automatic</option></select></label>
                    <label data-tablet-custom><span>Item gap (px)</span><input type="number" name="layout[tablet_item_gap]" value="{{ $lv('tablet_item_gap') }}" min="0" max="40" required></label>
                    <label data-tablet-custom><span>When items do not fit</span><select name="layout[tablet_overflow_behavior]"><option value="HORIZONTAL_SCROLL" {{ $selected('tablet_overflow_behavior','HORIZONTAL_SCROLL') }}>Scroll horizontally</option><option value="REDUCE_SPACING" {{ $selected('tablet_overflow_behavior','REDUCE_SPACING') }}>Reduce spacing</option><option value="REDUCE_FONT_WITHIN_LIMIT" {{ $selected('tablet_overflow_behavior','REDUCE_FONT_WITHIN_LIMIT') }}>Reduce font size</option><option value="ALLOW_EXTRA_ROW" {{ $selected('tablet_overflow_behavior','ALLOW_EXTRA_ROW') }}>Add another row</option><option value="COMPACT_ITEMS" {{ $selected('tablet_overflow_behavior','COMPACT_ITEMS') }}>Compact items</option></select></label>
                </div></details>

                <details><summary><span><strong>Mobile &amp; dropdowns</strong><small>Menu presentation and submenu sizing</small></span></summary><div class="snb-layout-grid">
                    <label><span>Mobile menu</span><select name="layout[mobile_mode]"><option value="OFF_CANVAS" {{ $selected('mobile_mode','OFF_CANVAS') }}>Off-canvas drawer</option><option value="DROPDOWN" {{ $selected('mobile_mode','DROPDOWN') }}>Dropdown</option><option value="FULL_SCREEN" {{ $selected('mobile_mode','FULL_SCREEN') }}>Full screen</option></select></label>
                    <label><span>Mobile alignment</span><select name="layout[mobile_alignment]"><option value="LEFT" {{ $selected('mobile_alignment','LEFT') }}>Left</option><option value="CENTER" {{ $selected('mobile_alignment','CENTER') }}>Center</option><option value="RIGHT" {{ $selected('mobile_alignment','RIGHT') }}>Right</option></select></label>
                    <label><span>Mobile subcategories</span><select name="layout[mobile_subcategory_display]"><option value="ACCORDION" {{ $selected('mobile_subcategory_display','ACCORDION') }}>Accordion</option><option value="EXPANDED" {{ $selected('mobile_subcategory_display','EXPANDED') }}>Always expanded</option></select></label>
                    <label><span>Dropdown alignment</span><select name="layout[dropdown_alignment]"><option value="AUTO" {{ $selected('dropdown_alignment','AUTO') }}>Automatic</option><option value="LEFT" {{ $selected('dropdown_alignment','LEFT') }}>Left</option><option value="CENTER" {{ $selected('dropdown_alignment','CENTER') }}>Center</option><option value="RIGHT" {{ $selected('dropdown_alignment','RIGHT') }}>Right</option></select></label>
                    <label><span>Dropdown width</span><select name="layout[dropdown_width_mode]"><option value="AUTO" {{ $selected('dropdown_width_mode','AUTO') }}>Fit content</option><option value="FIXED" {{ $selected('dropdown_width_mode','FIXED') }}>Fixed width</option><option value="MEGA_MENU_WIDTH" {{ $selected('dropdown_width_mode','MEGA_MENU_WIDTH') }}>Full menu width</option></select></label>
                    <label data-show-when="dropdown_width_mode:FIXED"><span>Fixed width (px)</span><input type="number" name="layout[dropdown_width]" value="{{ $lv('dropdown_width') }}" min="200" max="500" required></label>
                </div></details>

                <details><summary><span><strong>Decoration</strong><small>Borders, corners, hover, and active styles</small></span></summary><div class="snb-layout-grid">
                    <label><span>Shadow</span><select name="layout[shadow_style]"><option value="NONE" {{ $selected('shadow_style','NONE') }}>None</option><option value="SUBTLE" {{ $selected('shadow_style','SUBTLE') }}>Subtle</option><option value="MEDIUM" {{ $selected('shadow_style','MEDIUM') }}>Medium</option></select></label>
                    <label><span>Active item</span><select name="layout[active_indicator_style]"><option value="NONE" {{ $selected('active_indicator_style','NONE') }}>None</option><option value="UNDERLINE" {{ $selected('active_indicator_style','UNDERLINE') }}>Underline</option><option value="BOTTOM_BORDER" {{ $selected('active_indicator_style','BOTTOM_BORDER') }}>Bottom border</option><option value="BACKGROUND" {{ $selected('active_indicator_style','BACKGROUND') }}>Background</option><option value="TEXT_ONLY" {{ $selected('active_indicator_style','TEXT_ONLY') }}>Text only</option></select></label>
                    <label><span>Hover effect</span><select name="layout[hover_style]"><option value="TEXT" {{ $selected('hover_style','TEXT') }}>Text color</option><option value="UNDERLINE" {{ $selected('hover_style','UNDERLINE') }}>Underline</option><option value="BACKGROUND" {{ $selected('hover_style','BACKGROUND') }}>Background</option><option value="TEXT_UNDERLINE" {{ $selected('hover_style','TEXT_UNDERLINE') }}>Text + underline</option></select></label>
                    <label><span>Navbar radius (px)</span><input type="number" name="layout[border_radius]" value="{{ $lv('border_radius') }}" min="0" max="12" required></label><label><span>Item radius (px)</span><input type="number" name="layout[item_radius]" value="{{ $lv('item_radius') }}" min="0" max="12" required></label>
                    <label class="snb-check"><input type="hidden" name="layout[bottom_border]" value="0"><input type="checkbox" name="layout[bottom_border]" value="1" {{ $lv('bottom_border') ? 'checked' : '' }} data-no-uniform="true"><span>Show bottom border</span></label><label><span>Border width (px)</span><input type="number" name="layout[border_width]" value="{{ $lv('border_width') }}" min="0" max="4" required></label>
                </div></details>
            </div>
        </section>

        <div class="snb-savebar"><span id="snb-save-state">No unsaved changes</span><div><a href="{{ url('/') }}" target="_blank" rel="noopener" class="snb-btn snb-btn-secondary">View storefront</a><button type="submit" class="snb-btn">Save Navbar</button></div></div>
    </form>
</div>
<script src="{{ asset('js/admin-storefront-navbar.js') }}?v={{ filemtime(public_path('js/admin-storefront-navbar.js')) }}" defer></script>
@endsection
