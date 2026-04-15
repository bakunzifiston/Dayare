@props([
    'columns' => [],
    'emptyMessage' => __('No records found.'),
    'emptyActionLabel' => null,
    'emptyActionUrl' => null,
    'hasRows' => true,
])

<div class="overflow-hidden rounded-lg border border-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" class="px-4 py-3 text-left font-medium uppercase tracking-wide text-slate-500">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                @if ($hasRows)
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="{{ max(count($columns), 1) }}" class="px-4 py-10 text-center">
                            <p class="text-sm text-slate-500">{{ $emptyMessage }}</p>
                            @if ($emptyActionLabel && $emptyActionUrl)
                                <a href="{{ $emptyActionUrl }}" class="mt-3 inline-flex rounded-md border border-[#7A1C22] px-3 py-1.5 text-xs font-semibold text-[#7A1C22] hover:bg-[#f7eded]">
                                    {{ $emptyActionLabel }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
