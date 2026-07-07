<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit post-mortem inspection') }} — {{ $inspection->batch->batch_code }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
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

                <form method="post" action="{{ route('post-mortem-inspections.update', $inspection) }}" class="space-y-6" id="post-mortem-edit-form" novalidate>
                    @csrf
                    @method('put')

                    <input type="hidden" name="batch_id" value="{{ $inspection->batch_id }}">

                    <div>
                        <x-input-label :value="__('Slaughter execution')" />
                        <p class="mt-1 text-sm text-slate-900">
                            {{ $inspection->batch->slaughterExecution->slaughter_time->format('d M Y H:i') }}
                            — {{ $inspection->batch->slaughterExecution->slaughterPlan->facility->facility_name }}
                            ({{ $inspection->batch->species }})
                        </p>
                    </div>

                    <div>
                        <x-input-label for="species" :value="__('Species')" />
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @php($speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect())
                            <option value="">{{ __('Select species') }}</option>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species', $inspection->species ?? $inspection->batch->species) === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select inspector') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}" @selected(old('inspector_id', $inspection->inspector_id) == $insp['id'])>{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspection_date" :value="__('Inspection date')" />
                        <x-text-input id="inspection_date" name="inspection_date" type="date" class="mt-1 block w-full" :value="old('inspection_date', $inspection->inspection_date?->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('inspection_date')" />
                    </div>

                    <input type="hidden" id="slaughter_execution_id" value="{{ $selectedExecutionId }}">

                    <div id="per-animal-outcomes-section" @class(['rounded-lg border border-slate-200 bg-white', 'hidden' => ! $hasPerAnimal])>
                        <div class="border-b border-slate-200 px-4 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">{{ __('Individual animal post-mortem') }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Animals from this slaughter execution are listed below. Update outcomes or complete pending animals.') }}</p>
                                </div>
                                <div id="per-animal-aggregate-summary" @class(['grid min-w-[14rem] grid-cols-3 gap-2 sm:gap-3', 'hidden' => ! $hasPerAnimal])>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-center sm:px-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ __('Total meat examined') }}</p>
                                        <p class="text-lg font-semibold tabular-nums text-slate-900"><span id="pm-summary-examined">{{ number_format((float) old('total_examined', $inspection->total_examined), 2) }}</span> <span class="text-xs font-normal text-slate-500">kg</span></p>
                                    </div>
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-2 text-center sm:px-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wide text-emerald-700">{{ __('Approved meat') }}</p>
                                        <p class="text-lg font-semibold tabular-nums text-emerald-800"><span id="pm-summary-approved">{{ number_format((float) old('approved_quantity', $inspection->approved_quantity), 2) }}</span> <span class="text-xs font-normal text-emerald-600">kg</span></p>
                                    </div>
                                    <div class="rounded-lg border border-red-200 bg-red-50 px-2 py-2 text-center sm:px-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wide text-red-700">{{ __('Rejected meat') }}</p>
                                        <p class="text-lg font-semibold tabular-nums text-red-800"><span id="pm-summary-condemned">{{ number_format((float) old('condemned_quantity', $inspection->condemned_quantity), 2) }}</span> <span class="text-xs font-normal text-red-600">kg</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="execution-animals-roster" @class(['border-b border-slate-200 bg-white px-4 py-4', 'hidden' => ! $hasPerAnimal])>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Animals in this slaughter execution') }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                <span id="execution-animals-count">{{ $pmExecutionData['animal_count'] ?? 0 }}</span>
                                {{ __('animal(s) slaughtered — pending animals are loaded for inspection below.') }}
                            </p>
                            @include('post-mortem-inspections.partials._execution-animals-roster', [
                                'executionAnimals' => $executionAnimals,
                                'inspectedAnimalIds' => $inspectedAnimalIds,
                            ])
                        </div>

                        <div id="animal-tag-lookup" class="hidden border-b border-slate-200 bg-slate-50 px-4 py-4">
                            <x-input-label for="animal_tag_search" :value="__('Ear tag or tag number')" />
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-text-input id="animal_tag_search" type="text" class="block min-w-[12rem] flex-1" placeholder="{{ __('Scan or type tag…') }}" autocomplete="off" />
                                <button type="button" id="add-animal-by-tag" class="inline-flex items-center rounded-md border border-transparent bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-bucha-burgundy focus:outline-none focus:ring-2 focus:ring-bucha-primary focus:ring-offset-2">
                                    {{ __('Add animal') }}
                                </button>
                            </div>
                            <p id="animal-tag-feedback" class="mt-2 hidden text-sm"></p>
                            <p class="mt-2 text-xs text-slate-500">{{ __('Pending animals for this execution:') }} <span id="pending-animal-count">{{ $pmExecutionData['pending_count'] ?? 0 }}</span></p>
                        </div>

                        <div id="per-animal-outcomes-container" class="p-4">
                            @if ($hasPerAnimal && ! empty($displayAnimals))
                                @include('post-mortem-inspections.partials._per-animal-outcomes', [
                                    'animals' => $displayAnimals,
                                    'species' => old('species', $inspection->species ?? $inspection->batch->species),
                                    'inspectionItems' => $inspection->inspectionItems,
                                    'existingInspectionOutcomes' => $existingInspectionOutcomes ?? [],
                                ])
                            @endif
                        </div>
                        <x-input-error class="px-4 pb-3" :messages="$errors->get('item_outcomes')" />
                    </div>

                    <div id="legacy-checklist-section" @class(['space-y-6', 'hidden' => $hasPerAnimal])>
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

                    <div id="aggregate-counts-section" @class(['grid grid-cols-1 gap-4 sm:grid-cols-3', 'hidden' => $hasPerAnimal])>
                        <div>
                            <x-input-label for="total_examined" :value="__('Total examined')" />
                            <x-text-input id="total_examined" name="total_examined" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('total_examined', $inspection->total_examined)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('total_examined')" />
                        </div>
                        <div>
                            <x-input-label for="approved_quantity" :value="__('Approved quantity')" />
                            <x-text-input id="approved_quantity" name="approved_quantity" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('approved_quantity', $inspection->approved_quantity)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('approved_quantity')" />
                        </div>
                        <div>
                            <x-input-label for="condemned_quantity" :value="__('Condemned quantity')" />
                            <x-text-input id="condemned_quantity" name="condemned_quantity" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('condemned_quantity', $inspection->condemned_quantity)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('condemned_quantity')" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">{{ __('Approved + Condemned cannot exceed Total Examined.') }}</p>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">{{ old('notes', $inspection->notes) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update inspection') }}</x-primary-button>
                        <a href="{{ route('post-mortem-inspections.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('post-mortem-inspections.partials.form-batch-scripts', [
        'executionAnimalsByExecutionId' => $executionAnimalsByExecutionId,
        'checklists' => $checklists,
        'existingInspectionOutcomes' => $existingInspectionOutcomes ?? [],
        'preserveExistingOutcomes' => $preserveExistingOutcomes ?? false,
        'selectedAnimals' => $displayAnimals ?? [],
        'incrementalAnimalSelection' => false,
        'legacyObservations' => $inspection->observations
            ->whereNull('animal_intake_item_id')
            ->mapWithKeys(fn ($o) => [$o->item => ['value' => $o->value, 'notes' => $o->notes]])
            ->all(),
    ])
</x-app-layout>
