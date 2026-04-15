@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
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

        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Log tracking update') }}</h2>
            @if ($trips->isEmpty())
                <p class="text-sm text-slate-500">{{ __('No trips available for tracking yet. Plan and start a trip first.') }}</p>
            @else
                <form method="POST" action="{{ route('logistics.tracking.store', (int) $trips->first()->id) }}" class="grid gap-2 md:grid-cols-4">
                    @csrf
                    <select name="trip_id" onchange="this.form.action='{{ url('/logistics/tracking') }}/'+this.value" class="rounded-md border-slate-300 text-sm" required>
                        <option value="">{{ __('Trip') }}</option>
                        @foreach ($trips as $trip)
                            <option value="{{ $trip->id }}">#{{ $trip->id }}</option>
                        @endforeach
                    </select>
                    <input type="datetime-local" name="timestamp" class="rounded-md border-slate-300 text-sm" required>
                    <input name="location" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Location') }}" required>
                    <select name="status" class="rounded-md border-slate-300 text-sm" required>
                        @foreach (\App\Models\LogisticsTrackingLog::STATUSES as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                    <input name="notes" class="rounded-md border-slate-300 text-sm md:col-span-3" placeholder="{{ __('Notes (optional)') }}">
                    <div>
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save log') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <x-logistics.table
            :columns="[__('Trip'), __('Timestamp'), __('Location'), __('Status'), __('Notes')]"
            :has-rows="$trackingLogs->isNotEmpty()"
            :empty-message="__('No tracking logs found')"
        >
            @foreach ($trackingLogs as $log)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">#{{ $log->trip_id }}</td>
                    <td class="px-4 py-3">{{ optional($log->timestamp)->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $log->location }}</td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$log->status" /></td>
                    <td class="px-4 py-3 text-slate-600">{{ $log->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
