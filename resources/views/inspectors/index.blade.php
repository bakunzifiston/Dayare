<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inspectors') }}
            </h2>
            <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Register Inspector') }}
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

            @if ($inspectors->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('No inspectors registered yet.') }}</p>
                    <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Register first inspector') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($inspectors as $inspector)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('inspectors.show', $inspector) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $inspector->full_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $inspector->national_id }} · {{ $inspector->email }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ __('Assigned to') }}: {{ $inspector->facility->facility_name }} · {{ __('Auth') }}: {{ $inspector->authorization_number }}
                                        @if ($inspector->isAuthorizationExpired())
                                            <span class="text-red-600">{{ __('(Expired)') }}</span>
                                        @endif
                                        · {{ ucfirst($inspector->status) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('inspectors.edit', $inspector) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $inspectors->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
