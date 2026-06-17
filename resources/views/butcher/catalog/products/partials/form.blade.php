<form method="post" action="{{ $action }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Product name') }}</label>
        <input id="name" name="name" type="text" value="{{ old('name', $product?->name) }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <label for="cut_type_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Linked cut type') }}</label>
        <select id="cut_type_id" name="cut_type_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
            <option value="">{{ __('None — manual cost') }}</option>
            @foreach ($cutTypes as $cutType)
                <option value="{{ $cutType->id }}" @selected(old('cut_type_id', $product?->cut_type_id) == $cutType->id)>{{ $cutType->name }} ({{ ucfirst($cutType->meat_type) }})</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">{{ __('Cost auto-updates from closed cutting sessions (last 30 days).') }}</p>
        <x-input-error :messages="$errors->get('cut_type_id')" class="mt-1" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="meat_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat type') }}</label>
            <select id="meat_type" name="meat_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                @foreach ($meatTypes as $type)
                    <option value="{{ $type }}" @selected(old('meat_type', $product?->meat_type) === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('meat_type')" class="mt-1" />
        </div>
        <div>
            <label for="unit" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Unit') }}</label>
            <select id="unit" name="unit" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                @foreach ($units as $unit)
                    <option value="{{ $unit }}" @selected(old('unit', $product?->unit ?? 'per_kg') === $unit)>{{ str_replace('_', ' ', ucfirst($unit)) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('unit')" class="mt-1" />
        </div>
    </div>

    <div>
        <label for="default_price" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Default price (RWF)') }}</label>
        <input id="default_price" name="default_price" type="number" step="1" min="0" value="{{ old('default_price', $product?->default_price) }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
        <x-input-error :messages="$errors->get('default_price')" class="mt-1" />
    </div>

    <div class="flex items-center gap-2">
        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product?->is_active ?? true)) class="rounded border-gray-300 text-bucha-primary">
        <label for="is_active" class="text-sm text-slate-700">{{ __('Active for sale') }}</label>
    </div>

    <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
        {{ $product ? __('Save changes') : __('Create product') }}
    </button>
</form>
