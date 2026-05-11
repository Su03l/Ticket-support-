@props([
  'label' => '',
  'value' => 0,
  'icon' => null,
  'accent' => 'zinc',
  'description' => null,
])

@php
  $accentClasses = match($accent) {
    'blue'  => ['bar' => 'stat-accent-blue',  'icon' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400'],
    'emerald' => ['bar' => 'stat-accent-emerald', 'icon' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'],
    'amber'  => ['bar' => 'stat-accent-amber',  'icon' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'],
    'violet' => ['bar' => 'stat-accent-violet', 'icon' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400'],
    'red'   => ['bar' => 'stat-accent-red',   'icon' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400'],
    'indigo' => ['bar' => 'stat-accent-violet', 'icon' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400'],
    default  => ['bar' => 'stat-accent-zinc',  'icon' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'],
  };
@endphp

<div {{ $attributes->merge(['class' => 'group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 ' . $accentClasses['bar']]) }}>
  <div class="p-5">
    <div class="flex items-center justify-between gap-4">
      <div class="min-w-0 flex-1">
        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $label }}</flux:text>
        <flux:heading size="lg" class="mt-1.5 text-2xl font-semibold text-zinc-900 dark:text-white leading-none">
          {{ is_numeric($value) ? number_format((float) $value, is_float($value + 0) ? 1 : 0) : $value }}
        </flux:heading>
        @if ($description)
          <flux:text class="mt-1.5 truncate text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
        @endif
      </div>

      @if ($icon)
        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-105 {{ $accentClasses['icon'] }}">
          <flux:icon :name="$icon" class="size-5" />
        </div>
      @endif
    </div>

    @if ($footer ?? false)
      <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
        {{ $footer }}
      </div>
    @endif
  </div>
</div>