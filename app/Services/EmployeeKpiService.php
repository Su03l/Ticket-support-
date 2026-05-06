<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\CustomerSatisfactionSurvey;
use App\Models\EmployeeKpiTarget;
use App\Models\Ticket;
use App\Models\TicketTimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeeKpiService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function monthlyDashboard(User $viewer, int $month, int $year): Collection
    {
        $companyId = (int) $viewer->company_id;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return User::query()
            ->where('company_id', $companyId)
            ->whereNotNull('department_id')
            ->orderBy('name')
            ->get()
            ->map(function (User $agent) use ($companyId, $month, $year, $start, $end): array {
                $resolvedTickets = Ticket::query()
                    ->where('company_id', $companyId)
                    ->where('assigned_to_id', $agent->id)
                    ->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
                    ->whereBetween('resolved_at', [$start, $end])
                    ->count();

                $trackedSeconds = (int) TicketTimeEntry::query()
                    ->where('company_id', $companyId)
                    ->where('user_id', $agent->id)
                    ->whereBetween('started_at', [$start, $end])
                    ->sum('duration_seconds');

                $averageCsat = (float) CustomerSatisfactionSurvey::query()
                    ->where('company_id', $companyId)
                    ->where('agent_id', $agent->id)
                    ->whereNotNull('submitted_at')
                    ->whereBetween('submitted_at', [$start, $end])
                    ->avg('csat_score');

                $target = EmployeeKpiTarget::query()
                    ->where('company_id', $companyId)
                    ->where('user_id', $agent->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                $resolvedTarget = max(1, $target?->tickets_resolved_target ?? 20);
                $csatTarget = max(1, (float) ($target?->csat_target ?? 4));

                return [
                    'agent' => $agent,
                    'target' => $target,
                    'resolved_tickets' => $resolvedTickets,
                    'tracked_hours' => round($trackedSeconds / 3600, 2),
                    'average_csat' => round($averageCsat, 2),
                    'kpi_score' => min(100, (int) round((($resolvedTickets / $resolvedTarget) * 60) + (($averageCsat / $csatTarget) * 40))),
                ];
            });
    }

    public function saveTarget(User $manager, User $agent, int $month, int $year, int $resolvedTarget, int $firstResponseTarget, float $csatTarget, float $qualityTarget): EmployeeKpiTarget
    {
        return EmployeeKpiTarget::query()->updateOrCreate([
            'company_id' => $agent->company_id,
            'user_id' => $agent->id,
            'month' => $month,
            'year' => $year,
        ], [
            'managed_by_id' => $manager->id,
            'tickets_resolved_target' => $resolvedTarget,
            'first_response_minutes_target' => $firstResponseTarget,
            'csat_target' => $csatTarget,
            'quality_score_target' => $qualityTarget,
        ]);
    }
}
