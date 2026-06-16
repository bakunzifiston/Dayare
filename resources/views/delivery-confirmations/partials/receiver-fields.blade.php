@props([
    'receiverName' => '',
    'receiverCountry' => '',
    'receiverAddress' => '',
    'lockedReceiverFields' => collect(),
])

@php
    $locked = collect($lockedReceiverFields);
@endphp

<div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-4">
    <div>
        <p class="text-sm font-medium text-slate-800">{{ __('Receiver (from transport destination)') }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ __('Must match the destination recorded on the selected transport trip.') }}</p>
    </div>

    <div id="receiver_name_field">
        <x-input-label for="receiver_name" :value="__('Receiver name')" />
        @if ($locked->contains('receiver_name'))
            <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $receiverName }}</p>
            <input type="hidden" name="receiver_name" id="receiver_name" value="{{ $receiverName }}">
            <p class="mt-1 text-xs text-emerald-700">{{ __('From transport trip — edit the trip if this must change.') }}</p>
        @else
            <x-text-input id="receiver_name" name="receiver_name" type="text" class="mt-1 block w-full" :value="$receiverName" required />
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('receiver_name')" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div id="receiver_country_field">
            <x-input-label for="receiver_country" :value="__('Receiver country (optional)')" />
            @if ($locked->contains('receiver_country'))
                <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $receiverCountry ?: '—' }}</p>
                <input type="hidden" name="receiver_country" id="receiver_country" value="{{ $receiverCountry }}">
                <p class="mt-1 text-xs text-emerald-700">{{ __('From transport trip') }}</p>
            @else
                <x-text-input id="receiver_country" name="receiver_country" type="text" class="mt-1 block w-full" :value="$receiverCountry" />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('receiver_country')" />
        </div>
        <div id="receiver_address_field">
            <x-input-label for="receiver_address" :value="__('Receiver address (optional)')" />
            @if ($locked->contains('receiver_address'))
                <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $receiverAddress ?: '—' }}</p>
                <input type="hidden" name="receiver_address" id="receiver_address" value="{{ $receiverAddress }}">
                <p class="mt-1 text-xs text-emerald-700">{{ __('From transport trip') }}</p>
            @else
                <x-text-input id="receiver_address" name="receiver_address" type="text" class="mt-1 block w-full" :value="$receiverAddress" />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('receiver_address')" />
        </div>
    </div>
</div>
