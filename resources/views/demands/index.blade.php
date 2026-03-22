<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Demands') }}
            </h2>
            <a href="{{ route('demands.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Add demand') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Draft') }}" :value="$kpis['draft']" color="slate" />
                <x-kpi-card inline title="{{ __('Confirmed') }}" :value="$kpis['confirmed']" color="blue" />
                <x-kpi-card inline title="{{ __('In progress') }}" :value="$kpis['in_progress']" color="amber" />
                <x-kpi-card inline title="{{ __('Fulfilled') }}" :value="$kpis['fulfilled']" color="green" />
                <x-kpi-card inline title="{{ __('Cancelled') }}" :value="$kpis['cancelled']" color="slate" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($demands->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No demands yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Customer requests for product. Plan slaughter and deliveries to meet demand.') }}</p>
                    <a href="{{ route('demands.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Add first demand') }}</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">{{ __('Demand #') }}</th>
                                    <th class="px-4 py-3">{{ __('Title') }}</th>
                                    <th class="px-4 py-3">{{ __('Destination') }}</th>
                                    <th class="px-4 py-3">{{ __('Species') }}</th>
                                    <th class="px-4 py-3">{{ __('Quantity') }}</th>
                                    <th class="px-4 py-3">{{ __('Requested date') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Can fulfill') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($demands as $demand)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('demands.show', $demand) }}" class="font-medium text-slate-900 hover:text-bucha-primary">{{ $demand->demand_number }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ Str::limit($demand->title, 40) }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $demand->destination_display }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $demand->species }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $demand->quantity }} {{ $demand->quantity_unit_label }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $demand->requested_delivery_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                @if($demand->status === 'fulfilled') bg-emerald-50 text-emerald-700
                                                @elseif($demand->status === 'confirmed' || $demand->status === 'in_progress') bg-blue-50 text-blue-700
                                                @elseif($demand->status === 'cancelled') bg-slate-100 text-slate-600
                                                @else bg-slate-100 text-slate-700 @endif">
                                                {{ \App\Models\Demand::STATUSES[$demand->status] ?? $demand->status }}
                                            </span>
                                        </td>
                                        @php $fulfill = $demand->getFulfillmentInfo(); @endphp
                                        <td class="px-4 py-3">
                                            @if ($fulfill['can_fulfill'])
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700" title="{{ $fulfill['message'] }}">{{ __('Yes') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-amber-50 text-amber-700" title="{{ $fulfill['message'] }}">{{ __('No') }}@if ($fulfill['short_by'] > 0) (−{{ number_format($fulfill['short_by'], 0) }} {{ $fulfill['unit'] }})@endif</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('demands.show', $demand) }}" class="text-bucha-primary hover:text-bucha-burgundy text-xs font-medium">{{ __('View') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <a href="{{ route('demands.edit', $demand) }}" class="text-slate-600 hover:text-slate-800 text-xs font-medium">{{ __('Edit') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <form method="POST" action="{{ route('demands.destroy', $demand) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this demand?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $demands->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
