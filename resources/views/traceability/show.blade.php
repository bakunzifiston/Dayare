<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Traceability') }} — DAYARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; padding: 1.5rem; max-width: 28rem; margin: 0 auto; }
        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: #111827; }
        dl { display: grid; gap: 0.75rem; }
        dt { font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        dd { font-size: 1rem; color: #111827; margin: 0; }
    </style>
</head>
<body class="bg-gray-50">
    <h1>{{ __('Meat traceability') }}</h1>
    <dl>
        <div>
            <dt>{{ __('Facility name') }}</dt>
            <dd>{{ $facilityName }}</dd>
        </div>
        <div>
            <dt>{{ __('Inspector name') }}</dt>
            <dd>{{ $inspectorName }}</dd>
        </div>
        <div>
            <dt>{{ __('Slaughter date') }}</dt>
            <dd>{{ $slaughterDate }}</dd>
        </div>
        <div>
            <dt>{{ __('Batch code') }}</dt>
            <dd>{{ $batchCode }}</dd>
        </div>
        <div>
            <dt>{{ __('Certificate number') }}</dt>
            <dd>{{ $certificateNumber }}</dd>
        </div>
    </dl>
</body>
</html>
