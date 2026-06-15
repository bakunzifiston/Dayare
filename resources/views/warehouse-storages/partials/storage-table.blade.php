@props([
    'storages',
    'showPagination' => false,
    'emptyMessage' => null,
    'embedded' => false,
])

@php
    $emptyMessage = $emptyMessage ?? __('No cold room storage records yet.');
    $wrapperClass = $embedded
        ? 'bg-white'
        : 'bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60';
@endphp

<div class="{{ $wrapperClass }}">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Ear tag') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Animal') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Batch') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Cold room') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Entry date') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('Meat stored') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($storages as $s)
                    @php
                        $animal = $s->resolvedIntakeItem();
                    @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3">
                            <p class="font-mono text-sm font-medium text-slate-900">
                                {{ $animal?->ear_tag ?: '—' }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($animal)
                                <p class="text-sm text-slate-900">{{ $animal->species }}{{ $animal->sex ? ' · '.ucfirst($animal->sex) : '' }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ __('Live weight (before slaughter)') }}:
                                    <span class="font-medium text-slate-700 tabular-nums">
                                        {{ $animal->live_weight_kg ? number_format((float) $animal->live_weight_kg, 2).' kg' : '—' }}
                                    </span>
                                </p>
                                @if ($s->postMortemInspectionItem?->carcass_weight_kg)
                                    <p class="text-xs text-slate-500">
                                        {{ __('Carcass (post-mortem)') }}:
                                        <span class="tabular-nums">{{ number_format((float) $s->postMortemInspectionItem->carcass_weight_kg, 2) }} kg</span>
                                    </p>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $s->batch->batch_code ?? '—' }}</p>
                            @if ($s->certificate)
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $s->certificate->certificate_number ?: '#'.$s->certificate_id }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <p>{{ $s->coldRoom?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $s->warehouseFacility->facility_name ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $s->entry_date->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-900 tabular-nums whitespace-nowrap">
                            {{ number_format((float) $s->quantity_stored, 2) }} {{ $s->quantity_unit_label }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusClass = match ($s->status) {
                                    \App\Models\WarehouseStorage::STATUS_IN_STORAGE => 'bg-emerald-100 text-emerald-800',
                                    \App\Models\WarehouseStorage::STATUS_RELEASED => 'bg-slate-100 text-slate-700',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <span class="inline-flex text-xs px-2 py-0.5 rounded-full {{ $statusClass }}">
                                {{ ucfirst(str_replace('_', ' ', $s->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('warehouse-storages.show', $s) }}" class="text-bucha-primary hover:text-bucha-burgundy font-medium">{{ __('View') }}</a>
                                <a href="{{ route('warehouse-storages.edit', $s) }}" class="text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                <form method="post" action="{{ route('warehouse-storages.destroy', $s) }}" class="inline"
                                      onsubmit="return confirm(@json(__('Delete this storage record? The animal will be available to store again.')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($showPagination && method_exists($storages, 'links'))
        <div class="px-4 py-3 border-t border-slate-100">{{ $storages->links() }}</div>
    @endif
</div>
