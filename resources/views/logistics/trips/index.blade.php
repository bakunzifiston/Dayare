@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
        <form method="GET" action="{{ route('logistics.trips.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Company') }}</label>
            <div class="flex gap-2">
                <select name="company_id" class="w-full rounded-md border-slate-300 text-sm">
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-[#334155] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1e293b]">{{ __('Load') }}</button>
            </div>
        </form>

        <x-logistics.table
            :columns="[__('Trip'), __('Order'), __('Route'), __('Schedule'), __('Status'), __('Actions')]"
            :has-rows="$trips->isNotEmpty()"
            :empty-message="__('No trips found')"
            :empty-action-label="__('Plan trip')"
            :empty-action-url="route('logistics.planning.index', ['company_id' => $selectedCompanyId])"
        >
            @foreach ($trips as $trip)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">#{{ $trip->id }}</td>
                    <td class="px-4 py-3 text-sm">{{ $trip->order?->order_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $trip->originLocation?->name ?? '—' }} &rarr; {{ $trip->destinationLocation?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ optional($trip->planned_departure)->format('Y-m-d H:i') }}<br>
                        {{ optional($trip->planned_arrival)->format('Y-m-d H:i') }}
                    </td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$trip->status" /></td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            @if (in_array($trip->status, ['scheduled', 'loaded'], true))
                                <form method="POST" action="{{ route('logistics.trips.start', $trip) }}">
                                    @csrf
                                    <input type="hidden" name="actual_departure" value="{{ now()->format('Y-m-d H:i:s') }}">
                                    <button class="rounded-md bg-[#7A1C22] px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-[#64161c]">{{ __('Start') }}</button>
                                </form>
                            @endif
                            @if (in_array($trip->status, ['in_transit', 'at_checkpoint', 'delayed'], true))
                                <form method="POST" action="{{ route('logistics.trips.complete', $trip) }}" class="space-y-2 rounded-md border border-slate-200 p-2">
                                    @csrf
                                    <input type="hidden" name="actual_arrival" value="{{ now()->format('Y-m-d H:i:s') }}">
                                    <div class="grid grid-cols-2 gap-1 text-[10px] text-slate-500">
                                        <span>{{ __('Allocated (kg)') }}: {{ (int) $trip->allocated_weight_kg }}</span>
                                    </div>
                                    <input type="number" name="delivered_weight_kg" min="0" max="{{ (int) $trip->allocated_weight_kg }}" class="w-full rounded-md border-slate-300 text-xs" placeholder="{{ __('Delivered kg') }}">
                                    <input type="number" name="loss_weight_kg" min="0" max="{{ (int) $trip->allocated_weight_kg }}" class="w-full rounded-md border-slate-300 text-xs" placeholder="{{ __('Loss kg') }}">
                                    <select name="status" class="rounded-md border-slate-300 text-xs">
                                        <option value="completed">{{ __('completed') }}</option>
                                        <option value="cancelled">{{ __('cancelled') }}</option>
                                    </select>
                                    <button class="rounded-md bg-[#166534] px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-[#14532d]">{{ __('Complete') }}</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
