<?php

namespace Modules\Dialer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\Dialer\Models\DialerCampaign;

class DispatchDialerWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public string $campaignUuid,
        public array $payload
    ) {}

    public function handle(): void
    {
        $campaign = DialerCampaign::query()->find($this->campaignUuid);

        if (! $campaign || ! $campaign->webhook_url) {
            return;
        }

        $request = Http::timeout(15)
            ->acceptJson()
            ->withHeaders([
                'X-FSPBX-Event' => data_get($this->payload, 'event', 'dialer.updated'),
            ]);

        if ($campaign->webhook_secret) {
            $request = $request->withHeaders([
                'X-FSPBX-Signature' => hash_hmac('sha256', json_encode($this->payload), $campaign->webhook_secret),
            ]);
        }

        $request->post($campaign->webhook_url, $this->payload);
    }
}
