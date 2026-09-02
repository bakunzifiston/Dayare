@php
    $selected = old($name ?? 'assignee', $selected ?? '');
@endphp
<select id="{{ $name ?? 'assignee' }}" name="{{ $name ?? 'assignee' }}" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" {{ ($required ?? true) ? 'required' : '' }}>
    <option value="">{{ __('Select inspector') }}</option>
    @if (($inspectors ?? collect())->isNotEmpty())
        <optgroup label="{{ __('Inspectors') }}">
            @foreach ($inspectors as $inspector)
                <option value="inspector:{{ $inspector->id }}" @selected($selected === 'inspector:'.$inspector->id)>{{ $inspector->full_name }}</option>
            @endforeach
        </optgroup>
    @endif
    @if (($inspectorUsers ?? collect())->isNotEmpty())
        <optgroup label="{{ __('Staff with inspector role') }}">
            @foreach ($inspectorUsers as $user)
                <option value="user:{{ $user->id }}" @selected($selected === 'user:'.$user->id)>{{ $user->name }}</option>
            @endforeach
        </optgroup>
    @endif
</select>
