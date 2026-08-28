@php
    $v = $certificateView;
    $blank = fn ($value) => ($value !== null && trim((string) $value) !== '' && trim((string) $value) !== '—') ? $value : '—';
    $ownerName = $blank($v['butcherName'] ?: ($v['owner']->name ?? null));
    $ownerLocation = $blank($v['sellingLocationLine']);
@endphp

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">1.</span> IBAGIRO
        <span class="rica-section-title-rw">/ {{ __('Livestock owner') }}</span>
    </h2>
    <table class="rica-field-table">
        <tr>
            <td class="field-label">IZINA RY'IBAGIRO</td>
            <td class="field-value" colspan="3">{{ $ownerName }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Location') }} (District, Sector, Cell)</td>
            <td class="field-value" colspan="3">{{ $ownerLocation }}</td>
        </tr>
        <tr>
            <td class="field-label">TELEPHONE</td>
            <td class="field-value" colspan="3">{{ $blank($v['ownerPhone']) }}</td>
        </tr>
    </table>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">2.</span> IBIRANGA ITUNGO
        <span class="rica-section-title-rw">/ {{ __('Animal identification') }}</span>
    </h2>
    <table class="rica-field-table">
        <tr>
            <td class="field-label">Ubwoko</td>
            <td class="field-value">{{ $blank($v['species']) }}</td>
            <td class="field-label">Iherena n°</td>
            <td class="field-value font-mono">{{ $blank($v['earTagNumbers']) }}</td>
        </tr>
    </table>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">3.</span> UMWIRONDORO WA NYIRI BUSHERI
        <span class="rica-section-title-rw">/ {{ __('Butcher / meat selling shop') }}</span>
    </h2>
    <table class="rica-field-table">
        <tr>
            <td class="field-label">Amazina</td>
            <td class="field-value" colspan="3">{{ $ownerName }}</td>
        </tr>
        <tr>
            <td class="field-label">Izina rya busheri</td>
            <td class="field-value" colspan="3">{{ $blank($v['shopName']) }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Selling location') }}</td>
            <td class="field-value" colspan="3">{{ $ownerLocation }}</td>
        </tr>
        <tr>
            <td class="field-label">TELEPHONE</td>
            <td class="field-value" colspan="3">{{ $blank($v['shopPhone']) }}</td>
        </tr>
    </table>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">4.</span> UBUREMERE N'IGIPIMO CY'UBUSHYUHE
        <span class="rica-section-title-rw">/ {{ __('Meat weight and temperature') }}</span>
    </h2>
    <div class="rica-table-wrap">
        <table class="rica-table">
            <thead>
                <tr>
                    <th>Iherena n°</th>
                    <th class="text-center">{{ __('Carcass meat (Kg)') }}</th>
                    <th class="text-center">{{ __('Other meat (Kg)') }}</th>
                    <th class="text-center">{{ __('Temperature (°C)') }}</th>
                </tr>
            </thead>
            <tbody>
                @if (! empty($v['releasedAnimals']))
                    @foreach ($v['releasedAnimals'] as $releasedAnimal)
                        <tr>
                            <td class="font-mono">{{ $releasedAnimal['ear_tag'] }}</td>
                            <td class="text-center tabular-nums">{{ number_format($releasedAnimal['quantity_kg'], 2) }}</td>
                            <td class="text-center tabular-nums">{{ $loop->first ? number_format($v['otherMeatKg'], 2) : '—' }}</td>
                            <td class="text-center tabular-nums">
                                @if ($loop->first && $v['temperatureCelsius'] !== null)
                                    {{ number_format((float) $v['temperatureCelsius'], 1) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if (count($v['releasedAnimals']) > 1)
                        <tr>
                            <td class="font-bold">{{ __('Total') }}</td>
                            <td class="text-center tabular-nums font-bold">{{ number_format($v['carcassMeatKg'], 2) }}</td>
                            <td class="text-center tabular-nums font-bold">{{ number_format($v['otherMeatKg'], 2) }}</td>
                            <td class="text-center tabular-nums">
                                {{ $v['temperatureCelsius'] !== null ? number_format((float) $v['temperatureCelsius'], 1) : '—' }}
                            </td>
                        </tr>
                    @endif
                @else
                    <tr>
                        <td>—</td>
                        <td class="text-center tabular-nums">{{ number_format($v['carcassMeatKg'], 2) }}</td>
                        <td class="text-center tabular-nums">{{ number_format($v['otherMeatKg'], 2) }}</td>
                        <td class="text-center tabular-nums">
                            {{ $v['temperatureCelsius'] !== null ? number_format((float) $v['temperatureCelsius'], 1) : '—' }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">5.</span> UMWIRONDORO W'UTWAYE INYAMA
        <span class="rica-section-title-rw">/ {{ __('Authorized meat transporter') }}</span>
    </h2>
    <table class="rica-field-table">
        <tr>
            <td class="field-label">{{ __('License holder') }}</td>
            <td class="field-value" colspan="3">{{ $blank($v['transporterLicenseHolder']) }}</td>
        </tr>
        <tr>
            <td class="field-label">Purake</td>
            <td class="field-value">{{ $blank($v['vehiclePlateNumber']) }}</td>
            <td class="field-label">{{ __('Driver') }}</td>
            <td class="field-value">{{ $blank($v['driverName']) }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Departure time') }}</td>
            <td class="field-value">{{ $blank($v['departureTime']) }}</td>
            <td class="field-label">TELEPHONE</td>
            <td class="field-value">{{ $blank($v['transporterPhone']) }}</td>
        </tr>
        @if ($blank($v['departureDestination']) !== '—')
            <tr>
                <td class="field-label">{{ __('Destination') }}</td>
                <td class="field-value" colspan="3">{{ $blank($v['departureDestination']) }}</td>
            </tr>
        @endif
    </table>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">6.</span> KWEMEZA
        <span class="rica-section-title-rw">/ {{ __('Certification') }}</span>
    </h2>
    <table class="rica-field-table">
        <tr>
            <td class="field-label">{{ __('Certificate number') }}</td>
            <td class="field-value">{{ $certificateNumber }}</td>
            <td class="field-label">Tariki</td>
            <td class="field-value">{{ $v['issuedAtFormatted'] ?? ($v['issuedDay'].'/'.$v['issuedMonth'].'/'.$v['issuedYear']) }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Veterinarian') }}</td>
            <td class="field-value" colspan="3">{{ $v['certificate']->inspector?->full_name ?: $inspectorName }}</td>
        </tr>
        @if ($v['batch'])
            <tr>
                <td class="field-label">{{ __('Batch') }}</td>
                <td class="field-value font-mono">{{ $v['batch']->batch_code }}</td>
                <td class="field-label">{{ __('Slaughter date') }}</td>
                <td class="field-value">{{ $slaughterDate }}</td>
            </tr>
        @endif
    </table>
    <p class="rica-cert-note mt-3">
        Njyewe
        <span class="inline-block min-w-[12rem] border-b border-dotted border-slate-700 px-2 font-semibold">{{ $v['certificate']->inspector?->full_name ?: $inspectorName }}</span>
        Veterineri ushinzwe ubugenzuzi bw'inyama ku ibagiro ryavuzwe haruguru, nshingiye ku bugenzuzi n'isuzuma nakoze ngendeye ku mategeko n'amabwiriza abigenga mu Rwanda, ndemeza ko inyama zivugwa muri iki cyemezo zateguriwe mu ibagiro nshinzwe hubahirizwa ibisabwa byose, kandi ko nta ndwara, ubwandu, cyangwa ikindi cyazibuza gucuruzwa no gukoreshwa mu mafunguro agenewe abantu.
    </p>
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
        <div>
            <p>
                Tariki
                <span class="inline-block min-w-[2rem] border-b border-dotted border-slate-700 text-center font-semibold">{{ $v['issuedDay'] ?? '' }}</span>
                /
                <span class="inline-block min-w-[2.5rem] border-b border-dotted border-slate-700 text-center font-semibold">{{ $v['issuedMonth'] ?? '' }}</span>
                / 20
                <span class="inline-block min-w-[2rem] border-b border-dotted border-slate-700 text-center font-semibold">{{ isset($v['issuedYear']) ? substr((string) $v['issuedYear'], -2) : '' }}</span>
            </p>
            <p class="mt-6">Umukono na kashe bya veterineri.</p>
            <div class="mt-8 border-b border-slate-800"></div>
        </div>
        <div class="sm:text-right font-semibold">
            Tera muri aya magambo cashe y'ibagiro
        </div>
    </div>
</section>
