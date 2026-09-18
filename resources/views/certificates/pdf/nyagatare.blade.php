<!doctype html>
<html lang="rw">
<head>
    <meta charset="utf-8">
    <title>ICYEMEZO CYA VETERINERI KU BUGENZUZI BW'INYAMA</title>
    <style>
        @page { margin: 10mm 11mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #111;
            line-height: 1.3;
            margin: 0;
        }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: middle; }

        /* Outer frame is a single cell so DomPDF never has to resolve rowspans. */
        .sheet { border: 1.5px solid #111; }
        .sheet-cell { padding: 0; }

        /* ---------- Header ---------- */
        .header-table .seal-cell {
            width: 82px;
            text-align: center;
            padding: 7px 5px;
            border-right: 1px solid #111;
        }
        .header-table .header-main {
            padding: 8px 10px 7px;
            text-align: center;
        }
        .seal-ring {
            width: 46px;
            height: 30px;
            border: 1.5px solid #444;
            border-radius: 50%;
            margin: 0 auto 4px;
            padding-top: 16px;
            font-size: 6px;
            font-weight: bold;
            color: #444;
            line-height: 1.1;
        }
        .seal-caption {
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #333;
        }
        .republic-line {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #444;
        }
        .facility-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 3px 0 4px;
        }
        .facility-divisions {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #333;
        }

        /* ---------- Title + meta ---------- */
        .doc-title {
            background: #111;
            color: #fff;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 6px 10px;
        }
        .meta-table {
            border-bottom: 1px solid #111;
            background: #f6f7f8;
        }
        .meta-table td {
            padding: 4px 10px;
            font-size: 8px;
            border-right: 1px solid #d5d8dc;
        }
        .meta-table .meta-last { border-right: none; }
        .meta-key {
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #555;
        }
        .meta-val { font-weight: bold; }

        /* ---------- Sections ---------- */
        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 10px;
            background: #e9ebee;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
        }

        /* Field rows draw bottom/right rules only; the sheet frame supplies outer edges. */
        .field-table td {
            border-bottom: 1px solid #c9ccd1;
            padding: 5px 10px;
            height: 17px;
        }
        .field-table .field-label {
            width: 38%;
            font-size: 8px;
            color: #333;
            background: #fbfbfc;
            border-right: 1px solid #c9ccd1;
        }
        .field-table .field-value {
            font-size: 9px;
            font-weight: bold;
        }
        .field-table .row-last td { border-bottom: none; }

        /* ---------- Weights table ---------- */
        .meat-table th,
        .meat-table td {
            border-bottom: 1px solid #c9ccd1;
            border-right: 1px solid #c9ccd1;
            padding: 5px 4px;
            text-align: center;
        }
        .meat-table th {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #333;
            background: #f6f7f8;
        }
        .meat-table td {
            font-size: 9px;
            font-weight: bold;
            height: 17px;
        }
        .meat-table .total-row td { background: #f6f7f8; }
        .meat-table .row-last td { border-bottom: none; }
        .meat-table .last-col { border-right: none; }

        /* ---------- Declaration ---------- */
        .cert-text {
            padding: 8px 10px 6px;
            text-align: justify;
            font-size: 8px;
            line-height: 1.5;
            border-top: 1px solid #c9ccd1;
        }
        .name-line {
            border-bottom: 1px dotted #111;
            padding: 0 8px 1px;
            font-weight: bold;
        }
        .date-line {
            padding: 0 10px 8px;
            font-size: 8.5px;
        }
        .date-box {
            border-bottom: 1px dotted #111;
            padding: 0 9px 1px;
            font-weight: bold;
        }

        /* ---------- Signatures ---------- */
        .sign-table td {
            padding: 8px 10px 10px;
            vertical-align: top;
            border-top: 1px solid #c9ccd1;
        }
        .sign-label {
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #555;
        }
        .sign-rule {
            border-bottom: 1px solid #111;
            height: 26px;
        }
        .sign-name {
            margin-top: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .stamp-box {
            border: 1px dashed #999;
            height: 44px;
            padding-top: 16px;
            text-align: center;
            font-size: 7px;
            color: #666;
            line-height: 1.3;
        }
        .qr-cell {
            width: 92px;
            text-align: center;
            border-left: 1px solid #c9ccd1;
        }
        .qr-caption {
            font-size: 6px;
            color: #666;
            margin-top: 2px;
            line-height: 1.2;
        }

        /* ---------- Footer ---------- */
        .footer-table { margin-top: 5px; }
        .footer-table td {
            font-size: 6.5px;
            color: #777;
        }
        .footer-right { text-align: right; }
    </style>
</head>
<body>
@php
    $blank = fn ($value) => ($value !== null && trim((string) $value) !== '') ? $value : '—';
    $datePart = fn ($value) => ctype_digit(trim((string) $value)) ? trim((string) $value) : '';
    $divisionLine = collect([$headerDistrictLine, $headerSectorLine, $headerCellLine])
        ->filter(fn ($part) => $part !== null && trim((string) $part) !== '' && $part !== '—')
        ->implode('  ·  ');
    $inspectorName = $certificate->inspector?->full_name;
@endphp

<table class="sheet">
    <tr>
        <td class="sheet-cell">
            {{-- Header --}}
            <table class="header-table">
                <tr>
                    <td class="seal-cell">
                        <div class="seal-ring">RW</div>
                        <div class="seal-caption">Repubulika<br>y'u Rwanda</div>
                    </td>
                    <td class="header-main">
                        <div class="republic-line">Repubulika y'u Rwanda</div>
                        <div class="facility-name">{{ $slaughterhouseDisplayName }}</div>
                        <div class="facility-divisions">{{ $divisionLine !== '' ? $divisionLine : '—' }}</div>
                    </td>
                </tr>
            </table>

            <div class="doc-title">Icyemezo cya veterineri ku bugenzuzi bw'inyama</div>

            <table class="meta-table">
                <tr>
                    <td>
                        <span class="meta-key">Icyemezo n°</span><br>
                        <span class="meta-val">{{ $blank($certificate->certificate_number) }}</span>
                    </td>
                    <td>
                        <span class="meta-key">Itariki yatanzwe</span><br>
                        <span class="meta-val">{{ $blank($issuedAtFormatted) }}</span>
                    </td>
                    <td>
                        <span class="meta-key">Igihe kirangira</span><br>
                        <span class="meta-val">{{ $blank($certificate->expiry_date?->format('d/m/Y')) }}</span>
                    </td>
                    <td class="meta-last">
                        <span class="meta-key">Umurongo (Batch)</span><br>
                        <span class="meta-val">{{ $blank($batch->batch_code) }}</span>
                    </td>
                </tr>
            </table>

            {{-- Section 1: the slaughterhouse --}}
            <div class="section-title">1. Ibagiro</div>
            <table class="field-table">
                <tr>
                    <td class="field-label">Izina ry'ibagiro</td>
                    <td class="field-value">{{ $blank($slaughterhouseDisplayName) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Aho ribarizwa (District, Sector, Cell)</td>
                    <td class="field-value">{{ $blank($facilityLocationLine) }}</td>
                </tr>
                <tr class="row-last">
                    <td class="field-label">Telefoni</td>
                    <td class="field-value">{{ $blank($facilityPhone) }}</td>
                </tr>
            </table>

            {{-- Section 2: the animal --}}
            <div class="section-title">2. Ibiranga itungo</div>
            <table class="field-table">
                <tr>
                    <td class="field-label">Ubwoko</td>
                    <td class="field-value">{{ $blank($species) }}</td>
                </tr>
                <tr class="row-last">
                    <td class="field-label">Iherena n°</td>
                    <td class="field-value">{{ $blank($earTagNumbers) }}</td>
                </tr>
            </table>

            {{-- Section 3: butcher / shop --}}
            <div class="section-title">3. Umwirondoro wa nyiri busheri / iduka ricururiza inyama</div>
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
                    <td class="field-value">{{ $blank($sellingLocationLine) }}</td>
                </tr>
                <tr class="row-last">
                    <td class="field-label">Telefoni</td>
                    <td class="field-value">{{ $blank($shopPhone) }}</td>
                </tr>
            </table>

            {{-- Section 4: weights and temperature --}}
            <div class="section-title">4. Uburemere n'igipimo cy'ubushyuhe</div>
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
                            <tr @class(['row-last' => $loop->last && count($releasedAnimals) === 1])>
                                <td>{{ $releasedAnimal['ear_tag'] }}</td>
                                <td>{{ number_format($releasedAnimal['quantity_kg'], 2) }}</td>
                                <td>{{ $loop->first ? number_format($otherMeatKg, 2) : '—' }}</td>
                                <td class="last-col">{{ $loop->first && $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                            </tr>
                        @endforeach
                        @if (count($releasedAnimals) > 1)
                            <tr class="total-row row-last">
                                <td>Igiteranyo</td>
                                <td>{{ number_format($carcassMeatKg, 2) }}</td>
                                <td>{{ number_format($otherMeatKg, 2) }}</td>
                                <td class="last-col">{{ $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                            </tr>
                        @endif
                    @else
                        <tr class="row-last">
                            <td>—</td>
                            <td>{{ number_format($carcassMeatKg, 2) }}</td>
                            <td>{{ number_format($otherMeatKg, 2) }}</td>
                            <td class="last-col">{{ $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Section 5: transporter --}}
            <div class="section-title">5. Umwirondoro w'utwaye inyama</div>
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
                <tr class="row-last">
                    <td class="field-label">Telefoni</td>
                    <td class="field-value">{{ $blank($transporterPhone) }}</td>
                </tr>
            </table>

            {{-- Section 6: veterinary declaration --}}
            <div class="section-title">6. Kwemeza</div>
            <div class="cert-text" style="border-top: none;">
                Njyewe <span class="name-line">{{ $inspectorName ?: '&nbsp;' }}</span>
                Veterineri ushinzwe ubugenzuzi bw'inyama ku ibagiro ryavuzwe haruguru, nshingiye ku bugenzuzi n'isuzuma nakoze ngendeye ku mategeko n'amabwiriza abigenga mu Rwanda, ndemeza ko inyama zivugwa muri iki cyemezo zateguriwe mu ibagiro nshinzwe hubahirizwa ibisabwa byose, kandi ko nta ndwara, ubwandu, cyangwa ikindi cyazibuza gucuruzwa no gukoreshwa mu mafunguro agenewe abantu.
            </div>
            <div class="date-line">
                Tariki
                <span class="date-box">{{ $datePart($issuedDay) }}</span> /
                <span class="date-box">{{ $datePart($issuedMonth) }}</span> / 20
                <span class="date-box">{{ substr($datePart($issuedYear), -2) }}</span>
            </div>

            <table class="sign-table">
                <tr>
                    <td style="width: 44%;">
                        <div class="sign-label">Umukono wa veterineri</div>
                        <div class="sign-rule"></div>
                        <div class="sign-name">{{ $blank($inspectorName) }}</div>
                    </td>
                    <td style="width: 34%;">
                        <div class="sign-label">Kashe y'ibagiro</div>
                        <div class="stamp-box" style="margin-top: 4px;">Tera muri aya magambo<br>cashe y'ibagiro</div>
                    </td>
                    <td class="qr-cell">
                        <img src="{{ $qrImage }}" width="66" height="66" alt="QR">
                        <div class="qr-caption">Sikana urebe<br>ukuri kw'iki cyemezo</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="footer-table">
    <tr>
        <td>{{ $slaughterhouseDisplayName }}</td>
        <td class="footer-right">{{ __('Generated') }} {{ $generatedAt->format('d/m/Y H:i') }}</td>
    </tr>
</table>
</body>
</html>
