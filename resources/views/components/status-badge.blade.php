@props([
    'status' => '',
])

@php
    $normalized = strtolower(str_replace(' ', '_', $status));

    $colorMap = [
        'active' => 'emerald',
        'new' => 'blue',
        'open' => 'blue',
        'in_progress' => 'amber',
        'waiting_customer' => 'amber',
        'waiting_department' => 'amber',
        'resolved' => 'emerald',
        'closed' => 'zinc',
        'reopened' => 'violet',
        'cancelled' => 'zinc',
        'suspended' => 'red',
        'pending' => 'amber',
        'critical' => 'red',
        'high' => 'red',
        'medium' => 'amber',
        'low' => 'zinc',
        'internal' => 'amber',
        'public' => 'blue',
        'unread' => 'blue',
        'archived' => 'zinc',
        'inactive' => 'zinc',
    ];

    $color = $colorMap[$normalized] ?? 'zinc';
    $label = __(ucfirst(str_replace('_', ' ', $status)));
@endphp

<flux:badge :color="$color" size="sm" {{ $attributes }}>{{ $label }}</flux:badge>
