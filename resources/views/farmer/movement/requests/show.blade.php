<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h2 class="font-semibold text-xl text-slate-800">{{ $request->request_number }}</h2>
                <p class="text-sm text-slate-500 capitalize">{{ str_replace('_', ' ', $request->status) }}</p>
            </div>
            @if ($request->canIssuePermit())
                <a href="{{ route('farmer.movement.requests.issue-permit', $request) }}" class="rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">{{ __('Issue / upload permit') }}</a>
            @endif
        </div>
    </x-slot>
    <div class="max-w-5xl space-y-6">
        @include('farmer.movement.partials.nav')
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm grid gap-4 sm:grid-cols-2">
            <div><dt class="text-slate-500">{{ __('Farm') }}</dt><dd class="font-medium">{{ $request->farm?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Purpose') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $request->movement_purpose) }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Destination') }}</dt><dd class="font-medium">{{ $request->destinationLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Dates') }}</dt><dd class="font-medium">{{ $request->proposed_departure_date?->format('d/m/Y') }} → {{ $request->expected_arrival_date?->format('d/m/Y') }}</dd></div>
        </section>
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Animals') }}</h3>
            <table class="mt-4 min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600"><tr><th class="px-3 py-2">{{ __('Animal') }}</th><th class="px-3 py-2">{{ __('Eligible') }}</th><th class="px-3 py-2">{{ __('Issues') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($request->animals as $line)
                        <tr>
                            <td class="px-3 py-2">{{ $line->animal?->selectionLabel() ?: $line->animal_identifier }}</td>
                            <td class="px-3 py-2">{{ $line->eligibility_passed ? __('Yes') : __('No') }}</td>
                            <td class="px-3 py-2 text-amber-700">{{ $line->eligibility_issues ? implode('; ', $line->eligibility_issues) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        @if (in_array($request->status, [\App\Models\PermitRequest::STATUS_SUBMITTED, \App\Models\PermitRequest::STATUS_UNDER_REVIEW], true))
            <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm flex flex-wrap gap-2">
                <form method="POST" action="{{ route('farmer.movement.requests.approve', $request) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm text-white">{{ __('Approve') }}</button></form>
                <form method="POST" action="{{ route('farmer.movement.requests.reject', $request) }}" class="flex gap-2">@csrf<input name="rejection_reason" class="rounded-lg border-gray-300 text-sm" placeholder="{{ __('Rejection reason') }}" required /><button class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700">{{ __('Reject') }}</button></form>
            </section>
        @endif
        @if ($request->permit)
            <p class="text-sm"><a href="{{ route('farmer.movement.permits.show', $request->permit) }}" class="text-bucha-primary hover:underline">{{ __('View linked permit') }} →</a></p>
        @endif
    </div>
</x-app-layout>
