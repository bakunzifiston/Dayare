<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sanitation records') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Equipment cleaning, deep cleans, and inspections.') }}</p>
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
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Log sanitation') }}</h3>
                <form method="post" action="{{ route('butcher.compliance.sanitation.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Outlet') }}</label>
                        <select name="outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Equipment') }}</label>
                        <input name="equipment_name" required placeholder="Band saw" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Cleaning type') }}</label>
                        <select name="cleaning_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($cleaningTypes as $type)
                                <option value="{{ $type }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Chemical used') }}</label>
                        <input name="chemical_used" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Performed at') }}</label>
                        <input type="datetime-local" name="performed_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Next due') }}</label>
                        <input type="datetime-local" name="next_due_at" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Notes') }}</label>
                        <input name="notes" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save record') }}</button>
                    </div>
                </form>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Performed') }}</th>
                            <th class="py-2 pr-4">{{ __('Outlet') }}</th>
                            <th class="py-2 pr-4">{{ __('Equipment') }}</th>
                            <th class="py-2 pr-4">{{ __('Type') }}</th>
                            <th class="py-2">{{ __('Next due') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr class="border-b border-slate-100 @if($record->isOverdue()) bg-red-50 @endif">
                                <td class="py-3 pr-4">{{ $record->performed_at?->format('M j, H:i') }}</td>
                                <td class="py-3 pr-4">{{ $record->outlet?->name }}</td>
                                <td class="py-3 pr-4 font-medium">{{ $record->equipment_name }}</td>
                                <td class="py-3 pr-4">{{ str_replace('_', ' ', $record->cleaning_type) }}</td>
                                <td class="py-3 @if($record->isOverdue()) text-red-800 font-semibold @endif">
                                    {{ $record->next_due_at?->format('M j, Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">{{ __('No sanitation records yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $records->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
