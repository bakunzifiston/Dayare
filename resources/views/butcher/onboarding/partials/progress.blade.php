@php
    $percent = $progress['percent'] ?? 0;
@endphp

<div class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Onboarding progress') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $percent }}%</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __(':done of :total steps complete', ['done' => $progress['completed_steps'] ?? 0, 'total' => $progress['total_steps'] ?? 0]) }}
            </p>
        </div>
        <a href="{{ route('butcher.onboarding.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">
            {{ __('View checklist') }}
        </a>
    </div>
    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full bg-bucha-primary transition-all" style="width: {{ $percent }}%"></div>
    </div>
</div>
