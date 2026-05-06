<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;

class WebhookService
{
    /**
     * إرسال الحدث إلى جميع الـ Webhooks النشطة للشركة
     */
    public function dispatchEvent(int $companyId, string $event, array $payload): void
    {
        $webhooks = Webhook::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            // يفضل تشغيلها داخل طابور (Queue/Job) في المستقبل
            Http::timeout(5)->post($webhook->url, [
                'event' => $event,
                'payload' => $payload,
            ]);
        }
    }
}