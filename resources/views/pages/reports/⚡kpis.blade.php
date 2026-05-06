<?php

use App\Models\User;
use App\Services\EmployeeKpiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('HR KPI')] class extends Component
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

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <flux:heading size="xl">{{ __('HR KPI') }}</flux:heading>
            <flux:text>{{ __('Agent productivity, time tracking, and CSAT indicators.') }}</flux:text>
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <flux:input type="number" min="1" max="12" wire:model.live="month" :label="__('Month')" />
            <flux:input type="number" min="2024" max="2100" wire:model.live="year" :label="__('Year')" />
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($rows as $row)
            <div wire:key="kpi-agent-{{ $row['agent']->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="sm">{{ $row['agent']->name }}</flux:heading>
                        <flux:text>{{ __('KPI score') }}</flux:text>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-full border-4 border-emerald-500 text-lg font-semibold">
                        {{ $row['kpi_score'] }}
                    </div>
                </div>
                <div class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3"><span>{{ __('Resolved tickets') }}</span><strong>{{ $row['resolved_tickets'] }}</strong></div>
                    <div class="flex items-center justify-between gap-3"><span>{{ __('Tracked hours') }}</span><strong>{{ $row['tracked_hours'] }}</strong></div>
                    <div class="flex items-center justify-between gap-3"><span>{{ __('Average CSAT') }}</span><strong>{{ $row['average_csat'] }}</strong></div>
                </div>
            </div>
        @endforeach
    </div>

    @can('reports.export')
        <form wire:submit="saveTarget" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="sm">{{ __('KPI targets') }}</flux:heading>
            <div class="mt-4 grid gap-3 md:grid-cols-5">
                <flux:select wire:model="agentId" :label="__('Agent')">
                    <flux:select.option value="">{{ __('Select agent') }}</flux:select.option>
                    @foreach ($agents as $agent)
                        <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="number" wire:model="ticketsResolvedTarget" :label="__('Resolved target')" />
                <flux:input type="number" wire:model="firstResponseMinutesTarget" :label="__('Response minutes')" />
                <flux:input type="number" step="0.01" wire:model="csatTarget" :label="__('CSAT target')" />
                <flux:input type="number" step="0.01" wire:model="qualityScoreTarget" :label="__('Quality target')" />
            </div>
            <div class="mt-4 flex justify-end">
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save target') }}</flux:button>
            </div>
        </form>
    @endcan
</div>
