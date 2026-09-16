@props([
    'outlets',
    'selected' => null,
    'name' => 'outlet_id',
    'label' => null,
])

@php
    $label = $label ?? __('Outlet');
    $selected = $selected !== null && $selected !== '' ? (int) $selected : null;
@endphp

<div {{ $attributes->merge(['class' => 'min-w-[10rem]']) }}>
    <label for="{{ $name }}" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary"
        onchange="this.form.submit()"
    >
        <option value="">{{ __('All outlets') }}</option>
        @foreach ($outlets as $outlet)
            <option value="{{ $outlet->id }}" @selected($selected === (int) $outlet->id)>{{ $outlet->name }}</option>
        @endforeach
    </select>
</div>
