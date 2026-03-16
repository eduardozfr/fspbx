<?php

namespace Modules\CallCenter\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallCenterWallboardUpdated implements ShouldBroadcastNow
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
            new PrivateChannel('call-center.domain.' . $this->domainUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call-center.wallboard.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
