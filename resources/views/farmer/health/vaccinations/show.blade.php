<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800">{{ __('Vaccination record') }}</h2>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        @include('farmer.health.partials.nav')

        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ $record->vaccination_code }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $record->vaccine_name }}</h3>
                </div>
                <x-health-status-badge :status="$record->status" />
            </div>
            <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                <div><dt class="text-slate-500">{{ __('Animal') }}</dt><dd class="mt-1 font-medium">{{ $record->animal?->animal_code }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Farm') }}</dt><dd class="mt-1 font-medium">{{ $record->animal?->livestock?->farm?->name }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Vaccination date') }}</dt><dd class="mt-1 font-medium">{{ $record->vaccination_date?->toDateString() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Next due date') }}</dt><dd class="mt-1 font-medium">{{ $record->next_due_date?->toDateString() ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Veterinarian') }}</dt><dd class="mt-1 font-medium">{{ $record->veterinarian_name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Clinic') }}</dt><dd class="mt-1 font-medium">{{ $record->veterinary_clinic ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1">{{ $record->notes ?: '—' }}</dd></div>
            </dl>
            @if ($record->attachmentUrl())
                <a href="{{ $record->attachmentUrl() }}" target="_blank" class="mt-4 inline-flex text-bucha-primary hover:underline">{{ __('Open attachment') }}</a>
            @endif
        </section>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('farmer.health.vaccinations.edit', $record) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Edit') }}</a>
            <form method="post" action="{{ route('farmer.health.vaccinations.destroy', $record) }}" onsubmit="return confirm('{{ __('Archive this vaccination record?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded-bucha border border-red-200 px-4 py-2 text-sm text-red-700">{{ __('Archive') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
