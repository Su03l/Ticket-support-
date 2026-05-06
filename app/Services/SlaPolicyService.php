<?php

namespace App\Services;

use App\Enums\SlaAppliesTo;
use App\Models\Company;
use App\Models\SlaPolicy;

class SlaPolicyService
{
    public function createPolicy(
        Company $company,
        string $name,
        SlaAppliesTo $appliesTo,
        ?int $priorityId = null,
        ?int $firstResponseMinutes = null,
        ?int $resolutionMinutes = null,
        ?int $escalationMinutes = null,
    ): SlaPolicy {
        return SlaPolicy::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'applies_to' => $appliesTo,
            'priority_id' => $priorityId,
            'first_response_minutes' => $firstResponseMinutes,
            'resolution_minutes' => $resolutionMinutes,
            'escalation_minutes' => $escalationMinutes,
            'is_active' => true,
        ]);
    }

    public function matchingPolicy(object $slable): ?SlaPolicy
    {
        $appliesTo = SlaTrackingService::appliesToFor($slable);

        if ($appliesTo === null) {
            return null;
        }

        return SlaPolicy::query()
            ->where('company_id', $slable->company_id)
            ->where('applies_to', $appliesTo)
            ->where('is_active', true)
            ->when($slable->getAttribute('priority_id') !== null, fn ($query) => $query->where(fn ($query) => $query->whereNull('priority_id')->orWhere('priority_id', $slable->getAttribute('priority_id'))))
            ->orderByRaw('priority_id is null')
            ->first();
    }
}
