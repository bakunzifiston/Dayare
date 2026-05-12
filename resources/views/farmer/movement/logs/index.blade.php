<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Movement history & logs') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        @if ($records->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No movement activity logged yet.') }}</p>
        @else
            <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('Permit') }}</th>
                            <th class="px-4 py-3">{{ __('Action') }}</th>
                            <th class="px-4 py-3">{{ __('Actor') }}</th>
                            <th class="px-4 py-3">{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3">{{ $record->action_date?->toDateTimeString() }}</td>
                                <td class="px-4 py-3"><a href="{{ route('farmer.movement.permits.show', $record->movementPermit) }}" class="font-mono text-xs text-bucha-primary hover:underline">{{ $record->movementPermit?->permit_number }}</a></td>
                                <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->action_type) }}</td>
                                <td class="px-4 py-3">{{ $record->actor?->name ?: __('System') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $record->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
