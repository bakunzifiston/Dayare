@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.inventory.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Inventory') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-trash-x text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Disposals') }}</h2>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('butcher.inventory.disposals.store') }}" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm" onsubmit="return confirm(@js(__('Record this disposal? Stock will be permanently reduced.')))">
                @csrf
                <div class="space-y-4 p-4 sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Record disposal') }}</h3>
                    <div>
                        <label for="batch_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Batch') }}</label>
                        <select id="batch_id" name="batch_id" required class="{{ $fieldClass }}">
                            <option value="">{{ __('Select batch') }}</option>
                            @foreach ($activeBatches as $batch)
                                <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>
                                    {{ $batch->batch_number }} — {{ number_format((float) $batch->remaining_weight_kg, 2) }} kg left
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('batch_id')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="weight_disposed_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Weight disposed (kg)') }}</label>
                            <input id="weight_disposed_kg" name="weight_disposed_kg" type="number" step="0.001" min="0.1" value="{{ old('weight_disposed_kg') }}" required class="{{ $fieldClass }}">
                            <x-input-error :messages="$errors->get('weight_disposed_kg')" class="mt-2" />
                        </div>
                        <div>
                            <label for="reason" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Reason') }}</label>
                            <select id="reason" name="reason" required class="{{ $fieldClass }}">
                                @foreach (\App\Models\ButcherDisposalLog::REASONS as $reason)
                                    <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ ucfirst($reason) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes (optional)') }}</label>
                        <textarea id="notes" name="notes" rows="2" class="{{ $fieldClass }}">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Record disposal') }}
                    </button>
                </div>
            </form>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Disposal history') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Disposal') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Weight') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($disposals as $disposal)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-trash-x text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $disposal->batch?->batch_number }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $disposal->disposed_at?->format('Y-m-d H:i') }} · {{ ucfirst($disposal->reason) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right tabular-nums font-medium text-slate-900">{{ number_format((float) $disposal->weight_disposed_kg, 2) }} kg</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $disposal->disposedByUser?->name }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-10 text-center text-slate-500">{{ __('No disposals recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($disposals->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $disposals->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
