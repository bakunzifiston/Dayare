@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.stock-counts.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Stock counts') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $count->count_number }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $count->outlet?->name ?? __('All outlets') }} · {{ $count->count_date?->toDateString() }} · {{ $count->countedByUser?->name }}
                </p>
            </div>
            <x-butcher.status-badge :status="$count->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('butcher.stock-counts.lines.update', $count) }}">
                @csrf
                @method('PUT')
                <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Batch') }}</th>
                                <th class="px-4 py-3">{{ __('Meat') }}</th>
                                <th class="px-4 py-3">{{ __('System (kg)') }}</th>
                                <th class="px-4 py-3">{{ __('Counted (kg)') }}</th>
                                <th class="px-4 py-3">{{ __('Variance') }}</th>
                                <th class="px-4 py-3">{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($count->lines as $index => $line)
                                <tr>
                                    <td class="px-4 py-3 font-medium">
                                        {{ $line->batch?->batch_number }}
                                        <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                    </td>
                                    <td class="px-4 py-3 capitalize">{{ $line->batch?->meat_type }}</td>
                                    <td class="px-4 py-3">{{ $fmtKg($line->system_weight_kg) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($count->isDraft())
                                            <input type="number" step="0.001" min="0" name="lines[{{ $index }}][counted_weight_kg]" value="{{ old('lines.'.$index.'.counted_weight_kg', $line->counted_weight_kg) }}" class="w-28 rounded-lg border-gray-300 text-sm">
                                        @else
                                            {{ $fmtKg($line->counted_weight_kg) }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 @if($line->variance_kg !== null && abs((float)$line->variance_kg) > 0.001) font-semibold text-amber-800 @endif">
                                        {{ $line->variance_kg === null ? '—' : (((float)$line->variance_kg > 0 ? '+' : '').$fmtKg($line->variance_kg)) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($count->isDraft())
                                            <input type="text" name="lines[{{ $index }}][notes]" value="{{ old('lines.'.$index.'.notes', $line->notes) }}" class="w-full rounded-lg border-gray-300 text-sm">
                                        @else
                                            {{ $line->notes ?: '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                @if ($count->isDraft())
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <button type="submit" class="rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Save counted weights') }}</button>
                    </div>
                @endif
            </form>

            @if ($count->isDraft())
                <form method="post" action="{{ route('butcher.stock-counts.complete', $count) }}" class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha space-y-3" onsubmit="return confirm(@js(__('Complete this stock count?')))">
                    @csrf
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="apply_variances" value="1" class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary">
                        {{ __('Apply variances as inventory adjustments') }}
                    </label>
                    <div>
                        <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Complete count') }}</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
