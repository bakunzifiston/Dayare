<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ __('Super Admin') }}</span>
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold text-slate-800 tracking-tight truncate">{{ $tenant->name }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $tenant->email }}</p>
                </div>
            </div>
            <a href="{{ route('super-admin.tenants.index') }}" class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                {{ __('Back to directory') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            @include('super-admin.tenants.partials.users-tabs', ['tenantEnvironmentFilter' => $tenantEnvironmentFilter])

            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-700">{{ __('Account') }}</h2>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $isTest ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $tenant->tenantEnvironmentLabel() }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 w-48">{{ __('Name') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ $tenant->name }}</td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Login email') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ $tenant->email }}</td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Email status') }}</th>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $tenant->email_verified_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $tenant->email_verified_at ? __('Verified') : __('Unverified') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Environment') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ $tenant->tenantEnvironmentLabel() }}</td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Joined') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ $joined }}</td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Last sign in') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ $lastSignIn }}</td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Businesses') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ number_format($businessRows->count()) }}</td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Users accounts') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ number_format($usersCount) }}</td>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">{{ __('Staff accounts') }}</th>
                                <td class="px-6 py-3 text-slate-900">{{ number_format($staffCount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">{{ __('Businesses') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    @if ($businessRows->isEmpty())
                        <p class="p-6 text-sm text-slate-500">{{ __('No businesses registered for this tenant.') }}</p>
                    @else
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">{{ __('Business') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Registration') }}</th>
                                    <th class="px-4 py-3">{{ __('Tax ID') }}</th>
                                    <th class="px-4 py-3">{{ __('Email') }}</th>
                                    <th class="px-4 py-3">{{ __('Phone') }}</th>
                                    <th class="px-4 py-3">{{ __('Owner') }}</th>
                                    <th class="px-4 py-3">{{ __('Location') }}</th>
                                    <th class="px-4 py-3">{{ __('VIBE ID') }}</th>
                                    <th class="px-4 py-3">{{ __('Pathway') }}</th>
                                    <th class="px-4 py-3">{{ __('Ownership') }}</th>
                                    <th class="px-4 py-3">{{ __('Size') }}</th>
                                    <th class="px-4 py-3">{{ __('Facilities') }}</th>
                                    <th class="px-4 py-3">{{ __('Staff') }}</th>
                                    <th class="px-4 py-3">{{ __('Commenced') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($businessRows as $row)
                                    <tr class="hover:bg-slate-50/70 align-top">
                                        <td class="px-4 py-3.5 font-medium text-slate-900 whitespace-nowrap">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700">
                                            <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ $row['type'] }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-700">{{ $row['status'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">{{ $row['registration_number'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">{{ $row['tax_id'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700">{{ $row['email'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">{{ $row['phone'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700">{{ $row['owner'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700">{{ $row['location'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">{{ $row['vibe_id'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700">{{ $row['pathway'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700">{{ $row['ownership'] }}</td>
                                        <td class="px-4 py-3.5 text-slate-700">{{ $row['size'] }}</td>
                                        <td class="px-4 py-3.5 tabular-nums text-slate-800">{{ number_format($row['facilities']) }}</td>
                                        <td class="px-4 py-3.5 tabular-nums text-slate-800">{{ number_format($row['staff']) }}</td>
                                        <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">{{ $row['commenced'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </section>

            <section id="users" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">{{ __('Users') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Email') }}</th>
                                <th class="px-4 py-3">{{ __('Role') }}</th>
                                <th class="px-4 py-3">{{ __('Business') }}</th>
                                <th class="px-4 py-3">{{ __('Email status') }}</th>
                                <th class="px-4 py-3">{{ __('Joined') }}</th>
                                <th class="px-4 py-3">{{ __('Last sign in') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($userRows as $row)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3.5 font-medium text-slate-900">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3.5 text-slate-600">{{ $row['email'] }}</td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $row['role'] }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-600">{{ $row['business'] }}</td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $row['verified'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $row['verified'] ? __('Verified') : __('Unverified') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">{{ $row['joined'] }}</td>
                                    <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">{{ $row['last_sign_in'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
