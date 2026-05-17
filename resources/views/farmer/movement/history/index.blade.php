<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Movement history') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Animal') }}</th>
                        <th class="px-4 py-3">{{ __('Permit') }}</th>
                        <th class="px-4 py-3">{{ __('Route') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-4 py-3">{{ $record->movement_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $record->animal?->selectionLabel() ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('farmer.movement.permits.show', $record->permit) }}" class="text-bucha-primary hover:underline">{{ $record->permit?->permit_number }}</a></td>
                            <td class="px-4 py-3 text-slate-600">{{ $record->source_location }} → {{ $record->destination_location }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No movement history recorded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</x-app-layout>
