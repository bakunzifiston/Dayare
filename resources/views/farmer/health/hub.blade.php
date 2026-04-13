<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Health visit log') }}</span>
    </x-slot>

    <div class="max-w-5xl space-y-4">
        <p class="text-sm text-slate-600">{{ __('Track vaccination, treatment, and disease diagnosis events across all farms.') }}</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-bucha border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm font-semibold text-amber-900">{{ __('Upcoming vaccinations') }}</p>
                <p class="mt-1 text-xs text-amber-800">{{ __(':count due in next 14 days', ['count' => $upcomingVaccinations->count()]) }}</p>
            </div>
            <div class="rounded-bucha border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-900">{{ __('Sick animals') }}</p>
                <p class="mt-1 text-xs text-red-800">{{ __(':count livestock rows currently include sick quantity', ['count' => $sickRows->count()]) }}</p>
            </div>
        </div>

        @if ($records->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No health records yet.') }}
                <a href="{{ route('farmer.farms.index') }}" class="text-bucha-primary hover:underline">{{ __('Go to farms') }}</a>
            </p>
        @else
            <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-2">{{ __('Date') }}</th>
                            <th class="px-4 py-2">{{ __('Event') }}</th>
                            <th class="px-4 py-2">{{ __('Farm') }}</th>
                            <th class="px-4 py-2">{{ __('Condition') }}</th>
                            <th class="px-4 py-2">{{ __('Animal / batch') }}</th>
                            <th class="px-4 py-2">{{ __('Next due date') }}</th>
                            <th class="px-4 py-2">{{ __('Notes') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $r)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->record_date?->toDateString() }}</td>
                                <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $r->event_type) }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('farmer.farms.show', $r->farm) }}" class="text-bucha-primary hover:underline">{{ $r->farm?->name }}</a>
                                </td>
                                <td class="px-4 py-2 capitalize">{{ $r->condition }}</td>
                                <td class="px-4 py-2 text-slate-600">
                                    @if ($r->livestock_id)
                                        <span class="capitalize">{{ $r->livestock?->type ?? '—' }}</span>
                                        <span class="text-slate-400">#{{ $r->livestock_id }}</span>
                                    @else
                                        <span>{{ $r->batch_reference ?: '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-slate-600">{{ $r->next_due_date?->toDateString() ?? '—' }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ \Illuminate\Support\Str::limit($r->notes ?? '', 60) }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('farmer.farms.health-records.index', $r->farm) }}" class="text-bucha-primary hover:underline">{{ __('Open farm') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
