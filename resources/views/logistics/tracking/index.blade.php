@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <button type="button" class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]" @click="$dispatch('open-tracking-form')">
            {{ $actionLabel }}
        </button>
    @endslot

    <div class="space-y-4" x-data="{ showForm: @js($errors->any()) }" @open-tracking-form.window="showForm = true">
        <form method="GET" action="{{ route('logistics.tracking.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
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

        <section id="logistics-tracking-form" x-show="showForm" x-transition class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Log tracking event') }}</h2>
                <button type="button" class="text-xs text-slate-500 hover:text-slate-700" @click="showForm = false">{{ __('Close') }}</button>
            </div>
            <p class="mb-3 text-xs text-slate-500">{{ __('Each row is one event: when it happened (event time), what happened (status), and where (saved location and/or GPS coordinates).') }}</p>
            @if ($trips->isEmpty())
                <p class="text-sm text-slate-500">{{ __('No trips available for tracking yet. Plan and start a trip first.') }}</p>
            @else
                <form method="POST" action="{{ route('logistics.tracking.store', (int) $trips->first()->id) }}" class="grid gap-2 md:grid-cols-4">
                    @csrf
                    <select name="trip_id" onchange="this.form.action='{{ url('/logistics/tracking') }}/'+this.value" class="rounded-md border-slate-300 text-sm md:col-span-2" required>
                        <option value="">{{ __('Trip') }}</option>
                        @foreach ($trips as $trip)
                            <option value="{{ $trip->id }}">#{{ $trip->id }} @if($trip->order) — {{ $trip->order->order_number }} @endif</option>
                        @endforeach
                    </select>
                    <input type="datetime-local" name="event_time" value="{{ old('event_time', now()->format('Y-m-d\TH:i')) }}" class="rounded-md border-slate-300 text-sm md:col-span-2" required>
                    <select name="location_id" class="rounded-md border-slate-300 text-sm md:col-span-2">
                        <option value="">{{ __('Saved location (optional if GPS set)') }}</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Latitude') }}">
                    <input type="number" step="any" name="longitude" value="{{ old('longitude') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Longitude') }}">
                    <select name="status" class="rounded-md border-slate-300 text-sm md:col-span-2" required>
                        @foreach (\App\Models\LogisticsTrackingLog::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                        @endforeach
                    </select>
                    <input name="notes" value="{{ old('notes') }}" class="rounded-md border-slate-300 text-sm md:col-span-4" placeholder="{{ __('Notes (optional)') }}">
                    <div class="md:col-span-4">
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save event') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <x-logistics.table
            :columns="[__('Trip'), __('Event time'), __('Where'), __('Coordinates'), __('Status'), __('Notes')]"
            :has-rows="$trackingLogs->isNotEmpty()"
            :empty-message="__('No tracking events found')"
        >
            @foreach ($trackingLogs as $log)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">#{{ $log->trip_id }}</td>
                    <td class="px-4 py-3">{{ optional($log->event_time)->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 text-sm">{{ $log->location?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        @if ($log->latitude !== null && $log->longitude !== null)
                            {{ number_format((float) $log->latitude, 5) }}, {{ number_format((float) $log->longitude, 5) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$log->status" /></td>
                    <td class="px-4 py-3 text-slate-600">{{ $log->notes ?? '—' }}</td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
