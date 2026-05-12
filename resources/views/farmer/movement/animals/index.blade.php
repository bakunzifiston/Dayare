<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Movement animals') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        @if ($records->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No movement animal records yet.') }}</p>
        @else
            <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Permit') }}</th>
                            <th class="px-4 py-3">{{ __('Animal') }}</th>
                            <th class="px-4 py-3">{{ __('Farm') }}</th>
                            <th class="px-4 py-3">{{ __('Condition') }}</th>
                            <th class="px-4 py-3">{{ __('Loading') }}</th>
                            <th class="px-4 py-3">{{ __('Arrival') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3"><a href="{{ route('farmer.movement.permits.show', $record->movementPermit) }}" class="font-mono text-xs text-bucha-primary hover:underline">{{ $record->movementPermit?->permit_number }}</a></td>
                                <td class="px-4 py-3">{{ $record->animal?->animal_code ?: $record->animal_identifier ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $record->movementPermit?->sourceFarm?->name }}</td>
                                <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->movement_condition) }}</td>
                                <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->loading_status) }}</td>
                                <td class="px-4 py-3 capitalize">{{ $record->arrival_status ? str_replace('_', ' ', $record->arrival_status) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
