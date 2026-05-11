@props([
  'expandable' => false,
  'expanded' => true,
  'heading' => null,
])

<?php if ($expandable && $heading): ?>

<ui-disclosure
  {{ $attributes->class('group/disclosure') }}
  @if ($expanded === true) open @endif
  data-flux-navlist-group
>
  <button
    type="button"
    class="group/disclosure-button mb-1 flex h-11 w-full items-center rounded-xl text-zinc-500 transition-colors hover:bg-zinc-800/5 hover:text-zinc-900 lg:h-10 dark:text-white/70 dark:hover:bg-white/[7%] dark:hover:text-white"
  >
    <div class="ps-3 pe-4">
      <flux:icon.chevron-down class="hidden size-3.5! group-data-open/disclosure-button:block" />
      <flux:icon.chevron-right class="block size-3.5! group-data-open/disclosure-button:hidden" />
    </div>

    <span class="text-sm font-semibold tracking-tight">{{ $heading }}</span>
  </button>

  <div class="relative hidden space-y-1 ps-7 data-open:block" @if ($expanded === true) data-open @endif>
    <div class="absolute inset-y-1 start-0 ms-4 w-px bg-zinc-200 dark:bg-white/20"></div>

    {{ $slot }}
  </div>
</ui-disclosure>

<?php elseif ($heading): ?>

<div {{ $attributes->class('block space-y-1 mb-2') }}>
  <div class="px-3 py-3">
    <div class="text-xs font-bold tracking-wider uppercase text-zinc-400 dark:text-zinc-500">{{ $heading }}</div>
  </div>

  <div class="space-y-1">
    {{ $slot }}
  </div>
</div>

<?php else: ?>

<div {{ $attributes->class('block space-y-1') }}>
  {{ $slot }}
</div>

<?php endif; ?>