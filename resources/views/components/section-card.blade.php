@props([
    'heading' => '',
    'icon'    => null,
    'compact' => false,
    'variant' => 'default',
])

@php
    $accentBar = match($variant) {
        'highlight' => 'border-t-4 border-t-zinc-900 dark:border-t-white',
        'info'      => 'border-t-4 border-t-blue-500',
        'warning'   => 'border-t-4 border-t-amber-500',
        'success'   => 'border-t-4 border-t-emerald-500',
        default     => '',
    };
@endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800/80 dark:bg-zinc-900 ' . $accentBar]) }}>
    @if ($heading || ($headerAction ?? false) || $icon)
        <div class="flex items-center justify-between gap-4 border-b border-zinc-100/80 px-6 py-5 dark:border-zinc-800/80 sm:px-8">
            <div class="flex items-center gap-3">
                @if ($icon)
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon :name="$icon" variant="outline" class="size-5 text-zinc-500 dark:text-zinc-400" />
                    </div>
                @endif
                @if ($heading)
                    <flux:heading size="md" class="font-bold tracking-tight text-zinc-900 dark:text-white">{{ $heading }}</flux:heading>
                @endif
            </div>

            @if ($headerAction ?? false)
                <div class="shrink-0">
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $compact ? 'p-6 sm:p-8' : 'p-8 sm:p-10' }}">
        {{ $slot }}
    </div>
</div>