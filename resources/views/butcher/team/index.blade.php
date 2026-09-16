<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                            <i class="ti ti-user-shield text-lg leading-none"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Business owner') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-5">
                    @if ($owner)
                        <div class="flex items-start gap-3 rounded-lg border border-slate-100 bg-slate-50/50 px-4 py-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-bucha-burgundy ring-1 ring-inset ring-slate-200" aria-hidden="true">
                                <i class="ti ti-user text-[1.15rem] leading-none"></i>
                            </span>
                            <div>
                                <p class="font-semibold text-slate-900">{{ $owner->name }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $owner->email }}</p>
                                <span class="mt-2 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Owner') }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">{{ __('Owner account not found.') }}</p>
                    @endif
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                            <i class="ti ti-users text-lg leading-none"></i>
                        </span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Team members') }}</h3>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count member|:count members', $members->count(), ['count' => $members->count()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Member') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Role') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($members as $member)
                                @php
                                    $isOwnerRow = (int) $member->user_id === (int) $business->user_id;
                                @endphp
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-user text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $member->user?->name ?? __('Unknown user') }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $member->user?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @if ($isOwnerRow)
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Owner') }}</span>
                                        @else
                                            <span class="capitalize text-slate-700">{{ str_replace('_', ' ', $member->role) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        @unless ($isOwnerRow)
                                            <form method="post" action="{{ route('butcher.team.update') }}" class="inline-flex flex-wrap items-center justify-end gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                                <select id="role_{{ $member->id }}" name="role" required class="rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                                    @foreach ($roles as $roleKey => $roleLabel)
                                                        <option value="{{ $roleKey }}" @selected(old('role', $member->role) === $roleKey && (int) old('user_id', $member->user_id) === (int) $member->user_id)>
                                                            {{ __($roleLabel) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    <i class="ti ti-device-floppy text-sm leading-none" aria-hidden="true"></i>
                                                    {{ __('Update') }}
                                                </button>
                                            </form>
                                            @if ($errors->has('role') && (int) old('user_id') === (int) $member->user_id)
                                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-10 text-center text-slate-500">{{ __('No team members yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
