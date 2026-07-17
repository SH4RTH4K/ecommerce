<div class="lt-topbar">
    <div class="lt-container lt-topbar-inner">
        <p>{{ isset($siteSettings['notice_text']) && $siteSettings['notice_text'] ? $siteSettings['notice_text'] : 'বিশ্বস্ত প্রযুক্তি পণ্য, সেরা মূল্য ও নির্ভরযোগ্য সেবা' }}</p>
        @php $phone = isset($siteSettings['phone']) && $siteSettings['phone'] ? $siteSettings['phone'] : '+880 1711-000000'; @endphp
        <div><a href="tel:{{ preg_replace('/[^+0-9]/', '', $phone) }}"><i class="fa fa-phone"></i> {{ $phone }}</a><a href="{{ url('/contact-us') }}">Support</a></div>
    </div>
</div>
