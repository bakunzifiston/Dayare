<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Manage Cold Rooms') }}
                </h2>
            </div>
            <a href="{{ route('cold-rooms.manage.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Add cold room') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200/60 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <p class="text-sm text-slate-600">
                        {{ __('Rooms belong to a storage facility. Set a standard here, then choose this room when recording storage so monitoring can run. Standards are managed from Settings.') }}
                    </p>
                    <p class="mt-2 text-sm">
                        <a href="{{ route('settings.edit') }}" class="text-bucha-primary font-medium hover:text-bucha-burgundy">{{ __('Open settings') }} →</a>
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Facility') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Room name') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Standard') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('Capacity') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($coldRooms as $room)
                                <tr>
                                    <td class="px-4 py-3 text-slate-900">{{ $room->facility->facility_name }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $room->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $room->type }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($room->standard)
                                            {{ $room->standard->name }}
                                            <span class="text-slate-400">({{ $room->standard->min_temperature }}–{{ $room->standard->max_temperature }} °C)</span>
                                        @else
                                            <span class="text-amber-600">{{ __('Not set') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 tabular-nums">{{ $room->capacity ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <a href="{{ route('cold-rooms.manage.edit', $room) }}" class="text-bucha-primary hover:text-bucha-burgundy font-medium">{{ __('Edit') }}</a>
                                        <form method="post" action="{{ route('cold-rooms.manage.destroy', $room) }}" class="inline" onsubmit="return confirm('{{ __('Delete this cold room?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                        {{ __('No cold rooms yet. Add rooms for your storage facilities, then attach a standard.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($coldRooms->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $coldRooms->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
