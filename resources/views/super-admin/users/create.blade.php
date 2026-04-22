<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Add admin user') }}</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('super-admin.users.store') }}" class="space-y-6">
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

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Module access') }}</h2>
                <p class="text-xs text-slate-500">{{ __('Select which Super Admin modules this account can open.') }}</p>
                @php
                    $selectedPermissions = array_map('strval', old('module_permissions', []));
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($moduleOptions as $moduleKey => $module)
                        <label class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 flex items-start gap-2">
                            <input
                                type="checkbox"
                                name="module_permissions[]"
                                value="{{ $moduleKey }}"
                                class="mt-0.5 rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary"
                                @checked(in_array($moduleKey, $selectedPermissions, true))
                            >
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $module['label'] }}</span>
                                <span class="block text-xs text-slate-600 mt-0.5">{{ $module['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('module_permissions')" />
                <x-input-error class="mt-2" :messages="$errors->get('module_permissions.*')" />
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Create admin user') }}
                </button>
                <a href="{{ route('super-admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
