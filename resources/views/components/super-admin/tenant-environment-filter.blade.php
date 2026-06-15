@props(['action' => null, 'current' => null])

@php
    use App\Support\TenantEnvironmentScope;
    $current = $current ?? TenantEnvironmentScope::current();
    $action = $action ?? url()->current();
@endphp

<form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
    <div>
        <label for="tenant_environment" class="text-xs font-medium text-slate-500">{{ __('Data scope') }}</label>
        <p class="text-[11px] text-slate-400 mt-0.5">{{ __('Metrics exclude test tenants unless you choose otherwise.') }}</p>
        <select id="tenant_environment" name="tenant_environment" class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
            @foreach (TenantEnvironmentScope::filterOptions() as $value => $label)
                <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @foreach (request()->except(['tenant_environment', 'page']) as $name => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <x-primary-button type="submit" class="!text-xs">{{ __('Apply scope') }}</x-primary-button>
</form>
