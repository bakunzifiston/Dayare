@props(['facilities' => [], 'filters' => []])

<form method="get" class="mb-6 rounded-xl border border-slate-200/60 bg-white p-4 shadow-sm grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
    <div>
        <label for="status" class="block text-xs font-medium text-slate-600">{{ __('Status') }}</label>
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
            <option value="">{{ __('All') }}</option>
            @foreach (\App\Models\TransportTrip::STATUSES as $s)
                <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="from" class="block text-xs font-medium text-slate-600">{{ __('From') }}</label>
        <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm" />
    </div>
    <div>
        <label for="to" class="block text-xs font-medium text-slate-600">{{ __('To') }}</label>
        <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm" />
    </div>
    <div>
        <label for="origin_facility_id" class="block text-xs font-medium text-slate-600">{{ __('Origin') }}</label>
        <select id="origin_facility_id" name="origin_facility_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
            <option value="">{{ __('All') }}</option>
            @foreach ($facilities as $f)
                <option value="{{ $f['id'] }}" @selected(($filters['origin_facility_id'] ?? '') == $f['id'])>{{ $f['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="destination_facility_id" class="block text-xs font-medium text-slate-600">{{ __('Destination') }}</label>
        <select id="destination_facility_id" name="destination_facility_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
            <option value="">{{ __('All') }}</option>
            @foreach ($facilities as $f)
                <option value="{{ $f['id'] }}" @selected(($filters['destination_facility_id'] ?? '') == $f['id'])>{{ $f['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2 lg:col-span-5 flex gap-2">
        <button type="submit" class="px-4 py-2 rounded-lg bg-bucha-primary text-white text-sm font-medium">{{ __('Filter') }}</button>
        <a href="{{ request()->url() }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">{{ __('Reset') }}</a>
    </div>
</form>
