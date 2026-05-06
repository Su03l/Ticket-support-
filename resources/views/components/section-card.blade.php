@props([
    'heading' => '',
    'icon'    => null,
    'compact' => false,
    'variant' => 'default',
])

@php
    $accentBar = match($variant) {
        'highlight' => 'border-t-2 border-t-zinc-900 dark:border-t-white',
        'info'      => 'border-t-2 border-t-blue-500',
        'warning'   => 'border-t-2 border-t-amber-500',
        'success'   => 'border-t-2 border-t-emerald-500',
        default     => '',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card overflow-hidden ' . $accentBar]) }}>
    @if ($heading || ($headerAction ?? false) || $icon)
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100/80 px-5 py-4 dark:border-zinc-800/80 sm:px-6">
            <div class="flex items-center gap-2.5">
                @if ($icon)
                    <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon :name="$icon" class="size-4 text-zinc-500 dark:text-zinc-400" />
                    </div>
                @endif
                @if ($heading)
                    <flux:heading size="sm" class="font-semibold tracking-tight text-zinc-800 dark:text-zinc-200">{{ $heading }}</flux:heading>
                @endif
            </div>

            @if ($headerAction ?? false)
                <div class="shrink-0">
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $compact ? 'p-4 sm:p-5' : 'p-5 sm:p-6' }}">
        {{ $slot }}
    </div>
</div>
