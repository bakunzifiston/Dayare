<!doctype html>
<html lang="rw">
<head>
    <meta charset="utf-8">
    <title>ICYEMEZO CYA VETERINERI KU BUGENZUZI BW'INYAMA</title>
    <style>
        @page { margin: 8mm 10mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.25;
            margin: 0;
        }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: middle; }

        /* Outer frame: a single cell wrapper so DomPDF never has to resolve rowspans. */
        .sheet { border: 2px solid #000; }
        .sheet-cell { padding: 0; }

        .header-table .seal-cell {
            width: 76px;
            text-align: center;
            padding: 5px 4px;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .header-table .header-main {
            padding: 5px 8px 6px;
            border-bottom: 1px solid #000;
        }
        .seal-placeholder {
            width: 54px;
            height: 34px;
            border: 1px solid #333;
            border-radius: 50%;
            margin: 0 auto 3px;
            font-size: 6.5px;
            color: #444;
            line-height: 1.1;
            padding-top: 19px;
        }
        .republic-line {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .facility-name {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: center;
            margin-bottom: 5px;
        }

        .division-table td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .division-table .division-label {
            width: 24%;
            font-weight: bold;
            background: #fafafa;
        }
        .division-table .division-value { font-weight: bold; }

        .doc-title {
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.3;
            padding: 5px 10px;
            border-bottom: 1px solid #000;
        }

        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 6px;
            background: #f1f2f4;
            border-bottom: 1px solid #000;
        }

        /* Field rows draw only bottom/right rules; the sheet frame supplies the outer edges. */
        .field-table td {
            border-bottom: 1px solid #000;
            padding: 4px 6px;
            height: 18px;
        }
        .field-table .field-label {
            width: 36%;
            font-size: 8px;
            font-weight: bold;
            background: #fafafa;
            border-right: 1px solid #000;
        }
        .field-table .field-value {
            font-size: 9px;
            font-weight: bold;
        }

        .meat-table th,
        .meat-table td {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            font-size: 8px;
        }
        .meat-table th {
            font-weight: bold;
            background: #fafafa;
        }
        .meat-table td {
            font-size: 9px;
            font-weight: bold;
            height: 18px;
        }
        .meat-table .last-col { border-right: none; }

        .cert-text {
            padding: 7px 8px 5px;
            text-align: justify;
            font-size: 8px;
            line-height: 1.4;
        }
        .name-line {
            border-bottom: 1px dotted #000;
            padding: 0 6px 1px;
            font-weight: bold;
        }

        .date-row td {
            padding: 5px 8px 3px;
            font-size: 8.5px;
        }
        .date-box {
            border-bottom: 1px dotted #000;
            padding: 0 10px 1px;
            font-weight: bold;
        }

        .signature-cell {
            padding: 5px 8px 8px;
            vertical-align: top;
        }
        .signature-label { font-size: 8px; }
        .signature-line {
            border-bottom: 1px solid #000;
            height: 30px;
        }
        .inspector-name {
            margin-top: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .stamp-instruction {
            font-size: 8.5px;
            font-weight: bold;
            text-align: right;
            padding-top: 26px;
            line-height: 1.3;
        }

        .footer-table { margin-top: 6px; }
        .footer-table td {
            font-size: 7px;
            color: #555;
            vertical-align: top;
            padding-top: 4px;
        }
        .qr-wrap { text-align: right; width: 96px; }
        .qr-caption {
            font-size: 6px;
            color: #666;
            margin-top: 1px;
        }
    </style>
</head>
<body>
@php
    $blank = fn ($value) => ($value !== null && trim((string) $value) !== '') ? $value : '—';
    $datePart = fn ($value) => ctype_digit(trim((string) $value)) ? trim((string) $value) : '';
    $ownerLocation = $sellingLocationLine;
@endphp

<table class="sheet">
    <tr>
        <td class="sheet-cell">
            {{-- Header: seal, slaughterhouse name, administrative divisions --}}
            <table class="header-table">
                <tr>
                    <td class="seal-cell">
                        <div class="seal-placeholder">REPUBULIKA<br>Y'U RWANDA</div>
                        <div class="republic-line">Republika y'u Rwanda</div>
                    </td>
                    <td class="header-main">
                        <div class="facility-name">{{ $slaughterhouseDisplayName }}</div>
                        <table class="division-table">
                            <tr>
                                <td class="division-label">District</td>
                                <td class="division-value">{{ $headerDistrictLine }}</td>
                            </tr>
                            <tr>
                                <td class="division-label">Sector</td>
                                <td class="division-value">{{ $headerSectorLine }}</td>
                            </tr>
                            <tr>
                                <td class="division-label">Cell</td>
                                <td class="division-value">{{ $headerCellLine }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="doc-title">Inyito: Icyemezo cya veterineri ku bugenzuzi bw'inyama</div>

            {{-- Section 1: IBAGIRO --}}
            <div class="section-title">1. IBAGIRO</div>
            <table class="field-table">
                <tr>
                    <td class="field-label">IZINA RY'IBAGIRO</td>
                    <td class="field-value">{{ $blank($slaughterhouseDisplayName) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Aho ribarizwa (District, Sector, Cell)</td>
                    <td class="field-value">{{ $blank($facilityLocationLine) }}</td>
                </tr>
                <tr>
                    <td class="field-label">TELEPHONE</td>
                    <td class="field-value">{{ $blank($facilityPhone) }}</td>
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
                        <th class="last-col">Igipimo cy'ubushyuhe (°C)</th>
                    </tr>
                </thead>
                <tbody>
                    @if (! empty($releasedAnimals))
                        @foreach ($releasedAnimals as $releasedAnimal)
                            <tr>
                                <td>{{ $releasedAnimal['ear_tag'] }}</td>
                                <td>{{ number_format($releasedAnimal['quantity_kg'], 2) }}</td>
                                <td>{{ $loop->first ? number_format($otherMeatKg, 2) : '—' }}</td>
                                <td class="last-col">{{ $loop->first && $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                            </tr>
                        @endforeach
                        @if (count($releasedAnimals) > 1)
                            <tr>
                                <td>Igiteranyo</td>
                                <td>{{ number_format($carcassMeatKg, 2) }}</td>
                                <td>{{ number_format($otherMeatKg, 2) }}</td>
                                <td class="last-col">{{ $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                            </tr>
                        @endif
                    @else
                        <tr>
                            <td>—</td>
                            <td>{{ number_format($carcassMeatKg, 2) }}</td>
                            <td>{{ number_format($otherMeatKg, 2) }}</td>
                            <td class="last-col">{{ $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
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
                    <td class="field-label">Aho zigana</td>
                    <td class="field-value">{{ $blank($departureDestination) }}</td>
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
                Njyewe <span class="name-line">{{ $certificate->inspector?->full_name ?: '&nbsp;' }}</span>
                Veterineri ushinzwe ubugenzuzi bw'inyama ku ibagiro ryavuzwe haruguru, nshingiye ku bugenzuzi n'isuzuma nakoze ngendeye ku mategeko n'amabwiriza abigenga mu Rwanda, ndemeza ko inyama zivugwa muri iki cyemezo zateguriwe mu ibagiro nshinzwe hubahirizwa ibisabwa byose, kandi ko nta ndwara, ubwandu, cyangwa ikindi cyazibuza gucuruzwa no gukoreshwa mu mafunguro agenewe abantu.
            </div>

            <table class="date-row">
                <tr>
                    <td>
                        Tariki
                        <span class="date-box">{{ $datePart($issuedDay) }}</span> /
                        <span class="date-box">{{ $datePart($issuedMonth) }}</span> / 20
                        <span class="date-box">{{ substr($datePart($issuedYear), -2) }}</span>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td class="signature-cell" style="width: 55%;">
                        <div class="signature-label">Umukono na kashe bya veterineri.</div>
                        <div class="signature-line"></div>
                        <div class="inspector-name">{{ $certificate->inspector?->full_name }}</div>
                    </td>
                    <td class="signature-cell" style="width: 45%;">
                        <div class="stamp-instruction">Tera muri aya magambo cashe y'ibagiro</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="footer-table">
    <tr>
        <td>
            {{ __('Batch') }}: {{ $batch->batch_code }}
            @if ($certificate->certificate_number)
                · {{ __('Certificate No.') }} {{ $certificate->certificate_number }}
            @endif
        </td>
        <td class="qr-wrap">
            <img src="{{ $qrImage }}" width="64" height="64" alt="QR">
            <div class="qr-caption">{{ __('Scan to verify traceability') }}</div>
        </td>
    </tr>
</table>
</body>
</html>
