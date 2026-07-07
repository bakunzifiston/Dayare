@php
    $closure = $report['closure'];
    $fmtSigned = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '—';
@endphp

<div class="rica-closure-sections space-y-5">
    <section class="rica-section">
        <h2 class="rica-section-title">
            <span class="rica-section-num">7.</span> {{ __('CHALLENGES') }}
            <span class="rica-section-title-rw">/ {{ __('IMBOGAMIZI') }}</span>
        </h2>
        <div class="rica-challenges-box">{{ $closure['challenges'] ?: '—' }}</div>
    </section>

    <section class="rica-section">
        <h2 class="rica-section-title">
            <span class="rica-section-num">8.</span> {{ __('CONCLUSION & RECOMMENDATIONS') }}
            <span class="rica-section-title-rw">/ {{ __('UMWANZURO N\'INAMA') }}</span>
        </h2>
        @if (! empty($closure['recommendations']))
            <div class="rica-challenges-box">{{ $closure['recommendations'] }}</div>
        @endif

        <p class="rica-sub-heading">
            {{ __('Private Meat Inspector(s)') }} / {{ __('Umu(aba)genzuzi w\'(b\')inyama w(b)igenga') }}
        </p>
        @forelse ($closure['inspector_signatures'] as $index => $signature)
            <div class="rica-signature-block">
                <table>
                    <tr>
                        <td style="width:55%;">{{ $index + 1 }}. {{ __('Names') }} / {{ __('Amazina') }}:</td>
                        <td style="width:22%;">{{ __('Signature') }} / {{ __('Umukono') }}:</td>
                        <td style="width:23%;">{{ __('Date') }} / {{ __('Itariki') }}:</td>
                    </tr>
                    <tr>
                        <td class="font-semibold">{{ $signature['name'] ?: '—' }}</td>
                        <td>{{ filled($signature['signed_at'] ?? null) ? __('Signed') : '—' }}</td>
                        <td>{{ $fmtSigned($signature['signed_at'] ?? null) }}</td>
                    </tr>
                </table>
            </div>
        @empty
            <p class="rica-empty">{{ __('No inspector signatures recorded.') }}</p>
        @endforelse

        <p class="rica-sub-heading">
            {{ __('Slaughterhouse operator') }} / {{ __('Ucunga ibagiro') }}
        </p>
        <div class="rica-signature-block">
            <table>
                <tr>
                    <td style="width:55%;">{{ __('Names') }} / {{ __('Amazina') }}:</td>
                    <td style="width:22%;">{{ __('Signature') }} / {{ __('Umukono') }}:</td>
                    <td style="width:23%;">{{ __('Date') }} / {{ __('Itariki') }}:</td>
                </tr>
                <tr>
                    <td class="font-semibold">{{ $closure['operator_name'] ?: '—' }}</td>
                    <td>{{ filled($closure['operator_signed_at'] ?? null) ? __('Signed') : '—' }}</td>
                    <td>{{ $fmtSigned($closure['operator_signed_at']) }}</td>
                </tr>
            </table>
        </div>

        <p class="rica-sub-heading">
            {{ __('Slaughterhouse stamp') }} / {{ __('Kashi y\'ibagiro') }}:
            <span class="font-normal">{{ ($closure['stamp_acknowledged'] ?? false) ? __('Confirmed') : '—' }}</span>
        </p>

        @if (($closure['status'] ?? '') === 'submitted')
            <p class="rica-submitted-note">
                {{ __('Submitted to RICA') }}: {{ $fmtSigned($closure['submitted_at'] ?? null) }}
                @if (! empty($closure['submitted_by_name']))
                    · {{ $closure['submitted_by_name'] }}
                @endif
            </p>
        @endif
    </section>
</div>
