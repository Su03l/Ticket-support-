@props([
  'icon'    => 'inbox',
  'heading'   => '',
  'description' => '',
])

<div {{ $attributes->merge(['class' => 'col-span-full flex flex-col items-center justify-center rounded-2xl border border-zinc-200 bg-zinc-50 px-8 py-16 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50']) }}>
  <div class="mb-5 flex size-14 items-center justify-center rounded-xl bg-white shadow-md ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
    <flux:icon :name="$icon" class="size-7 text-zinc-400 dark:text-zinc-500" />
  </div>

  <flux:heading size="md" class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $heading }}</flux:heading>

  @if ($description)
    <flux:text class="mt-2 max-w-sm text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
  @endif

  @if ($action ?? false)
    <div class="mt-6">
      {{ $action }}
    </div>
  @endif
</div>