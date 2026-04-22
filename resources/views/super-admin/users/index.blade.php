<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('Admin users') }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('Create super admin users and limit module access.') }}</p>
            </div>
            <a href="{{ route('super-admin.users.create') }}" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                {{ __('Add admin user') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Super admin directory') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Email') }}</th>
                                <th class="px-4 py-3">{{ __('Module access') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($users as $user)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $permissions = $user->normalizedSuperAdminPermissions();
                                        @endphp
                                        @if ($permissions === [])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">
                                                {{ __('Full access (legacy)') }}
                                            </span>
                                        @else
                                            @foreach ($permissions as $permission)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 mr-1 mb-1">
                                                    {{ $moduleOptions[$permission]['label'] ?? $permission }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ((int) $user->id === (int) Auth::id())
                                            <span class="text-slate-400 text-xs">{{ __('You') }}</span>
                                        @else
                                            <a href="{{ route('super-admin.users.edit', $user) }}" class="text-bucha-primary hover:text-bucha-burgundy text-xs font-semibold">{{ __('Edit') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <form method="POST" action="{{ route('super-admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('{{ __('Remove this admin user?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">{{ __('Remove') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No super admin users found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
