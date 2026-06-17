<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Butcher onboarding') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Complete your shop setup before using the workspace.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('butcher.onboarding.partials.progress', ['progress' => $progress])

            <div class="rounded-bucha border border-slate-200/80 bg-white shadow-bucha divide-y divide-slate-100">
                @foreach ($progress['steps'] as $step)
                    <div class="flex items-center justify-between gap-4 p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span @class([
                                'mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold',
                                'bg-bucha-primary text-white' => $step['complete'],
                                'bg-slate-200 text-slate-600' => ! $step['complete'],
                            ])>
                                {{ $step['complete'] ? '✓' : '•' }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $step['label'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $step['complete'] ? __('Complete') : __('Required') }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route($step['route']) }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            {{ $step['complete'] ? __('Review') : __('Start') }}
                        </a>
                    </div>
                @endforeach
            </div>

            @if (($progress['percent'] ?? 0) === 100)
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ __('Onboarding complete. You can now use your butcher workspace.') }}
                    <a href="{{ route('butcher.dashboard') }}" class="ml-1 font-semibold underline">{{ __('Go to dashboard') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
