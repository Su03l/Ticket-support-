<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use App\Services\ScheduledReportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('reports:send-scheduled')]
#[Description('Send due scheduled support reports to configured recipients.')]
class SendScheduledReports extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ScheduledReportService $scheduledReports, ReportService $reports): int
    {
        $sent = 0;

        foreach ($scheduledReports->due() as $scheduledReport) {
            $summary = $reports->dashboard($scheduledReport->createdBy);
            $body = view('emails.scheduled-report', [
                'report' => $scheduledReport,
                'summary' => $summary,
            ])->render();

            foreach ($scheduledReport->recipients as $recipient) {
                Mail::html($body, function ($message) use ($scheduledReport, $recipient): void {
                    $message->to($recipient)->subject($scheduledReport->name);
                });
            }

            $scheduledReports->markSent($scheduledReport);
            $sent++;
        }

        $this->info("Sent {$sent} scheduled report(s).");

        return self::SUCCESS;
    }
}
