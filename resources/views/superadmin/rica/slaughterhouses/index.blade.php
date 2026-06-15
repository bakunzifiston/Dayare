<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <a href="{{ route('rica.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← RICA') }}</a>
            <div class="mt-1 flex items-center gap-3">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200" aria-hidden="true">
                    <span class="[&>svg]:h-4 [&>svg]:w-4">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'building'])
                    </span>
                </span>
                <div>
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('All slaughterhouses') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Registered facilities across all operators.') }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            <section class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('rica.slaughterhouses.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div class="md:col-span-2">
                        <x-input-label for="search" :value="__('Search facility')" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="{{ __('Facility name') }}" />
                    </div>
                    <div>
                        <x-input-label for="business_id" :value="__('Operator')" />
                        <select id="business_id" name="business_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('All operators') }}</option>
                            @foreach ($businesses as $business)
                                <option value="{{ $business->id }}" @selected((string) request('business_id') === (string) $business->id)>
                                    {{ $business->business_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-primary-button>{{ __('Apply') }}</x-primary-button>
                        <a href="{{ route('rica.slaughterhouses.index') }}" class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Registered slaughterhouses') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-4 py-3">{{ __('Slaughterhouse') }}</th>
                                <th class="px-4 py-3">{{ __('Operator') }}</th>
                                <th class="px-4 py-3">{{ __('Slaughter plans') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($slaughterhouses as $facility)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-4 py-3.5">
                                        <a href="{{ route('rica.slaughterhouses.show', $facility) }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy hover:underline">
                                            {{ $facility->facility_name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700">
                                        {{ $facility->business->business_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700 tabular-nums">
                                        {{ number_format($facility->slaughter_plans_count) }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <a href="{{ route('rica.slaughterhouses.show', $facility) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            {{ __('View records') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                                        {{ __('No slaughterhouses match your filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($slaughterhouses->hasPages())
                    <div class="px-4 py-3 border-t border-slate-200">
                        {{ $slaughterhouses->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
