<x-login-layout
    leftTitle="{{ __('Create account') }}"
    :leftSubtitle="config('app.name')"
    :leftDescription="__('Join the platform to manage your facilities, inspections, and meat traceability.')"
>
    <div class="w-full max-w-sm mx-auto">
        <h2 class="text-2xl font-bold text-[#3B82F6]">{{ __('Sign up') }}</h2>
        <p class="text-sm text-gray-500 mt-1 mb-6">{{ __('Create your account to get started.') }}</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" :value="__('Name')" class="text-gray-700 font-medium" />
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" class="text-gray-700 font-medium" />
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]"
                    :value="old('email')"
                    required
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
                    {{ __('Register') }}
                </button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="font-medium text-[#3B82F6] hover:text-[#2563eb] focus:outline-none focus:underline">
                {{ __('Sign in') }}
            </a>
        </p>
    </div>
</x-login-layout>
