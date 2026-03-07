<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ __('Recipients') }}
        </h1>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm text-slate-600 mb-4">
            {{ __('Facilities (from your businesses) that have received deliveries. Clients are listed under Clients.') }}
        </p>

        @if ($recipients->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No recipient facilities yet. Deliveries to your facilities will appear here.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase">
                            <th class="py-2 pr-4">{{ __('Facility') }}</th>
                            <th class="py-2 pr-4">{{ __('Business') }}</th>
                            <th class="py-2 pr-4">{{ __('Last delivery') }}</th>
                            <th class="py-2 pr-4">{{ __('Delivery count') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($recipients as $recipient)
                            @php
                                $facility = $recipient->facility;
                                $lastDate = $recipient->last_delivery_date instanceof \Carbon\Carbon
                                    ? $recipient->last_delivery_date
                                    : \Carbon\Carbon::parse($recipient->last_delivery_date);
                            @endphp
                            <tr>
                                <td class="py-2 pr-4 font-medium text-slate-900">{{ $facility?->facility_name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-slate-700">{{ $facility?->business?->business_name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-slate-700">{{ $lastDate->format('d M Y') }}</td>
                                <td class="py-2 pr-4 text-slate-700">{{ $recipient->delivery_count }}</td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                    @if ($facility && $facility->business)
                                        <a href="{{ route('businesses.facilities.show', [$facility->business, $facility]) }}" class="text-indigo-600 hover:underline font-medium">{{ __('View facility') }}</a>
                                        <span class="text-slate-300 mx-1">|</span>
                                        <a href="{{ route('delivery-confirmations.index', ['receiving_facility_id' => $facility->id]) }}" class="text-slate-600 hover:text-slate-900 font-medium">{{ __('Deliveries') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
