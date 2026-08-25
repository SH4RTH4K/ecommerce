@extends('admin.admin-master')
@section('title', 'Product Code Configuration')
@section('admin_main_content')
@php
    $selectedConfigurationId = old('configuration_id', optional($selectedConfiguration)->id);
    $selectedCodeType = $selectedCodeType ?? 'product';
    $codeTypes = $codeTypes ?? [];
    $selectedCodeTypeLabel = $codeTypes[$selectedCodeType] ?? \Illuminate\Support\Str::headline($selectedCodeType);
    $typeCounts = $typeCounts ?? [];
    $selectedSnapshot = is_array($snapshot ?? null) ? $snapshot : [];
    $activeSnapshot = $activeConfiguration ? app(\App\Services\ProductCodeGenerator::class)->snapshot($activeConfiguration) : [];

    $rawComponentRows = old('components');
    if (! is_array($rawComponentRows) || $rawComponentRows === []) {
        $rawComponentRows = $selectedSnapshot['components'] ?? [];
    }
    if (! is_array($rawComponentRows) || $rawComponentRows === []) {
        $rawComponentRows = [
            [
                'component_type' => 'sequence',
                'position' => 1,
                'static_value' => '',
                'format_options' => '',
                'is_required' => 1,
            ],
        ];
    }

    $componentRows = [];
    foreach ($rawComponentRows as $index => $component) {
        if (! is_array($component)) {
            continue;
        }

        $formatOptions = $component['format_options'] ?? '';
        if (is_array($formatOptions)) {
            $formatOptions = json_encode($formatOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $componentRows[] = [
            'component_type' => (string) ($component['component_type'] ?? 'sequence'),
            'position' => (int) ($component['position'] ?? ($index + 1)),
            'static_value' => (string) ($component['static_value'] ?? ''),
            'format_options' => (string) ($formatOptions ?? ''),
            'is_required' => array_key_exists('is_required', $component) ? (bool) $component['is_required'] : true,
        ];
    }

    if ($componentRows === []) {
        $componentRows[] = [
            'component_type' => 'sequence',
            'position' => 1,
            'static_value' => '',
            'format_options' => '',
            'is_required' => 1,
        ];
    }

    $separatorOptions = $separators ?? [];
    $separatorValue = old('separator', $selectedSnapshot['separator'] ?? config('product_code.default_separator', '-'));
    $separatorMode = array_key_exists($separatorValue, $separatorOptions) ? $separatorValue : '__custom__';
    $customSeparatorValue = array_key_exists($separatorValue, $separatorOptions) ? '' : $separatorValue;

    $configurationOptions = $configurations->mapWithKeys(function ($configuration) {
        return [$configuration->id => $configuration->name.' #'.$configuration->id.((int) $configuration->is_active === 1 ? ' (active)' : '')];
    })->all();

    $companyLookup = $companies->pluck('name', 'id')->all();
    $branchLookup = $branches->pluck('name', 'id')->all();
    $categoryLookup = $categories->pluck('category_name', 'category_id')->all();
    $subcategoryLookup = $subcategories->pluck('sub_category_name', 'sub_category_id')->all();
    $brandLookup = $brands->pluck('manufacturer_name', 'manufacturer_id')->all();
    $seriesLookup = $series->pluck('name', 'id')->all();

    $previewContextDefaults = $previewContextDefaults ?? [];
    $previewDefaults = [
        'company_id' => old('company_id', $selectedSnapshot['company_id'] ?? ($previewContextDefaults['company_id'] ?? '')),
        'branch_id' => old('branch_id', $selectedSnapshot['branch_id'] ?? ($previewContextDefaults['branch_id'] ?? '')),
        'category_id' => old('category_id', $previewContextDefaults['category_id'] ?? ''),
        'subcategory_id' => old('subcategory_id', $previewContextDefaults['subcategory_id'] ?? ''),
        'manufacturer_id' => old('manufacturer_id', $previewContextDefaults['manufacturer_id'] ?? ''),
        'series_id' => old('series_id', $previewContextDefaults['series_id'] ?? ''),
        'variant_code' => old('variant_code', ''),
        'custom_prefix' => old('custom_prefix', ''),
        'custom_suffix' => old('custom_suffix', ''),
        'product_type_code' => old('product_type_code', ''),
    ];

    $componentLabels = $availableComponents ?? [];
    $simplePrefix = collect($componentRows)->firstWhere('component_type', 'prefix')['static_value'] ?? '';
    $simpleHasNameCode = collect($componentRows)->contains(fn ($component) => $component['component_type'] === 'name_code');
    $simpleHasSequence = collect($componentRows)->contains(fn ($component) => $component['component_type'] === 'sequence');
@endphp
<style>
.pcc{padding:26px}
.pcc-hero{background:linear-gradient(135deg,#0a3854,#0f6b8f);color:#fff;border-radius:14px;padding:24px;margin-bottom:18px;box-shadow:0 10px 22px rgba(10,48,70,.12);display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
.pcc-hero h1{margin:0 0 8px;font-size:27px}
.pcc-hero p{margin:0;opacity:.9;max-width:920px}
.pcc-hero .summary{display:grid;gap:7px;min-width:260px}
.pcc-pill{padding:8px 11px;border-radius:999px;background:rgba(255,255,255,.14);font-size:12px;font-weight:700;white-space:nowrap}
.pcc-banner{margin:0 0 18px;padding:14px 16px;border:1px solid #dbe7ee;border-radius:12px;background:#f7fbfd;display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap}
.pcc-banner strong{display:block;margin-bottom:5px;color:#123f61}
.pcc-banner small{display:block;color:#607684;line-height:1.45}
.pcc-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.pcc-tab{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border:1px solid #dce7ed;border-radius:999px;background:#fff;color:#315468;font-weight:700;text-decoration:none;box-shadow:0 3px 10px rgba(14,56,76,.04)}
.pcc-tab{align-items:flex-start;border-radius:10px;min-width:150px}.pcc-tab-copy{display:grid;gap:2px}.pcc-tab-copy small{font-size:10px;font-weight:400;line-height:1.2;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;opacity:.8}
.pcc-tab:hover{text-decoration:none;background:#f6fbfe}
.pcc-tab.active{background:#0f6b8f;color:#fff;border-color:#0f6b8f}
.pcc-tab .count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 7px;border-radius:999px;background:rgba(255,255,255,.2);font-size:12px}
.pcc-tab:not(.active) .count{background:#eef5f9;color:#0f6b8f}
.pcc-selector{margin-bottom:18px;background:#fff;border:1px solid #dce7ed;border-radius:12px;padding:15px 16px;display:flex;gap:12px;align-items:end;flex-wrap:wrap;box-shadow:0 4px 16px rgba(18,63,97,.05)}
.pcc-selector label{font-weight:600;margin:0}
.pcc-selector select,.pcc-selector input{margin:6px 0 0!important;height:38px;min-width:260px}
.pcc-selector .btn{height:38px}
.pcc-grid{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(320px,.95fr);gap:18px;align-items:start}
.pcc-card{background:#fff;border:1px solid #dce7ed;border-radius:12px;box-shadow:0 5px 18px rgba(14,56,76,.06);overflow:hidden}
.pcc-head{padding:18px;border-bottom:1px solid #e8eff3}
.pcc-head h2{margin:0 0 4px;font-size:19px}
.pcc-head p{margin:0;color:#667985}
.pcc-body{padding:18px}
.pcc-grid-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.pcc-grid-form .span-2{grid-column:1 / -1}
.pcc-grid-form label{display:block;font-weight:600;margin:0 0 6px}
.pcc-grid-form input,.pcc-grid-form select,.pcc-grid-form textarea{width:100%;box-sizing:border-box;margin:0}
.pcc-grid-form input[type=text],.pcc-grid-form input[type=number],.pcc-grid-form input[type=datetime-local],.pcc-grid-form select{height:38px}
.pcc-grid-form textarea{min-height:88px}
.pcc-checks{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.pcc-checks label{margin:0;padding:8px 11px;border:1px solid #dbe6ec;border-radius:10px;background:#f8fbfd;font-weight:400}
.pcc-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.pcc-actions .btn{margin:0}
.pcc-note{margin-top:14px;padding:12px 14px;border-left:4px solid #f5821f;background:#fff7ef;color:#7e4f10;border-radius:8px}
.pcc-note strong{display:block;margin-bottom:4px;color:#7e4f10}
.pcc-help{margin:12px 0 0;color:#758995;font-size:12px}
.pcc-token-list{display:flex;flex-wrap:wrap;gap:8px}
.pcc-token{display:inline-flex;align-items:center;padding:6px 9px;border-radius:999px;background:#eef5f9;color:#214e67;font-size:12px;font-weight:700}
.pcc-preview{padding:14px 16px;border:1px solid #d6e4ec;border-left:4px solid #1988ad;border-radius:10px;background:#f4fbff;margin-bottom:14px}
.pcc-preview strong{display:block;color:#123f61;font-size:15px;margin-bottom:5px}
.pcc-preview code{display:block;padding:8px 10px;border-radius:8px;background:#fff;border:1px solid #d8e6ee;color:#184e69;word-break:break-all}
.pcc-preview small{display:block;margin-top:7px;color:#607684;line-height:1.45}
.pcc-preview .error{color:#a32d1e}
.pcc-preview-panel{display:grid;gap:12px}
.pcc-section{margin-top:18px}
.pcc-section h3{margin:0 0 10px;font-size:17px;color:#123f61}
.pcc-table-wrap{overflow:auto;border:1px solid #dce7ed;border-radius:12px;background:#fff;box-shadow:0 5px 18px rgba(14,56,76,.06)}
.pcc-table{width:100%;min-width:1180px;margin:0}
.pcc-table th,.pcc-table td{vertical-align:top}
.pcc-table textarea{min-height:70px}
.pcc-row-tools{display:flex;gap:6px;flex-wrap:wrap}
.pcc-row-tools .btn{margin:0}
.pcc-small{font-size:12px;color:#607684;line-height:1.4}
.pcc-history-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.pcc-pre{max-height:220px;overflow:auto;white-space:pre-wrap;word-break:break-word;background:#f8fbfd;border:1px solid #dbe7ee;border-radius:8px;padding:10px;font-size:12px;line-height:1.45;color:#355063}
.pcc-flow{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;margin:0 0 18px}
.pcc-flow-step{display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid #dce7ed;border-radius:10px;background:#fff;color:#607684;font:inherit;font-size:12px;line-height:1.3;text-align:left;cursor:pointer}
.pcc-flow-step strong{display:block;color:#214e67;font-size:13px}.pcc-flow-step .num{display:grid;place-items:center;width:25px;height:25px;border-radius:50%;background:#eaf3f7;color:#0f6b8f;font-weight:800;flex:0 0 auto}.pcc-flow-step.active{border-color:#8bc6dc;background:#f4fbfe}.pcc-flow-step.active .num{background:#0f6b8f;color:#fff}.pcc-flow-step.completed{border-color:#b8dfc8;background:#f4fbf6}.pcc-flow-step.completed .num{background:#23834b;color:#fff}
.pcc-type-label{margin:0 0 9px;color:#607684;font-size:12px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
.pcc-tab{transition:transform .15s ease,box-shadow .15s ease}.pcc-tab:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(14,56,76,.1)}
.pcc-selector{position:relative}.pcc-selector .selector-copy{flex:1;min-width:220px}.pcc-selector .selector-copy strong{display:block;color:#123f61}.pcc-selector .selector-copy small{display:block;margin-top:3px;color:#758995}
.pcc-status{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:#e9f8ef;color:#147043;font-size:11px;font-weight:800}.pcc-status.is-draft{background:#fff4e5;color:#9a5c08}
.pcc-section-title{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin:20px 0 10px;padding-bottom:9px;border-bottom:1px solid #e8eff3}.pcc-section-title h3{margin:0}.pcc-section-title small{max-width:440px;text-align:right;color:#758995;line-height:1.4}
.pcc-sticky-actions{position:sticky;bottom:12px;z-index:5;padding:11px 12px;background:rgba(255,255,255,.96);border:1px solid #cfe0e8;border-radius:11px;box-shadow:0 8px 22px rgba(14,56,76,.14)}
.pcc-safety{display:flex;gap:9px;align-items:flex-start;margin-top:12px;padding:11px 13px;border:1px solid #f0d49c;border-radius:9px;background:#fff9ec;color:#76521a;font-size:12px;line-height:1.45}.pcc-safety i{margin-top:2px}
.pcc-table-wrap{scrollbar-color:#b8d1dc #f5f9fb}.pcc-table th{background:#f4f8fa;color:#315468;font-size:12px;white-space:nowrap}.pcc-table td input,.pcc-table td select,.pcc-table td textarea{max-width:100%;box-sizing:border-box}
.pcc-intro{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:16px;margin-bottom:18px}.pcc-intro-card{background:#fff;border:1px solid #dce7ed;border-radius:12px;padding:18px;box-shadow:0 5px 18px rgba(14,56,76,.05)}.pcc-intro-card h2{margin:0 0 7px;color:#123f61;font-size:18px}.pcc-intro-card p{margin:0;color:#607684;line-height:1.55}.pcc-checklist{margin:0;padding:0;list-style:none}.pcc-checklist li{display:flex;gap:9px;align-items:flex-start;margin:8px 0;color:#315468;font-size:13px}.pcc-checklist i{color:#1988ad;margin-top:2px}.pcc-stage-label{display:flex;align-items:center;gap:8px;margin:0 0 9px;color:#123f61;font-size:13px;font-weight:800}.pcc-stage-label .stage-number{display:grid;place-items:center;width:24px;height:24px;border-radius:50%;background:#0f6b8f;color:#fff}.pcc-policy-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.pcc-policy-option{position:relative;display:block;margin:0!important;padding:12px 12px 12px 36px!important;border:1px solid #d7e4eb;border-radius:10px;background:#fff;cursor:pointer;font-weight:400!important;line-height:1.35}.pcc-policy-option input{position:absolute;left:13px;top:15px}.pcc-policy-option strong{display:block;color:#214e67;margin-bottom:3px}.pcc-policy-option small{display:block;color:#758995;font-size:11px}.pcc-policy-option.recommended{border-color:#8bc6dc;background:#f2fbfe}.pcc-policy-help{margin:10px 0 0;color:#607684;font-size:12px;line-height:1.45}
.pcc{max-width:1380px;margin:0 auto!important;float:none!important}.pcc-wizard-tabs,.pcc-utility-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:16px 0}.pcc-wizard-tab,.pcc-utility-tab{border:1px solid #cbdfe9;background:#fff;color:#315468;border-radius:9px;padding:10px 13px;font-weight:700;font-size:12px;cursor:pointer}.pcc-wizard-tab .number{display:inline-grid;place-items:center;width:20px;height:20px;border-radius:50%;margin-right:6px;background:#e7f1f6;color:#0f6b8f}.pcc-wizard-tab.active,.pcc-utility-tab.active{background:#0f6b8f;border-color:#0f6b8f;color:#fff}.pcc-wizard-tab.active .number{background:#fff;color:#0f6b8f}.pcc-wizard-panel{display:none}.pcc-wizard-panel.active{display:block}.pcc-current-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.pcc-current-metric{border:1px solid #dce7ed;border-radius:10px;padding:12px;background:#f9fcfe}.pcc-current-metric span{display:block;font-size:11px;color:#758995;text-transform:uppercase;letter-spacing:.04em}.pcc-current-metric strong{display:block;margin-top:4px;color:#123f61;word-break:break-word}.pcc-sample-list{margin:14px 0 0;padding:0;list-style:none;border-top:1px solid #e4edf2}.pcc-sample-list li{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid #e4edf2;color:#315468}.pcc-sample-list code{color:#0f6b8f}.pcc-mode-switch{display:flex;gap:8px;margin:0 0 16px}.pcc-mode-switch button{border:1px solid #cbdfe9;border-radius:8px;background:#fff;color:#315468;padding:8px 12px;font-weight:700;cursor:pointer}.pcc-mode-switch button.active{background:#e8f7fc;border-color:#1988ad;color:#0b5d80}.pcc-simple-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;padding:15px;border:1px solid #dce7ed;border-radius:11px;background:#f9fcfe}.pcc-simple-grid label{display:block;font-weight:700;color:#315468}.pcc-simple-grid input,.pcc-simple-grid select{width:100%;height:38px;box-sizing:border-box;margin:6px 0 0}.pcc-step-actions{display:flex;justify-content:space-between;gap:10px;margin-top:20px;padding-top:15px;border-top:1px solid #e4edf2}.pcc-step-actions .right{display:flex;gap:8px}.pcc-danger-zone{margin-top:18px;padding:14px;border:1px solid #edb0a8;border-radius:10px;background:#fff5f4}.pcc-danger-zone h3{margin:0 0 5px;color:#9d2e21}.pcc-danger-zone p{margin:0 0 10px;color:#7b4b46;font-size:12px}.pcc-utility-panel{display:none}.pcc-utility-panel.active{display:block}.pcc-compare{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:12px 0}.pcc-compare div{padding:10px;border:1px solid #dbe7ee;border-radius:8px;background:#fff}.pcc-compare span{display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:#758995}.pcc-compare code{display:block;margin-top:4px;color:#0f6b8f;word-break:break-all}.pcc-impact{margin:12px 0;padding:11px 13px;border:1px solid #cbe4d1;border-radius:9px;background:#f5fbf6;color:#275c39;font-size:12px}.pcc-hidden{display:none!important}
@media(max-width:1050px){.pcc-flow{grid-template-columns:repeat(3,1fr)}}
@media(max-width:850px){.pcc-flow{grid-template-columns:repeat(2,1fr)}.pcc-section-title{display:block}.pcc-section-title small{text-align:left;display:block;margin-top:5px}}
@media(max-width:1100px){.pcc-grid{grid-template-columns:1fr}.pcc-history-grid{grid-template-columns:1fr}.pcc-hero{flex-direction:column}.pcc-selector select,.pcc-selector input{min-width:0;width:100%}}
@media(max-width:850px){.pcc-intro{grid-template-columns:1fr}.pcc-policy-options{grid-template-columns:1fr}}
@media(max-width:850px){.pcc-current-grid,.pcc-simple-grid{grid-template-columns:1fr}.pcc-wizard-tab{flex:1 1 42%}}
@media(max-width:640px){.pcc{padding:16px}.pcc-grid-form{grid-template-columns:1fr}.pcc-flow{grid-template-columns:1fr}.pcc-sticky-actions .btn{width:100%;margin:3px 0}}
</style>

<main id="content" class="span10 pcc">
    <header class="pcc-hero">
        <div>
            <h1>Product Code Configuration</h1>
            <p>Define how new {{ strtolower($selectedCodeTypeLabel) }} records receive codes. Test the format first, then decide separately whether old codes should change.</p>
        </div>
        <div class="summary">
            <span class="pcc-pill">Type: {{ $selectedCodeTypeLabel }}</span>
            <span class="pcc-pill">Active: {{ $activeSnapshot['name'] ?? ($selectedCodeTypeLabel ?? config('product_code.default_name', 'Default Product Code')) }}</span>
            <span class="pcc-pill">Template: {{ $selectedSnapshot['template'] ?? config('product_code.code_type_defaults.'.$selectedCodeType.'.template', config('product_code.default_template', '{PREFIX}-{CATEGORY_CODE}-{SUBCATEGORY_CODE}-{BRAND_CODE}-{SERIES_CODE}-{SEQUENCE}')) }}</span>
            <span class="pcc-pill">Sequence: {{ $selectedSnapshot['sequence_scope'] ?? config('product_code.default_sequence_scope', 'global') }}</span>
        </div>
    </header>

    <nav class="pcc-flow" aria-label="Configuration workflow">
        <button type="button" class="pcc-flow-step active" data-flow-step="1" data-wizard-go="1"><span class="num">1</span><span><strong>Code type</strong>Choose what to configure.</span></button>
        <button type="button" class="pcc-flow-step" data-flow-step="2" data-wizard-go="2"><span class="num">2</span><span><strong>Current setup</strong>Review active settings.</span></button>
        <button type="button" class="pcc-flow-step" data-flow-step="3" data-wizard-go="3"><span class="num">3</span><span><strong>Build format</strong>Create the new pattern.</span></button>
        <button type="button" class="pcc-flow-step" data-flow-step="4" data-wizard-go="4"><span class="num">4</span><span><strong>Existing codes</strong>Choose what changes.</span></button>
        <button type="button" class="pcc-flow-step" data-flow-step="5" data-wizard-go="5"><span class="num">5</span><span><strong>Test &amp; preview</strong>Validate safely.</span></button>
        <button type="button" class="pcc-flow-step" data-flow-step="6" data-wizard-go="6"><span class="num">6</span><span><strong>Review &amp; save</strong>Commit the configuration.</span></button>
    </nav>

    <div class="pcc-intro pcc-wizard-panel active" id="wizard-intro-panel">
        <div class="pcc-intro-card"><h2>What this page controls</h2><p>This format is used when a new {{ strtolower($selectedCodeTypeLabel) }} is created. Editing and saving this page does not rewrite existing codes. Old-code changes require a separate preview and confirmation.</p></div>
        <div class="pcc-intro-card"><h2>Safe workflow</h2><ul class="pcc-checklist"><li><i class="icon-ok"></i><span>Build the format</span></li><li><i class="icon-ok"></i><span>Test a sample</span></li><li><i class="icon-ok"></i><span>Save for future records</span></li><li><i class="icon-lock"></i><span>Review old-code impact separately</span></li></ul></div>
    </div>

    @if(session('message'))<div class="pcc-banner"><div><strong>Success</strong><small>{{ session('message') }}</small></div></div>@endif
    @if(session('exception'))<div class="pcc-banner"><div><strong>Attention</strong><small>{{ session('exception') }}</small></div></div>@endif
    @if($errors->any())<div class="pcc-banner"><div><strong>Please review the form</strong><small>{{ $errors->first() }}</small></div></div>@endif
    @if(! empty($previewWarnings))
        <div class="pcc-banner" style="background:#fff7e8;border-color:#f0c36d">
            <div>
                <strong>Preview needs catalog data</strong>
                <small>
                    @foreach($previewWarnings as $warning)
                        {{ $warning }}@if(! $loop->last)<br>@endif
                    @endforeach
                </small>
            </div>
        </div>
    @endif

    <div class="pcc-type-label">Step 1 · Choose what kind of code you are configuring</div>
    <div class="pcc-tabs" id="wizard-code-types">
        @foreach($codeTypes as $codeType => $label)
            <a class="pcc-tab {{ $selectedCodeType === $codeType ? 'active' : '' }}" href="{{ url('/product-code-configuration?code_type='.$codeType) }}">
                <span class="pcc-tab-copy"><strong>{{ $label }}</strong><small>{{ !empty($codeTypeSummaries[$codeType]['is_active']) ? 'Active · ' : 'No active setup · ' }}{{ $codeTypeSummaries[$codeType]['template'] ?? 'Not configured' }}</small></span>
                <span class="count">{{ (int) ($typeCounts[$codeType] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <form class="pcc-selector pcc-hidden" id="wizard-current-selector" method="get" action="{{ url('/product-code-configuration') }}">
        <input type="hidden" name="code_type" value="{{ $selectedCodeType }}">
        <div class="selector-copy"><strong>Step 2 · Choose a saved configuration</strong><small>Loading a version does not change anything until you press Save.</small></div>
        @if($activeConfiguration)<span class="pcc-status"><i class="icon-ok"></i> Active version loaded</span>@else<span class="pcc-status is-draft"><i class="icon-warning-sign"></i> No active version</span>@endif
        <label for="configuration-switch" class="sr-only">Load configuration</label>
        <select id="configuration-switch" name="configuration">
            @foreach($configurationOptions as $configurationId => $configurationLabel)
                <option value="{{ $configurationId }}" {{ (string) $selectedConfigurationId === (string) $configurationId ? 'selected' : '' }}>{{ $configurationLabel }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary"><i class="icon-folder-open"></i> Load</button>
        <a class="btn" href="{{ url('/product-code-configuration') }}">Refresh active</a>
    </form>

    <section class="pcc-card pcc-wizard-panel" id="wizard-current-summary">
        <div class="pcc-head"><h2>Current {{ $selectedCodeTypeLabel }} setup</h2><p>Review what is active now before changing the format.</p></div>
        <div class="pcc-body">
            <div class="pcc-current-grid">
                <div class="pcc-current-metric"><span>Format</span><strong>{{ $selectedSnapshot['template'] ?? 'Not configured' }}</strong></div>
                <div class="pcc-current-metric"><span>Status</span><strong>{{ !empty($selectedSnapshot['is_active']) ? 'Active' : 'Draft / inactive' }}</strong></div>
                <div class="pcc-current-metric"><span>Sequence</span><strong>{{ $selectedSnapshot['sequence_scope'] ?? 'Global' }} · {{ $selectedSnapshot['sequence_length'] ?? '-' }} digits</strong></div>
                <div class="pcc-current-metric"><span>Existing records</span><strong>{{ number_format($existingRecordCount ?? 0) }}</strong></div>
            </div>
            <ul class="pcc-sample-list">@forelse($currentCodeSample ?? [] as $sample)<li><span>{{ $sample->name }}</span><code>{{ $sample->code }}</code></li>@empty<li><span>No current codes are available yet.</span></li>@endforelse</ul>
            <div class="pcc-step-actions"><span></span><div class="right"><button type="button" class="btn btn-primary" data-wizard-next>Continue to build format <i class="icon-arrow-right"></i></button></div></div>
        </div>
    </section>

    <div class="pcc-grid pcc-hidden" id="wizard-editor-shell">
        <section class="pcc-card">
            <div class="pcc-head">
                <h2>Step 3 · Design the code format</h2>
                <p>Set the scope, sequence, and code components. The rows below are joined from top to bottom.</p>
            </div>
            <div class="pcc-body">
                <form id="product-code-configuration-form" method="post" action="{{ url('/product-code-configuration') }}">
                    @csrf
                    <input type="hidden" name="configuration_id" value="{{ $selectedConfigurationId }}">
                    <input type="hidden" name="code_type" value="{{ $selectedCodeType }}">
                    <input type="hidden" name="separator" id="separator" value="{{ $separatorValue }}">

                    <div class="pcc-section-title"><h3>Basic identity and scope</h3><small>These settings decide where this configuration applies.</small></div>
                    <div class="pcc-grid-form">
                        <div>
                            <label for="name">Configuration name</label>
                            <input id="name" name="name" type="text" maxlength="160" required value="{{ old('name', $selectedSnapshot['name'] ?? config('product_code.default_name', 'Default Product Code')) }}" placeholder="Default Product Code">
                        </div>
                        <div>
                            <label for="sequence_scope">Sequence scope</label>
                            <select id="sequence_scope" name="sequence_scope" required>
                                @foreach($sequenceScopes as $value => $label)
                                    <option value="{{ $value }}" {{ old('sequence_scope', $selectedSnapshot['sequence_scope'] ?? config('product_code.default_sequence_scope', 'global')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="company_id">Company</label>
                            <select id="company_id" name="company_id">
                                <option value="">All companies</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ (string) $previewDefaults['company_id'] === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}{{ $company->company_code ? ' - '.$company->company_code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="branch_id">Branch</label>
                            <select id="branch_id" name="branch_id">
                                <option value="">All branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string) $previewDefaults['branch_id'] === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}{{ $branch->code ? ' - '.$branch->code : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="sequence_length">Sequence length</label>
                            <input id="sequence_length" name="sequence_length" type="number" min="1" max="12" required value="{{ old('sequence_length', $selectedSnapshot['sequence_length'] ?? config('product_code.default_sequence_length', 6)) }}">
                        </div>
                        <div>
                            <label for="sequence_start">Starting number</label>
                            <input id="sequence_start" name="sequence_start" type="number" min="1" max="999999999" required value="{{ old('sequence_start', $selectedSnapshot['sequence_start'] ?? config('product_code.default_sequence_start', 1)) }}">
                        </div>

                        <div class="span-2">
                            <label for="separator_mode">Separator</label>
                            <select id="separator_mode" name="separator_mode">
                                @foreach($separatorOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $separatorMode === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                                <option value="__custom__" {{ $separatorMode === '__custom__' ? 'selected' : '' }}>Custom separator</option>
                            </select>
                            <input id="custom_separator" type="text" maxlength="10" placeholder="Custom separator" value="{{ $customSeparatorValue }}" style="display:{{ $separatorMode === '__custom__' ? 'block' : 'none' }};margin-top:8px">
                            <div class="pcc-small">Choose a preset or type a custom separator up to 10 characters. Leave it empty for no separator.</div>
                        </div>

                        <div>
                            <label for="reset_rule">Reset rule</label>
                            <select id="reset_rule" name="reset_rule" required>
                                @foreach($resetRules as $value => $label)
                                    <option value="{{ $value }}" {{ old('reset_rule', $selectedSnapshot['reset_rule'] ?? config('product_code.default_reset_rule', 'never')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="effective_from">Effective from</label>
                            <input id="effective_from" name="effective_from" type="datetime-local" value="{{ old('effective_from', ! empty($selectedSnapshot['effective_from']) ? \Illuminate\Support\Carbon::parse($selectedSnapshot['effective_from'])->format('Y-m-d\TH:i') : '') }}">
                        </div>
                        <div>
                            <label for="effective_to">Effective to</label>
                            <input id="effective_to" name="effective_to" type="datetime-local" value="{{ old('effective_to', ! empty($selectedSnapshot['effective_to']) ? \Illuminate\Support\Carbon::parse($selectedSnapshot['effective_to'])->format('Y-m-d\TH:i') : '') }}">
                        </div>

                        <div class="span-2">
                            <label>Validation</label>
                            <div class="pcc-checks">
                                <label><input type="checkbox" name="auto_generate" value="1" {{ old('auto_generate', $selectedSnapshot['auto_generate'] ?? config('product_code.default_auto_generate', true)) ? 'checked' : '' }}> Auto generate</label>
                                <label><input type="checkbox" name="strict_mode" value="1" {{ old('strict_mode', $selectedSnapshot['strict_mode'] ?? config('product_code.default_strict_mode', true)) ? 'checked' : '' }}> Strict mode</label>
                                <label><input type="checkbox" name="skip_empty_components" value="1" {{ old('skip_empty_components', $selectedSnapshot['skip_empty_components'] ?? config('product_code.default_skip_empty_components', false)) ? 'checked' : '' }}> Skip empty values</label>
                                <label><input type="checkbox" name="allow_manual_override" value="1" {{ old('allow_manual_override', $selectedSnapshot['allow_manual_override'] ?? config('product_code.default_allow_manual_override', false)) ? 'checked' : '' }}> Allow manual override</label>
                                <label><input type="checkbox" name="allow_regeneration" value="1" {{ old('allow_regeneration', $selectedSnapshot['allow_regeneration'] ?? config('product_code.default_allow_regeneration', true)) ? 'checked' : '' }}> Allow regeneration</label>
                                <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $selectedSnapshot['is_active'] ?? true) ? 'checked' : '' }}> Active</label>
                            </div>
                        </div>

                        <div class="span-2 pcc-policy-card" id="wizard-existing-policy">
                            <label>Step 5 · What should happen to existing codes?</label>
                            <div class="pcc-policy-options">
                                <label class="pcc-policy-option recommended"><input type="radio" name="existing_record_policy" value="FUTURE_ONLY" {{ old('existing_record_policy', 'FUTURE_ONLY') === 'FUTURE_ONLY' ? 'checked' : '' }}><strong>Keep existing codes</strong><small>Recommended. 0 existing records change; future records use this format.</small></label>
                                <label class="pcc-policy-option"><input type="radio" name="existing_record_policy" value="UPDATE_ALL" {{ old('existing_record_policy') === 'UPDATE_ALL' ? 'checked' : '' }}><strong>Update all</strong><small>{{ number_format($existingRecordCount ?? 0) }} eligible record(s) are reviewed in a dry run.</small></label>
                                <label class="pcc-policy-option"><input type="radio" name="existing_record_policy" value="UPDATE_SELECTED" {{ old('existing_record_policy') === 'UPDATE_SELECTED' ? 'checked' : '' }}><strong>Update selected</strong><small>Choose from {{ number_format($existingRecordCount ?? 0) }} record(s) in the dry-run preview.</small></label>
                            </div>
                            <p class="pcc-policy-help"><i class="icon-lock"></i> Saving never rewrites old codes immediately. Update options always open a dry-run preview first.</p>
                            <div class="pcc-step-actions"><button type="button" class="btn" data-wizard-back>Back</button><div class="right"><button type="button" class="btn btn-primary" data-wizard-next>Test this format <i class="icon-arrow-right"></i></button></div></div>
                            <div style="display:none"><select id="existing_record_policy_legacy" name="existing_record_policy_legacy">
                                <option value="FUTURE_ONLY" selected>Future records only — Recommended</option>
                                <option value="UPDATE_ALL">Preview and update all eligible existing records</option>
                                <option value="UPDATE_SELECTED">Preview and update selected existing records</option>
                            </select>
                            <div class="pcc-small">Saving never rewrites existing codes immediately. Existing-record modes open a dry-run preview first.</div>
                        </div>
                        </div>

                        <div class="span-2" id="wizard-advanced-components">
                            <label>Available component types</label>
                            <div class="pcc-token-list">
                                @foreach($wizardComponentLabels as $key => $label)
                                    <span class="pcc-token">{{ $label }}</span>
                                @endforeach
                            </div>
                            <div class="pcc-help">Arrange the rows below in the exact order you want the code to render. Add a Sequence component only when this code type needs a running number.</div>
                        </div>
                    </div>

                    <div class="pcc-section" id="wizard-format-builder">
                        <div class="pcc-section-title"><h3>Step 3 · Build the code pattern</h3><small>Keep one Sequence component. The row order becomes the final code order.</small></div>
                        <div class="pcc-mode-switch"><button type="button" class="active" data-builder-mode="simple">Simple mode</button><button type="button" data-builder-mode="advanced">Advanced mode</button></div>
                        <div id="pcc-simple-builder"><p class="pcc-small">Use these common controls first. Switch to Advanced only for special tokens or a custom component order.</p><div class="pcc-simple-grid"><label>Prefix<input id="simple-prefix" type="text" maxlength="255" value="{{ $simplePrefix }}" placeholder="Example: CAT"></label><label>Name code<select id="simple-name-code"><option value="1" {{ $simpleHasNameCode ? 'selected' : '' }}>Include name code</option><option value="0" {{ ! $simpleHasNameCode ? 'selected' : '' }}>Do not include</option></select></label><label>Numeric sequence<select id="simple-sequence"><option value="1" {{ $simpleHasSequence ? 'selected' : '' }}>Include sequence</option><option value="0" {{ ! $simpleHasSequence ? 'selected' : '' }}>Do not include</option></select></label></div></div>
                        <div id="pcc-advanced-builder" class="pcc-hidden"><div class="pcc-small">Add, remove, or move rows to build the template. The position field controls the final order.</div>
                        <div class="pcc-actions">
                            <button type="button" class="btn btn-small" id="add-component-row"><i class="icon-plus"></i> Add component</button>
                        </div>

                        <div class="pcc-table-wrap">
                            <table class="table table-bordered table-striped pcc-table">
                                <thead>
                                    <tr>
                                        <th style="width:190px">Component</th>
                                        <th style="width:85px">Position</th>
                                        <th style="width:180px">Static value</th>
                                        <th>Format options</th>
                                        <th style="width:90px">Required</th>
                                        <th style="width:150px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="component-rows">
                                    @foreach($componentRows as $index => $component)
                                        <tr data-component-row data-component-index="{{ $index }}">
                                            <td>
                                                <select name="components[{{ $index }}][component_type]" required>
                                                    @foreach($componentLabels as $value => $label)
                                                        @if(isset($wizardComponentLabels[$value]) || ($component['component_type'] ?? 'sequence') === $value)
                                                            <option value="{{ $value }}" {{ ($component['component_type'] ?? 'sequence') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" name="components[{{ $index }}][position]" min="1" max="100" value="{{ $component['position'] ?? ($index + 1) }}" required></td>
                                            <td><input type="text" name="components[{{ $index }}][static_value]" maxlength="255" value="{{ $component['static_value'] ?? '' }}" placeholder="Optional"></td>
                                            <td><textarea name="components[{{ $index }}][format_options]" rows="3" placeholder='{"pad": 6, "uppercase": true}'>{{ $component['format_options'] ?? '' }}</textarea></td>
                                            <td class="text-center">
                                                <label class="ch-check" style="margin:0"><input type="checkbox" name="components[{{ $index }}][is_required]" value="1" {{ ! empty($component['is_required']) ? 'checked' : '' }}> Yes</label>
                                            </td>
                                            <td>
                                                <div class="pcc-row-tools">
                                                    <button type="button" class="btn btn-mini" data-move-up><i class="icon-arrow-up"></i></button>
                                                    <button type="button" class="btn btn-mini" data-move-down><i class="icon-arrow-down"></i></button>
                                                    <button type="button" class="btn btn-mini btn-danger" data-remove-row><i class="icon-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        </div>
                        <div class="pcc-step-actions"><button type="button" class="btn" data-wizard-back>Back</button><div class="right"><button type="button" class="btn btn-primary" data-wizard-next>Continue to existing codes <i class="icon-arrow-right"></i></button></div></div>
                    </div>

                    <div class="pcc-sticky-actions pcc-hidden" id="wizard-original-actions">
                    <div class="pcc-actions">
                        <button type="submit" class="btn btn-primary"><i class="icon-save"></i> Save Configuration</button>
                        <button type="button" class="btn btn-warning" id="pcc-preview-button"><i class="icon-play"></i> Test Generator</button>
                        <button type="reset" class="btn">Reset</button>
                        @if($selectedConfigurationId)
                            <button type="submit" class="btn btn-danger" form="delete-product-code-configuration" onclick="return confirm('Move this product code configuration to the Recycle Bin?')"><i class="icon-trash"></i> Delete Configuration</button>
                        @endif
                    </div>
                    <div class="pcc-help"><strong>Step 4 · Test and save:</strong> Test Generator never consumes a sequence number. Save activates this format for future generated codes.</div>
                    </div>
                    <div class="pcc-safety"><i class="icon-lock"></i><span><strong>Safety check:</strong> Preview the draft before activating it. Existing generated codes are not rewritten, but new codes will follow the active pattern.</span></div>
                </form>
                @if($selectedConfigurationId)
                    <form id="delete-product-code-configuration" method="post" action="{{ route('product-code-configuration.destroy', $selectedConfigurationId) }}" style="display:none">@csrf @method('DELETE')</form>
                @endif
            </div>
        </section>

        <aside class="pcc-card pcc-preview-panel" id="wizard-preview-panel">
            <div class="pcc-head">
                <h2>Preview &amp; Reference</h2>
                <p>See the current template, test draft values, and scan the token list before saving.</p>
            </div>
            <div class="pcc-body">
                <div class="pcc-preview">
                    <strong>Generated preview</strong>
                    <code id="pcc-preview-code">Generate a preview to see the code here.</code>
                    <small id="pcc-preview-status">Current template loaded from {{ $selectedSnapshot['name'] ?? config('product_code.default_name', 'Default Product Code') }}.</small>
                    <div class="pcc-actions"><button type="button" class="btn btn-warning" id="pcc-wizard-preview-button"><i class="icon-play"></i> Test generated code</button></div>
                </div>

                <div class="pcc-compare" aria-label="Current and proposed code comparison">
                    <div><span>Current</span><code id="pcc-current-preview-code">{{ ! empty($currentCodeSample) && $currentCodeSample->first() ? $currentCodeSample->first()->code : 'No current code' }}</code></div>
                    <div><span>Proposed</span><code id="pcc-proposed-preview-code">Test the draft to generate a proposed code.</code></div>
                </div>
                <div class="pcc-impact" id="pcc-impact-summary">Existing-code impact: keep existing codes selected — 0 records will change.</div>

                <div class="pcc-card" style="box-shadow:none;margin:0 0 14px">
                    <div class="pcc-head" style="border-bottom:0;padding:0 0 10px">
                        <h2 style="font-size:16px">Preview Context</h2>
                        <p>These values are sent to the generator when you test the draft.</p>
                    </div>
                    <div class="pcc-grid-form">
                        <div>
                            <label for="preview-category_id">Category</label>
                            <select id="preview-category_id" name="category_id" form="product-code-configuration-form">
                                <option value="">Choose category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}" {{ (string) $previewDefaults['category_id'] === (string) $category->category_id ? 'selected' : '' }}>{{ $category->category_name }}{{ $category->category_code ? ' - '.$category->category_code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="preview-subcategory_id">Subcategory</label>
                            <select id="preview-subcategory_id" name="subcategory_id" form="product-code-configuration-form">
                                <option value="">Choose subcategory</option>
                                @foreach($subcategories as $subcategory)
                                    <option value="{{ $subcategory->sub_category_id }}" {{ (string) $previewDefaults['subcategory_id'] === (string) $subcategory->sub_category_id ? 'selected' : '' }}>{{ $subcategory->sub_category_name }}{{ $subcategory->subcategory_code ? ' - '.$subcategory->subcategory_code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="preview-manufacturer_id">Brand</label>
                            <select id="preview-manufacturer_id" name="manufacturer_id" form="product-code-configuration-form">
                                <option value="">Choose brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->manufacturer_id }}" {{ (string) $previewDefaults['manufacturer_id'] === (string) $brand->manufacturer_id ? 'selected' : '' }}>{{ $brand->manufacturer_name }}{{ $brand->brand_code ? ' - '.$brand->brand_code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="preview-series_id">Series</label>
                            <select id="preview-series_id" name="series_id" form="product-code-configuration-form">
                                <option value="">Choose series</option>
                                @foreach($series as $productSeries)
                                    <option value="{{ $productSeries->id }}" {{ (string) $previewDefaults['series_id'] === (string) $productSeries->id ? 'selected' : '' }}>{{ $productSeries->name }}{{ $productSeries->series_code ? ' - '.$productSeries->series_code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="preview-variant_code">Variant code</label>
                            <input id="preview-variant_code" name="variant_code" type="text" maxlength="255" value="{{ $previewDefaults['variant_code'] }}" form="product-code-configuration-form">
                        </div>
                        <div>
                            <label for="preview-product_type_code">Product type</label>
                            <input id="preview-product_type_code" name="product_type_code" type="text" maxlength="255" value="{{ $previewDefaults['product_type_code'] }}" form="product-code-configuration-form">
                        </div>
                        <div>
                            <label for="preview-custom_prefix">Custom prefix</label>
                            <input id="preview-custom_prefix" name="custom_prefix" type="text" maxlength="255" value="{{ $previewDefaults['custom_prefix'] }}" form="product-code-configuration-form">
                        </div>
                        <div>
                            <label for="preview-custom_suffix">Custom suffix</label>
                            <input id="preview-custom_suffix" name="custom_suffix" type="text" maxlength="255" value="{{ $previewDefaults['custom_suffix'] }}" form="product-code-configuration-form">
                        </div>
                    </div>
                </div>

                <div class="pcc-note">
                    <strong>Current configuration summary</strong>
                    <div class="pcc-small">
                        Auto generate: {{ ! empty($selectedSnapshot['auto_generate']) ? 'Enabled' : 'Disabled' }}<br>
                        Manual override: {{ ! empty($selectedSnapshot['allow_manual_override']) ? 'Allowed' : 'Blocked' }}<br>
                        Regeneration: {{ ! empty($selectedSnapshot['allow_regeneration']) ? 'Allowed' : 'Blocked' }}<br>
                        Strict mode: {{ ! empty($selectedSnapshot['strict_mode']) ? 'Enabled' : 'Disabled' }}<br>
                        Skip empty values: {{ ! empty($selectedSnapshot['skip_empty_components']) ? 'Enabled' : 'Disabled' }}<br>
                        Separator: {{ $separatorMode === '__custom__' ? ($customSeparatorValue !== '' ? $customSeparatorValue : 'Custom / empty') : ($separatorOptions[$separatorMode] ?? $separatorMode) }}
                    </div>
                </div>

                <div id="wizard-token-reference" class="pcc-hidden">
                    <h3 style="margin:0 0 10px;font-size:16px;color:#123f61">Token Reference</h3>
                    <div class="pcc-token-list">
                        @foreach($wizardComponentLabels as $label)
                            <span class="pcc-token">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="pcc-step-actions"><button type="button" class="btn" data-wizard-back>Back to existing codes</button><div class="right"><button type="button" class="btn btn-primary" data-wizard-next>Review and save <i class="icon-arrow-right"></i></button></div></div>
            </div>
        </aside>
    </div>

    <section class="pcc-card pcc-wizard-panel" id="wizard-review-summary">
        <div class="pcc-head"><h2>Step 6 · Review and save</h2><p>Confirm the format and existing-code policy before making this configuration active.</p></div>
        <div class="pcc-body">
            <div class="pcc-current-grid">
                <div class="pcc-current-metric"><span>Code type</span><strong>{{ $selectedCodeTypeLabel }}</strong></div>
                <div class="pcc-current-metric"><span>Current format</span><strong>{{ $selectedSnapshot['template'] ?? 'Not configured' }}</strong></div>
                <div class="pcc-current-metric"><span>New format</span><strong id="wizard-review-template">Use Test &amp; Preview to confirm.</strong></div>
                <div class="pcc-current-metric"><span>Existing-code policy</span><strong id="wizard-review-policy">Keep existing codes</strong></div>
            </div>
            <div class="pcc-impact" id="wizard-review-impact">Existing codes will remain unchanged.</div>
            <div class="pcc-safety"><i class="icon-lock"></i><span>For existing-code updates, Save opens the impact preview. No existing records change until the preview is reviewed and a strong confirmation is entered.</span></div>
            <div class="pcc-step-actions"><div><button type="button" class="btn" data-wizard-back>Back</button><button type="button" class="btn" data-reset-draft>Discard draft</button></div><div class="right"><button type="submit" class="btn btn-primary" form="product-code-configuration-form" id="wizard-save-button"><i class="icon-save"></i> Save Configuration</button></div></div>
            @if($selectedConfigurationId)<div class="pcc-danger-zone"><h3>Danger zone</h3><p>Deleting this configuration can affect future code generation. This does not rewrite historical codes.</p><button type="submit" class="btn btn-danger" form="delete-product-code-configuration" onclick="return confirm('Move this configuration to the Recycle Bin?')">Delete Configuration</button></div>@endif
        </div>
    </section>

    <nav class="pcc-utility-tabs" aria-label="Configuration utilities">
        <button type="button" class="pcc-utility-tab active" data-utility-go="configure">Configure</button>
        <button type="button" class="pcc-utility-tab" data-utility-go="sequences">Sequences</button>
        <button type="button" class="pcc-utility-tab" data-utility-go="generated-history">Code History</button>
        <button type="button" class="pcc-utility-tab" data-utility-go="configuration-history">Configuration History</button>
    </nav>

    <section class="pcc-section pcc-utility-panel" id="utility-sequences">
        <h3>Sequence Management - {{ $selectedCodeTypeLabel }}</h3>
        <div class="pcc-small">View the live counters for this code type, correct the next generated number, or reset a scope with an audit reason.</div>
        <div class="pcc-table-wrap" style="margin-top:12px">
            <table class="table table-striped table-bordered pcc-table">
                <thead>
                    <tr>
                        <th>Scope</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th>Brand</th>
                        <th>Series</th>
                        <th>Period</th>
                        <th>Current</th>
                        <th>Next</th>
                        <th>Updated</th>
                        <th>Adjust</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sequences as $sequence)
                        <tr>
                            <td>{{ $sequenceScopes[$sequence->sequence_scope] ?? $sequence->sequence_scope }}</td>
                            <td>{{ $companyLookup[$sequence->company_id] ?? 'All' }}</td>
                            <td>{{ $branchLookup[$sequence->branch_id] ?? 'All' }}</td>
                            <td>{{ $categoryLookup[$sequence->category_id] ?? 'All' }}</td>
                            <td>{{ $subcategoryLookup[$sequence->subcategory_id] ?? 'All' }}</td>
                            <td>{{ $brandLookup[$sequence->brand_id] ?? 'All' }}</td>
                            <td>{{ $seriesLookup[$sequence->series_id] ?? 'All' }}</td>
                            <td>{{ $sequence->period_key }}</td>
                            <td>{{ (int) $sequence->last_number }}</td>
                            <td>{{ (int) $sequence->last_number + 1 }}</td>
                            <td>{{ optional($sequence->updated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                            <td>
                                <details>
                                    <summary>Adjust</summary>
                                    <form method="post" action="{{ url('/product-code-configuration/'.$sequence->id.'/reset-sequence') }}" style="margin-top:10px">
                                        @csrf
                                        <label>Next generated number</label>
                                        <input type="number" name="next_number" min="1" max="999999999" value="{{ (int) $sequence->last_number + 1 }}" required>
                                        <label>Reason</label>
                                        <textarea name="reason" rows="3" required placeholder="Why is this correction or reset needed?"></textarea>
                                        <button type="submit" class="btn btn-danger">Apply</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12">No sequence counters have been created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="pcc-history-grid pcc-section">
        <section class="pcc-card pcc-utility-panel" id="utility-generated-history">
            <div class="pcc-head">
                <h2>Generated Code History</h2>
                <p>Track generated code changes for the selected type over time.</p>
            </div>
            <div class="pcc-body">
                <div class="pcc-table-wrap">
                    <table class="table table-striped table-bordered pcc-table" style="min-width:900px">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Old code</th>
                                <th>New code</th>
                                <th>Reason</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productCodeHistories as $history)
                                <tr>
                                    <td>{{ optional($history->product)->product_name ?: 'Product #'.$history->product_id }}</td>
                                    <td>{{ $history->old_code ?: '-' }}</td>
                                    <td>{{ $history->new_code }}</td>
                                    <td>{{ $history->reason ?: '-' }}</td>
                                    <td>{{ optional($history->changed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No product code history yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="pcc-card pcc-utility-panel" id="utility-configuration-history">
            <div class="pcc-head">
                <h2>{{ $selectedCodeTypeLabel }} Configuration History</h2>
                <p>See how the template and settings changed for this code type.</p>
            </div>
            <div class="pcc-body">
                <div class="pcc-table-wrap">
                    <table class="table table-striped table-bordered pcc-table" style="min-width:900px">
                        <thead>
                            <tr>
                                <th>Configuration</th>
                                <th>Old template</th>
                                <th>New template</th>
                                <th>Changed by</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($configurationHistories as $history)
                                <tr>
                                    <td>{{ optional($history->configuration)->name ?: 'Configuration #'.$history->configuration_id }}</td>
                                    <td><pre class="pcc-pre">{{ $history->old_template ?: '-' }}</pre></td>
                                    <td><pre class="pcc-pre">{{ $history->new_template ?: '-' }}</pre></td>
                                    <td>{{ $history->changed_by ? 'Admin #'.$history->changed_by : '-' }}</td>
                                    <td>{{ optional($history->changed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No configuration history yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>

<template id="component-row-template">
    <tr data-component-row data-component-index="__INDEX__">
        <td>
            <select name="components[__INDEX__][component_type]" required>
                @foreach($wizardComponentLabels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="components[__INDEX__][position]" min="1" max="100" value="1" required></td>
        <td><input type="text" name="components[__INDEX__][static_value]" maxlength="255" value="" placeholder="Optional"></td>
        <td><textarea name="components[__INDEX__][format_options]" rows="3" placeholder='{"pad": 6, "uppercase": true}'></textarea></td>
        <td class="text-center"><label class="ch-check" style="margin:0"><input type="checkbox" name="components[__INDEX__][is_required]" value="1" checked> Yes</label></td>
        <td>
            <div class="pcc-row-tools">
                <button type="button" class="btn btn-mini" data-move-up><i class="icon-arrow-up"></i></button>
                <button type="button" class="btn btn-mini" data-move-down><i class="icon-arrow-down"></i></button>
                <button type="button" class="btn btn-mini btn-danger" data-remove-row><i class="icon-trash"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('product-code-configuration-form');
    var previewButton = document.getElementById('pcc-preview-button');
    var wizardPreviewButton = document.getElementById('pcc-wizard-preview-button');
    var previewCode = document.getElementById('pcc-preview-code');
    var previewStatus = document.getElementById('pcc-preview-status');
    var separatorMode = document.getElementById('separator_mode');
    var customSeparator = document.getElementById('custom_separator');
    var separatorField = document.getElementById('separator');
    var rowsBody = document.getElementById('component-rows');
    var template = document.getElementById('component-row-template');
    var addButton = document.getElementById('add-component-row');
    var nextIndex = rowsBody.querySelectorAll('[data-component-row]').length;
    var previewUrl = @json(url('/product-code-configuration/preview'));
    var csrfToken = @json(csrf_token());
    var formIsDirty = false;
    var wizardStep = 1;
    var existingRecordCount = @json((int) ($existingRecordCount ?? 0));
    var wizardTabs = Array.prototype.slice.call(document.querySelectorAll('[data-wizard-go]'));
    var flowSteps = Array.prototype.slice.call(document.querySelectorAll('[data-flow-step]'));
    var editorShell = document.getElementById('wizard-editor-shell');
    var currentSelector = document.getElementById('wizard-current-selector');
    var currentSummary = document.getElementById('wizard-current-summary');
    var codeTypes = document.getElementById('wizard-code-types');
    var introPanel = document.getElementById('wizard-intro-panel');
    var policyPanel = document.getElementById('wizard-existing-policy');
    var formatBuilder = document.getElementById('wizard-format-builder');
    var previewPanel = document.getElementById('wizard-preview-panel');
    var reviewPanel = document.getElementById('wizard-review-summary');
    var settingsGrid = form ? form.querySelector('.pcc-grid-form') : null;
    var advancedTokens = document.getElementById('wizard-advanced-components');
    var simpleBuilder = document.getElementById('pcc-simple-builder');
    var advancedBuilder = document.getElementById('pcc-advanced-builder');
    var tokenReference = document.getElementById('wizard-token-reference');
    var simplePrefix = document.getElementById('simple-prefix');
    var simpleNameCode = document.getElementById('simple-name-code');
    var simpleSequence = document.getElementById('simple-sequence');
    var reviewTemplate = document.getElementById('wizard-review-template');
    var reviewPolicy = document.getElementById('wizard-review-policy');
    var reviewImpact = document.getElementById('wizard-review-impact');
    var wizardSaveButton = document.getElementById('wizard-save-button');
    var proposedPreview = document.getElementById('pcc-proposed-preview-code');
    var impactSummary = document.getElementById('pcc-impact-summary');
    var previewTimer = null;

    function syncSeparator() {
        var value = separatorMode.value === '__custom__' ? customSeparator.value : separatorMode.value;
        separatorField.value = value;
        customSeparator.style.display = separatorMode.value === '__custom__' ? 'block' : 'none';
    }

    function setVisible(element, visible) {
        if (! element) return;
        element.classList.toggle('pcc-hidden', !visible);
    }

    function selectedPolicy() {
        var selected = form.querySelector('input[name="existing_record_policy"]:checked');
        return selected ? selected.value : 'FUTURE_ONLY';
    }

    function updateReview() {
        var separator = separatorField.value || '';
        var componentNames = Array.prototype.slice.call(rowsBody.querySelectorAll('[data-component-row] select[name$="[component_type]"]')).map(function (field) {
            return field.options[field.selectedIndex].text;
        });
        if (reviewTemplate) reviewTemplate.textContent = componentNames.length ? componentNames.join(separator || ' ') : 'No components selected';
        var policy = selectedPolicy();
        var policyLabels = { FUTURE_ONLY: 'Keep existing codes', UPDATE_ALL: 'Update all eligible existing codes', UPDATE_SELECTED: 'Update selected existing codes' };
        if (reviewPolicy) reviewPolicy.textContent = policyLabels[policy] || policy;
        var impact = policy === 'UPDATE_ALL'
            ? existingRecordCount+' existing record(s) will be checked in a dry run before any update.'
            : (policy === 'UPDATE_SELECTED'
                ? 'You will choose from '+existingRecordCount+' existing record(s) in the dry-run preview.'
                : 'Existing codes will remain unchanged; only future records use this format.');
        if (impactSummary) impactSummary.textContent = 'Existing-code impact: '+impact;
        if (reviewImpact) reviewImpact.textContent = impact;
        if (wizardSaveButton) {
            wizardSaveButton.textContent = policy === 'UPDATE_ALL' ? 'Save & update '+existingRecordCount+' existing codes' : (policy === 'UPDATE_SELECTED' ? 'Save & choose existing codes' : 'Save Configuration');
        }
    }

    function setSettingsStep(step) {
        if (! settingsGrid) return;
        Array.prototype.slice.call(settingsGrid.children).forEach(function (child) {
            if (child === policyPanel) setVisible(child, step === 4);
            else if (child === advancedTokens) setVisible(child, step === 3 && advancedBuilder && !advancedBuilder.classList.contains('pcc-hidden'));
            else setVisible(child, step === 3);
        });
    }

    function showWizardStep(step) {
        wizardStep = Math.max(1, Math.min(6, step));
        wizardTabs.forEach(function (tab) { tab.classList.toggle('active', Number(tab.getAttribute('data-wizard-go')) === wizardStep); });
        flowSteps.forEach(function (flowStep) {
            var flowNumber = Number(flowStep.getAttribute('data-flow-step'));
            flowStep.classList.toggle('active', flowNumber === wizardStep);
            flowStep.classList.toggle('completed', flowNumber < wizardStep);
        });
        setVisible(introPanel, wizardStep === 1);
        setVisible(codeTypes, wizardStep === 1);
        setVisible(currentSelector, wizardStep === 2);
        setVisible(currentSummary, wizardStep === 2);
        if (currentSummary) currentSummary.classList.toggle('active', wizardStep === 2);
        setVisible(editorShell, wizardStep === 3 || wizardStep === 4 || wizardStep === 5);
        setSettingsStep(wizardStep);
        setVisible(formatBuilder, wizardStep === 3);
        setVisible(previewPanel, wizardStep === 3 || wizardStep === 5);
        setVisible(reviewPanel, wizardStep === 6);
        if (reviewPanel) reviewPanel.classList.toggle('active', wizardStep === 6);
        updateReview();
        if (wizardStep === 3 || wizardStep === 5) window.setTimeout(runPreview, 0);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function findComponentRow(type) {
        return Array.prototype.slice.call(rowsBody.querySelectorAll('[data-component-row]')).find(function (row) {
            var field = row.querySelector('select[name$="[component_type]"]');
            return field && field.value === type;
        });
    }

    function syncSimpleBuilder() {
        if (! simpleBuilder || simpleBuilder.classList.contains('pcc-hidden')) return;
        var prefixRow = findComponentRow('prefix');
        if (simplePrefix && simplePrefix.value.trim() !== '') {
            if (! prefixRow) prefixRow = addRow({ component_type: 'prefix', position: 1, static_value: simplePrefix.value, format_options: '', is_required: 1 });
            var prefixValue = prefixRow.querySelector('input[name$="[static_value]"]');
            if (prefixValue) prefixValue.value = simplePrefix.value;
        } else if (prefixRow) prefixRow.remove();
        [['name_code', simpleNameCode], ['sequence', simpleSequence]].forEach(function (entry) {
            var row = findComponentRow(entry[0]);
            var enabled = entry[1] && entry[1].value === '1';
            if (enabled && !row) addRow({ component_type: entry[0], position: rowsBody.querySelectorAll('[data-component-row]').length + 1, static_value: '', format_options: '', is_required: 1 });
            if (!enabled && row) row.remove();
        });
        if (! rowsBody.querySelector('[data-component-row]')) addRow({ component_type: 'sequence', position: 1, static_value: '', format_options: '', is_required: 1 });
        renumberRows();
    }

    function validateFormatStep() {
        var rows = Array.prototype.slice.call(rowsBody.querySelectorAll('[data-component-row]'));
        if (!rows.length) { window.alert('Add at least one code component before continuing.'); return false; }
        var seen = {};
        for (var index = 0; index < rows.length; index++) {
            var type = rows[index].querySelector('select[name$="[component_type]"]').value;
            if (type === 'prefix') {
                var value = rows[index].querySelector('input[name$="[static_value]"]').value.trim();
                if (!value) { window.alert('Enter a prefix value or remove the Prefix component.'); return false; }
            }
            if (type !== 'static_text' && seen[type]) { window.alert('Each component can be used once. Remove the duplicate '+type.replace('_', ' ')+' component.'); return false; }
            seen[type] = true;
        }
        return true;
    }

    function setBuilderMode(mode) {
        var advanced = mode === 'advanced';
        setVisible(simpleBuilder, !advanced);
        setVisible(advancedBuilder, advanced);
        setVisible(advancedTokens, advanced && wizardStep === 3);
        setVisible(tokenReference, false);
        Array.prototype.slice.call(document.querySelectorAll('[data-builder-mode]')).forEach(function (button) { button.classList.toggle('active', button.getAttribute('data-builder-mode') === mode); });
        if (!advanced) syncSimpleBuilder();
    }

    function renumberRows() {
        Array.prototype.slice.call(rowsBody.querySelectorAll('[data-component-row]')).forEach(function (row, index) {
            row.querySelector('input[name$="[position]"]').value = index + 1;
        });
    }

    function addRow(values) {
        var rowIndex = nextIndex++;
        var html = template.innerHTML.replace(/__INDEX__/g, rowIndex);
        var wrapper = document.createElement('tbody');
        wrapper.innerHTML = html.trim();
        var row = wrapper.firstElementChild;

        if (values) {
            var typeField = row.querySelector('select[name$="[component_type]"]');
            var positionField = row.querySelector('input[name$="[position]"]');
            var staticField = row.querySelector('input[name$="[static_value]"]');
            var formatField = row.querySelector('textarea[name$="[format_options]"]');
            var requiredField = row.querySelector('input[name$="[is_required]"]');

            if (typeField && values.component_type) typeField.value = values.component_type;
            if (positionField && values.position) positionField.value = values.position;
            if (staticField && values.static_value !== undefined) staticField.value = values.static_value;
            if (formatField && values.format_options !== undefined) formatField.value = values.format_options;
            if (requiredField) requiredField.checked = values.is_required ? true : false;
        }

        rowsBody.appendChild(row);
        renumberRows();
        return row;
    }

    function moveRow(row, direction) {
        if (! row) {
            return;
        }

        var sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
        if (! sibling) {
            return;
        }

        if (direction < 0) {
            rowsBody.insertBefore(row, sibling);
        } else {
            rowsBody.insertBefore(sibling, row);
        }

        renumberRows();
    }

    rowsBody.addEventListener('click', function (event) {
        var row = event.target.closest('[data-component-row]');
        if (! row) {
            return;
        }

        if (event.target.closest('[data-remove-row]')) {
            event.preventDefault();
            row.remove();
            if (! rowsBody.querySelector('[data-component-row]')) {
                addRow({ component_type: 'sequence', position: 1, static_value: '', format_options: '', is_required: 1 });
            }
            renumberRows();
        }

        if (event.target.closest('[data-move-up]')) {
            event.preventDefault();
            moveRow(row, -1);
        }

        if (event.target.closest('[data-move-down]')) {
            event.preventDefault();
            moveRow(row, 1);
        }
    });

    addButton.addEventListener('click', function () {
        addRow({ component_type: 'sequence', position: rowsBody.querySelectorAll('[data-component-row]').length + 1, static_value: '', format_options: '', is_required: 1 });
    });

    separatorMode.addEventListener('change', syncSeparator);
    customSeparator.addEventListener('input', syncSeparator);
    syncSeparator();
    renumberRows();

    if (! rowsBody.querySelector('[data-component-row]')) {
        addRow({ component_type: 'sequence', position: 1, static_value: '', format_options: '', is_required: 1 });
    }

    function runPreview() {
            syncSeparator();
            syncSimpleBuilder();
            var payload = new FormData(form);
            fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: payload,
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (! result.ok) {
                    throw result.data;
                }

                if (previewCode) {
                    previewCode.textContent = result.data.preview || 'No preview available.';
                }
                if (proposedPreview) proposedPreview.textContent = result.data.preview || 'No preview available.';
                if (previewStatus) {
                    previewStatus.textContent = 'Preview updated from the current draft configuration.';
                }
            })
            .catch(function (error) {
                var message = error && (error.message || error.error);
                if (! message && error && error.errors && error.errors.product_code && error.errors.product_code.length) {
                    message = error.errors.product_code[0];
                }
                var previewMessage = message || 'Select the required fields to generate a preview.';
                if (previewCode) {
                    previewCode.textContent = previewMessage;
                }
                if (proposedPreview) proposedPreview.textContent = 'Preview unavailable until the required context is selected.';
                if (previewStatus) {
                    previewStatus.textContent = message ? 'Preview failed. Fix the preview context and try again.' : 'Preview request failed.';
                }
            });
    }

    if (previewButton) previewButton.addEventListener('click', runPreview);
    if (wizardPreviewButton) wizardPreviewButton.addEventListener('click', runPreview);

    wizardTabs.forEach(function (tab) {
        tab.addEventListener('click', function () { showWizardStep(Number(tab.getAttribute('data-wizard-go'))); });
    });
    Array.prototype.slice.call(document.querySelectorAll('[data-wizard-next]')).forEach(function (button) {
        button.addEventListener('click', function () { syncSimpleBuilder(); if (wizardStep === 3 && !validateFormatStep()) return; showWizardStep(wizardStep + 1); });
    });
    Array.prototype.slice.call(document.querySelectorAll('[data-wizard-back]')).forEach(function (button) {
        button.addEventListener('click', function () { showWizardStep(wizardStep - 1); });
    });
    Array.prototype.slice.call(document.querySelectorAll('[data-builder-mode]')).forEach(function (button) {
        button.addEventListener('click', function () { setBuilderMode(button.getAttribute('data-builder-mode')); });
    });
    function queuePreview() {
        if (wizardStep !== 3 && wizardStep !== 5) return;
        window.clearTimeout(previewTimer);
        previewTimer = window.setTimeout(runPreview, 350);
    }
    [simplePrefix, simpleNameCode, simpleSequence].forEach(function (field) { if (field) field.addEventListener('input', function () { syncSimpleBuilder(); updateReview(); queuePreview(); }); if (field) field.addEventListener('change', function () { syncSimpleBuilder(); updateReview(); queuePreview(); }); });
    Array.prototype.slice.call(form.querySelectorAll('input[name="existing_record_policy"]')).forEach(function (field) { field.addEventListener('change', updateReview); });

    var utilityTabs = Array.prototype.slice.call(document.querySelectorAll('[data-utility-go]'));
    var utilityPanels = Array.prototype.slice.call(document.querySelectorAll('.pcc-utility-panel'));
    utilityTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-utility-go');
            utilityTabs.forEach(function (item) { item.classList.toggle('active', item === tab); });
            utilityPanels.forEach(function (panel) { panel.classList.toggle('active', panel.id === 'utility-'+target); });
            var wizardElements = [introPanel, codeTypes, currentSelector, currentSummary, editorShell, reviewPanel];
            wizardElements.forEach(function (element) { if (element) element.classList.toggle('pcc-hidden', target !== 'configure'); });
            if (target === 'configure') showWizardStep(wizardStep);
        });
    });

    if (codeTypes) {
        Array.prototype.slice.call(codeTypes.querySelectorAll('a')).forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (formIsDirty && ! window.confirm('Changing code type discards the current unsaved draft. Continue?')) event.preventDefault();
            });
        });
    }
    Array.prototype.slice.call(document.querySelectorAll('[data-reset-draft]')).forEach(function (button) {
        button.addEventListener('click', function () {
            if (window.confirm('Discard unsaved draft changes?')) window.location.assign(window.location.href);
        });
    });

    form.addEventListener('submit', function (event) {
        syncSeparator();
        syncSimpleBuilder();
        var activeField = form.querySelector('input[name="is_active"]');
        if (activeField && activeField.checked && ! window.confirm('Make this configuration active for future codes? Existing codes will not change.')) {
            event.preventDefault();
            return;
        }
        formIsDirty = false;
    });

    form.addEventListener('input', function () { formIsDirty = true; });
    form.addEventListener('change', function () { formIsDirty = true; queuePreview(); });
    window.addEventListener('beforeunload', function (event) {
        if (formIsDirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
    setBuilderMode('simple');
    showWizardStep(1);
});
</script>
@endsection
