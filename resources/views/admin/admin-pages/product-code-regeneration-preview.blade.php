<main id="content" class="span10 pcc">
    <div class="pcc-hero"><div><h1>Existing Code Impact Preview</h1><p>Dry-run only. No codes, sequences, or history are changed until you explicitly apply this preview.</p></div><div class="summary"><span class="pcc-pill">{{ strtoupper($configuration->code_type) }}</span><span class="pcc-pill">{{ $preview['total'] }} affected</span><span class="pcc-pill">{{ $preview['ready'] }} ready</span><span class="pcc-pill">{{ $preview['conflicts'] }} conflicts</span></div></div>
    @if(! empty($preview['error']))
        <div class="pcc-banner" style="background:#fff5f4;border-color:#edb0a8">
            <div><strong>Preview unavailable</strong><small>{{ $preview['error'] }}</small></div>
            <a class="btn" href="{{ route('product-code-configuration.index', ['code_type' => $configuration->code_type, 'configuration' => $configuration->id]) }}">Back to configuration</a>
        </div>
    @endif
    <div class="pcc-card"><div class="pcc-head"><h2>Old and proposed codes</h2><p>Preserve existing sequence numbers is enabled. Resolve all conflicts before applying.</p></div><div class="pcc-body">
        <form method="post" action="{{ route('product-code-configuration.regeneration.apply') }}" onsubmit="return confirm('Type REGENERATE to confirm this controlled code change. Historical transaction snapshots will not be rewritten.')">
            @csrf <input type="hidden" name="configuration_id" value="{{ $configuration->id }}"><input type="hidden" name="mode" value="{{ $mode }}">
            <div class="pcc-table-wrap"><table class="table table-bordered pcc-table" style="min-width:900px"><thead><tr><th>Select</th><th>Name</th><th>Current code</th><th>Proposed code</th><th>Status</th></tr></thead><tbody>
            @forelse($preview['items'] as $item)<tr><td><input type="checkbox" name="selected[]" value="{{ $item['entity_id'] }}" {{ $mode === 'UPDATE_ALL' ? 'checked' : '' }} {{ $item['status'] !== 'READY' ? 'disabled' : '' }}></td><td>{{ $item['name'] }}</td><td><code>{{ $item['old_code'] }}</code></td><td><code>{{ $item['new_code'] ?: '—' }}</code></td><td>{{ $item['status'] }}{{ $item['error'] ? ': '.$item['error'] : '' }}</td></tr>@empty<tr><td colspan="5">No eligible records found.</td></tr>@endforelse
            </tbody></table></div>
            <div class="pcc-grid-form" style="margin-top:18px"><div class="span-2"><label>Reason for code regeneration</label><textarea name="reason" required placeholder="Changed code format to include Company Code."></textarea></div><div><label>Strong confirmation</label><input name="confirmation" required placeholder="Type REGENERATE"></div></div>
            <div class="pcc-actions"><a class="btn" href="{{ route('product-code-configuration.index', ['code_type' => $configuration->code_type, 'configuration' => $configuration->id]) }}">Back</a><button class="btn btn-danger" type="submit" {{ ! empty($preview['error']) || $preview['conflicts'] ? 'disabled' : '' }}>Apply Changes</button></div>
            <p class="pcc-safety"><i class="icon-warning-sign"></i> Historical orders, invoices, purchases, and other snapshots are not rewritten. External integrations and barcodes may need review.</p>
        </form>
    </div></div>
</main>
