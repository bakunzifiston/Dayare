<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('AR invoices') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0 space-y-4">
            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search invoice number or note') }}" class="h-9 rounded-lg border border-slate-200 px-3 text-sm">
                        <select name="status" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                            <option value="">{{ __('All document statuses') }}</option>
                            @foreach (['draft', 'issued', 'overdue', 'paid', 'cancelled'] as $s)
                                <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <select name="payment_state" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                            <option value="">{{ __('All payment states') }}</option>
                            @foreach (['paid' => __('Paid'), 'unpaid' => __('Unpaid'), 'pending' => __('Pending')] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['payment_state'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    </form>
                    <a href="{{ route('finance.invoices.create') }}" class="h-9 inline-flex items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white">{{ __('New invoice') }}</a>
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200 bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-4 py-2">{{ __('Invoice') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Date') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Client') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Site') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Source') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Payment') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Total') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Paid') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Due') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2 font-medium text-slate-800">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-2">{{ optional($invoice->issued_at)->format('Y-m-d') ?? optional($invoice->created_at)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2">
                                        @if ($invoice->animalIntake)
                                            <span class="block font-medium text-slate-800">{{ $invoice->animalIntake->clientSourceDisplayName() }}</span>
                                            <span class="text-xs text-slate-500">{{ __($invoice->animalIntake->species) }} · {{ $invoice->animalIntake->number_of_animals }} {{ __('animals') }}</span>
                                        @else
                                            {{ $invoice->client?->name ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">{{ $invoice->facility?->facility_name ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ ucfirst((string) ($invoice->source_type ?: 'manual')) }}</td>
                                    <td class="px-4 py-2">
                                        <span class="block">{{ $invoice->paymentStateLabel() }}</span>
                                        @if ($invoice->needsEbmFollowUp())
                                            <span class="text-xs font-semibold text-amber-700">{{ __('EBM follow-up') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $invoice->amount_paid, 2) }}</td>
                                    <td class="px-4 py-2">{{ optional($invoice->due_date)->format('Y-m-d') ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center justify-end gap-2">
                                            @include('finance.invoices._row-actions', ['invoice' => $invoice, 'from' => 'invoices'])
                                            @if ((float) $invoice->amount_paid < (float) $invoice->total_amount)
                                                <form method="POST" action="{{ route('finance.invoices.mark-paid', $invoice) }}">
                                                    @csrf
                                                    <button class="text-green-700">{{ __('Mark paid') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500">{{ __('No invoices found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-100">{{ $invoices->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
