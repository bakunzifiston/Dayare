<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('slaughter-executions.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Slaughter execution') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Record slaughter execution') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12" data-form-mode="create">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('slaughter-executions.store') }}" class="space-y-6" data-slaughter-form novalidate>
                    @csrf

                    <div>
                        <x-input-label for="slaughter_plan_id" :value="__('Slaughter session')" />
                        <select id="slaughter_plan_id" name="slaughter_plan_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select slaughter session') }}</option>
                            @foreach ($plans as $p)
                                <option value="{{ $p['id'] }}"
                                    @selected(old('slaughter_plan_id', $selectedPlan?->id) == $p['id'])>
                                    {{ $p['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_plan_id')" />
                    </div>

                    <div id="slaughter-progress-summary" class="hidden rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <span id="slaughter-progress-text"></span>
                    </div>

                    <div id="per-animal-slaughter-section"
                         class="rounded-lg border border-slate-200 bg-white @if (! isset($approvedItems) || $approvedItems->isEmpty()) hidden @endif">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">{{ __('Individual animal slaughter') }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Check each animal as it is slaughtered. You can save one at a time or several together.') }}</p>
                        </div>
                        <div id="per-animal-slaughter-container" class="p-4">
                            @if (isset($approvedItems) && $approvedItems->isNotEmpty())
                                @include('slaughter-executions.partials._per-animal-slaughter', [
                                    'approvedItems' => $approvedItems,
                                    'executionItems' => collect(),
                                    'slaughteredItemIds' => $slaughteredItemIds ?? [],
                                    'slaughteredDetails' => $slaughteredDetails ?? [],
                                    'currentExecutionItemIds' => [],
                                ])
                            @else
                                <p class="text-sm text-gray-500">
                                    {{ __('Select a slaughter session with ante-mortem approved animals.') }}
                                </p>
                            @endif
                        </div>
                        <x-input-error class="px-4 pb-3" :messages="$errors->get('item_slaughters')" />
                    </div>

                    <div id="manual-count-section" @if (isset($approvedItems) && $approvedItems->isNotEmpty()) class="hidden" @endif>
                        <x-input-label for="actual_animals_slaughtered" :value="__('Actual animals slaughtered')" />
                    </div>
                    <input type="number"
                           id="actual_animals_slaughtered"
                           name="actual_animals_slaughtered"
                           min="0"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary @if (isset($approvedItems) && $approvedItems->isNotEmpty()) hidden @endif"
                           value="{{ old('actual_animals_slaughtered', isset($approvedItems) && $approvedItems->isNotEmpty() ? 0 : ($selectedPlan ? $approvedItems->count() : '')) }}"
                           required>
                    <x-input-error class="mt-2" :messages="$errors->get('actual_animals_slaughtered')" />

                    <div>
                        <x-input-label for="slaughter_time" :value="__('Slaughter time')" />
                        <div id="am-gate-warning" style="display:none;"></div>
                        <x-text-input id="slaughter_time" name="slaughter_time" type="datetime-local" class="mt-1 block w-full" :value="old('slaughter_time', now()->format('Y-m-d\TH:i'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_time')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\SlaughterExecution::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', 'completed') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div id="yield-summary" style="display:none;"
                         class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        {{ __('Total yield:') }} <strong id="yield-total">0.00</strong> kg
                        {{ __('across') }} <span id="yield-count">0</span> {{ __('animals in this save') }}
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save execution') }}</x-primary-button>
                        <a href="{{ route('slaughter-executions.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('slaughter-executions.partials.form-scripts')
</x-app-layout>
