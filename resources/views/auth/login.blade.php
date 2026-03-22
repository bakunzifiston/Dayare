<x-login-layout>
    <div class="w-full max-w-sm mx-auto">
        <h2 class="text-2xl font-bold text-bucha-primary">{{ __('Sign in') }}</h2>
        <p class="text-sm text-gray-500 mt-1 mb-6">{{ __('Enter your credentials to access your account.') }}</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        @if ($errors->any())
            <div class="mb-4 p-4 rounded-lg bg-red-100 border-2 border-red-400 text-red-900 text-sm shadow-sm" role="alert">
                <p class="font-semibold">{{ __('Login failed') }}</p>
                <p class="mt-1">{{ $errors->first('email') ?: $errors->first('password') ?: __('Wrong email or password. Please check and try again.') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email')" class="text-gray-700 font-medium" />
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    class="block mt-1 w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="{{ __('Email') }}"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" class="text-gray-700 font-medium" />
                <div class="relative mt-1" x-data="{ show: false }">
                    <input
                        id="password"
                        name="password"
                        x-bind:type="show ? 'text' : 'password'"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary pr-20"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500 hover:text-bucha-burgundy focus:outline-none"
                        tabindex="-1"
                        x-text="show ? '{{ __('Hide') }}' : '{{ __('Show') }}'"
                    ></button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center">
                    <input
                        id="remember_me"
                        type="checkbox"
                        class="rounded border-gray-300 text-bucha-primary shadow-sm focus:ring-bucha-primary"
                        name="remember"
                    >
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm text-gray-600 hover:text-bucha-burgundy rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-bucha-primary"
                    >
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full flex justify-center items-center px-4 py-3 bg-bucha-primary hover:bg-bucha-burgundy text-white font-semibold text-sm uppercase tracking-widest rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-bucha-primary focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    {{ __('Sign in') }}
                </button>
            </div>
        </form>

        @if (Route::has('register'))
            <p class="mt-6 text-center text-sm text-gray-500">
                {{ __('Don\'t have an account?') }}
                <a href="{{ route('register') }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy focus:outline-none focus:underline">
                    {{ __('Sign up') }}
                </a>
            </p>
        @endif
    </div>
</x-login-layout>
