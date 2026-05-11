@props([
  'title',
  'description',
])

<div class="flex w-full flex-col gap-1.5 text-center">
  <flux:heading size="lg" class="font-semibold text-zinc-950 dark:text-white">{{ $title }}</flux:heading>
  <flux:subheading class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:subheading>
</div>
