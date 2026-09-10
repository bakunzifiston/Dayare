@props([
    'banner' => ['show' => false, 'message' => null],
])

@if (! empty($banner['show']) && ! empty($banner['message']))
    <div
        x-data="{ open: true }"
        x-show="open"
        x-cloak
        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
        role="status"
    >
        <div class="flex items-start justify-between gap-3">
            <p>{{ $banner['message'] }}</p>
            <button type="button" @click="open = false" class="shrink-0 text-xs font-semibold uppercase tracking-wide text-amber-800 hover:underline">
                {{ __('Dismiss') }}
            </button>
        </div>
    </div>
@endif
