<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Permit') }} {{ $permit->permit_number }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-800">
    <main class="mx-auto max-w-3xl px-4 py-10">
        <header class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-emerald-700">{{ __('Authenticity check') }}</p>
            <h1 class="mt-1 text-2xl font-semibold">{{ $permit->permit_number }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('Verified at') }}: {{ $verifiedAt->toDateTimeString() }}</p>
        </header>
        <section class="mt-6 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $permit->permit_status) }}</dd></div>
                <motion><dt class="text-slate-500">{{ __('Valid now') }}</dt><dd class="font-medium">{{ $permit->isValidOn(now()) ? __('Yes') : __('No') }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Issue date') }}</dt><dd class="font-medium">{{ $permit->issue_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Expiry date') }}</dt><dd class="font-medium">{{ $permit->expiry_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Owner') }}</dt><dd class="font-medium">{{ $permit->owner_name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Animals') }}</dt><dd class="font-medium">{{ $permit->animals->count() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Origin') }}</dt><dd class="font-medium">{{ $permit->sourceLocationLabel() ?: $permit->origin_location ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Destination') }}</dt><dd class="font-medium">{{ $permit->destinationLocationLabel() ?: $permit->destination_location ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Issued by') }}</dt><dd class="font-medium">{{ $permit->issued_by ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Authority') }}</dt><dd class="font-medium">{{ $permit->issuing_authority ?: 'RAB' }}</dd></div>
            </dl>
            @if ($permit->file_path)
                <p class="mt-4"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($permit->file_path) }}" class="text-bucha-primary hover:underline" target="_blank" rel="noopener">{{ __('Download permit PDF') }}</a></p>
            @endif
        </section>
        <p class="mt-6 text-center text-sm"><a href="{{ route('verify.permit.lookup') }}" class="text-bucha-primary hover:underline">{{ __('Verify another permit') }}</a></p>
    </main>
</body>
</html>
