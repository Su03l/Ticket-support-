@props([
    'columns' => 'md:grid-cols-3',
])

<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <div class="flex items-center gap-2 border-b border-zinc-100/80 px-4 py-3 dark:border-zinc-800/80">
        <flux:icon name="adjustments-horizontal" class="size-4 text-zinc-400 dark:text-zinc-500" />
        <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Filters') }}</span>
    </div>
    <div class="grid gap-3 p-4 {{ $columns }}">
        {{ $slot }}
    </div>
</div>
