<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.stock-counts.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Stock counts') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Start stock count') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('butcher.stock-counts.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf
                <div>
                    <x-input-label for="outlet_id" :value="__('Outlet (optional)')" />
                    <select id="outlet_id" name="outlet_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">{{ __('All outlets') }}</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="count_date" :value="__('Count date')" />
                    <x-text-input id="count_date" name="count_date" type="date" class="mt-1 block w-full" :value="old('count_date', now()->toDateString())" />
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
                </div>
                <p class="text-xs text-slate-500">{{ __('A draft count will be created with one line per active inventory batch.') }}</p>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Start count') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
