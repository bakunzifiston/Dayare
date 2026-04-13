<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800">{{ __('Health certificate') }} #{{ $healthCertificate->certificate_number }}</h2>
            <a href="{{ route('farmer.health-certificates.download', $healthCertificate) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">
                {{ __('Download file') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-4">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-bucha border border-slate-200/60 p-6 grid sm:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-slate-500">{{ __('Certificate type') }}</p>
                <p class="font-medium text-slate-900 capitalize">{{ str_replace('_', ' ', $healthCertificate->certificate_type) }}</p>
            </div>
            <div>
                <p class="text-slate-500">{{ __('Current validity') }}</p>
                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $isValidToday ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900' }}">
                    {{ $isValidToday ? __('Valid') : __('Expired') }}
                </span>
            </div>
            <div>
                <p class="text-slate-500">{{ __('Farm') }}</p>
                <p class="font-medium text-slate-900">{{ $healthCertificate->farm?->name }}</p>
            </div>
            <div>
                <p class="text-slate-500">{{ __('Livestock') }}</p>
                <p class="font-medium text-slate-900">{{ $healthCertificate->livestock ? ucfirst($healthCertificate->livestock->type).' #'.$healthCertificate->livestock->id : '—' }}</p>
            </div>
            <div>
                <p class="text-slate-500">{{ __('Batch reference') }}</p>
                <p class="font-medium text-slate-900">{{ $healthCertificate->batch_reference ?: '—' }}</p>
            </div>
            <div>
                <p class="text-slate-500">{{ __('Issued by') }}</p>
                <p class="font-medium text-slate-900">{{ $healthCertificate->issued_by }}</p>
            </div>
            <div>
                <p class="text-slate-500">{{ __('Issue date') }}</p>
                <p class="font-medium text-slate-900">{{ $healthCertificate->issue_date?->toDateString() }}</p>
            </div>
            <div>
                <p class="text-slate-500">{{ __('Expiry date') }}</p>
                <p class="font-medium text-slate-900">{{ $healthCertificate->expiry_date?->toDateString() ?? '—' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-slate-500">{{ __('Source health record') }}</p>
                <p class="font-medium text-slate-900">
                    @if ($healthCertificate->sourceHealthRecord)
                        {{ __('Record') }} #{{ $healthCertificate->sourceHealthRecord->id }} — {{ $healthCertificate->sourceHealthRecord->condition }}
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-slate-500">{{ __('Notes') }}</p>
                <p class="font-medium text-slate-900 whitespace-pre-line">{{ $healthCertificate->notes ?: '—' }}</p>
            </div>
        </div>
    </div>
</x-app-layout>

