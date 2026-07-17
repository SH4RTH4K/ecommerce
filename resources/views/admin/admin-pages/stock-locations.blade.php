@extends('admin.admin-master')
@section('admin_main_content')
@php $formLocation=$editLocation; @endphp
<div id="content" class="span10">
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>Locations &amp; Transfers</li></ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error"><strong>Please correct the location details:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @include('admin.components.data-transfer',['resource'=>'locations'])

    <div class="row-fluid">
        <div class="box span5">
            <div class="box-header"><h2><i class="icon-map-marker"></i> {{ $formLocation ? 'Edit inventory location' : 'Add inventory location' }}</h2></div>
            <div class="box-content">
                <form method="post" action="{{ $formLocation ? url('/stock-locations/'.$formLocation->id) : url('/stock-locations') }}">
                    {{ csrf_field() }}
                    <div class="row-fluid"><div class="span8"><label for="location-name">Location name *</label><input id="location-name" class="span12" name="name" maxlength="150" placeholder="Uttara Branch" value="{{ old('name',$formLocation?$formLocation->name:'') }}" required></div><div class="span4"><label for="location-code">Short code *</label><input id="location-code" class="span12" name="code" maxlength="30" placeholder="UTTARA" value="{{ old('code',$formLocation?$formLocation->code:'') }}" required></div></div>
                    <label for="location-type">Location type *</label><select id="location-type" class="span12" name="type">@foreach(['warehouse'=>'Warehouse','branch'=>'Branch','store'=>'Retail store','distribution_center'=>'Distribution center','office'=>'Office'] as $value=>$label)<option value="{{ $value }}" {{ old('type',$formLocation?$formLocation->type:'warehouse')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select>
                    <label for="location-address">Street address</label><textarea id="location-address" class="span12" rows="3" name="address" maxlength="1000">{{ old('address',$formLocation?$formLocation->address:'') }}</textarea>
                    <div class="row-fluid"><div class="span6"><label for="location-country">Country</label><input id="location-country" class="span12" name="country" value="{{ old('country',$formLocation?$formLocation->country:'Bangladesh') }}"></div><div class="span6"><label for="location-division">Division / state</label><input id="location-division" class="span12" name="division" value="{{ old('division',$formLocation?$formLocation->division:'') }}"></div></div>
                    <div class="row-fluid"><div class="span8"><label for="location-city">City</label><input id="location-city" class="span12" name="city" value="{{ old('city',$formLocation?$formLocation->city:'') }}"></div><div class="span4"><label for="location-postal">Postal code</label><input id="location-postal" class="span12" name="postal_code" maxlength="20" value="{{ old('postal_code',$formLocation?$formLocation->postal_code:'') }}"></div></div>

                    <h4>GPS &amp; map</h4>
                    <div class="row-fluid"><div class="span6"><label for="latitude">Latitude</label><input id="latitude" class="span12" type="number" step="0.0000001" min="-90" max="90" name="latitude" placeholder="23.8103310" value="{{ old('latitude',$formLocation?$formLocation->latitude:'') }}"></div><div class="span6"><label for="longitude">Longitude</label><input id="longitude" class="span12" type="number" step="0.0000001" min="-180" max="180" name="longitude" placeholder="90.4125210" value="{{ old('longitude',$formLocation?$formLocation->longitude:'') }}"></div></div>
                    <button type="button" class="btn" id="capture-location"><i class="icon-crosshairs"></i> Use my current GPS location</button> <a id="preview-location" class="btn" href="#" target="_blank" rel="noopener" style="display:none"><i class="icon-map-marker"></i> Preview map</a><p id="gps-status" class="help-block">GPS capture requires browser location permission and HTTPS or localhost.</p>
                    <label for="google-maps-url">Google Maps URL (optional)</label><input id="google-maps-url" class="span12" type="url" name="google_maps_url" maxlength="255" placeholder="https://maps.google.com/..." value="{{ old('google_maps_url',$formLocation?$formLocation->google_maps_url:'') }}">

                    <h4>Contact &amp; operations</h4>
                    <div class="row-fluid"><div class="span6"><label for="contact-person">Contact person / manager</label><input id="contact-person" class="span12" name="contact_person" value="{{ old('contact_person',$formLocation?$formLocation->contact_person:'') }}"></div><div class="span6"><label for="location-phone">Phone</label><input id="location-phone" class="span12" type="tel" name="phone" value="{{ old('phone',$formLocation?$formLocation->phone:'') }}"></div></div>
                    <div class="row-fluid"><div class="span6"><label for="location-email">Email</label><input id="location-email" class="span12" type="email" name="email" value="{{ old('email',$formLocation?$formLocation->email:'') }}"></div><div class="span6"><label for="operating-hours">Operating hours</label><input id="operating-hours" class="span12" name="operating_hours" placeholder="Sat–Thu, 9:00 AM–8:00 PM" value="{{ old('operating_hours',$formLocation?$formLocation->operating_hours:'') }}"></div></div>
                    <label class="checkbox"><input type="checkbox" name="pickup_available" value="1" {{ old('pickup_available',$formLocation?$formLocation->pickup_available:0)?'checked':'' }}> Customer pickup available</label>
                    <label class="checkbox"><input type="checkbox" name="delivery_hub" value="1" {{ old('delivery_hub',$formLocation?$formLocation->delivery_hub:0)?'checked':'' }}> Delivery/dispatch hub</label>
                    <label for="location-notes">Internal notes</label><textarea id="location-notes" class="span12" rows="3" maxlength="2000" name="notes">{{ old('notes',$formLocation?$formLocation->notes:'') }}</textarea>
                    <button class="btn btn-primary" style="margin-top:12px">{{ $formLocation ? 'Update location' : 'Create location' }}</button>
                    @if($formLocation)<a class="btn" style="margin-top:12px" href="{{ url('/stock-locations') }}">Cancel</a>@endif
                </form>
            </div>
        </div>

        <div class="box span7">
            <div class="box-header"><h2><i class="icon-building"></i> Inventory locations</h2></div>
            <div class="box-content">
                <table class="table table-striped table-bordered"><thead><tr><th>Location</th><th>Contact &amp; capabilities</th><th style="width:105px">Status</th><th style="width:90px">Actions</th></tr></thead><tbody>
                @forelse($locations as $location)
                    @php $mapUrl=$location->google_maps_url?:($location->latitude!==null&&$location->longitude!==null?'https://www.google.com/maps?q='.$location->latitude.','.$location->longitude:null); @endphp
                    <tr><td><strong>{{ $location->name }}</strong><br><small>{{ $location->code }} · {{ ucwords(str_replace('_',' ',$location->type)) }}</small>@if($location->address||$location->city)<br><small>{{ implode(', ',array_filter([$location->address,$location->city,$location->division,$location->postal_code,$location->country])) }}</small>@endif @if($mapUrl)<br><a href="{{ $mapUrl }}" target="_blank" rel="noopener"><i class="icon-map-marker"></i> Open map</a>@endif</td><td>@if($location->contact_person)<strong>{{ $location->contact_person }}</strong><br>@endif @if($location->phone)<small><i class="icon-phone"></i> {{ $location->phone }}</small><br>@endif @if($location->email)<small><i class="icon-envelope"></i> {{ $location->email }}</small><br>@endif @if($location->operating_hours)<small><i class="icon-time"></i> {{ $location->operating_hours }}</small><br>@endif @if($location->pickup_available)<span class="label label-info">Pickup</span>@endif @if($location->delivery_hub)<span class="label label-warning">Delivery hub</span>@endif</td><td>@if($location->is_default)<span class="label label-info">Default</span>@endif <span class="label {{ $location->is_active?'label-success':'' }}">{{ $location->is_active?'Active':'Disabled' }}</span></td><td><a class="btn btn-mini btn-info" href="{{ url('/stock-locations?edit='.$location->id) }}" title="Edit"><i class="icon-edit"></i></a>@if(!$location->is_default)<form method="post" action="{{ url('/stock-locations/'.$location->id.'/toggle') }}" style="display:inline">{{ csrf_field() }}<button class="btn btn-mini" title="Enable or disable"><i class="icon-off"></i></button></form>@endif</td></tr>
                @empty<tr><td colspan="4">No inventory locations have been created.</td></tr>@endforelse
                </tbody></table>
            </div>

            <div class="box-header"><h2><i class="icon-exchange"></i> Transfer stock</h2></div>
            <div class="box-content">
                @if($locations->where('is_active',1)->count()<2)<div class="alert alert-info">Create and activate at least two locations before transferring stock.</div>@else
                <form method="post" action="{{ url('/stock-transfers') }}">{{ csrf_field() }}
                    <div class="row-fluid"><div class="span6"><label>From location</label><select class="span12" name="from_location_id" required>@foreach($locations->where('is_active',1) as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div><div class="span6"><label>To location</label><select class="span12" name="to_location_id" required>@foreach($locations->where('is_active',1) as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div></div>
                    <table class="table table-bordered" id="transfer-lines"><thead><tr><th>Product</th><th style="width:120px">Quantity</th><th style="width:45px"></th></tr></thead><tbody></tbody></table>
                    <button id="add-transfer-line" class="btn" type="button"><i class="icon-plus"></i> Add product</button><label style="margin-top:12px">Transfer note</label><textarea class="span12" name="notes"></textarea><button class="btn btn-primary" style="margin-top:12px" onclick="return confirm('Complete this stock transfer?')">Complete transfer</button>
                </form>@endif
            </div>
        </div>
    </div>

    <div class="box"><div class="box-header"><h2>Stock by location</h2></div><div class="box-content" style="overflow-x:auto"><table class="table table-striped table-bordered"><thead><tr><th>Product</th>@foreach($locations as $location)<th>{{ $location->name }}</th>@endforeach<th>Total</th></tr></thead><tbody>@foreach($products as $product)<tr><td><strong>{{ $product->product_name }}</strong><br><small>{{ $product->sku }}</small></td>@foreach($locations as $location)@php($balance=isset($balances[$product->id])?$balances[$product->id]->firstWhere('location_id',$location->id):null)<td>{{ $balance?$balance->quantity:0 }}</td>@endforeach<td><strong>{{ $product->stock_quantity }}</strong></td></tr>@endforeach</tbody></table></div></div>
    <div class="box"><div class="box-header"><h2>Recent stock transfers</h2></div><div class="box-content"><table class="table table-striped"><thead><tr><th>Reference</th><th>From</th><th>To</th><th>Status</th><th>Completed</th><th>By</th></tr></thead><tbody>@forelse($transfers as $transfer)<tr><td><strong>{{ $transfer->transfer_number }}</strong></td><td>{{ $transfer->from_name }}</td><td>{{ $transfer->to_name }}</td><td><span class="label label-success">{{ ucfirst($transfer->status) }}</span></td><td>{{ date('M j, Y g:i A',strtotime($transfer->completed_at)) }}</td><td>{{ $transfer->created_by }}</td></tr>@empty<tr><td colspan="6">No stock transfers completed.</td></tr>@endforelse</tbody></table></div></div>
</div>

<template id="transfer-line-template"><tr><td><select class="span12" name="product_id[]" required><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->product_name }}{{ $product->sku?' · '.$product->sku:'' }} · total {{ $product->stock_quantity }}</option>@endforeach</select></td><td><input class="span12" type="number" min="1" max="100000" name="quantity[]" value="1" required></td><td><button class="btn btn-danger remove-transfer-line" type="button">×</button></td></tr></template>
<script>
(function(){
    var latitude=document.getElementById('latitude'),longitude=document.getElementById('longitude'),capture=document.getElementById('capture-location'),preview=document.getElementById('preview-location'),status=document.getElementById('gps-status');
    function updatePreview(){if(latitude.value&&longitude.value){preview.href='https://www.google.com/maps?q='+encodeURIComponent(latitude.value+','+longitude.value);preview.style.display='inline-block';}else preview.style.display='none';}
    latitude.addEventListener('input',updatePreview);longitude.addEventListener('input',updatePreview);updatePreview();
    capture.addEventListener('click',function(){if(!navigator.geolocation){status.textContent='This browser does not support GPS location.';return;}capture.disabled=true;status.textContent='Getting current location…';navigator.geolocation.getCurrentPosition(function(position){latitude.value=position.coords.latitude.toFixed(7);longitude.value=position.coords.longitude.toFixed(7);status.textContent='GPS coordinates captured (accuracy approximately '+Math.round(position.coords.accuracy)+' metres).';capture.disabled=false;updatePreview();},function(error){status.textContent='Could not capture location: '+error.message;capture.disabled=false;},{enableHighAccuracy:true,timeout:15000,maximumAge:0});});
    var body=document.querySelector('#transfer-lines tbody'),button=document.getElementById('add-transfer-line'),template=document.getElementById('transfer-line-template');if(!body||!button)return;function add(){body.appendChild(template.content.cloneNode(true));}button.addEventListener('click',add);body.addEventListener('click',function(e){if(e.target.classList.contains('remove-transfer-line')&&body.children.length>1)e.target.closest('tr').remove();});add();
}());
</script>
@endsection
