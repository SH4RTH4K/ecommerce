@extends('admin.admin-master')
@section('admin_main_content')
@php
    $permissionLabels = [
        'dashboard' => 'Dashboard',
        'catalog' => 'Products and catalog',
        'inventory' => 'Inventory',
        'orders' => 'Orders and delivery',
        'customers' => 'Customer service',
        'marketing' => 'Marketing tools',
        'reports' => 'Sales reports',
        'settings' => 'Website and payment settings',
        'staff' => 'Administrators and audit logs',
        'view_product_code_configuration' => 'View product code configuration',
        'change_product_code_configuration' => 'Change product code configuration',
        'view_product_code_sequence' => 'View product code sequences',
        'change_product_code_sequence' => 'Change product code sequences',
        'reset_product_code_sequence' => 'Reset product code sequences',
        'override_product_code' => 'Override product code',
        'regenerate_product_code' => 'Regenerate product code',
        'view_product_code_history' => 'View product code history',
        'view_recycle_bin' => 'View recycle bin',
        'restore_deleted_items' => 'Restore deleted items',
        'permanently_delete_items' => 'Permanently delete items',
        'empty_recycle_bin' => 'Empty recycle bin',
        'view_orphan_media' => 'View orphan media',
        'cleanup_orphan_media' => 'Cleanup orphan media',
        'view_storefront_navbar' => 'View storefront navbar',
        'change_storefront_navbar' => 'Change storefront navbar',
    ];
    $permissionTotal = count($permissionLabels);
@endphp
<div id="content" class="span10">
    <ul class="breadcrumb">
        <li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li>
        <li>Administrators &amp; Roles</li>
    </ul>

    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    <div class="row-fluid">
        <div class="box span6">
            <div class="box-header"><h2><i class="icon-user"></i> Add administrator</h2></div>
            <div class="box-content">
                <form method="post" action="{{ url('/admin-users') }}">{{ csrf_field() }}
                    <label>Username</label><input class="span12" name="admin_name" value="{{ old('admin_name') }}" maxlength="30" required>
                    <label>Full name</label><input class="span12" name="full_name" value="{{ old('full_name') }}" maxlength="120">
                    <label>Email</label><input class="span12" type="email" name="admin_email" value="{{ old('admin_email') }}" maxlength="150">
                    <label>Role</label><select class="span12" name="role_id" required>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select>
                    <label>Temporary password</label><input class="span12" type="password" name="password" minlength="8" autocomplete="new-password" required>
                    <button class="btn btn-primary" style="margin-top:12px">Create administrator</button>
                </form>
            </div>
        </div>
        <div class="box span6">
            <div class="box-header"><h2><i class="icon-lock"></i> Create role</h2></div>
            <div class="box-content">
                <form method="post" action="{{ url('/admin-roles') }}">{{ csrf_field() }}
                    <label>Role name</label><input class="span12" name="name" placeholder="Example: Catalog Editor" maxlength="100" required>
                    <p><strong>Allowed permissions ({{ $permissionTotal }} total)</strong></p>
                    @foreach($permissionLabels as $key => $label)
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" {{ $key === 'dashboard' ? 'checked disabled' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                    <input type="hidden" name="permissions[]" value="dashboard">
                    <button class="btn btn-primary" style="margin-top:12px">Create role</button>
                </form>
            </div>
        </div>
    </div>

    <div class="box">
        <div class="box-header"><h2><i class="icon-key"></i> Roles and permissions</h2></div>
        <div class="box-content">
            <div class="accordion" id="role-editor">
                @foreach($roles as $role)
                    @php($rolePermissions = (array) json_decode($role->permissions, true))
                    @php($isEditableSystemRole = $role->is_system && $role->name !== 'Super Admin')
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" data-toggle="collapse" data-parent="#role-editor" href="#role-{{ $role->id }}">
                                <strong>{{ $role->name }}</strong>
                                <span class="muted">- {{ count($rolePermissions) }} / {{ $permissionTotal }} permissions</span>
                            </a>
                        </div>
                        <div id="role-{{ $role->id }}" class="accordion-body collapse">
                            <div class="accordion-inner">
                                <form method="post" action="{{ url('/admin-roles/'.$role->id.'/update') }}">{{ csrf_field() }}
                                    <label>Role name</label><input class="span5" name="name" value="{{ $role->name }}" maxlength="100" {{ $isEditableSystemRole ? 'readonly' : '' }} required>
                                    <div class="row-fluid">
                                        @foreach($permissionLabels as $key => $label)
                                            <label class="checkbox span4" style="margin-left:0">
                                                <input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, $rolePermissions, true) ? 'checked' : '' }} {{ $isEditableSystemRole ? 'disabled' : '' }}>
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <input type="hidden" name="permissions[]" value="dashboard">
                                    <button class="btn btn-primary">Save role</button>
                                </form>
                                @if(! $role->is_system)
                                    <form method="post" action="{{ url('/admin-roles/'.$role->id.'/delete') }}" style="display:inline-block;margin-top:8px">{{ csrf_field() }}<button class="btn btn-mini btn-danger" onclick="return confirm('Delete this role?')">Delete unused role</button></form>
                                @else
                                    <span class="label" style="margin-left:8px">System role{{ $role->name === 'Super Admin' ? ' - permissions editable' : '' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="box">
        <div class="box-header"><h2><i class="icon-group"></i> Administrator accounts</h2></div>
        <div class="box-content">
            <div class="accordion" id="account-editor">
                @forelse($admins as $admin)
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" data-toggle="collapse" data-parent="#account-editor" href="#admin-{{ $admin->admin_id }}">
                                <strong>{{ $admin->full_name ?: $admin->admin_name }}</strong>
                                <span class="muted">({{ $admin->admin_name }}) - {{ $admin->role_name ?: 'No role' }}</span>
                                <span class="label {{ $admin->is_active ? 'label-success' : 'label-important' }} pull-right">{{ $admin->is_active ? 'Active' : 'Disabled' }}</span>
                            </a>
                        </div>
                        <div id="admin-{{ $admin->admin_id }}" class="accordion-body collapse">
                            <div class="accordion-inner">
                                <div class="row-fluid">
                                    <div class="span7">
                                        <h4>Account information</h4>
                                        <form method="post" action="{{ url('/admin-users/'.$admin->admin_id.'/update') }}">{{ csrf_field() }}
                                            <label>Username</label><input class="span12" name="admin_name" value="{{ $admin->admin_name }}" maxlength="30" required>
                                            <label>Full name</label><input class="span12" name="full_name" value="{{ $admin->full_name }}" maxlength="120">
                                            <label>Email</label><input class="span12" type="email" name="admin_email" value="{{ $admin->admin_email }}" maxlength="150">
                                            <label>Role</label><select class="span12" name="role_id" {{ (int) $admin->admin_id === (int) session('admin_id') ? 'disabled' : '' }}>@foreach($roles as $role)<option value="{{ $role->id }}" {{ (int) $admin->role_id === (int) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>@endforeach</select>
                                            @if((int) $admin->admin_id === (int) session('admin_id'))<input type="hidden" name="role_id" value="{{ $admin->role_id }}"><p class="muted">Your own role cannot be changed while signed in.</p>@endif
                                            <button class="btn btn-primary">Save account</button>
                                        </form>
                                    </div>
                                    <div class="span5">
                                        <h4>Reset password</h4>
                                        <form method="post" action="{{ url('/admin-users/'.$admin->admin_id.'/password') }}">{{ csrf_field() }}
                                            <label>New password</label><input class="span12" type="password" name="password" minlength="8" autocomplete="new-password" required>
                                            <label>Confirm new password</label><input class="span12" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                                            <button class="btn"><i class="icon-key"></i> Reset password</button>
                                        </form>
                                        <hr>
                                        @if((int) $admin->admin_id !== (int) session('admin_id'))
                                            <form method="post" action="{{ url('/admin-users/'.$admin->admin_id.'/toggle') }}">{{ csrf_field() }}<button class="btn {{ $admin->is_active ? 'btn-danger' : 'btn-success' }}" onclick="return confirm('{{ $admin->is_active ? 'Disable' : 'Enable' }} this administrator?')">{{ $admin->is_active ? 'Disable account' : 'Enable account' }}</button></form>
                                        @else
                                            <span class="label label-info">Current account</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="muted">No administrator accounts found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
