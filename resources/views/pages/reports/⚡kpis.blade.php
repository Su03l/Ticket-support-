<?php

use App\Models\User;
use App\Services\EmployeeKpiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Performance Metrics')] class extends Component
{
 public int $month;

 public int $year;

 public string $agentId = '';

 public int $ticketsResolvedTarget = 20;

 public int $firstResponseMinutesTarget = 30;

 public string $csatTarget = '4.00';

 public string $qualityScoreTarget = '90.00';

 public function mount(): void
 {
  abort_unless(Auth::user()->can('reports.view'), 403);

  $this->month = (int) now()->month;
  $this->year = (int) now()->year;
 }

 public function saveTarget(EmployeeKpiService $kpis): void
 {
  abort_unless(Auth::user()->can('reports.export'), 403);

  $validated = $this->validate([
   'agentId' => ['required', Rule::exists('users', 'id')->where('company_id', Auth::user()->company_id)],
   'month' => ['required', 'integer', 'between:1,12'],
   'year' => ['required', 'integer', 'min:2024', 'max:2100'],
   'ticketsResolvedTarget' => ['required', 'integer', 'min:0'],
   'firstResponseMinutesTarget' => ['required', 'integer', 'min:0'],
   'csatTarget' => ['required', 'numeric', 'min:0', 'max:5'],
   'qualityScoreTarget' => ['required', 'numeric', 'min:0', 'max:100'],
  ]);

  $agent = User::query()->findOrFail($validated['agentId']);
  $kpis->saveTarget(Auth::user(), $agent, $validated['month'], $validated['year'], $validated['ticketsResolvedTarget'], $validated['firstResponseMinutesTarget'], (float) $validated['csatTarget'], (float) $validated['qualityScoreTarget']);
  
  flux()->toast(
   text: __('Performance target established for :name.', ['name' => $agent->name]),
   variant: 'success',
  );

  $this->reset(['agentId']);
 }

 public function with(EmployeeKpiService $kpis): array
 {
  return [
   'rows' => $kpis->monthlyDashboard(Auth::user(), $this->month, $this->year),
   'agents' => User::query()
    ->where('company_id', Auth::user()->company_id)
    ->whereNotNull('department_id')
    ->orderBy('name')
    ->get(['id', 'name']),
  ];
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
  <div>
   <flux:heading size="xl"level="1">{{ __('Performance Benchmarks') }}</flux:heading>
   <flux:text variant="subtle" class="mt-1">{{ __('Evaluate agent efficiency and service quality across key dimensions.') }}</flux:text>
  </div>

  <div class="flex items-center gap-2 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
   <flux:select wire:model.live="month" class="!border-0 !shadow-none">
    @for ($m = 1; $m <= 12; $m++)
     <flux:select.option :value="$m">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</flux:select.option>
    @endfor
   </flux:select>
   <flux:separator vertical class="h-4"/>
   <flux:select wire:model.live="year" class="!border-0 !shadow-none">
    @for ($y = now()->year; $y >= 2024; $y--)
     <flux:select.option :value="$y">{{ $y }}</flux:select.option>
    @endfor
   </flux:select>
  </div>
 </div>

 <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
  @forelse ($rows as $row)
   <div wire:key="kpi-agent-{{ $row['agent']->id }}" class="group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition-all hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
    <div class="p-6">
     <div class="flex items-start justify-between mb-8">
      <div class="flex items-center gap-3 min-w-0">
       <flux:avatar :name="$row['agent']->name" size="md"/>
       <div class="min-w-0">
        <flux:heading size="sm" class="truncate font-bold">{{ $row['agent']->name }}</flux:heading>
        <flux:text size="xs" variant="subtle">{{ $row['agent']->department?->name ?? __('Staff') }}</flux:text>
       </div>
      </div>
      
      @php
       $score = $row['kpi_score'];
       $color = match(true) {
        $score >= 90 => 'emerald',
        $score >= 75 => 'blue',
        $score >= 50 => 'amber',
        default => 'rose',
       };
      @endphp
      
      <div class="flex flex-col items-center">
       <div class="relative flex size-14 items-center justify-center">
        <svg class="absolute inset-0 size-full -rotate-90"viewBox="0 0 36 36">
         <circle cx="18"cy="18"r="16"fill="none" class="stroke-zinc-100 dark:stroke-zinc-800"stroke-width="3"></circle>
         <circle cx="18"cy="18"r="16"fill="none" class="stroke-{{ $color }}-500 transition-all duration-1000"stroke-width="3"stroke-dasharray="100"stroke-dashoffset="{{ 100 - $score }}"></circle>
        </svg>
        <span class="text-sm font-semibold text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ $score }}%</span>
       </div>
      </div>
     </div>

     <div class="grid grid-cols-2 gap-4">
      <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/50">
       <flux:text size="xs" class="font-bold uppercase tracking-wide text-zinc-400 mb-1">{{ __('Resolved') }}</flux:text>
       <div class="flex items-center gap-2">
        <flux:heading size="md">{{ $row['resolved_tickets'] }}</flux:heading>
        <flux:icon icon="check-circle" variant="mini" class="text-emerald-500/50"/>
       </div>
      </div>
      <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/50">
       <flux:text size="xs" class="font-bold uppercase tracking-wide text-zinc-400 mb-1">{{ __('Efficiency') }}</flux:text>
       <div class="flex items-center gap-2">
        <flux:heading size="md">{{ number_format($row['tracked_hours'], 1) }}h</flux:heading>
        <flux:icon icon="bolt" variant="mini" class="text-blue-500/50"/>
       </div>
      </div>
     </div>
    </div>

    <div class="mt-auto border-t border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-950/20">
     <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
       <flux:text size="sm" class="font-medium">{{ __('CSAT Average') }}</flux:text>
      </div>
      <div class="flex items-center gap-1.5">
       <span class="text-sm font-semibold">{{ number_format($row['average_csat'], 1) }}</span>
       <flux:icon icon="star" variant="mini" class="text-amber-400"/>
      </div>
     </div>
    </div>
   </div>
  @empty
   <div class="col-span-full flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200 py-24 dark:border-zinc-800">
    <div class="flex size-16 items-center justify-center rounded-full bg-zinc-50 dark:bg-zinc-800">
     <flux:icon icon="users" class="size-8 text-zinc-300"/>
    </div>
    <flux:heading size="lg" class="mt-4">{{ __('No operational data') }}</flux:heading>
    <flux:text variant="subtle" class="mt-1">{{ __('No activity recorded for the selected time horizon.') }}</flux:text>
   </div>
  @endforelse
 </div>

 @can('reports.export')
  <section class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
   <div class="mb-8 max-w-2xl">
    <flux:heading size="lg">{{ __('Performance Configuration') }}</flux:heading>
    <flux:text variant="subtle" class="mt-1">{{ __('Establish baseline expectations for resolution volume, response velocity, and service quality.') }}</flux:text>
   </div>

   <form wire:submit="saveTarget" class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
    <flux:field>
     <flux:label>{{ __('Agent') }}</flux:label>
     <flux:select wire:model="agentId">
      <flux:select.option value="">{{ __('Select agent...') }}</flux:select.option>
      @foreach ($agents as $agent)
       <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
      @endforeach
     </flux:select>
     <flux:error name="agentId"/>
    </flux:field>

    <flux:field>
     <flux:label>{{ __('Resolutions') }}</flux:label>
     <flux:input type="number" wire:model="ticketsResolvedTarget" icon="ticket"placeholder="20"/>
     <flux:error name="ticketsResolvedTarget"/>
    </flux:field>

    <flux:field>
     <flux:label>{{ __('Response Speed (min)') }}</flux:label>
     <flux:input type="number" wire:model="firstResponseMinutesTarget" icon="bolt"placeholder="30"/>
     <flux:error name="firstResponseMinutesTarget"/>
    </flux:field>

    <flux:field>
     <flux:label>{{ __('CSAT Floor') }}</flux:label>
     <flux:input type="number"step="0.01" wire:model="csatTarget" icon="star"placeholder="4.00"/>
     <flux:error name="csatTarget"/>
    </flux:field>

    <flux:field>
     <flux:label>{{ __('Quality Target (%)') }}</flux:label>
     <flux:input type="number"step="0.1" wire:model="qualityScoreTarget" icon="shield-check"placeholder="90"/>
     <flux:error name="qualityScoreTarget"/>
    </flux:field>

    <div class="flex items-end md:col-span-2 lg:col-span-4 xl:col-span-5">
     <flux:button type="submit" variant="primary" icon="check-badge" class="w-full lg:w-auto lg:px-12">{{ __('Define Benchmarks') }}</flux:button>
    </div>
   </form>
  </section>
 @endcan
</div>

