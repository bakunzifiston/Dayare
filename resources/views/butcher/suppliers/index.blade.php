@php
    $filters = $filters ?? ['q' => '', 'status' => 'all', 'type' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'types' => 0];
    $typeLabel = static fn (string $type): string => str_replace('_', ' ', ucfirst($type));
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.suppliers.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <label for="supplier_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input
                                id="supplier_q"
                                type="search"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="{{ __('Name, phone, district…') }}"
                                class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary"
                            >
                        </div>
                        <div>
                            <label for="supplier_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="supplier_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                <option value="active" @selected($filters['status'] === 'active')>{{ __('Active') }}</option>
                                <option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="supplier_type_filter" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</label>
                            <select id="supplier_type_filter" name="type" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['type'] === 'all')>{{ __('All types') }}</option>
                                @foreach (\App\Models\ButcherSupplier::SUPPLIER_TYPES as $supplierType)
                                    <option value="{{ $supplierType }}" @selected($filters['type'] === $supplierType)>{{ $typeLabel($supplierType) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.suppliers.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a
                            href="{{ route('butcher.suppliers.create') }}"
                            class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10"
                        >
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Add supplier') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Total suppliers')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-truck" />
                <x-butcher.kpi-card :label="__('Active')" :value="(string) $kpis['active']" tone="emerald" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Inactive')" :value="(string) $kpis['inactive']" tone="amber" icon="ti ti-ban" />
                <x-butcher.kpi-card :label="__('Supplier types')" :value="(string) $kpis['types']" tone="sky" icon="ti ti-tags" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Supplier directory') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Manual entries for procurement and receiving.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count supplier|:count suppliers', $suppliers->total(), ['count' => $suppliers->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Supplier') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Type') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Contact') }}</th>
                                <th class="hidden lg:table-cell px-4 py-3 sm:px-5">{{ __('Location') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right">{{ __('POs') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Deliveries') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($suppliers as $supplier)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-truck text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-900">{{ $supplier->name }}</p>
                                                @if ($supplier->email)
                                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $supplier->email }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $typeLabel((string) $supplier->supplier_type) }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">
                                        <p>{{ $supplier->contact_person ?: '—' }}</p>
                                        @if ($supplier->phone)
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $supplier->phone }}</p>
                                        @endif
                                    </td>
                                    <td class="hidden lg:table-cell px-4 py-3 sm:px-5 text-slate-700">
                                        @if ($supplier->district || $supplier->sector)
                                            {{ $supplier->district ?: '—' }}@if ($supplier->sector)<span class="text-slate-400"> · {{ $supplier->sector }}</span>@endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @if ($supplier->is_active)
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200/80">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $supplier->purchase_orders_count }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $supplier->deliveries_count }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('butcher.suppliers.edit', $supplier) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                <i class="ti ti-pencil text-sm leading-none" aria-hidden="true"></i>
                                                {{ __('Edit') }}
                                            </a>
                                            <form method="post" action="{{ route('butcher.suppliers.destroy', $supplier) }}" onsubmit="return confirm(@js(__('Remove this supplier?')))">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                                >
                                                    <i class="ti ti-trash text-sm leading-none" aria-hidden="true"></i>
                                                    {{ __('Remove') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' || $filters['type'] !== 'all'
                                            ? __('No suppliers match your filters.')
                                            : __('No suppliers yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($suppliers->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $suppliers->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
