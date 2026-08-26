@extends('admin.admin-master')
@section('admin_main_content')
<div id="content" class="span10">
    <ul class="breadcrumb"><li><i class="icon-home"></i> <a href="{{ url('/dashboard') }}">Home</a> <i class="icon-angle-right"></i></li><li>PC Builder Rules</li></ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <div class="box"><div class="box-header"><h2><i class="halflings-icon wrench"></i><span class="break"></span>PC Builder Configuration</h2></div><div class="box-content">
        <p class="muted">Map each slot to the exact product subcategory when possible. In this catalog, PC parts are subcategories under <strong>Component</strong>; this prevents Desktop products from appearing as Processors.</p>
        <form method="post" action="{{ url('/pc-builder-settings') }}" enctype="multipart/form-data">{{ csrf_field() }}
            <h3>Builder slots</h3>
            <p class="muted">Choose a built-in fallback icon or upload a custom local icon for each builder slot.</p>
            <table class="table table-bordered"><thead><tr><th>Slot</th><th>Preview</th><th>Upload custom icon</th><th>Remove</th></tr></thead><tbody>
            @foreach($slots as $slot)
                <tr><td><strong>{{ $slot['label'] }}</strong></td><td><span data-slot-icon-preview="{{ $slot['key'] }}" style="width:38px;height:38px;display:inline-grid;place-items:center;border:1px solid #d9e3e9;border-radius:7px;background:#f8fafb;color:#0b3d62;font-size:20px;overflow:hidden">@if(!empty($slot['icon_image']))<img src="{{ asset($slot['icon_image']) }}" alt="" style="width:100%;height:100%;object-fit:contain">@else<i class="fa fa-{{ $slot['icon'] }}" aria-hidden="true"></i>@endif</span></td><td><input type="file" name="slot_icons[{{ $slot['key'] }}]" accept="image/png,image/jpeg,image/webp" data-slot-icon-file="{{ $slot['key'] }}" title="Upload custom icon for {{ $slot['label'] }}"><small style="display:block;color:#71828d">PNG, JPG or WebP · max 1 MB</small></td><td>@if(!empty($slot['icon_image']))<button type="submit" class="btn btn-mini btn-danger" formaction="{{ url('/pc-builder-settings/remove-icon/'.$slot['key']) }}" formmethod="post" onclick="return confirm('Remove this custom slot image and use the built-in icon?')"><i class="halflings-icon white trash"></i> Remove image</button>@else<span class="muted">Using fallback</span>@endif</td></tr>
            @endforeach
            </tbody></table>
            <table class="table table-bordered"><thead><tr><th>Slot</th><th>Label</th><th>Category</th><th>Exact subcategory</th><th>Icon</th><th>Required</th><th>Available</th></tr></thead><tbody>
            @foreach($slots as $slot)
                <tr><td><strong>{{ $slot['key'] }}</strong></td><td><input name="slots[{{ $slot['key'] }}][label]" value="{{ $slot['label'] }}" class="span2"></td>
                <td><select name="slots[{{ $slot['key'] }}][category_id]" class="span2"><option value="">Default: {{ $slot['category'] }}</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" {{ (int)($slot['category_id']??0)===(int)$category->category_id?'selected':'' }}>{{ $category->category_name }}</option>@endforeach</select></td>
                <td><select name="slots[{{ $slot['key'] }}][sub_category_id]" class="span2"><option value="">Any subcategory</option>@foreach($subcategories as $sub)<option value="{{ $sub->sub_category_id }}" {{ (int)($slot['sub_category_id']??0)===(int)$sub->sub_category_id?'selected':'' }}>{{ $sub->sub_category_name }} ({{ $sub->category_id }})</option>@endforeach</select></td>
                <td><select name="slots[{{ $slot['key'] }}][icon]"><option value="cog" {{ $slot['icon']==='cog'?'selected':'' }}>Processor</option><option value="sitemap" {{ $slot['icon']==='sitemap'?'selected':'' }}>Motherboard</option><option value="list" {{ $slot['icon']==='list'?'selected':'' }}>Memory</option><option value="picture-o" {{ $slot['icon']==='picture-o'?'selected':'' }}>Graphics</option><option value="hdd-o" {{ $slot['icon']==='hdd-o'?'selected':'' }}>Storage</option><option value="bolt" {{ $slot['icon']==='bolt'?'selected':'' }}>Power</option><option value="archive" {{ $slot['icon']==='archive'?'selected':'' }}>Case</option><option value="refresh" {{ $slot['icon']==='refresh'?'selected':'' }}>Cooling</option><option value="desktop" {{ $slot['icon']==='desktop'?'selected':'' }}>Monitor</option></select></td>
                <td><label><input type="checkbox" name="slots[{{ $slot['key'] }}][required]" value="1" {{ !empty($slot['required'])?'checked':'' }}> Required</label></td><td><span class="label {{ $slot['available_count']?'label-success':'label-important' }}">{{ $slot['available_count'] }}</span></td></tr>
            @endforeach
            </tbody></table>
            <p class="alert alert-info"><strong>Setup status:</strong> a zero available count means the category/subcategory has no published, in-stock products yet. Import products and publish them before testing the chooser.</p>
            <h3>Compatibility rules</h3><p class="muted">Use the exact attribute slug or name from Product Attributes. Example: compare <code>generation</code> on Processor and Motherboard, plus <code>socket</code>.</p>
            <table class="table table-bordered"><thead><tr><th>Rule name</th><th>First component attribute</th><th>Must equal second component attribute</th><th>Message</th><th>On</th></tr></thead><tbody>
            @for($i=0;$i<max(5,count($rules));$i++) @php($rule=$rules[$i]??[])<tr><td><input name="rules[{{ $i }}][name]" value="{{ $rule['name']??'' }}" placeholder="Generation match" class="span2"></td><td><select name="rules[{{ $i }}][left_slot]">@foreach($slots as $item)<option value="{{ $item['key'] }}" {{ ($rule['left_slot']??'')===$item['key']?'selected':'' }}>{{ $item['label'] }}</option>@endforeach</select><input name="rules[{{ $i }}][left_attribute]" value="{{ $rule['left_attribute']??'' }}" placeholder="generation" class="span2"></td><td><select name="rules[{{ $i }}][right_slot]">@foreach($slots as $item)<option value="{{ $item['key'] }}" {{ ($rule['right_slot']??'')===$item['key']?'selected':'' }}>{{ $item['label'] }}</option>@endforeach</select><input name="rules[{{ $i }}][right_attribute]" value="{{ $rule['right_attribute']??'' }}" placeholder="generation" class="span2"></td><td><input name="rules[{{ $i }}][message]" value="{{ $rule['message']??'' }}" placeholder="Components are not compatible." class="span3"></td><td><input type="checkbox" name="rules[{{ $i }}][enabled]" value="1" {{ array_key_exists('enabled',$rule)?(!empty($rule['enabled'])?'checked':''):'checked' }}></td></tr>@endfor
            </tbody></table>
            <button class="btn btn-primary" type="submit"><i class="halflings-icon white ok"></i> Save PC Builder settings</button>
        </form>
    </div></div>
</div>
<script>
(function () {
    document.querySelectorAll('[data-slot-icon-file]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0], preview = document.querySelector('[data-slot-icon-preview="' + input.dataset.slotIconFile + '"]');
            if (!file || !preview) return;
            var image = document.createElement('img'); image.alt = 'New slot icon preview'; image.style.cssText = 'width:100%;height:100%;object-fit:contain'; image.src = URL.createObjectURL(file); image.onload = function () { URL.revokeObjectURL(image.src); }; preview.innerHTML = ''; preview.appendChild(image);
        });
    });
}());
</script>
@endsection
