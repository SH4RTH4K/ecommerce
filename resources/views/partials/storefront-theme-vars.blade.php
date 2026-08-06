@php
    $themeCss = $storefrontThemeCss ?? [];
@endphp
@if(!empty($themeCss))
<style id="storefront-theme-vars">
:root{
@foreach($themeCss as $variable => $value)
    {{ $variable }}: {{ $value }};
@endforeach
}
</style>
@endif
