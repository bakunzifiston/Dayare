<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Edit movement permit') }}</h2></x-slot>
    <div class="max-w-6xl space-y-6">
        @include('farmer.movement.partials.nav')
        <form method="POST" action="{{ route('farmer.movement.permits.update', $permit) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('farmer.movement.permits.partials.form', ['permit' => $permit, 'farms' => $farms, 'animals' => $animals])
            <div class="flex gap-3">
                <x-primary-button>{{ __('Update permit') }}</x-primary-button>
                <a href="{{ route('farmer.movement.permits.show', $permit) }}" class="text-sm text-slate-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
