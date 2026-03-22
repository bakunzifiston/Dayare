<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $inspector->full_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('inspectors.edit', $inspector) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <form method="POST" action="{{ route('inspectors.destroy', $inspector) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this inspector? This cannot be undone.') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                        {{ __('Delete') }}
                    </button>
                </form>
                <a href="{{ route('inspectors.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Back to Inspectors') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Personal information') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('First name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->first_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Last name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->last_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('National ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->national_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->phone_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Date of birth') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->dob?->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Nationality') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->nationality }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Location') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500">{{ __('Location') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ $inspector->location_line }}</dd></div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Authorization & assignment') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Authorization number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->authorization_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Issue date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->authorization_issue_date?->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Expiry date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $inspector->authorization_expiry_date?->format('d M Y') }}
                            @if ($inspector->isAuthorizationExpired())
                                <span class="text-red-600">{{ __('(Expired)') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Species allowed') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->species_allowed ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Daily capacity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->daily_capacity ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Stamp serial number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspector->stamp_serial_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Assigned facility') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('businesses.facilities.show', [$inspector->facility->business, $inspector->facility]) }}" class="text-bucha-primary hover:underline">
                                {{ $inspector->facility->facility_name }} ({{ $inspector->facility->facility_type }})
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($inspector->status) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
