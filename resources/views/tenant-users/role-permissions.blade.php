<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Role access') }}</h1>
            <a href="{{ route('tenant-users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/>
                </svg>
                {{ __('Back to users') }}
            </a>
        </div>
    </x-slot>

    @php
        $roleLabel = $roleOptions[$selectedRole] ?? ucwords(str_replace('_', ' ', $selectedRole));
        $totalPermissions = count(\App\Models\BusinessUser::ACTION_PERMISSIONS);
        $enabledPermissions = count($effectivePermissions);
        $customizedPermissions = count($overrides);
    @endphp

    <div
        class="mx-auto max-w-7xl space-y-5 py-6 lg:py-8"
        x-data="{
            dirty: false,
            enabled: {{ $enabledPermissions }},
            total: {{ $totalPermissions }},
            refresh() {
                this.enabled = [...this.$el.querySelectorAll('[data-permission-checkbox]')]
                    .filter((input) => input.checked).length;
            }
        }"
        @permission-change.window="dirty = true; refresh()"
    >
        @if (session('status'))
            <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</span>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">{{ __('Please review the following:') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto]">
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-bucha-primary/10 text-bucha-primary">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4 7v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V7l-8-4Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Editing access for') }}</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $roleLabel }}</h2>
                            <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $roleDescription }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {{ $selectedBusiness->business_name }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {{ trans_choice(':count assigned member|:count assigned members', $roleMemberCount, ['count' => $roleMemberCount]) }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    {{ __('Active role') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('tenant-users.role-permissions.index') }}" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="business_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Business') }}</label>
                            <select id="business_id" name="business_id" onchange="this.form.submit()" class="mt-1.5 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary/20">
                                @foreach ($businesses as $business)
                                    <option value="{{ $business->id }}" @selected($selectedBusiness->id === $business->id)>
                                        {{ $business->business_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="role" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Role') }}</label>
                            <select id="role" name="role" onchange="this.form.submit()" class="mt-1.5 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary/20">
                                @foreach ($roleOptions as $role => $label)
                                    <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-3 border-t border-slate-200 bg-slate-50/70 lg:w-[360px] lg:border-l lg:border-t-0">
                    <div class="flex flex-col items-center justify-center border-r border-slate-200 px-3 py-5 text-center">
                        <span class="text-xl font-semibold text-slate-900" x-text="enabled">{{ $enabledPermissions }}</span>
                        <span class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('Enabled') }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center border-r border-slate-200 px-3 py-5 text-center">
                        <span class="text-xl font-semibold text-slate-900" x-text="total - enabled">{{ $totalPermissions - $enabledPermissions }}</span>
                        <span class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('Disabled') }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center px-3 py-5 text-center">
                        <span class="text-xl font-semibold text-bucha-primary">{{ $customizedPermissions }}</span>
                        <span class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('Custom') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('tenant-users.role-permissions.update') }}" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="business_id" value="{{ $selectedBusiness->id }}">
            <input type="hidden" name="role" value="{{ $selectedRole }}">

            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-bucha-primary">{{ __('Modules and permissions') }}</p>
                    <h2 class="mt-1 text-base font-semibold text-slate-900">{{ __('Configure access by module') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Each module shows whether this role has full, partial, or no access.') }}</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('Enabled') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300"></span>{{ __('Disabled') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                @foreach ($permissionGroups as $group)
                    @php
                        $groupPermissions = array_keys($group['permissions']);
                        $enabledCount = count(array_intersect($groupPermissions, $effectivePermissions));
                    @endphp
                    <section
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        x-data="{
                            moduleEnabled: {{ $enabledCount }},
                            moduleTotal: {{ count($groupPermissions) }},
                            sync() {
                                this.moduleEnabled = [...this.$el.querySelectorAll('[data-permission-checkbox]')]
                                    .filter((input) => input.checked).length;
                                this.$dispatch('permission-change');
                            },
                            setAll(value) {
                                this.$el.querySelectorAll('[data-permission-checkbox]')
                                    .forEach((input) => input.checked = value);
                                this.sync();
                            }
                        }"
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :class="moduleEnabled > 0 ? 'bg-emerald-500' : 'bg-slate-300'"
                                    ></span>
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $group['label'] }}</h3>
                                </div>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ $group['description'] }}</p>
                                <span
                                    class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                    :class="moduleEnabled === 0
                                        ? 'bg-slate-100 text-slate-500'
                                        : (moduleEnabled === moduleTotal
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-amber-50 text-amber-700')"
                                    x-text="moduleEnabled === 0
                                        ? '{{ __('No access') }}'
                                        : (moduleEnabled === moduleTotal
                                            ? '{{ __('Full access') }}'
                                            : '{{ __('Partial access') }}')"
                                ></span>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold tabular-nums text-slate-600">
                                    <span x-text="moduleEnabled">{{ $enabledCount }}</span>/{{ count($groupPermissions) }}
                                </span>
                                <div class="hidden items-center gap-2 sm:flex">
                                    <button type="button" @click="setAll(true)" class="text-[11px] font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                    {{ __('Enable all') }}
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="setAll(false)" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800">
                                        {{ __('Disable all') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach ($group['permissions'] as $permission => $label)
                                @php
                                    $isEnabled = in_array($permission, $effectivePermissions, true);
                                    $isDefault = in_array($permission, $defaultPermissions, true);
                                    $isOverridden = array_key_exists($permission, $overrides);
                                @endphp
                                <label class="group flex cursor-pointer items-center gap-3 px-5 py-3 transition hover:bg-slate-50/80">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission }}"
                                        @checked($isEnabled)
                                        data-permission-checkbox
                                        @change="sync()"
                                        class="h-4 w-4 shrink-0 rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary/30"
                                    >
                                    <span class="min-w-0 flex-1 text-sm font-medium text-slate-700 group-hover:text-slate-900">{{ $label }}</span>
                                    @if ($isOverridden)
                                        <span class="shrink-0 rounded-full bg-bucha-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-bucha-primary">
                                            {{ __('Custom') }}
                                        </span>
                                    @else
                                        <span class="shrink-0 text-[10px] font-medium uppercase tracking-wide text-slate-400">
                                            {{ $isDefault ? __('Default on') : __('Default off') }}
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white/95 px-5 py-4 shadow-lg shadow-slate-900/5 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :class="dirty ? 'bg-amber-500' : 'bg-emerald-500'"
                    ></span>
                    <div>
                        <p class="text-sm font-medium text-slate-800" x-text="dirty ? '{{ __('Unsaved changes') }}' : '{{ __('Access is up to date') }}'">
                            {{ __('Access is up to date') }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Only differences from system defaults are stored.') }}</p>
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-bucha-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-bucha-burgundy focus:outline-none focus:ring-2 focus:ring-bucha-primary/30 focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.5 17 19 7.5"/>
                    </svg>
                    {{ __('Save access') }}
                </button>
            </div>
        </form>

        <section class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Restore system defaults') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Remove all custom settings for this business and role.') }}</p>
            </div>
            <form method="POST" action="{{ route('tenant-users.role-permissions.destroy') }}" onsubmit="return confirm('{{ __('Restore the system defaults for this role?') }}');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="business_id" value="{{ $selectedBusiness->id }}">
                <input type="hidden" name="role" value="{{ $selectedRole }}">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800">
                    {{ __('Restore defaults') }}
                </button>
            </form>
        </section>
    </div>
</x-app-layout>
