<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <h2 class="font-semibold text-xl text-slate-800">{{ __('Disease records') }}</h2>
            <a href="{{ route('farmer.health.diseases.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">{{ __('Add disease record') }}</a>
        </div>
    </x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.health.partials.nav')
        @if ($records->isEmpty())
            <div class="rounded-bucha border border-dashed border-slate-300 bg-white px-6 py-12 text-center"><p class="text-sm text-slate-600">{{ __('No disease records yet.') }}</p></div>
        @else
            <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600"><tr><th class="px-4 py-3">{{ __('Code') }}</th><th class="px-4 py-3">{{ __('Animal') }}</th><th class="px-4 py-3">{{ __('Disease') }}</th><th class="px-4 py-3">{{ __('Severity') }}</th><th class="px-4 py-3">{{ __('Recovery') }}</th><th class="px-4 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $record->disease_code }}</td>
                                <td class="px-4 py-3">{{ $record->animal?->animal_code }}</td>
                                <td class="px-4 py-3">{{ $record->disease_name }}</td>
                                <td class="px-4 py-3"><x-health-status-badge :status="$record->severity_level" /></td>
                                <td class="px-4 py-3"><x-health-status-badge :status="$record->recovery_status" /></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('farmer.health.diseases.show', $record) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
