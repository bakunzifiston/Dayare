{{--
    BuchaPro mark: rounded-square red icon + white "BUCHA" + accent "PRO".
    Use size="hero" on the landing page for a larger treatment.
--}}
@props([
    'href' => null,
    'showAdminBadge' => false,
    'size' => 'default', // default | hero
    'theme' => 'dark', // dark (gradient/sidebar) | light (white cards)
])

@php
    $href = $href ?? route('dashboard');
    $hero = $size === 'hero';
    $light = $theme === 'light';
    $boxClass = $hero
        ? 'h-14 w-14 sm:h-16 sm:w-16 rounded-2xl'
        : 'h-11 w-11 rounded-xl';
    $bSize = $hero ? 'text-[1.85rem] sm:text-[2.1rem]' : 'text-[1.35rem]';
    $linesGap = $hero ? 'gap-1' : 'gap-[3px]';
    $lineW = $hero ? ['w-2.5', 'w-3', 'w-3.5'] : ['w-2', 'w-2.5', 'w-3'];
    $wordClass = $hero
        ? 'text-base sm:text-lg tracking-[0.16em]'
        : 'text-[0.8125rem] sm:text-sm tracking-[0.14em]';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-3 sm:gap-4 min-w-0']) }}>
    <div class="flex shrink-0 items-center justify-center bg-bucha-primary shadow-lg shadow-black/25 ring-1 ring-white/15 {{ $boxClass }}">
        <span class="relative flex items-center justify-center {{ $bSize }} font-black italic leading-none text-white" style="letter-spacing: -0.02em;" aria-hidden="true">
            <span class="absolute -left-0.5 top-1/2 -translate-y-1/2 flex flex-col {{ $linesGap }} opacity-90">
                <span class="block h-[2px] {{ $lineW[0] }} rounded-full bg-white/90"></span>
                <span class="block h-[2px] {{ $lineW[1] }} rounded-full bg-white/90"></span>
                <span class="block h-[2px] {{ $lineW[2] }} rounded-full bg-white/90"></span>
            </span>
            <span class="pl-1">B</span>
        </span>
    </div>
    <div class="flex flex-col min-w-0 leading-none">
        <span class="font-extrabold uppercase {{ $wordClass }}">
            @if ($light)
                <span class="text-bucha-charcoal">BUCHA</span><span class="text-bucha-primary">PRO</span>
            @else
                <span class="text-white">BUCHA</span><span class="text-red-200">PRO</span>
            @endif
        </span>
        @if ($showAdminBadge)
            <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider {{ $light ? 'text-bucha-muted' : 'text-white/55' }}">{{ __('Platform admin') }}</span>
        @endif
    </div>
</a>
