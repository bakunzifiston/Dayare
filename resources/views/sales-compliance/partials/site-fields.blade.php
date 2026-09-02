@php
    $site = $site ?? null;
    $type = old('site_type', $site?->site_type ?? \App\Support\SalesComplianceCatalog::SITE_RESTAURANT);
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="site_type" :value="__('Site type')" />
        <select id="site_type" name="site_type" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
            @foreach (\App\Support\SalesComplianceCatalog::siteTypeLabels() as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('site_type')" />
    </div>
    <div>
        <x-input-label for="name" :value="__('Site name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('name', $site?->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="location_address" :value="__('Site location')" />
        <x-text-input id="location_address" name="location_address" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('location_address', $site?->location_address)" required />
        <p class="mt-1 text-xs text-slate-500">{{ __('Street address. Optional coordinates can be added below.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('location_address')" />
    </div>
    <div>
        <x-input-label for="latitude" :value="__('Latitude')" />
        <x-text-input id="latitude" name="latitude" type="number" step="0.0000001" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('latitude', $site?->latitude)" />
        <x-input-error class="mt-2" :messages="$errors->get('latitude')" />
    </div>
    <div>
        <x-input-label for="longitude" :value="__('Longitude')" />
        <x-text-input id="longitude" name="longitude" type="number" step="0.0000001" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('longitude', $site?->longitude)" />
        <x-input-error class="mt-2" :messages="$errors->get('longitude')" />
    </div>
</div>

<div id="event-fields" class="mt-4 grid gap-4 sm:grid-cols-2 {{ $type === \App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT ? '' : 'hidden' }}">
    <div>
        <x-input-label for="event_type" :value="__('Event type')" />
        <select id="event_type" name="event_type" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
            <option value="">{{ __('Select event type') }}</option>
            @foreach (['wedding' => __('Wedding'), 'private_party' => __('Private party'), 'funeral' => __('Funeral'), 'corporate' => __('Corporate'), 'other' => __('Other')] as $value => $label)
                <option value="{{ $value }}" @selected(old('event_type', $site?->event_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('event_type')" />
    </div>
    <div>
        <x-input-label for="event_name" :value="__('Event name')" />
        <x-text-input id="event_name" name="event_name" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('event_name', $site?->event_name)" placeholder="{{ __('e.g. Uwase wedding') }}" />
        <x-input-error class="mt-2" :messages="$errors->get('event_name')" />
    </div>
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-3">
    <div>
        <x-input-label for="contact_name" :value="__('Contact person')" />
        <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('contact_name', $site?->contact_name)" />
        <p id="contact-hint" class="mt-1 text-xs text-slate-500">{{ __('Required for restaurants, bars, and butcheries.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('contact_name')" />
    </div>
    <div>
        <x-input-label for="contact_phone" :value="__('Phone')" />
        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('contact_phone', $site?->contact_phone)" />
        <x-input-error class="mt-2" :messages="$errors->get('contact_phone')" />
    </div>
    <div>
        <x-input-label for="contact_email" :value="__('Email')" />
        <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('contact_email', $site?->contact_email)" />
        <x-input-error class="mt-2" :messages="$errors->get('contact_email')" />
    </div>
</div>

<div class="mt-4 flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300 text-bucha-primary" @checked(old('is_active', $site?->is_active ?? true))>
    <x-input-label for="is_active" :value="__('Active site')" class="mb-0" />
</div>

<script>
    (function () {
        const type = document.getElementById('site_type');
        const eventFields = document.getElementById('event-fields');
        const contactHint = document.getElementById('contact-hint');
        const contactRequired = @json([\App\Support\SalesComplianceCatalog::SITE_RESTAURANT, \App\Support\SalesComplianceCatalog::SITE_BAR, \App\Support\SalesComplianceCatalog::SITE_BUTCHERY]);
        function sync() {
            const isEvent = type.value === @json(\App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT);
            eventFields.classList.toggle('hidden', !isEvent);
            contactHint.textContent = contactRequired.includes(type.value)
                ? @json(__('Required for restaurants, bars, and butcheries.'))
                : @json(__('Optional for private / individual events.'));
        }
        type.addEventListener('change', sync);
        sync();
    })();
</script>
