<article class="pcb-slot" data-configured="{{ $products[$key]->isNotEmpty()?'1':'0' }}">
    <span class="pcb-icon">@if(!empty($slot['icon_image']))<img src="{{ asset($slot['icon_image']) }}" alt="">@else<i class="fa fa-{{ $slot['icon'] }}"></i>@endif</span>
    <div class="pcb-label"><strong>{{ $slot['label'] }}</strong><small>{{ $slot['required']?'Required':'Optional' }}</small></div>
    <div class="pcb-choice">@if(!empty($build[$key])&&$selected->has($build[$key]))@php($product=$selected[$build[$key]])<div class="pcb-selected"><img src="{{ $product->image_url }}" alt=""><div><strong>{{ $product->product_name }}</strong><span>&#2547;{{ number_format($product->selling_price) }}</span></div></div>@else<a class="lt-secondary-button" href="{{ route('pc-builder.choose',$key) }}" style="display:block;text-align:center">Choose {{ $slot['label'] }}</a>@endif</div>
    @if(!empty($build[$key])&&$selected->has($build[$key]))<div class="pcb-actions"><form method="post" action="{{ route('pc-builder.remove',$key) }}">{{ csrf_field() }}<button class="pcb-remove" aria-label="Remove {{ $slot['label'] }}"><i class="fa fa-trash"></i> Remove</button></form></div>@endif
</article>
