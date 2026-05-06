<?php

namespace App\Services;

use App\Enums\SlaAppliesTo;
use App\Enums\SlaStatus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Inquiry;
use App\Models\SlaRecord;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonInterface;

class SlaTrackingService
{
    public function __construct(
        private SlaPolicyService $policies,
        private WorkingHoursService $workingHours,
        private EscalationService $escalations,
    ) {}

    public static function appliesToFor(object $slable): ?SlaAppliesTo
    {
        return match (true) {
            $slable instanceof Ticket => SlaAppliesTo::Tickets,
            $slable instanceof Complaint => SlaAppliesTo::Complaints,
            $slable instanceof Inquiry => SlaAppliesTo::Inquiries,
            default => null,
        };
    }

    public function attachTo(Model $slable): ?SlaRecord
    {
        $policy = $this->policies->matchingPolicy($slable);

        if ($policy === null) {
            return null;
        }

        /** @var Company $company */
        $company = $slable->company;

        return SlaRecord::query()->firstOrCreate([
            'slable_type' => $slable::class,
            'slable_id' => $slable->id,
        ], [
            'company_id' => $slable->company_id,
            'policy_id' => $policy->id,
            'first_response_due_at' => $policy->first_response_minutes ? $this->workingHours->dueAt($company, $policy->first_response_minutes) : null,
            'resolution_due_at' => $policy->resolution_minutes ? $this->workingHours->dueAt($company, $policy->resolution_minutes) : null,
            'status' => SlaStatus::Active,
        ]);
    }

    public function markFirstResponse(Model $slable, ?CarbonInterface $when = null): ?SlaRecord
    {
        $record = $this->recordFor($slable);

        if ($record === null || $record->first_responded_at !== null) {
            return $record;
        }

        $record->forceFill(['first_responded_at' => $when ?? now()])->save();

        return $record->refresh();
    }

    public function markResolved(Model $slable, ?CarbonInterface $when = null): ?SlaRecord
    {
        $record = $this->recordFor($slable);

        if ($record === null) {
            return null;
        }

        $record->forceFill([
            'resolved_at' => $when ?? now(),
            'status' => $record->status === SlaStatus::Breached ? SlaStatus::Breached : SlaStatus::Met,
        ])->save();

        return $record->refresh();
    }

    public function checkBreaches(): int
    {
        $count = 0;

        SlaRecord::query()
            ->with('slable')
            ->where('status', SlaStatus::Active)
            ->where(function ($query): void {
                $query->where(fn ($query) => $query->whereNull('first_responded_at')->whereNotNull('first_response_due_at')->where('first_response_due_at', '<', now()))
                    ->orWhere(fn ($query) => $query->whereNull('resolved_at')->whereNotNull('resolution_due_at')->where('resolution_due_at', '<', now()));
            })
            ->orderBy('id')
            ->chunkById(100, function ($records) use (&$count): void {
                foreach ($records as $record) {
                    $attributes = ['status' => SlaStatus::Breached];

                    if ($record->first_responded_at === null && $record->first_response_due_at?->isPast() && $record->breached_first_response_at === null) {
                        $attributes['breached_first_response_at'] = now();
                    }

                    if ($record->resolved_at === null && $record->resolution_due_at?->isPast() && $record->breached_resolution_at === null) {
                        $attributes['breached_resolution_at'] = now();
                    }

                    $record->forceFill($attributes)->save();

                    if ($record->slable instanceof Model) {
                        $this->escalations->escalate($record->slable, reason: 'SLA breach detected.');
                    }

                    activity()->performedOn($record)->event('sla.breached')->log('SLA breached');
                    $count++;
                }
            });

        return $count;
    }

    private function recordFor(Model $slable): ?SlaRecord
    {
        return SlaRecord::query()
            ->where('slable_type', $slable::class)
            ->where('slable_id', $slable->id)
            ->first();
    }
}
