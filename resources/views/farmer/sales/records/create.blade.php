<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Create sale') }}</h2></x-slot>
    <div class="max-w-6xl space-y-6">@include('farmer.sales.partials.nav')<form method="POST" action="{{ route('farmer.sales.records.store') }}" enctype="multipart/form-data" class="space-y-6">@csrf @include('farmer.sales.records.partials.form', compact('farms', 'buyers', 'animals', 'livestock', 'permits'))<div class="flex gap-3"><x-primary-button>{{ __('Save sale') }}</x-primary-button><a href="{{ route('farmer.sales.records.index') }}" class="text-sm text-slate-600 hover:underline">{{ __('Cancel') }}</a></div></form></div>
</x-app-layout>
