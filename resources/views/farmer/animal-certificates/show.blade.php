<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Certificate details') }}</h2></x-slot>
    <div class="max-w-5xl space-y-6">
        @include('farmer.animal-certificates.partials.nav')
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-xs uppercase tracking-wide text-slate-500">{{ $certificate->certificate_number }}</p><h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $certificate->certificate_title }}</h3></div>
                <x-certificate-status-badge :status="$certificate->certificate_status" />
            </div>
            <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                <div><dt class="text-slate-500">{{ __('Animal') }}</dt><dd class="mt-1 font-medium">{{ $certificate->animal?->animal_code }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Verification URL') }}</dt><dd class="mt-1 break-all"><a href="{{ $certificate->verificationUrl() }}" class="text-bucha-primary hover:underline" target="_blank">{{ $certificate->verificationUrl() }}</a></dd></div>
                <div><dt class="text-slate-500">{{ __('Issue date') }}</dt><dd class="mt-1">{{ $certificate->issue_date?->toDateString() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Expiry date') }}</dt><dd class="mt-1">{{ $certificate->expiry_date?->toDateString() ?: '—' }}</dd></div>
            </dl>
        </section>
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <h3 class="font-semibold text-slate-900">{{ __('Traceability summary') }}</h3>
            <ul class="mt-3 space-y-2"><li>{{ $summary['ownership_summary'] }}</li><li>{{ $summary['health_summary'] }}</li><li>{{ $summary['vaccination_summary'] }}</li><li>{{ $summary['feeding_summary'] }}</li></ul>
        </section>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('farmer.certificates.animal-certificates.edit', $certificate) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Edit') }}</a>
            <a href="{{ route('farmer.certificates.animal-certificates.download', $certificate) }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Download PDF') }}</a>
            <a href="{{ route('farmer.certificates.animal-certificates.qr', $certificate) }}" target="_blank" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Download QR') }}</a>
            @if ($certificate->certificate_status === \App\Models\AnimalCertificate::STATUS_ACTIVE)
                <form method="post" action="{{ route('farmer.certificates.animal-certificates.revoke', $certificate) }}">@csrf<button type="submit" class="inline-flex items-center rounded-bucha border border-red-200 px-4 py-2 text-sm text-red-700">{{ __('Revoke') }}</button></form>
            @endif
        </div>
    </div>
</x-app-layout>
