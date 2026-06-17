<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.storage.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold storage') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Disposal log') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('butcher.storage.disposals.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Record disposal') }}</h3>

                <div>
                    <x-input-label for="batch_id" :value="__('Batch')" />
                    <select id="batch_id" name="batch_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
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
                        <x-input-label for="weight_disposed_kg" :value="__('Weight disposed (kg)')" />
                        <x-text-input id="weight_disposed_kg" name="weight_disposed_kg" type="number" step="0.001" min="0.1" class="mt-1 block w-full" :value="old('weight_disposed_kg')" required />
                        <x-input-error :messages="$errors->get('weight_disposed_kg')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="reason" :value="__('Reason')" />
                        <select id="reason" name="reason" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach (\App\Models\ButcherDisposalLog::REASONS as $reason)
                                <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ ucfirst($reason) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record disposal') }}</button>
                </div>
            </form>

            <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Batch') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Weight') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Reason') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('By') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($disposals as $disposal)
                            <tr>
                                <td class="px-4 py-3">{{ $disposal->disposed_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">{{ $disposal->batch?->batch_number }}</td>
                                <td class="px-4 py-3">{{ number_format((float) $disposal->weight_disposed_kg, 2) }} kg</td>
                                <td class="px-4 py-3 capitalize">{{ $disposal->reason }}</td>
                                <td class="px-4 py-3">{{ $disposal->disposedByUser?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No disposals recorded.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $disposals->links() }}</div>
        </div>
    </div>
</x-app-layout>
