@php
    $isEdit = $outlet !== null;
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.outlets.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Outlets') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-building-store text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? __('Edit outlet') : __('Add outlet') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $isEdit ? __('Update outlet details and status.') : __('Create an outlet for receiving, inventory, and POS.') }}</p>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ $isEdit ? route('butcher.outlets.update', $outlet) : route('butcher.outlets.store') }}"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Outlet') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet name') }}</label>
                                <input id="name" name="name" type="text" required value="{{ old('name', $outlet?->name) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="district" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('District') }}</label>
                                <select id="district" name="district" required class="{{ $fieldClass }}">
                                    <option value="">{{ __('Select district') }}</option>
                                    @foreach ($districts as $district)
                                        <option value="{{ $district }}" @selected(old('district', $outlet?->district) === $district)>{{ $district }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('district')" class="mt-2" />
                            </div>
                            <div>
                                <label for="sector" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sector') }}</label>
                                <input id="sector" name="sector" type="text" value="{{ old('sector', $outlet?->sector) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('sector')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="phone" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Phone') }}</label>
                                <input id="phone" name="phone" type="text" required placeholder="+250788123456" value="{{ old('phone', $outlet?->phone) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Location') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="gps_lat" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('GPS latitude') }}</label>
                                <input id="gps_lat" name="gps_lat" type="number" step="any" value="{{ old('gps_lat', $outlet?->gps_lat) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('gps_lat')" class="mt-2" />
                            </div>
                            <div>
                                <label for="gps_lng" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('GPS longitude') }}</label>
                                <input id="gps_lng" name="gps_lng" type="number" step="any" value="{{ old('gps_lng', $outlet?->gps_lng) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('gps_lng')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="inline-flex w-full items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-700">
                                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', $outlet?->is_primary ?? false)) class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary">
                                    <span class="font-medium text-slate-900">{{ __('Primary outlet') }}</span>
                                </label>
                            </div>
                            @if ($isEdit)
                                <div class="sm:col-span-2">
                                    <label for="status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                                    <select id="status" name="status" class="{{ $fieldClass }}">
                                        @foreach (\App\Models\ButcherOutlet::STATUSES as $status)
                                            <option value="{{ $status }}" @selected(old('status', $outlet->status) === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.outlets.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti {{ $isEdit ? 'ti-device-floppy' : 'ti-plus' }} text-base leading-none" aria-hidden="true"></i>
                        {{ $isEdit ? __('Update outlet') : __('Add outlet') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
