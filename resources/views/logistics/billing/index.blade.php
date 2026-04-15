@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
        <form method="GET" action="{{ route('logistics.billing.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
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
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Generate invoice') }}</h2>
            @if ($trips->isEmpty())
                <p class="text-sm text-slate-500">{{ __('No trips available for billing yet. Complete a trip first.') }}</p>
            @else
                <form method="POST" action="{{ route('logistics.billing.store', (int) $trips->first()->id) }}" class="grid gap-2 md:grid-cols-4">
                    @csrf
                    <select name="trip_id" onchange="this.form.action='{{ url('/logistics/billing') }}/'+this.value+'/invoice'" class="rounded-md border-slate-300 text-sm" required>
                        <option value="">{{ __('Trip') }}</option>
                        @foreach ($trips as $trip)
                            <option value="{{ $trip->id }}">#{{ $trip->id }}</option>
                        @endforeach
                    </select>
                    <input type="number" min="0" step="0.01" name="base_cost" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Base cost') }}" required>
                    <input type="number" min="0" step="0.01" name="cost_per_km" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Cost per km') }}" required>
                    <input type="number" min="0" step="0.01" name="distance_km" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Distance km') }}">
                    <input type="number" min="0" step="0.01" name="cost_per_unit" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Cost per unit') }}" required>
                    <input type="number" min="0" step="0.01" name="extra_charges" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Extra charges') }}">
                    <div class="md:col-span-4">
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Create invoice') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <x-logistics.table
            :columns="[__('Invoice'), __('Trip'), __('Total Amount'), __('Payment Status')]"
            :has-rows="$invoices->isNotEmpty()"
            :empty-message="__('No invoices found')"
        >
            @foreach ($invoices as $invoice)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">#{{ $invoice->id }}</td>
                    <td class="px-4 py-3">#{{ $invoice->trip_id }}</td>
                    <td class="px-4 py-3">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$invoice->payment_status" /></td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
