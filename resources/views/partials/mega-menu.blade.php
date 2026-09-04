@php
    $navbarItems = collect(data_get($navbar ?? [], 'items', []));
    $navbarContext = (array) data_get($navbar ?? [], 'context', []);
    $navbarLayout = (array) data_get($navbar ?? [], 'layout', []);
    $activeCategoryId = (int) ($navbarContext['category_id'] ?? 0);
    $activeSubCategoryId = (int) ($navbarContext['sub_category_id'] ?? 0);
    $layoutClass = function ($value) { return strtolower(str_replace('_', '-', (string) $value)); };
    $navbarStyle = implode(';', [
        '--navbar-min-height:'.(int) ($navbarLayout['minimum_height'] ?? 42).'px',
        '--navbar-gap:'.(int) ($navbarLayout['item_gap'] ?? 0).'px',
        '--navbar-row-gap:'.(int) ($navbarLayout['row_gap'] ?? $navbarLayout['item_gap'] ?? 0).'px',
        '--navbar-padding-x:'.(int) ($navbarLayout['padding_x'] ?? 8).'px',
        '--navbar-padding-y:'.(int) ($navbarLayout['padding_y'] ?? 8).'px',
        '--navbar-item-padding-x:'.(int) ($navbarLayout['item_padding_x'] ?? $navbarLayout['padding_x'] ?? 8).'px',
        '--navbar-item-padding-y:'.(int) ($navbarLayout['item_padding_y'] ?? $navbarLayout['padding_y'] ?? 8).'px',
        '--navbar-font-size:'.(int) ($navbarLayout['font_size_desktop'] ?? 14).'px',
        '--navbar-font-size-tablet:'.(int) ($navbarLayout['font_size_tablet'] ?? 13).'px',
        '--navbar-font-size-mobile:'.(int) ($navbarLayout['font_size_mobile'] ?? 14).'px',
        '--navbar-font-weight:'.(int) ($navbarLayout['font_weight'] ?? 600),
        '--navbar-item-text-align:'.strtolower((string) ($navbarLayout['item_text_alignment'] ?? 'CENTER')),
        '--navbar-min-item-width:'.(int) ($navbarLayout['minimum_item_width'] ?? 80).'px',
        '--navbar-custom-width:'.(int) ($navbarLayout['custom_max_width'] ?? 1320).'px',
        '--navbar-tablet-gap:'.(int) ($navbarLayout['tablet_item_gap'] ?? 0).'px',
        '--navbar-dropdown-width:'.(int) ($navbarLayout['dropdown_width'] ?? 260).'px',
        '--navbar-border-width:'.(int) ($navbarLayout['border_width'] ?? 1).'px',
        '--navbar-radius:'.(int) ($navbarLayout['border_radius'] ?? 0).'px',
        '--navbar-item-radius:'.(int) ($navbarLayout['item_radius'] ?? 0).'px',
    ]);
@endphp

<nav id="main-menu" class="lt-mega lt-startech-menu lt-navbar-pending nav-align-{{ $layoutClass($navbarLayout['alignment'] ?? 'LEFT') }} nav-row-align-{{ $layoutClass($navbarLayout['row_alignment'] ?? 'LEFT') }} nav-row-{{ $layoutClass($navbarLayout['row_mode'] ?? 'SINGLE_ROW') }} nav-container-{{ $layoutClass($navbarLayout['container_mode'] ?? 'CONTENT_WIDTH') }} nav-width-{{ $layoutClass($navbarLayout['item_width_mode'] ?? 'AUTO') }} nav-wrap-{{ $layoutClass($navbarLayout['label_wrap'] ?? 'NO_WRAP') }} nav-shadow-{{ $layoutClass($navbarLayout['shadow_style'] ?? 'SUBTLE') }} nav-active-{{ $layoutClass($navbarLayout['active_indicator_style'] ?? 'UNDERLINE') }} nav-hover-{{ $layoutClass($navbarLayout['hover_style'] ?? 'TEXT_UNDERLINE') }} nav-overflow-{{ $layoutClass($navbarLayout['overflow_behavior'] ?? 'HORIZONTAL_SCROLL') }} nav-mobile-{{ $layoutClass($navbarLayout['mobile_mode'] ?? 'OFF_CANVAS') }} nav-mobile-align-{{ $layoutClass($navbarLayout['mobile_alignment'] ?? 'LEFT') }} nav-mobile-subcats-{{ $layoutClass($navbarLayout['mobile_subcategory_display'] ?? 'ACCORDION') }} nav-tablet-{{ $layoutClass($navbarLayout['tablet_mode'] ?? 'SAME_AS_DESKTOP') }} nav-tablet-overflow-{{ $layoutClass($navbarLayout['tablet_overflow_behavior'] ?? 'HORIZONTAL_SCROLL') }} {{ empty($navbarLayout['enabled']) ? 'nav-disabled' : '' }} {{ !empty($navbarLayout['sticky']) ? 'lt-navbar-sticky' : '' }} {{ empty($navbarLayout['bottom_border']) ? 'nav-no-border' : '' }} {{ empty($navbarLayout['desktop_enabled']) ? 'nav-desktop-disabled' : '' }}" style="{{ $navbarStyle }}" data-navbar-layout data-max-rows="{{ $navbarLayout['max_rows'] ?? 1 }}" data-dropdown-align="{{ strtolower($navbarLayout['dropdown_alignment'] ?? 'AUTO') }}" data-dropdown-width="{{ strtolower($navbarLayout['dropdown_width_mode'] ?? 'AUTO') }}" aria-label="Storefront categories">
    <div class="lt-container lt-category-nav" data-navbar-items>
        @forelse($navbarItems as $item)
            @php
                $category = $item->category;
                $subCategories = $item->show_subcategories && $category ? collect($category->navbarSubCategories ?? []) : collect();
                $categoryId = (int) optional($category)->category_id;
                $isActiveCategory = $activeCategoryId && $categoryId === $activeCategoryId;
            @endphp
            @if($category)
                <div class="lt-category-item {{ $isActiveCategory ? 'is-active' : '' }} {{ $subCategories->isNotEmpty() ? 'has-children' : '' }}"
                     data-navbar-item data-navbar-order="{{ $item->priority }}" data-category-id="{{ $categoryId }}">
                    <a class="lt-category-link" href="{{ url('/product-by-category/'.$categoryId) }}" title="{{ $item->label() }}" @if($isActiveCategory) aria-current="page" @endif>
                        <span>{{ $item->label() }}</span>
                    </a>
                    @if($subCategories->isNotEmpty())
                        <div class="lt-category-dropdown" aria-label="{{ $item->label() }} subcategories">
                            <a class="lt-menu-category-title" href="{{ url('/product-by-category/'.$categoryId) }}">All {{ $item->label() }}</a>
                            @foreach($subCategories as $subCategory)
                                @php
                                    $subCategoryBrands = collect($subCategory->menuManufacturers ?? []);
                                    $isActiveSubCategory = $activeSubCategoryId && (int) $subCategory->sub_category_id === $activeSubCategoryId;
                                    $subCategoryLabel = trim((string) ($subCategory->navbar_name ?: $subCategory->sub_category_name));
                                @endphp
                                <div class="lt-subcategory-item {{ $subCategoryBrands->isNotEmpty() ? 'has-nested' : '' }} {{ $isActiveSubCategory ? 'is-active' : '' }}">
                                    <a class="lt-subcategory-link" href="{{ url('/product-by-sub-category/'.$subCategory->sub_category_id) }}" @if($isActiveSubCategory) aria-current="page" @endif>
                                        <span>{{ $subCategoryLabel }}</span>
                                        @if($subCategoryBrands->isNotEmpty())<i class="fa fa-angle-right" aria-hidden="true"></i>@endif
                                    </a>
                                    @if($subCategoryBrands->isNotEmpty())
                                        <div class="lt-subcategory-dropdown" aria-label="{{ $subCategoryLabel }} brands">
                                            <a class="lt-nested-title" href="{{ url('/product-by-sub-category/'.$subCategory->sub_category_id) }}">All {{ $subCategoryLabel }}</a>
                                            @foreach($subCategoryBrands as $manufacturer)
                                                <a href="{{ url('/all-manufacturer-by-id/'.$manufacturer->manufacturer_id).'?'.http_build_query(['category_id' => $categoryId, 'sub_category_id' => $subCategory->sub_category_id]) }}">{{ $manufacturer->manufacturer_name }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            <a class="lt-view-all" href="{{ url('/product-by-category/'.$categoryId) }}">Show all {{ $item->label() }}</a>
                        </div>
                    @endif
                </div>
            @endif
        @empty
            <div class="lt-menu-empty">Enable categories in Navbar Management to populate this menu.</div>
        @endforelse
    </div>
</nav>
