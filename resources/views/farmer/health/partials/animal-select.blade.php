@props(['animals', 'selectedAnimalId' => 0, 'required' => true, 'disabled' => false])

<div>
    <x-input-label for="animal_id" :value="__('Animal')" />
    <select
        id="animal_id"
        name="animal_id"
        class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
        @disabled($disabled)
        @required($required && ! $disabled)
    >
        <option value="">{{ __('Select animal') }}</option>
        @foreach ($animals as $animal)
            <option value="{{ $animal->id }}" @selected((int) old('animal_id', $selectedAnimalId) === $animal->id)>
                {{ $animal->selectionLabel() }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('animal_id')" class="mt-2" />
</div>
