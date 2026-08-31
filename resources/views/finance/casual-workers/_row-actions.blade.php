@php
    $worker = $worker ?? null;
    $actionClass = 'inline-flex h-8 items-center gap-1.5 px-2.5 text-xs font-medium transition';
@endphp
@if ($worker)
    <div class="inline-flex divide-x divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <a href="{{ route('finance.casual-workers.edit', $worker) }}" class="{{ $actionClass }} text-slate-700 hover:bg-slate-50">
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            {{ __('Edit') }}
        </a>
        <form method="POST" action="{{ route('finance.casual-workers.destroy', $worker) }}" class="flex" onsubmit="return confirm(@js(__('Delete this casual worker?')))">
            @csrf
            @method('DELETE')
            <button type="submit" class="{{ $actionClass }} text-red-700 hover:bg-red-50">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10"/></svg>
                {{ __('Delete') }}
            </button>
        </form>
    </div>
@endif
