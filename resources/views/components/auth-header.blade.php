@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <flux:heading size="xl" class="text-zinc-950 dark:text-white">{{ $title }}</flux:heading>
    <flux:subheading class="text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:subheading>
</div>
