<?php

namespace Modules\Dialer\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DialerCampaignUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $domainUuid,
        public array $payload
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dialer.domain.' . $this->domainUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dialer.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
