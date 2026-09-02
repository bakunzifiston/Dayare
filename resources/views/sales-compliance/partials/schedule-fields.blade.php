<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="site_id" :value="__('Site')" />
        <select id="site_id" name="site_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
            <option value="">{{ __('Select site') }}</option>
            @foreach ($sites as $siteOption)
                <option value="{{ $siteOption->id }}" @selected((string) old('site_id', $selectedSiteId ?? $inspection?->site_id) === (string) $siteOption->id)>
                    {{ $siteOption->name }} · {{ $siteOption->siteTypeLabel() }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('site_id')" />
    </div>
    <div>
        <x-input-label for="scheduled_date" :value="__('Scheduled date')" />
        <x-text-input id="scheduled_date" name="scheduled_date" type="date" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('scheduled_date', optional($inspection?->scheduled_date)?->toDateString() ?? now()->addDay()->toDateString())" required />
        <x-input-error class="mt-2" :messages="$errors->get('scheduled_date')" />
    </div>
    <div>
        <x-input-label for="scheduled_time" :value="__('Scheduled time')" />
        <x-text-input id="scheduled_time" name="scheduled_time" type="time" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('scheduled_time', isset($inspection) ? $inspection->scheduledTimeDisplay() : '09:00')" required />
        <x-input-error class="mt-2" :messages="$errors->get('scheduled_time')" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="assignee" :value="__('Assigned inspector')" />
        @include('sales-compliance.partials.assignee-select', [
            'inspectors' => $inspectors,
            'inspectorUsers' => $inspectorUsers,
            'selected' => old('assignee', isset($inspection) ? $inspection->assigneeValue() : ''),
        ])
        <x-input-error class="mt-2" :messages="$errors->get('assignee')" />
    </div>
</div>
