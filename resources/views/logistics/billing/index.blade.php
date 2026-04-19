@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <button type="button" class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]" @click="$dispatch('open-billing-form')">
            {{ $actionLabel }}
        </button>
    @endslot

    <div class="space-y-4" x-data="{ showForm: @js($errors->any()) }" @open-billing-form.window="showForm = true">
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

        <section id="logistics-billing-form" x-show="showForm" x-transition class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Create invoice') }}</h2>
                <button type="button" class="text-xs text-slate-500 hover:text-slate-700" @click="showForm = false">{{ __('Close') }}</button>
            </div>
            <p class="mb-3 text-xs text-slate-500">{{ __('One invoice per trip. Line items capture distance, weight, flat fees, and other charges. Totals are computed from lines, tax, and discounts.') }}</p>
            @if ($trips->isEmpty())
                <p class="text-sm text-slate-500">{{ __('No trips available for billing yet. Complete a trip first.') }}</p>
            @elseif ($clients->isEmpty())
                <p class="text-sm text-slate-500">{{ __('Add clients under your business CRM before invoicing.') }}</p>
            @else
                <form method="POST" action="{{ route('logistics.billing.store', (int) $trips->first()->id) }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-2 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Trip') }}</label>
                            <select name="trip_id" onchange="this.form.action='{{ url('/logistics/billing') }}/'+this.value+'/invoice'" class="w-full rounded-md border-slate-300 text-sm" required>
                                @foreach ($trips as $trip)
                                    <option value="{{ $trip->id }}">#{{ $trip->id }} @if($trip->order) — {{ $trip->order->order_number }} @endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Client') }}</label>
                            <select name="client_id" class="w-full rounded-md border-slate-300 text-sm" required>
                                <option value="">{{ __('Select client') }}</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Currency') }}</label>
                            <select name="currency" class="w-full rounded-md border-slate-300 text-sm" required>
                                @foreach (['RWF', 'USD', 'EUR'] as $c)
                                    <option value="{{ $c }}" @selected(old('currency', 'RWF') === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Payment status') }}</label>
                            <select name="payment_status" class="w-full rounded-md border-slate-300 text-sm">
                                @foreach (\App\Models\LogisticsInvoice::PAYMENT_STATUSES as $ps)
                                    <option value="{{ $ps }}" @selected(old('payment_status', 'pending') === $ps)>{{ str_replace('_', ' ', $ps) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Tax amount') }}</label>
                            <input type="number" min="0" step="0.01" name="tax_amount" value="{{ old('tax_amount', '0') }}" class="w-full rounded-md border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Discount amount') }}</label>
                            <input type="number" min="0" step="0.01" name="discount_amount" value="{{ old('discount_amount', '0') }}" class="w-full rounded-md border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Issued at') }}</label>
                            <input type="datetime-local" name="issued_at" value="{{ old('issued_at', now()->format('Y-m-d\TH:i')) }}" class="w-full rounded-md border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Due date') }}</label>
                            <input type="datetime-local" name="due_date" value="{{ old('due_date') }}" class="w-full rounded-md border-slate-300 text-sm">
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Line items') }}</h3>
                        <div class="space-y-2">
                            @for ($i = 0; $i < 8; $i++)
                                <div class="grid gap-2 md:grid-cols-12 md:items-end">
                                    <div class="md:col-span-5">
                                        @if ($i === 0)
                                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Description') }}</label>
                                        @endif
                                        <input name="items[{{ $i }}][description]" value="{{ old('items.'.$i.'.description') }}" class="w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('e.g. Distance charge') }}">
                                    </div>
                                    <div class="md:col-span-2">
                                        @if ($i === 0)
                                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Qty') }}</label>
                                        @endif
                                        <input type="number" min="0" step="any" name="items[{{ $i }}][quantity]" value="{{ old('items.'.$i.'.quantity') }}" class="w-full rounded-md border-slate-300 text-sm" placeholder="0">
                                    </div>
                                    <div class="md:col-span-2">
                                        @if ($i === 0)
                                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Unit price') }}</label>
                                        @endif
                                        <input type="number" min="0" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ old('items.'.$i.'.unit_price') }}" class="w-full rounded-md border-slate-300 text-sm" placeholder="0">
                                    </div>
                                    <div class="md:col-span-3">
                                        @if ($i === 0)
                                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Line total (optional)') }}</label>
                                        @endif
                                        <input type="number" min="0" step="0.01" name="items[{{ $i }}][total]" value="{{ old('items.'.$i.'.total') }}" class="w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('Auto: qty × unit') }}">
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div>
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save invoice') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <x-logistics.table
            :columns="[__('Invoice #'), __('Trip'), __('Order'), __('Client'), __('Subtotal'), __('Total'), __('Payment')]"
            :has-rows="$invoices->isNotEmpty()"
            :empty-message="__('No invoices found')"
        >
            @foreach ($invoices as $invoice)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $invoice->invoice_number }}</td>
                    <td class="px-4 py-3">#{{ $invoice->trip_id }}</td>
                    <td class="px-4 py-3 text-sm">{{ $invoice->order?->order_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">{{ $invoice->client?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</td>
                    <td class="px-4 py-3 font-medium">{{ number_format((float) $invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$invoice->payment_status" /></td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
