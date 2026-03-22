<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record slaughter execution') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('slaughter-executions.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="slaughter_plan_id" :value="__('Slaughter session')" />
                        <select id="slaughter_plan_id" name="slaughter_plan_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select slaughter session') }}</option>
                            @foreach ($plans as $p)
                                <option value="{{ $p['id'] }}" @selected(old('slaughter_plan_id') == $p['id'])>{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_plan_id')" />
                    </div>

                    <div>
                        <x-input-label for="actual_animals_slaughtered" :value="__('Actual animals slaughtered')" />
                        <x-text-input id="actual_animals_slaughtered" name="actual_animals_slaughtered" type="number" min="0" class="mt-1 block w-full" :value="old('actual_animals_slaughtered', 0)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('actual_animals_slaughtered')" />
                    </div>

                    <div>
                        <x-input-label for="slaughter_time" :value="__('Slaughter time')" />
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

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save execution') }}</x-primary-button>
                        <a href="{{ route('slaughter-executions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
