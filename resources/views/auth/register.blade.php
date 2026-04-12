<x-login-layout
    :show-footer="false"
    leftTitle="{{ __('Create account') }}"
    :leftSubtitle="config('app.name')"
    :leftDescription="__('Join the platform to manage your facilities, inspections, and meat traceability.')"
>
    <div class="w-full max-w-sm mx-auto">
        <h2 class="text-2xl font-bold text-bucha-primary">{{ __('Sign up') }}</h2>
        <p class="text-sm text-gray-500 mt-1 mb-6">{{ __('Create your account to get started.') }}</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" :value="__('Name')" class="text-gray-700 font-medium" />
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary"
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
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary"
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
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary"
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
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary"
                    required
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="business_type" :value="__('Business type')" class="text-gray-700 font-medium" />
                <select
                    id="business_type"
                    name="business_type"
                    required
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary text-sm"
                >
                    @php $bt = old('business_type', 'processor'); @endphp
                    <option value="farmer" @selected($bt === 'farmer')>{{ __('Farmer') }}</option>
                    <option value="processor" @selected($bt === 'processor')>{{ __('Processor') }}</option>
                    <option value="logistics" @selected($bt === 'logistics')>{{ __('Logistics') }}</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">{{ __('This sets your workspace. You can complete business details after signing up.') }}</p>
                <x-input-error :messages="$errors->get('business_type')" class="mt-2" />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full flex justify-center items-center px-4 py-3 bg-bucha-primary hover:bg-bucha-burgundy text-white font-semibold text-sm uppercase tracking-widest rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-bucha-primary focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    {{ __('Register') }}
                </button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy focus:outline-none focus:underline">
                {{ __('Sign in') }}
            </a>
        </p>
    </div>
</x-login-layout>
