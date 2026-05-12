<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Sales payments') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.sales.partials.nav')
        @if ($records->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No payments recorded yet.') }}</p>
        @else
            <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Reference') }}</th>
                            <th class="px-4 py-3">{{ __('Sale') }}</th>
                            <th class="px-4 py-3">{{ __('Buyer') }}</th>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('Amount') }}</th>
                            <th class="px-4 py-3">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $record->payment_reference }}</td>
                                <td class="px-4 py-3"><a href="{{ route('farmer.sales.records.show', $record->sale) }}" class="text-bucha-primary hover:underline">{{ $record->sale?->sale_number }}</a></td>
                                <td class="px-4 py-3">{{ $record->sale?->buyer?->buyer_name }}</td>
                                <td class="px-4 py-3">{{ $record->payment_date?->toDateString() }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ number_format($record->amount_paid, 2) }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ number_format($record->remaining_balance, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
