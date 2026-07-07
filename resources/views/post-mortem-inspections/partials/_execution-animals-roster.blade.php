<ul id="execution-animals-list" class="mt-3 flex flex-wrap gap-2">
    @foreach ($executionAnimals as $animal)
        @php($animalIsInspected = in_array((int) $animal['animal_intake_item_id'], $inspectedAnimalIds, true))
        <li @class([
            'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium',
            'border-slate-200 bg-slate-100 text-slate-500' => $animalIsInspected,
            'border-emerald-200 bg-emerald-50 text-emerald-800' => ! $animalIsInspected,
        ])>
            <span class="font-mono">{{ $animal['ear_tag'] }}</span>
            <span class="text-[10px] uppercase tracking-wide">
                @if ($animalIsInspected)
                    {{ __('Inspected') }}
                @else
                    {{ __('Pending') }}
                @endif
            </span>
        </li>
    @endforeach
</ul>
