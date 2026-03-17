<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Users') }}
            </h2>
            <a href="{{ route('tenant-users.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Add user') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('info'))
                <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Email') }}</th>
                                <th class="px-4 py-3">{{ __('Role / Businesses') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $u)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $u->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $u->email }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @foreach ($userBusinessRoles[$u->id] ?? [] as $br)
                                            <span class="inline-block rounded bg-slate-100 px-2 py-0.5 text-xs mr-1 mb-1">{{ $br['business_name'] }} ({{ ucfirst($br['role']) }})</span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ($u->id === Auth::id())
                                            <span class="text-slate-400 text-xs">{{ __('You') }}</span>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <a href="{{ route('profile.edit') }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('Profile') }}</a>
                                        @else
                                            <a href="{{ route('tenant-users.edit', $u) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('Edit') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <form method="POST" action="{{ route('tenant-users.destroy', $u) }}" class="inline" onsubmit="return confirm('{{ __('Remove this user from your team? They will lose access to your businesses.') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('Remove') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
