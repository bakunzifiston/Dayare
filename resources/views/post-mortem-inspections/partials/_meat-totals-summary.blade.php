@php
    $examinedTotal = (float) ($examinedTotal ?? 0);
    $carcassApprovedTotal = (float) ($carcassApprovedTotal ?? 0);
    $otherMeatApprovedTotal = (float) ($otherMeatApprovedTotal ?? 0);
    $condemnedTotal = (float) ($condemnedTotal ?? 0);
@endphp

<div id="per-animal-aggregate-summary" @class(['grid w-full grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3', 'hidden' => ! ($visible ?? true)])>
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-center sm:px-3">
        <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ __('Total examined meat') }}</p>
        <p class="text-lg font-semibold tabular-nums text-slate-900">
            <span id="pm-summary-examined">{{ number_format($examinedTotal, 2) }}</span>
            <span class="text-xs font-normal text-slate-500">kg</span>
        </p>
    </div>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-2 text-center sm:px-3">
        <p class="text-[10px] font-medium uppercase tracking-wide text-emerald-700">{{ __('Carcass meat approved') }}</p>
        <p class="text-lg font-semibold tabular-nums text-emerald-800">
            <span id="pm-summary-carcass">{{ number_format($carcassApprovedTotal, 2) }}</span>
            <span class="text-xs font-normal text-emerald-600">kg</span>
        </p>
    </div>
    <div class="rounded-lg border border-teal-200 bg-teal-50 px-2 py-2 text-center sm:px-3">
        <p class="text-[10px] font-medium uppercase tracking-wide text-teal-700">{{ __('Other meat approved') }}</p>
        <p class="text-lg font-semibold tabular-nums text-teal-800">
            <span id="pm-summary-other-meat">{{ number_format($otherMeatApprovedTotal, 2) }}</span>
            <span class="text-xs font-normal text-teal-600">kg</span>
        </p>
    </div>
    <div class="rounded-lg border border-red-200 bg-red-50 px-2 py-2 text-center sm:px-3">
        <p class="text-[10px] font-medium uppercase tracking-wide text-red-700">{{ __('Condemned meat') }}</p>
        <p class="text-lg font-semibold tabular-nums text-red-800">
            <span id="pm-summary-condemned">{{ number_format($condemnedTotal, 2) }}</span>
            <span class="text-xs font-normal text-red-600">kg</span>
        </p>
    </div>
</div>
