<!doctype html>
<html lang="rw">
<head>
    <meta charset="utf-8">
    <title>ICYEMEZO CYA VETERINERI KU BUGENZUZI BW'INYAMA</title>
    <style>
        @page { margin: 8mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #000;
            line-height: 1.25;
            margin: 0;
        }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: middle; }

        .form-shell { width: 100%; border: 2px solid #000; }
        .form-shell td { border: 1px solid #000; }

        .seal-cell {
            width: 72px;
            text-align: center;
            vertical-align: middle;
            padding: 6px 4px;
        }
        .seal-placeholder {
            width: 58px;
            height: 58px;
            border: 1px solid #333;
            border-radius: 50%;
            margin: 0 auto 4px;
            font-size: 7px;
            color: #444;
            line-height: 1.1;
            padding-top: 18px;
        }
        .republic-line {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .header-main {
            padding: 6px 8px;
            text-align: center;
        }
        .facility-name {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 4px;
        }
        .division-row td {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            padding: 3px 6px;
        }

        .title-sidebar {
            width: 28px;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 8px 2px;
            line-height: 1.2;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 6px;
            background: #f3f4f6;
        }

        .field-table { width: 100%; }
        .field-table td {
            border: 1px solid #000;
            padding: 0;
        }
        .field-label {
            width: 34%;
            font-size: 8.5px;
            font-weight: bold;
            padding: 5px 6px;
            background: #fafafa;
        }
        .field-value {
            font-size: 9.5px;
            font-weight: 600;
            padding: 5px 8px;
            min-height: 22px;
        }

        .meat-table th,
        .meat-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 8.5px;
        }
        .meat-table th {
            font-weight: bold;
            background: #fafafa;
        }
        .meat-table td {
            font-size: 10px;
            font-weight: 600;
            min-height: 26px;
        }

        .cert-text {
            padding: 8px 8px 6px;
            text-align: justify;
            font-size: 8.5px;
            line-height: 1.45;
        }
        .name-line {
            display: inline-block;
            border-bottom: 1px dotted #000;
            min-width: 220px;
            padding: 0 4px 1px;
            font-weight: 600;
            text-align: center;
        }

        .date-row td {
            padding: 8px 8px 4px;
            font-size: 9px;
            border: none;
        }
        .date-box {
            display: inline-block;
            border-bottom: 1px dotted #000;
            min-width: 36px;
            text-align: center;
            font-weight: 600;
            padding: 0 4px 1px;
        }

        .signature-cell {
            padding: 6px 8px 10px;
            vertical-align: top;
            border: none;
        }
        .signature-label {
            font-size: 8.5px;
            margin-bottom: 4px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            min-height: 36px;
            margin-top: 8px;
        }
        .inspector-name {
            margin-top: 4px;
            font-size: 9px;
            font-weight: 600;
        }
        .stamp-instruction {
            font-size: 9px;
            font-weight: bold;
            text-align: right;
            padding-top: 28px;
            line-height: 1.35;
        }

        .footer-row td {
            border: none;
            padding-top: 8px;
            font-size: 7px;
            color: #555;
            vertical-align: bottom;
        }
        .qr-wrap { text-align: right; }
        .qr-caption {
            font-size: 6.5px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
@php
    $blank = fn ($value) => ($value !== null && trim((string) $value) !== '') ? $value : '—';
    $ownerLocation = $sellingLocationLine;
    $ownerName = $butcherName ?: ($owner->name ?? '—');
@endphp

<table class="form-shell">
    {{-- Header: seal + slaughterhouse + divisions --}}
    <tr>
        <td class="seal-cell" rowspan="4">
            <div class="seal-placeholder">REPUBULIKA<br>Y'U RWANDA</div>
            <div class="republic-line">Republika y'u Rwanda</div>
        </td>
        <td class="header-main" colspan="2">
            <div class="facility-name">{{ $slaughterhouseDisplayName }}</div>
        </td>
    </tr>
    <tr>
        <td class="division-row" colspan="2">
            <table class="field-table">
                <tr>
                    <td class="field-label" style="width: 22%;">District</td>
                    <td class="field-value">{{ $headerDistrictLine }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="division-row" colspan="2">
            <table class="field-table">
                <tr>
                    <td class="field-label" style="width: 22%;">Sector</td>
                    <td class="field-value">{{ $headerSectorLine }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="division-row" colspan="2">
            <table class="field-table">
                <tr>
                    <td class="field-label" style="width: 22%;">Cell</td>
                    <td class="field-value">{{ $headerCellLine }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Main body with vertical title --}}
    <tr>
        <td class="title-sidebar" rowspan="20">
            INYITO: ICYEMEZO CYA VETERINERI KU BUGENZUZI BW'INYAMA
        </td>
        <td colspan="2" style="padding: 0;">
            {{-- Section 1: IBAGIRO --}}
            <div class="section-title">1. IBAGIRO</div>
            <table class="field-table">
                <tr>
                    <td class="field-label">IZINA RY'IBAGIRO</td>
                    <td class="field-value">{{ $blank($ownerName) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Aho ribarizwa (District, Sector, Cell)</td>
                    <td class="field-value">{{ $blank($ownerLocation) }}</td>
                </tr>
                <tr>
                    <td class="field-label">TELEPHONE</td>
                    <td class="field-value">{{ $blank($ownerPhone) }}</td>
                </tr>
            </table>

            {{-- Section 2: IBIRANGA ITUNGO --}}
            <div class="section-title">2. IBIRANGA ITUNGO</div>
            <table class="field-table">
                <tr>
                    <td class="field-label">Ubwoko</td>
                    <td class="field-value">{{ $blank($species) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Iherena n°</td>
                    <td class="field-value">{{ $blank($earTagNumbers) }}</td>
                </tr>
            </table>

            {{-- Section 3: Butcher / shop --}}
            <div class="section-title">3. UMWIRONDORO WA NYIRI BUSHERI / IDUKA RICURURIZA INYAMA</div>
            <table class="field-table">
                <tr>
                    <td class="field-label">Amazina</td>
                    <td class="field-value">{{ $blank($butcherName ?: ($owner->name ?? null)) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Izina rya busheri</td>
                    <td class="field-value">{{ $blank($shopName) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Aho acururiza (District, Sector, Cell)</td>
                    <td class="field-value">{{ $blank($ownerLocation) }}</td>
                </tr>
                <tr>
                    <td class="field-label">TELEPHONE</td>
                    <td class="field-value">{{ $blank($shopPhone) }}</td>
                </tr>
            </table>

            {{-- Section 4: Weight / temperature --}}
            <div class="section-title">4. UBUREMERE N'IGIPIMO CY'UBUSHYUHE</div>
            <table class="meat-table">
                <thead>
                    <tr>
                        <th>Iherena n°</th>
                        <th>Inyama z'umubiri (Kg)</th>
                        <th>Izindi nyama (Kg)</th>
                        <th>Igipimo cy'ubushyuhe (°C)</th>
                    </tr>
                </thead>
                <tbody>
                    @if (! empty($releasedAnimals))
                        @foreach ($releasedAnimals as $releasedAnimal)
                            <tr>
                                <td>{{ $releasedAnimal['ear_tag'] }}</td>
                                <td>{{ number_format($releasedAnimal['quantity_kg'], 2) }}</td>
                                <td>{{ $loop->first ? number_format($otherMeatKg, 2) : '—' }}</td>
                                <td>{{ $loop->first && $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                            </tr>
                        @endforeach
                        @if (count($releasedAnimals) > 1)
                            <tr>
                                <td style="font-weight: bold;">Igiteranyo</td>
                                <td style="font-weight: bold;">{{ number_format($carcassMeatKg, 2) }}</td>
                                <td style="font-weight: bold;">{{ number_format($otherMeatKg, 2) }}</td>
                                <td>{{ $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                            </tr>
                        @endif
                    @else
                        <tr>
                            <td>—</td>
                            <td>{{ number_format($carcassMeatKg, 2) }}</td>
                            <td>{{ number_format($otherMeatKg, 2) }}</td>
                            <td>{{ $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Section 5: Transporter --}}
            <div class="section-title">5. UMWIRONDORO W'UTWAYE INYAMA</div>
            <table class="field-table">
                <tr>
                    <td class="field-label">Amazina y'uhawe uruhushya</td>
                    <td class="field-value">{{ $blank($transporterLicenseHolder) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Purake</td>
                    <td class="field-value">{{ $blank($vehiclePlateNumber) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Amazina y'umushoferi</td>
                    <td class="field-value">{{ $blank($driverName) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Isaha ahagurukiye</td>
                    <td class="field-value">{{ $blank($departureTime) }}</td>
                </tr>
                <tr>
                    <td class="field-label">TELEPHONE</td>
                    <td class="field-value">{{ $blank($transporterPhone) }}</td>
                </tr>
            </table>

            {{-- Section 6: Official veterinary declaration --}}
            <div class="section-title">6. KWEMEZA</div>
            <div class="cert-text">
                Njyewe
                <span class="name-line">{{ $certificate->inspector?->full_name ?: '' }}</span>
                Veterineri ushinzwe ubugenzuzi bw'inyama ku ibagiro ryavuzwe haruguru, nshingiye ku bugenzuzi n'isuzuma nakoze ngendeye ku mategeko n'amabwiriza abigenga mu Rwanda, ndemeza ko inyama zivugwa muri iki cyemezo zateguriwe mu ibagiro nshinzwe hubahirizwa ibisabwa byose, kandi ko nta ndwara, ubwandu, cyangwa ikindi cyazibuza gucuruzwa no gukoreshwa mu mafunguro agenewe abantu.
            </div>

            <table class="date-row" style="width: 100%;">
                <tr>
                    <td>
                        Tariki
                        <span class="date-box">{{ $issuedDay !== '—' ? $issuedDay : '' }}</span>
                        /
                        <span class="date-box">{{ $issuedMonth !== '—' ? $issuedMonth : '' }}</span>
                        / 20
                        <span class="date-box">{{ $issuedYear !== '—' ? substr((string) $issuedYear, -2) : '' }}</span>
                    </td>
                </tr>
            </table>

            <table style="width: 100%;">
                <tr>
                    <td class="signature-cell" style="width: 55%;">
                        <div class="signature-label">Umukono na kashe bya veterineri.</div>
                        <div class="signature-line"></div>
                    </td>
                    <td class="signature-cell" style="width: 45%;">
                        <div class="stamp-instruction">Tera muri aya magambo cashe y'ibagiro</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="footer-row">
    <tr>
        <td>
            {{ __('Batch') }}: {{ $batch->batch_code }}
            @if ($certificate->certificate_number)
                · {{ __('Certificate No.') }} {{ $certificate->certificate_number }}
            @endif
            @if ($departureDestination)
                · {{ $departureDestination }}
            @endif
        </td>
        <td class="qr-wrap" width="100">
            <img src="{{ $qrImage }}" width="72" height="72" alt="QR">
            <div class="qr-caption">{{ __('Scan to verify traceability') }}</div>
        </td>
    </tr>
</table>
</body>
</html>
