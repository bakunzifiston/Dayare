<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Edit buyer') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">
        @include('farmer.sales.partials.nav')
        <form method="POST" action="{{ route('farmer.sales.buyers.update', $buyer) }}" class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            @csrf
            @method('PUT')
            @include('farmer.sales.buyers.partials.form')
            <div class="flex gap-3">
                <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                <a href="{{ route('farmer.sales.buyers.show', $buyer) }}" class="text-sm text-slate-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
