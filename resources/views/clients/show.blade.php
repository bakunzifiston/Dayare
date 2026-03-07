<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Client') }} — {{ $client->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this client?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">{{ __('Delete') }}</button>
                </form>
                <a href="{{ route('clients.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Business') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->business?->business_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->is_active ? __('Active') : __('Inactive') }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Contact person') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->contact_person ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Email') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->email ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Phone') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->phone ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Country') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->country ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Tax ID') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->tax_id ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Registration number') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->registration_number ?? '—' }}</dd></div>
                    @if ($client->address_line_1 || $client->address_line_2 || $client->city || $client->state_region || $client->postal_code)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-slate-500">{{ __('Address') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $client->address_line }}</dd>
                        </div>
                    @endif
                    @if ($client->notes)
                        <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-base font-semibold text-slate-800 mb-4">{{ __('Delivery confirmations') }}</h3>
                @if ($client->deliveryConfirmations->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No delivery confirmations linked yet.') }}</p>
                    <p class="text-sm text-slate-500 mt-1">{{ __('When creating or editing a delivery confirmation (external recipient), you can link this client.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase">
                                    <th class="py-2 pr-4">{{ __('Date') }}</th>
                                    <th class="py-2 pr-4">{{ __('Trip / Reference') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                    <th class="py-2 pr-4 text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($client->deliveryConfirmations as $confirmation)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $confirmation->received_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="py-2 pr-4">{{ $confirmation->transportTrip?->vehicle_plate_number ?? '—' }}</td>
                                        <td class="py-2 pr-4">{{ $confirmation->confirmation_status ? ucfirst($confirmation->confirmation_status) : '—' }}</td>
                                        <td class="py-2 pr-4 text-right">
                                            <a href="{{ route('delivery-confirmations.show', $confirmation) }}" class="text-indigo-600 hover:underline font-medium">{{ __('View') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
