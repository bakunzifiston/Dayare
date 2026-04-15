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
                            @foreach (['pending', 'approved', 'rejected'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Priority') }}</label>
                        <select name="priority" class="w-full rounded-md border-slate-300 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['low', 'normal', 'high'] as $priority)
                                <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Search') }}</label>
                        <input name="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('Order # or location') }}">
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
                <input type="number" min="1" name="client_id" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Client business ID') }}" required>
                <input name="pickup_location" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Pickup location') }}" required>
                <input name="delivery_location" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Delivery location') }}" required>
                <input name="species" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Species (optional)') }}">
                <input type="number" min="1" name="quantity" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Quantity') }}" required>
                <input type="number" min="0" step="0.01" name="weight" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Weight') }}">
                <input type="date" name="requested_date" class="rounded-md border-slate-300 text-sm" required>
                <select name="priority" class="rounded-md border-slate-300 text-sm">
                    <option value="low">low</option>
                    <option value="normal" selected>normal</option>
                    <option value="high">high</option>
                </select>
                <div class="md:col-span-4">
                    <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save order') }}</button>
                </div>
            </form>
        </section>

        <x-logistics.table
            :columns="[__('Order'), __('Route'), __('Quantity'), __('Priority'), __('Status'), __('Actions')]"
            :has-rows="$orders->isNotEmpty()"
            :empty-message="__('No orders found')"
            :empty-action-label="__('Create new order')"
            :empty-action-url="route('logistics.orders.index', ['company_id' => $selectedCompanyId])"
        >
            @foreach ($orders as $order)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">#{{ $order->id }}</td>
                    <td class="px-4 py-3">{{ $order->pickup_location }} &rarr; {{ $order->delivery_location }}</td>
                    <td class="px-4 py-3">{{ number_format((float) $order->quantity, 2) }}</td>
                    <td class="px-4 py-3 capitalize">{{ $order->priority }}</td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$order->status" /></td>
                    <td class="px-4 py-3">
                        <x-logistics.actions-menu :actions="array_filter([
                            $order->status === 'pending' ? ['label' => __('Approve order'), 'href' => route('logistics.orders.approve', $order), 'method' => 'POST'] : null,
                        ])" />
                    </td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
