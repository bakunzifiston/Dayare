@php
    $editingPermit = $editing ?? null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Permits') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Licenses and certificates for :name.', ['name' => $business->business_name]) }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Permit register') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Track expiry dates and keep supporting documents on file.') }}</p>
                <div class="mt-4 space-y-3">
                    @forelse ($permits as $permit)
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-slate-900">{{ $permit->permit_number }}</p>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">{{ str_replace('_', ' ', ucfirst($permit->permit_type)) }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ str_replace('_', ' ', ucfirst($permit->status)) }}</span>
                                </div>
                            </div>
                            <p class="mt-1 text-slate-600">{{ $permit->issued_by }}</p>
                            <p class="mt-1 text-slate-500">
                                {{ optional($permit->issue_date)->format('Y-m-d') }}
                                →
                                {{ optional($permit->expiry_date)->format('Y-m-d') }}
                            </p>
                            <div class="mt-2 flex gap-3 text-xs font-semibold">
                                <a href="{{ route('butcher.permits.index', ['edit' => $permit->id]) }}" class="text-bucha-primary hover:underline">{{ __('Edit') }}</a>
                                @if ($permit->documentUrl())
                                    <a href="{{ $permit->documentUrl() }}" target="_blank" rel="noopener" class="text-bucha-primary hover:underline">{{ __('Document') }}</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No permits yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <form
                method="post"
                action="{{ $editingPermit ? route('butcher.permits.update', $editingPermit) : route('butcher.permits.store') }}"
                enctype="multipart/form-data"
                class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4"
            >
                @csrf
                @if ($editingPermit)
                    @method('PUT')
                @endif

                <h3 class="text-sm font-semibold text-slate-900">
                    {{ $editingPermit ? __('Edit permit') : __('Add permit') }}
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="permit_type" :value="__('Permit type')" />
                        <select id="permit_type" name="permit_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($permitTypes as $type)
                                <option value="{{ $type }}" @selected(old('permit_type', $editingPermit?->permit_type) === $type)>
                                    {{ str_replace('_', ' ', ucfirst($type)) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('permit_type')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="permit_number" :value="__('Permit number')" />
                        <x-text-input id="permit_number" name="permit_number" type="text" class="mt-1 block w-full" :value="old('permit_number', $editingPermit?->permit_number)" required />
                        <x-input-error :messages="$errors->get('permit_number')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="issued_by" :value="__('Issued by')" />
                    <x-text-input id="issued_by" name="issued_by" type="text" class="mt-1 block w-full" :value="old('issued_by', $editingPermit?->issued_by)" required />
                    <x-input-error :messages="$errors->get('issued_by')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="issue_date" :value="__('Issue date')" />
                        <x-text-input
                            id="issue_date"
                            name="issue_date"
                            type="date"
                            class="mt-1 block w-full"
                            :value="old('issue_date', optional($editingPermit?->issue_date)->format('Y-m-d'))"
                            required
                        />
                        <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="expiry_date" :value="__('Expiry date')" />
                        <x-text-input
                            id="expiry_date"
                            name="expiry_date"
                            type="date"
                            class="mt-1 block w-full"
                            :value="old('expiry_date', optional($editingPermit?->expiry_date)->format('Y-m-d'))"
                            required
                        />
                        <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="document" :value="__('Document')" />
                    <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-bucha file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('PDF or image, max 5 MB.') }}</p>
                    @if ($editingPermit?->documentUrl())
                        <p class="mt-1 text-xs">
                            <a href="{{ $editingPermit->documentUrl() }}" target="_blank" rel="noopener" class="font-semibold text-bucha-primary hover:underline">{{ __('View current document') }}</a>
                        </p>
                    @endif
                    <x-input-error :messages="$errors->get('document')" class="mt-2" />
                </div>

                @if ($editingPermit)
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $editingPermit->status) === $status)>
                                    {{ str_replace('_', ' ', ucfirst($status)) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                @endif

                <div class="flex flex-wrap justify-between gap-3 pt-2">
                    @if ($editingPermit)
                        <a href="{{ route('butcher.permits.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Cancel edit') }}
                        </a>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ $editingPermit ? __('Update permit') : __('Add permit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
