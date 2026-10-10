<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recipientId,
        public int $senderId,
        public string $callId,
        public string $signalType,
        public array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callId,
            'sender_id' => $this->senderId,
            'signal_type' => $this->signalType,
            'payload' => $this->payload,
        ];
    }
}
