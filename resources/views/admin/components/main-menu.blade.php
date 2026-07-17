@php
    $active = function ($patterns) {
        foreach ((array) $patterns as $pattern) if (request()->is($pattern)) return 'is-active';
        return '';
    };
@endphp
<aside id="sidebar-left" class="span2" aria-label="Administration navigation">
    <div class="sidebar-nav nav-collapse">
        <div class="admin-menu-intro">
            <span class="admin-menu-mark"><i class="icon-dashboard"></i></span>
            <div><strong>Control Center</strong><small>Store administration</small></div>
        </div>
        <nav class="admin-menu">
            <a class="admin-menu-link {{ $active('dashboard') }}" href="{{ route('admin.dashboard') }}"><i class="icon-dashboard"></i><span>Dashboard</span></a>

            <section class="admin-menu-group">
                <h2>Catalog</h2>
                <a class="admin-menu-link {{ $active(['add-product','manage-product','edit-product/*']) }}" href="{{ url('/manage-product') }}"><i class="icon-shopping-cart"></i><span>Products</span></a>
                <a class="admin-menu-link admin-menu-sub {{ $active('add-product') }}" href="{{ url('/add-product') }}"><i class="icon-plus"></i><span>Add Product</span></a>
                <a class="admin-menu-link {{ $active(['manage-category','add-category','edit-category/*']) }}" href="{{ url('/manage-category') }}"><i class="icon-folder-open"></i><span>Categories</span></a>
                <a class="admin-menu-link admin-menu-sub {{ $active('add-category') }}" href="{{ url('/add-category') }}"><i class="icon-plus"></i><span>Add Category</span></a>
                <a class="admin-menu-link {{ $active(['manage-subCategory','add-subCategory','edit-subCategory/*']) }}" href="{{ url('/manage-subCategory') }}"><i class="icon-sitemap"></i><span>Subcategories</span></a>
                <a class="admin-menu-link admin-menu-sub {{ $active('add-subCategory') }}" href="{{ url('/add-subCategory') }}"><i class="icon-plus"></i><span>Add Subcategory</span></a>
                <a class="admin-menu-link {{ $active(['manage-manufacturer','add-manufacturer','edit-manufacturer/*']) }}" href="{{ url('/manage-manufacturer') }}"><i class="icon-certificate"></i><span>Brands &amp; Manufacturers</span></a>
                <a class="admin-menu-link admin-menu-sub {{ $active('add-manufacturer') }}" href="{{ url('/add-manufacturer') }}"><i class="icon-plus"></i><span>Add Manufacturer</span></a>
                <a class="admin-menu-link {{ $active('catalog-attributes*') }}" href="{{ url('/catalog-attributes') }}"><i class="icon-list-alt"></i><span>Product Attributes</span></a>
                <a class="admin-menu-link {{ $active('inventory*') }}" href="{{ url('/inventory') }}"><i class="icon-tasks"></i><span>Inventory</span></a>
                <a class="admin-menu-link {{ $active(['purchasing*','purchase-orders*']) }}" href="{{ url('/purchasing') }}"><i class="icon-truck"></i><span>Suppliers &amp; Purchasing</span></a>
                <a class="admin-menu-link {{ $active(['stock-locations*','stock-transfers*']) }}" href="{{ url('/stock-locations') }}"><i class="icon-map-marker"></i><span>Locations &amp; Transfers</span></a>
            </section>

            <section class="admin-menu-group">
                <h2>Sales</h2>
                <a class="admin-menu-link {{ $active('manage-orders*') }}" href="{{ url('/manage-orders') }}"><i class="icon-file-alt"></i><span>Orders</span>@if($adminHeaderCounts['orders'])<b>{{ $adminHeaderCounts['orders'] }}</b>@endif</a>
                <a class="admin-menu-link {{ $active('returns*') }}" href="{{ url('/returns') }}"><i class="icon-undo"></i><span>Returns &amp; Refunds</span></a>
                <a class="admin-menu-link {{ $active('sales-reports*') }}" href="{{ url('/sales-reports') }}"><i class="icon-bar-chart"></i><span>Sales &amp; Profit Reports</span></a>
                <a class="admin-menu-link {{ $active('payment-methods*') }}" href="{{ url('/payment-methods') }}"><i class="icon-credit-card"></i><span>Payments &amp; EMI</span></a>
                <a class="admin-menu-link {{ $active('delivery-zones*') }}" href="{{ url('/delivery-zones') }}"><i class="icon-truck"></i><span>Delivery Zones</span></a>
            </section>

            <section class="admin-menu-group">
                <h2>Customers</h2>
                <a class="admin-menu-link {{ $active('customer-inbox*') }}" href="{{ url('/customer-inbox') }}"><i class="icon-comments"></i><span>Customer Inbox</span>@if($adminHeaderCounts['messages'])<b>{{ $adminHeaderCounts['messages'] }}</b>@endif</a>
                <a class="admin-menu-link {{ $active('service-claims*') }}" href="{{ url('/service-claims') }}"><i class="icon-legal"></i><span>Warranty &amp; RMA</span></a>
                <a class="admin-menu-link {{ $active('stock-alerts*') }}" href="{{ url('/stock-alerts') }}"><i class="icon-bullhorn"></i><span>Stock Alerts</span></a>
            </section>

            <section class="admin-menu-group">
                <h2>Marketing</h2>
                <a class="admin-menu-link {{ $active('coupons*') }}" href="{{ url('/coupons') }}"><i class="icon-tags"></i><span>Coupons &amp; Offers</span></a>
                <a class="admin-menu-link {{ $active('abandoned-carts*') }}" href="{{ url('/abandoned-carts') }}"><i class="icon-repeat"></i><span>Cart Recovery</span></a>
                <a class="admin-menu-link {{ $active('marketing-campaigns*') }}" href="{{ url('/marketing-campaigns') }}"><i class="icon-envelope-alt"></i><span>Customer Campaigns</span></a>
            </section>

            <section class="admin-menu-group">
                <h2>System</h2>
                <a class="admin-menu-link {{ $active('admin-notifications*') }}" href="{{ url('/admin-notifications') }}"><i class="icon-bell"></i><span>Notifications</span>@if($adminHeaderCounts['notifications'])<b>{{ $adminHeaderCounts['notifications'] }}</b>@endif</a>
                <a class="admin-menu-link {{ $active('site-customization*') }}" href="{{ url('/site-customization') }}"><i class="icon-cogs"></i><span>Website Settings</span></a>
                @if(in_array('staff',(array)session('admin_permissions',[])))<a class="admin-menu-link {{ $active('admin-users*') }}" href="{{ url('/admin-users') }}"><i class="icon-group"></i><span>Administrators &amp; Roles</span></a><a class="admin-menu-link {{ $active('admin-activity*') }}" href="{{ url('/admin-activity') }}"><i class="icon-list-alt"></i><span>Activity Audit Log</span></a>@endif
                @if(in_array('settings',(array)session('admin_permissions',[])))<a class="admin-menu-link {{ $active('system-health*') }}" href="{{ url('/system-health') }}"><i class="icon-stethoscope"></i><span>System Health &amp; Backups</span></a>@endif
                @if(in_array('settings',(array)session('admin_permissions',[])))<a class="admin-menu-link {{ $active('system-monitor*') }}" href="{{ url('/system-monitor') }}"><i class="icon-warning-sign"></i><span>Errors &amp; Security</span></a>@endif
                @if(in_array('settings',(array)session('admin_permissions',[])))<a class="admin-menu-link {{ $active('integrations*') }}" href="{{ url('/integrations') }}"><i class="icon-exchange"></i><span>API &amp; Integrations</span></a>@endif
            </section>
        </nav>
    </div>
</aside>
