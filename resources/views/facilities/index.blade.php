<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Facilities') }} — {{ $business->business_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Add Facility') }}
                </a>
                <a href="{{ route('businesses.show', $business) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Back to Business') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($facilities->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('No facilities for this business yet.') }}</p>
                    <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Add first facility') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($facilities as $facility)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('businesses.facilities.show', [$business, $facility]) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $facility->facility_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $facility->facility_type }} · {{ $facility->district }}, {{ $facility->sector }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ __('License') }}: {{ $facility->license_number ?? '—' }} · {{ ucfirst($facility->status) }}
                                        @if ($facility->isLicenseExpired())
                                            <span class="text-red-600">{{ __('(Expired)') }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('businesses.facilities.edit', [$business, $facility]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                                    <form method="post" action="{{ route('businesses.facilities.destroy', [$business, $facility]) }}" class="inline" onsubmit="return confirm('{{ __('Delete this facility?') }}');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $facilities->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
