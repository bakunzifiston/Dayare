<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('certificates.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Certificates') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Edit certificate') }} — {{ $certificate->certificate_number ?: '#' . $certificate->id }}
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bucha-wizard-panel">
                @if ($errors->any())
                    <div class="mb-6 rounded-bucha border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-medium">{{ __('Please fix the following:') }}</p>
                        <ul class="mt-2 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('certificates.update', $certificate) }}" class="bucha-wizard-form" id="certificate-edit-form">
                    @csrf
                    @method('put')

                    <x-wizard-section :title="__('Source')">
                        <x-wizard-field :label="__('Slaughter execution')">
                            <p class="bucha-wizard-input flex items-center bg-slate-50 text-slate-800">{{ $executionLabel ?? '—' }}</p>
                        </x-wizard-field>
                        <input type="hidden" name="batch_id" value="{{ old('batch_id', $certificate->batch_id) }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />

                        <x-wizard-field :label="__('Certified animal')">
                            <p class="bucha-wizard-input flex items-center bg-slate-50 font-mono text-slate-900">{{ $certifiedAnimalLabel ?? '—' }}</p>
                        </x-wizard-field>
                    </x-wizard-section>

                    @include('certificates.partials.issue-fields', [
                        'certificate' => $certificate,
                        'inspectorsByFacility' => $inspectorsByFacility,
                        'facilities' => $facilities,
                        'pdfDefaults' => $pdfDefaults ?? [],
                        'savedPdfDetails' => $savedPdfDetails ?? [],
                        'submitLabel' => __('Update certificate'),
                    ])
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const facilityId = @json((string) ($certificate->facility_id ?? ''));
            const inspectorSelect = document.getElementById('inspector_id');
            const facilitySelect = document.getElementById('facility_id');

            function filterByFacility() {
                if (inspectorSelect) {
                    Array.from(inspectorSelect.options).forEach(opt => {
                        if (opt.value === '') { opt.hidden = false; return; }
                        opt.hidden = opt.dataset.facilityId !== facilityId;
                    });
                    const cur = inspectorSelect.options[inspectorSelect.selectedIndex];
                    if (cur && cur.hidden) {
                        const v = Array.from(inspectorSelect.options).find(o => o.value && !o.hidden);
                        inspectorSelect.value = v ? v.value : '';
                    }
                }
                if (facilitySelect) {
                    Array.from(facilitySelect.options).forEach(opt => {
                        opt.hidden = opt.value !== '' && opt.dataset.facilityId !== facilityId;
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', filterByFacility);
        })();
    </script>
</x-app-layout>
