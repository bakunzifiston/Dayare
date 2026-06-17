@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $session->session_number }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $session->batch?->batch_number }} · {{ $session->outlet?->name }} · {{ $session->session_date?->toDateString() }}
                </p>
            </div>
            <x-butcher.status-badge :status="$session->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Source (kg)')" :value="$fmtKg($session->source_weight_kg)" />
                <x-kpi-card stat :title="__('Cuts (kg)')" :value="$fmtKg($wastage['total_cuts_weight_kg'])" />
                <x-kpi-card stat :title="__('Wastage (kg)')" :value="$fmtKg($wastage['wastage_kg'] ?? 0)" />
                <x-kpi-card stat :title="__('Wastage %')" :value="number_format((float) ($wastage['wastage_pct'] ?? 0), 1).'%'" />
            </div>

            @if ($session->isOpen())
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Add cut output') }}</h3>
                    @if ($cutTypes->isEmpty())
                        <p class="mt-3 text-sm text-slate-500">
                            {{ __('Define cut types before recording outputs.') }}
                            <a href="{{ route('butcher.cutting.types.index') }}" class="font-semibold text-bucha-primary hover:underline">{{ __('Cut type catalog') }}</a>
                        </p>
                    @else
                        <form method="post" action="{{ route('butcher.cutting.sessions.outputs.store', $session) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            @csrf
                            <div>
                                <label for="cut_type_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Cut type') }}</label>
                                <select id="cut_type_id" name="cut_type_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                    @foreach ($cutTypes as $cutType)
                                        <option value="{{ $cutType->id }}" @selected(old('cut_type_id') == $cutType->id)>{{ $cutType->name }} ({{ ucfirst($cutType->meat_type) }})</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('cut_type_id')" class="mt-1" />
                            </div>
                            <div>
                                <label for="weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Weight (kg)') }}</label>
                                <input id="weight_kg" name="weight_kg" type="number" step="0.001" min="0.01" value="{{ old('weight_kg') }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                <x-input-error :messages="$errors->get('weight_kg')" class="mt-1" />
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record cut') }}</button>
                            </div>
                        </form>
                    @endif
                </section>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Cut outputs') }}</h3>
                    @if ($session->isOpen() && $session->cutOutputs->isNotEmpty())
                        <form method="post" action="{{ route('butcher.cutting.sessions.close', $session) }}" onsubmit="return confirm(@json(__('Close this session? Wastage will be calculated.')))">
                            @csrf
                            <button type="submit" class="rounded-bucha border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Close session') }}</button>
                        </form>
                    @endif
                </div>
                <table class="mt-4 min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4">{{ __('Cut') }}</th>
                            <th class="py-2 pr-4">{{ __('Weight (kg)') }}</th>
                            <th class="py-2 pr-4">{{ __('Unit cost/kg') }}</th>
                            <th class="py-2 pr-4">{{ __('Line value') }}</th>
                            <th class="py-2">{{ __('Label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($session->cutOutputs as $output)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium">{{ $output->cutType?->name }}</td>
                                <td class="py-3 pr-4">{{ $fmtKg($output->weight_kg) }}</td>
                                <td class="py-3 pr-4">{{ $fmtMoney($output->unit_cost_per_kg) }}</td>
                                <td class="py-3 pr-4">{{ $fmtMoney((float) $output->weight_kg * (float) $output->unit_cost_per_kg) }}</td>
                                <td class="py-3">
                                    <form method="post" action="{{ route('butcher.cutting.sessions.label', [$session, $output]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm font-semibold text-bucha-primary hover:underline">
                                            {{ $output->label_printed ? __('Reprint label') : __('Print label') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">{{ __('No cuts recorded yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
