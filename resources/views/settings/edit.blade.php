<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Configuration') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Settings') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('Configure how your DayareMeat system behaves for this account.') }}
                        </p>
                    </div>
                </div>

                <div class="px-6 py-6 space-y-6">
                    @if (session('status'))
                        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">{{ __('Species configuration') }}</h4>
                            <p class="text-xs text-slate-600 mt-0.5">
                                {{ __('Manage the list of animal species that can be selected across all modules.') }}
                            </p>
                        </div>
                        <a href="{{ route('species.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                            {{ __('Open species settings') }}
                        </a>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <section>
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                {{ __('General') }}
                            </h4>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="system_name" :value="__('System name')" />
                                    <x-text-input
                                        id="system_name"
                                        name="system_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :value="$settings['system_name'] ?? 'Dayare Meat Traceability'"
                                    />
                                    <x-input-error :messages="$errors->get('system_name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="default_language" :value="__('Default language')" />
                                    <select
                                        id="default_language"
                                        name="default_language"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                        @php
                                            $lang = $settings['default_language'] ?? app()->getLocale();
                                        @endphp
                                        <option value="en" @selected($lang === 'en')>English</option>
                                        <option value="rw" @selected($lang === 'rw')>Kinyarwanda</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('default_language')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="default_country" :value="__('Default country')" />
                                    <x-text-input
                                        id="default_country"
                                        name="default_country"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :value="$settings['default_country'] ?? 'Rwanda'"
                                    />
                                    <x-input-error :messages="$errors->get('default_country')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-gray-100 pt-6">
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                {{ __('Business & Facility defaults') }}
                            </h4>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="default_daily_capacity" :value="__('Default daily production capacity (carcasses)')" />
                                    <x-text-input
                                        id="default_daily_capacity"
                                        name="default_daily_capacity"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        :value="$settings['default_daily_capacity'] ?? ''"
                                    />
                                    <x-input-error :messages="$errors->get('default_daily_capacity')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-gray-100 pt-6">
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                {{ __('Compliance & alerts') }}
                            </h4>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <x-input-label for="temperature_warning" :value="__('Temperature warning (°C)')" />
                                    <x-text-input
                                        id="temperature_warning"
                                        name="temperature_warning"
                                        type="number"
                                        step="1"
                                        class="mt-1 block w-full"
                                        :value="$settings['temperature_warning'] ?? '5'"
                                    />
                                    <x-input-error :messages="$errors->get('temperature_warning')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="temperature_critical" :value="__('Temperature critical (°C)')" />
                                    <x-text-input
                                        id="temperature_critical"
                                        name="temperature_critical"
                                        type="number"
                                        step="1"
                                        class="mt-1 block w-full"
                                        :value="$settings['temperature_critical'] ?? '10'"
                                    />
                                    <x-input-error :messages="$errors->get('temperature_critical')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="max_storage_days" :value="__('Max storage days before alert')" />
                                    <x-text-input
                                        id="max_storage_days"
                                        name="max_storage_days"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        :value="$settings['max_storage_days'] ?? '7'"
                                    />
                                    <x-input-error :messages="$errors->get('max_storage_days')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="alert_email" :value="__('Alert email address')" />
                                    <x-text-input
                                        id="alert_email"
                                        name="alert_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        :value="$settings['alert_email'] ?? ''"
                                    />
                                    <x-input-error :messages="$errors->get('alert_email')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <div class="border-t border-gray-100 pt-6 flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                {{ __('Save settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

