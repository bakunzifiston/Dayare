<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Certificate rules') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Whether a certificate of origin is required depends on meat source. Business overrides replace the default without a code change.') }}</p>
                </div>
                <a href="{{ route('sales-compliance.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">{{ __('Site type') }}</th>
                            <th class="px-4 py-2.5">{{ __('Meat source') }}</th>
                            <th class="px-4 py-2.5">{{ __('Certificate') }}</th>
                            <th class="px-4 py-2.5">{{ __('Scope') }}</th>
                            <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rules as $rule)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-2.5 text-slate-800">{{ \App\Support\SalesComplianceCatalog::siteTypeLabel($rule->site_type) }}</td>
                                <td class="px-4 py-2.5 text-slate-700">{{ \App\Support\SalesComplianceCatalog::meatSourceLabel($rule->meat_source) }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $rule->certificate_required ? 'bg-amber-50 text-amber-900' : 'bg-emerald-50 text-emerald-800' }}">
                                        {{ $rule->certificate_required ? __('Required') : __('Not required') }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-700">{{ $rule->business_id ? __('Business override') : __('Default') }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    @if ($rule->business_id)
                                        <form method="POST" action="{{ route('sales-compliance.rules.destroy', $rule) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Remove override') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">{{ __('System default') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Add or replace a business override') }}</p>
            </div>
            <div class="px-4 py-4">
                <form method="post" action="{{ route('sales-compliance.rules.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <x-input-label for="site_type" :value="__('Site type')" />
                        <select id="site_type" name="site_type" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            @foreach ([\App\Support\SalesComplianceCatalog::SITE_RESTAURANT, \App\Support\SalesComplianceCatalog::SITE_BUTCHERY, \App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT] as $type)
                                <option value="{{ $type }}">{{ \App\Support\SalesComplianceCatalog::siteTypeLabel($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="meat_source" :value="__('Meat source')" />
                        <select id="meat_source" name="meat_source" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            @foreach (\App\Support\SalesComplianceCatalog::meatSourceLabels() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="certificate_required" :value="__('Certificate required')" />
                        <select id="certificate_required" name="certificate_required" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            <option value="1">{{ __('Required') }}</option>
                            <option value="0">{{ __('Not required') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <x-text-input id="notes" name="notes" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save override') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>
