@props([
    'facility',
    'monthValue',
    'report',
    'closureRoute',
])

@php
    use App\Models\RicaMonthlyInspectionReport;

    $closure = $report['closure'];
    $isSubmitted = ($closure['status'] ?? RicaMonthlyInspectionReport::STATUS_DRAFT) === RicaMonthlyInspectionReport::STATUS_SUBMITTED;
    $signatureRows = old(
        'inspector_signatures',
        $closure['inspector_signatures'] ?? [['name' => null, 'signed_at' => null]]
    );
    while (count($signatureRows) < 3) {
        $signatureRows[] = ['name' => null, 'signed_at' => null];
    }
    $fmtSigned = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : null;
@endphp

@if (session('status'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-medium">{{ __('Please fix the following:') }}</p>
        <ul class="mt-2 list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($isSubmitted)
    @include('partials.monthly-inspection-report.closure-readonly', ['report' => $report])
@else
    <div class="space-y-4 print:hidden">
        <p class="text-sm text-slate-600">{{ __('Complete sections 7–8 below, then save a draft or submit to RICA.') }}</p>
        <form method="post" action="{{ $closureRoute }}" class="space-y-6">
            @csrf
            <input type="hidden" name="month" value="{{ $monthValue }}">

            <section class="rica-section">
                <h2 class="rica-section-title">
                    <span class="rica-section-num">7.</span> {{ __('CHALLENGES') }}
                    <span class="rica-section-title-rw">/ {{ __('IMBOGAMIZI') }}</span>
                </h2>
                <textarea id="challenges" name="challenges" rows="4" class="rica-challenges-box block w-full text-sm focus:border-[#00a651] focus:ring-[#00a651]" placeholder="{{ __('Describe challenges during this month…') }}">{{ old('challenges', $closure['challenges']) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('challenges')" />
            </section>

            <section class="rica-section">
                <h2 class="rica-section-title">
                    <span class="rica-section-num">8.</span> {{ __('CONCLUSION & RECOMMENDATIONS') }}
                    <span class="rica-section-title-rw">/ {{ __('UMWANZURO N\'INAMA') }}</span>
                </h2>
                <textarea id="recommendations" name="recommendations" rows="3" class="rica-challenges-box block w-full text-sm focus:border-[#00a651] focus:ring-[#00a651]" placeholder="{{ __('Summary and recommendations…') }}">{{ old('recommendations', $closure['recommendations']) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('recommendations')" />

                <p class="rica-sub-heading">{{ __('Signatures') }}</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($signatureRows as $index => $signature)
                            @php
                                $signedAt = $signature['signed_at'] ?? null;
                                $isSigned = filled($signedAt);
                            @endphp
                            <div class="rica-signature-block space-y-2">
                                <p class="text-xs text-slate-500">{{ __('Inspector') }} {{ $index + 1 }}</p>
                                <x-text-input :id="'inspector_name_'.$index" name="inspector_signatures[{{ $index }}][name]" type="text" class="block w-full text-sm" :value="old('inspector_signatures.'.$index.'.name', $signature['name'] ?? '')" placeholder="{{ __('Name') }}" />
                                <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                                    <input type="checkbox" name="inspector_signatures[{{ $index }}][attest]" value="1" class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary" @checked(old('inspector_signatures.'.$index.'.attest', $isSigned))>
                                    <span>{{ __('Attest signature') }}</span>
                                </label>
                                @if ($isSigned)
                                    <p class="text-xs text-green-700">{{ $fmtSigned($signedAt) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('inspector_signatures')" />

                    <div class="rica-signature-block space-y-2 max-w-md">
                        <p class="text-xs text-slate-500">{{ __('Slaughterhouse operator') }}</p>
                        <x-text-input id="operator_name" name="operator_name" type="text" class="block w-full text-sm" :value="old('operator_name', $closure['operator_name'])" placeholder="{{ __('Name') }}" />
                        <x-input-error :messages="$errors->get('operator_name')" />
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="operator_attest" value="1" class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary" @checked(old('operator_attest', filled($closure['operator_signed_at'])))>
                            <span>{{ __('Attest operator signature') }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('operator_attest')" />
                    </div>

                    <label class="inline-flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="stamp_acknowledged" value="1" class="mt-0.5 rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary" @checked(old('stamp_acknowledged', $closure['stamp_acknowledged']))>
                        <span>{{ __('Slaughterhouse stamp applied on the official record') }}</span>
                    </label>
                    <x-input-error :messages="$errors->get('stamp_acknowledged')" />
            </section>

            <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-200">
                <button type="submit" name="submit_to_rica" value="0" class="rica-btn rica-btn--secondary">
                    {{ __('Save draft') }}
                </button>
                <button type="submit" name="submit_to_rica" value="1" class="rica-btn rica-btn--primary"
                        onclick="return confirm(@js(__('Submit to RICA? This report cannot be edited afterwards.')));">
                    {{ __('Submit to RICA') }}
                </button>
            </div>
        </form>
    </div>
@endif
