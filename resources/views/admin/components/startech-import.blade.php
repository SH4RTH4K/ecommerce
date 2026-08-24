@php
    $siteSettings = $siteSettings ?? collect();
    $sourceAddress = old('source_address', $sourceAddress ?? catalog_import_source_address($siteSettings->get('catalog_import_source_address')));
    $sourceLabel = catalog_import_source_label($sourceAddress);
    $title = $title ?? $sourceLabel.' source import';
    $description = $description ?? 'Import the live catalog source structure here in the correct order, then keep editing it in this admin area.';
    $action = $action ?? route('catalog-hierarchy.startech-import');
    $catalogImportSettingsUrl = $catalogImportSettingsUrl ?? url('/site-customization#catalog-import-workspace');
    $stepLabels = $stepLabels ?? [
        'categories' => 'Categories',
        'subcategories' => 'Subcategories',
        'brands' => 'Brands',
        'series' => 'Series',
    ];
    $importState = isset($importState) && is_array($importState) ? $importState : null;
    $selectedSteps = old('steps', $selectedSteps ?? array_keys($stepLabels));
    if (! is_array($selectedSteps)) {
        $selectedSteps = array_keys($stepLabels);
    }
    $selectedSteps = array_values(array_unique($selectedSteps));
    $productCategoryOptions = $productCategoryOptions ?? [];
    $selectedProductCategory = old('category_slug', $selectedProductCategory ?? ($importState['category_slug'] ?? ''));
    $scopeSelectLabel = $scopeSelectLabel ?? null;
    $scopeSelectName = $scopeSelectName ?? null;
    $scopeSelectOptions = $scopeSelectOptions ?? [];
    $scopeSelectPlaceholder = $scopeSelectPlaceholder ?? 'All items';
    $scopeSelectHelp = $scopeSelectHelp ?? '';
    $selectedScopeValue = $selectedScopeValue ?? '';
    if ($scopeSelectName) {
        $selectedScopeValue = old($scopeSelectName, $selectedScopeValue);
    }
    $allowSingleProductImport = $allowSingleProductImport ?? false;
    $selectedProductUrl = old('product_url', $selectedProductUrl ?? '');
    $dryRun = old('dry_run');
    $previewResults = $previewResults ?? session('startech_catalog_import_preview');
    if (is_array($previewResults) && (($previewResults['source_address'] ?? null) !== $sourceAddress)) {
        $previewResults = null;
    }
    $showSourceImportPanelOverride = $showSourceImportPanelOverride ?? null;
    $showSourceImportPanel = $showSourceImportPanelOverride === null
        ? ((string) $siteSettings->get('startech_source_import_enabled', '1') === '1')
        : (bool) $showSourceImportPanelOverride;
    $showSourceImportPlaceholder = $showSourceImportPlaceholder ?? true;
    $showSourceSaveButton = $showSourceSaveButton ?? true;
    $sourceSaveLabel = $sourceSaveLabel ?? 'Save source address';
    $productBatchSize = old('product_batch_size', $productBatchSize ?? (($importState['batch_size'] ?? null) !== null ? (string) $importState['batch_size'] : '1'));
    $productCursor = old('product_cursor', $productCursor ?? ($importState['cursor'] ?? ''));
    $submitLabel = $submitLabel ?? 'Import selected source data';
    $helpText = $helpText ?? 'Leave every box selected to import everything. Use Fetch only to preview the selected source data before saving changes.';
    $noteTitle = $noteTitle ?? 'What happens first';
    $noteBody = $noteBody ?? 'Categories are created first, then subcategories, then brands, then product series, so product assignments stay in the right order.';
    $selectedProductCategoryLabel = $selectedProductCategory && isset($productCategoryOptions[$selectedProductCategory])
        ? $productCategoryOptions[$selectedProductCategory]
        : 'All categories';
@endphp
<style>
.sti-card{margin:0 0 18px;border:1px solid #d9e5ec;border-radius:12px;background:linear-gradient(180deg,#fff,#f7fbfd);box-shadow:0 6px 18px rgba(18,63,97,.06);overflow:hidden}
.sti-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;padding:18px 20px;border-bottom:1px solid #e4edf2;background:linear-gradient(120deg,#fff8f1,#f4fbff)}
.sti-title{display:flex;gap:12px;align-items:flex-start}
.sti-icon{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;background:#f5821f;color:#fff;font-size:18px;flex:0 0 auto}
.sti-title h3{margin:0;color:#123f61;font-size:17px}
.sti-title p{margin:4px 0 0;color:#607684;font-size:12px;max-width:680px}
.sti-source{margin:8px 0 0;color:#516571;font-size:12px}
.sti-source a{color:#17618d;text-decoration:none;font-weight:700}
.sti-source a:hover{text-decoration:underline}
.sti-source-form{margin-top:12px;display:grid;gap:6px;max-width:640px}
.sti-source-form label{font-weight:700;color:#234861;font-size:12px}
.sti-source-input{width:100%;box-sizing:border-box;height:40px;border:1px solid #c9d8e0;border-radius:9px;padding:0 12px;background:#fff;color:#16384f;font-size:13px}
.sti-source-input:focus{outline:none;border-color:#f5821f;box-shadow:0 0 0 3px rgba(245,130,31,.14)}
.sti-source-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px}
.sti-source-actions .btn{flex:0 0 auto}
.sti-flow{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.sti-chip{padding:7px 11px;border-radius:999px;background:#eef5f9;color:#214e67;font-size:12px;font-weight:700}
.sti-body{padding:18px 20px}
.sti-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:14px}
.sti-check{display:flex;align-items:center;gap:8px;padding:11px 12px;border:1px solid #dbe6ec;border-radius:10px;background:#fff;font-weight:700;color:#234861}
.sti-check input{margin:0}
.sti-batch{margin:0 0 14px;padding:12px 14px;border:1px solid #d9e6ee;border-radius:10px;background:#fff;display:grid;gap:6px}
.sti-batch label{font-weight:700;color:#234861;font-size:12px}
.sti-batch small{color:#718392;font-size:12px;line-height:1.45}
.sti-progress{margin:0 0 14px;padding:12px 14px;border-left:4px solid #1988ad;border-radius:8px;background:#eef8fc;color:#234861}
.sti-progress strong{display:block;margin-bottom:4px;color:#123f61}
.sti-progress small{display:block;color:#5b6f7b}
.sti-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.sti-help{margin:0;color:#718392;font-size:12px}
.sti-note{margin-top:12px;padding:12px 14px;border-left:4px solid #f5821f;background:#fff7ef;color:#7e4f10;border-radius:8px}
.sti-note strong{display:block;margin-bottom:4px}
.sti-preview{margin:14px 0 16px;padding:14px 16px;border:1px solid #d6e4ec;border-left:4px solid #1988ad;border-radius:10px;background:#f4fbff}
.sti-preview strong{display:block;color:#123f61;font-size:15px;margin-bottom:5px}
.sti-preview p{margin:0 0 12px;color:#607684;font-size:12px;line-height:1.45}
.sti-preview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px}
.sti-preview-item{padding:10px 11px;border:1px solid #dbe6ec;border-radius:9px;background:#fff}
.sti-preview-item strong{display:block;margin:0;color:#173f56;font-size:13px}
.sti-preview-item span{display:block;margin-top:3px;color:#607684;font-size:12px;line-height:1.4}
.sti-hidden{display:none!important}
.sti-disabled{margin:0 0 18px;padding:18px 20px;border:1px dashed #d9e5ec;border-radius:12px;background:#fff;box-shadow:0 6px 18px rgba(18,63,97,.06);display:flex;justify-content:space-between;align-items:center;gap:14px}
.sti-disabled strong{display:block;color:#123f61;font-size:16px;margin-bottom:4px}
.sti-disabled span{display:block;color:#607684;font-size:12px;line-height:1.45;max-width:760px}
.sti-disabled .btn{flex:0 0 auto}
@media(max-width:1100px){.sti-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sti-head{flex-direction:column}.sti-flow{justify-content:flex-start}}
@media(max-width:640px){.sti-grid{grid-template-columns:1fr}.sti-disabled{flex-direction:column;align-items:flex-start}}
</style>
@if($showSourceImportPanel)
<section class="sti-card">
    <header class="sti-head">
        <div class="sti-title">
            <div class="sti-icon"><i class="icon-download-alt"></i></div>
            <div>
                <h3>{{ $title }}</h3>
                <p>{{ $description }}</p>
                <p class="sti-source">Source address: <a href="{{ $sourceAddress }}" target="_blank" rel="noopener">{{ $sourceAddress }}</a></p>
            </div>
        </div>
        <div class="sti-flow">
            @foreach($stepLabels as $stepLabel)
                <span class="sti-chip">{{ $loop->iteration }}. {{ $stepLabel }}</span>
            @endforeach
        </div>
    </header>
    <div class="sti-body">
        <form method="post" action="{{ $action }}">
            {{ csrf_field() }}
            <div class="sti-source-form">
                <label for="sti-source-address">Source address</label>
                <input id="sti-source-address" class="sti-source-input" type="url" name="source_address" value="{{ $sourceAddress }}" placeholder="https://www.startech.com.bd/" required autocomplete="url">
                <small class="sti-help">Change this URL when the source moves. Save it now, or fetch/import once to remember it automatically.</small>
                @if($showSourceSaveButton)
                    <div class="sti-source-actions">
                        <button class="btn" type="submit" name="save_source_address" value="1"><i class="icon-save"></i> {{ $sourceSaveLabel }}</button>
                        <small class="sti-help">Use this when you only want to update the saved source URL.</small>
                    </div>
                @endif
            </div>
            @if(is_array($previewResults) && ! empty($previewResults['results']))
                <div class="sti-preview">
                    <strong>Last fetch preview</strong>
                    <p>{{ $previewResults['summary'] ?? 'Preview completed for the selected source.' }} Review the step list below and import only the parts you need.</p>
                    <div class="sti-preview-grid">
                        @foreach($previewResults['results'] as $step => $stats)
                            @php
                                $stepLabel = $stepLabels[$step] ?? ucfirst($step);
                                $parts = [];
                                if (array_key_exists('created', $stats) || array_key_exists('updated', $stats)) {
                                    $parts[] = (int) ($stats['created'] ?? 0).' created';
                                    $parts[] = (int) ($stats['updated'] ?? 0).' updated';
                                }
                                if (array_key_exists('processed', $stats)) {
                                    $parts[] = (int) ($stats['processed'] ?? 0).' processed';
                                }
                                if (array_key_exists('remaining', $stats) && ! empty($stats['has_more'])) {
                                    $parts[] = (int) ($stats['remaining'] ?? 0).' remaining';
                                }
                            @endphp
                            <div class="sti-preview-item">
                                <strong>{{ $stepLabel }}</strong>
                                <span>{{ $parts !== [] ? implode(', ', $parts) : 'Preview data available.' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($importState && array_key_exists('products', $stepLabels))
                <div class="sti-progress">
                    <strong>Product batch in progress</strong>
                    <small>{{ (int) ($importState['processed'] ?? 0) }} processed out of {{ (int) ($importState['total'] ?? 0) }}. {{ (int) ($importState['remaining'] ?? 0) }} remaining. Re-run the import to continue from the saved cursor.</small>
                </div>
            @endif
            <div class="sti-grid" id="sti-mode-grid">
                @foreach($stepLabels as $step => $label)
                    <label class="sti-check">
                        <input type="checkbox" name="steps[]" value="{{ $step }}" {{ in_array($step, $selectedSteps, true) ? 'checked' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
                <label class="sti-check">
                    <input type="checkbox" name="dry_run" value="1" {{ $dryRun ? 'checked' : '' }}>
                    <span>Fetch only — do not save</span>
                </label>
            </div>
            <div class="sti-help sti-dry-run-help" style="{{ $dryRun ? '' : 'display:none' }}">Preview mode is active: categories, brands, series, and products will not be written to the database.</div>
            @if($scopeSelectName)
                <div class="sti-batch" id="sti-scope-wrap">
                    <label for="sti-scope-select">{{ $scopeSelectLabel }}</label>
                    <select id="sti-scope-select" class="sti-source-input" name="{{ $scopeSelectName }}">
                        <option value="">{{ $scopeSelectPlaceholder }}</option>
                        @foreach($scopeSelectOptions as $scopeValue => $scopeLabel)
                            <option value="{{ $scopeValue }}" {{ (string) $selectedScopeValue === (string) $scopeValue ? 'selected' : '' }}>{{ $scopeLabel }}</option>
                        @endforeach
                    </select>
                    @if($scopeSelectHelp !== '')
                        <small>{{ $scopeSelectHelp }}</small>
                    @endif
                </div>
            @endif
            @if($productCategoryOptions !== [] && array_key_exists('products', $stepLabels))
                <div class="sti-batch" id="sti-product-category-wrap">
                    <label for="sti-product-category">Product category</label>
                    <select id="sti-product-category" class="sti-source-input" name="category_slug">
                        <option value="">All categories</option>
                        @foreach($productCategoryOptions as $slug => $label)
                            <option value="{{ $slug }}" {{ (string) $selectedProductCategory === (string) $slug ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small>Pick a single source category to narrow the product import, or leave it on all categories.</small>
                </div>
            @endif
            @if($allowSingleProductImport)
                <div class="sti-batch" id="sti-product-url-wrap">
                    <label for="sti-product-url">Individual product link</label>
                    <input id="sti-product-url" class="sti-source-input" type="text" name="product_url" value="{{ $selectedProductUrl }}" placeholder="Paste one product page link">
                    <small>Paste a single product URL or path here to import only that product. This overrides the category and batch options below.</small>
                </div>
            @endif
            @if(array_key_exists('products', $stepLabels))
                <div class="sti-batch" id="sti-product-batch-wrap">
                    <label for="sti-product-batch-size">Product batch size</label>
                    <input id="sti-product-batch-size" class="sti-source-input" type="number" min="1" max="500" name="product_batch_size" value="{{ $productBatchSize }}" placeholder="e.g. 25" inputmode="numeric">
                    <small>Leave this at 1 to process products step by step, or raise it for a larger safe batch.</small>
                    <input type="hidden" name="product_cursor" value="{{ $productCursor }}">
                    @if($productCursor !== '')
                        <small>Saved cursor active. The next submission continues from the last imported product.</small>
                    @endif
                </div>
            @endif
            <div class="sti-actions">
                <button class="btn btn-warning sti-submit-button" type="submit"><i class="icon-refresh"></i> <span>{{ $dryRun ? 'Preview only' : $submitLabel }}</span></button>
                <span class="sti-help">{{ $helpText }}</span>
            </div>
            <div class="sti-note">
                <strong>{{ $noteTitle }}</strong>
                {{ $noteBody }} @if($selectedProductCategoryLabel !== 'All categories') Current product category: {{ $selectedProductCategoryLabel }}.@endif
            </div>
        </form>
    </div>
</section>
@elseif($showSourceImportPlaceholder)
<div class="sti-disabled">
    <div>
        <strong>{{ $sourceLabel }} source import is disabled</strong>
        <span>Turn it back on in Website Settings to show the source import workspace on catalog admin pages.</span>
    </div>
    <a class="btn btn-primary" href="{{ $catalogImportSettingsUrl }}"><i class="icon-cog"></i> Open setting</a>
</div>
@endif
@if($allowSingleProductImport)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var urlInput = document.getElementById('sti-product-url');
    if (!urlInput) {
        return;
    }

    var categoryWrap = document.getElementById('sti-product-category-wrap');
    var batchWrap = document.getElementById('sti-product-batch-wrap');
    var modeGrid = document.getElementById('sti-mode-grid');

    function setSectionDisabled(section, disabled) {
        if (!section) {
            return;
        }

        section.querySelectorAll('input, select, textarea, button').forEach(function (field) {
            if (field === urlInput) {
                return;
            }

            field.disabled = disabled;
        });
    }

    function toggleMode() {
        var hasSingleLink = urlInput.value.trim() !== '';
        if (categoryWrap) {
            categoryWrap.classList.toggle('sti-hidden', hasSingleLink);
            setSectionDisabled(categoryWrap, hasSingleLink);
        }
        if (batchWrap) {
            batchWrap.classList.toggle('sti-hidden', hasSingleLink);
            setSectionDisabled(batchWrap, hasSingleLink);
        }
        if (modeGrid) {
            modeGrid.classList.toggle('sti-hidden', hasSingleLink);
            setSectionDisabled(modeGrid, hasSingleLink);
        }
    }

    urlInput.addEventListener('input', toggleMode);
    toggleMode();
});
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[name="dry_run"]').forEach(function (checkbox) {
        var form = checkbox.closest('form');
        var button = form && form.querySelector('.sti-submit-button span');
        var help = form && form.querySelector('.sti-dry-run-help');
        if (!button) return;
        var importLabel = @json($submitLabel);
        checkbox.addEventListener('change', function () {
            button.textContent = checkbox.checked ? 'Preview only' : importLabel;
            if (help) help.style.display = checkbox.checked ? '' : 'none';
        });
    });
});
</script>
