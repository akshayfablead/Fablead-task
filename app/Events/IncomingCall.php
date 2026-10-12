<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncomingCall implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recipientId,
        public int $callerId,
        public string $callerName,
        public string $callType,
        public string $callId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->recipientId)];
    }

    public function broadcastAs(): string
    {
        return 'incoming.call';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callId,
            'caller_id' => $this->callerId,
            'caller_name' => $this->callerName,
            'call_type' => $this->callType,
        ];
    }
}
