<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Compliance monitoring') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-500">{{ __('System tracks expired licenses, missing inspections, certificates, transport and other non-compliance.') }}</p>

            @if ($expiredFacilityLicenses->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-red-800 mb-2">{{ __('Expired facility licenses') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($expiredFacilityLicenses as $f)
                            <li class="py-2">
                                <a href="{{ route('businesses.facilities.show', [$f->business_id, $f]) }}" class="font-medium text-indigo-600 hover:underline">{{ $f->facility_name }}</a>
                                <span class="text-sm text-gray-500"> — {{ __('Expired') }} {{ $f->license_expiry_date->format('d M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($expiredInspectorAuthorizations->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-red-800 mb-2">{{ __('Expired inspector authorizations') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($expiredInspectorAuthorizations as $i)
                            <li class="py-2">
                                <a href="{{ route('inspectors.show', $i) }}" class="font-medium text-indigo-600 hover:underline">{{ $i->full_name }}</a>
                                <span class="text-sm text-gray-500"> — {{ $i->authorization_expiry_date ? $i->authorization_expiry_date->format('d M Y') : __('Expired') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($overCapacityPlans->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-amber-800 mb-2">{{ __('Over capacity scheduling') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($overCapacityPlans as $p)
                            <li class="py-2">
                                <a href="{{ route('slaughter-plans.show', $p) }}" class="font-medium text-indigo-600 hover:underline">{{ $p->slaughter_date->format('d M Y') }} — {{ $p->facility->facility_name ?? '' }}</a>
                                <span class="text-sm text-gray-500"> {{ __('Scheduled') }}: {{ $p->number_of_animals_scheduled }}, {{ __('Capacity') }}: {{ $p->facility->daily_capacity ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($missingAnteMortemPlans->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-amber-800 mb-2">{{ __('Missing ante-mortem inspections') }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ __('Slaughter sessions without an ante-mortem inspection.') }}</p>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($missingAnteMortemPlans as $p)
                            <li class="py-2">
                                <a href="{{ route('slaughter-plans.show', $p) }}" class="font-medium text-indigo-600 hover:underline">{{ $p->slaughter_date->format('d M Y') }} — {{ $p->facility->facility_name ?? '' }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($missingPostMortemBatches->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-amber-800 mb-2">{{ __('Missing post-mortem inspections') }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ __('Batches without a post-mortem inspection.') }}</p>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($missingPostMortemBatches as $b)
                            <li class="py-2">
                                <a href="{{ route('batches.show', $b) }}" class="font-medium text-indigo-600 hover:underline">{{ $b->batch_code }}</a>
                                <span class="text-sm text-gray-500"> — {{ $b->slaughterExecution->slaughterPlan->facility->facility_name ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($missingCertificateBatches->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-amber-800 mb-2">{{ __('Missing certificates') }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ __('Batches eligible for a certificate (post-mortem approved &gt; 0) but without one.') }}</p>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($missingCertificateBatches as $b)
                            <li class="py-2">
                                <a href="{{ route('batches.show', $b) }}" class="font-medium text-indigo-600 hover:underline">{{ $b->batch_code }}</a>
                                <span class="text-sm text-gray-500"> — {{ $b->slaughterExecution->slaughterPlan->facility->facility_name ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($missingTransportCertificates->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-amber-800 mb-2">{{ __('Missing transport records') }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ __('Certificates with no transport trip recorded.') }}</p>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($missingTransportCertificates as $c)
                            <li class="py-2">
                                <a href="{{ route('certificates.show', $c) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->certificate_number ?: '#' . $c->id }}</a>
                                <span class="text-sm text-gray-500"> — {{ $c->batch ? $c->batch->batch_code : ($c->facility->facility_name ?? '') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (
                $expiredFacilityLicenses->isEmpty() &&
                $expiredInspectorAuthorizations->isEmpty() &&
                $overCapacityPlans->isEmpty() &&
                $missingAnteMortemPlans->isEmpty() &&
                $missingPostMortemBatches->isEmpty() &&
                $missingCertificateBatches->isEmpty() &&
                $missingTransportCertificates->isEmpty()
            )
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p>{{ __('No compliance issues detected for your facilities.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
