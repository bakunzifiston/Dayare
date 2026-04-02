<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Compliance monitoring') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Track expired licenses, missing inspections, certificates, and other issues.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @php $hasIssues = (int) ($kpis['total_issues'] ?? 0) > 0; @endphp
            <div class="rounded-xl px-5 py-4 {{ $hasIssues ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200' }}">
                <p class="font-medium {{ $hasIssues ? 'text-amber-800' : 'text-emerald-800' }}">
                    {{ $hasIssues ? __(':count issue(s) need attention.', ['count' => $kpis['total_issues']]) : __('All clear — no compliance issues.') }}
                </p>
                <p class="text-sm mt-0.5 {{ $hasIssues ? 'text-amber-700/90' : 'text-emerald-700/90' }}">
                    {{ $hasIssues ? __('Review and resolve the items below.') : __('Licenses, inspections, and records are in order.') }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">{{ __('Overview') }}</h3>
                </div>
                <div class="p-5">
                    <div class="flex flex-wrap items-baseline gap-x-6 gap-y-2 text-sm">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold tabular-nums {{ $hasIssues ? 'text-amber-600' : 'text-emerald-600' }}">{{ $kpis['total_issues'] }}</span>
                            <span class="text-slate-600 font-medium">{{ __('Total issues') }}</span>
                        </div>
                        <span class="text-slate-300">·</span>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-slate-500">
                            <span>{{ __('Expired licenses') }} <strong class="text-slate-700">{{ $kpis['expired_licenses'] }}</strong></span>
                            <span>{{ __('Expired auth.') }} <strong class="text-slate-700">{{ $kpis['expired_authorizations'] }}</strong></span>
                            <span>{{ __('Over capacity') }} <strong class="text-slate-700">{{ $kpis['over_capacity_plans'] }}</strong></span>
                            <span>{{ __('Missing ante-mortem') }} <strong class="text-slate-700">{{ $kpis['missing_ante_mortem'] }}</strong></span>
                            <span>{{ __('Missing post-mortem') }} <strong class="text-slate-700">{{ $kpis['missing_post_mortem'] }}</strong></span>
                            <span>{{ __('Missing certificates') }} <strong class="text-slate-700">{{ $kpis['missing_certificates'] }}</strong></span>
                            <span>{{ __('Missing transport') }} <strong class="text-slate-700">{{ $kpis['missing_transport'] }}</strong></span>
                            <span>{{ __('Temp. alerts') }} <strong class="text-slate-700">{{ $kpis['temperature_alerts'] ?? 0 }}</strong></span>
                            <span>{{ __('Storage exceeded') }} <strong class="text-slate-700">{{ $kpis['storage_duration_exceeded'] ?? 0 }}</strong></span>
                            <span>{{ __('Intakes (expired cert)') }} <strong class="text-slate-700">{{ $kpis['intakes_expired_health_cert'] ?? 0 }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($hasIssues)
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Issues by category') }}</p>
            @endif
            <div class="space-y-4">
                @if ($expiredFacilityLicenses->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-red-50/50">
                            <h3 class="text-sm font-semibold text-red-800">{{ __('Expired facility licenses') }}</h3>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($expiredFacilityLicenses as $f)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('businesses.facilities.show', [$f->business_id, $f]) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $f->facility_name }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ __('Expired') }} {{ $f->license_expiry_date->format('d M Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($expiredInspectorAuthorizations->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-red-50/50">
                            <h3 class="text-sm font-semibold text-red-800">{{ __('Expired inspector authorizations') }}</h3>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($expiredInspectorAuthorizations as $i)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('inspectors.show', $i) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $i->full_name }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ $i->authorization_expiry_date ? $i->authorization_expiry_date->format('d M Y') : __('Expired') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($overCapacityPlans->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Over capacity scheduling') }}</h3>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($overCapacityPlans as $p)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('slaughter-plans.show', $p) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $p->slaughter_date->format('d M Y') }} — {{ $p->facility->facility_name ?? '' }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ __('Scheduled') }}: {{ $p->number_of_animals_scheduled }}, {{ __('Daily production capacity') }}: {{ $p->facility->daily_capacity ?? '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($missingAnteMortemPlans->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Missing ante-mortem inspections') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Slaughter sessions without an ante-mortem inspection.') }}</p>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($missingAnteMortemPlans as $p)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('slaughter-plans.show', $p) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $p->slaughter_date->format('d M Y') }} — {{ $p->facility->facility_name ?? '' }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($missingPostMortemBatches->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Missing post-mortem inspections') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Batches without a post-mortem inspection.') }}</p>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($missingPostMortemBatches as $b)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('batches.show', $b) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $b->batch_code }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ $b->slaughterExecution->slaughterPlan->facility->facility_name ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($missingCertificateBatches->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Missing certificates') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Batches eligible for a certificate but without one.') }}</p>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($missingCertificateBatches as $b)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('batches.show', $b) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $b->batch_code }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ $b->slaughterExecution->slaughterPlan->facility->facility_name ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($missingTransportCertificates->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Missing transport records') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Certificates with no transport trip recorded.') }}</p>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($missingTransportCertificates as $c)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('certificates.show', $c) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $c->certificate_number ?: '#' . $c->id }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ $c->batch ? $c->batch->batch_code : ($c->facility?->facility_name ?? '') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($temperatureAlerts->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Temperature alerts (cold room)') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Logs with warning or critical status.') }}</p>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($temperatureAlerts as $log)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('warehouse-storages.show', $log->warehouseStorage) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $log->warehouseStorage->batch->batch_code ?? '#' . $log->warehouse_storage_id }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ $log->recorded_temperature }} °C · {{ $log->recorded_at->format('d M Y H:i') }} · {{ ucfirst($log->status) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (($intakesWithExpiredHealthCert ?? collect())->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Animal intakes — expired health certificate') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Slaughter cannot be scheduled until certificate is renewed.') }}</p>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($intakesWithExpiredHealthCert as $i)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('animal-intakes.show', $i) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $i->intake_date->format('d M Y') }} — {{ $i->facility->facility_name ?? '' }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ $i->supplier_firstname }} {{ $i->supplier_lastname }} · {{ __('Expired') }} {{ $i->health_certificate_expiry_date?->format('d M Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($storageDurationExceeded->isNotEmpty())
                    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-amber-50/50">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Storage duration exceeded') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Batches in storage longer than allowed days.') }}</p>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            @foreach ($storageDurationExceeded as $ws)
                                <li class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                                    <a href="{{ route('warehouse-storages.show', $ws) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy">{{ $ws->batch->batch_code ?? '' }}</a>
                                    <span class="text-slate-500 text-sm"> · {{ $ws->warehouseFacility->facility_name ?? '' }} · {{ __('Entry') }} {{ $ws->entry_date->format('d M Y') }} ({{ $ws->entry_date->diffInDays(now()) }} {{ __('days') }})</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if (!$hasIssues)
                <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-slate-700 font-medium">{{ __('No compliance issues detected') }}</p>
                    <p class="text-slate-500 text-sm mt-1">{{ __('Your facilities and records are in good standing.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
