@extends('admin.admin-master')
@section('admin_main_content')
@php
    $canBulk = $items->isNotEmpty();
@endphp
<style>
.rb-workspace{font-size:12px;color:#253946}.rb-hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:16px;align-items:center;margin-bottom:16px;padding:18px 20px;border:1px solid #d7e5eb;border-radius:14px;background:linear-gradient(135deg,#103e59,#187395);color:#fff;box-shadow:0 10px 24px rgba(18,62,89,.15)}.rb-hero h3{margin:0 0 6px;color:#fff;font-size:22px;line-height:1.2}.rb-hero p{margin:0;max-width:760px;color:rgba(255,255,255,.78);font-size:12px;line-height:1.55}.rb-hero-stat{min-width:132px;text-align:center;padding:12px 14px;border-radius:12px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18)}.rb-hero-stat strong{display:block;color:#fff;font-size:24px;line-height:1}.rb-hero-stat span{display:block;margin-top:4px;color:rgba(255,255,255,.72);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.rb-card{margin-bottom:15px;padding:15px;border:1px solid #dce8ee;border-radius:12px;background:#fff;box-shadow:0 5px 18px rgba(25,58,76,.05)}.rb-filter-bar{display:flex;gap:8px;flex-wrap:wrap}.rb-filter-chip{display:inline-flex;align-items:center;gap:7px;border:1px solid #d7e2e8!important;border-radius:999px!important;background:#f7fafb!important;color:#344f60!important;font-weight:800;padding:8px 11px!important;text-shadow:none!important}.rb-filter-chip:hover{border-color:#1988ad!important;background:#edf8fc!important;color:#12607d!important}.rb-filter-chip.btn-primary{border-color:#1988ad!important;background:#1988ad!important;color:#fff!important}.rb-filter-chip span{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;padding:0 6px;border-radius:999px;background:rgba(20,65,86,.09);font-size:11px}.rb-filter-chip.btn-primary span{background:rgba(255,255,255,.2);color:#fff}.rb-note{margin-bottom:15px;padding:12px 14px;border-left:4px solid #1988ad;border-radius:9px;background:#f1f8fb;color:#4d6876}.rb-note strong{color:#173f56}.rb-danger-zone{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;align-items:center;margin-bottom:15px;padding:13px 15px;border:1px solid #efdddd;border-radius:12px;background:#fffafa}.rb-danger-zone label{display:block;margin:0 0 4px;color:#8d2e2e}.rb-danger-zone small{display:block;color:#9d6a6a}.rb-danger-zone form{margin:0}.rb-danger-zone input{height:31px}.rb-toolbar{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;align-items:center;margin-bottom:15px;padding:14px 15px;border:1px solid #d7e5eb;border-radius:12px;background:linear-gradient(135deg,#ffffff,#f6fbfd);box-shadow:0 8px 22px rgba(28,61,78,.07)}.rb-selection-summary{display:grid;grid-template-columns:auto auto minmax(220px,1fr);gap:11px;align-items:center}.rb-select-all{display:flex;align-items:center;gap:9px;margin:0;font-weight:900;color:#263f50;cursor:pointer}.rb-select-all input,.rb-check-wrap input{width:18px;height:18px;margin:0}.rb-selected-count{display:inline-block;border-radius:999px;background:#e8f4fa;color:#12607d;font-weight:900;padding:7px 11px;white-space:nowrap}.rb-help{display:block;color:#758893;font-size:11px;line-height:1.45}.rb-bulk-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}.rb-bulk-actions form{display:flex;align-items:center;gap:8px;margin:0}.rb-delete-form{padding-left:10px;border-left:1px solid #e2ebef}.rb-delete-confirm{width:138px!important;height:31px!important;margin:0!important;border-radius:4px}.rb-bulk-actions .btn{border-radius:5px;font-weight:800;padding:7px 12px}.rb-bulk-actions .btn[disabled]{opacity:.48;cursor:not-allowed}.rb-table-shell{border:1px solid #dfe9ee;border-radius:12px;background:#fff;overflow:hidden}.rb-table-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-bottom:1px solid #e5eef2;background:#f8fbfc}.rb-table-tools strong{color:#173f56}.rb-table-tools span{color:#71828c}.rb-table-wrap{overflow-x:auto}.rb-table{margin-bottom:0!important}.rb-table th{background:#f3f7f9!important;color:#405665;font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-color:#e2ebef!important}.rb-table td{vertical-align:middle!important;border-color:#edf2f4!important}.rb-table tbody tr:hover{background:#f8fcfd!important}.rb-check-cell{width:62px;text-align:center}.rb-check-wrap{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:1px solid #d8e5eb;border-radius:11px;background:#fff;cursor:pointer;transition:border .15s ease,background .15s ease,box-shadow .15s ease}.rb-check-wrap:hover{border-color:#1988ad;background:#eef8fb}.rb-row{cursor:pointer;transition:background .15s ease,box-shadow .15s ease}.rb-row.is-selected{background:#f3fbff!important;box-shadow:inset 4px 0 0 #1988ad}.rb-row.is-selected .rb-check-wrap{border-color:#1988ad;background:#e6f6fb;box-shadow:0 0 0 3px rgba(25,136,173,.11)}.rb-type-badge{display:inline-block;border-radius:999px;background:#edf3f6;color:#365768;font-weight:900;font-size:11px;padding:6px 9px}.rb-item-name strong{display:block;color:#173f56;font-size:13px}.rb-item-name .muted{font-size:11px;color:#87949b;text-transform:uppercase;letter-spacing:.03em}.rb-reason{max-width:420px;color:#465c69}.rb-file-badge{display:inline-block;border-radius:7px;background:#f1f6f8;color:#526d7a;padding:5px 8px}.rb-actions{white-space:nowrap}.rb-actions .btn{border-radius:4px;font-weight:800}.rb-empty{padding:32px!important;text-align:center;color:#71828c}@media(max-width:1050px){.rb-toolbar,.rb-hero,.rb-danger-zone{grid-template-columns:1fr}.rb-bulk-actions{justify-content:flex-start}.rb-delete-form{padding-left:0;border-left:0}.rb-selection-summary{grid-template-columns:1fr}}@media(max-width:680px){.rb-bulk-actions,.rb-bulk-actions form{align-items:stretch;flex-direction:column;width:100%}.rb-bulk-actions .btn,.rb-delete-confirm{width:100%!important}.rb-actions{white-space:normal}.rb-table-tools{align-items:flex-start;flex-direction:column}}
.rb-check-input{position:absolute!important;opacity:0!important;width:1px!important;height:1px!important;margin:0!important;pointer-events:none}.rb-check-visual{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;box-sizing:border-box;border:2px solid #9bb6c4;border-radius:6px;background:#fff;color:#fff;font-size:13px;font-weight:900;line-height:1;box-shadow:inset 0 1px 0 rgba(255,255,255,.7)}.rb-check-visual:after{content:"";display:block;width:9px;height:5px;border-left:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(-45deg);opacity:0;margin-top:-2px}.rb-select-all .rb-check-visual{flex:0 0 20px}.rb-check-input:focus+.rb-check-visual{box-shadow:0 0 0 3px rgba(25,136,173,.18)}.rb-check-input:checked+.rb-check-visual,.rb-check-wrap.is-checked .rb-check-visual,.rb-select-all.is-checked .rb-check-visual{border-color:#1988ad;background:#1988ad}.rb-check-input:checked+.rb-check-visual:after,.rb-check-wrap.is-checked .rb-check-visual:after,.rb-select-all.is-checked .rb-check-visual:after{opacity:1}.rb-select-all.is-indeterminate .rb-check-visual{border-color:#1988ad;background:#1988ad}.rb-select-all.is-indeterminate .rb-check-visual:after{opacity:1;width:10px;height:0;border-left:0;border-bottom:2px solid #fff;transform:none;margin-top:0}.rb-check-wrap .checker,.rb-select-all .checker{display:none!important}.rb-empty-state{padding:38px 20px;text-align:center;background:linear-gradient(135deg,#fbfdfe,#f4f9fb)}.rb-empty-state i{display:inline-flex;align-items:center;justify-content:center;width:54px;height:54px;margin-bottom:12px;border-radius:16px;background:#edf6fa;color:#1988ad;font-size:24px}.rb-empty-state strong{display:block;color:#173f56;font-size:16px;margin-bottom:5px}.rb-empty-state span{display:block;color:#71828c}
</style>
<div id="content" class="span10">
    <ul class="breadcrumb">
        <li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li>
        <li>Recycle Bin</li>
    </ul>

    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    <div class="box rb-workspace">
        <div class="box-header">
            <h2><i class="icon-trash"></i> Recycle Bin</h2>
        </div>
        <div class="box-content">
            <div class="rb-hero">
                <div>
                    <h3>Recover deleted items with confidence</h3>
                    <p>Select items from the list, restore them in one click, or permanently delete them only after typing DELETE. Mixed item types are supported from the All view.</p>
                </div>
                <div class="rb-hero-stat"><strong>{{ $items->count() }}</strong><span>shown</span></div>
            </div>

            <div class="rb-card">
                <div class="rb-filter-bar">
                    @foreach($types as $typeKey => $typeLabel)
                        <a class="btn rb-filter-chip {{ $selectedType === $typeKey ? 'btn-primary' : '' }}" href="{{ route('recycle-bin.index', ['type' => $typeKey]) }}">{{ $typeLabel }} <span>{{ $typeCounts[$typeKey] ?? 0 }}</span></a>
                    @endforeach
                </div>
            </div>

            <div class="rb-note">
                <strong>Tip:</strong> Restore brings records back to their original admin area. Permanent deletion removes the record and any unreferenced managed files.
            </div>

            <div class="rb-danger-zone">
                <div>
                    <label for="recycle-bin-empty-confirm"><strong>Danger zone: Empty everything</strong></label>
                    <small>This permanently deletes every item currently in the Recycle Bin.</small>
                </div>
                <form method="post" action="{{ route('recycle-bin.empty') }}">
                    @csrf
                    <div class="input-append">
                        <input id="recycle-bin-empty-confirm" type="text" name="confirm_text" placeholder="Type DELETE" style="width:160px">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Permanently delete all items currently in the Recycle Bin? This cannot be undone.')">Empty Recycle Bin</button>
                    </div>
                </form>
            </div>

            @if($canBulk)
                <div class="rb-toolbar">
                    <div class="rb-selection-summary">
                        <label class="rb-select-all"><input class="rb-check-input" type="checkbox" data-recycle-select-all data-no-uniform="true"><span class="rb-check-visual" aria-hidden="true"></span> Select all {{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }} in this filter</label>
                        <span class="rb-selected-count" data-recycle-selected-count>No items selected</span>
                        <span class="rb-help">Selected rows are highlighted. Batch actions work on this whole list, including the All filter.</span>
                    </div>
                    <div class="rb-bulk-actions">
                        <form method="post" action="{{ route('recycle-bin.bulk-restore', ['type' => $selectedType]) }}" class="recycle-bin-bulk-form" data-bulk-form="restore">
                            @csrf
                            <div class="recycle-bin-bulk-target"></div>
                            <button type="submit" class="btn btn-success" data-bulk-button><i class="icon-refresh"></i> Restore Selected</button>
                        </form>
                        <form method="post" action="{{ route('recycle-bin.bulk-delete', ['type' => $selectedType]) }}" class="recycle-bin-bulk-form rb-delete-form" data-bulk-form="delete">
                            @csrf
                            <input class="rb-delete-confirm" type="text" name="confirm_text" placeholder="Type DELETE" aria-label="Confirm permanent delete">
                            <div class="recycle-bin-bulk-target"></div>
                            <button type="submit" class="btn btn-danger" data-bulk-button onclick="return confirm('Delete the selected items permanently? This cannot be undone.')"><i class="icon-trash"></i> Delete Selected</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">No deleted items are available for batch actions.</div>
            @endif

            <div class="rb-table-shell">
                <div class="rb-table-tools">
                    <strong>{{ $types[$selectedType] ?? 'Deleted items' }}</strong>
                    <span>Use the checkbox column or click a row to select it.</span>
                </div>
                @if($items->isEmpty())
                    <div class="rb-empty-state">
                        <i class="icon-ok"></i>
                        <strong>No deleted items found</strong>
                        <span>This filter is clean. Deleted records will appear here when items are moved to the Recycle Bin.</span>
                    </div>
                @else
                    <div class="rb-table-wrap"><table class="table table-striped table-bordered bootstrap-datatable datatable rb-table">
                        <thead>
                            <tr>
                                <th style="width:32px">Select</th>
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
                            @foreach($items as $item)
                                <tr class="rb-row">
                                    <td class="rb-check-cell"><label class="rb-check-wrap"><input type="checkbox" class="recycle-bin-item rb-check-input" value="{{ $item['id'] }}" data-entity-type="{{ $item['entity_type'] }}" data-no-uniform="true" aria-label="Select {{ $item['entity_label'] }} {{ $item['name'] }}" {{ $canBulk ? '' : 'disabled' }}><span class="rb-check-visual" aria-hidden="true"></span></label></td>
                                    <td><span class="rb-type-badge">{{ $item['entity_label'] }}</span></td>
                                    <td class="rb-item-name">
                                        <strong>{{ $item['name'] }}</strong><br>
                                        <span class="muted">{{ $item['reference'] }}</span>
                                    </td>
                                    <td>{{ $item['deleted_at'] ?? '-' }}</td>
                                    <td>{{ $item['deleted_by_name'] ?? ($item['deleted_by'] ?? '-') }}</td>
                                    <td class="rb-reason">{{ $item['delete_reason'] ?: '-' }}</td>
                                    <td>
                                        @if(! empty($item['media_paths']))
                                            <span class="rb-file-badge">{{ count($item['media_paths']) }} file(s)</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="rb-actions">
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
                            @endforeach
                        </tbody>
                    </table></div>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('.recycle-bin-bulk-form');
    var selectAll = document.querySelector('[data-recycle-select-all]');
    var countOutput = document.querySelector('[data-recycle-selected-count]');
    var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.recycle-bin-item'));
    var bulkButtons = Array.prototype.slice.call(document.querySelectorAll('[data-bulk-button]'));

    function selectedItems() {
        return checkboxes.filter(function (input) {
            return input.checked && !input.disabled;
        }).map(function (input) {
            return {
                id: input.value,
                type: input.getAttribute('data-entity-type') || ''
            };
        });
    }

    function updateSelectionUi() {
        var selected = selectedItems().length;
        if (countOutput) {
            countOutput.textContent = selected ? selected + (selected === 1 ? ' item selected' : ' items selected') : 'No items selected';
        }
        if (selectAll) {
            var enabled = checkboxes.filter(function (input) { return !input.disabled; });
            selectAll.checked = enabled.length > 0 && selected === enabled.length;
            selectAll.indeterminate = selected > 0 && selected < enabled.length;
            var selectAllLabel = selectAll.closest('label');
            if (selectAllLabel) {
                selectAllLabel.classList.toggle('is-checked', selectAll.checked);
                selectAllLabel.classList.toggle('is-indeterminate', selectAll.indeterminate);
            }
        }
        bulkButtons.forEach(function (button) {
            button.disabled = selected === 0;
        });
        checkboxes.forEach(function (input) {
            var row = input.closest('tr');
            var label = input.closest('label');
            if (label) {
                label.classList.toggle('is-checked', input.checked && !input.disabled);
            }
            if (row) {
                row.classList.toggle('is-selected', input.checked && !input.disabled);
            }
        });
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (input) {
                if (!input.disabled) {
                    input.checked = selectAll.checked;
                }
            });
            updateSelectionUi();
        });
    }

    checkboxes.forEach(function (input) {
        input.addEventListener('change', updateSelectionUi);
    });

    document.querySelectorAll('.rb-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a,button,input,form,label')) {
                return;
            }

            var checkbox = row.querySelector('.recycle-bin-item');
            if (!checkbox || checkbox.disabled) {
                return;
            }

            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', {bubbles: true}));
        });
    });

    function prepareForm(form) {
        form.addEventListener('submit', function (event) {
            var items = selectedItems();
            if (!items.length) {
                event.preventDefault();
                alert('Select at least one item first.');
                return;
            }

            var target = form.querySelector('.recycle-bin-bulk-target');
            if (!target) {
                return;
            }

            target.innerHTML = '';
            items.forEach(function (item, index) {
                var typeInput = document.createElement('input');
                var idInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'items[' + index + '][type]';
                typeInput.value = item.type;
                idInput.type = 'hidden';
                idInput.name = 'items[' + index + '][id]';
                idInput.value = item.id;
                target.appendChild(typeInput);
                target.appendChild(idInput);
            });
        });
    }

    Array.prototype.forEach.call(forms, prepareForm);
    updateSelectionUi();
});
</script>
@endsection
