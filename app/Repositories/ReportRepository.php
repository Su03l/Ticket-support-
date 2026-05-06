<?php

namespace App\Repositories;

use App\Enums\SlaStatus;
use App\Enums\TicketStatus;
use App\Models\Complaint;
use App\Models\Inquiry;
use App\Models\SlaRecord;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;

class ReportRepository implements ReportRepositoryInterface
{
    public function summaryForUser(User $user, array $filters = []): array
    {
        $ticketQuery = $this->scopeTicketQuery(Ticket::query(), $user, $filters);
        $complaintQuery = $this->scopeCompanyQuery(Complaint::query(), $user, $filters);
        $inquiryQuery = $this->scopeCompanyQuery(Inquiry::query(), $user, $filters);
        $slaQuery = $this->scopeCompanyQuery(SlaRecord::query(), $user, $filters);

        return [
            'total_tickets' => (clone $ticketQuery)->count(),
            'open_tickets' => (clone $ticketQuery)->whereNotIn('status', [TicketStatus::Closed, TicketStatus::Cancelled])->count(),
            'closed_tickets' => (clone $ticketQuery)->where('status', TicketStatus::Closed)->count(),
            'overdue_tickets' => (clone $slaQuery)->where('status', SlaStatus::Breached)->where('slable_type', Ticket::class)->count(),
            'tickets_by_status' => (clone $ticketQuery)->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')->all(),
            'tickets_by_department' => (clone $ticketQuery)->join('departments', 'tickets.department_id', '=', 'departments.id')->selectRaw('departments.name, count(*) as aggregate')->groupBy('departments.name')->pluck('aggregate', 'departments.name')->all(),
            'complaints_by_severity' => (clone $complaintQuery)->selectRaw('severity, count(*) as aggregate')->groupBy('severity')->pluck('aggregate', 'severity')->all(),
            'inquiries_by_status' => (clone $inquiryQuery)->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')->all(),
            'sla_breaches' => (clone $slaQuery)->where('status', SlaStatus::Breached)->count(),
            'agent_workload' => (clone $ticketQuery)->whereNotNull('assigned_to_id')->join('users', 'tickets.assigned_to_id', '=', 'users.id')->selectRaw('users.name, count(*) as aggregate')->groupBy('users.name')->pluck('aggregate', 'users.name')->all(),
            'average_ticket_rating' => $this->scopeCompanyQuery(TicketRating::query(), $user, $filters)->avg('rating'),
        ];
    }

    private function scopeTicketQuery($query, User $user, array $filters)
    {
        if ($user->company_id !== null) {
            $query->where('tickets.company_id', $user->company_id);
        }

        if ($user->can('tickets.view.department') && ! $user->can('tickets.view') && $user->department_id !== null) {
            $query->where('tickets.department_id', $user->department_id);
        }

        if ($user->can('tickets.view.assigned') && ! $user->can('tickets.view.department') && ! $user->can('tickets.view')) {
            $query->where('tickets.assigned_to_id', $user->id);
        }

        return $this->applyCommonFilters($query, $filters, 'tickets');
    }

    private function scopeCompanyQuery($query, User $user, array $filters)
    {
        if ($user->company_id !== null) {
            $query->where('company_id', $user->company_id);
        }

        return $this->applyCommonFilters($query, $filters);
    }

    private function applyCommonFilters($query, array $filters, ?string $table = null)
    {
        $prefix = $table ? "{$table}." : '';

        return $query
            ->when(($filters['department_id'] ?? null), fn ($query, $departmentId) => $query->where($prefix.'department_id', $departmentId))
            ->when(($filters['agent_id'] ?? null) && $table === 'tickets', fn ($query, $agentId) => $query->where($prefix.'assigned_to_id', $agentId))
            ->when(($filters['date_from'] ?? null), fn ($query, $date) => $query->whereDate($prefix.'created_at', '>=', $date))
            ->when(($filters['date_to'] ?? null), fn ($query, $date) => $query->whereDate($prefix.'created_at', '<=', $date));
    }
}
