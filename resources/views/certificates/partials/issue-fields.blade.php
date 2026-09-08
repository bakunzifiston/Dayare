@props([
    'certificate' => null,
    'inspectorsByFacility' => [],
    'facilities' => [],
    'pdfDefaults' => [],
    'savedPdfDetails' => [],
    'defaultInspectorId' => null,
    'defaultFacilityId' => null,
    'defaultSlaughterhouseName' => null,
    'executionsEmpty' => false,
    'submitLabel',
])

@php
    $isEdit = $certificate !== null;
    $slaughterhouseDefault = $isEdit
        ? ($certificate->slaughterhouse_display_name ?: \App\Services\Processor\CertificatePdfService::NYAGATARE_FACILITY_NAME)
        : ($defaultSlaughterhouseName ?? \App\Services\Processor\CertificatePdfService::NYAGATARE_FACILITY_NAME);
@endphp

<x-wizard-section :title="__('Inspection')">
    <x-wizard-field for="inspector_id" :label="__('Inspector')" required>
        <select id="inspector_id" name="inspector_id" class="bucha-wizard-select" required @disabled($executionsEmpty)>
            <option value="">{{ $isEdit ? __('Select inspector') : __('Select slaughter execution first') }}</option>
            @foreach ($inspectorsByFacility as $fid => $inspectors)
                @foreach ($inspectors as $insp)
                    <option
                        value="{{ $insp['id'] }}"
                        data-facility-id="{{ $fid }}"
                        @selected((string) old('inspector_id', $certificate?->inspector_id ?? $defaultInspectorId) === (string) $insp['id'])
                    >{{ $insp['label'] }}</option>
                @endforeach
            @endforeach
        </select>
    </x-wizard-field>
    <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />

    <x-wizard-field for="facility_id" :label="__('Facility')" required>
        <select id="facility_id" name="facility_id" class="bucha-wizard-select" required @disabled($executionsEmpty)>
            @unless ($isEdit)
                <option value="">{{ __('Select slaughter execution first') }}</option>
            @endunless
            @foreach ($facilities as $f)
                <option
                    value="{{ $f['id'] }}"
                    data-facility-id="{{ $f['id'] }}"
                    @selected((string) old('facility_id', $certificate?->facility_id ?? $defaultFacilityId) === (string) $f['id'])
                >{{ $f['label'] }}</option>
            @endforeach
        </select>
    </x-wizard-field>
    <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />

    <x-wizard-field for="slaughterhouse_display_name" :label="__('Slaughterhouse name (on certificate)')" required>
        <input
            id="slaughterhouse_display_name"
            name="slaughterhouse_display_name"
            type="text"
            class="bucha-wizard-input uppercase"
            value="{{ old('slaughterhouse_display_name', $slaughterhouseDefault) }}"
            required
        />
    </x-wizard-field>
    <x-input-error class="mt-2" :messages="$errors->get('slaughterhouse_display_name')" />
</x-wizard-section>

@include('certificates.partials.pdf-details-form', [
    'pdfDefaults' => $pdfDefaults,
    'savedPdfDetails' => $savedPdfDetails,
])

<x-wizard-section :title="__('Certificate')">
    <x-wizard-field for="certificate_number" :label="__('Certificate number')">
        <input id="certificate_number" name="certificate_number" type="text" class="bucha-wizard-input" value="{{ old('certificate_number', $certificate?->certificate_number) }}" />
    </x-wizard-field>
    <x-input-error class="mt-2" :messages="$errors->get('certificate_number')" />

    <div class="bucha-wizard-grid">
        <div>
            <x-wizard-field for="issued_at" :label="__('Issue date')" required>
                <input id="issued_at" name="issued_at" type="date" class="bucha-wizard-input" value="{{ old('issued_at', $certificate?->issued_at?->format('Y-m-d') ?? date('Y-m-d')) }}" required />
            </x-wizard-field>
            <x-input-error class="mt-2" :messages="$errors->get('issued_at')" />
        </div>
        <div>
            <x-wizard-field for="expiry_date" :label="__('Expiry date (if applicable)')">
                <input id="expiry_date" name="expiry_date" type="date" class="bucha-wizard-input" value="{{ old('expiry_date', $certificate?->expiry_date?->format('Y-m-d')) }}" />
            </x-wizard-field>
            <x-input-error class="mt-2" :messages="$errors->get('expiry_date')" />
        </div>
    </div>

    <x-wizard-field for="status" :label="__('Status')">
        <select id="status" name="status" class="bucha-wizard-select">
            @foreach (\App\Models\Certificate::STATUSES as $s)
                <option value="{{ $s }}" @selected(old('status', $certificate?->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </x-wizard-field>
    <x-input-error class="mt-2" :messages="$errors->get('status')" />
</x-wizard-section>

<div class="flex flex-wrap items-center gap-3 pt-2">
    <x-primary-button :disabled="$executionsEmpty">{{ $submitLabel }}</x-primary-button>
    <a href="{{ route('certificates.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-bucha font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
        {{ __('Cancel') }}
    </a>
</div>
