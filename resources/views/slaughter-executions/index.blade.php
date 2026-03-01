<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Slaughter execution') }}
            </h2>
            <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Record execution') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($executions->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('No slaughter executions recorded yet.') }}</p>
                    <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Record first execution') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($executions as $execution)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('slaughter-executions.show', $execution) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $execution->slaughter_time->format('d M Y H:i') }} — {{ $execution->slaughterPlan->facility->facility_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ __('Session') }} #{{ $execution->slaughter_plan_id }} · {{ $execution->actual_animals_slaughtered }} {{ __('animals slaughtered') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ ucfirst(str_replace('_', ' ', $execution->status)) }}
                                    </p>
                                </div>
                                <a href="{{ route('slaughter-executions.edit', $execution) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $executions->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
