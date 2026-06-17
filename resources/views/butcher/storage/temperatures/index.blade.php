<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.storage.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold storage') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Temperature logs') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Log twice daily. Fresh max :fresh°C · Frozen max :frozen°C', ['fresh' => $freshThreshold, 'frozen' => $frozenThreshold]) }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('butcher.storage.temperatures.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Quick temperature log') }}</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="outlet_id" :value="__('Outlet')" />
                        <select id="outlet_id" name="outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="storage_type" :value="__('Storage type')" />
                        <select id="storage_type" name="storage_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach (\App\Models\ButcherTemperatureLog::STORAGE_TYPES as $type)
                                <option value="{{ $type }}" @selected(old('storage_type', 'fresh') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="storage_location" :value="__('Storage location')" />
                        <x-text-input id="storage_location" name="storage_location" type="text" class="mt-1 block w-full" placeholder="Fridge A" :value="old('storage_location')" required />
                        <x-input-error :messages="$errors->get('storage_location')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="temperature_celsius" :value="__('Temperature (°C)')" />
                        <x-text-input id="temperature_celsius" name="temperature_celsius" type="number" step="0.1" class="mt-1 block w-full" :value="old('temperature_celsius')" required />
                        <x-input-error :messages="$errors->get('temperature_celsius')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Log temperature') }}</button>
                </div>
            </form>

            <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Outlet') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Location') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Temp') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Breach') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr @class(['bg-red-50/50' => $log->is_breach])>
                                <td class="px-4 py-3">{{ $log->logged_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">{{ $log->outlet?->name }}</td>
                                <td class="px-4 py-3">{{ $log->storage_location }} ({{ $log->storage_type }})</td>
                                <td class="px-4 py-3">{{ $log->temperature_celsius }}°C</td>
                                <td class="px-4 py-3">{{ $log->is_breach ? __('Yes') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No logs yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
