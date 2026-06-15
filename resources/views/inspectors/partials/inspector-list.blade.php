@props([
    'inspectors',
    'showPagination' => true,
])

@if ($inspectors->isEmpty())
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
        <p class="mb-4">{{ __('No inspectors registered yet.') }}</p>
        <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
            {{ __('Register first inspector') }}
        </a>
    </div>
@else
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
        <ul class="divide-y divide-slate-100">
            @foreach ($inspectors as $inspector)
                <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                    <div>
                        <a href="{{ route('inspectors.show', $inspector) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                            {{ $inspector->full_name }}
                        </a>
                        <p class="text-sm text-slate-500">
                            {{ $inspector->national_id }} · {{ $inspector->email }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            @php
                                $toString = fn ($v) => is_array($v) ? implode(', ', $v) : (string) ($v ?? '');
                                $facName = $toString(optional($inspector->facility)->facility_name);
                                $authNum = $toString($inspector->authorization_number);
                                $stat = $toString($inspector->status);
                                $labelAssigned = $toString(__('Assigned to'));
                                $labelAuth = $toString(__('Auth'));
                            @endphp
                            {{ $labelAssigned }}: {{ $facName }} · {{ $labelAuth }}: {{ $authNum }}
                            @if ($inspector->isAuthorizationExpired())
                                @php $expiredLabel = $toString(__('(Expired)')); @endphp
                                <span class="text-red-600">{{ $expiredLabel }}</span>
                            @endif
                            · {{ ucfirst($stat) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('inspectors.show', $inspector) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('View') }}</a>
                        <a href="{{ route('inspectors.edit', $inspector) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($showPagination)
            <div class="px-4 py-3 border-t border-slate-100">{{ $inspectors->links() }}</div>
        @endif
    </div>
@endif
