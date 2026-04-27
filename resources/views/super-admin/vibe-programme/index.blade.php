<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('VIBE Programme') }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('Global business monitoring and evaluation workspace.') }}</p>
            </div>
            <a
                href="{{ route('super-admin.vibe-programme.export', array_filter($filters, fn ($value) => $value !== '')) }}"
                class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy"
            >
                {{ __('Export programme CSV') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-5">
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Businesses') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['total_businesses'] ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Filtered: :count', ['count' => $summary['filtered_businesses'] ?? 0]) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Active') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['active_businesses'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Pathway: Active') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['pathway_active'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Pathway: Verification') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['pathway_verification'] ?? 0 }}</p>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('super-admin.vibe-programme.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                    <div class="md:col-span-2">
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search']" placeholder="{{ __('Business name, registration number, owner, VIBE ID') }}" />
                    </div>
                    <div>
                        <x-input-label for="type" :value="__('Sector')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\Business::TYPES as $type)
                                <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="pathway_status" :value="__('Pathway')" />
                        <select id="pathway_status" name="pathway_status" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\Business::PATHWAY_STATUSES as $pathway)
                                <option value="{{ $pathway }}" @selected($filters['pathway_status'] === $pathway)>{{ ucfirst($pathway) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\Business::STATUSES as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-6 flex flex-wrap items-center gap-2 pt-1">
                        <x-primary-button>{{ __('Apply filters') }}</x-primary-button>
                        <a href="{{ route('super-admin.vibe-programme.index') }}" class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Reset') }}
                        </a>
                        <span class="ml-auto inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ __('Results: :count', ['count' => $businesses->total()]) }}
                        </span>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Businesses list') }}</h3>
                </div>
                <div class="overflow-x-auto max-h-[620px]">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur border-b border-slate-200">
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-4 py-3">{{ __('Business') }}</th>
                                <th class="px-4 py-3">{{ __('Owner') }}</th>
                                <th class="px-4 py-3">{{ __('Sector') }}</th>
                                <th class="px-4 py-3">{{ __('Pathway') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3">{{ __('Facilities') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($businesses as $business)
                                <tr class="hover:bg-slate-50/70 transition-colors align-top">
                                    <td class="px-4 py-3.5">
                                        <p class="font-medium text-slate-900">{{ $business->business_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $business->registration_number ?: '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <p class="text-slate-800">{{ $business->user?->name ?? trim(($business->owner_first_name ?? '').' '.($business->owner_last_name ?? '')) ?: '—' }}</p>
                                        <p class="text-xs text-slate-500">{{ $business->user?->email ?? $business->owner_email ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ ucfirst((string) $business->type) }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700">
                                        <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ ucfirst((string) ($business->pathway_status ?? '—')) }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ ($business->status ?? '') === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ ucfirst((string) ($business->status ?? '—')) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700">
                                        <span class="inline-flex min-w-8 justify-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold tabular-nums text-slate-700">{{ $business->facilities_count }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        <a href="{{ route('super-admin.vibe-programme.show', $business) }}" class="text-bucha-primary hover:text-bucha-burgundy text-xs font-semibold">{{ __('Profile') }}</a>
                                        <span class="text-slate-300 mx-1">|</span>
                                        <a href="{{ route('super-admin.vibe-programme.export-business', $business) }}" class="text-slate-700 hover:text-slate-900 text-xs font-semibold">{{ __('Export') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No businesses found for the current filters.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $businesses->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
