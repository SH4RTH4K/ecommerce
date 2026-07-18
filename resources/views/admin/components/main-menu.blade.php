@php
    $active = function ($patterns) {
        foreach ((array)$patterns as $pattern) if (request()->is($pattern)) return 'is-active';
        return '';
    };
    $groupOpen = function ($patterns) {
        foreach ((array)$patterns as $pattern) if (request()->is($pattern)) return 'is-open';
        return '';
    };
@endphp
<aside id="sidebar-left" class="span2" aria-label="Administration navigation">
    <div class="sidebar-nav nav-collapse">
        <div class="admin-menu-intro">
            <span class="admin-menu-mark"><i class="icon-dashboard"></i></span>
            <div class="admin-menu-brand"><strong>Control Center</strong><small>Store administration</small></div>
            <button type="button" class="admin-menu-collapse" data-menu-collapse aria-label="Collapse navigation" title="Collapse navigation"><i class="icon-chevron-left"></i></button>
        </div>
        <div class="admin-menu-search"><i class="icon-search"></i><input type="search" data-menu-search placeholder="Find an admin tool…" aria-label="Find an admin tool"><button type="button" data-menu-search-clear aria-label="Clear navigation search">×</button></div>
        <nav class="admin-menu">
            <a class="admin-menu-link admin-menu-dashboard {{ $active('dashboard') }}" href="{{ route('admin.dashboard') }}" title="Dashboard"><i class="icon-dashboard"></i><span>Dashboard</span></a>

            <section class="admin-menu-group {{ $groupOpen(['add-product','manage-product','edit-product/*','manage-category','add-category','edit-category/*','manage-subCategory','add-subCategory','edit-subCategory/*','manage-manufacturer','add-manufacturer','edit-manufacturer/*','catalog-hierarchy*','catalog-attributes*']) }}" data-menu-group="catalog">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-shopping-cart"></i><span>Catalog</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items">
                    <a class="admin-menu-link {{ $active(['manage-category','add-category','edit-category/*']) }}" href="{{ url('/manage-category') }}"><i class="icon-folder-open"></i><span>Step 1 · Categories</span></a>
                    <a class="admin-menu-link admin-menu-sub {{ $active('add-category') }}" href="{{ url('/add-category') }}"><i class="icon-plus"></i><span>Add Category</span></a>
                    <a class="admin-menu-link {{ $active(['manage-subCategory','add-subCategory','edit-subCategory/*']) }}" href="{{ url('/manage-subCategory') }}"><i class="icon-sitemap"></i><span>Step 2 · Subcategories</span></a>
                    <a class="admin-menu-link admin-menu-sub {{ $active('add-subCategory') }}" href="{{ url('/add-subCategory') }}"><i class="icon-plus"></i><span>Add Subcategory</span></a>
                    <a class="admin-menu-link {{ $active(['catalog-hierarchy*','manage-manufacturer','add-manufacturer','edit-manufacturer/*']) }}" href="{{ url('/catalog-hierarchy') }}"><i class="icon-certificate"></i><span>Step 3 · Companies, Brands &amp; Series</span></a>
                    <a class="admin-menu-link {{ $active('catalog-attributes*') }}" href="{{ url('/catalog-attributes') }}"><i class="icon-list-alt"></i><span>Step 4 · Product Attributes</span></a>
                    <a class="admin-menu-link {{ $active(['add-product','manage-product','edit-product/*']) }}" href="{{ url('/manage-product') }}"><i class="icon-shopping-cart"></i><span>Step 5 · Products</span></a>
                    <a class="admin-menu-link admin-menu-sub {{ $active('add-product') }}" href="{{ url('/add-product') }}"><i class="icon-plus"></i><span>Add Product</span></a>
                </div>
            </section>

            <section class="admin-menu-group {{ $groupOpen(['manage-orders*','returns*','sales-reports*','payment-methods*','delivery-zones*']) }}" data-menu-group="sales">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-file-alt"></i><span>Sales</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items">
                    <a class="admin-menu-link {{ $active('manage-orders*') }}" href="{{ url('/manage-orders') }}"><i class="icon-file-alt"></i><span>Orders</span>@if($adminHeaderCounts['orders'])<b>{{ $adminHeaderCounts['orders'] }}</b>@endif</a>
                    <a class="admin-menu-link {{ $active('returns*') }}" href="{{ url('/returns') }}"><i class="icon-undo"></i><span>Returns &amp; Refunds</span></a>
                    <a class="admin-menu-link {{ $active('sales-reports*') }}" href="{{ url('/sales-reports') }}"><i class="icon-bar-chart"></i><span>Sales &amp; Profit Reports</span></a>
                    <a class="admin-menu-link {{ $active('payment-methods*') }}" href="{{ url('/payment-methods') }}"><i class="icon-credit-card"></i><span>Payments &amp; EMI</span></a>
                    <a class="admin-menu-link {{ $active('delivery-zones*') }}" href="{{ url('/delivery-zones') }}"><i class="icon-truck"></i><span>Delivery Zones</span></a>
                </div>
            </section>

            <section class="admin-menu-group {{ $groupOpen(['inventory*','purchasing*','purchase-orders*','suppliers*','stock-locations*','stock-transfers*','stock-alerts*']) }}" data-menu-group="inventory">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-tasks"></i><span>Inventory</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items">
                    <a class="admin-menu-link {{ $active('inventory*') }}" href="{{ url('/inventory') }}"><i class="icon-tasks"></i><span>Stock Overview</span></a>
                    <a class="admin-menu-link {{ $active(['purchasing*','purchase-orders*','suppliers*']) }}" href="{{ url('/purchasing') }}"><i class="icon-truck"></i><span>Suppliers &amp; Purchasing</span></a>
                    <a class="admin-menu-link {{ $active(['stock-locations*','stock-transfers*']) }}" href="{{ url('/stock-locations') }}"><i class="icon-map-marker"></i><span>Locations &amp; Transfers</span></a>
                    <a class="admin-menu-link {{ $active('stock-alerts*') }}" href="{{ url('/stock-alerts') }}"><i class="icon-bullhorn"></i><span>Stock Alerts</span></a>
                </div>
            </section>

            <section class="admin-menu-group {{ $groupOpen(['customer-inbox*','service-claims*','review/*','question/*']) }}" data-menu-group="customers">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-group"></i><span>Customers</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items">
                    <a class="admin-menu-link {{ $active('customer-inbox*') }}" href="{{ url('/customer-inbox') }}"><i class="icon-comments"></i><span>Customer Inbox</span>@if($adminHeaderCounts['messages'])<b>{{ $adminHeaderCounts['messages'] }}</b>@endif</a>
                    <a class="admin-menu-link {{ $active('service-claims*') }}" href="{{ url('/service-claims') }}"><i class="icon-legal"></i><span>Warranty &amp; RMA</span></a>
                </div>
            </section>

            <section class="admin-menu-group {{ $groupOpen(['coupons*','abandoned-carts*','marketing-campaigns*','banner-management*','top-bar-management*']) }}" data-menu-group="marketing">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-bullhorn"></i><span>Marketing</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items">
                    <a class="admin-menu-link {{ $active('coupons*') }}" href="{{ url('/coupons') }}"><i class="icon-tags"></i><span>Coupons &amp; Offers</span></a>
                    <a class="admin-menu-link {{ $active('abandoned-carts*') }}" href="{{ url('/abandoned-carts') }}"><i class="icon-repeat"></i><span>Cart Recovery</span></a>
                    <a class="admin-menu-link {{ $active('marketing-campaigns*') }}" href="{{ url('/marketing-campaigns') }}"><i class="icon-envelope-alt"></i><span>Customer Campaigns</span></a>
                    <a class="admin-menu-link {{ $active('banner-management*') }}" href="{{ url('/banner-management') }}"><i class="icon-picture"></i><span>Homepage Banners</span></a>
                    <a class="admin-menu-link {{ $active('top-bar-management*') }}" href="{{ url('/top-bar-management') }}"><i class="icon-bullhorn"></i><span>Top Bar &amp; Contacts</span></a>
                </div>
            </section>

            <section class="admin-menu-group {{ $groupOpen(['site-customization*']) }}" data-menu-group="website">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-globe"></i><span>Website</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items"><a class="admin-menu-link {{ $active('site-customization*') }}" href="{{ url('/site-customization') }}"><i class="icon-cogs"></i><span>Website Settings</span></a></div>
            </section>

            @if(in_array('staff',(array)session('admin_permissions',[])))
            <section class="admin-menu-group {{ $groupOpen(['admin-users*','admin-roles*','admin-activity*']) }}" data-menu-group="administration">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-user"></i><span>Administration</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items">
                    <a class="admin-menu-link {{ $active(['admin-users*','admin-roles*']) }}" href="{{ url('/admin-users') }}"><i class="icon-group"></i><span>Administrators &amp; Roles</span></a>
                    <a class="admin-menu-link {{ $active('admin-activity*') }}" href="{{ url('/admin-activity') }}"><i class="icon-list-alt"></i><span>Activity Audit Log</span></a>
                </div>
            </section>
            @endif

            <section class="admin-menu-group {{ $groupOpen(['admin-notifications*','system-health*','system-monitor*','integrations*']) }}" data-menu-group="system">
                <button type="button" class="admin-menu-group-toggle" data-menu-toggle aria-expanded="false"><i class="icon-cogs"></i><span>System</span><i class="icon-chevron-down admin-menu-chevron"></i></button>
                <div class="admin-menu-items">
                    <a class="admin-menu-link {{ $active('admin-notifications*') }}" href="{{ url('/admin-notifications') }}"><i class="icon-bell"></i><span>Notifications</span>@if($adminHeaderCounts['notifications'])<b>{{ $adminHeaderCounts['notifications'] }}</b>@endif</a>
                    @if(in_array('settings',(array)session('admin_permissions',[])))<a class="admin-menu-link {{ $active('system-health*') }}" href="{{ url('/system-health') }}"><i class="icon-stethoscope"></i><span>System Health &amp; Backups</span></a>@endif
                    @if(in_array('settings',(array)session('admin_permissions',[])))<a class="admin-menu-link {{ $active('system-monitor*') }}" href="{{ url('/system-monitor') }}"><i class="icon-warning-sign"></i><span>Errors &amp; Security</span></a>@endif
                    @if(in_array('settings',(array)session('admin_permissions',[])))<a class="admin-menu-link {{ $active('integrations*') }}" href="{{ url('/integrations') }}"><i class="icon-exchange"></i><span>API &amp; Integrations</span></a>@endif
                </div>
            </section>
            <p class="admin-menu-empty" data-menu-empty>No matching tools found.</p>
        </nav>
    </div>
</aside>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var sidebar=document.getElementById('sidebar-left');if(!sidebar)return;
    var groups=[].slice.call(sidebar.querySelectorAll('[data-menu-group]')),search=sidebar.querySelector('[data-menu-search]'),clear=sidebar.querySelector('[data-menu-search-clear]'),empty=sidebar.querySelector('[data-menu-empty]'),collapse=sidebar.querySelector('[data-menu-collapse]');
    function openGroup(group,remember){groups.forEach(function(item){var open=item===group;item.classList.toggle('is-open',open);item.querySelector('[data-menu-toggle]').setAttribute('aria-expanded',open?'true':'false')});if(remember&&group)try{localStorage.setItem('admin-menu-group',group.getAttribute('data-menu-group'))}catch(e){}}
    groups.forEach(function(group){group.querySelector('[data-menu-toggle]').addEventListener('click',function(){var wasOpen=group.classList.contains('is-open');if(wasOpen){group.classList.remove('is-open');this.setAttribute('aria-expanded','false')}else openGroup(group,true)})});
    var activeGroup=sidebar.querySelector('.admin-menu-group.is-open'),remembered='';try{remembered=localStorage.getItem('admin-menu-group')||''}catch(e){}if(activeGroup)openGroup(activeGroup,false);else if(remembered){var saved=sidebar.querySelector('[data-menu-group="'+remembered+'"]');if(saved)openGroup(saved,false)}
    function filterMenu(){var query=(search.value||'').trim().toLowerCase(),matches=0;groups.forEach(function(group){var links=[].slice.call(group.querySelectorAll('.admin-menu-link')),groupMatches=0;links.forEach(function(link){var match=!query||link.textContent.toLowerCase().indexOf(query)>-1;link.style.display=match?'grid':'none';if(match)groupMatches++});group.style.display=(!query||groupMatches)?'block':'none';if(query&&groupMatches){group.classList.add('is-open');group.querySelector('[data-menu-toggle]').setAttribute('aria-expanded','true');matches+=groupMatches}});if(!query){var current=sidebar.querySelector('.admin-menu-group .admin-menu-link.is-active'),restore=current?current.closest('[data-menu-group]'):null;if(!restore&&remembered)restore=sidebar.querySelector('[data-menu-group="'+remembered+'"]');if(restore)openGroup(restore,false)}empty.style.display=query&&!matches?'block':'none';clear.style.display=query?'block':'none'}
    search.addEventListener('input',filterMenu);clear.addEventListener('click',function(){search.value='';filterMenu();search.focus()});
    function setCollapsed(value){document.body.classList.toggle('admin-sidebar-collapsed',value);collapse.setAttribute('aria-label',value?'Expand navigation':'Collapse navigation');collapse.setAttribute('title',value?'Expand navigation':'Collapse navigation');var icon=collapse.querySelector('i');icon.className=value?'icon-chevron-right':'icon-chevron-left';try{localStorage.setItem('admin-sidebar-collapsed',value?'1':'0')}catch(e){}}
    var collapsed=false;try{collapsed=localStorage.getItem('admin-sidebar-collapsed')==='1'}catch(e){}setCollapsed(collapsed);collapse.addEventListener('click',function(){setCollapsed(!document.body.classList.contains('admin-sidebar-collapsed'))});
});
</script>
