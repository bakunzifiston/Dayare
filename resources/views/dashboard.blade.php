<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-800 tracking-tight">
            {{ __('Your Dashboard') }}
        </h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="p-6 sm:p-8 text-slate-800">
                    <p class="text-lg font-medium text-slate-900 mb-1">
                        {{ __('Welcome, :name!', ['name' => $user->name]) }}
                    </p>
                    <p class="text-slate-600 mb-6">
                        {{ __("This is your personal dashboard. Only you can see this page and your data.") }}
                    </p>
                    <a href="{{ route('businesses.index') }}" class="inline-flex items-center px-4 py-2.5 bg-[#3B82F6] hover:bg-[#2563eb] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        {{ __('Manage Businesses & Facilities') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
