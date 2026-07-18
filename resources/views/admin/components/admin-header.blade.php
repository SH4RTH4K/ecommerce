<div class="navbar">
    <div class="navbar-inner">
        <div class="container-fluid">
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".top-nav.nav-collapse,.sidebar-nav.nav-collapse" aria-label="Toggle admin navigation">
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </a>
            <a class="brand" href="{{ route('admin.dashboard') }}"><span>{{ $brandName }} Admin</span></a>
            <div class="nav-no-collapse header-nav">
                <ul class="nav pull-right">
                    @if(!empty($developmentModeActive))<li><a class="btn btn-warning" href="{{ url('/site-customization#development-mode') }}" title="Public storefront is hidden"><i class="halflings-icon white warning-sign"></i> <strong>Development Mode Active</strong></a></li>@endif
                    <li class="hidden-phone"><a class="btn" href="{{ url('/admin-notifications') }}" title="Unread notifications" aria-label="Unread notifications: {{ $adminHeaderCounts['notifications'] }}"><i class="halflings-icon white bell"></i>@if($adminHeaderCounts['notifications'])<span class="badge badge-important">{{ $adminHeaderCounts['notifications'] }}</span>@endif</a></li>
                    <li class="hidden-phone">
                        <a class="btn" href="{{ url('/inventory?filter=low') }}" title="Inventory alerts" aria-label="Inventory alerts: {{ $adminHeaderCounts['inventory'] }}">
                            <i class="halflings-icon white warning-sign"></i>@if($adminHeaderCounts['inventory'])<span class="badge badge-important">{{ $adminHeaderCounts['inventory'] }}</span>@endif
                        </a>
                    </li>
                    <li class="hidden-phone">
                        <a class="btn" href="{{ url('/manage-orders?status=pending') }}" title="Orders requiring action" aria-label="Orders requiring action: {{ $adminHeaderCounts['orders'] }}">
                            <i class="halflings-icon white tasks"></i>@if($adminHeaderCounts['orders'])<span class="badge badge-warning">{{ $adminHeaderCounts['orders'] }}</span>@endif
                        </a>
                    </li>
                    <li class="hidden-phone">
                        <a class="btn" href="{{ url('/customer-inbox') }}" title="Customer inbox" aria-label="Customer inbox: {{ $adminHeaderCounts['messages'] }}">
                            <i class="halflings-icon white envelope"></i>@if($adminHeaderCounts['messages'])<span class="badge badge-info">{{ $adminHeaderCounts['messages'] }}</span>@endif
                        </a>
                    </li>
                    <li class="hidden-phone"><a class="btn" href="{{ url('/site-customization') }}" title="Website settings" aria-label="Website settings"><i class="halflings-icon white wrench"></i></a></li>
                    <li class="dropdown">
                        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#" aria-label="Administrator menu"><i class="halflings-icon white user"></i> {{ session('admin_display_name', session('admin_name')) }} <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-menu-title"><span>Administrator</span></li>
                            <li><a href="{{ route('admin.dashboard') }}"><i class="halflings-icon home"></i> Dashboard</a></li>
                            <li><form method="post" action="{{ route('admin.logout') }}" style="margin:0">{{ csrf_field() }}<button type="submit" style="border:0;background:transparent;width:100%;text-align:left;padding:8px 15px"><i class="halflings-icon off"></i> Logout</button></form></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
