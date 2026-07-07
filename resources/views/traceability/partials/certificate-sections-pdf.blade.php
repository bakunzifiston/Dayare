@php
    $v = $certificateView;
    $blank = fn ($value) => ($value !== null && trim((string) $value) !== '' && trim((string) $value) !== '—') ? $value : '—';
    $ownerName = $blank($v['butcherName'] ?: ($v['owner']->name ?? null));
    $ownerLocation = $blank($v['sellingLocationLine']);
@endphp

<div class="section">
    <div class="section-title">1. IBAGIRO / {{ __('Livestock owner') }}</div>
    <table class="field-table">
        <tr>
            <td class="field-label">IZINA RY'IBAGIRO</td>
            <td class="field-value" colspan="3">{{ $ownerName }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Location') }}</td>
            <td class="field-value" colspan="3">{{ $ownerLocation }}</td>
        </tr>
        <tr>
            <td class="field-label">TELEPHONE</td>
            <td class="field-value" colspan="3">{{ $blank($v['ownerPhone']) }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">2. IBIRANGA ITUNGO / {{ __('Animal identification') }}</div>
    <table class="field-table">
        <tr>
            <td class="field-label">Ubwoko</td>
            <td class="field-value">{{ $blank($v['species']) }}</td>
            <td class="field-label">Iherena n°</td>
            <td class="field-value">{{ $blank($v['earTagNumbers']) }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">3. UMWIRONDORO WA NYIRI BUSHERI / {{ __('Butcher / shop') }}</div>
    <table class="field-table">
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
</div>

<div class="section">
    <div class="section-title">4. UBUREMERE N'IGIPIMO CY'UBUSHYUHE / {{ __('Meat weight and temperature') }}</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Iherena n°</th>
                <th>{{ __('Carcass meat (Kg)') }}</th>
                <th>{{ __('Other meat (Kg)') }}</th>
                <th>{{ __('Temperature (°C)') }}</th>
            </tr>
        </thead>
        <tbody>
            @if (! empty($v['releasedAnimals']))
                @foreach ($v['releasedAnimals'] as $releasedAnimal)
                    <tr>
                        <td>{{ $releasedAnimal['ear_tag'] }}</td>
                        <td class="num">{{ number_format($releasedAnimal['quantity_kg'], 2) }}</td>
                        <td class="num">{{ $loop->first ? number_format($v['otherMeatKg'], 2) : '—' }}</td>
                        <td class="num">
                            @if ($loop->first && $v['temperatureCelsius'] !== null)
                                {{ number_format((float) $v['temperatureCelsius'], 1) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if (count($v['releasedAnimals']) > 1)
                    <tr class="total-row">
                        <td>{{ __('Total') }}</td>
                        <td class="num">{{ number_format($v['carcassMeatKg'], 2) }}</td>
                        <td class="num">{{ number_format($v['otherMeatKg'], 2) }}</td>
                        <td class="num">{{ $v['temperatureCelsius'] !== null ? number_format((float) $v['temperatureCelsius'], 1) : '—' }}</td>
                    </tr>
                @endif
            @else
                <tr>
                    <td>—</td>
                    <td class="num">{{ number_format($v['carcassMeatKg'], 2) }}</td>
                    <td class="num">{{ number_format($v['otherMeatKg'], 2) }}</td>
                    <td class="num">{{ $v['temperatureCelsius'] !== null ? number_format((float) $v['temperatureCelsius'], 1) : '—' }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">5. UMWIRONDORO W'UTWAYE INYAMA / {{ __('Transporter') }}</div>
    <table class="field-table">
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
</div>

<div class="section">
    <div class="section-title">6. KWEMEZA / {{ __('Certification') }}</div>
    <table class="field-table">
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
                <td class="field-value">{{ $v['batch']->batch_code }}</td>
                <td class="field-label">{{ __('Slaughter date') }}</td>
                <td class="field-value">{{ $slaughterDate }}</td>
            </tr>
        @endif
    </table>
    <p class="cert-note">{{ __('This certificate was issued after veterinary meat inspection at the slaughterhouse.') }}</p>
</div>
