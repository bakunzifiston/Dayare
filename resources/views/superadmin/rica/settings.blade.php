<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <a href="{{ route('rica.dashboard') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy shrink-0">{{ __('← RICA') }}</a>
            <span class="hidden sm:inline text-slate-300" aria-hidden="true">·</span>
            <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ __('RICA') }}</span>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight shrink-0">{{ __('Settings') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Settings') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('Configure defaults and notifications for the RICA oversight workspace. These settings apply to all RICA administrators.') }}
                    </p>
                </div>

                <div class="px-6 py-6">
                    @if (session('status'))
                        <div class="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('rica.settings.update') }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <section class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">{{ __('General') }}</h4>

                            <div>
                                <x-input-label for="workspace_name" :value="__('Workspace name')" />
                                <x-text-input
                                    id="workspace_name"
                                    name="workspace_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('workspace_name', $settings['workspace_name'])"
                                />
                                <p class="mt-1 text-xs text-slate-500">{{ __('Displayed in the RICA workspace header and reports.') }}</p>
                                <x-input-error :messages="$errors->get('workspace_name')" class="mt-2" />
                            </div>
                        </section>

                        <section class="space-y-4 border-t border-slate-100 pt-6">
                            <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">{{ __('Dashboard defaults') }}</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="default_tenant_environment" :value="__('Default tenant scope')" />
                                    <select
                                        id="default_tenant_environment"
                                        name="default_tenant_environment"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary text-sm"
                                    >
                                        @foreach ($tenantEnvironmentOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('default_tenant_environment', $settings['default_tenant_environment']) === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Which tenant environments are included when opening the RICA dashboard.') }}</p>
                                    <x-input-error :messages="$errors->get('default_tenant_environment')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="default_dashboard_period" :value="__('Default slaughter period')" />
                                    <select
                                        id="default_dashboard_period"
                                        name="default_dashboard_period"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary text-sm"
                                    >
                                        @foreach ($dashboardPeriodOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('default_dashboard_period', $settings['default_dashboard_period']) === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Initial period filter when no date range is selected.') }}</p>
                                    <x-input-error :messages="$errors->get('default_dashboard_period')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4 border-t border-slate-100 pt-6">
                            <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">{{ __('Meat condemnation') }}</h4>

                            <div>
                                <x-input-label for="condemnation_loss_per_kg_rwf" :value="__('Estimated loss per condemned kg (RWF)')" />
                                <x-text-input
                                    id="condemnation_loss_per_kg_rwf"
                                    name="condemnation_loss_per_kg_rwf"
                                    type="number"
                                    min="100"
                                    max="50000"
                                    step="1"
                                    class="mt-1 block w-full md:max-w-xs"
                                    :value="old('condemnation_loss_per_kg_rwf', $settings['condemnation_loss_per_kg_rwf'])"
                                />
                                <p class="mt-1 text-xs text-slate-500">{{ __('Used to estimate economic loss on the Meat & condemnation dashboard (condemned kg × this value).') }}</p>
                                <x-input-error :messages="$errors->get('condemnation_loss_per_kg_rwf')" class="mt-2" />
                            </div>
                        </section>

                        <section class="space-y-4 border-t border-slate-100 pt-6">
                            <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">{{ __('Reporting & alerts') }}</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="notification_email" :value="__('Notification email')" />
                                    <x-text-input
                                        id="notification_email"
                                        name="notification_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        :value="old('notification_email', $settings['notification_email'])"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Primary contact for RICA workspace alerts and escalations.') }}</p>
                                    <x-input-error :messages="$errors->get('notification_email')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="monthly_report_deadline_day" :value="__('Monthly report deadline (day of month)')" />
                                    <x-text-input
                                        id="monthly_report_deadline_day"
                                        name="monthly_report_deadline_day"
                                        type="number"
                                        min="1"
                                        max="28"
                                        class="mt-1 block w-full"
                                        :value="old('monthly_report_deadline_day', $settings['monthly_report_deadline_day'])"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Expected submission day for FPU/FRM/018 monthly inspection reports.') }}</p>
                                    <x-input-error :messages="$errors->get('monthly_report_deadline_day')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <div class="border-t border-slate-100 pt-6 flex justify-end">
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
