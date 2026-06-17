<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.onboarding.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Onboarding') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Permits & licenses') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('butcher.onboarding.partials.progress', ['progress' => $progress])

            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Uploaded permits') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($permits as $permit)
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <p class="font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($permit->permit_type)) }} · {{ $permit->permit_number }}</p>
                            <p class="mt-1 text-slate-600">{{ __('Issued by') }}: {{ $permit->issued_by }}</p>
                            <p class="mt-1 text-slate-500">{{ $permit->issue_date?->format('Y-m-d') }} → {{ $permit->expiry_date?->format('Y-m-d') }}</p>
                            @if ($permit->documentUrl())
                                <a href="{{ $permit->documentUrl() }}" target="_blank" class="mt-2 inline-block text-xs font-semibold text-bucha-primary hover:underline">{{ __('View document') }}</a>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No permits uploaded yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <form method="post" action="{{ route('butcher.onboarding.permits.store') }}" enctype="multipart/form-data" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Upload permit') }}</h3>

                <div>
                    <x-input-label for="permit_type" :value="__('Permit type')" />
                    <select id="permit_type" name="permit_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach (\App\Models\ButcherPermit::PERMIT_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('permit_type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('permit_type')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="permit_number" :value="__('Permit number')" />
                        <x-text-input id="permit_number" name="permit_number" type="text" class="mt-1 block w-full" :value="old('permit_number')" required />
                        <x-input-error :messages="$errors->get('permit_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="issued_by" :value="__('Issued by')" />
                        <x-text-input id="issued_by" name="issued_by" type="text" class="mt-1 block w-full" :value="old('issued_by')" required />
                        <x-input-error :messages="$errors->get('issued_by')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="issue_date" :value="__('Issue date')" />
                        <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date')" required />
                        <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="expiry_date" :value="__('Expiry date')" />
                        <x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" required />
                        <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="document" :value="__('Document (PDF or image, optional)')" />
                    <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-slate-600" />
                    <x-input-error :messages="$errors->get('document')" class="mt-2" />
                </div>

                <div class="flex flex-wrap justify-between gap-3 pt-2">
                    <a href="{{ route('butcher.onboarding.suppliers') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('Skip to suppliers') }}
                    </a>
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Upload permit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
