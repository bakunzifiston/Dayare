<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Businesses') }}
            </h2>
            <a href="{{ route('businesses.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Register Business') }}
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

            @if ($businesses->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('You have not registered any business yet.') }}</p>
                    <a href="{{ route('businesses.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Register your first business') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($businesses as $business)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('businesses.show', $business) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $business->business_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $business->registration_number }} · {{ $business->email }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $business->facilities_count }} {{ __('facility(ies)') }} · {{ ucfirst($business->status) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('businesses.facilities.index', $business) }}" class="text-sm text-gray-600 hover:text-gray-900">
                                        {{ __('Facilities') }}
                                    </a>
                                    <a href="{{ route('businesses.edit', $business) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                                        {{ __('Edit') }}
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $businesses->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
