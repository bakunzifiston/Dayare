<x-login-layout
    leftTitle="{{ __('Forgot password?') }}"
    :leftSubtitle="config('app.name')"
    :leftDescription="__('Enter your email and we will send you a link to reset your password.')"
>
    <div class="w-full max-w-sm mx-auto">
        <h2 class="text-2xl font-bold text-bucha-primary">{{ __('Reset password') }}</h2>
        <p class="text-sm text-gray-500 mt-1 mb-6">{{ __('We will email you a password reset link.') }}</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full flex justify-center items-center px-4 py-3 bg-bucha-primary hover:bg-bucha-burgundy text-white font-semibold text-sm uppercase tracking-widest rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-bucha-primary focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    {{ __('Email Password Reset Link') }}
                </button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-medium text-bucha-primary hover:text-bucha-burgundy focus:outline-none focus:underline">
                {{ __('Back to sign in') }}
            </a>
        </p>
    </div>
</x-login-layout>
