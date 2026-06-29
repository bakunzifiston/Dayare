<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('favicon-32x32.png') }}" sizes="32x32">
<link rel="icon" type="image/png" href="{{ asset('favicon-16x16.png') }}" sizes="16x16">
@if (empty($pwa) || ! $pwa)
    <link rel="apple-touch-icon" href="{{ asset('favicon-32x32.png') }}">
@endif
@if (! empty($pwa) && config('pwa.enabled'))
    @include('partials.pwa')
@endif
