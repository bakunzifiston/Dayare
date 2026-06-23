@props([
    'action',
    'confirm',
])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm));" {{ $attributes }}>
    @csrf
    @method('DELETE')
    <button type="submit" class="entity-action entity-action--danger">
        {{ $slot }}
    </button>
</form>
