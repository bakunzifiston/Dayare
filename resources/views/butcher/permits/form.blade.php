@php
    $isEdit = $permit !== null;
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
    $typeLabel = static fn (string $type): string => str_replace('_', ' ', ucfirst($type));
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.permits.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Permits') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-certificate text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? __('Edit permit') : __('Add permit') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $isEdit ? __('Update permit details and supporting document.') : __('Register a permit with issue and expiry dates.') }}</p>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ $isEdit ? route('butcher.permits.update', $permit) : route('butcher.permits.store') }}"
                enctype="multipart/form-data"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Permit') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="permit_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Permit type') }}</label>
                                <select id="permit_type" name="permit_type" required class="{{ $fieldClass }}">
                                    @foreach ($permitTypes as $type)
                                        <option value="{{ $type }}" @selected(old('permit_type', $permit?->permit_type) === $type)>{{ $typeLabel($type) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('permit_type')" class="mt-2" />
                            </div>
                            <div>
                                <label for="permit_number" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Permit number') }}</label>
                                <input id="permit_number" name="permit_number" type="text" required value="{{ old('permit_number', $permit?->permit_number) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('permit_number')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="issued_by" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issued by') }}</label>
                                <input id="issued_by" name="issued_by" type="text" required value="{{ old('issued_by', $permit?->issued_by) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('issued_by')" class="mt-2" />
                            </div>
                            <div>
                                <label for="issue_date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issue date') }}</label>
                                <input id="issue_date" name="issue_date" type="date" required value="{{ old('issue_date', optional($permit?->issue_date)->format('Y-m-d')) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                            </div>
                            <div>
                                <label for="expiry_date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Expiry date') }}</label>
                                <input id="expiry_date" name="expiry_date" type="date" required value="{{ old('expiry_date', optional($permit?->expiry_date)->format('Y-m-d')) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Document') }}</h3>
                        <div>
                            <label for="document" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Upload') }}</label>
                            <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-bucha file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                            @if ($permit?->documentUrl())
                                <p class="mt-2 text-xs">
                                    <a href="{{ $permit->documentUrl() }}" target="_blank" rel="noopener" class="font-semibold text-bucha-primary hover:text-bucha-burgundy">{{ __('View current document') }}</a>
                                </p>
                            @endif
                            <x-input-error :messages="$errors->get('document')" class="mt-2" />
                        </div>
                        @if ($isEdit)
                            <div>
                                <label for="status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                                <select id="status" name="status" class="{{ $fieldClass }}">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(old('status', $permit->status) === $status)>{{ $typeLabel($status) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        @endif
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.permits.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti {{ $isEdit ? 'ti-device-floppy' : 'ti-plus' }} text-base leading-none" aria-hidden="true"></i>
                        {{ $isEdit ? __('Update permit') : __('Add permit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
