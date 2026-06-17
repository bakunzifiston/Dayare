<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Staff health cards') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Medical fitness tracking for food handlers.') }}</p>
            </div>
            <a href="{{ route('butcher.compliance.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Compliance dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Register / update health card') }}</h3>
                <form method="post" action="{{ route('butcher.compliance.health.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Staff member') }}</label>
                        <select name="user_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($staffUsers as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Card number') }}</label>
                        <input name="medical_card_number" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Health status') }}</label>
                        <select name="health_status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($healthStatuses as $status)
                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Issued date') }}</label>
                        <input type="date" name="issued_date" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Expiry date') }}</label>
                        <input type="date" name="expiry_date" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Notes') }}</label>
                        <input name="notes" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save') }}</button>
                    </div>
                </form>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Staff') }}</th>
                            <th class="py-2 pr-4">{{ __('Card #') }}</th>
                            <th class="py-2 pr-4">{{ __('Expiry') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2">{{ __('Last checked') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr class="border-b border-slate-100 @if($record->isExpired()) bg-red-50 @elseif($record->isExpiringSoon()) bg-amber-50 @endif">
                                <td class="py-3 pr-4 font-medium">{{ $record->user?->name }}</td>
                                <td class="py-3 pr-4">{{ $record->medical_card_number }}</td>
                                <td class="py-3 pr-4">{{ $record->expiry_date?->toDateString() }}</td>
                                <td class="py-3 pr-4">{{ ucfirst(str_replace('_', ' ', $record->health_status)) }}</td>
                                <td class="py-3">{{ $record->last_checked_at?->toDateString() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">{{ __('No health records yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $records->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
