<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record post-mortem inspection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('One post-mortem inspection per batch. Select a batch to filter animals. If no batch items exist, slaughtered animals from the linked execution are shown.') }}</p>

                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-medium">{{ __('Please fix the following before saving:') }}</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('post-mortem-inspections.store') }}" class="space-y-6" id="post-mortem-form" novalidate>
                    @csrf

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch') }}</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b['id'] }}" data-facility-id="{{ $b['facility_id'] }}" data-species="{{ $b['species'] }}" @selected(old('batch_id', $selectedBatchId ?? null) == $b['id'])>{{ $b['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
                    </div>

                    <div>
                        <x-input-label for="species" :value="__('Species')" />
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @php($speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect())
                            <option value="">{{ __('Select species') }}</option>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species', is_array($selectedBatchData ?? null) ? ($selectedBatchData['species'] ?? null) : null) === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Species is auto-filled from the selected batch.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}" @selected((string) old('inspector_id') === (string) $insp['id'])>{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspection_date" :value="__('Inspection date')" />
                        <x-text-input id="inspection_date" name="inspection_date" type="date" class="mt-1 block w-full" :value="old('inspection_date', date('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('inspection_date')" />
                    </div>

                    <p id="animal-source-notice"
                       class="@if (! is_array($selectedBatchData ?? null) || ($selectedBatchData['source'] ?? '') !== 'execution') hidden @endif rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        @if (is_array($selectedBatchData ?? null) && ($selectedBatchData['source'] ?? '') === 'execution')
                            {{ __('Showing slaughtered animals from the linked execution because this batch has no individual animal rows yet.') }}
                        @endif
                    </p>

                    <div id="per-animal-outcomes-section"
                         class="rounded-lg border border-slate-200 bg-white @if (! ($selectedBatchData['has_per_animal'] ?? false)) hidden @endif">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">{{ __('Individual animal post-mortem') }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Record an outcome and complete the checklist for each animal.') }}</p>
                                </div>
                                <div id="per-animal-aggregate-summary"
                                     class="@if (! ($selectedBatchData['has_per_animal'] ?? false)) hidden @endif grid min-w-[14rem] grid-cols-3 gap-2 sm:gap-3">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-center sm:px-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ __('Total meat examined') }}</p>
                                        <p class="text-lg font-semibold tabular-nums text-slate-900"><span id="pm-summary-examined">{{ number_format((float) old('total_examined', 0), 2) }}</span> <span class="text-xs font-normal text-slate-500">kg</span></p>
                                    </div>
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-2 text-center sm:px-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wide text-emerald-700">{{ __('Approved meat') }}</p>
                                        <p class="text-lg font-semibold tabular-nums text-emerald-800"><span id="pm-summary-approved">{{ number_format((float) old('approved_quantity', 0), 2) }}</span> <span class="text-xs font-normal text-emerald-600">kg</span></p>
                                    </div>
                                    <div class="rounded-lg border border-red-200 bg-red-50 px-2 py-2 text-center sm:px-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wide text-red-700">{{ __('Rejected meat') }}</p>
                                        <p class="text-lg font-semibold tabular-nums text-red-800"><span id="pm-summary-condemned">{{ number_format((float) old('condemned_quantity', 0), 2) }}</span> <span class="text-xs font-normal text-red-600">kg</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="per-animal-outcomes-container" class="p-4">
                            @if (($selectedBatchData['has_per_animal'] ?? false) && ! empty($selectedBatchData['animals']))
                                @include('post-mortem-inspections.partials._per-animal-outcomes', [
                                    'animals' => $selectedBatchData['animals'],
                                    'species' => old('species', $selectedBatchData['species'] ?? ''),
                                    'inspectionItems' => collect(),
                                    'existingInspectionOutcomes' => $existingInspectionOutcomes ?? [],
                                ])
                            @else
                                <p class="text-sm text-gray-500">{{ __('Select a batch to load animals for individual post-mortem inspection.') }}</p>
                            @endif
                        </div>
                        <x-input-error class="px-4 pb-3" :messages="$errors->get('item_outcomes')" />
                    </div>

                    <div id="legacy-checklist-section" class="@if ($selectedBatchData['has_per_animal'] ?? false) hidden @endif space-y-6">
                        <div>
                            <h3 class="text-base font-semibold text-slate-800">{{ __('Carcass inspection') }}</h3>
                            <div class="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="carcass-checklist-body" class="divide-y divide-slate-100 bg-white"></tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-base font-semibold text-slate-800">{{ __('Organ inspection') }}</h3>
                            <div class="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="organ-checklist-body" class="divide-y divide-slate-100 bg-white"></tbody>
                                </table>
                            </div>
                            <p id="checklist-empty" class="mt-2 text-xs text-slate-500 hidden">{{ __('No checklist configured for this species.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('observations')" />
                        </div>

                        <div>
                            <h3 class="text-base font-semibold text-slate-800">{{ __('Decision & comment') }}</h3>
                            <div class="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="decision-checklist-body" class="divide-y divide-slate-100 bg-white"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div id="aggregate-counts-section" class="grid grid-cols-1 gap-4 sm:grid-cols-3 @if ($selectedBatchData['has_per_animal'] ?? false) hidden @endif">
                        <div>
                            <x-input-label for="total_examined" :value="__('Total examined')" />
                            <x-text-input id="total_examined" name="total_examined" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('total_examined', $defaultTotalExamined ?? 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('total_examined')" />
                        </div>
                        <div>
                            <x-input-label for="approved_quantity" :value="__('Approved quantity')" />
                            <x-text-input id="approved_quantity" name="approved_quantity" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('approved_quantity', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('approved_quantity')" />
                        </div>
                        <div>
                            <x-input-label for="condemned_quantity" :value="__('Condemned quantity')" />
                            <x-text-input id="condemned_quantity" name="condemned_quantity" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('condemned_quantity', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('condemned_quantity')" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">{{ __('Approved + Condemned cannot exceed Total Examined.') }}</p>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save inspection') }}</x-primary-button>
                        <a href="{{ route('post-mortem-inspections.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('post-mortem-inspections.partials.form-batch-scripts', [
        'batchAnimalsByBatchId' => $batchAnimalsByBatchId,
        'checklists' => $checklists,
        'existingInspectionOutcomes' => $existingInspectionOutcomes ?? [],
        'preserveExistingOutcomes' => $preserveExistingOutcomes ?? false,
    ])
</x-app-layout>
