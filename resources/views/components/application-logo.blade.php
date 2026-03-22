{{-- BuchaPro brand logo (PNG in public/images) --}}
@props(['alt' => null])
<img
    src="{{ asset('images/buchapro-logo.png') }}"
    alt="{{ $alt ?? config('app.name', 'BuchaPro') }}"
    {{ $attributes->merge(['class' => 'h-9 w-auto max-w-[200px] object-contain object-left']) }}
/>
