<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ __('Edit user') }}: {{ $user->name }}
        </h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('tenant-users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Account') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="__('New password (leave blank to keep)')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Access') }}</h2>
                <div>
                    <x-input-label :value="__('Role')" />
                    <select name="role" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                        <option value="manager" @selected(old('role', $currentRole) === 'manager')>{{ __('Manager') }}</option>
                        <option value="staff" @selected(old('role', $currentRole) === 'staff')>{{ __('Staff') }}</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('role')" />
                </div>
                <div>
                    <x-input-label :value="__('Businesses')" />
                    <div class="space-y-2">
                        @foreach ($businesses as $b)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="business_ids[]" value="{{ $b->id }}" @checked(in_array($b->id, old('business_ids', $userBusinessIds))) class="rounded border-gray-300 focus:ring-bucha-primary">
                                <span class="text-sm text-slate-700">{{ $b->business_name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('business_ids')" />
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Module access') }}</h2>
                <p class="text-sm text-slate-600">{{ __('Select which modules this user can access. Leave empty to use the role default.') }}</p>
                @foreach ($permissionGroups as $groupLabel => $permissions)
                    @if (count($permissions) > 0)
                        <div class="border border-slate-200 rounded-lg p-3">
                            <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">{{ $groupLabel }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($permissions as $permName => $permLabel)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="permissions[]" value="{{ $permName }}" @checked(in_array($permName, old('permissions', $userPermissionNames))) class="rounded border-gray-300 focus:ring-bucha-primary">
                                        <span class="text-slate-700">{{ $permLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Update user') }}
                </button>
                <a href="{{ route('tenant-users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
