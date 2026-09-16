<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.compliance.hygiene.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Hygiene') }}
                </a>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100">
                        <i class="ti ti-clipboard-check text-lg leading-none" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Hygiene log') }}</h3>
                        <p class="text-sm text-slate-500">{{ __('Signed by') }} <span class="font-medium text-slate-700">{{ $log->signedByUser?->name }}</span></p>
                    </div>
                </div>

                <div class="px-4 py-5 sm:px-5 space-y-5">
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Checklist') }}</h4>
                        <ul class="mt-3 space-y-2">
                            @foreach ($checklistKeys as $key => $label)
                                <li class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2 text-sm">
                                    <span class="text-slate-800">{{ __($label) }}</span>
                                    @if ($log->checklist[$key] ?? false)
                                        <span class="font-semibold text-emerald-700">{{ __('Pass') }}</span>
                                    @else
                                        <span class="font-semibold text-red-700">{{ __('Fail') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @if ($log->issues_found)
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issues found') }}</h4>
                            <p class="mt-1 text-sm text-slate-700">{{ $log->issues_found }}</p>
                        </div>
                    @endif

                    @if ($log->corrective_action)
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Corrective action') }}</h4>
                            <p class="mt-1 text-sm text-slate-700">{{ $log->corrective_action }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
