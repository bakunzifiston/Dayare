<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Settings') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('System Settings') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('Configure how DayareMeat behaves. Global master data is managed in this settings module.') }}
                        </p>
                    </div>
                </div>

                <div class="px-6 py-6 space-y-6">
                    @php($isSuperAdmin = auth()->user()?->isSuperAdmin())
                    @if (session('status'))
                        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">{{ __('Species configuration') }}</h4>
                            <p class="text-xs text-slate-600 mt-0.5">
                                {{ __('Global list managed by Super Admin. Tenants can only select from it.') }}
                            </p>
                            @unless ($isSuperAdmin)
                                <p class="mt-2 text-xs font-semibold text-slate-600">
                                    {{ __('Managed by Super Admin') }}
                                </p>
                            @endunless
                        </div>
                        @if ($isSuperAdmin)
                            <a href="{{ route('super-admin.species.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                                {{ __('Open species settings') }}
                            </a>
                        @endif
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">{{ __('Units configuration') }}</h4>
                            <p class="text-xs text-slate-600 mt-0.5">
                                {{ __('Global list managed by Super Admin. Tenants can only select from it.') }}
                            </p>
                            @unless ($isSuperAdmin)
                                <p class="mt-2 text-xs font-semibold text-slate-600">
                                    {{ __('Managed by Super Admin') }}
                                </p>
                            @endunless
                        </div>
                        @if ($isSuperAdmin)
                            <a href="{{ route('super-admin.units.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                                {{ __('Open units settings') }}
                            </a>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        @unless($isSuperAdmin)
                            <section class="border-t border-gray-100 pt-6 space-y-4">
                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                    {{ __('Tenant species & units selection') }}
                                </h4>
                                <p class="text-xs text-slate-500">
                                    {{ __('Select which global species and units are enabled for each of your businesses. You cannot create or edit global values here.') }}
                                </p>

                                @forelse ($businesses as $business)
                                    @php($selectedSpecies = old('business_species.'.$business->id, $selectedSpeciesByBusiness[$business->id] ?? []))
                                    @php($selectedUnits = old('business_units.'.$business->id, $selectedUnitsByBusiness[$business->id] ?? []))

                                    <div class="rounded-lg border border-slate-200 p-4 space-y-4">
                                        <div class="text-sm font-semibold text-slate-800">{{ $business->business_name }}</div>

                                        <div>
                                            <div class="text-xs font-semibold text-slate-600 mb-2">{{ __('Enabled species') }}</div>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                @foreach ($species as $item)
                                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                        <input
                                                            type="checkbox"
                                                            class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary"
                                                            name="business_species[{{ $business->id }}][]"
                                                            value="{{ $item->id }}"
                                                            @checked(in_array($item->id, $selectedSpecies, true))
                                                        >
                                                        <span>{{ __($item->name) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-xs font-semibold text-slate-600 mb-2">{{ __('Enabled units') }}</div>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                @foreach ($units as $item)
                                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                        <input
                                                            type="checkbox"
                                                            class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary"
                                                            name="business_units[{{ $business->id }}][]"
                                                            value="{{ $item->id }}"
                                                            @checked(in_array($item->id, $selectedUnits, true))
                                                        >
                                                        <span>{{ __($item->name) }} ({{ $item->code }})</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">{{ __('No accessible businesses found for this account.') }}</p>
                                @endforelse
                            </section>
                        @endunless

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
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary text-sm"
                                    >
                                        <option value="en" @selected(($settings['default_language'] ?? app()->getLocale()) === 'en')>{{ __('English') }}</option>
                                        <option value="rw" @selected(($settings['default_language'] ?? app()->getLocale()) === 'rw')>{{ __('Kinyarwanda') }}</option>
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
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-bucha-primary"
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

