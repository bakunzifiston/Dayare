<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Batches') }}
            </h2>
            <a href="{{ route('batches.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Create batch') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total batches') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Approved') }}" :value="$kpis['approved']" color="green" />
                <x-kpi-card inline title="{{ __('Pending') }}" :value="$kpis['pending']" color="amber" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($batches->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No batches yet.') }}</p>
                    <a href="{{ route('batches.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Create first batch') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($batches as $batch)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('batches.show', $batch) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                        {{ $batch->batch_code }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ $batch->species }} · {{ $batch->quantity }} {{ __('carcasses') }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ __('Execution') }} {{ $batch->slaughterExecution->slaughter_time->format('d M Y H:i') }} · {{ $batch->inspector->full_name }} · {{ ucfirst($batch->status) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('batches.show', $batch) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('batches.edit', $batch) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $batches->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
