@props([
    'label' => '',
    'value' => 0,
    'icon' => null,
    'accent' => 'zinc',
    'description' => null,
])

@php
    $accentClasses = match($accent) {
        'blue'    => ['bar' => 'stat-accent-blue',    'icon' => 'bg-blue-50 ring-blue-100 text-blue-600 dark:bg-blue-500/10 dark:ring-blue-500/20 dark:text-blue-400'],
        'emerald' => ['bar' => 'stat-accent-emerald',  'icon' => 'bg-emerald-50 ring-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:ring-emerald-500/20 dark:text-emerald-400'],
        'amber'   => ['bar' => 'stat-accent-amber',   'icon' => 'bg-amber-50 ring-amber-100 text-amber-600 dark:bg-amber-500/10 dark:ring-amber-500/20 dark:text-amber-400'],
        'violet'  => ['bar' => 'stat-accent-violet',  'icon' => 'bg-violet-50 ring-violet-100 text-violet-600 dark:bg-violet-500/10 dark:ring-violet-500/20 dark:text-violet-400'],
        'red'     => ['bar' => 'stat-accent-red',     'icon' => 'bg-red-50 ring-red-100 text-red-600 dark:bg-red-500/10 dark:ring-red-500/20 dark:text-red-400'],
        default   => ['bar' => 'stat-accent-zinc',    'icon' => 'bg-zinc-100 ring-zinc-200/60 text-zinc-500 dark:bg-zinc-800 dark:ring-zinc-700/50 dark:text-zinc-400'],
    };
@endphp

<div {{ $attributes->merge(['class' => 'group card card-hover overflow-hidden ' . $accentClasses['bar']]) }}>
    <div class="p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <flux:text class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ $label }}</flux:text>
                <flux:heading size="xl" class="mt-2 font-bold tracking-tight text-zinc-900 dark:text-white">
                    {{ is_numeric($value) ? number_format((float) $value, is_float($value + 0) ? 1 : 0) : $value }}
                </flux:heading>
                @if ($description)
                    <flux:text class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
                @endif
            </div>

            @if ($icon)
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 transition-all duration-200 group-hover:scale-105 {{ $accentClasses['icon'] }}">
                    <flux:icon :name="$icon" class="size-5" />
                </div>
            @endif
        </div>

        @if ($footer ?? false)
            <div class="mt-4 border-t border-zinc-100/80 pt-3 dark:border-zinc-800">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
