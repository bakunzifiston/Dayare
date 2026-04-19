@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <a href="#plan-trip-form" class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ $actionLabel }}</a>
    @endslot

    <div class="space-y-4">
        <form method="GET" action="{{ route('logistics.planning.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
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

        <section id="plan-trip-form" class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Plan trip') }}</h2>
            <p class="mb-3 text-xs text-slate-500">{{ __('Each trip executes one confirmed order. Allocation is in whole kilograms. Origin and destination reference the shared locations directory.') }}</p>
            <form method="POST" action="{{ route('logistics.planning.store') }}" class="grid gap-2 md:grid-cols-3">
                @csrf
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                <select name="order_id" class="rounded-md border-slate-300 text-sm md:col-span-3" required>
                    <option value="">{{ __('Confirmed order') }}</option>
                    @foreach ($orders->where('status', 'confirmed') as $order)
                        <option value="{{ $order->id }}">{{ $order->order_number ?? ('#'.$order->id) }} — {{ __('max :kg kg', ['kg' => $order->allocatableWeightKg()]) }}</option>
                    @endforeach
                </select>
                <select name="origin_location_id" class="rounded-md border-slate-300 text-sm" required>
                    <option value="">{{ __('Origin location') }}</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                <select name="destination_location_id" class="rounded-md border-slate-300 text-sm" required>
                    <option value="">{{ __('Destination location') }}</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                <input type="number" min="1" name="allocated_weight_kg" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Allocated weight (kg)') }}" required>
                <select name="vehicle_id" class="rounded-md border-slate-300 text-sm" required>
                    <option value="">{{ __('Vehicle') }}</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }}</option>
                    @endforeach
                </select>
                <select name="driver_id" class="rounded-md border-slate-300 text-sm" required>
                    <option value="">{{ __('Driver') }}</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                    @endforeach
                </select>
                <input type="datetime-local" name="planned_departure" class="rounded-md border-slate-300 text-sm" required>
                <input type="datetime-local" name="planned_arrival" class="rounded-md border-slate-300 text-sm" required>
                <textarea name="notes" rows="2" class="md:col-span-3 rounded-md border-slate-300 text-sm" placeholder="{{ __('Notes (optional)') }}"></textarea>
                <input type="hidden" name="compliance_documents[0][type]" value="health_certificate">
                <input type="hidden" name="compliance_documents[0][status]" value="valid">
                <input type="hidden" name="compliance_documents[1][type]" value="movement_permit">
                <input type="hidden" name="compliance_documents[1][status]" value="valid">
                <div class="md:col-span-3">
                    <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Submit trip plan') }}</button>
                </div>
            </form>
        </section>
    </div>
@endcomponent
