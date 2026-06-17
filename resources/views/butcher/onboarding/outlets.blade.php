<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.onboarding.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Onboarding') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Outlets') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('butcher.onboarding.partials.progress', ['progress' => $progress])

            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Your outlets') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($outlets as $outlet)
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-slate-900">{{ $outlet->name }}</p>
                                @if ($outlet->is_primary)
                                    <span class="rounded-full bg-bucha-primary/10 px-2 py-0.5 text-xs font-semibold text-bucha-primary">{{ __('Primary') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-slate-600">{{ $outlet->district }}@if($outlet->sector) · {{ $outlet->sector }}@endif</p>
                            <p class="mt-1 text-slate-500">{{ $outlet->phone }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No outlets yet. Add your first branch below.') }}</p>
                    @endforelse
                </div>
            </section>

            <form method="post" action="{{ route('butcher.onboarding.outlets.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Add outlet') }}</h3>

                <div>
                    <x-input-label for="name" :value="__('Outlet name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="district" :value="__('District')" />
                        <select id="district" name="district" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('Select district') }}</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district }}" @selected(old('district') === $district)>{{ $district }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('district')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="sector" :value="__('Sector')" />
                        <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full" :value="old('sector')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" placeholder="+250788123456" :value="old('phone')" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary')) class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary">
                    {{ __('Set as primary outlet') }}
                </label>

                <div class="flex flex-wrap justify-between gap-3 pt-2">
                    <a href="{{ route('butcher.onboarding.permits') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('Skip to permits') }}
                    </a>
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Add outlet') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
