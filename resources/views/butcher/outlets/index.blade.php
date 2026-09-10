@php
    $editingOutlet = $editing ?? null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Outlets') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Shops and counters under :name.', ['name' => $business->business_name]) }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Outlet directory') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Primary outlet is used as the default for receiving and POS.') }}</p>
                <div class="mt-4 space-y-3">
                    @forelse ($outlets as $outlet)
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-slate-900">{{ $outlet->name }}</p>
                                <div class="flex items-center gap-2">
                                    @if ($outlet->is_primary)
                                        <span class="rounded-full bg-bucha-primary/10 px-2 py-0.5 text-xs font-medium text-bucha-primary">{{ __('Primary') }}</span>
                                    @endif
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ ucfirst($outlet->status) }}</span>
                                </div>
                            </div>
                            @if ($outlet->phone)
                                <p class="mt-1 text-slate-500">{{ $outlet->phone }}</p>
                            @endif
                            @if ($outlet->district)
                                <p class="mt-1 text-slate-500">{{ $outlet->district }}@if ($outlet->sector), {{ $outlet->sector }}@endif</p>
                            @endif
                            <div class="mt-2 flex gap-3 text-xs font-semibold">
                                <a href="{{ route('butcher.outlets.index', ['edit' => $outlet->id]) }}" class="text-bucha-primary hover:underline">{{ __('Edit') }}</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No outlets yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <form
                method="post"
                action="{{ $editingOutlet ? route('butcher.outlets.update', $editingOutlet) : route('butcher.outlets.store') }}"
                class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4"
            >
                @csrf
                @if ($editingOutlet)
                    @method('PUT')
                @endif

                <h3 class="text-sm font-semibold text-slate-900">
                    {{ $editingOutlet ? __('Edit outlet') : __('Add outlet') }}
                </h3>

                <div>
                    <x-input-label for="name" :value="__('Outlet name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $editingOutlet?->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="district" :value="__('District')" />
                        <select id="district" name="district" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('Select district') }}</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district }}" @selected(old('district', $editingOutlet?->district) === $district)>{{ $district }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('district')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="sector" :value="__('Sector')" />
                        <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full" :value="old('sector', $editingOutlet?->sector)" />
                        <x-input-error :messages="$errors->get('sector')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" placeholder="+250788123456" :value="old('phone', $editingOutlet?->phone)" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="gps_lat" :value="__('GPS latitude')" />
                        <x-text-input id="gps_lat" name="gps_lat" type="number" step="any" class="mt-1 block w-full" :value="old('gps_lat', $editingOutlet?->gps_lat)" />
                        <x-input-error :messages="$errors->get('gps_lat')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="gps_lng" :value="__('GPS longitude')" />
                        <x-text-input id="gps_lng" name="gps_lng" type="number" step="any" class="mt-1 block w-full" :value="old('gps_lng', $editingOutlet?->gps_lng)" />
                        <x-input-error :messages="$errors->get('gps_lng')" class="mt-2" />
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', $editingOutlet?->is_primary ?? false)) class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary">
                    {{ __('Primary outlet') }}
                </label>

                @if ($editingOutlet)
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach (\App\Models\ButcherOutlet::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $editingOutlet->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                @endif

                <div class="flex flex-wrap justify-between gap-3 pt-2">
                    @if ($editingOutlet)
                        <a href="{{ route('butcher.outlets.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Cancel edit') }}
                        </a>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ $editingOutlet ? __('Update outlet') : __('Add outlet') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
