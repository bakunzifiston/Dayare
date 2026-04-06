<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Temperature standards') }}
                </h2>
            </div>
            <a href="{{ route('cold-room-standards.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Add standard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200/60 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <p class="text-sm text-slate-600">
                        {{ __('Define allowed temperature bands and how long an excursion can last before batches are flagged. Link each standard to a room under Manage cold rooms in the Cold Room module.') }}
                    </p>
                </div>
                @if (session('status'))
                    <div class="mx-6 mt-4 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Min °C') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Max °C') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Tolerance (min)') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Rooms') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($standards as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row->type }}</td>
                                    <td class="px-4 py-3 text-slate-600 tabular-nums">{{ $row->min_temperature }}</td>
                                    <td class="px-4 py-3 text-slate-600 tabular-nums">{{ $row->max_temperature }}</td>
                                    <td class="px-4 py-3 text-slate-600 tabular-nums">{{ $row->tolerance_minutes }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row->cold_rooms_count }}</td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <a href="{{ route('cold-room-standards.edit', $row) }}" class="text-bucha-primary hover:text-bucha-burgundy font-medium">{{ __('Edit') }}</a>
                                        <form method="post" action="{{ route('cold-room-standards.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this standard?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                        {{ __('No standards yet. Add one to attach to your cold rooms.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($standards->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $standards->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
