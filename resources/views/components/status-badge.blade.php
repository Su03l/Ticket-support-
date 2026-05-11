@props([
  'status' => '',
])

@php
  $normalized = strtolower(str_replace(' ', '_', $status));

  $colorMap = [
    'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    'open' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    'in_progress' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'waiting_customer' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'waiting_department' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'resolved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    'closed' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-400',
    'reopened' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    'cancelled' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-400',
    'suspended' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'critical' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    'high' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'low' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-400',
    'internal' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'public' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    'unread' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    'archived' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-400',
    'inactive' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-400',
  ];

  $colorClasses = $colorMap[$normalized] ?? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-400';
  $label = __(ucfirst(str_replace('_', ' ', $status)));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {$colorClasses}"]) }}>
  {{ $label }}
</span>
