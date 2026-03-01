<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ante-mortem inspections') }}
            </h2>
            <a href="{{ route('ante-mortem-inspections.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Record inspection') }}
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

            @if ($inspections->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('No ante-mortem inspections recorded yet.') }}</p>
                    <a href="{{ route('ante-mortem-inspections.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Record first inspection') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($inspections as $inspection)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('ante-mortem-inspections.show', $inspection) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $inspection->inspection_date->format('d M Y') }} — {{ $inspection->slaughterPlan->facility->facility_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ __('Session') }} #{{ $inspection->slaughter_plan_id }} · {{ $inspection->species }} · {{ $inspection->number_examined }} {{ __('examined') }}, {{ $inspection->number_approved }} {{ __('approved') }}, {{ $inspection->number_rejected }} {{ __('rejected') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ __('Inspector') }}: {{ $inspection->inspector->full_name }}
                                    </p>
                                </div>
                                <a href="{{ route('ante-mortem-inspections.edit', $inspection) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $inspections->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
