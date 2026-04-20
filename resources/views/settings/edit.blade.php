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
                            <a href="{{ route('super-admin.species.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                                {{ __('Manage species') }}
                            </a>
                        @else
                            <a href="#tenant-species-units" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                                {{ __('Manage species') }}
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
                            <a href="{{ route('super-admin.units.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                                {{ __('Manage units') }}
                            </a>
                        @else
                            <a href="#tenant-species-units" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                                {{ __('Manage units') }}
                            </a>
                        @endif
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">{{ __('Temperature standards') }}</h4>
                            <p class="text-xs text-slate-600 mt-0.5">
                                {{ __('Allowed °C ranges and tolerance before batches are marked at risk.') }}
                            </p>
                        </div>
                        <a href="{{ route('cold-room-standards.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                            {{ __('Manage standards') }}
                        </a>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        @unless($isSuperAdmin)
                            <section id="tenant-species-units" class="border-t border-gray-100 pt-6 space-y-4">
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

                        <div class="border-t border-gray-100 pt-6 flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-bucha-primary"
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

