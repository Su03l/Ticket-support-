<?php

namespace App\Services;

use App\Enums\NpsCategory;
use App\Models\CustomerSatisfactionSurvey;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerSatisfactionService
{
    public function createForClosedTicket(Ticket $ticket): CustomerSatisfactionSurvey
    {
        if (! $ticket->isClosed()) {
            throw new InvalidArgumentException('Satisfaction surveys can only be created for closed tickets.');
        }

        return CustomerSatisfactionSurvey::query()->firstOrCreate([
            'ticket_id' => $ticket->id,
        ], [
            'company_id' => $ticket->company_id,
            'customer_id' => $ticket->customer_id,
            'agent_id' => $ticket->assigned_to_id,
            'department_id' => $ticket->department_id,
            'sent_at' => now(),
        ]);
    }

    public function submit(CustomerSatisfactionSurvey $survey, int $csatScore, int $npsScore, ?string $feedback = null): CustomerSatisfactionSurvey
    {
        if ($csatScore < 1 || $csatScore > 5 || $npsScore < 0 || $npsScore > 10) {
            throw new InvalidArgumentException('Survey scores are outside the allowed range.');
        }

        $survey->forceFill([
            'csat_score' => $csatScore,
            'nps_score' => $npsScore,
            'nps_category' => NpsCategory::fromScore($npsScore),
            'feedback' => $feedback,
            'submitted_at' => now(),
        ])->save();

        return $survey->refresh();
    }

    /**
     * @return array{average_csat: float, nps_score: int, promoters: int, passives: int, detractors: int, responses: int}
     */
    public function dashboard(int $companyId): array
    {
        $responses = CustomerSatisfactionSurvey::query()
            ->where('company_id', $companyId)
            ->whereNotNull('submitted_at');

        $counts = (clone $responses)
            ->select('nps_category', DB::raw('count(*) as aggregate'))
            ->groupBy('nps_category')
            ->pluck('aggregate', 'nps_category');

        $total = (clone $responses)->count();
        $promoters = (int) ($counts[NpsCategory::Promoter->value] ?? 0);
        $passives = (int) ($counts[NpsCategory::Passive->value] ?? 0);
        $detractors = (int) ($counts[NpsCategory::Detractor->value] ?? 0);

        return [
            'average_csat' => round((float) (clone $responses)->avg('csat_score'), 2),
            'nps_score' => $total === 0 ? 0 : (int) round((($promoters - $detractors) / $total) * 100),
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'responses' => $total,
        ];
    }
}
