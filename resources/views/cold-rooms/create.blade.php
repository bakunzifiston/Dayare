<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">{{ __('New cold room') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200/60 p-6">
                @if ($facilities->isEmpty())
                    <p class="text-sm text-amber-700">{{ __('You need a facility with type “storage” first. Add one under Businesses → Facilities.') }}</p>
                    <a href="{{ route('businesses.hub') }}" class="mt-4 inline-block text-bucha-primary font-medium">{{ __('Go to businesses') }}</a>
                @else
                    <form method="post" action="{{ route('cold-rooms.manage.store') }}" class="space-y-5">
                        @csrf
                        <div>
                            <x-input-label for="facility_id" :value="__('Storage facility')" />
                            <select id="facility_id" name="facility_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                                @foreach ($facilities as $f)
                                    <option value="{{ $f->id }}" @selected(old('facility_id') == $f->id)>{{ $f->facility_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                        </div>
                        <div>
                            <x-input-label for="name" :value="__('Room name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" placeholder="{{ __('e.g. Chiller A') }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="type" :value="__('Type')" />
                            <select id="type" name="type" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                                <option value="chiller" @selected(old('type') === 'chiller')>{{ __('Chiller') }}</option>
                                <option value="freezer" @selected(old('type') === 'freezer')>{{ __('Freezer') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>
                        <div>
                            <x-input-label for="standard_id" :value="__('Temperature standard')" />
                            <select id="standard_id" name="standard_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="">{{ __('None (monitoring disabled until set)') }}</option>
                                @foreach ($standards as $s)
                                    <option value="{{ $s->id }}" @selected(old('standard_id') == $s->id)>{{ $s->name }} — {{ $s->min_temperature }}–{{ $s->max_temperature }} °C</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('standard_id')" />
                        </div>
                        <div>
                            <x-input-label for="capacity" :value="__('Capacity (optional)')" />
                            <x-text-input id="capacity" name="capacity" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('capacity')" />
                            <x-input-error class="mt-2" :messages="$errors->get('capacity')" />
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Save') }}</button>
                            <a href="{{ route('cold-rooms.manage.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
