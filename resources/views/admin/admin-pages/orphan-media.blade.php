@extends('admin.admin-master')
@section('admin_main_content')
<div id="content" class="span10">
    <ul class="breadcrumb">
        <li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li>
        <li>Orphan Media</li>
    </ul>

    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    @php
        $summary = $report['summary'] ?? [];
        $rows = $report['rows'] ?? [];
    @endphp

    <div class="box">
        <div class="box-header"><h2><i class="icon-hdd"></i> Orphan Media Scanner</h2></div>
        <div class="box-content">
            <div class="alert alert-info">
                This page runs in dry-run mode by default. Files marked as protected, referenced, or too new are not deleted.
            </div>

            <form method="get" action="{{ route('orphan-media.index') }}" class="form-inline" style="margin-bottom:16px">
                <label style="margin-right:8px">Entity</label>
                <select name="entity" class="span2" style="margin-right:12px">
                    @foreach($entityOptions as $key => $label)
                        <option value="{{ $key }}" {{ $selectedEntity === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <label style="margin-right:8px">Older than (hours)</label>
                <input type="number" min="0" max="8760" name="older_than_hours" value="{{ $olderThanHours }}" class="span2" style="margin-right:12px">
                <button type="submit" class="btn btn-primary">Run dry run</button>
            </form>

            <div class="row-fluid" style="margin-bottom:16px">
                <div class="span2"><div class="well well-small"><strong>{{ $summary['scanned'] ?? 0 }}</strong><br>Scanned</div></div>
                <div class="span2"><div class="well well-small"><strong>{{ $summary['referenced'] ?? 0 }}</strong><br>Referenced</div></div>
                <div class="span2"><div class="well well-small"><strong>{{ $summary['orphan'] ?? 0 }}</strong><br>Potential orphans</div></div>
                <div class="span2"><div class="well well-small"><strong>{{ $summary['protected'] ?? 0 }}</strong><br>Protected</div></div>
                <div class="span2"><div class="well well-small"><strong>{{ $summary['unknown'] ?? 0 }}</strong><br>Unknown</div></div>
                <div class="span2"><div class="well well-small"><strong>{{ $summary['deleted'] ?? 0 }}</strong><br>Deleted</div></div>
            </div>

            <form method="post" action="{{ route('orphan-media.cleanup') }}" style="margin-bottom:16px">
                @csrf
                <input type="hidden" name="entity" value="{{ $selectedEntity }}">
                <input type="hidden" name="older_than_hours" value="{{ $olderThanHours }}">
                <label for="orphan-confirm"><strong>Permanent cleanup</strong></label>
                <div class="input-append">
                    <input id="orphan-confirm" type="text" name="confirm_text" placeholder="DELETE" style="width:160px">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete confirmed orphan media files? This cannot be undone.')">Delete confirmed orphan media</button>
                </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable datatable">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Entity</th>
                        <th>Path</th>
                        <th>Last Modified</th>
                        <th>Size</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['status'] }}</td>
                            <td>{{ $row['entity_type'] }}</td>
                            <td><code>{{ $row['path'] }}</code></td>
                            <td>{{ $row['last_modified'] }}</td>
                            <td>{{ $row['size_human'] }}</td>
                            <td>{{ $row['reason'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="center muted">No managed files found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
