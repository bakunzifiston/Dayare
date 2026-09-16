@php
    $registrationValue = old(
        'registration_number',
        str_starts_with((string) $business->registration_number, 'PENDING-') ? '' : $business->registration_number
    );
    $phoneValue = old(
        'contact_phone',
        $business->contact_phone === '0000000000' ? '' : $business->contact_phone
    );
    $progressPercent = is_array($progress ?? null) ? ($progress['percent'] ?? null) : ($progress ?? null);
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
    $typeLabel = static fn (string $type): string => str_replace('_', ' ', ucfirst($type));
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            @if ($progressPercent !== null)
                <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Onboarding progress') }}</p>
                        <p class="text-sm font-semibold text-bucha-primary tabular-nums">{{ $progressPercent }}%</p>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-bucha-primary" style="width: {{ min(100, max(0, (int) $progressPercent)) }}%"></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('butcher.outlets.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ __('Outlets') }}</a>
                        <a href="{{ route('butcher.permits.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ __('Permits') }}</a>
                    </div>
                </section>
            @endif

            <div>
                <a href="{{ route('butcher.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Dashboard') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-building text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Business profile') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Legal identity, contact, and butcher defaults.') }}</p>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('butcher.business.update') }}" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                @csrf
                @method('PUT')

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Identity') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="business_name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Shop / business name') }}</label>
                                <input id="business_name" name="business_name" type="text" required value="{{ old('business_name', $business->business_name) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="butchery_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Butchery type') }}</label>
                                <select id="butchery_type" name="butchery_type" required class="{{ $fieldClass }}">
                                    @foreach (\App\Models\Business::BUTCHERY_TYPES as $type)
                                        <option value="{{ $type }}" @selected(old('butchery_type', $business->butchery_type) === $type)>{{ $typeLabel($type) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('butchery_type')" class="mt-2" />
                            </div>
                            <div>
                                <label for="registration_number" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Registration number') }}</label>
                                <input id="registration_number" name="registration_number" type="text" required value="{{ $registrationValue }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                            </div>
                            <div>
                                <label for="tax_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tax ID (TIN)') }}</label>
                                <input id="tax_id" name="tax_id" type="text" required placeholder="1234567890" value="{{ old('tax_id', $business->tax_id) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Contact') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="contact_phone" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Contact phone') }}</label>
                                <input id="contact_phone" name="contact_phone" type="text" required placeholder="+250788123456" value="{{ $phoneValue }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                            </div>
                            <div>
                                <label for="email" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Email') }}</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $business->email) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="address_line_1" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Street address') }}</label>
                                <input id="address_line_1" name="address_line_1" type="text" value="{{ old('address_line_1', $business->address_line_1) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('address_line_1')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="city" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('City / town') }}</label>
                                <input id="city" name="city" type="text" value="{{ old('city', $business->city) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('city')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Permits & location') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="rfa_permit_number" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('RFA permit number') }}</label>
                                <input id="rfa_permit_number" name="rfa_permit_number" type="text" value="{{ old('rfa_permit_number', $business->rfa_permit_number) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('rfa_permit_number')" class="mt-2" />
                            </div>
                            <div>
                                <label for="rfa_permit_expiry" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('RFA permit expiry') }}</label>
                                <input id="rfa_permit_expiry" name="rfa_permit_expiry" type="date" value="{{ old('rfa_permit_expiry', optional($business->rfa_permit_expiry)->format('Y-m-d')) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('rfa_permit_expiry')" class="mt-2" />
                            </div>
                            <div>
                                <label for="butcher_district" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('District') }}</label>
                                <select id="butcher_district" name="butcher_district" required class="{{ $fieldClass }}">
                                    <option value="">{{ __('Select district') }}</option>
                                    @foreach ($districts as $district)
                                        <option value="{{ $district }}" @selected(old('butcher_district', $business->butcher_district) === $district)>{{ $district }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('butcher_district')" class="mt-2" />
                            </div>
                            <div>
                                <label for="butcher_sector" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sector') }}</label>
                                <input id="butcher_sector" name="butcher_sector" type="text" value="{{ old('butcher_sector', $business->butcher_sector) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('butcher_sector')" class="mt-2" />
                            </div>
                            <div>
                                <label for="butcher_cell" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Cell') }}</label>
                                <input id="butcher_cell" name="butcher_cell" type="text" value="{{ old('butcher_cell', $business->butcher_cell) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('butcher_cell')" class="mt-2" />
                            </div>
                            <div>
                                <label for="gps_lat" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('GPS latitude') }}</label>
                                <input id="gps_lat" name="gps_lat" type="number" step="any" value="{{ old('gps_lat', $business->gps_lat) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('gps_lat')" class="mt-2" />
                            </div>
                            <div>
                                <label for="gps_lng" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('GPS longitude') }}</label>
                                <input id="gps_lng" name="gps_lng" type="number" step="any" value="{{ old('gps_lng', $business->gps_lng) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('gps_lng')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Operations defaults') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label for="butcher_fresh_max_temp_c" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Fresh max temp (°C)') }}</label>
                                <input id="butcher_fresh_max_temp_c" name="butcher_fresh_max_temp_c" type="number" step="0.1" required value="{{ old('butcher_fresh_max_temp_c', $business->butcher_fresh_max_temp_c ?? 4) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('butcher_fresh_max_temp_c')" class="mt-2" />
                            </div>
                            <div>
                                <label for="butcher_frozen_max_temp_c" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Frozen max temp (°C)') }}</label>
                                <input id="butcher_frozen_max_temp_c" name="butcher_frozen_max_temp_c" type="number" step="0.1" required value="{{ old('butcher_frozen_max_temp_c', $business->butcher_frozen_max_temp_c ?? -18) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('butcher_frozen_max_temp_c')" class="mt-2" />
                            </div>
                            <div>
                                <label for="butcher_batch_shelf_life_days" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Batch shelf life (days)') }}</label>
                                <input id="butcher_batch_shelf_life_days" name="butcher_batch_shelf_life_days" type="number" min="1" required value="{{ old('butcher_batch_shelf_life_days', $business->butcher_batch_shelf_life_days ?? 3) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('butcher_batch_shelf_life_days')" class="mt-2" />
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.dashboard') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-device-floppy text-base leading-none" aria-hidden="true"></i>
                        {{ __('Save profile') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
