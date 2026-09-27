@php
    // One gtag loader, configured for whichever of the two is filled in:
    // GA4 measures traffic, Google Ads attributes conversions to campaigns.
    // Either works alone; both together share the same script.
    $gaId = setting('ga_id');
    $adsId = setting('google_ads_id');
    $primary = $gaId ?: $adsId;
@endphp

@if (filled($primary))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $primary }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @if (filled($gaId))
            gtag('config', @json($gaId));
        @endif
        @if (filled($adsId))
            gtag('config', @json($adsId));
        @endif
    </script>
@endif
