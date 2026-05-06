@props([
    'href'         => null,
    'wireNavigate' => true,
])

@php
    $baseClasses = 'grid gap-3 border-b border-zinc-100/80 px-4 py-3.5 last:border-b-0 dark:border-zinc-800/80 transition-colors duration-150';
    $interactiveClasses = $href ? 'hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 cursor-pointer' : '';
@endphp

@if ($href)
    <a href="{{ $href }}" @if($wireNavigate) wire:navigate @endif {{ $attributes->merge(['class' => "{$baseClasses} {$interactiveClasses}"]) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $slot }}
    </div>
@endif
