@extends('front-end.master')
@section('title', ($product_details->seo_title ?: $product_details->product_name).' | '.$brandName)
@section('meta_description', $product_details->seo_description ?: ($product_details->short_description ?: 'Buy '.$product_details->product_name.' from '.$brandName.'. View price, specifications, warranty and availability.'))
@section('canonical', url('/product-details/'.$product_details->id))
@section('og_type', 'product')
@section('og_image', $product_details->image_url)
@push('structured_data')
<script type="application/ld+json">{!! json_encode(array_filter(['@context' => 'https://schema.org','@type' => 'Product','name' => $product_details->product_name,'image' => $product_details->all_images->values()->all(),'description' => $product_details->seo_description ?: $product_details->short_description,'sku' => $product_details->sku ?: 'PRD'.$product_details->id,'brand' => $product_details->manufacturer ? ['@type' => 'Brand', 'name' => $product_details->manufacturer->manufacturer_name] : null,'offers' => ['@type' => 'Offer','url' => url('/product-details/'.$product_details->id),'priceCurrency' => 'BDT','price' => (string) $product_details->selling_price,'availability' => $product_details->product_condition === 'In Stock' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock','itemCondition' => 'https://schema.org/NewCondition'],'aggregateRating' => $averageRating ? ['@type' => 'AggregateRating','ratingValue' => (string) $averageRating,'reviewCount' => (string) $reviews->count()] : null]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush
@section('main_content')
@php
    $warrantyText = $product_details->warranty_display;
    $productCode = trim((string) ($product_details->product_code ?: $product_details->sku));
    $sku = trim((string) $product_details->sku);
    $showSeparateSku = $sku !== '' && $sku !== $productCode;
@endphp
<section class="lt-detail-section">
    @include('partials.flash')

    <nav class="lt-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">Home</a>
        <i class="fa fa-angle-right"></i>
        @if($product_details->category)
            <a href="{{ url('/product-by-category/'.$product_details->category_id) }}">{{ $product_details->category->category_name }}</a>
            <i class="fa fa-angle-right"></i>
        @endif
        <span>{{ $product_details->product_name }}</span>
    </nav>

    <div class="lt-detail-grid">
        <div class="lt-product-gallery">
            <div class="lt-detail-image">
                <img id="main-product-image" src="{{ $product_details->image_url }}" alt="{{ $product_details->product_name }}">
            </div>

            @if($product_details->all_images->count() > 1)
                <div class="lt-thumbnails">
                    @foreach($product_details->all_images as $index => $image)
                        @php($resolvedImage = filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image))
                        <button type="button" aria-label="View {{ $product_details->product_name }} image {{ $index + 1 }}" onclick="document.getElementById('main-product-image').src=this.dataset.image" data-image="{{ $resolvedImage }}">
                            <img src="{{ $resolvedImage }}" alt="{{ $product_details->product_name }} thumbnail {{ $index + 1 }}" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lt-detail-info">
            <span class="lt-detail-code">Product code: {{ $productCode ?: 'LT'.str_pad($product_details->id, 5, '0', STR_PAD_LEFT) }}</span>
            <h1>{{ $product_details->product_name }}</h1>
            <p class="lt-detail-model">Model: {{ $product_details->product_model ?: 'Not specified' }}</p>
            <p class="lt-product-intro">Buy {{ $product_details->product_name }} from {{ optional($product_details->manufacturer)->manufacturer_name ?: $brandName }} with fast delivery and support.</p>
            <div class="lt-detail-price">&#2547;{{ number_format($product_details->selling_price) }} @if($product_details->has_offer)<del>&#2547;{{ number_format($product_details->regular_price) }}</del>@endif</div>
            <p class="lt-stock {{ $product_details->product_condition === 'In Stock' ? 'is-in' : 'is-out' }}"><i class="fa fa-circle"></i> {{ $product_details->product_condition }}</p>

            <div class="lt-detail-highlights">
                <div>
                    <dt>Status</dt>
                    <dd>{{ $product_details->product_condition }}</dd>
                </div>
                <div>
                    <dt>Warranty</dt>
                    <dd>{{ $warrantyText }}</dd>
                </div>
                <div>
                    <dt>Delivery</dt>
                    <dd>1-3 working days nationwide</dd>
                </div>
            </div>

            @if($product_details->short_description)
                <p class="lt-product-summary">{{ $product_details->short_description }}</p>
            @endif

            @if(!empty($product_details->key_features))
                <div class="lt-key-features">
                    <h2>Key Features</h2>
                    <ul>
                        @foreach($product_details->key_features as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <dl class="lt-detail-meta">
                @if(optional($product_details->manufacturer)->company)
                    <div>
                        <dt>Company</dt>
                        <dd>{{ $product_details->manufacturer->company->name }}</dd>
                    </div>
                @endif
                @if($product_details->manufacturer)
                    <div>
                        <dt>Brand</dt>
                        <dd>{{ $product_details->manufacturer->manufacturer_name }}</dd>
                    </div>
                @endif
                @if($product_details->series)
                    <div>
                        <dt>Collection / Product Line</dt>
                        <dd>{{ $product_details->series->name }}</dd>
                    </div>
                @endif
                @if($showSeparateSku)
                    <div>
                        <dt>SKU</dt>
                        <dd>{{ $sku }}</dd>
                    </div>
                @endif
            </dl>

            <div class="lt-buy-actions">
                <form action="{{ route('cart.add', $product_details->id) }}" method="post">
                    {{ csrf_field() }}
                    <input type="number" min="1" max="99" name="quantity" value="1">
                    <button class="lt-primary-button" type="submit"><i class="fa fa-shopping-cart"></i> Add to cart</button>
                </form>
                <form action="{{ route('compare.add', $product_details->id) }}" method="post">
                    {{ csrf_field() }}
                    <button class="lt-secondary-button" type="submit"><i class="fa fa-exchange"></i> Compare</button>
                </form>
                <form action="{{ route('wishlist.add', $product_details->id) }}" method="post">
                    {{ csrf_field() }}
                    <button class="lt-secondary-button" type="submit"><i class="fa fa-heart-o"></i> Wishlist</button>
                </form>
            </div>

            @if($product_details->product_condition !== 'In Stock')
                <form class="lt-feedback-form" method="post" action="{{ route('stock-alerts.subscribe', $product_details->id) }}" style="margin-top:18px">
                    {{ csrf_field() }}
                    <h3><i class="fa fa-bell-o"></i> Notify me when available</h3>
                    <div style="display:flex;gap:10px">
                        <input type="email" name="email" value="{{ old('email', optional(auth()->user())->email) }}" placeholder="Your email address" required style="flex:1">
                        <button class="lt-primary-button" type="submit">Create alert</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if(!empty($product_details->specifications))
        <div class="lt-description">
            <h2>Specification</h2>
            <table class="lt-spec-table">
                @foreach($product_details->specifications as $label => $value)
                    @if(is_array($value))
                        <tr class="lt-spec-group">
                            <th colspan="2">{{ $label }}</th>
                        </tr>
                        @foreach($value as $itemLabel => $itemValue)
                            <tr>
                                <th>{{ $itemLabel }}</th>
                                <td>{{ $itemValue }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <th>{{ $label }}</th>
                            <td>{{ $value }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    @endif

    @if($product_details->variants->isNotEmpty())
        <div class="lt-description">
            <h2>Available Options</h2>
            <table class="lt-spec-table">
                <tr>
                    <th>Option</th>
                    <th>SKU</th>
                    <th>Availability</th>
                </tr>
                @foreach($product_details->variants as $variant)
                    <tr>
                        <td>{{ $variant->name }}</td>
                        <td>{{ $variant->sku ?: '-' }}</td>
                        <td>{{ $variant->stock_quantity > 0 ? $variant->stock_quantity.' available' : 'Out of stock' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if($product_details->industry_profile === 'medicine')
        <div class="lt-description">
            <h2>Medicine Information</h2>
            <table class="lt-spec-table">
                @if($product_details->generic_name)
                    <tr>
                        <th>Generic name</th>
                        <td>{{ $product_details->generic_name }}</td>
                    </tr>
                @endif
                @if($product_details->strength)
                    <tr>
                        <th>Strength</th>
                        <td>{{ $product_details->strength }}</td>
                    </tr>
                @endif
                @if($product_details->dosage_form)
                    <tr>
                        <th>Dosage form</th>
                        <td>{{ $product_details->dosage_form }}</td>
                    </tr>
                @endif
                <tr>
                    <th>Prescription</th>
                    <td>{{ $product_details->prescription_required ? 'Required' : 'Not marked as required' }}</td>
                </tr>
                @if($product_details->storage_instructions)
                    <tr>
                        <th>Storage</th>
                        <td>{{ $product_details->storage_instructions }}</td>
                    </tr>
                @endif
            </table>
        </div>
    @endif

    @if($product_details->industry_profile === 'food' && ($product_details->allergen_information || $product_details->storage_instructions))
        <div class="lt-description">
            <h2>Food &amp; Storage Information</h2>
            <table class="lt-spec-table">
                @if($product_details->allergen_information)
                    <tr>
                        <th>Ingredients / allergens</th>
                        <td>{{ $product_details->allergen_information }}</td>
                    </tr>
                @endif
                @if($product_details->storage_instructions)
                    <tr>
                        <th>Storage</th>
                        <td>{{ $product_details->storage_instructions }}</td>
                    </tr>
                @endif
            </table>
        </div>
    @endif

    @if($product_details->attributeValues->isNotEmpty())
        <div class="lt-description">
            <h2>Product Attributes</h2>
            <table class="lt-spec-table">
                @foreach($product_details->attributeValues as $attributeValue)
                    @if($attributeValue->attribute)
                        <tr>
                            <th>{{ $attributeValue->attribute->name }}</th>
                            <td>{{ $attributeValue->display_value }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    @endif

    <div class="lt-description">
        <h2>Product Description</h2>
        <div class="lt-product-description">{!! product_description_html($product_details->product_description) !!}</div>
    </div>

    <div class="lt-warranty">
        <h2>After-sales Service</h2>
        <p>Our support team can help with product guidance, warranty claims, and service coordination. Accidental damage, unauthorized repair, improper installation, and misuse are not covered unless the manufacturer states otherwise.</p>
    </div>

    <div class="lt-feedback-grid">
        <section class="lt-feedback-panel">
            <div class="lt-feedback-heading">
                <div>
                    <span>Customer feedback</span>
                    <h2>Reviews</h2>
                </div>
                @if($averageRating)
                    <strong>{{ $averageRating }} <i class="fa fa-star"></i><small>{{ $reviews->count() }} reviews</small></strong>
                @endif
            </div>

            <div class="lt-feedback-list">
                @forelse($reviews as $review)
                    <article>
                        <div>
                            <strong>{{ $review->customer_name }}</strong>
                            <span>
                                @for($star = 1; $star <= 5; $star++)
                                    <i class="fa {{ $star <= $review->rating ? 'fa-star' : 'fa-star-o' }}"></i>
                                @endfor
                            </span>
                        </div>
                        <p>{{ $review->review }}</p>
                        <small>{{ date('M j, Y', strtotime($review->created_at)) }}</small>
                    </article>
                @empty
                    <p>No published reviews yet. Be the first to share your experience.</p>
                @endforelse
            </div>

            <form class="lt-feedback-form" method="post" action="{{ route('reviews.store', $product_details->id) }}">
                {{ csrf_field() }}
                <h3>Write a review</h3>
                <div class="lt-inline-fields">
                    <label>Name
                        <input name="customer_name" value="{{ old('customer_name', optional(auth()->user())->name) }}" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" value="{{ old('email', optional(auth()->user())->email) }}">
                    </label>
                </div>
                <label>Rating
                    <select name="rating" required>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Very good</option>
                        <option value="3">3 - Good</option>
                        <option value="2">2 - Fair</option>
                        <option value="1">1 - Poor</option>
                    </select>
                </label>
                <label>Review
                    <textarea name="review" minlength="10" required>{{ old('review') }}</textarea>
                </label>
                <button class="lt-primary-button" type="submit">Submit for review</button>
            </form>
        </section>

        <section class="lt-feedback-panel">
            <div class="lt-feedback-heading">
                <div>
                    <span>Need product advice?</span>
                    <h2>Questions &amp; Answers</h2>
                </div>
            </div>

            <div class="lt-feedback-list">
                @forelse($questions as $question)
                    <article>
                        <strong>Q: {{ $question->question }}</strong>
                        <p><b>A:</b> {{ $question->answer }}</p>
                        <small>Asked by {{ $question->customer_name }}</small>
                    </article>
                @empty
                    <p>No answered questions yet.</p>
                @endforelse
            </div>

            <form class="lt-feedback-form" method="post" action="{{ route('questions.store', $product_details->id) }}">
                {{ csrf_field() }}
                <h3>Ask a question</h3>
                <div class="lt-inline-fields">
                    <label>Name
                        <input name="customer_name" value="{{ old('customer_name', optional(auth()->user())->name) }}" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" value="{{ old('email', optional(auth()->user())->email) }}">
                    </label>
                </div>
                <label>Your question
                    <textarea name="question" minlength="5" required>{{ old('question') }}</textarea>
                </label>
                <button class="lt-primary-button" type="submit">Submit question</button>
            </form>
        </section>
    </div>

    @if($similarProducts->isNotEmpty())
        <div class="lt-section-heading">
            <div>
                <span>You may also like</span>
                <h2>Similar Products</h2>
            </div>
        </div>
        <div class="lt-product-grid">
            @foreach($similarProducts as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif
</section>
@endsection
