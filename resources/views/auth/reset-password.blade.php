<x-login-layout
    leftTitle="{{ __('Reset password') }}"
    :leftSubtitle="config('app.name')"
    :leftDescription="__('Enter your new password below.')"
>
    <div class="w-full max-w-sm mx-auto">
        <h2 class="text-2xl font-bold text-[#3B82F6]">{{ __('New password') }}</h2>
        <p class="text-sm text-gray-500 mt-1 mb-6">{{ __('Choose a new password for your account.') }}</p>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email" :value="__('Email')" class="text-gray-700 font-medium" />
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]"
                    :value="old('email', $request->email)"
                    required
                    autofocus
                    autocomplete="username"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" class="text-gray-700 font-medium" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]"
                    required
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-gray-700 font-medium" />
                <x-text-input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]"
                    required
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full flex justify-center items-center px-4 py-3 bg-[#3B82F6] hover:bg-[#2563eb] text-white font-semibold text-sm uppercase tracking-widest rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#3B82F6] focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    {{ __('Reset Password') }}
                </button>
            </div>
        </form>
    </div>
</x-login-layout>
