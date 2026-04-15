@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
        <form method="GET" action="{{ route('logistics.assets.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
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

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Vehicles') }}</h2>
                <form method="POST" action="{{ route('logistics.vehicles.store') }}" class="grid gap-2 md:grid-cols-2">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input name="plate_number" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Plate number') }}" required>
                    <input name="type" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Type') }}" required>
                    <input type="number" min="1" name="max_units" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Max units') }}" required>
                    <input type="number" min="0" step="0.01" name="max_weight" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Max weight') }}">
                    <div class="md:col-span-2">
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Add vehicle') }}</button>
                    </div>
                </form>
                <div class="mt-4">
                    <x-logistics.table
                        :columns="[__('Plate'), __('Type'), __('Status')]"
                        :has-rows="$vehicles->isNotEmpty()"
                        :empty-message="__('No vehicles found')"
                    >
                        @foreach ($vehicles as $vehicle)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $vehicle->plate_number }}</td>
                                <td class="px-4 py-3">{{ $vehicle->type }}</td>
                                <td class="px-4 py-3"><x-logistics.status-badge :status="$vehicle->status" /></td>
                            </tr>
                        @endforeach
                    </x-logistics.table>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Drivers') }}</h2>
                <form method="POST" action="{{ route('logistics.drivers.store') }}" class="grid gap-2 md:grid-cols-2">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input name="name" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Name') }}" required>
                    <input name="license_number" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('License number') }}" required>
                    <input type="date" name="license_expiry" class="rounded-md border-slate-300 text-sm" required>
                    <div class="md:col-span-2">
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Add driver') }}</button>
                    </div>
                </form>
                <div class="mt-4">
                    <x-logistics.table
                        :columns="[__('Name'), __('License'), __('Status')]"
                        :has-rows="$drivers->isNotEmpty()"
                        :empty-message="__('No drivers found')"
                    >
                        @foreach ($drivers as $driver)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $driver->name }}</td>
                                <td class="px-4 py-3">{{ $driver->license_number }}</td>
                                <td class="px-4 py-3"><x-logistics.status-badge :status="$driver->status" /></td>
                            </tr>
                        @endforeach
                    </x-logistics.table>
                </div>
            </section>
        </div>
    </div>
@endcomponent
