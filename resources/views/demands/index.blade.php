<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">
                {{ __('Demand') }}
            </h1>
            <a href="{{ route('demands.create') }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-[#3B82F6] text-white text-xs font-semibold hover:bg-[#2563eb]">
                {{ __('Add demand') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm text-slate-600 mb-4">
            {{ __('Customer requests for product (who wants what, how much, by when). Plan slaughter and deliveries to meet demand.') }}
        </p>

        @if (session('status'))
            <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
        @endif

        @if ($demands->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No demands yet.') }}</p>
            <a href="{{ route('demands.create') }}" class="inline-flex items-center px-4 py-2 mt-4 rounded-lg bg-[#3B82F6] text-white text-sm font-medium hover:bg-[#2563eb]">{{ __('Add first demand') }}</a>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase">
                            <th class="py-2 pr-4">{{ __('Demand #') }}</th>
                            <th class="py-2 pr-4">{{ __('Title') }}</th>
                            <th class="py-2 pr-4">{{ __('Destination') }}</th>
                            <th class="py-2 pr-4">{{ __('Species') }}</th>
                            <th class="py-2 pr-4">{{ __('Quantity') }}</th>
                            <th class="py-2 pr-4">{{ __('Requested date') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2 pr-4">{{ __('Can fulfill') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($demands as $demand)
                            <tr>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('demands.show', $demand) }}" class="text-indigo-600 hover:underline font-medium">{{ $demand->demand_number }}</a>
                                </td>
                                <td class="py-2 pr-4">{{ $demand->title }}</td>
                                <td class="py-2 pr-4">{{ $demand->destination_display }}</td>
                                <td class="py-2 pr-4">{{ $demand->species }}</td>
                                <td class="py-2 pr-4">{{ $demand->quantity }} {{ $demand->quantity_unit }}</td>
                                <td class="py-2 pr-4">{{ $demand->requested_delivery_date?->format('d M Y') ?? '—' }}</td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        @if($demand->status === 'fulfilled') bg-emerald-50 text-emerald-700
                                        @elseif($demand->status === 'confirmed' || $demand->status === 'in_progress') bg-blue-50 text-blue-700
                                        @elseif($demand->status === 'cancelled') bg-slate-100 text-slate-600
                                        @else bg-slate-100 text-slate-700 @endif">
                                        {{ \App\Models\Demand::STATUSES[$demand->status] ?? $demand->status }}
                                    </span>
                                </td>
                                @php $fulfill = $demand->getFulfillmentInfo(); @endphp
                                <td class="py-2 pr-4">
                                    @if ($fulfill['can_fulfill'])
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700" title="{{ $fulfill['message'] }}">{{ __('Yes') }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700" title="{{ $fulfill['message'] }}">{{ __('No') }}@if ($fulfill['short_by'] > 0) (−{{ number_format($fulfill['short_by'], 0) }} {{ $fulfill['unit'] }})@endif</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                    <a href="{{ route('demands.show', $demand) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">{{ __('View') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <a href="{{ route('demands.edit', $demand) }}" class="text-slate-600 hover:text-slate-900 text-xs font-medium">{{ __('Edit') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <form method="POST" action="{{ route('demands.destroy', $demand) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this demand?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-medium">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $demands->links() }}</div>
        @endif
    </div>
</x-app-layout>
