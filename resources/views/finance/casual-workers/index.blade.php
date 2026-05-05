<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Casual workers (AP registry)') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1000px] mx-auto px-0 sm:px-0 space-y-4">
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('finance.payables.index', ['tab' => 'casual']) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Back to casual payables') }}</a>
                <a href="{{ route('finance.casual-workers.create') }}" class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Add casual worker') }}</a>
            </div>

            <section class="rounded-bucha border border-slate-200 bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-4 py-2">{{ __('Name') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Phone') }}</th>
                                <th class="text-left px-4 py-2">{{ __('National ID') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Active') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($workers as $worker)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2 font-medium text-slate-800">{{ $worker->displayName() }}</td>
                                    <td class="px-4 py-2">{{ $worker->phone ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $worker->national_id ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $worker->is_active ? __('Yes') : __('No') }}</td>
                                    <td class="px-4 py-2 text-right space-x-2">
                                        <a href="{{ route('finance.casual-workers.edit', $worker) }}" class="text-bucha-primary">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('finance.casual-workers.destroy', $worker) }}" class="inline" onsubmit="return confirm(@js(__('Delete this casual worker?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-700">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No casual workers yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-100">{{ $workers->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
