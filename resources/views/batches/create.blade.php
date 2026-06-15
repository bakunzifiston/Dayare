<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('batches.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Batches') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create batch') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('Batch code will be auto-generated. You can combine animals from all completed slaughter executions on the same day at one facility.') }}</p>
                <form method="post" action="{{ route('batches.store') }}" class="space-y-6" id="batch-form">
                    @csrf

                    <input type="hidden" id="slaughter_execution_id" name="slaughter_execution_id"
                           value="{{ old('slaughter_execution_id', $selectedDayData['primary_execution_id'] ?? '') }}">

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="facility_id" :value="__('Facility')" />
                            <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                <option value="">{{ __('Select facility') }}</option>
                                @foreach ($facilities as $facility)
                                    <option value="{{ $facility->id }}" @selected((int) old('facility_id', $selectedFacilityId) === $facility->id)>
                                        {{ $facility->facility_name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                        </div>

                        <div>
                            <x-input-label for="slaughter_date" :value="__('Slaughter date')" />
                            <x-text-input id="slaughter_date" name="slaughter_date" type="date" class="mt-1 block w-full"
                                          :value="old('slaughter_date', $selectedSlaughterDate)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('slaughter_date')" />
                        </div>
                    </div>

                    <div id="same-day-summary" class="@if (! $selectedDayData) hidden @endif rounded-md border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                        <p id="same-day-summary-text">
                            @if ($selectedDayData)
                                {{ trans_choice(':count completed slaughter execution on this day|:count completed slaughter executions on this day', $selectedDayData['execution_count'], ['count' => $selectedDayData['execution_count']]) }}
                                — <strong>{{ $selectedDayData['available_animal_count'] }}</strong> {{ __('animals available') }}
                                @if ($selectedDayData['available_meat_kg'] > 0)
                                    ({{ number_format($selectedDayData['available_meat_kg'], 2) }} kg)
                                @endif
                            @endif
                        </p>
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}" @selected(old('inspector_id') == $insp['id'])>{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="species_display" :value="__('Species')" />
                        <x-text-input id="species_display" type="text" class="mt-1 block w-full bg-gray-50 text-gray-700"
                                      :value="old('species', $selectedDayData['species'] ?? '')" readonly />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Inherited from slaughter executions on the selected day.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="quantity" :value="__('Quantity')" />
                        <x-text-input id="quantity" name="quantity" type="number" min="0.01" step="0.01" class="mt-1 block w-full"
                                      :value="old('quantity', $selectedDayData['available_meat_kg'] ?? '')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('quantity')" />
                    </div>

                    <div id="animal-selector-wrapper" class="mt-4">
                        @if ($selectedDayData && count($selectedDayData['items']) > 0)
                            @include('batches.partials._same-day-animal-selector', [
                                'items' => $selectedDayData['items'],
                                'alreadyBatchedIds' => $alreadyBatchedAnimalIds,
                            ])
                        @else
                            <p class="text-sm text-gray-400" id="animal-selector-placeholder">
                                {{ __('Select a facility and slaughter date with completed executions to choose animals for this batch.') }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <x-input-label for="quantity_unit" :value="__('Unit')" />
                        <select id="quantity_unit" name="quantity_unit" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @if (isset($units) && $units->isNotEmpty())
                                @foreach ($units as $unit)
                                    <option value="{{ $unit['code'] }}" @selected(old('quantity_unit', 'kg') === $unit['code'])>{{ $unit['name'] }}</option>
                                @endforeach
                            @else
                                <option value="">{{ __('No configured units available') }}</option>
                            @endif
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('quantity_unit')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\Batch::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', \App\Models\Batch::STATUS_APPROVED) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <x-input-error class="mt-2" :messages="$errors->get('selected_animal_ids')" />
                    <x-input-error class="mt-2" :messages="$errors->get('slaughter_execution_id')" />

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Create batch') }}</x-primary-button>
                        <a href="{{ route('batches.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('batches.partials.form-create-scripts', [
        'sameDayBatchData' => $sameDayBatchData,
    ])
</x-app-layout>
