@php
    use App\Models\SlaughterExecution;
@endphp
<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Slaughter execution') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $execution->slaughter_time->format('d M Y H:i') }}</p>
                    <p class="text-xs text-slate-500">{{ $execution->actual_animals_slaughtered }} {{ __('animals') }} · {{ ucfirst(str_replace('_', ' ', $execution->status)) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_CREATE_BATCH))
                        <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Create batch') }}</a>
                    @endif
                    <a href="{{ route('slaughter-executions.edit', $execution) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <a href="{{ route('slaughter-executions.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Slaughter session ID') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('slaughter-plans.show', $execution->slaughterPlan) }}" class="text-bucha-primary hover:underline">
                            #{{ $execution->slaughter_plan_id }} — {{ $execution->slaughterPlan->slaughter_date->format('d M Y') }} ({{ $execution->slaughterPlan->facility->facility_name }})
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Slaughter time') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $execution->slaughter_time->format('l, d M Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Actual animals slaughtered') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $execution->actual_animals_slaughtered }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $execution->status)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Count source') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        @if ($execution->slaughter_count_source === SlaughterExecution::SOURCE_ITEMS)
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('From items') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('Manual') }}</span>
                        @endif
                    </dd>
                </div>
                @if ($execution->total_meat_quantity_kg > 0)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Total meat yield') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium tabular-nums text-slate-900">{{ number_format($execution->total_meat_quantity_kg, 2) }} kg</dd>
                    </div>
                @endif
            </dl>

            @if ($execution->slaughterPlan->anteMortemInspections->isNotEmpty())
                @php $latestAM = $execution->slaughterPlan->anteMortemInspections->last(); @endphp
                <div class="mx-4 mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="mb-1 text-xs font-medium text-slate-500">{{ __('Ante-mortem inspection') }}</p>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-slate-700">
                        <span>{{ __('Date:') }} <strong>{{ $latestAM->inspection_date->format('d M Y') }}</strong></span>
                        <span class="text-emerald-700">{{ __('Approved:') }} <strong>{{ $latestAM->number_approved }}</strong></span>
                        <span class="text-red-700">{{ __('Rejected:') }} <strong>{{ $latestAM->number_rejected }}</strong></span>
                        <a href="{{ route('ante-mortem-inspections.show', $latestAM) }}" class="text-xs font-medium text-bucha-primary hover:underline">{{ __('View inspection') }}</a>
                    </div>
                </div>
                @if ($execution->exceedsAnteMortemWindow())
                    <div class="mx-4 mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <p class="font-medium">{{ __('Ante-mortem window notice') }}</p>
                        <p class="mt-1">{{ $execution->anteMortemWindowReportNote() }}</p>
                    </div>
                @elseif ($note = $execution->anteMortemWindowReportNote())
                    <div class="mx-4 mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <p>{{ $note }}</p>
                    </div>
                @endif
            @endif

            @if ($execution->hasPerAnimalSlaughter())
                <div class="border-t border-slate-100 px-4 py-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ __('Individual animal slaughter') }}
                            <span class="font-normal text-slate-500">({{ $execution->executionItems->count() }} {{ __('animals') }})</span>
                        </p>
                        <p class="text-sm text-slate-600">
                            {{ __('Total yield:') }}
                            <strong class="tabular-nums">{{ number_format($execution->total_meat_quantity_kg, 2) }} kg</strong>
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-slate-500">
                                    <th class="px-2 pb-1">{{ __('Ear tag') }}</th>
                                    <th class="px-2 pb-1">{{ __('Species') }}</th>
                                    <th class="px-2 pb-1">{{ __('Sex') }}</th>
                                    <th class="px-2 pb-1">{{ __('Live weight') }}</th>
                                    <th class="px-2 pb-1">{{ __('Meat qty') }}</th>
                                    <th class="px-2 pb-1">{{ __('Yield %') }}</th>
                                    <th class="px-2 pb-1">{{ __('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($execution->executionItems as $execItem)
                                    @php
                                        $intake = $execItem->intakeItem;
                                        $yieldPct = ($intake->live_weight_kg && $intake->live_weight_kg > 0)
                                            ? round($execItem->meat_quantity_kg / $intake->live_weight_kg * 100, 1)
                                            : null;
                                    @endphp
                                    <tr class="border-t border-slate-100">
                                        <td class="px-2 py-1.5 font-mono text-xs">
                                            {{ $intake->ear_tag }}
                                            @if (str_starts_with($intake->ear_tag, 'LEGACY-'))
                                                <span class="ml-1 rounded bg-slate-100 px-1 text-xs text-slate-400">[legacy]</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-1.5">{{ $intake->species }}</td>
                                        <td class="px-2 py-1.5">{{ ucfirst($intake->sex) }}</td>
                                        <td class="px-2 py-1.5 tabular-nums">{{ $intake->live_weight_kg ? number_format($intake->live_weight_kg, 2).' kg' : '—' }}</td>
                                        <td class="px-2 py-1.5 font-medium tabular-nums">{{ number_format($execItem->meat_quantity_kg, 2) }} kg</td>
                                        <td class="px-2 py-1.5">{{ $yieldPct !== null ? $yieldPct.'%' : '—' }}</td>
                                        <td class="px-2 py-1.5 text-slate-500">{{ $execItem->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <p class="border-t border-slate-100 px-4 py-4 text-sm text-slate-500">
                    {{ __('No individual animal slaughter data recorded.') }}
                    <a href="{{ route('slaughter-executions.edit', $execution) }}" class="text-bucha-primary hover:underline">{{ __('Edit this execution') }}</a>
                    {{ __('to add per-animal meat quantities.') }}
                </p>
            @endif
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Batches') }}</p>
                @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_CREATE_BATCH))
                    <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Add batch') }}</a>
                @endif
            </div>
            @if ($execution->batches->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach ($execution->batches as $b)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                            <a href="{{ route('batches.show', $b) }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ $b->batch_code }}</a>
                            <span class="text-xs text-slate-500">{{ $b->species }} · {{ $b->quantity }} · {{ ucfirst($b->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="px-4 py-8 text-center">
                    <p class="text-sm text-slate-500">{{ __('No batches created for this execution yet.') }}</p>
                    @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_CREATE_BATCH))
                        <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}" class="mt-3 inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Create batch from this execution') }}</a>
                    @endif
                </div>
            @endif

            @if ($execution->hasPerAnimalSlaughter())
                @php
                    $batchedIds = $execution->batches->flatMap(fn ($b) => $b->items->pluck('animal_intake_item_id'))->toArray();
                    $unbatchedCount = $execution->executionItems->whereNotIn('animal_intake_item_id', $batchedIds)->count();
                @endphp
                @if ($unbatchedCount > 0)
                    <div class="border-t border-slate-100 px-4 py-3 text-sm text-amber-800">
                        {{ trans_choice(':count animal from this execution is not yet in a batch.|:count animals from this execution are not yet in a batch.', $unbatchedCount, ['count' => $unbatchedCount]) }}
                        <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}" class="ml-1 font-medium text-bucha-primary hover:underline">{{ __('Create another batch') }}</a>
                    </div>
                @endif
            @endif
        </section>

        @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER))
            <form method="post" action="{{ route('slaughter-executions.destroy', $execution) }}"
                  onsubmit="return confirm(@json(__('Delete this slaughter execution? This cannot be undone if batches exist.')));">
                @csrf
                @method('delete')
                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete execution') }}</button>
            </form>
        @endif
    </div>
</x-app-layout>
