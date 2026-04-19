@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <button type="button" class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]" @click="$dispatch('open-order-form')">
            {{ $actionLabel }}
        </button>
    @endslot

    <div class="space-y-4" x-data="{ showForm: @js($errors->any()) }" @open-order-form.window="showForm = true">
        <div class="grid gap-3 lg:grid-cols-3">
            <form method="GET" action="{{ route('logistics.orders.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3 lg:col-span-2">
                <div class="grid gap-2 md:grid-cols-4">
                    <div class="md:col-span-1">
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Company') }}</label>
                        <select name="company_id" class="w-full rounded-md border-slate-300 text-sm">
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Status') }}</label>
                        <select name="status" class="w-full rounded-md border-slate-300 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['confirmed', 'in_progress', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Service') }}</label>
                        <select name="service_type" class="w-full rounded-md border-slate-300 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['local', 'export'] as $st)
                                <option value="{{ $st }}" @selected(($filters['service_type'] ?? '') === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Mode') }}</label>
                        <select name="transport_mode" class="w-full rounded-md border-slate-300 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['road', 'air', 'sea'] as $mode)
                                <option value="{{ $mode }}" @selected(($filters['transport_mode'] ?? '') === $mode)>{{ ucfirst($mode) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Search') }}</label>
                        <input name="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('Order number or location') }}">
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <button class="rounded-md bg-[#334155] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1e293b]">{{ __('Apply filters') }}</button>
                    <a href="{{ route('logistics.orders.index', ['company_id' => $selectedCompanyId]) }}" class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                        {{ __('Reset filters') }}
                    </a>
                </div>
            </form>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Orders in view') }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($orders->count()) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Data refreshes for each module route.') }}</p>
            </div>
        </div>

        <section x-show="showForm" x-transition class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Create new order') }}</h2>
                <button type="button" class="text-xs text-slate-500 hover:text-slate-700" @click="showForm = false">{{ __('Close') }}</button>
            </div>
            <form method="POST" action="{{ route('logistics.orders.store') }}" class="grid gap-2 md:grid-cols-4">
                @csrf
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                <select name="service_type" class="rounded-md border-slate-300 text-sm" required>
                    <option value="local">{{ __('Local') }}</option>
                    <option value="export">{{ __('Export') }}</option>
                </select>
                <select name="transport_mode" class="rounded-md border-slate-300 text-sm" required>
                    <option value="road">{{ __('Road') }}</option>
                    <option value="air">{{ __('Air') }}</option>
                    <option value="sea">{{ __('Sea') }}</option>
                </select>
                <input name="pickup_location" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Pickup location') }}" required>
                <input name="delivery_location" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Delivery location') }}" required>
                <input type="number" min="0" step="0.001" name="total_weight" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Total weight') }}" required>
                <input type="number" min="0" step="0.001" name="total_volume" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Total volume') }}" required>
                <textarea name="special_instructions" rows="2" class="md:col-span-2 rounded-md border-slate-300 text-sm" placeholder="{{ __('Special instructions (optional)') }}"></textarea>
                <div class="md:col-span-4">
                    <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save order') }}</button>
                </div>
            </form>
        </section>

        <x-logistics.table
            :columns="[__('Order'), __('Route'), __('Weight / vol.'), __('Service'), __('Status')]"
            :has-rows="$orders->isNotEmpty()"
            :empty-message="__('No orders found')"
            :empty-action-label="__('Create new order')"
            :empty-action-url="route('logistics.orders.index', ['company_id' => $selectedCompanyId])"
        >
            @foreach ($orders as $order)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $order->order_number ?? ('#'.$order->id) }}</td>
                    <td class="px-4 py-3">{{ $order->pickup_location }} &rarr; {{ $order->delivery_location }}</td>
                    <td class="px-4 py-3 text-sm">{{ number_format((float) $order->total_weight, 3) }} / {{ number_format((float) $order->total_volume, 3) }}</td>
                    <td class="px-4 py-3 text-sm capitalize">{{ $order->service_type }} · {{ str_replace('_', ' ', $order->transport_mode) }}</td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$order->status" /></td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
