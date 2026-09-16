@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.inventory.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Inventory') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-temperature text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Temperature logs') }}</h2>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('butcher.inventory.temperatures.store') }}" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                @csrf
                <div class="space-y-4 p-4 sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Quick temperature log') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                            <select id="outlet_id" name="outlet_id" required class="{{ $fieldClass }}">
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                        </div>
                        <div>
                            <label for="storage_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Storage type') }}</label>
                            <select id="storage_type" name="storage_type" required class="{{ $fieldClass }}">
                                @foreach (\App\Models\ButcherTemperatureLog::STORAGE_TYPES as $type)
                                    <option value="{{ $type }}" @selected(old('storage_type', 'fresh') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="storage_location" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Storage location') }}</label>
                            <input id="storage_location" name="storage_location" type="text" placeholder="Fridge A" value="{{ old('storage_location') }}" required class="{{ $fieldClass }}">
                            <x-input-error :messages="$errors->get('storage_location')" class="mt-2" />
                        </div>
                        <div>
                            <label for="temperature_celsius" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Temperature (°C)') }}</label>
                            <input id="temperature_celsius" name="temperature_celsius" type="number" step="0.1" value="{{ old('temperature_celsius') }}" required class="{{ $fieldClass }}">
                            <x-input-error :messages="$errors->get('temperature_celsius')" class="mt-2" />
                        </div>
                    </div>
                </div>
                <div class="flex justify-end border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Log temperature') }}
                    </button>
                </div>
            </form>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent logs') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Reading') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Outlet') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Temp') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Breach') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($logs as $log)
                                <tr @class(['hover:bg-slate-50/80', 'bg-red-50/60' => $log->is_breach])>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-temperature text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $log->storage_location }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $log->logged_at?->format('Y-m-d H:i') }} · {{ ucfirst($log->storage_type) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $log->outlet?->name }}</td>
                                    <td class="px-4 py-3 sm:px-5 tabular-nums text-slate-900">{{ $log->temperature_celsius }}°C</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @if ($log->is_breach)
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200/80">{{ __('Yes') }}</span>
                                        @else
                                            <span class="text-slate-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('No logs yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $logs->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
