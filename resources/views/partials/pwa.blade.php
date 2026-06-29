<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="{{ config('pwa.theme_color') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('pwa.short_name') }}">
<link rel="apple-touch-icon" href="{{ asset('pwa-icon-192.png') }}">
<script>
    window.__pwaDeferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        window.__pwaDeferredPrompt = event;
        window.dispatchEvent(new Event('pwa-installable'));
    });
</script>
@vite(['resources/js/pwa-install.js'])
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@json(route('pwa.service-worker')), { scope: '/' }).catch(function () {});
        });
    }
</script>
