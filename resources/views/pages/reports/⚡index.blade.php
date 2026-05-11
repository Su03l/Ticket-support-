<?php

use App\Enums\ReportExportFormat;
use App\Enums\ScheduledReportFrequency;
use App\Services\CustomerSatisfactionService;
use App\Services\ReportService;
use App\Services\ScheduledReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reports')] class extends Component
{
 public string $scheduledName = '';

 public string $scheduledFrequency = 'weekly';

 public string $scheduledFormat = 'pdf';

 public string $scheduledRecipients = '';

 public function mount(): void
 {
  abort_unless(Auth::user()->can('reports.view'), 403);
 }

 public function createScheduledReport(ScheduledReportService $scheduledReports): void
 {
  abort_unless(Auth::user()->can('reports.export'), 403);

  $validated = $this->validate([
   'scheduledName' => ['required', 'string', 'max:120'],
   'scheduledFrequency' => ['required', Rule::enum(ScheduledReportFrequency::class)],
   'scheduledFormat' => ['required', Rule::enum(ReportExportFormat::class)],
   'scheduledRecipients' => ['required', 'string'],
  ]);

  $recipients = collect(explode(',', $validated['scheduledRecipients']))
   ->map(fn (string $email): string => trim($email))
   ->filter()
   ->values()
   ->all();

  $scheduledReports->create(Auth::user(), $validated['scheduledName'], ScheduledReportFrequency::from($validated['scheduledFrequency']), ReportExportFormat::from($validated['scheduledFormat']), $recipients);
  
  flux()->toast(
   text: __('Scheduled report created successfully.'),
   variant: 'success',
  );

  $this->reset(['scheduledName', 'scheduledRecipients']);
 }

 public function with(ReportService $reports, CustomerSatisfactionService $satisfaction): array
 {
  return [
   'summary' => $reports->dashboard(Auth::user()),
   'satisfaction' => Auth::user()->company_id === null
    ? ['average_csat' => 0, 'nps_score' => 0, 'promoters' => 0, 'passives' => 0, 'detractors' => 0, 'responses' => 0]
    : $satisfaction->dashboard(Auth::user()->company_id),
  ];
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex items-center justify-between gap-4">
  <div>
   <flux:heading size="xl"level="1">{{ __('Reports & Analytics') }}</flux:heading>
   <flux:text variant="subtle" class="mt-1">{{ __('Monitor your support operations and team performance.') }}</flux:text>
  </div>

  <div class="flex items-center gap-3">
   @can('reports.export')
    <flux:button icon="arrow-down-tray" variant="outline" size="sm">{{ __('Export Data') }}</flux:button>
   @endcan
  </div>
 </div>

 {{-- Main KPI Grid --}}
 <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  @php
   $metrics = [
    ['key' => 'total_tickets', 'label' => __('Total Tickets'), 'icon' => 'ticket', 'color' => 'blue'],
    ['key' => 'open_tickets', 'label' => __('Active Tickets'), 'icon' => 'clock', 'color' => 'orange'],
    ['key' => 'closed_tickets', 'label' => __('Resolved'), 'icon' => 'check-circle', 'color' => 'emerald'],
    ['key' => 'overdue_tickets', 'label' => __('SLA Breaches'), 'icon' => 'exclamation-triangle', 'color' => 'rose'],
   ];
  @endphp

  @foreach ($metrics as $metric)
   <div class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-center justify-between">
     <flux:text size="xs" class="font-bold text-zinc-500 uppercase tracking-widest">{{ $metric['label'] }}</flux:text>
     <div class="flex size-8 items-center justify-center rounded-lg bg-{{ $metric['color'] }}-50 text-{{ $metric['color'] }}-600 dark:bg-{{ $metric['color'] }}-950/30">
      <flux:icon :icon="$metric['icon']" variant="mini"/>
     </div>
    </div>
    <div class="mt-4 flex items-baseline gap-2">
     <flux:heading size="xl" class="font-bold">{{ number_format($summary[$metric['key']] ?? 0) }}</flux:heading>
    </div>
   </div>
  @endforeach
 </div>

 <div class="grid gap-8 lg:grid-cols-3">
  {{-- Satisfaction & sentiment --}}
  <div class="lg:col-span-2 space-y-8">
   <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mb-8 flex items-center justify-between">
     <flux:heading size="lg">{{ __('Customer Sentiment') }}</flux:heading>
     <flux:badge size="sm" color="zinc"inset="top bottom">{{ __('Live data') }}</flux:badge>
    </div>
    
    <div class="grid gap-8 sm:grid-cols-2">
     <div class="flex items-center gap-5">
      <div class="flex size-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40">
       <flux:icon icon="face-smile" class="size-8"/>
      </div>
      <div>
       <flux:text size="sm" class="font-medium text-zinc-500">{{ __('CSAT Score') }}</flux:text>
       <div class="flex items-baseline gap-1">
        <flux:heading size="xl" class="font-semibold">{{ number_format($satisfaction['average_csat'] ?? 0, 1) }}</flux:heading>
        <flux:text size="sm" class="text-zinc-400">/ 5.0</flux:text>
       </div>
      </div>
     </div>

     <div class="flex items-center gap-5">
      <div class="flex size-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40">
       <flux:icon icon="chart-bar-square" class="size-8"/>
      </div>
      <div>
       <flux:text size="sm" class="font-medium text-zinc-500">{{ __('Net Promoter Score') }}</flux:text>
       <flux:heading size="xl" class="font-semibold">{{ $satisfaction['nps_score'] ?? 0 }}</flux:heading>
      </div>
     </div>
    </div>

    <div class="mt-10 border-t border-zinc-100 pt-8 dark:border-zinc-800">
     <div class="flex items-center justify-between mb-3">
      <flux:text size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Distribution') }}</flux:text>
      <flux:text size="xs" class="text-zinc-400">{{ $satisfaction['responses'] ?? 0 }} {{ __('Responses') }}</flux:text>
     </div>
     <div class="flex h-4 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
      @php
       $total = max(1, ($satisfaction['promoters'] ?? 0) + ($satisfaction['passives'] ?? 0) + ($satisfaction['detractors'] ?? 0));
       $promotersPct = (($satisfaction['promoters'] ?? 0) / $total) * 100;
       $passivesPct = (($satisfaction['passives'] ?? 0) / $total) * 100;
       $detractorsPct = (($satisfaction['detractors'] ?? 0) / $total) * 100;
      @endphp
      <div style="width: {{ $promotersPct }}%" class="bg-emerald-500 transition-all duration-700"title="{{ __('Promoters') }}"></div>
      <div style="width: {{ $passivesPct }}%" class="bg-amber-400 transition-all duration-700"title="{{ __('Passives') }}"></div>
      <div style="width: {{ $detractorsPct }}%" class="bg-rose-500 transition-all duration-700"title="{{ __('Detractors') }}"></div>
     </div>
     <div class="mt-4 grid grid-cols-3 gap-2 text-center">
      <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/40">
       <flux:text size="xs" class="block font-bold text-emerald-600">{{ round($promotersPct) }}%</flux:text>
       <flux:text size="xs" variant="subtle">{{ __('Promoters') }}</flux:text>
      </div>
      <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/40">
       <flux:text size="xs" class="block font-bold text-amber-600">{{ round($passivesPct) }}%</flux:text>
       <flux:text size="xs" variant="subtle">{{ __('Passives') }}</flux:text>
      </div>
      <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/40">
       <flux:text size="xs" class="block font-bold text-rose-600">{{ round($detractorsPct) }}%</flux:text>
       <flux:text size="xs" variant="subtle">{{ __('Detractors') }}</flux:text>
      </div>
     </div>
    </div>
   </section>

   <div class="grid gap-6 md:grid-cols-2">
    @foreach (['tickets_by_status' => __('Status Distribution'), 'tickets_by_department' => __('Volume by Department')] as $group => $label)
     <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <flux:heading size="md" class="mb-5">{{ $label }}</flux:heading>
      <div class="space-y-3">
       @forelse (($summary[$group] ?? []) as $statusLabel => $count)
        <div class="flex items-center justify-between gap-3">
         <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-1">
           <flux:text size="sm" class="truncate font-medium">{{ $statusLabel }}</flux:text>
           <flux:text size="xs" variant="subtle">{{ $count }}</flux:text>
          </div>
          <div class="h-1.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
           @php
            $maxVal = max(1, ...array_values($summary[$group] ?? [1]));
            $pct = ($count / $maxVal) * 100;
           @endphp
           <div style="width: {{ $pct }}%" class="h-full rounded-full bg-zinc-400 dark:bg-zinc-600"></div>
          </div>
         </div>
        </div>
       @empty
        <flux:text class="py-4 text-center italic" variant="subtle">{{ __('No activity.') }}</flux:text>
       @endforelse
      </div>
     </div>
    @endforeach
   </div>
  </div>

  {{-- Efficiency & Workload --}}
  <div class="space-y-8">
   <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="md" class="mb-6">{{ __('Operational Efficiency') }}</flux:heading>
    <div class="space-y-6">
     <div class="flex items-center justify-between p-4 rounded-xl bg-rose-50/50 dark:bg-rose-950/10 border border-rose-100 dark:border-rose-900/20">
      <div>
       <flux:text size="xs" class="font-bold text-rose-600 uppercase tracking-wider">{{ __('SLA Breaches') }}</flux:text>
       <flux:heading size="lg" class="mt-1">{{ $summary['sla_breaches'] ?? 0 }}</flux:heading>
      </div>
      <flux:icon icon="exclamation-circle" class="size-8 text-rose-500/50"/>
     </div>

     <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
      <flux:text size="xs" class="font-bold text-zinc-500 uppercase tracking-wider">{{ __('Quality Rating') }}</flux:text>
      <div class="mt-2 flex items-center gap-3">
       <flux:heading size="lg">{{ number_format($summary['average_ticket_rating'] ?? 0, 1) }}</flux:heading>
       <div class="flex text-amber-400">
        @for ($i = 1; $i <= 5; $i++)
         <flux:icon icon="star" variant="mini" class="{{ $i <= ($summary['average_ticket_rating'] ?? 0) ? 'fill-current' : 'text-zinc-200 dark:text-zinc-700' }}"/>
        @endfor
       </div>
      </div>
     </div>
    </div>
   </section>

   <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mb-6 flex items-center justify-between">
     <flux:heading size="md">{{ __('Agent Load') }}</flux:heading>
     <flux:text size="xs" variant="subtle">{{ __('Active tasks') }}</flux:text>
    </div>
    <div class="space-y-5">
     @forelse (($summary['agent_workload'] ?? []) as $agent => $count)
      <div class="group flex items-center gap-3">
       <flux:avatar :name="$agent" size="xs"/>
       <div class="min-w-0 flex-1">
        <div class="flex justify-between items-center mb-1">
         <flux:text size="sm" class="truncate font-semibold">{{ $agent }}</flux:text>
         <flux:text size="xs" class="font-bold" variant="subtle">{{ $count }}</flux:text>
        </div>
        <div class="h-1.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
         @php
          $maxWorkload = max(1, ...array_values($summary['agent_workload'] ?? [1]));
          $workloadPct = ($count / $maxWorkload) * 100;
         @endphp
         <div style="width: {{ $workloadPct }}%" class="h-full rounded-full bg-blue-500 transition-all duration-500"></div>
        </div>
       </div>
      </div>
     @empty
      <flux:text class="py-4 text-center italic" variant="subtle">{{ __('No agents assigned.') }}</flux:text>
     @endforelse
    </div>
   </section>
  </div>
 </div>

 {{-- Automated Reports --}}
 @can('reports.export')
  <section class="rounded-2xl bg-zinc-900 p-8 text-white dark:bg-zinc-800">
   <div class="mb-8 max-w-2xl">
    <flux:heading size="lg" class="text-white">{{ __('Automated Intelligence') }}</flux:heading>
    <flux:text class="mt-2 text-zinc-400">{{ __('Configure periodic delivery of these metrics directly to your stakeholder inboxes. Stay ahead of operational shifts with zero effort.') }}</flux:text>
   </div>

   <form wire:submit="createScheduledReport" class="grid gap-6 lg:grid-cols-12">
    <div class="lg:col-span-4">
     <flux:field>
      <flux:label class="text-zinc-300">{{ __('Report Title') }}</flux:label>
      <flux:input wire:model="scheduledName"placeholder="{{ __('e.g. Weekly Service Review') }}" class="!bg-zinc-800 !border-zinc-700 !text-white placeholder:text-zinc-500"/>
      <flux:error name="scheduledName"/>
     </flux:field>
    </div>

    <div class="lg:col-span-2">
     <flux:field>
      <flux:label class="text-zinc-300">{{ __('Interval') }}</flux:label>
      <flux:select wire:model="scheduledFrequency" class="!bg-zinc-800 !border-zinc-700 !text-white">
       <flux:select.option value="weekly">{{ __('Weekly') }}</flux:select.option>
       <flux:select.option value="monthly">{{ __('Monthly') }}</flux:select.option>
      </flux:select>
      <flux:error name="scheduledFrequency"/>
     </flux:field>
    </div>

    <div class="lg:col-span-2">
     <flux:field>
      <flux:label class="text-zinc-300">{{ __('File Type') }}</flux:label>
      <flux:select wire:model="scheduledFormat" class="!bg-zinc-800 !border-zinc-700 !text-white">
       <flux:select.option value="pdf">{{ __('PDF Document') }}</flux:select.option>
       <flux:select.option value="excel">{{ __('Excel Spreadsheet') }}</flux:select.option>
      </flux:select>
      <flux:error name="scheduledFormat"/>
     </flux:field>
    </div>

    <div class="lg:col-span-4">
     <flux:field>
      <flux:label class="text-zinc-300">{{ __('Stakeholder Emails') }}</flux:label>
      <flux:input wire:model="scheduledRecipients"placeholder="{{ __('comma separated emails...') }}" class="!bg-zinc-800 !border-zinc-700 !text-white placeholder:text-zinc-500"/>
      <flux:error name="scheduledRecipients"/>
     </flux:field>
    </div>

    <div class="lg:col-span-12 flex justify-end mt-2">
     <flux:button type="submit" variant="primary" icon="bolt" class="px-8">{{ __('Deploy Automation') }}</flux:button>
    </div>
   </form>
  </section>
 @endcan
</div>


