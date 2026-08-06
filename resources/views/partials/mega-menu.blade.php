<nav id="main-menu" class="lt-mega lt-startech-menu" aria-label="Primary categories">
    @php
        $menuCategories = collect($categoryTree ?? []);
        $visibleCategories = $menuCategories->take(20);
        $extraCategories = $menuCategories->slice(20);
    @endphp
    <div class="lt-container lt-category-nav">
        @forelse($visibleCategories as $category)
            @php
                $categoryLink = url('/product-by-category/'.$category->category_id);
                $subCategories = collect($category->subCategories ?? []);
                $visibleSubCategories = $subCategories->take(10);
            @endphp
            <div class="lt-category-item {{ $loop->first ? 'is-active' : '' }} {{ $subCategories->isNotEmpty() ? 'has-children' : '' }}">
                <a href="{{ $categoryLink }}">
                    <span>{{ $category->category_name }}</span>
                    @if($subCategories->isNotEmpty())
                        <i class="fa fa-angle-down" aria-hidden="true"></i>
                    @endif
                </a>
                @if($subCategories->isNotEmpty())
                    <div class="lt-category-dropdown" aria-label="{{ $category->category_name }} subcategories">
                        @foreach($visibleSubCategories as $subCategory)
                            <a href="{{ url('/product-by-sub-category/'.$subCategory->sub_category_id) }}">{{ $subCategory->sub_category_name }}</a>
                        @endforeach
                        @if($subCategories->count() > $visibleSubCategories->count())
                            <a class="lt-view-all" href="{{ $categoryLink }}">Show all {{ $category->category_name }}</a>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="lt-menu-empty">Publish categories in the admin panel to populate this menu.</div>
        @endforelse

        @if($extraCategories->isNotEmpty())
            <div class="lt-category-item lt-more-menu">
                <button class="lt-more-toggle" type="button" aria-expanded="false" aria-controls="lt-more-dropdown">
                    <span>More</span>
                    <i class="fa fa-angle-down" aria-hidden="true"></i>
                </button>
                <div class="lt-category-dropdown lt-category-dropdown--more" id="lt-more-dropdown" aria-label="More categories">
                    @foreach($extraCategories as $category)
                        <a href="{{ url('/product-by-category/'.$category->category_id) }}">{{ $category->category_name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</nav>
