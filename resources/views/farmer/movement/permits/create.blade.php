<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Create movement permit') }}</h2></x-slot>
    <div class="max-w-6xl space-y-6">
        @include('farmer.movement.partials.nav')

        <section class="rounded-bucha border border-emerald-200 bg-emerald-50/60 p-6 shadow-sm space-y-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">{{ __('Upload Rwanda movement permit (PDF)') }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ __('Import an official RAB permit (URUHUSHYA RWO KWIMURA AMATUNGO). Permit number, owner, route, transport, dates, and all listed animals are extracted automatically.') }}</p>
            </div>
            <form method="POST" action="{{ route('farmer.movement.permits.import-pdf') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="import_source_farm_id" :value="__('Link to your farm')" />
                        <select id="import_source_farm_id" name="source_farm_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                            <option value="">{{ __('Select farm') }}</option>
                            @foreach ($farms as $farm)
                                <option value="{{ $farm->id }}" @selected((int) old('source_farm_id') === $farm->id)>{{ $farm->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('source_farm_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="permit_pdf" :value="__('Permit PDF')" />
                        <input id="permit_pdf" name="permit_pdf" type="file" accept="application/pdf,.pdf" class="mt-1 block w-full text-sm text-slate-600" required />
                        <x-input-error :messages="$errors->get('permit_pdf')" class="mt-2" />
                    </div>
                </div>
                <x-primary-button>{{ __('Import permit from PDF') }}</x-primary-button>
            </form>
        </section>

        <div class="relative">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-slate-50 px-3 text-slate-500">{{ __('or enter manually') }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('farmer.movement.permits.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('farmer.movement.permits.partials.form', compact('farms', 'animals'))
            <div class="flex gap-3">
                <x-primary-button>{{ __('Save permit') }}</x-primary-button>
                <a href="{{ route('farmer.movement.permits.index') }}" class="text-sm text-slate-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
