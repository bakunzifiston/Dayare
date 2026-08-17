<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">
                    {{ __('Individual access') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('Customize access for :name without changing other users in the same role.', ['name' => $member->name]) }}
                </p>
            </div>
            <a href="{{ route('tenant-users.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                {{ __('Back to users') }}
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 py-6 lg:py-8">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-bucha border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('User') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $member->name }}</p>
                    <p class="text-xs text-slate-500">{{ $member->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Current role') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $roleLabel }}</p>
                </div>
                <form method="GET" action="{{ route('tenant-users.user-permissions.index', $member) }}">
                    <label for="business_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Business') }}</label>
                    <select id="business_id" name="business_id" onchange="this.form.submit()" class="mt-1 block w-full rounded-bucha border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        @foreach ($businesses as $business)
                            <option value="{{ $business->id }}" @selected($selectedBusiness->id === $business->id)>
                                {{ $business->business_name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </section>

        <div class="rounded-bucha border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-relaxed text-sky-900">
            <strong>{{ __('How this works:') }}</strong>
            {{ __('The role provides the starting access. Changes on this page apply only to this user in the selected business and take effect immediately.') }}
        </div>

        <form method="POST" action="{{ route('tenant-users.user-permissions.update', $member) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="business_id" value="{{ $selectedBusiness->id }}">

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                @foreach ($permissionGroups as $group)
                    @php
                        $groupPermissions = array_keys($group['permissions']);
                        $enabledCount = count(array_intersect($groupPermissions, $effectivePermissions));
                    @endphp
                    <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $group['label'] }}</h3>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $group['description'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                {{ $enabledCount }}/{{ count($groupPermissions) }}
                            </span>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach ($group['permissions'] as $permission => $label)
                                @php
                                    $isEnabled = in_array($permission, $effectivePermissions, true);
                                    $isRoleEnabled = in_array($permission, $rolePermissions, true);
                                    $isOverridden = array_key_exists($permission, $overrides);
                                @endphp
                                <label class="flex cursor-pointer items-start gap-3 px-5 py-3.5 transition hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission }}"
                                        @checked($isEnabled)
                                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary/30"
                                    >
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-slate-800">{{ $label }}</span>
                                        <span class="mt-0.5 block font-mono text-[11px] text-slate-400">{{ $permission }}</span>
                                    </span>
                                    <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide {{ $isOverridden ? 'text-bucha-primary' : 'text-slate-400' }}">
                                        {{ $isOverridden ? __('Individual') : ($isRoleEnabled ? __('Role on') : __('Role off')) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="flex flex-col-reverse gap-3 rounded-bucha border border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    {{ __('Only differences from the role are stored. Other users assigned to the role are not affected.') }}
                </p>
                <button type="submit" class="inline-flex items-center justify-center rounded-bucha bg-bucha-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-bucha-burgundy">
                    {{ __('Save individual access') }}
                </button>
            </div>
        </form>

        <section class="flex flex-col gap-3 rounded-bucha border border-amber-200 bg-amber-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-amber-900">{{ __('Restore role access') }}</h3>
                <p class="mt-1 text-xs text-amber-800">
                    {{ __('Remove all individual changes so this user follows the current :role role.', ['role' => $roleLabel]) }}
                </p>
            </div>
            <form method="POST" action="{{ route('tenant-users.user-permissions.destroy', $member) }}" onsubmit="return confirm('{{ __('Restore this user to their role access?') }}');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="business_id" value="{{ $selectedBusiness->id }}">
                <button type="submit" class="inline-flex items-center justify-center rounded-bucha border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
                    {{ __('Restore role access') }}
                </button>
            </form>
        </section>
    </div>
</x-app-layout>
