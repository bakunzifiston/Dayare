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
                    @if ($client->country_id || $client->country)
                    <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Location') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->location_line }}</dd></div>
                    @endif
                    @if ($client->business_type)<div><dt class="text-sm font-medium text-slate-500">{{ __('Business type') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ \App\Models\Client::BUSINESS_TYPES[$client->business_type] ?? $client->business_type }}</dd></div>@endif
                    @if ($client->preferredFacility)<div><dt class="text-sm font-medium text-slate-500">{{ __('Preferred facility') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('businesses.facilities.show', [$client->preferredFacility->business, $client->preferredFacility]) }}" class="text-indigo-600 hover:underline">{{ $client->preferredFacility->facility_name }}</a></dd></div>@endif
                    @if ($client->preferred_species)<div><dt class="text-sm font-medium text-slate-500">{{ __('Preferred species') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $client->preferred_species }}</dd></div>@endif
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-base font-semibold text-slate-800 mb-4">{{ __('Activities') }}</h3>
                <p class="text-sm text-slate-500 mb-4">{{ __('Log calls, emails, meetings and notes for this client.') }}</p>
                <form method="POST" action="{{ route('clients.activities.store', $client) }}" class="mb-6 p-4 rounded-lg bg-slate-50 border border-slate-100 space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="activity_type" class="block text-xs font-medium text-slate-600">{{ __('Type') }}</label>
                            <select id="activity_type" name="activity_type" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" required>
                                @foreach (\App\Models\ClientActivity::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('activity_type') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            @error('activity_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="occurred_at" class="block text-xs font-medium text-slate-600">{{ __('Date & time') }}</label>
                            <input type="datetime-local" id="occurred_at" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" required />
                            @error('occurred_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="activity_subject" class="block text-xs font-medium text-slate-600">{{ __('Subject (optional)') }}</label>
                        <input type="text" id="activity_subject" name="subject" value="{{ old('subject') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" />
                        @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="activity_notes" class="block text-xs font-medium text-slate-600">{{ __('Notes (optional)') }}</label>
                        <textarea id="activity_notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg bg-[#3B82F6] text-white text-xs font-semibold hover:bg-[#2563eb]">{{ __('Log activity') }}</button>
                </form>
                @if ($client->activities->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No activities logged yet.') }}</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($client->activities as $activity)
                            <li class="py-3 first:pt-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">{{ \App\Models\ClientActivity::TYPES[$activity->activity_type] ?? $activity->activity_type }}</span>
                                        <span class="text-slate-500 text-xs ml-2">{{ $activity->occurred_at->format('d M Y, H:i') }}</span>
                                        @if ($activity->subject)<p class="mt-1 text-sm font-medium text-slate-900">{{ $activity->subject }}</p>@endif
                                        @if ($activity->notes)<p class="mt-0.5 text-sm text-slate-600">{{ $activity->notes }}</p>@endif
                                        @if ($activity->user)<p class="mt-0.5 text-xs text-slate-400">{{ __('By') }} {{ $activity->user->name }}</p>@endif
                                    </div>
                                    <form method="POST" action="{{ route('client-activities.destroy', $activity) }}" class="inline" onsubmit="return confirm('{{ __('Delete this activity?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-600 text-xs">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-base font-semibold text-slate-800 mb-4">{{ __('Demands') }}</h3>
                @if ($client->demands->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No demands linked yet.') }}</p>
                    <p class="text-sm text-slate-500 mt-1">{{ __('When creating a demand for an external/international client, you can link this client.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase">
                                    <th class="py-2 pr-4">{{ __('Demand #') }}</th>
                                    <th class="py-2 pr-4">{{ __('Title') }}</th>
                                    <th class="py-2 pr-4">{{ __('Quantity') }}</th>
                                    <th class="py-2 pr-4">{{ __('Requested date') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                    <th class="py-2 pr-4 text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($client->demands as $demand)
                                    <tr>
                                        <td class="py-2 pr-4"><a href="{{ route('demands.show', $demand) }}" class="text-indigo-600 hover:underline font-medium">{{ $demand->demand_number }}</a></td>
                                        <td class="py-2 pr-4">{{ $demand->title }}</td>
                                        <td class="py-2 pr-4">{{ $demand->quantity }} {{ $demand->quantity_unit_label }}</td>
                                        <td class="py-2 pr-4">{{ $demand->requested_delivery_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="py-2 pr-4">{{ \App\Models\Demand::STATUSES[$demand->status] ?? $demand->status }}</td>
                                        <td class="py-2 pr-4 text-right"><a href="{{ route('demands.show', $demand) }}" class="text-indigo-600 hover:underline font-medium">{{ __('View') }}</a></td>
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
