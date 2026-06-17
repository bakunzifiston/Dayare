<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Cut type catalog') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Define cuts and expected yield percentages for your butchery.') }}</p>
            </div>
            <a href="{{ route('butcher.cutting.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back to cutting') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Add cut type') }}</h3>
                <form method="post" action="{{ route('butcher.cutting.types.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                    @csrf
                    <div>
                        <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="T-Bone">
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <label for="meat_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat type') }}</label>
                        <select id="meat_type" name="meat_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($meatTypes as $type)
                                <option value="{{ $type }}" @selected(old('meat_type') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('meat_type')" class="mt-1" />
                    </div>
                    <div>
                        <label for="expected_yield_pct" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Expected yield %') }}</label>
                        <input id="expected_yield_pct" name="expected_yield_pct" type="number" step="0.01" min="0.01" max="100" value="{{ old('expected_yield_pct', '85') }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <x-input-error :messages="$errors->get('expected_yield_pct')" class="mt-1" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Add cut type') }}</button>
                    </div>
                </form>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4">{{ __('Name') }}</th>
                            <th class="py-2 pr-4">{{ __('Meat type') }}</th>
                            <th class="py-2 pr-4">{{ __('Expected yield') }}</th>
                            <th class="py-2">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cutTypes as $cutType)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $cutType->name }}</td>
                                <td class="py-3 pr-4">{{ ucfirst($cutType->meat_type) }}</td>
                                <td class="py-3 pr-4">{{ number_format((float) $cutType->expected_yield_pct, 1) }}%</td>
                                <td class="py-3">
                                    @if ($cutType->is_active)
                                        <x-butcher.status-badge status="in_storage" />
                                    @else
                                        <x-butcher.status-badge status="cancelled" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-slate-500">{{ __('No cut types yet. Add your first cut above.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $cutTypes->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
