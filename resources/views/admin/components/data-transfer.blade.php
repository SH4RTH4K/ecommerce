@php
    $transferLabels = [
        'products'=>'Products','categories'=>'Categories','subcategories'=>'Subcategories',
        'manufacturers'=>'Manufacturers','attributes'=>'Catalog Attributes',
        'suppliers'=>'Suppliers','locations'=>'Stock Locations'
    ];
    $transferLabel = $transferLabels[$resource] ?? ucfirst($resource);
@endphp
<style>
.dt-card{margin:14px 0 20px;border:1px solid #d9e5ec;border-radius:10px;background:#fff;box-shadow:0 4px 16px rgba(18,63,97,.07);overflow:hidden}.dt-head{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:16px 19px;background:linear-gradient(120deg,#f4f9fc,#eaf3f8)}.dt-title{display:flex;align-items:center;gap:12px}.dt-title i{display:grid;place-items:center;width:38px;height:38px;border-radius:9px;background:#17618d;color:#fff;font-size:18px}.dt-title h3{margin:0;color:#123f61;font-size:17px}.dt-title p{margin:3px 0 0;color:#6d8290;font-size:12px}.dt-actions{display:flex;gap:8px;flex-wrap:wrap}.dt-actions .btn{margin:0}.dt-body{display:none;padding:18px;border-top:1px solid #dce6ec}.dt-card.open .dt-body{display:block}.dt-grid{display:grid;grid-template-columns:minmax(250px,1fr) 220px auto;gap:14px;align-items:end}.dt-drop{position:relative;min-height:105px;display:grid;place-items:center;text-align:center;padding:18px;border:2px dashed #aac1d0;border-radius:8px;background:#f8fbfd;color:#607887;cursor:pointer}.dt-drop.drag{border-color:#f5821f;background:#fff8f0}.dt-drop input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.dt-drop strong{display:block;color:#194f70}.dt-field label{display:block;font-weight:700;color:#38576a;margin-bottom:6px}.dt-field select{width:100%;height:39px;margin:0}.dt-submit{height:40px}.dt-help{margin:12px 0 0;color:#758995;font-size:12px}.dt-errors{margin:12px 0;padding:12px 15px;max-height:220px;overflow:auto;border-left:4px solid #bd362f;background:#fff1f0}.dt-errors li{margin:4px 0}@media(max-width:900px){.dt-head{align-items:flex-start;flex-direction:column}.dt-grid{grid-template-columns:1fr}.dt-actions{width:100%}}
</style>
<section class="dt-card" data-transfer>
    <header class="dt-head">
        <div class="dt-title"><i class="icon-exchange"></i><div><h3>{{ $transferLabel }} Import &amp; Export</h3><p>CSV templates, transactional validation, and safe create/update matching.</p></div></div>
        <div class="dt-actions">
            <a class="btn btn-small" href="{{ route('admin-data.template',$resource) }}"><i class="icon-download-alt"></i> Template</a>
            <a class="btn btn-small btn-success" href="{{ route('admin-data.export',$resource) }}"><i class="icon-file"></i> Export CSV</a>
            <button class="btn btn-small btn-primary" type="button" data-transfer-toggle><i class="icon-upload icon-white"></i> Import CSV</button>
        </div>
    </header>
    <div class="dt-body">
        <form method="post" enctype="multipart/form-data" action="{{ route('admin-data.import',$resource) }}">{{ csrf_field() }}
            <div class="dt-grid">
                <label class="dt-drop"><input type="file" name="csv_file" accept=".csv,text/csv" required><span><strong><i class="icon-cloud-upload"></i> Drop CSV here or browse</strong><small data-file-name>Maximum 5 MB · UTF-8 CSV recommended</small></span></label>
                <div class="dt-field"><label>Import behavior</label><select name="mode"><option value="upsert">Create new and update matches</option><option value="create">Create new; skip matches</option></select></div>
                <button class="btn btn-primary dt-submit" type="submit"><i class="icon-upload icon-white"></i> Validate &amp; Import</button>
            </div>
            <p class="dt-help"><i class="icon-info-sign"></i> The entire file is validated in one transaction. If any row fails, nothing is saved and row-specific errors are shown.</p>
        </form>
    </div>
</section>
@if(session('import_errors'))<div class="dt-errors"><strong>Import errors</strong><ol>@foreach(session('import_errors') as $importError)<li>{{ $importError }}</li>@endforeach</ol></div>@endif
<script>
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('[data-transfer]').forEach(function(card){var toggle=card.querySelector('[data-transfer-toggle]'),input=card.querySelector('input[type=file]'),drop=card.querySelector('.dt-drop'),name=card.querySelector('[data-file-name]');toggle.addEventListener('click',function(){card.classList.toggle('open');if(card.classList.contains('open'))input.focus();});input.addEventListener('change',function(){name.textContent=input.files.length?input.files[0].name:'Maximum 5 MB · UTF-8 CSV recommended';});['dragenter','dragover'].forEach(function(event){drop.addEventListener(event,function(){drop.classList.add('drag');});});['dragleave','drop'].forEach(function(event){drop.addEventListener(event,function(){drop.classList.remove('drag');});});});});
</script>
