<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ __('Add user') }}
        </h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('tenant-users.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Account') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4" x-data="{ selectedRole: '{{ old('role', 'operations_manager') }}' }">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Access') }}</h2>
                <div>
                    <x-input-label :value="__('Role')" />
                    <select name="role" x-model="selectedRole" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                        @foreach ($roleOptions as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}" @selected(old('role', 'operations_manager') === $roleValue)>{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('role')" />
                </div>
                <div>
                    <x-input-label :value="__('Role access summary')" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Review each role before assigning access.') }}</p>
                    <div class="mt-2 grid grid-cols-1 lg:grid-cols-2 gap-3">
                        @foreach ($roleGuidance as $roleValue => $guidance)
                            <div
                                class="rounded-lg border p-3 transition-colors"
                                :class="selectedRole === '{{ $roleValue }}'
                                    ? 'border-bucha-primary bg-red-50/30'
                                    : 'border-slate-200 bg-slate-50/60'"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ $roleOptions[$roleValue] ?? $roleValue }}</p>
                                    <span class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('Role') }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-600">{{ $guidance['description'] }}</p>
                                <ul class="mt-2 space-y-1 text-xs text-slate-700">
                                    @foreach (($guidance['permissions'] ?? []) as $permissionLine)
                                        <li class="flex items-start gap-1.5">
                                            <span class="mt-1 inline-flex h-1.5 w-1.5 rounded-full bg-bucha-primary"></span>
                                            <span>{{ $permissionLine }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <x-input-label :value="__('Assigned businesses')" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('This user can only access selected businesses.') }}</p>
                    @php
                        $defaultBusinessIds = array_map(fn ($business) => (int) $business['id'], $assignableBusinesses ?? []);
                        $selectedBusinesses = array_map('intval', old('business_ids', $defaultBusinessIds));
                    @endphp
                    <div class="mt-2 space-y-2 rounded-lg border border-slate-200 p-3 max-h-56 overflow-y-auto">
                        @foreach ($assignableBusinesses as $business)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="business_ids[]"
                                    value="{{ $business['id'] }}"
                                    class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary"
                                    @checked(in_array((int) $business['id'], $selectedBusinesses, true))
                                >
                                <span>{{ $business['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('business_ids')" />
                    <x-input-error class="mt-2" :messages="$errors->get('business_ids.*')" />
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Create user') }}
                </button>
                <a href="{{ route('tenant-users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
