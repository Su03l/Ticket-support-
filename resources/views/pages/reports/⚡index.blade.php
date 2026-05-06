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

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Reports') }}</flux:heading>
        <flux:text>{{ __('Operational overview scoped to your role.') }}</flux:text>
    </div>
    <div class="grid gap-4 md:grid-cols-4">
        @foreach (['total_tickets', 'open_tickets', 'closed_tickets', 'overdue_tickets', 'sla_breaches', 'average_ticket_rating'] as $metric)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase text-zinc-500">{{ __(str_replace('_', ' ', $metric)) }}</flux:text>
                <flux:heading size="xl">{{ is_numeric($summary[$metric] ?? null) ? round((float) $summary[$metric], 2) : ($summary[$metric] ?? 0) }}</flux:heading>
            </div>
        @endforeach
    </div>
    <div class="grid gap-4 md:grid-cols-4">
        @foreach (['average_csat', 'nps_score', 'promoters', 'detractors'] as $metric)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase text-zinc-500">{{ __(str_replace('_', ' ', $metric)) }}</flux:text>
                <flux:heading size="xl">{{ $satisfaction[$metric] ?? 0 }}</flux:heading>
            </div>
        @endforeach
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach (['tickets_by_status', 'tickets_by_department', 'complaints_by_severity', 'inquiries_by_status', 'agent_workload'] as $group)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="sm">{{ __(str_replace('_', ' ', $group)) }}</flux:heading>
                <div class="mt-3 flex flex-col gap-2">
                    @forelse (($summary[$group] ?? []) as $label => $count)
                        <div class="flex items-center justify-between gap-3 text-sm"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                    @empty
                        <flux:text>{{ __('No data yet.') }}</flux:text>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
    @can('reports.export')
        <form wire:submit="createScheduledReport" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-1">
                <flux:heading size="sm">{{ __('Scheduled reports') }}</flux:heading>
                <flux:text>{{ __('Weekly or monthly delivery to managers.') }}</flux:text>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-4">
                <flux:input wire:model="scheduledName" :label="__('Report name')" />
                <flux:select wire:model="scheduledFrequency" :label="__('Frequency')">
                    <flux:select.option value="weekly">{{ __('Weekly') }}</flux:select.option>
                    <flux:select.option value="monthly">{{ __('Monthly') }}</flux:select.option>
                </flux:select>
                <flux:select wire:model="scheduledFormat" :label="__('Format')">
                    <flux:select.option value="pdf">{{ __('PDF') }}</flux:select.option>
                    <flux:select.option value="excel">{{ __('Excel') }}</flux:select.option>
                </flux:select>
                <flux:input wire:model="scheduledRecipients" :label="__('Recipients')" placeholder="manager@example.com" />
            </div>
            <div class="mt-4 flex justify-end">
                <flux:button type="submit" variant="primary" icon="calendar-days">{{ __('Schedule report') }}</flux:button>
            </div>
        </form>
    @endcan
</div>
