<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Post-mortem') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Record inspection') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Select a slaughter execution to load the animals slaughtered in that session.') }}</p>
                </div>
                <a href="{{ route('post-mortem-inspections.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <div class="px-4 py-4">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-medium">{{ __('Please fix the following before saving:') }}</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('post-mortem-inspections.store') }}" class="space-y-5" id="post-mortem-form" novalidate>
                    @csrf

                    <div>
                        <x-input-label for="slaughter_execution_id" :value="__('Slaughter execution')" />
                        <select id="slaughter_execution_id" name="slaughter_execution_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            <option value="">{{ __('Select slaughter execution') }}</option>
                            @foreach ($executions as $execution)
                                <option value="{{ $execution['id'] }}" data-facility-id="{{ $execution['facility_id'] }}" data-species="{{ $execution['species'] }}" @selected(old('slaughter_execution_id', $selectedExecutionId ?? null) == $execution['id'])>{{ $execution['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_execution_id')" />
                    </div>

                    <div>
                        <x-input-label for="species" :value="__('Species')" />
                        <select id="species" name="species" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            @php($speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect())
                            <option value="">{{ __('Select species') }}</option>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species', is_array($selectedExecutionData ?? null) ? ($selectedExecutionData['species'] ?? null) : null) === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Species is auto-filled from the selected slaughter execution.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            <option value="">{{ __('Select slaughter execution first') }}</option>
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
                        <x-text-input id="inspection_date" name="inspection_date" type="date" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('inspection_date', date('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('inspection_date')" />
                    </div>

                    <div id="per-animal-outcomes-section" @class(['rounded-lg border border-slate-200 bg-white', 'hidden' => ! $hasPerAnimal])>
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">{{ __('Individual animal post-mortem') }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Animals from the selected slaughter execution are listed below. Record an outcome and checklist for each.') }}</p>
                        </div>

                        <div id="animal-tag-lookup" class="hidden border-b border-slate-200 bg-slate-50 px-4 py-4">
                            <x-input-label for="animal_tag_search" :value="__('Ear tag or tag number')" />
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-text-input id="animal_tag_search" type="text" class="h-9 min-w-[12rem] flex-1 rounded-lg border-slate-200 text-sm" placeholder="{{ __('Scan or type tag…') }}" autocomplete="off" />
                                <button type="button" id="add-animal-by-tag" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">
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
                                    'species' => old('species', $pmExecutionData['species'] ?? ''),
                                    'inspectionItems' => collect(),
                                    'existingInspectionOutcomes' => $existingInspectionOutcomes ?? [],
                                ])
                            @else
                                <p class="text-sm text-gray-500">{{ __('Select a slaughter execution to load animals.') }}</p>
                            @endif
                        </div>
                        <x-input-error class="px-4 pb-3" :messages="$errors->get('item_outcomes')" />

                        <div id="execution-animals-roster" @class(['border-t border-slate-200 bg-white px-4 py-4', 'hidden' => ! $hasPerAnimal])>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Animals in this slaughter execution') }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                <span id="execution-animals-count">{{ $pmExecutionData['animal_count'] ?? 0 }}</span>
                                {{ __('animal(s) slaughtered — pending animals are loaded for inspection above.') }}
                            </p>
                            @include('post-mortem-inspections.partials._execution-animals-roster', [
                                'executionAnimals' => $executionAnimals,
                                'inspectedAnimalIds' => $inspectedAnimalIds,
                            ])
                        </div>

                        <div class="border-t border-slate-200 px-4 py-4">
                            @include('post-mortem-inspections.partials._meat-totals-summary', [
                                'visible' => $hasPerAnimal,
                                'examinedTotal' => old('total_examined', 0),
                                'carcassApprovedTotal' => old('approved_carcass_kg', 0),
                                'otherMeatApprovedTotal' => old('approved_other_meat_kg', 0),
                                'condemnedTotal' => old('condemned_quantity', 0),
                            ])
                        </div>
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

                    @include('post-mortem-inspections.partials._meat-totals-fields', [
                        'hidden' => $hasPerAnimal,
                        'examinedValue' => old('total_examined', $defaultTotalExamined ?? 0),
                        'carcassApprovedValue' => old('approved_carcass_kg', 0),
                        'otherMeatApprovedValue' => old('approved_other_meat_kg', 0),
                        'condemnedValue' => old('condemned_quantity', 0),
                    ])

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                        <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save inspection') }}</button>
                        <a href="{{ route('post-mortem-inspections.hub') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </section>
    </div>

    @include('post-mortem-inspections.partials.form-batch-scripts', [
        'executionAnimalsByExecutionId' => $executionAnimalsByExecutionId,
        'checklists' => $checklists,
        'existingInspectionOutcomes' => $existingInspectionOutcomes ?? [],
        'preserveExistingOutcomes' => $preserveExistingOutcomes ?? false,
        'selectedAnimals' => $selectedAnimals ?? [],
        'incrementalAnimalSelection' => false,
    ])
</x-app-layout>
