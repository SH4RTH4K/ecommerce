@extends('admin.admin-master')
@section('admin_main_content')
@php
    $canBulk = $selectedType !== 'all';
@endphp
<div id="content" class="span10">
    <ul class="breadcrumb">
        <li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li>
        <li>Recycle Bin</li>
    </ul>

    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    <div class="box">
        <div class="box-header">
            <h2><i class="icon-trash"></i> Recycle Bin</h2>
        </div>
        <div class="box-content">
            <div class="btn-group" style="margin-bottom:16px">
                @foreach($types as $typeKey => $typeLabel)
                    <a class="btn {{ $selectedType === $typeKey ? 'btn-primary' : '' }}" href="{{ route('recycle-bin.index', ['type' => $typeKey]) }}">{{ $typeLabel }}</a>
                @endforeach
            </div>

            <div class="alert alert-info">
                Items in the Recycle Bin can be restored. Permanent deletion removes the record and any unreferenced managed files.
            </div>

            <form method="post" action="{{ route('recycle-bin.empty') }}" style="margin-bottom:16px">
                @csrf
                <label for="recycle-bin-empty-confirm"><strong>Empty Recycle Bin</strong></label>
                <div class="input-append">
                    <input id="recycle-bin-empty-confirm" type="text" name="confirm_text" placeholder="DELETE" style="width:160px">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Permanently delete all items currently in the Recycle Bin? This cannot be undone.')">Empty Recycle Bin</button>
                </div>
            </form>

            @if($canBulk)
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap">
                    <form method="post" action="{{ route('recycle-bin.bulk-restore', ['type' => $selectedType]) }}" class="recycle-bin-bulk-form" data-bulk-form="restore">
                        @csrf
                        <div class="recycle-bin-bulk-target"></div>
                        <button type="submit" class="btn btn-success">Restore Selected</button>
                    </form>
                    <form method="post" action="{{ route('recycle-bin.bulk-delete', ['type' => $selectedType]) }}" class="recycle-bin-bulk-form" data-bulk-form="delete">
                        @csrf
                        <input type="text" name="confirm_text" placeholder="DELETE" style="width:120px" aria-label="Confirm permanent delete">
                        <div class="recycle-bin-bulk-target"></div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete the selected items permanently? This cannot be undone.')">Delete Permanently Selected</button>
                    </form>
                    <span class="muted">Use this list after filtering by a single entity type.</span>
                </div>
            @else
                <div class="alert alert-warning">Filter by a single entity type to use bulk actions.</div>
            @endif

            <table class="table table-striped table-bordered bootstrap-datatable datatable">
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>Type</th>
                            <th>Name / Reference</th>
                            <th>Deleted At</th>
                            <th>Deleted By</th>
                            <th>Reason</th>
                            <th>Files</th>
                            <th style="width:180px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td><input type="checkbox" class="recycle-bin-item" value="{{ $item['id'] }}" data-entity-type="{{ $item['entity_type'] }}" {{ $canBulk ? '' : 'disabled' }}></td>
                                <td>{{ $item['entity_label'] }}</td>
                                <td>
                                    <strong>{{ $item['name'] }}</strong><br>
                                    <span class="muted">{{ $item['reference'] }}</span>
                                </td>
                                <td>{{ $item['deleted_at'] ?? '-' }}</td>
                                <td>{{ $item['deleted_by_name'] ?? ($item['deleted_by'] ?? '-') }}</td>
                                <td>{{ $item['delete_reason'] ?: '-' }}</td>
                                <td>
                                    @if(! empty($item['media_paths']))
                                        {{ count($item['media_paths']) }} file(s)
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('recycle-bin.restore', [$item['entity_type'], $item['id']]) }}" style="display:inline">
                                        @csrf
                                        <button class="btn btn-mini btn-success" type="submit">Restore</button>
                                    </form>
                                    <form method="post" action="{{ route('recycle-bin.destroy', [$item['entity_type'], $item['id']]) }}" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="confirm_text" value="DELETE">
                                        <button class="btn btn-mini btn-danger" type="submit" onclick="return confirm('Delete permanently? This cannot be undone.')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="center muted">No deleted items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('.recycle-bin-bulk-form');
    if (!forms.length) {
        return;
    }

    function selectedIds() {
        return Array.prototype.slice.call(document.querySelectorAll('.recycle-bin-item:checked')).map(function (input) {
            return input.value;
        });
    }

    function prepareForm(form) {
        form.addEventListener('submit', function (event) {
            var ids = selectedIds();
            if (!ids.length) {
                event.preventDefault();
                alert('Select at least one item first.');
                return;
            }

            var target = form.querySelector('.recycle-bin-bulk-target');
            if (!target) {
                return;
            }

            target.innerHTML = '';
            ids.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                target.appendChild(input);
            });
        });
    }

    Array.prototype.forEach.call(forms, prepareForm);
});
</script>
@endsection
