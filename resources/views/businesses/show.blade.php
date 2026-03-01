<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $business->business_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('businesses.facilities.index', $business) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Facilities') }}
                </a>
                <a href="{{ route('businesses.edit', $business) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Business details') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Registration Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->registration_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Tax ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->tax_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Contact Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->contact_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($business->status) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Facilities') }}</h3>
                @if ($business->facilities->isEmpty())
                    <p class="text-gray-600">{{ __('No facilities registered yet.') }}</p>
                    <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center mt-2 text-indigo-600 hover:text-indigo-900">
                        {{ __('Add facility') }}
                    </a>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($business->facilities as $facility)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <a href="{{ route('businesses.facilities.show', [$business, $facility]) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $facility->facility_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">{{ $facility->facility_type }} · {{ $facility->district }}, {{ $facility->sector }}</p>
                                </div>
                                <a href="{{ route('businesses.facilities.edit', [$business, $facility]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center mt-4 text-indigo-600 hover:text-indigo-900">
                        {{ __('Add facility') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
