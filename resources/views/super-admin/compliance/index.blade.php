<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('super-admin.dashboard') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Platform dashboard') }}</a>
                <h1 class="mt-1 text-xl font-semibold text-slate-800 tracking-tight">{{ $meta['label'] }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">{{ $meta['description'] }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <x-super-admin.tenant-environment-filter
                :action="route('super-admin.compliance.index', ['alert' => $alert])"
                :current="$tenantEnvironmentFilter ?? null"
            />

            <section class="flex flex-wrap gap-2">
                @foreach ($pipelineAlerts as $navAlert)
                    <a href="{{ route('super-admin.compliance.index', array_merge(['alert' => $navAlert['key']], \App\Support\TenantEnvironmentScope::queryParams($tenantEnvironmentFilter ?? null))) }}"
                       class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border {{ $navAlert['key'] === $alert ? 'bg-bucha-primary text-white border-bucha-primary' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }}">
                        {{ $navAlert['label'] }}
                        <span class="ml-1.5 tabular-nums opacity-80">({{ number_format($navAlert['count']) }})</span>
                    </a>
                @endforeach
            </section>

            @if ($items->total() === 0)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 px-6 py-10 text-center">
                    <p class="text-sm font-medium text-emerald-900">{{ __('No items in this alert category') }}</p>
                    <p class="mt-1 text-xs text-emerald-800">{{ __('All records are compliant for this pipeline check.') }}</p>
                </div>
            @else
                <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Reference') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility / business') }}</th>
                                    <th class="px-4 py-3">{{ __('Details') }}</th>
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($items as $item)
                                    <tr class="hover:bg-slate-50/70">
                                        @if ($item instanceof \App\Models\Batch)
                                            <td class="px-4 py-3 font-medium text-slate-900">{{ $item->batch_code }}</td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $item->slaughterExecution?->slaughterPlan?->facility?->facility_name ?? '—' }}
                                                <span class="block text-xs text-slate-400">{{ $item->slaughterExecution?->slaughterPlan?->facility?->business?->business_name ?? '' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">{{ __('Batch') }} · {{ ucfirst((string) $item->status) }}</td>
                                            <td class="px-4 py-3 text-slate-500 tabular-nums">{{ optional($item->slaughterExecution?->slaughter_time)->format('M j, Y') ?? '—' }}</td>
                                        @elseif ($item instanceof \App\Models\SlaughterExecution)
                                            <td class="px-4 py-3 font-medium text-slate-900">#{{ $item->id }}</td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $item->slaughterPlan?->facility?->facility_name ?? '—' }}
                                                <span class="block text-xs text-slate-400">{{ $item->slaughterPlan?->facility?->business?->business_name ?? '' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">{{ __('Execution') }} · {{ $item->actual_animals_slaughtered }} {{ __('animals') }}</td>
                                            <td class="px-4 py-3 text-slate-500 tabular-nums">{{ optional($item->slaughter_time)->format('M j, Y H:i') ?? '—' }}</td>
                                        @elseif ($item instanceof \App\Models\WarehouseStorage)
                                            <td class="px-4 py-3 font-medium text-slate-900">#{{ $item->id }}</td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $item->warehouseFacility?->facility_name ?? $item->batch?->slaughterExecution?->slaughterPlan?->facility?->facility_name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ __('Storage') }} · {{ number_format((float) $item->quantity_stored, 2) }} {{ $item->quantity_unit_label }}
                                                @if ($item->batch?->batch_code)
                                                    <span class="block text-xs text-slate-400">{{ $item->batch->batch_code }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 tabular-nums">{{ optional($item->entry_date)->format('M j, Y') ?? '—' }}</td>
                                        @elseif ($item instanceof \App\Models\TemperatureLog)
                                            <td class="px-4 py-3 font-medium text-slate-900">#{{ $item->id }}</td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $item->warehouseStorage?->batch?->slaughterExecution?->slaughterPlan?->facility?->facility_name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $item->status === 'critical' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ ucfirst($item->status) }} · {{ $item->recorded_temperature }}°C
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 tabular-nums">{{ optional($item->recorded_at)->format('M j, Y H:i') ?? '—' }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($items->hasPages())
                        <div class="px-4 py-3 border-t border-slate-100">{{ $items->links() }}</div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
