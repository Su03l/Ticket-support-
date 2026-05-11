@props([
  'heading' => '',
  'icon'  => null,
  'compact' => false,
  'variant' => 'default',
])

@php
  $accentBar = match($variant) {
    'highlight' => 'border-s-4 border-s-zinc-900 dark:border-s-white',
    'info'   => 'border-s-4 border-s-blue-500',
    'warning'  => 'border-s-4 border-s-amber-500',
    'success'  => 'border-s-4 border-s-emerald-500',
    default   => '',
  };
@endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 ' . $accentBar]) }}>
  @if ($heading || ($headerAction ?? false) || $icon)
    <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
      <div class="flex items-center gap-2.5">
        @if ($icon)
          <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
            <flux:icon :name="$icon" variant="outline" class="size-4 text-zinc-500 dark:text-zinc-400" />
          </div>
        @endif
        @if ($heading)
          <flux:heading size="sm" class="font-semibold text-zinc-900 dark:text-white">{{ $heading }}</flux:heading>
        @endif
      </div>

      @if ($headerAction ?? false)
        <div class="shrink-0">
          {{ $headerAction }}
        </div>
      @endif
    </div>
  @endif

  <div class="{{ $compact ? 'p-5' : 'p-6' }}">
    {{ $slot }}
  </div>
</div>