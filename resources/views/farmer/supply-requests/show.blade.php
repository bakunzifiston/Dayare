<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.supply-requests.index') }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Requests') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Supply request') }} #{{ $supplyRequest->id }}</h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-2 text-sm">
            <p><span class="text-slate-500">{{ __('Processor') }}:</span> {{ $supplyRequest->processor?->business_name }}</p>
            <p><span class="text-slate-500">{{ __('Destination facility') }}:</span> {{ $supplyRequest->destinationFacility?->facility_name }}</p>
            <p><span class="text-slate-500">{{ __('Animal type') }}:</span> {{ ucfirst($supplyRequest->animal_type) }}</p>
            <p><span class="text-slate-500">{{ __('Quantity') }}:</span> {{ $supplyRequest->quantity_requested }}</p>
            <p><span class="text-slate-500">{{ __('Preferred date') }}:</span> {{ $supplyRequest->preferred_date?->toDateString() ?? '—' }}</p>
            <p><span class="text-slate-500">{{ __('Status') }}:</span> {{ ucfirst($supplyRequest->status) }}</p>
        </div>

        @if ($supplyRequest->isPending())
            @if ($farmOptions->isEmpty())
                <div class="rounded-bucha border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('Create a farm and add livestock of this type before you can accept this request.') }}
                    <a href="{{ route('farmer.farms.create') }}" class="font-medium text-bucha-primary hover:underline">{{ __('Add farm') }}</a>
                </div>
            @endif
            <div class="rounded-bucha border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                {{ __('Fulfilment only draws from healthy animals. Total, healthy, and available quantities are reduced by the requested amount.') }}
            </div>
            <div class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-4">
                <h3 class="font-semibold text-slate-800">{{ __('Respond') }}</h3>
                <x-input-error :messages="$errors->get('farm_id')" class="mt-0" />
                <x-input-error :messages="$errors->get('quantity')" class="mt-0" />
                <x-input-error :messages="$errors->get('animal_type')" class="mt-0" />
                <x-input-error :messages="$errors->get('supply_request')" class="mt-0" />

                <form method="post" action="{{ route('farmer.supply-requests.accept', $supplyRequest) }}" class="space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="farm_id" :value="__('Source farm (stock will be deducted here)')" />
                        <select name="farm_id" id="farm_id" @if($farmOptions->isEmpty()) disabled @else required @endif class="mt-1 block w-full rounded-lg border-gray-300">
                            @foreach ($farmOptions as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" @if($farmOptions->isEmpty()) disabled @endif class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha disabled:opacity-50">{{ __('Accept & fulfil') }}</button>
                </form>

                <form method="post" action="{{ route('farmer.supply-requests.reject', $supplyRequest) }}" onsubmit="return confirm('{{ __('Reject this request?') }}');">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Reject') }}</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
