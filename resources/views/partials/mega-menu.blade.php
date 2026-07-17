<nav id="main-menu" class="lt-mega" aria-label="Product categories">
    <div class="lt-container lt-mega-inner">
        <div class="lt-all-wrap">
            <button class="lt-all-categories" type="button" aria-expanded="false"><i class="fa fa-th-large"></i> All Categories <i class="fa fa-angle-down"></i></button>
            <div class="lt-all-dropdown">
                @php
                    /* Temporary display-only grouping: the category table has no group or sort-order field. */
                    $groupRules = [
                        'Computers & Components' => ['desktop pc','laptop','processor','motherboard','ram','graphics card','power supply','casing','cpu cooler','dvd writer','sound card'],
                        'Storage Devices' => ['hdd','ssd','portable hard disk','hdd enclosure','pendrive','memory card'],
                        'Networking & Security' => ['router','pocket router','switch','wifi receiver','lan cable','ip camera'],
                        'Accessories & Peripherals' => ['monitor','printer','ups','ups battery','keyboard','mouse','webcam','game pad','laptop cooler','multiplug','cable','connector'],
                        'Audio & Smart Devices' => ['audio','bluetooth','headphone','microphone','speaker','smart watch'],
                    ];
                    $groupIcons = [
                        'Computers & Components' => 'fa-desktop',
                        'Storage Devices' => 'fa-hdd-o',
                        'Networking & Security' => 'fa-wifi',
                        'Accessories & Peripherals' => 'fa-keyboard-o',
                        'Audio & Smart Devices' => 'fa-headphones',
                        'Other Categories' => 'fa-th-large',
                    ];
                    $displayNames = [
                        'cpu cooler' => 'CPU Cooler', 'desktop pc' => 'Desktop PC', 'dvd writer' => 'DVD Writer',
                        'hdd' => 'HDD', 'hdd enclosure' => 'HDD Enclosure', 'ip camera' => 'IP Camera',
                        'lan cable' => 'LAN Cable', 'memory card' => 'Memory Card', 'pc' => 'PC',
                        'pocket router' => 'Pocket Router', 'portable hard disk' => 'Portable Hard Disk',
                        'ram' => 'RAM', 'sound card' => 'Sound Card', 'ssd' => 'SSD', 'ups' => 'UPS',
                        'ups battery' => 'UPS Battery', 'usb' => 'USB', 'webcam' => 'Webcam',
                        'wifi receiver' => 'Wi-Fi Receiver', 'bluetooth' => 'Bluetooth',
                    ];
                    $menuGroups = [];
                    $assignedCategoryIds = [];
                    foreach ($groupRules as $groupName => $names) {
                        $items = $categoryTree->filter(function ($category) use ($names) {
                            return in_array(strtolower(trim($category->category_name)), $names, true);
                        })->sortBy(function ($category) use ($names) {
                            return array_search(strtolower(trim($category->category_name)), $names, true);
                        });
                        if ($items->count()) {
                            $menuGroups[$groupName] = $items;
                            foreach ($items as $item) $assignedCategoryIds[] = $item->category_id;
                        }
                    }
                    $otherItems = $categoryTree->whereNotIn('category_id', $assignedCategoryIds)->sortBy('category_name');
                    if ($otherItems->count()) $menuGroups['Other Categories'] = $otherItems;
                @endphp
                @forelse($menuGroups as $groupName => $categories)
                    <section class="lt-category-group">
                        <h3><i class="fa {{ $groupIcons[$groupName] }}"></i><span>{{ $groupName }}</span><b title="Published products">{{ $categories->sum('published_products_count') }}</b></h3>
                        @foreach($categories as $category)
                            <div class="lt-group-item">
                                @php $normalizedName = strtolower(trim($category->category_name)); @endphp
                                <a class="lt-group-title" href="{{ url('/product-by-category/'.$category->category_id) }}"><span>{{ isset($displayNames[$normalizedName]) ? $displayNames[$normalizedName] : ucwords($normalizedName) }}</span><span class="lt-category-count">{{ $category->published_products_count }}</span><i class="fa fa-angle-right"></i></a>
                                @if($category->subCategories->count())
                                    <div class="lt-group-children">
                                        @foreach($category->subCategories as $subCategory)
                                            <a href="{{ url('/product-by-sub-category/'.$subCategory->sub_category_id) }}">{{ $subCategory->sub_category_name }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @empty
                    <span class="lt-menu-empty">Categories will appear here when published.</span>
                @endforelse
            </div>
        </div>
        <div class="lt-site-links">
            <a href="{{ url('/') }}"><i class="fa fa-home"></i> Home</a>
            <a href="{{ url('/#products') }}">Featured Products</a>
            <a href="{{ url('/#new-arrivals') }}">New Arrivals</a>
            <a href="{{ url('/about-us') }}">About Us</a>
            <a href="{{ url('/contact-us') }}">Contact Us</a>
        </div>
        <a class="lt-nav-cta" href="{{ url('/contact-us') }}"><i class="fa fa-desktop"></i> Build Your PC</a>
    </div>
</nav>
