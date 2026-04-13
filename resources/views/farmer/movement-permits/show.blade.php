<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('farmer.movement-permits.index') }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Movement permits') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Permit') }} {{ $movementPermit->permit_number }}</h2>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-3 text-sm">
            <div class="flex items-center gap-2">
                <span class="text-slate-500">{{ __('Status') }}:</span>
                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $isValid ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900' }}">
                    {{ $isValid ? __('Valid') : __('Expired') }}
                </span>
            </div>
            <p><span class="text-slate-500">{{ __('Source farm') }}:</span> {{ $movementPermit->sourceFarm?->name }}</p>
            <p><span class="text-slate-500">{{ __('Issued by') }}:</span> {{ $movementPermit->issued_by }}</p>
            <p><span class="text-slate-500">{{ __('Issue date') }}:</span> {{ $movementPermit->issue_date?->toDateString() }}</p>
            <p><span class="text-slate-500">{{ __('Expiry date') }}:</span> {{ $movementPermit->expiry_date?->toDateString() }}</p>
            <p><span class="text-slate-500">{{ __('Transport') }}:</span> {{ $movementPermit->transport_mode ?: '—' }} / {{ $movementPermit->vehicle_plate ?: '—' }}</p>
            <p><span class="text-slate-500">{{ __('Destination') }}:</span>
                {{ $movementPermit->destinationDistrict?->name ?: '—' }},
                {{ $movementPermit->destinationSector?->name ?: '—' }},
                {{ $movementPermit->destinationCell?->name ?: '—' }},
                {{ $movementPermit->destinationVillage?->name ?: '—' }}
            </p>
            <a href="{{ route('farmer.movement-permits.download', $movementPermit) }}" class="inline-flex items-center px-3 py-2 bg-bucha-primary text-white text-xs font-semibold rounded-bucha">{{ __('Download permit file') }}</a>
        </div>

        <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
            <div class="px-4 py-3 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-800">{{ __('Linked animals') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-2">{{ __('Livestock') }}</th>
                        <th class="px-4 py-2">{{ __('Animal identifier') }}</th>
                        <th class="px-4 py-2">{{ __('Quantity') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movementPermit->animals as $row)
                        <tr>
                            <td class="px-4 py-2">
                                @if ($row->livestock)
                                    {{ \App\Support\FarmerAnimalType::label($row->livestock->type) }} #{{ $row->livestock->id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $row->animal_identifier ?: '—' }}</td>
                            <td class="px-4 py-2">{{ $row->quantity ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">{{ __('No linked animals.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

