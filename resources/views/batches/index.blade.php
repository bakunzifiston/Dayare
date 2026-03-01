<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Batches') }}
            </h2>
            <a href="{{ route('batches.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Create batch') }}
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

            @if ($batches->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('No batches yet.') }}</p>
                    <a href="{{ route('batches.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Create first batch') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($batches as $batch)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('batches.show', $batch) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $batch->batch_code }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $batch->species }} · {{ $batch->quantity }} {{ __('carcasses') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ __('Execution') }} {{ $batch->slaughterExecution->slaughter_time->format('d M Y H:i') }} · {{ $batch->inspector->full_name }} · {{ ucfirst($batch->status) }}
                                    </p>
                                </div>
                                <a href="{{ route('batches.edit', $batch) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $batches->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
