<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Hygiene log') }} — {{ $log->log_date?->toDateString() }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $log->outlet?->name }}</p>
            </div>
            <x-butcher.status-badge :status="$log->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <p class="text-sm text-slate-500">{{ __('Signed by') }} <span class="font-medium text-slate-900">{{ $log->signedByUser?->name }}</span></p>

                <h3 class="mt-6 text-sm font-semibold text-slate-900">{{ __('Checklist') }}</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($checklistKeys as $key => $label)
                        <li class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <span>{{ __($label) }}</span>
                            @if ($log->checklist[$key] ?? false)
                                <span class="text-emerald-700 font-semibold">{{ __('Pass') }}</span>
                            @else
                                <span class="text-red-700 font-semibold">{{ __('Fail') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($log->issues_found)
                    <div class="mt-6">
                        <h4 class="text-xs font-semibold uppercase text-slate-500">{{ __('Issues found') }}</h4>
                        <p class="mt-1 text-sm text-slate-700">{{ $log->issues_found }}</p>
                    </div>
                @endif

                @if ($log->corrective_action)
                    <div class="mt-4">
                        <h4 class="text-xs font-semibold uppercase text-slate-500">{{ __('Corrective action') }}</h4>
                        <p class="mt-1 text-sm text-slate-700">{{ $log->corrective_action }}</p>
                    </div>
                @endif
            </section>

            <a href="{{ route('butcher.compliance.hygiene.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back to hygiene logs') }}</a>
        </div>
    </div>
</x-app-layout>
