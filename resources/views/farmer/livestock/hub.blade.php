<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Livestock') }}</span>
    </x-slot>

    <div class="max-w-5xl space-y-4">
        <p class="text-sm text-slate-600">{{ __('All livestock across your farms. Use a farm’s page to add or edit rows.') }}</p>

        @if ($rows->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No livestock yet.') }}
                <a href="{{ route('farmer.farms.index') }}" class="text-bucha-primary hover:underline">{{ __('Go to farms') }}</a>
            </p>
        @else
            <div class="rounded-bucha border border-slate-200/80 bg-white px-4 py-3 text-sm shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Animals by healthy vs sick (all your farms)') }}</p>
                <div class="flex flex-wrap gap-x-6 gap-y-1">
                    <span><span class="text-slate-500">{{ __('Healthy') }}</span> <strong class="text-emerald-800 tabular-nums">{{ $healthHeadcounts['healthy'] }}</strong></span>
                    <span><span class="text-slate-500">{{ __('Sick') }}</span> <strong class="text-red-800 tabular-nums">{{ $healthHeadcounts['sick'] }}</strong></span>
                    @if ($healthHeadcounts['unrecorded'] > 0)
                        <span><span class="text-slate-500">{{ __('Unassigned') }}</span> <strong class="text-amber-800 tabular-nums">{{ $healthHeadcounts['unrecorded'] }}</strong></span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-2">{{ __('Farm') }}</th>
                            <th class="px-4 py-2">{{ __('Type') }}</th>
                            <th class="px-4 py-2">{{ __('Breed') }}</th>
                            <th class="px-4 py-2">{{ __('Healthy / sick') }}</th>
                            <th class="px-4 py-2">{{ __('Quality') }}</th>
                            <th class="px-4 py-2">{{ __('Total') }}</th>
                            <th class="px-4 py-2">{{ __('Available') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            @php
                                $qs = $row->qualityScore();
                            @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <a href="{{ route('farmer.farms.show', $row->farm) }}" class="text-bucha-primary hover:underline">{{ $row->farm?->name }}</a>
                                </td>
                                <td class="px-4 py-2">{{ \App\Support\FarmerAnimalType::label($row->type) }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ $row->breed !== '' ? $row->breed : '—' }}</td>
                                <td class="px-4 py-2 tabular-nums">
                                    <span class="text-emerald-800 font-medium">{{ (int) $row->healthy_quantity }}</span>
                                    <span class="text-slate-400">/</span>
                                    <span class="text-red-800 font-medium">{{ (int) $row->sick_quantity }}</span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-xs font-bold uppercase rounded px-2 py-0.5 {{ $qs['tier'] === 'A' ? 'bg-emerald-100 text-emerald-900' : ($qs['tier'] === 'B' ? 'bg-amber-100 text-amber-900' : 'bg-slate-200 text-slate-800') }}" title="{{ __('Quality score') }}">{{ $qs['tier'] }}</span>
                                </td>
                                <td class="px-4 py-2">{{ $row->total_quantity }}</td>
                                <td class="px-4 py-2">{{ $row->available_quantity }}</td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('farmer.farms.livestock.index', $row->farm) }}" class="text-bucha-primary hover:underline">{{ __('Manage') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $rows->links() }}</div>
        @endif
    </div>
</x-app-layout>
