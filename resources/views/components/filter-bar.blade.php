@props([
  'columns' => 'md:grid-cols-3',
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900']) }}>
  <div class="flex items-center gap-2.5 border-b border-zinc-100 px-5 py-3 dark:border-zinc-800">
    <flux:icon name="adjustments-horizontal" class="size-4 text-zinc-400 dark:text-zinc-500" />
    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Filters') }}</span>
  </div>
  <div class="grid gap-3 p-5 {{ $columns }}">
    {{ $slot }}
  </div>
</div>