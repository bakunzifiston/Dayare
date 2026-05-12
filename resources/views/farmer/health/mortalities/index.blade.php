<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <h2 class="font-semibold text-xl text-slate-800">{{ __('Mortality records') }}</h2>
            <a href="{{ route('farmer.health.mortalities.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">{{ __('Record mortality') }}</a>
        </div>
    </x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.health.partials.nav')
        @if ($records->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No mortality records yet.') }}</p>
        @else
            <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600"><tr><th class="px-4 py-3">{{ __('Code') }}</th><th class="px-4 py-3">{{ __('Animal') }}</th><th class="px-4 py-3">{{ __('Death date') }}</th><th class="px-4 py-3">{{ __('Cause') }}</th><th class="px-4 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $record->mortality_code }}</td>
                                <td class="px-4 py-3">{{ $record->animal?->animal_code }}</td>
                                <td class="px-4 py-3">{{ $record->death_date?->toDateString() }}</td>
                                <td class="px-4 py-3">{{ $record->cause_of_death }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('farmer.health.mortalities.show', $record) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
