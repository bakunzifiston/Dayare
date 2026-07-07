@props([
    'storage' => null,
])

@php
    use App\Models\WarehouseStorage;

    $status = $storage?->status;
    $badge = match ($status) {
        WarehouseStorage::STATUS_RELEASED => 'bg-green-100 text-green-800',
        WarehouseStorage::STATUS_IN_STORAGE => 'bg-blue-100 text-blue-800',
        WarehouseStorage::STATUS_DISPOSED => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-500',
    };
    $statusLabel = $status
        ? ucfirst(str_replace('_', ' ', $status))
        : __('Not stored');
@endphp

<td {{ $attributes->merge(['class' => 'py-1 px-2']) }}>
    @if ($storage && $storage->isReleased())
        <span class="font-medium tabular-nums">{{ number_format((float) $storage->quantity_stored, 2) }} kg</span>
    @else
        <span class="text-gray-400">—</span>
    @endif
</td>
<td {{ $attributes->merge(['class' => 'py-1 px-2']) }}>
    <span class="text-xs px-2 py-0.5 rounded-full {{ $badge }}">{{ $statusLabel }}</span>
    @if ($storage?->released_date)
        <span class="block text-[10px] text-gray-400 mt-0.5">{{ $storage->released_date->format('d M Y') }}</span>
    @endif
</td>
