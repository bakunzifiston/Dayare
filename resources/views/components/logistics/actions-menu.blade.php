@props(['actions' => []])

<div class="relative" x-data="{ open: false }">
    <button
        type="button"
        class="rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50"
        @click="open = !open"
        @click.away="open = false"
    >
        {{ __('Actions') }}
    </button>
    <div
        x-show="open"
        x-transition
        class="absolute right-0 z-10 mt-1 w-44 rounded-md border border-slate-200 bg-white p-1 shadow-lg"
    >
        @foreach ($actions as $action)
            @if (($action['method'] ?? 'GET') === 'POST')
                <form method="POST" action="{{ $action['href'] }}" class="block">
                    @csrf
                    <button type="submit" class="block w-full rounded px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50">
                        {{ $action['label'] }}
                    </button>
                </form>
            @else
                <a href="{{ $action['href'] }}" class="block rounded px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                    {{ $action['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
