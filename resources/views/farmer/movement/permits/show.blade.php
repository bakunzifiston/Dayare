<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h2 class="font-semibold text-xl text-slate-800">{{ $permit->permit_number }}</h2>
                <p class="text-sm text-slate-500">{{ $permit->sourceFarm?->name }} · {{ str_replace('_', ' ', $permit->permit_type) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($permit->isEditable())
                    <a href="{{ route('farmer.movement.permits.edit', $permit) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">{{ __('Edit') }}</a>
                @endif
                <a href="{{ route('farmer.movement.permits.download', $permit) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">{{ __('Download PDF') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        <section class="grid gap-4 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-4">
            <div><p class="text-xs uppercase text-slate-500">{{ __('Permit status') }}</p><x-movement-permit-status-badge :status="$permit->permit_status" /></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Movement status') }}</p><x-movement-status-badge :status="$permit->movement_status" /></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Veterinary clearance') }}</p><p class="mt-1 text-sm font-medium capitalize">{{ str_replace('_', ' ', $permit->veterinary_status) }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Validity today') }}</p><p class="mt-1 text-sm font-medium">{{ $isValid ? __('Valid') : __('Not valid') }}</p></div>
        </section>
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Route & schedule') }}</h3>
                <dl class="mt-4 space-y-2">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Origin') }}</dt><dd class="font-medium text-right">{{ $permit->origin_location ?: $permit->sourceFarm?->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Destination') }}</dt><dd class="font-medium text-right">{{ $permit->destination_location ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Departure') }}</dt><dd class="font-medium">{{ $permit->departure_date?->toDateString() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Expected arrival') }}</dt><dd class="font-medium">{{ $permit->expected_arrival_date?->toDateString() }}</dd></div>
                </dl>
            </section>
            <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Transport & verification') }}</h3>
                <dl class="mt-4 space-y-2">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Vehicle') }}</dt><dd class="font-medium">{{ $permit->transport?->vehicle_number ?: $permit->vehicle_plate ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Driver') }}</dt><dd class="font-medium">{{ $permit->transport?->driver_name ?: $permit->driver_name ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Transporter') }}</dt><dd class="font-medium">{{ $permit->transport?->transporter_company ?: $permit->transporter_name ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Public verify') }}</dt><dd class="font-medium break-all"><a href="{{ $permit->verificationUrl() }}" class="text-bucha-primary hover:underline" target="_blank" rel="noopener">{{ $permit->verificationUrl() }}</a></dd></div>
                </dl>
            </section>
        </div>
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Animals on this permit') }}</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600"><tr><th class="px-3 py-2">{{ __('Animal') }}</th><th class="px-3 py-2">{{ __('Identifier') }}</th><th class="px-3 py-2">{{ __('Condition') }}</th><th class="px-3 py-2">{{ __('Loading') }}</th><th class="px-3 py-2">{{ __('Arrival') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($permit->animals as $line)
                            <tr>
                                <td class="px-3 py-2">{{ $line->animal?->animal_code ?: $line->animal_identifier ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $line->animal_identifier ?: $line->animal?->tag_number ?: '—' }}</td>
                                <td class="px-3 py-2 capitalize">{{ str_replace('_', ' ', $line->movement_condition) }}</td>
                                <td class="px-3 py-2 capitalize">{{ str_replace('_', ' ', $line->loading_status) }}</td>
                                <td class="px-3 py-2 capitalize">{{ $line->arrival_status ? str_replace('_', ' ', $line->arrival_status) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Veterinary approval') }}</h3>
            @if ($permit->veterinaryApproval)
                <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div><dt class="text-slate-500">{{ __('Veterinarian') }}</dt><dd class="font-medium">{{ $permit->veterinaryApproval->veterinarian_name ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Inspection date') }}</dt><dd class="font-medium">{{ $permit->veterinaryApproval->inspection_date?->toDateString() ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Result') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $permit->veterinaryApproval->inspection_result ?: '—') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Approval') }}</dt><dd class="font-medium capitalize">{{ $permit->veterinaryApproval->approval_status ?: __('Pending') }}</dd></div>
                </dl>
            @else
                <p class="mt-3 text-slate-500">{{ __('No veterinary inspection recorded yet.') }}</p>
            @endif
        </section>
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Workflow actions') }}</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @if (in_array($permit->permit_status, [\App\Models\MovementPermit::STATUS_DRAFT, \App\Models\MovementPermit::STATUS_PENDING_APPROVAL], true))
                    <form method="POST" action="{{ route('farmer.movement.permits.submit', $permit) }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-sm">{{ __('Submit for approval') }}</button></form>
                @endif
                @can('approve', $permit)
                    @if ($permit->permit_status === \App\Models\MovementPermit::STATUS_PENDING_APPROVAL)
                        <form method="POST" action="{{ route('farmer.movement.permits.approve', $permit) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white">{{ __('Approve') }}</button></form>
                        <form method="POST" action="{{ route('farmer.movement.permits.reject', $permit) }}" class="flex items-center gap-2">@csrf<input type="text" name="notes" placeholder="{{ __('Rejection notes') }}" class="rounded-lg border-gray-300 text-sm" /><button class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700">{{ __('Reject') }}</button></form>
                    @endif
                @endcan
                @if ($permit->permit_status === \App\Models\MovementPermit::STATUS_APPROVED && $permit->movement_status === \App\Models\MovementPermit::MOVEMENT_PENDING)
                    <form method="POST" action="{{ route('farmer.movement.permits.start-transit', $permit) }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-sm">{{ __('Start transit') }}</button></form>
                @endif
                @if ($permit->movement_status === \App\Models\MovementPermit::MOVEMENT_IN_TRANSIT)
                    <form method="POST" action="{{ route('farmer.movement.permits.confirm-arrival', $permit) }}">@csrf<button class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white">{{ __('Confirm arrival') }}</button></form>
                @endif
                @if (! in_array($permit->permit_status, [\App\Models\MovementPermit::STATUS_CANCELLED, \App\Models\MovementPermit::STATUS_EXPIRED], true))
                    <form method="POST" action="{{ route('farmer.movement.permits.cancel', $permit) }}" class="flex items-center gap-2">@csrf<input type="text" name="notes" placeholder="{{ __('Cancellation notes') }}" class="rounded-lg border-gray-300 text-sm" /><button class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700">{{ __('Cancel permit') }}</button></form>
                @endif
            </div>
        </section>
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Activity log') }}</h3>
            <div class="mt-4 space-y-3 text-sm">
                @forelse ($permit->logs as $log)
                    <div class="rounded-lg border border-slate-100 px-3 py-2">
                        <p class="font-medium capitalize">{{ str_replace('_', ' ', $log->action_type) }}</p>
                        <p class="text-slate-500">{{ $log->action_date?->toDateTimeString() }} · {{ $log->actor?->name ?: __('System') }}</p>
                        @if ($log->notes)<p class="mt-1 text-slate-600">{{ $log->notes }}</p>@endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('No activity recorded yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
