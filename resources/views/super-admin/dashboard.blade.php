<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ __('Super Admin') }}</span>
            <h1 class="text-xl font-semibold text-slate-800 tracking-tight">
                {{ __('Platform overview') }}
            </h1>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-8">
            <x-super-admin.tenant-environment-filter
                :action="route('super-admin.dashboard')"
                :current="$tenantEnvironmentFilter"
            />

            {{-- Platform summary --}}
            <section class="rounded-xl border border-slate-200/80 bg-gradient-to-r from-slate-50 to-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Platform summary') }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('Key operational counts for the current month.') }}</p>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-kpi-card stat glyph="building" color="blue"
                        :title="__('Active facilities')"
                        :subtitle="__('Facilities with active operating status')"
                        :value="$complianceSummary['active_facilities']" />
                    <x-kpi-card stat glyph="box" color="slate"
                        :title="__('Batches this month')"
                        :subtitle="__('Slaughter batches by execution date')"
                        :value="$complianceSummary['batches_this_month']" />
                    <x-kpi-card stat glyph="certificate" color="green"
                        :title="__('Certificates this month')"
                        :subtitle="__('Veterinary certificates issued')"
                        :value="$complianceSummary['certificates_this_month']" />
                </div>
            </section>

            {{-- Pipeline compliance alerts --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Pipeline compliance alerts') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Actionable slaughter pipeline issues across all tenants.') }}</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($pipelineAlerts as $alert)
                            <x-super-admin.compliance-alert-card
                                :label="$alert['label']"
                                :description="$alert['description']"
                                :count="$alert['count']"
                                :severity="$alert['count'] > 0 ? $alert['severity'] : 'green'"
                                :icon="$alert['icon']"
                                :href="$alert['href']"
                            />
                        @endforeach
                    </div>
                    @if (collect($pipelineAlerts)->every(fn ($a) => ($a['count'] ?? 0) === 0))
                        <p class="mt-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg px-4 py-3">
                            {{ __('All pipeline compliance checks are clear. No slaughter workflow issues require attention.') }}
                        </p>
                    @endif
                </div>
            </section>

            {{-- Administrative compliance --}}
            @php $adminWithIssues = collect($administrativeAlerts)->filter(fn ($a) => ($a['count'] ?? 0) > 0); @endphp
            @if ($adminWithIssues->isNotEmpty())
                <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Administrative compliance') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Licenses, contracts, and authorization expiry alerts.') }}</p>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($adminWithIssues as $alert)
                            <x-super-admin.compliance-alert-card
                                :label="$alert['label']"
                                :description="$alert['description']"
                                :count="$alert['count']"
                                :severity="$alert['severity']"
                                :icon="$alert['icon']"
                                :href="null"
                            />
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- 1. Global KPIs --}}
            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Global KPIs') }}</h2>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-3 border-b border-slate-100">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Dashboard structure (Super Admin)') }}</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <x-kpi-card title="{{ __('Total tenants') }}" :value="$workspaceKpis['tenants']" color="blue" />
                            <x-kpi-card title="{{ __('Total businesses') }}" :value="$workspaceKpis['businesses']" :href="route('super-admin.vibe-programme.index')" color="slate" />
                            <x-kpi-card title="{{ __('Total users') }}" :value="$workspaceKpis['users']" color="blue" />
                            <x-kpi-card title="{{ __('Total delete actions') }}" :value="$workspaceKpis['delete_actions']" color="slate" />
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2. Tenants table --}}
            <section class="space-y-6">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Tenants table') }}</h2>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-700">{{ __('Tenants') }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('Tenant name with number of registered businesses and users.') }}</p>
                            </div>
                            @if (auth()->user()?->hasSuperAdminModuleAccess(\App\Models\User::SUPER_ADMIN_MODULE_USER_MANAGEMENT))
                                <button
                                    type="button"
                                    id="tenant-bulk-delete-trigger"
                                    class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled
                                >
                                    {{ __('Delete selected') }}
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-[560px]">
                        @if ($tenantRows->isEmpty())
                            <div class="p-6 text-sm text-slate-500">{{ __('No tenants yet.') }}</div>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">
                                            <input id="tenant-select-all" type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                                        </th>
                                        <th class="px-4 py-3">{{ __('Tenant name') }}</th>
                                        <th class="px-4 py-3">{{ __('Login email') }}</th>
                                        <th class="px-4 py-3">{{ __('Registered businesses') }}</th>
                                        <th class="px-4 py-3">{{ __('Business type') }}</th>
                                        <th class="px-4 py-3">{{ __('Number of businesses') }}</th>
                                        <th class="px-4 py-3">{{ __('Number of users') }}</th>
                                        <th class="px-4 py-3">{{ __('Environment') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($tenantRows as $tenant)
                                        <tr class="hover:bg-slate-50/70 transition-colors align-top">
                                            <td class="px-4 py-3.5">
                                                @if ((int) ($tenant['id'] ?? 0) !== (int) Auth::id() && auth()->user()?->hasSuperAdminModuleAccess(\App\Models\User::SUPER_ADMIN_MODULE_USER_MANAGEMENT))
                                                    <input
                                                        type="checkbox"
                                                        class="tenant-select-checkbox rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                                        value="{{ $tenant['id'] }}"
                                                    />
                                                @else
                                                    <span class="text-slate-300">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <p class="font-medium text-slate-900">{{ $tenant['tenant_name'] }}</p>
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-700">
                                                <span class="text-xs sm:text-sm">{{ $tenant['tenant_email'] ?? '—' }}</span>
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-700">
                                                @if (!empty($tenant['business_names']))
                                                    <div class="flex flex-wrap gap-1.5 max-w-xl">
                                                        @foreach ($tenant['business_names'] as $businessName)
                                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                                {{ $businessName }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-700">
                                                @if (!empty($tenant['business_types']))
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach ($tenant['business_types'] as $businessType)
                                                            <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                                                {{ $businessType }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <span class="inline-flex min-w-8 justify-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold tabular-nums text-slate-700">{{ $tenant['businesses_count'] }}</span>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <span class="inline-flex min-w-8 justify-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold tabular-nums text-slate-700">{{ $tenant['users_count'] }}</span>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                @if (auth()->user()?->hasSuperAdminModuleAccess(\App\Models\User::SUPER_ADMIN_MODULE_USER_MANAGEMENT) && (int) ($tenant['id'] ?? 0) !== (int) Auth::id())
                                                    <form method="POST" action="{{ route('super-admin.tenants.environment', ['tenant' => $tenant['id']]) }}" class="inline-flex items-center gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select
                                                            name="tenant_environment"
                                                            onchange="this.form.submit()"
                                                            class="text-xs rounded-md border-slate-300 py-1 pl-2 pr-7 focus:border-bucha-primary focus:ring-bucha-primary {{ ($tenant['tenant_environment'] ?? 'live') === 'test' ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-emerald-50 text-emerald-900 border-emerald-200' }}"
                                                            aria-label="{{ __('Tenant environment for :name', ['name' => $tenant['tenant_name']]) }}"
                                                        >
                                                            <option value="live" @selected(($tenant['tenant_environment'] ?? 'live') === 'live')>{{ __('Live') }}</option>
                                                            <option value="test" @selected(($tenant['tenant_environment'] ?? 'live') === 'test')>{{ __('Test') }}</option>
                                                        </select>
                                                    </form>
                                                @else
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ ($tenant['tenant_environment'] ?? 'live') === 'test' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                                        {{ ($tenant['tenant_environment'] ?? 'live') === 'test' ? __('Test') : __('Live') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3.5 text-right">
                                                @if ((int) ($tenant['id'] ?? 0) !== (int) Auth::id() && auth()->user()?->hasSuperAdminModuleAccess(\App\Models\User::SUPER_ADMIN_MODULE_USER_MANAGEMENT))
                                                    <button
                                                        type="button"
                                                        class="tenant-delete-trigger text-rose-600 hover:text-rose-800 text-xs font-semibold"
                                                        data-delete-url="{{ route('super-admin.tenants.destroy', ['tenant' => $tenant['id']]) }}"
                                                        data-tenant-name="{{ $tenant['tenant_name'] }}"
                                                        data-businesses-count="{{ $tenant['businesses_count'] }}"
                                                        data-staff-count="{{ $tenant['staff_count'] }}"
                                                    >
                                                        {{ __('Delete') }}
                                                    </button>
                                                @else
                                                    <span class="text-slate-400 text-xs">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>

                {{-- 3. Users table --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-700">{{ __('Users') }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Registered users with role and tenant assignment.') }}</p>
                    </div>
                    <div class="overflow-x-auto max-h-[440px]">
                        @if ($tenantUserRows->isEmpty())
                            <div class="p-6 text-sm text-slate-500">{{ __('No users yet.') }}</div>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">{{ __('Name') }}</th>
                                        <th class="px-4 py-3">{{ __('Email') }}</th>
                                        <th class="px-4 py-3">{{ __('Role') }}</th>
                                        <th class="px-4 py-3">{{ __('Tenant') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($tenantUserRows as $row)
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="px-4 py-3.5 font-medium text-slate-900">{{ $row['name'] ?? '—' }}</td>
                                            <td class="px-4 py-3.5 text-slate-600">{{ $row['email'] ?? '—' }}</td>
                                            <td class="px-4 py-3.5 text-slate-600">
                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                    {{ str_replace('_', ' ', ucfirst((string) ($row['role'] ?? 'User'))) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-600">{{ $row['tenant'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </section>

            {{-- 2b. System health --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('System health') }}</h2>
                </div>
                <div class="p-6 flex flex-wrap gap-4 items-center">
                    <div class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 px-4 py-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-sm font-medium text-emerald-900">{{ __('Platform operational') }}</span>
                    </div>
                    <span class="text-xs text-slate-500">{{ config('app.name') }} · {{ config('app.env') }}</span>
                </div>
            </section>

            {{-- 3. Operational insights (charts) --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Operational insights') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Platform analytics.') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Slaughter activity – animals slaughtered per day') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-slaughter-activity" aria-label="{{ __('Slaughter activity') }}"></canvas>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Species distribution') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-species-distribution" aria-label="{{ __('Species distribution') }}"></canvas>
                        </div>
                    </div>
                    <div class="lg:col-span-2 rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Demand vs supply') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-demand-vs-supply" aria-label="{{ __('Demand vs supply') }}"></canvas>
                        </div>
                    </div>
                    <div class="lg:col-span-2 rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Deliveries by region') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-deliveries-by-region" aria-label="{{ __('Deliveries by region') }}"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 4. CRM insights --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('CRM insights') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Supplier and customer analytics.') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-slate-700">{{ __('Supplier performance') }}</h3>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">{{ __('Top suppliers by volume') }}</p>
                            @if (!empty($crmInsights['top_suppliers']))
                                <ul class="divide-y divide-slate-100 text-sm">
                                    @foreach (array_slice($crmInsights['top_suppliers'], 0, 5) as $s)
                                        <li class="py-2 flex justify-between">
                                            <span class="font-medium text-slate-900">{{ $s['name'] }}</span>
                                            <span class="tabular-nums text-slate-600">{{ number_format($s['volume']) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-slate-500">{{ __('No supplier data yet.') }}</p>
                            @endif
                        </div>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">{{ __('Supplier rejection rate') }}</p>
                            <p class="text-2xl font-bold text-slate-900">{{ $crmInsights['supplier_rejection_rate']['rate'] }}%</p>
                            <p class="text-xs text-slate-500">{{ $crmInsights['supplier_rejection_rate']['rejected'] }} / {{ $crmInsights['supplier_rejection_rate']['total'] }} {{ __('intakes') }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-slate-700">{{ __('Customer activity') }}</h3>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">{{ __('Top customers by purchase volume') }}</p>
                            @if (!empty($crmInsights['top_customers']))
                                <ul class="divide-y divide-slate-100 text-sm">
                                    @foreach (array_slice($crmInsights['top_customers'], 0, 5) as $c)
                                        <li class="py-2 flex justify-between">
                                            <span class="font-medium text-slate-900">{{ $c['name'] }}</span>
                                            <span class="tabular-nums text-slate-600">{{ number_format($c['volume'], 1) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-slate-500">{{ __('No customer delivery data yet.') }}</p>
                            @endif
                        </div>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">{{ __('Customer demand trends') }}</p>
                            <div class="h-40">
                                <canvas id="chart-demand-trends" aria-label="{{ __('Demand trends') }}"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <dialog id="tenant-delete-dialog" class="w-full max-w-lg rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/40">
        <div class="px-6 py-5">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Delete tenant account') }}</h3>
            <p class="mt-3 text-sm text-slate-700">{{ __('This action is irreversible. The following will be permanently removed:') }}</p>
            <ul class="mt-3 space-y-1 text-sm text-slate-700 list-disc list-inside">
                <li id="tenant-delete-businesses-line"></li>
                <li id="tenant-delete-staff-line"></li>
                <li>{{ __('All slaughter, inspection, certificate, and business data') }}</li>
            </ul>
            <p id="tenant-delete-name" class="mt-4 text-sm font-semibold text-slate-900"></p>
            <p class="mt-2 text-xs text-rose-700 bg-rose-50 border border-rose-100 rounded-md px-3 py-2">
                {{ __('Warning: This cannot be undone. Export any data you need before proceeding.') }}
            </p>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-2">
            <button id="tenant-delete-cancel" type="button" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50">
                {{ __('Cancel') }}
            </button>
            <form id="tenant-delete-form" method="POST" action="">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-rose-600 text-white hover:bg-rose-700">
                    {{ __('Confirm Delete') }}
                </button>
            </form>
        </div>
    </dialog>

    <dialog id="tenant-bulk-delete-dialog" class="w-full max-w-lg rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/40">
        <div class="px-6 py-5">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Delete tenant accounts') }}</h3>
            <p class="mt-3 text-sm text-slate-700">{{ __('Are you sure you want to delete this tenant?') }}</p>
            <p class="mt-1 text-sm text-slate-700">{{ __('This action will permanently remove all associated businesses and users.') }}</p>
            <p id="tenant-bulk-delete-count" class="mt-3 text-xs text-slate-500"></p>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-2">
            <button id="tenant-bulk-delete-cancel" type="button" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50">
                {{ __('Cancel') }}
            </button>
            <form id="tenant-bulk-delete-form" method="POST" action="{{ route('super-admin.tenants.bulk-destroy') }}">
                @csrf
                @method('DELETE')
                <div id="tenant-bulk-delete-inputs"></div>
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-rose-600 text-white hover:bg-rose-700">
                    {{ __('Confirm Delete') }}
                </button>
            </form>
        </div>
    </dialog>

    @push('scripts')
        <script>window.dashboardCharts = @json($charts);</script>
        @vite('resources/js/dashboard-charts.js')
        <script>
            (function () {
                const dialog = document.getElementById('tenant-delete-dialog');
                const form = document.getElementById('tenant-delete-form');
                const cancelButton = document.getElementById('tenant-delete-cancel');
                const nameEl = document.getElementById('tenant-delete-name');
                const businessesLine = document.getElementById('tenant-delete-businesses-line');
                const staffLine = document.getElementById('tenant-delete-staff-line');
                const triggers = document.querySelectorAll('.tenant-delete-trigger');
                const selectAll = document.getElementById('tenant-select-all');
                const selectRows = document.querySelectorAll('.tenant-select-checkbox');
                const bulkTrigger = document.getElementById('tenant-bulk-delete-trigger');
                const bulkDialog = document.getElementById('tenant-bulk-delete-dialog');
                const bulkCancel = document.getElementById('tenant-bulk-delete-cancel');
                const bulkForm = document.getElementById('tenant-bulk-delete-form');
                const bulkInputs = document.getElementById('tenant-bulk-delete-inputs');
                const bulkCount = document.getElementById('tenant-bulk-delete-count');

                if (!dialog || !form || !cancelButton) {
                    return;
                }

                triggers.forEach((button) => {
                    button.addEventListener('click', function () {
                        const deleteUrl = button.getAttribute('data-delete-url') || '';
                        const tenantName = button.getAttribute('data-tenant-name') || '';
                        const businessesCount = button.getAttribute('data-businesses-count') || '0';
                        const staffCount = button.getAttribute('data-staff-count') || '0';
                        form.setAttribute('action', deleteUrl);
                        nameEl.textContent = tenantName ? `{{ __('Tenant') }}: ${tenantName}` : '';
                        if (businessesLine) {
                            businessesLine.textContent = `{{ __('Businesses') }}: ${businessesCount}`;
                        }
                        if (staffLine) {
                            staffLine.textContent = `{{ __('Staff accounts') }}: ${staffCount}`;
                        }
                        dialog.showModal();
                    });
                });

                cancelButton.addEventListener('click', function () {
                    dialog.close();
                });

                dialog.addEventListener('click', function (event) {
                    const rect = dialog.getBoundingClientRect();
                    const inDialog = (
                        rect.top <= event.clientY &&
                        event.clientY <= rect.top + rect.height &&
                        rect.left <= event.clientX &&
                        event.clientX <= rect.left + rect.width
                    );
                    if (!inDialog) {
                        dialog.close();
                    }
                });

                const selectedTenantIds = function () {
                    return Array.from(selectRows)
                        .filter((checkbox) => checkbox.checked)
                        .map((checkbox) => checkbox.value);
                };

                const refreshBulkButtonState = function () {
                    if (!bulkTrigger) {
                        return;
                    }
                    bulkTrigger.disabled = selectedTenantIds().length === 0;
                };

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        selectRows.forEach((checkbox) => {
                            checkbox.checked = selectAll.checked;
                        });
                        refreshBulkButtonState();
                    });
                }

                selectRows.forEach((checkbox) => {
                    checkbox.addEventListener('change', function () {
                        if (selectAll) {
                            selectAll.checked = Array.from(selectRows).every((row) => row.checked);
                        }
                        refreshBulkButtonState();
                    });
                });

                if (bulkTrigger && bulkDialog && bulkCancel && bulkForm && bulkInputs && bulkCount) {
                    bulkTrigger.addEventListener('click', function () {
                        const ids = selectedTenantIds();
                        bulkInputs.innerHTML = '';
                        ids.forEach((id) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'tenant_ids[]';
                            input.value = id;
                            bulkInputs.appendChild(input);
                        });
                        bulkCount.textContent = ids.length > 0 ? `Selected tenants: ${ids.length}` : '';
                        bulkDialog.showModal();
                    });

                    bulkCancel.addEventListener('click', function () {
                        bulkDialog.close();
                    });

                    bulkDialog.addEventListener('click', function (event) {
                        const rect = bulkDialog.getBoundingClientRect();
                        const inDialog = (
                            rect.top <= event.clientY &&
                            event.clientY <= rect.top + rect.height &&
                            rect.left <= event.clientX &&
                            event.clientX <= rect.left + rect.width
                        );
                        if (!inDialog) {
                            bulkDialog.close();
                        }
                    });
                }

                refreshBulkButtonState();
            })();
        </script>
    @endpush
</x-app-layout>
