<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800">{{ __('Health certificates') }}</h2>
            <a href="{{ route('farmer.health-certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">
                {{ __('Add certificate') }}
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
                        <th class="px-4 py-2">{{ __('Certificate #') }}</th>
                        <th class="px-4 py-2">{{ __('Type') }}</th>
                        <th class="px-4 py-2">{{ __('Farm') }}</th>
                        <th class="px-4 py-2">{{ __('Issue date') }}</th>
                        <th class="px-4 py-2">{{ __('Expiry date') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($certificates as $cert)
                        @php
                            $isValid = $cert->isValidOn(now());
                        @endphp
                        <tr>
                            <td class="px-4 py-2 font-medium text-slate-900">{{ $cert->certificate_number }}</td>
                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $cert->certificate_type) }}</td>
                            <td class="px-4 py-2">{{ $cert->farm?->name }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $cert->issue_date?->toDateString() }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $cert->expiry_date?->toDateString() ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $isValid ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900' }}">
                                    {{ $isValid ? __('Valid') : __('Expired') }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('farmer.health-certificates.show', $cert) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No certificates yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $certificates->links() }}</div>
    </div>
</x-app-layout>

