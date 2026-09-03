@php
    $themeCss = $storefrontThemeCss ?? [];
@endphp
@if(!empty($themeCss))
<style id="storefront-theme-vars">
:root{
@foreach($themeCss as $variable => $value)
    {{ $variable }}: {!! str_replace('&quot;', '"', e($value)) !!};
@endforeach
}
</style>
@endif
