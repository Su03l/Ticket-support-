<?php

namespace App\Services;

use App\Enums\EscalationStatus;
use App\Enums\MailboxMessageType;
use App\Enums\UserType;
use App\Models\Escalation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EscalationService
{
    public function __construct(
        private MailboxService $mailbox,
        private NotificationService $notifications,
    ) {}

    public function escalate(Model $escalatable, ?User $actor = null, ?User $target = null, ?string $reason = null): Escalation
    {
        $target ??= User::query()
            ->where('company_id', $escalatable->company_id)
            ->where('user_type', UserType::CompanyAdmin)
            ->first();

        $escalation = Escalation::query()->create([
            'company_id' => $escalatable->company_id,
            'escalatable_type' => $escalatable::class,
            'escalatable_id' => $escalatable->id,
            'escalated_by_id' => $actor?->id,
            'escalated_to_id' => $target?->id,
            'reason' => $reason,
            'status' => EscalationStatus::Open,
            'escalated_at' => now(),
        ]);

        if ($target !== null) {
            $this->notifications->notify(
                recipient: $target,
                type: 'sla.escalated',
                title: 'SLA escalation',
                body: $reason ?: 'An item requires escalation review.',
                link: $this->linkFor($escalatable),
                company: $escalatable->company,
            );

            $this->mailbox->send(
                recipient: $target,
                subject: 'SLA escalation',
                body: $reason ?: 'An item requires escalation review.',
                sender: $actor,
                type: MailboxMessageType::Escalation,
                relatedType: class_basename($escalatable),
                relatedId: $escalatable->id,
                companyId: $escalatable->company_id,
            );
        }

        activity()->performedOn($escalation)->causedBy($actor)->event('sla.escalated')->log('SLA escalation created');

        return $escalation;
    }

    private function linkFor(Model $model): ?string
    {
        return match (class_basename($model)) {
            'Ticket' => route('tickets.show', $model),
            'Complaint' => route('complaints.show', $model),
            'Inquiry' => route('inquiries.show', $model),
            default => null,
        };
    }
}
