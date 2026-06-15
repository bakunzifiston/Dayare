<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Veterinary Meat Inspection Certificate') }}</title>
    <style>
        @page { margin: 11mm 13mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 14px;
        }
        .slaughterhouse-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 8px;
        }
        .republic {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .location-line {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 2px;
        }
        .doc-title {
            margin-top: 10px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .section {
            margin-top: 12px;
            page-break-inside: avoid;
        }
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .form-line {
            margin-bottom: 7px;
            width: 100%;
        }
        .form-label {
            white-space: nowrap;
        }
        .form-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 68%;
            padding: 0 2px 1px 6px;
            font-weight: 600;
        }
        .form-value-inline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 24%;
            padding: 0 2px 1px 4px;
            font-weight: 600;
        }
        .inline-sep {
            margin: 0 6px;
        }
        .meat-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .meat-table th,
        .meat-table td {
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: center;
            font-size: 10px;
        }
        .meat-table th {
            font-weight: bold;
            text-transform: none;
        }
        .meat-table td {
            font-size: 11px;
            font-weight: 600;
            min-height: 28px;
        }
        .cert-text {
            margin-top: 4px;
            text-align: justify;
            font-size: 10.5px;
            line-height: 1.45;
        }
        .signature-row {
            margin-top: 16px;
            width: 100%;
        }
        .signature-label {
            font-size: 10px;
            font-weight: bold;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            min-height: 28px;
            margin-top: 8px;
        }
        .stamp-box {
            border: 1px dashed #444;
            width: 120px;
            height: 72px;
            text-align: center;
            font-size: 9px;
            color: #555;
            padding-top: 28px;
            margin-left: auto;
        }
        .footer-row {
            margin-top: 14px;
            width: 100%;
            font-size: 8px;
            color: #666;
        }
        .qr-wrap {
            text-align: right;
        }
        .qr-caption {
            font-size: 7px;
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="slaughterhouse-name">{{ $slaughterhouseDisplayName }}</div>
        <div class="republic">{{ __('REPUBLIC OF RWANDA') }}</div>
        <div class="location-line">{{ $headerDistrictLine }}</div>
        <div class="location-line">{{ $headerSectorLine }}</div>
        <div class="location-line">{{ $headerCellLine }}</div>
        <div class="doc-title">{{ __('TITLE: VETERINARY MEAT INSPECTION CERTIFICATE') }}</div>
    </div>

    <div class="section">
        <div class="section-heading">1. {{ __('SLAUGHTERHOUSE') }}</div>
        <div class="form-line">
            <span class="form-label">{{ __('Name of Slaughterhouse') }}:</span>
            <span class="form-value">{{ $slaughterhouseDisplayName }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Location (District, Sector, Cell)') }}:</span>
            <span class="form-value">{{ $facilityLocationLine }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Type') }}:</span>
            <span class="form-value">{{ $facilityTypeLabel }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Telephone') }}:</span>
            <span class="form-value-inline">{{ $facilityPhone ?: '—' }}</span>
            <span class="inline-sep">|</span>
            <span class="form-label">{{ __('Registration No.') }}:</span>
            <span class="form-value-inline">{{ $facilityRegistrationNumber ?: '—' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-heading">2. {{ __('ANIMAL IDENTIFICATION') }}</div>
        <div class="form-line">
            <span class="form-label">{{ __('Names') }}:</span>
            <span class="form-value">{{ $ownerNames }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __("Butcher's Name") }}:</span>
            <span class="form-value">{{ $butcherName ?: '—' }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Selling Location (District, Sector, Cell)') }}:</span>
            <span class="form-value">{{ $sellingLocationLine }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Telephone') }}:</span>
            <span class="form-value">{{ $ownerPhone ?: '—' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-heading">3. {{ __('IDENTIFICATION OF THE BUTCHER / MEAT SELLING SHOP') }}</div>
        <div class="form-line">
            <span class="form-label">{{ __('Names') }}:</span>
            <span class="form-value">{{ $shopName ?: '—' }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Telephone') }}:</span>
            <span class="form-value">{{ $shopPhone ?: '—' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-heading">4. {{ __('MEAT WEIGHT AND TEMPERATURE') }}</div>
        <table class="meat-table">
            <thead>
                <tr>
                    <th>{{ __('Carcass Meat (Kg)') }}</th>
                    <th>{{ __('Other Meat (Kg)') }}</th>
                    <th>{{ __('Temperature (°C)') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($carcassMeatKg, 2) }}</td>
                    <td>{{ number_format($otherMeatKg, 2) }}</td>
                    <td>{{ $temperatureCelsius !== null ? number_format($temperatureCelsius, 1) : '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-heading">5. {{ __('IDENTIFICATION OF THE AUTHORIZED MEAT TRANSPORTER') }}</div>
        <div class="form-line">
            <span class="form-label">{{ __('Name of License Holder') }}:</span>
            <span class="form-value">{{ $transporterLicenseHolder ?: '—' }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Vehicle Plate Number') }}:</span>
            <span class="form-value">{{ $vehiclePlateNumber ?: '—' }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __("Driver's Name") }}:</span>
            <span class="form-value">{{ $driverName ?: '—' }}</span>
        </div>
        <div class="form-line">
            <span class="form-label">{{ __('Departure Destination') }}:</span>
            <span class="form-value-inline">{{ $departureDestination ?: '—' }}</span>
            <span class="inline-sep">|</span>
            <span class="form-label">{{ __('Telephone') }}:</span>
            <span class="form-value-inline">{{ $transporterPhone ?: '—' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-heading">6. {{ __('CERTIFICATION') }}</div>
        <p class="cert-text">
            {{ __('I, the undersigned Veterinarian, certify that I have inspected the meat at the slaughterhouse, and that the meat meets all Rwanda standards. It has been examined for all diseases and conditions, is fit for human consumption, and falls within the categories permitted by the relevant authority.') }}
        </p>
        <div class="form-line" style="margin-top: 12px;">
            <span class="form-label">{{ __('Date') }}:</span>
            <span class="form-value-inline" style="min-width: 12%; text-align: center;">{{ $issuedDay }}</span>
            <span class="inline-sep">/</span>
            <span class="form-value-inline" style="min-width: 12%; text-align: center;">{{ $issuedMonth }}</span>
            <span class="inline-sep">/</span>
            <span class="form-value-inline" style="min-width: 18%; text-align: center;">{{ $issuedYear }}</span>
        </div>

        <table class="signature-row">
            <tr>
                <td width="58%" style="vertical-align: top;">
                    <div class="signature-label">{{ __("Veterinarian's Signature and Stamp") }}:</div>
                    <div class="signature-line"></div>
                    @if ($certificate->inspector?->full_name)
                        <div style="margin-top: 6px; font-size: 10px; font-weight: 600;">{{ $certificate->inspector->full_name }}</div>
                    @endif
                </td>
                <td width="42%" style="vertical-align: top;">
                    <div class="stamp-box">{{ __('Official Stamp') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="footer-row">
        <tr>
            <td>
                {{ __('Batch') }}: {{ $batch->batch_code }}
                @if ($certificate->certificate_number)
                    · {{ __('Certificate No.') }} {{ $certificate->certificate_number }}
                @endif
            </td>
            <td class="qr-wrap" width="130">
                <img src="{{ $qrImage }}" width="90" height="90" alt="QR">
                <div class="qr-caption">{{ __('Scan to verify traceability') }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
