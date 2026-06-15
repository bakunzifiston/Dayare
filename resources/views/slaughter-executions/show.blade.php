@php
    use App\Models\SlaughterExecution;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('slaughter-executions.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Slaughter execution') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Slaughter execution') }} — {{ $execution->slaughter_time->format('d M Y H:i') }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_CREATE_BATCH))
                    <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        {{ __('Create batch') }}
                    </a>
                @endif
                <a href="{{ route('slaughter-executions.edit', $execution) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('slaughter-executions.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('All executions') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter session ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('slaughter-plans.show', $execution->slaughterPlan) }}" class="text-bucha-primary hover:underline">
                                #{{ $execution->slaughter_plan_id }} — {{ $execution->slaughterPlan->slaughter_date->format('d M Y') }} ({{ $execution->slaughterPlan->facility->facility_name }})
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter time') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $execution->slaughter_time->format('l, d M Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Actual animals slaughtered') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $execution->actual_animals_slaughtered }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $execution->status)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Count source') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($execution->slaughter_count_source === SlaughterExecution::SOURCE_ITEMS)
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">{{ __('From items') }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('Manual') }}</span>
                            @endif
                        </dd>
                    </div>
                    @if ($execution->total_meat_quantity_kg > 0)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Total meat yield') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ number_format($execution->total_meat_quantity_kg, 2) }} kg</dd>
                        </div>
                    @endif
                </dl>

                @if ($execution->slaughterPlan->anteMortemInspections->isNotEmpty())
                    @php $latestAM = $execution->slaughterPlan->anteMortemInspections->last(); @endphp
                    <div class="mt-4 rounded bg-gray-50 border border-gray-200 p-3">
                        <p class="text-xs font-medium text-gray-600 mb-1">{{ __('Ante-mortem inspection') }}</p>
                        <div class="flex flex-wrap gap-6 text-sm">
                            <span>{{ __('Date:') }} <strong>{{ $latestAM->inspection_date->format('d M Y') }}</strong></span>
                            <span class="text-green-700">{{ __('Approved:') }} <strong>{{ $latestAM->number_approved }}</strong></span>
                            <span class="text-red-700">{{ __('Rejected:') }} <strong>{{ $latestAM->number_rejected }}</strong></span>
                            <a href="{{ route('ante-mortem-inspections.show', $latestAM) }}"
                               class="text-blue-600 hover:underline text-xs self-center">{{ __('View inspection →') }}</a>
                        </div>
                    </div>
                    @if ($execution->exceedsAnteMortemWindow())
                        <div class="mt-3 rounded bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900">
                            <p class="font-medium">{{ __('Ante-mortem window notice') }}</p>
                            <p class="mt-1">{{ $execution->anteMortemWindowReportNote() }}</p>
                        </div>
                    @elseif ($note = $execution->anteMortemWindowReportNote())
                        <div class="mt-3 rounded bg-yellow-50 border border-yellow-200 p-3 text-sm text-yellow-900">
                            <p>{{ $note }}</p>
                        </div>
                    @endif
                @endif

                @if ($execution->hasPerAnimalSlaughter())
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700">
                                {{ __('Individual animal slaughter') }}
                                ({{ $execution->executionItems->count() }} {{ __('animals') }})
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ __('Total yield:') }}
                                <strong>{{ number_format($execution->total_meat_quantity_kg, 2) }} kg</strong>
                            </p>
                        </div>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                                    <th class="pb-1 px-2">{{ __('Species') }}</th>
                                    <th class="pb-1 px-2">{{ __('Sex') }}</th>
                                    <th class="pb-1 px-2">{{ __('Live weight') }}</th>
                                    <th class="pb-1 px-2">{{ __('Meat qty') }}</th>
                                    <th class="pb-1 px-2">{{ __('Yield %') }}</th>
                                    <th class="pb-1 px-2">{{ __('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($execution->executionItems as $execItem)
                                @php
                                    $intake   = $execItem->intakeItem;
                                    $yieldPct = ($intake->live_weight_kg && $intake->live_weight_kg > 0)
                                        ? round($execItem->meat_quantity_kg / $intake->live_weight_kg * 100, 1)
                                        : null;
                                @endphp
                                <tr class="border-t border-gray-100">
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
                                    <td class="py-1 px-2 font-medium">
                                        {{ number_format($execItem->meat_quantity_kg, 2) }} kg
                                    </td>
                                    <td class="py-1 px-2">
                                        {{ $yieldPct !== null ? $yieldPct.'%' : '—' }}
                                    </td>
                                    <td class="py-1 px-2 text-gray-500">{{ $execItem->notes ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-400">
                        {{ __('No individual animal slaughter data recorded.') }}
                        <a href="{{ route('slaughter-executions.edit', $execution) }}"
                           class="text-blue-600 hover:underline">{{ __('Edit this execution') }}</a>
                        {{ __('to add per-animal meat quantities.') }}
                    </p>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Batches') }}</h3>
                    @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_CREATE_BATCH))
                        <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}"
                           class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                            {{ __('Add batch') }}
                        </a>
                    @endif
                </div>
                @if ($execution->batches->isNotEmpty())
                    <ul class="divide-y divide-gray-200">
                        @foreach ($execution->batches as $b)
                            <li class="py-2">
                                <a href="{{ route('batches.show', $b) }}" class="font-medium text-bucha-primary hover:underline">{{ $b->batch_code }}</a>
                                <span class="text-sm text-gray-500"> {{ $b->species }} · {{ $b->quantity }} · {{ ucfirst($b->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mb-3">{{ __('No batches created for this execution yet.') }}</p>
                    @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_CREATE_BATCH))
                        <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}"
                           class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                            <i class="ti ti-box text-base" aria-hidden="true"></i>
                            {{ __('Create batch from this execution') }}
                        </a>
                    @endif
                @endif

                @if ($execution->hasPerAnimalSlaughter())
                    @php
                        $batchedIds = $execution->batches->flatMap(fn ($b) => $b->items->pluck('animal_intake_item_id'))->toArray();
                        $unbatchedCount = $execution->executionItems->whereNotIn('animal_intake_item_id', $batchedIds)->count();
                    @endphp
                    @if ($unbatchedCount > 0)
                        <div class="mt-2 rounded bg-amber-50 border border-amber-200 p-2 text-sm text-amber-800">
                            {{ trans_choice(':count animal from this execution is not yet in a batch.|:count animals from this execution are not yet in a batch.', $unbatchedCount, ['count' => $unbatchedCount]) }}
                            <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}"
                               class="underline ml-1">{{ __('Create another batch →') }}</a>
                        </div>
                    @endif
                @endif
            </div>

            @if (auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER))
                <form method="post" action="{{ route('slaughter-executions.destroy', $execution) }}"
                      class="mt-6"
                      onsubmit="return confirm(@json(__('Delete this slaughter execution? This cannot be undone if batches exist.')));">
                    @csrf
                    @method('delete')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                        {{ __('Delete execution') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
