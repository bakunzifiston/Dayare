@php
    $icon = $icon ?? 'animal';
@endphp
@switch($icon)
    @case('cattle')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10c0-2.2 1.8-4 4-4h8c2.2 0 4 1.8 4 4v2H4v-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12v3M17 12v3M9 7V5M15 7V5"/><circle cx="8" cy="10" r="1" fill="currentColor" stroke="none"/><circle cx="16" cy="10" r="1" fill="currentColor" stroke="none"/></svg>
        @break
    @case('goat')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8c0-2 2-3 4-3h4c2 0 4 1 4 3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 18c1-4 3-6 7-6s6 2 7 6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5l-1-2M16 5l1-2"/></svg>
        @break
    @case('sheep')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14c-2 0-3-1.5-3-3.5S5 7 7 7s3 1.5 3 3.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14c2 0 3-1.5 3-3.5S19 7 17 7s-3 1.5-3 3.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14h4c2 0 4 2 4 5H6c0-3 2-5 4-5z"/></svg>
        @break
    @case('pig')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7" stroke-width="2"/><circle cx="9" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 15c.5.5 1.2.8 2 .8s1.5-.3 2-.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8l-2-1M16 8l2-1"/></svg>
        @break
    @case('mixed')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l2 4 4 .5-3 3 .8 4.5L12 13l-3.8 2 1-4.5-3-3 4-.5L12 3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19h14"/></svg>
        @break
    @case('arrow-down')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        @break
    @case('player-play')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-5.197-3.028A1 1 0 008 8.97v6.06a1 1 0 001.555.832l5.197-3.028a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @break
    @case('box')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        @break
    @case('animal')
    @default
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6c-2 0-3.5 1.2-4 3-.3 1.2-.5 2.5-.5 3.5 0 2.2 1.8 4 4.5 4s4.5-1.8 4.5-4c0-1-.2-2.3-.5-3.5-.5-1.8-2-3-4-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 20c1-2 2.5-3 4-3s3 1 4 3"/></svg>
@endswitch
