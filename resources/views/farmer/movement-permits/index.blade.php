<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800">{{ __('Movement permits') }}</h2>
            <a href="{{ route('farmer.movement-permits.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">
                {{ __('Upload permit') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-4">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-2">{{ __('Permit number') }}</th>
                        <th class="px-4 py-2">{{ __('Source farm') }}</th>
                        <th class="px-4 py-2">{{ __('Issue date') }}</th>
                        <th class="px-4 py-2">{{ __('Expiry date') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($permits as $permit)
                        @php
                            $isValid = $permit->expiry_date && $permit->issue_date && $permit->issue_date->lte(now()) && $permit->expiry_date->gte(now());
                        @endphp
                        <tr>
                            <td class="px-4 py-2 font-medium text-slate-900">{{ $permit->permit_number }}</td>
                            <td class="px-4 py-2">{{ $permit->sourceFarm?->name }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $permit->issue_date?->toDateString() }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $permit->expiry_date?->toDateString() }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $isValid ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900' }}">
                                    {{ $isValid ? __('Valid') : __('Expired') }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('farmer.movement-permits.show', $permit) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No movement permits uploaded yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $permits->links() }}</div>
    </div>
</x-app-layout>

