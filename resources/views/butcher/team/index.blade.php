<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Team & roles') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Assign roles for people working at :name.', ['name' => $business->business_name]) }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Business owner') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Owner role cannot be changed here.') }}</p>
                @if ($owner)
                    <div class="mt-4 rounded-lg border border-slate-200 px-4 py-3 text-sm">
                        <p class="font-semibold text-slate-900">{{ $owner->name }}</p>
                        <p class="mt-1 text-slate-500">{{ $owner->email }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-600">{{ __('Owner') }}</p>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500">{{ __('Owner account not found.') }}</p>
                @endif
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Team members') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Update one member at a time.') }}</p>
                <div class="mt-4 space-y-3">
                    @forelse ($members as $member)
                        @php
                            $isOwnerRow = (int) $member->user_id === (int) $business->user_id;
                        @endphp
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $member->user?->name ?? __('Unknown user') }}</p>
                                    <p class="mt-1 text-slate-500">{{ $member->user?->email }}</p>
                                </div>
                                @if ($isOwnerRow)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('Owner') }}</span>
                                @endif
                            </div>

                            @unless ($isOwnerRow)
                                <form method="post" action="{{ route('butcher.team.update') }}" class="mt-3 flex flex-wrap items-end gap-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                    <div class="min-w-[12rem] flex-1">
                                        <x-input-label :for="'role_'.$member->id" :value="__('Role')" />
                                        <select id="role_{{ $member->id }}" name="role" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                            @foreach ($roles as $roleKey => $roleLabel)
                                                <option value="{{ $roleKey }}" @selected(old('role', $member->role) === $roleKey && (int) old('user_id', $member->user_id) === (int) $member->user_id)>
                                                    {{ __($roleLabel) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('role') && (int) old('user_id') === (int) $member->user_id)
                                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                        @endif
                                    </div>
                                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                                        {{ __('Update role') }}
                                    </button>
                                </form>
                            @endunless
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No team members yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
