@if(isset($siteSettings['google_analytics_id']) && preg_match('/^G-[A-Z0-9]+$/i', $siteSettings['google_analytics_id']))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($siteSettings['google_analytics_id']) }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', @json(strtoupper($siteSettings['google_analytics_id'])), { 'anonymize_ip': true });
</script>
@endif
