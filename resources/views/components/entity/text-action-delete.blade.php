@props([
    'action',
    'confirm',
])

<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirm));">
    @csrf
    @method('DELETE')
    <button type="submit" class="profile-action profile-action--danger">{{ $slot }}</button>
</form>
