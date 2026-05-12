@props(['events' => collect(), 'emptyMessage' => null])

@if ($events->isEmpty())
    <p class="text-sm text-slate-500">{{ $emptyMessage ?: __('No health events recorded yet.') }}</p>
@else
    <ol class="space-y-4">
        @foreach ($events as $event)
            <li class="rounded-bucha border border-slate-200 bg-slate-50/60 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $event['type']) }}</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $event['label'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $event['code'] }}</p>
                    </div>
                    <div class="text-right text-sm text-slate-600">
                        <p>{{ $event['date']?->toDateString() }}</p>
                        <x-health-status-badge :status="$event['status']" class="mt-2" />
                    </div>
                </div>
                <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">{{ __('Veterinarian') }}</dt>
                        <dd class="text-slate-800">{{ $event['veterinarian'] ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">{{ __('Notes') }}</dt>
                        <dd class="text-slate-800">{{ $event['notes'] ?: '—' }}</dd>
                    </div>
                </dl>
                @if (! empty($event['route']))
                    <a href="{{ $event['route'] }}" class="mt-3 inline-flex text-sm font-medium text-bucha-primary hover:underline">{{ __('Open record') }}</a>
                @endif
            </li>
        @endforeach
    </ol>
@endif
