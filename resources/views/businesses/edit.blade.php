<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('businesses.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Businesses') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit Business') }}
                </h2>
            </div>
            <a href="{{ route('businesses.show', $business) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">{{ __('Back to business') }}</a>
        </div>
    </x-slot>

    @include('businesses.partials.onboarding-wizard', [
        'business' => $business,
        'formId' => 'business-edit-form',
        'formAction' => route('businesses.update', $business),
        'formMethod' => 'patch',
        'draftKey' => 'bucha-processor-business-edit-draft-' . $business->id,
        'sidebarBadge' => __('Edit business setup'),
        'sidebarTitle' => __('Business Onboarding Wizard'),
        'sidebarDescription' => __('Update operator survey details covering identity, workforce, operations, and digital readiness.'),
        'headerTitle' => __('Update the setup'),
        'submitLabel' => __('Update business'),
        'backUrl' => route('businesses.show', $business),
        'backLabel' => __('← Back to business'),
        'cancelUrl' => route('businesses.hub'),
    ])
</x-app-layout>
