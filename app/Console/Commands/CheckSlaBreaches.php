<?php

namespace App\Console\Commands;

use App\Services\SlaTrackingService;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    // command signature 
    protected $signature = 'sla:check-breaches';

    protected $description = 'Check active SLA records and escalate breached items.';

    public function handle(SlaTrackingService $slaTracking): int
    {
        $count = $slaTracking->checkBreaches();

        $this->info("SLA breaches processed: {$count}");

        return self::SUCCESS;
    }
}
