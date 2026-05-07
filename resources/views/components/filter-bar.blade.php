@props([
    'columns' => 'md:grid-cols-3',
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800/80 dark:bg-zinc-900']) }}>
    <div class="flex items-center gap-3 border-b border-zinc-100/80 px-6 py-4 dark:border-zinc-800/80">
        <flux:icon name="adjustments-horizontal" class="size-4 text-zinc-400 dark:text-zinc-500" />
        <span class="text-xs font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Filters') }}</span>
    </div>
    <div class="grid gap-4 p-6 {{ $columns }}">
        {{ $slot }}
    </div>
</div>