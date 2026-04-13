@php
    $currentLocale = app()->getLocale();
@endphp

<form method="POST" action="{{ route('locale.update') }}" class="inline-flex items-center gap-2">
    @csrf
    <label for="locale-switcher-{{ $attributes->get('id', 'default') }}" class="sr-only">{{ __('Language') }}</label>
    <select
        id="locale-switcher-{{ $attributes->get('id', 'default') }}"
        name="locale"
        onchange="this.form.submit()"
        class="rounded-md border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:border-bucha-primary focus:ring-bucha-primary"
    >
        <option value="en" @selected($currentLocale === 'en')>{{ __('English') }}</option>
        <option value="rw" @selected($currentLocale === 'rw')>{{ __('Kinyarwanda') }}</option>
    </select>
</form>
