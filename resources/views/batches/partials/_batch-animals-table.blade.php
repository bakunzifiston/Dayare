@php
    $totalMeat = (float) $batchItems->sum('meat_quantity_kg');
    $yieldValues = $batchItems->map(function ($item) {
        $live = $item->intakeItem->live_weight_kg ?? null;
        if (! $live || $live <= 0) {
            return null;
        }

        return round($item->meat_quantity_kg / $live * 100, 1);
    })->filter(fn ($v) => $v !== null);
    $avgYield = $yieldValues->isNotEmpty() ? round($yieldValues->avg(), 1) : null;
@endphp
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h3 class="text-lg font-medium text-gray-900">{{ __('Animals in batch') }}</h3>
        <p class="text-sm text-gray-600">
            {{ $batchItems->count() }} {{ __('animals') }}
            — {{ __('Total') }}: <strong>{{ number_format($totalMeat, 2) }} kg</strong>
            @if ($avgYield !== null)
                — {{ __('Avg yield') }}: <strong>{{ $avgYield }}%</strong>
            @endif
        </p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500">
                    <th class="pb-1 px-2">#</th>
                    <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                    <th class="pb-1 px-2">{{ __('Species') }}</th>
                    <th class="pb-1 px-2">{{ __('Sex') }}</th>
                    <th class="pb-1 px-2">{{ __('Live weight') }}</th>
                    <th class="pb-1 px-2">{{ __('Meat qty') }}</th>
                    <th class="pb-1 px-2">{{ __('Yield %') }}</th>
                    <th class="pb-1 px-2">{{ __('PM outcome') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batchItems as $batchItem)
                    @php
                        $intake = $batchItem->intakeItem;
                        $yieldPct = ($intake->live_weight_kg && $intake->live_weight_kg > 0)
                            ? round($batchItem->meat_quantity_kg / $intake->live_weight_kg * 100, 1)
                            : null;
                        $outcome = $batchItem->postMortemOutcome?->outcome;
                        $outcomeBadge = match ($outcome) {
                            'approved' => 'bg-green-100 text-green-800',
                            'condemned' => 'bg-red-100 text-red-800',
                            'deferred' => 'bg-yellow-100 text-yellow-800',
                            default => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <tr class="border-t border-gray-100">
                        <td class="py-1 px-2 text-gray-400">{{ $loop->iteration }}</td>
                        <td class="py-1 px-2 font-mono text-xs">
                            {{ $intake->ear_tag }}
                            @if (str_starts_with($intake->ear_tag, 'LEGACY-'))
                                <span class="ml-1 text-xs text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
                            @endif
                        </td>
                        <td class="py-1 px-2">{{ $intake->species }}</td>
                        <td class="py-1 px-2">{{ ucfirst($intake->sex) }}</td>
                        <td class="py-1 px-2">
                            {{ $intake->live_weight_kg ? number_format($intake->live_weight_kg, 2).' kg' : '—' }}
                        </td>
                        <td class="py-1 px-2 font-medium">{{ number_format($batchItem->meat_quantity_kg, 2) }} kg</td>
                        <td class="py-1 px-2">{{ $yieldPct !== null ? $yieldPct.'%' : '—' }}</td>
                        <td class="py-1 px-2">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $outcomeBadge }}">
                                {{ $outcome ? ucfirst($outcome) : __('Pending') }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-gray-200 bg-gray-50">
                    <td colspan="5" class="py-2 px-2 text-xs font-medium text-gray-600">{{ __('Totals') }}</td>
                    <td class="py-2 px-2 font-medium">{{ number_format($totalMeat, 2) }} kg</td>
                    <td class="py-2 px-2">{{ $avgYield !== null ? $avgYield.'%' : '—' }}</td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $batchItems->count() }} {{ __('animals') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
