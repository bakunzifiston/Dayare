<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Add demand') }}</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('demands.store') }}" class="space-y-6" id="demand-form">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Basic information') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="business_id" :value="__('Business')" />
                        <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            <option value="">{{ __('Select business') }}</option>
                            @foreach ($businesses as $b)
                                <option value="{{ $b->id }}" @selected(old('business_id') == $b->id)>{{ $b->business_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('business_id')" />
                    </div>
                    <div>
                        <x-input-label for="demand_number" :value="__('Demand number (optional, auto-generated if empty)')" />
                        <x-text-input id="demand_number" name="demand_number" type="text" class="mt-1 block w-full" :value="old('demand_number')" placeholder="e.g. DEM-2026-0001" />
                        <x-input-error class="mt-2" :messages="$errors->get('demand_number')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required placeholder="e.g. March order – Butchery Kigali" />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>
                <div>
                    <x-input-label for="contract_id" :value="__('Contract (optional)')" />
                    <select id="contract_id" name="contract_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($contracts as $c)
                            <option value="{{ $c->id }}" @selected(old('contract_id') == $c->id)>{{ $c->contract_number }} — {{ $c->title }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('contract_id')" />
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Destination') }}</h2>
                <p class="text-sm text-slate-500">{{ __('Choose a facility you deliver to, or an external/international client (e.g. client in another country).') }}</p>
                <div>
                    <x-input-label :value="__('Destination type')" />
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="destination_type" value="facility" class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary" @checked(old('destination_type', 'facility') === 'facility')>
                            <span class="text-sm">{{ __('Facility (domestic or known)') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="destination_type" value="external" class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary" @checked(old('destination_type') === 'external')>
                            <span class="text-sm">{{ __('External / International client') }}</span>
                        </label>
                    </div>
                </div>
                <div id="destination-facility-block">
                    <x-input-label for="destination_facility_id" :value="__('Facility')" />
                    <select id="destination_facility_id" name="destination_facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                        <option value="">{{ __('Select facility') }}</option>
                        @foreach ($facilities as $f)
                            <option value="{{ $f->id }}" @selected(old('destination_facility_id') == $f->id)>{{ $f->facility_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('destination_facility_id')" />
                </div>
                <div id="destination-external-block" class="hidden space-y-4">
                    <div>
                        <x-input-label for="client_id" :value="__('Link to existing client (optional)')" />
                        <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('None – enter details below') }}</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}" data-name="{{ e($c->name) }}" data-company="{{ e($c->name) }}" data-country="{{ e($c->country ?? '') }}" data-contact="{{ e($c->phone ?? $c->email ?? '') }}" data-address="{{ e($c->address_line_1 ?? '') }} {{ e($c->address_line_2 ?? '') }}" @selected(old('client_id') == $c->id)>{{ $c->name }} ({{ $c->country }})</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Select a client to prefill name, country and contact.') }}</p>
                    </div>
                    <div>
                        <x-input-label for="client_name" :value="__('Client / contact name')" />
                        <x-text-input id="client_name" name="client_name" type="text" class="mt-1 block w-full" :value="old('client_name')" />
                        <x-input-error class="mt-2" :messages="$errors->get('client_name')" />
                    </div>
                    <div>
                        <x-input-label for="client_company" :value="__('Company name (optional)')" />
                        <x-text-input id="client_company" name="client_company" type="text" class="mt-1 block w-full" :value="old('client_company')" />
                    </div>
                    <div>
                        <x-input-label for="client_country" :value="__('Country')" />
                        <x-text-input id="client_country" name="client_country" type="text" class="mt-1 block w-full" :value="old('client_country')" placeholder="e.g. Uganda, Kenya, DRC" />
                        <x-input-error class="mt-2" :messages="$errors->get('client_country')" />
                    </div>
                    <div>
                        <x-input-label for="client_contact" :value="__('Phone or email')" />
                        <x-text-input id="client_contact" name="client_contact" type="text" class="mt-1 block w-full" :value="old('client_contact')" />
                    </div>
                    <div>
                        <x-input-label for="client_address" :value="__('Address (optional)')" />
                        <textarea id="client_address" name="client_address" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('client_address') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Product requested') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="species" :value="__('Species')" />
                        <select id="species" name="species" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species') === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>
                    <div>
                        <x-input-label for="quantity_unit" :value="__('Unit')" />
                        <select id="quantity_unit" name="quantity_unit" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            @if (isset($units) && $units->isNotEmpty())
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->code }}" @selected(old('quantity_unit', $units->first()?->code ?? 'kg') === $unit->code)>{{ $unit->name }}</option>
                                @endforeach
                            @else
                                @foreach (\App\Models\Demand::QUANTITY_UNITS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('quantity_unit', 'kg') === $value)>{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="product_description" :value="__('Product description (optional)')" />
                    <x-text-input id="product_description" name="product_description" type="text" class="mt-1 block w-full" :value="old('product_description')" placeholder="e.g. Beef cuts, Whole carcass" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="quantity" :value="__('Quantity')" />
                        <x-text-input id="quantity" name="quantity" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('quantity')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('quantity')" />
                    </div>
                    <div>
                        <x-input-label for="requested_delivery_date" :value="__('Requested delivery date')" />
                        <x-text-input id="requested_delivery_date" name="requested_delivery_date" type="date" class="mt-1 block w-full" :value="old('requested_delivery_date')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('requested_delivery_date')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                        @foreach (\App\Models\Demand::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Create demand') }}</button>
                <a href="{{ route('demands.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var destFacility = document.getElementById('destination-facility-block');
            var destExternal = document.getElementById('destination-external-block');
            var facilitySelect = document.getElementById('destination_facility_id');
            var clientSelect = document.getElementById('client_id');
            var clientName = document.getElementById('client_name');
            var clientCompany = document.getElementById('client_company');
            var clientCountry = document.getElementById('client_country');
            var clientContact = document.getElementById('client_contact');
            var clientAddress = document.getElementById('client_address');

            function toggleDestination() {
                var type = document.querySelector('input[name="destination_type"]:checked')?.value;
                if (type === 'external') {
                    destFacility.classList.add('hidden');
                    destExternal.classList.remove('hidden');
                    facilitySelect.value = '';
                } else {
                    destFacility.classList.remove('hidden');
                    destExternal.classList.add('hidden');
                    clientSelect.value = '';
                }
            }
            document.querySelectorAll('input[name="destination_type"]').forEach(function(r) {
                r.addEventListener('change', toggleDestination);
            });
            toggleDestination(); // initial state on load

            clientSelect.addEventListener('change', function() {
                var opt = this.options[this.selectedIndex];
                if (!opt || !opt.value) return;
                if (clientName) clientName.value = opt.dataset.name || '';
                if (clientCompany) clientCompany.value = opt.dataset.company || '';
                if (clientCountry) clientCountry.value = opt.dataset.country || '';
                if (clientContact) clientContact.value = opt.dataset.contact || '';
                if (clientAddress) clientAddress.value = (opt.dataset.address || '').trim();
            });

            document.getElementById('demand-form').addEventListener('submit', function() {
                var type = document.querySelector('input[name="destination_type"]:checked')?.value;
                if (type === 'facility') {
                    facilitySelect.setAttribute('required', 'required');
                } else {
                    facilitySelect.removeAttribute('required');
                }
            });
        });
    </script>
</x-app-layout>
